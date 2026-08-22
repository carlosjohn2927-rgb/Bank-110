<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public homepage — marketing landing for NorthWest Financial.
 * Routed from "/" via the default_controller route.
 */
class Home extends MY_Controller
{
    public function index()
    {
        if ($this->user) {
            redirect($this->user['role'] === 'admin' ? 'admin/dashboard' : 'dashboard');
        }
        $this->load->view('home/index');
    }
}
