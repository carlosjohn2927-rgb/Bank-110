<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    /**
     * /login — Administrator sign-in (captcha-free, rate-limited).
     */
    public function login()
    {
        if ($this->user) redirect($this->user['role'] === 'admin' ? 'admin/dashboard' : 'dashboard');
        if ($this->input->method() === 'post') {
            if (!$this->db_ok()) {
                $this->session->set_flashdata('error', 'Our services are temporarily unavailable. Please try again shortly.');
                redirect('login');
            }
            $this->form_validation->set_rules('identity', 'Email or username', 'required|trim');
            $this->form_validation->set_rules('password', 'Password', 'required');
            if ($this->form_validation->run()) {
                $key = 'admin:'.($this->input->post('identity', TRUE));
                try {
                    if ($this->Bank_model->login_attempts($key) >= 5) {
                        $this->session->set_flashdata('error', 'Too many failed attempts. Please try again in 15 minutes.');
                        redirect('login');
                    }
                    $user = $this->Bank_model->authenticate($this->input->post('identity', TRUE), $this->input->post('password'), 'admin');
                    if ($user) {
                        $this->Bank_model->clear_login_attempts($key);
                        $this->establish_session($user);
                        redirect('admin/dashboard');
                    }
                    $this->Bank_model->record_login_attempt($key, FALSE);
                } catch (\Throwable $e) {
                    log_message('error', 'Admin login error: '.$e->getMessage());
                    $this->session->set_flashdata('error', 'Our services are temporarily unavailable. Please try again shortly.');
                    redirect('login');
                }
                $this->session->set_flashdata('error', 'Invalid administrator credentials.');
            } else {
                $this->session->set_flashdata('error', validation_errors(' ', ' '));
            }
            redirect('login');
        }
        try {
            $this->load->view('auth/login');
        } catch (\Throwable $e) {
            log_message('error', 'Failed to render admin login: '.$e->getMessage());
            show_error('Service temporarily unavailable', 500, 'Login error');
        }
    }

    /**
     * /user/login — Customer sign-in with bot-check captcha.
     */
    public function user_login()
    {
        if ($this->user) redirect($this->user['role'] === 'admin' ? 'admin/dashboard' : 'dashboard');
        try {
            if (!$this->session->userdata('captcha')) $this->refresh_captcha();
            $this->load->view('auth/user_login', array('captcha' => $this->session->userdata('captcha')));
        } catch (\Throwable $e) {
            log_message('error', 'Failed to render customer login: '.$e->getMessage());
            // Fallback: try to show a minimal login without captcha to avoid 500
            try {
                $this->session->set_userdata('captcha_verified', TRUE);
                $this->load->view('auth/user_login', array('captcha' => '00000'));
            } catch (\Throwable $e2) {
                show_error('Service temporarily unavailable', 500, 'Login error');
            }
        }
    }

    public function verify()
    {
        if (!$this->input->post()) redirect('user/login');
        $code = trim((string) $this->input->post('code', TRUE));
        $stored = (string) $this->session->userdata('captcha');
        // hash_equals requires same length; if lengths differ, treat as mismatch without fatal
        $valid = FALSE;
        if ($stored !== '' && $code !== '' && strlen($stored) === strlen($code)) {
            try {
                $valid = hash_equals($stored, $code);
            } catch (\Throwable $e) {
                $valid = ($stored === $code);
            }
        } else {
            $valid = ($stored !== '' && $stored === $code);
        }
        if (!$valid) {
            $this->session->set_flashdata('error', 'The verification code does not match.');
            $this->refresh_captcha();
            redirect('user/login');
        }
        $this->session->set_userdata('captcha_verified', TRUE);
        redirect('user/login?credentials=1');
    }

    public function customer_login()
    {
        if (!$this->session->userdata('captcha_verified')) redirect('user/login');
        if (!$this->db_ok()) {
            $this->session->set_flashdata('error', 'Our services are temporarily unavailable. Please try again shortly.');
            redirect('user/login?credentials=1');
        }
        $this->form_validation->set_rules('identity', 'Account number or email', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');
        if (!$this->form_validation->run()) { $this->session->set_flashdata('error', validation_errors('',' ')); redirect('user/login?credentials=1'); }
        // Brute-force protection: lock out after repeated failures.
        $key='customer:'.($this->input->post('identity',TRUE));
        try {
            if($this->Bank_model->login_attempts($key)>=5){$this->session->set_flashdata('error','Too many failed attempts. Please try again in 15 minutes.');redirect('user/login?credentials=1');}
            $user = $this->Bank_model->authenticate($this->input->post('identity', TRUE), $this->input->post('password'), 'customer');
            if (!$user) { $this->Bank_model->record_login_attempt($key,FALSE); $this->session->set_flashdata('error', 'Invalid login details or inactive account.'); redirect('user/login?credentials=1'); }
            $this->Bank_model->clear_login_attempts($key);
            // Two-factor authentication: if enabled, pause sign-in for a code.
            if (!empty($user['twofa_enabled'])) {
                $this->begin_twofa($user);
                return;
            }
            $this->establish_session($user);
            redirect('dashboard');
        } catch (\Throwable $e) {
            log_message('error', 'Customer login error: '.$e->getMessage());
            $this->session->set_flashdata('error', 'Our services are temporarily unavailable. Please try again shortly.');
            redirect('user/login?credentials=1');
        }
    }

    public function twofa()
    {
        $pending = $this->session->userdata('twofa_pending');
        if (!$pending || empty($pending['user'])) redirect('user/login');
        if ($this->input->method() === 'post') {
            if (!$this->db_ok()) {
                $this->session->set_flashdata('error', 'Our services are temporarily unavailable. Please try again shortly.');
                redirect('twofa');
            }
            $code = trim((string) $this->input->post('code', TRUE));
            $user = $pending['user'];
            $verified = FALSE;

            try {
                // TOTP (authenticator app) or backup code.
                if (!empty($user['totp_confirmed']) && !empty($user['totp_secret'])) {
                    $result = $this->Bank_model->totp_verify($user, $code);
                    if ($result === 'backup') {
                        // Refresh the user row in session since backup codes were cleared.
                        try {
                            $q = $this->db->where('id', $user['id'])->get('users');
                            $fresh = ($q !== FALSE) ? $q->row_array() : $user;
                            if ($fresh) $user = $fresh;
                        } catch (\Throwable $e) {}
                        $verified = TRUE;
                        $this->session->set_flashdata('success', 'Signed in with a backup code. Generate new backup codes in Settings → Security.');
                    } elseif ($result === 'totp') {
                        $verified = TRUE;
                    }
                }

                // Email OTP fallback (always allowed when 2FA is on).
                if (!$verified && !empty($pending['code'])) {
                    $pc = (string)$pending['code'];
                    if (strlen($pc) === strlen($code) && $pc !== '') {
                        if (hash_equals($pc, $code) && strtotime($pending['expires']) > time()) {
                            $verified = TRUE;
                        }
                    } elseif ($pc === $code && $pc !== '') {
                        if (strtotime($pending['expires']) > time()) $verified = TRUE;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '2FA verification error: '.$e->getMessage());
            }

            if ($verified) {
                $this->session->unset_userdata('twofa_pending');
                $this->establish_session($user);
                redirect('dashboard');
            }
            $this->session->set_flashdata('error', 'That code is incorrect or has expired.');
            redirect('twofa');
        }

        $has_totp = !empty($pending['user']['totp_confirmed']);
        $this->load->view('auth/twofa', array(
            'masked_email' => $this->mask_email($pending['user']['email']),
            'has_totp'     => $has_totp,
            'method'       => $has_totp ? 'totp' : 'email',
        ));
    }

    public function resend_twofa()
    {
        $pending = $this->session->userdata('twofa_pending');
        if (!$pending || empty($pending['user'])) redirect('user/login');
        $this->dispatch_otp($pending['user']);
        $this->session->set_flashdata('success', 'A new code has been sent to your email.');
        redirect('twofa');
    }

    private function begin_twofa($user)
    {
        $pending = array('user' => $user);
        // If the user uses an authenticator app we don't email a code up front —
        // they'll enter the TOTP code. Email OTP remains available as fallback.
        if (empty($user['totp_confirmed'])) {
            $code = (string) random_int(100000, 999999);
            $pending['code'] = $code;
            $pending['expires'] = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            if (function_exists('send_notification_email')) {
                try {
                    send_notification_email($user['email'], 'Your NorthWest sign-in code', '<p>Your verification code is:</p><p style="font-size:26px;font-weight:800;letter-spacing:3px;color:#1468e5">'.$code.'</p><p>It expires in 5 minutes. If you didn\'t attempt to sign in, please contact support immediately.</p>');
                } catch (\Throwable $e) {
                    log_message('error', 'Failed to send 2FA email: '.$e->getMessage());
                }
            }
        }
        $this->session->set_userdata('twofa_pending', $pending);
        redirect('twofa');
    }

    private function dispatch_otp($user)
    {
        $code = (string) random_int(100000, 999999);
        $pending = $this->session->userdata('twofa_pending') ?: array();
        $pending['code'] = $code;
        $pending['expires'] = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $pending['user'] = $user;
        $this->session->set_userdata('twofa_pending', $pending);
        if (function_exists('send_notification_email')) {
            try {
                send_notification_email($user['email'], 'Your NorthWest sign-in code', '<p>Your verification code is:</p><p style="font-size:26px;font-weight:800;letter-spacing:3px;color:#1468e5">'.$code.'</p><p>It expires in 5 minutes. If you didn\'t attempt to sign in, please contact support immediately.</p>');
            } catch (\Throwable $e) {
                log_message('error', 'Failed to dispatch OTP: '.$e->getMessage());
            }
        }
        return $code;
    }

    private function mask_email($email)
    {
        $parts = explode('@', (string)$email);
        if (count($parts) !== 2) return 'your email';
        $name = $parts[0];
        $masked = substr($name,0,2).str_repeat('•',max(2,strlen($name)-4)).substr($name,-1);
        return $masked.'@'.$parts[1];
    }

    public function admin()
    {
        // /admin kept as an alias for the administrator sign-in.
        redirect('login');
    }

    public function forgot()
    {
        if ($this->user) redirect($this->user['role'] === 'admin' ? 'admin/dashboard' : 'dashboard');
        if ($this->input->method() === 'post') {
            if (!$this->db_ok()) {
                $this->session->set_flashdata('error', 'Our services are temporarily unavailable. Please try again shortly.');
                redirect('forgot');
            }
            $this->form_validation->set_rules('email','Email','required|valid_email');
            if ($this->form_validation->run()) {
                try {
                    $token = $this->Bank_model->create_password_reset($this->input->post('email', TRUE));
                    if ($token) {
                        $this->Bank_model->audit('password_reset_requested','Password reset link generated');
                        $reset_url = site_url('reset/'.$token);
                        $sent = FALSE;
                        if (function_exists('send_notification_email')) {
                            try {
                                $sent = send_notification_email($this->input->post('email', TRUE), 'Reset your NorthWest password', '<p>Use this secure link to reset your password. It expires in 30 minutes:</p><p><a href="'.htmlspecialchars($reset_url).'" style="display:inline-block;background:#1468e5;color:#fff;padding:11px 18px;border-radius:8px;text-decoration:none;font-weight:700">Reset password</a></p><p>If you didn\'t request this, you can safely ignore this email.</p>');
                            } catch (\Throwable $e) {}
                        }
                        // If email isn't configured, still surface the link once so the flow is usable.
                        if (!$sent) $this->session->set_flashdata('reset_link', $reset_url);
                        redirect('forgot?sent=1');
                    }
                    $this->session->set_flashdata('error', 'No active account is associated with that email.');
                } catch (\Throwable $e) {
                    log_message('error', 'Forgot password error: '.$e->getMessage());
                    $this->session->set_flashdata('error', 'Our services are temporarily unavailable.');
                }
            } else $this->session->set_flashdata('error', validation_errors('',' '));
            redirect('forgot');
        }
        $this->load->view('auth/forgot');
    }

    public function reset($token = NULL)
    {
        if ($this->user) redirect('dashboard');
        if (!$token) redirect('forgot');
        if ($this->input->method() === 'post') {
            if (!$this->db_ok()) {
                $this->session->set_flashdata('error', 'Our services are temporarily unavailable. Please try again shortly.');
                redirect('reset/'.$token);
            }
            $this->form_validation->set_rules('password','New password','required|min_length[8]');
            $this->form_validation->set_rules('confirm','Confirm password','required|matches[password]');
            try {
                if ($this->form_validation->run() && $this->Bank_model->complete_password_reset($token, $this->input->post('password'))) {
                    $this->Bank_model->audit('password_reset_completed','Password reset completed');
                    $this->session->set_flashdata('success','Your password has been updated. Please sign in.');
                    redirect('user/login');
                }
                $this->session->set_flashdata('error', $this->Bank_model->get_password_reset($token) ? validation_errors('',' ') : 'This reset link is invalid or has expired.');
            } catch (\Throwable $e) {
                log_message('error', 'Password reset error: '.$e->getMessage());
                $this->session->set_flashdata('error', 'Unable to reset password at this time.');
            }
            redirect('reset/'.$token);
        }
        try {
            if (!$this->Bank_model->get_password_reset($token)) { show_404(); }
        } catch (\Throwable $e) {
            show_404();
        }
        $this->load->view('auth/reset', array('token' => $token));
    }

    /** Return to the administrator session after using Login as customer. */
    public function return_to_admin()
    {
        $impersonation = $this->session->userdata('impersonation_admin');
        if (!$impersonation) {
            redirect($this->user && ($this->user['role'] ?? '') === 'admin' ? 'admin/dashboard' : 'user/login');
        }
        try {
            $administrator = $this->Bank_model->user_by_id((int) $impersonation['id'], 'admin');
        } catch (\Throwable $e) {
            $administrator = NULL;
        }
        if (!$administrator || $administrator['status'] !== 'active') {
            $this->session->sess_destroy();
            redirect('login');
        }
        $customer_id = $this->user['id'] ?? 0;
        try { $this->Bank_model->audit('admin_impersonation_end', 'Administrator returned from customer #'.$customer_id, $administrator['id']); } catch (\Throwable $e) {}
        try { $this->session->sess_regenerate(TRUE); } catch (\Throwable $e) {}
        unset($administrator['password_hash']);
        $this->session->set_userdata('user', $administrator);
        $secret=(string)$this->config->item('auth_secret');
        $this->session->set_userdata('auth_signature',hash_hmac('sha256',$administrator['id'].'|'.$administrator['role'].'|'.$administrator['created_at'],$secret));
        $this->session->unset_userdata('impersonation_admin');
        redirect('admin/dashboard');
    }

    public function logout()
    {
        $role = $this->user['role'] ?? 'customer';
        try { $this->Bank_model->audit('logout', 'User signed out', $this->user['id'] ?? NULL); } catch (\Throwable $e) {}
        $this->session->sess_destroy();
        redirect($role === 'admin' ? 'login' : 'user/login');
    }

    private function establish_session($user)
    {
        try { $this->session->sess_regenerate(TRUE); } catch (\Throwable $e) { log_message('error', 'sess_regenerate failed: '.$e->getMessage()); }
        unset($user['password_hash']);
        $this->session->set_userdata('user', $user);
        try { $prefs=$this->Bank_model->preferences($user['id']); if(!empty($prefs['language'])) $this->session->set_userdata('language',$prefs['language']); } catch (\Throwable $e) {}
        $auth_secret = (string) $this->config->item('auth_secret');
        $created = $user['created_at'] ?? '';
        $this->session->set_userdata('auth_signature', hash_hmac('sha256', $user['id'].'|'.$user['role'].'|'.$created, $auth_secret));
        $this->session->unset_userdata(array('captcha', 'captcha_verified'));
        try { $this->Bank_model->record_login($user['id']); } catch (\Throwable $e) {}
        try { $this->Bank_model->audit('login', ucfirst($user['role']).' signed in', $user['id']); } catch (\Throwable $e) {}
    }

    private function refresh_captcha()
    {
        try {
            $this->session->set_userdata('captcha', (string) random_int(10000, 99999));
        } catch (\Throwable $e) {
            $this->session->set_userdata('captcha', (string) mt_rand(10000, 99999));
        }
    }
}
