<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function login()
    {
        if ($this->user) redirect($this->user['role'] === 'admin' ? 'admin/dashboard' : 'dashboard');
        if (!$this->session->userdata('captcha')) $this->refresh_captcha();
        $this->load->view('auth/login', array('captcha' => $this->session->userdata('captcha')));
    }

    public function verify()
    {
        if (!$this->input->post()) redirect('login');
        $code = trim((string) $this->input->post('code', TRUE));
        if (!hash_equals((string) $this->session->userdata('captcha'), $code)) {
            $this->session->set_flashdata('error', 'The verification code does not match.');
            $this->refresh_captcha();
            redirect('login');
        }
        $this->session->set_userdata('captcha_verified', TRUE);
        redirect('login?credentials=1');
    }

    public function customer_login()
    {
        if (!$this->session->userdata('captcha_verified')) redirect('login');
        $this->form_validation->set_rules('identity', 'Account number or email', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');
        if (!$this->form_validation->run()) { $this->session->set_flashdata('error', validation_errors('',' ')); redirect('login?credentials=1'); }
        $user = $this->Bank_model->authenticate($this->input->post('identity', TRUE), $this->input->post('password'), 'customer');
        if (!$user) { $this->session->set_flashdata('error', 'Invalid login details or inactive account.'); redirect('login?credentials=1'); }
        // Two-factor authentication: if enabled, send an OTP and pause sign-in.
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
        if (!$pending || empty($pending['user'])) redirect('login');
        if ($this->input->method() === 'post') {
            $code = trim((string) $this->input->post('code', TRUE));
            if (!empty($pending['code']) && hash_equals((string)$pending['code'], $code) && strtotime($pending['expires']) > time()) {
                $this->session->unset_userdata('twofa_pending');
                $this->establish_session($pending['user']);
                redirect('dashboard');
            }
            $this->session->set_flashdata('error', 'That code is incorrect or has expired.');
            redirect('twofa');
        }
        $this->load->view('auth/twofa', array('masked_email' => $this->mask_email($pending['user']['email'])));
    }

    public function resend_twofa()
    {
        $pending = $this->session->userdata('twofa_pending');
        if (!$pending || empty($pending['user'])) redirect('login');
        $this->dispatch_otp($pending['user']);
        $this->session->set_flashdata('success', 'A new code has been sent.');
        redirect('twofa');
    }

    private function begin_twofa($user)
    {
        $this->session->set_userdata('twofa_pending', array('user'=>$user));
        $this->dispatch_otp($user);
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
        if ($this->user && $this->user['role'] === 'admin') redirect('admin/dashboard');
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('identity', 'Email or username', 'required|trim');
            $this->form_validation->set_rules('password', 'Password', 'required');
            if ($this->form_validation->run()) {
                $user = $this->Bank_model->authenticate($this->input->post('identity', TRUE), $this->input->post('password'), 'admin');
                if ($user) { $this->establish_session($user); redirect('admin/dashboard'); }
                $this->session->set_flashdata('error', 'Invalid administrator credentials.');
            } else $this->session->set_flashdata('error', validation_errors('',' '));
            redirect('admin');
        }
        $this->load->view('auth/admin');
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
                redirect('login');
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
        redirect($role === 'admin' ? 'admin' : 'login');
    }

    private function establish_session($user)
    {
        $this->session->sess_regenerate(TRUE);
        unset($user['password_hash']);
        $this->session->set_userdata('user', $user);
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
