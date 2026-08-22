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
            $this->form_validation->set_rules('identity', 'Email or username', 'required|trim');
            $this->form_validation->set_rules('password', 'Password', 'required');
            if ($this->form_validation->run()) {
                $key = 'admin:'.($this->input->post('identity', TRUE));
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
                $this->session->set_flashdata('error', 'Invalid administrator credentials.');
            } else {
                $this->session->set_flashdata('error', validation_errors(' ', ' '));
            }
            redirect('login');
        }
        $this->load->view('auth/login');
    }

    /**
     * /user/login — Customer sign-in with bot-check captcha.
     */
    public function user_login()
    {
        if ($this->user) redirect($this->user['role'] === 'admin' ? 'admin/dashboard' : 'dashboard');
        if (!$this->session->userdata('captcha')) $this->refresh_captcha();
        $this->load->view('auth/user_login', array('captcha' => $this->session->userdata('captcha')));
    }

    public function verify()
    {
        if (!$this->input->post()) redirect('user/login');
        $code = trim((string) $this->input->post('code', TRUE));
        if (!hash_equals((string) $this->session->userdata('captcha'), $code)) {
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
        $this->form_validation->set_rules('identity', 'Account number or email', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');
        if (!$this->form_validation->run()) { $this->session->set_flashdata('error', validation_errors('',' ')); redirect('user/login?credentials=1'); }
        // Brute-force protection: lock out after repeated failures.
        $key='customer:'.($this->input->post('identity',TRUE));
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
    }

    public function twofa()
    {
        $pending = $this->session->userdata('twofa_pending');
        if (!$pending || empty($pending['user'])) redirect('user/login');
        if ($this->input->method() === 'post') {
            $code = trim((string) $this->input->post('code', TRUE));
            $user = $pending['user'];
            $verified = FALSE;

            // TOTP (authenticator app) or backup code.
            if (!empty($user['totp_confirmed']) && !empty($user['totp_secret'])) {
                $result = $this->Bank_model->totp_verify($user, $code);
                if ($result === 'backup') {
                    // Refresh the user row in session since backup codes were cleared.
                    $user = $this->db->where('id', $user['id'])->get('users')->row_array();
                    $verified = TRUE;
                    $this->session->set_flashdata('success', 'Signed in with a backup code. Generate new backup codes in Settings → Security.');
                } elseif ($result === 'totp') {
                    $verified = TRUE;
                }
            }

            // Email OTP fallback (always allowed when 2FA is on).
            if (!$verified && !empty($pending['code']) && hash_equals((string)$pending['code'], $code) && strtotime($pending['expires']) > time()) {
                $verified = TRUE;
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
                send_notification_email($user['email'], 'Your NorthWest sign-in code', '<p>Your verification code is:</p><p style="font-size:26px;font-weight:800;letter-spacing:3px;color:#1468e5">'.$code.'</p><p>It expires in 5 minutes. If you didn\'t attempt to sign in, please contact support immediately.</p>');
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
            send_notification_email($user['email'], 'Your NorthWest sign-in code', '<p>Your verification code is:</p><p style="font-size:26px;font-weight:800;letter-spacing:3px;color:#1468e5">'.$code.'</p><p>It expires in 5 minutes. If you didn\'t attempt to sign in, please contact support immediately.</p>');
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
            $this->form_validation->set_rules('email','Email','required|valid_email');
            if ($this->form_validation->run()) {
                $token = $this->Bank_model->create_password_reset($this->input->post('email', TRUE));
                if ($token) {
                    $this->Bank_model->audit('password_reset_requested','Password reset link generated');
                    $reset_url = site_url('reset/'.$token);
                    $sent = function_exists('send_notification_email') && send_notification_email($this->input->post('email', TRUE), 'Reset your NorthWest password', '<p>Use this secure link to reset your password. It expires in 30 minutes:</p><p><a href="'.htmlspecialchars($reset_url).'" style="display:inline-block;background:#1468e5;color:#fff;padding:11px 18px;border-radius:8px;text-decoration:none;font-weight:700">Reset password</a></p><p>If you didn\'t request this, you can safely ignore this email.</p>');
                    // If email isn't configured, still surface the link once so the flow is usable.
                    if (!$sent) $this->session->set_flashdata('reset_link', $reset_url);
                    redirect('forgot?sent=1');
                }
                $this->session->set_flashdata('error', 'No active account is associated with that email.');
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
            $this->form_validation->set_rules('password','New password','required|min_length[8]');
            $this->form_validation->set_rules('confirm','Confirm password','required|matches[password]');
            if ($this->form_validation->run() && $this->Bank_model->complete_password_reset($token, $this->input->post('password'))) {
                $this->Bank_model->audit('password_reset_completed','Password reset completed');
                $this->session->set_flashdata('success','Your password has been updated. Please sign in.');
                redirect('user/login');
            }
            $this->session->set_flashdata('error', $this->Bank_model->get_password_reset($token) ? validation_errors('',' ') : 'This reset link is invalid or has expired.');
            redirect('reset/'.$token);
        }
        if (!$this->Bank_model->get_password_reset($token)) { show_404(); }
        $this->load->view('auth/reset', array('token' => $token));
    }

    public function logout()
    {
        $role = $this->user['role'] ?? 'customer';
        $this->Bank_model->audit('logout', 'User signed out', $this->user['id'] ?? NULL);
        $this->session->sess_destroy();
        redirect($role === 'admin' ? 'login' : 'user/login');
    }

    private function establish_session($user)
    {
        $this->session->sess_regenerate(TRUE);
        unset($user['password_hash']);
        $this->session->set_userdata('user', $user);
        try { $prefs=$this->Bank_model->preferences($user['id']); if(!empty($prefs['language'])) $this->session->set_userdata('language',$prefs['language']); } catch (Exception $e) {}
        $auth_secret = (string) $this->config->item('auth_secret');
        $this->session->set_userdata('auth_signature', hash_hmac('sha256', $user['id'].'|'.$user['role'].'|'.$user['created_at'], $auth_secret));
        $this->session->unset_userdata(array('captcha', 'captcha_verified'));
        $this->Bank_model->record_login($user['id']);
        $this->Bank_model->audit('login', ucfirst($user['role']).' signed in', $user['id']);
    }

    private function refresh_captcha()
    {
        $this->session->set_userdata('captcha', (string) random_int(10000, 99999));
    }
}
