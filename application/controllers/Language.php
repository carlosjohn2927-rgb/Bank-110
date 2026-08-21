<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Language extends CI_Controller
{
    public function set()
    {
        $code = (string) $this->input->post('language', TRUE);
        $this->config->load('languages', TRUE);
        $available = $this->config->item('available_languages') ?: array();
        if (array_key_exists($code, $available)) {
            $this->session->set_userdata('language', $code);
            // Persist the choice for signed-in users so it survives re-login.
            $user = $this->session->userdata('user');
            if ($user && isset($this->Bank_model)) {
                try { $this->Bank_model->set_preference((int)$user['id'], 'language', $code); } catch (Exception $e) {}
            }
        }
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }
}
