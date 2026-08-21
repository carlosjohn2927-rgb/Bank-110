<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected $user;

    public function __construct()
    {
        parent::__construct();
        $this->user = $this->session->userdata('user');
        if ($this->user) {
            $secret = (string) $this->config->item('auth_secret');
            $expected = hash_hmac('sha256', $this->user['id'].'|'.$this->user['role'].'|'.$this->user['created_at'], $secret);
            if (!hash_equals($expected, (string) $this->session->userdata('auth_signature'))) {
                $this->session->sess_destroy();
                $this->user = NULL;
            }
        }
    }

    protected function render($view, $data = array(), $layout = 'layouts/customer')
    {
        $data['current_user'] = $this->user;
        $data['current_route'] = $this->uri->uri_string();
        $data['content_view'] = $view;
        $data['flash_success'] = $this->session->flashdata('success');
        $data['flash_error'] = $this->session->flashdata('error');
        $this->load->view($layout, $data);
    }

    protected function require_customer()
    {
        if (!$this->user || ($this->user['role'] ?? '') !== 'customer') {
            $this->session->set_flashdata('error', 'Please sign in to continue.');
            redirect('login');
        }
    }

    protected function require_admin()
    {
        if (!$this->user || ($this->user['role'] ?? '') !== 'admin') {
            $this->session->set_flashdata('error', 'Administrator authentication required.');
            redirect('admin');
        }
    }

    protected function json($data, $status = 200)
    {
        return $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($data));
    }
}
