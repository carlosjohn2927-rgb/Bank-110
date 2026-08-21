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
        $this->establish_session($user);
        redirect('dashboard');
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
