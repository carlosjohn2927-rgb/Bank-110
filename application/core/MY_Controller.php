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
        // Load the UI language (from session preference or default).
        $this->config->load('languages', TRUE);
        $lang = $this->session->userdata('language') ?: ($this->config->item('default_language') ?: 'english');
        if (!array_key_exists($lang, $this->config->item('available_languages') ?: array())) {
            $lang = 'english';
        }
        $this->lang->load('northwest', $lang);
    }

    protected function render($view, $data = array(), $layout = 'layouts/customer')
    {
        $data['current_user'] = $this->user;
        $data['current_route'] = $this->uri->uri_string();
        $data['content_view'] = $view;
        $data['flash_success'] = $this->session->flashdata('success');
        $data['flash_error'] = $this->session->flashdata('error');
        $data['notifications'] = $this->recent_notifications();
        $data['app_notifications'] = $this->app_notifications();
        $data['unread_count'] = $this->unread_count();
        $this->load->view($layout, $data);
    }

    /**
     * Recent activity for the header bell — the last few transactions the user
     * can see (customer-scoped for customers, all for admins).
     */
    protected function app_notifications()
    {
        if (!$this->user) return array();
        try { return $this->Bank_model->notifications((int)$this->user['id'], 8); }
        catch (Exception $e) { return array(); }
    }

    protected function unread_count()
    {
        if (!$this->user) return 0;
        try { return (int)$this->Bank_model->unread_notification_count((int)$this->user['id']); }
        catch (Exception $e) { return 0; }
    }

    protected function recent_notifications()
    {
        if (!$this->user) return array();
        try {
            if (($this->user['role'] ?? '') === 'admin') {
                return $this->Bank_model->all_transactions(NULL, 5);
            }
            return $this->Bank_model->recent_activity((int)$this->user['id'], 6);
        } catch (Exception $e) {
            return array();
        }
    }

    protected function require_customer()
    {
        if (!$this->user || ($this->user['role'] ?? '') !== 'customer') {
            $this->session->set_flashdata('error', 'Please sign in to continue.');
            redirect('user/login');
        }
    }

    protected function require_admin()
    {
        if (!$this->user || ($this->user['role'] ?? '') !== 'admin') {
            $this->session->set_flashdata('error', 'Administrator authentication required.');
            redirect('login');
        }
    }

    protected function json($data, $status = 200)
    {
        return $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($data));
    }
}
