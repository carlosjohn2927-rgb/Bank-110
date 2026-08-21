<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chat — serves the In-Site Operating AI Assistant.
 *
 * Accepts a user message over POST (JSON or form-encoded) and returns a reply
 * generated entirely by the local Site_operator_engine. No external APIs.
 */
class Chat extends CI_Controller
{
    public function index()
    {
        if ($this->input->method() !== 'post') {
            return $this->json_out(array('error' => 'Method not allowed.'), 405);
        }

        $this->load->library('Site_operator_engine');

        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, TRUE);
        $message = is_array($body) ? ($body['message'] ?? '') : $this->input->post('message', TRUE);
        $message = trim((string) $message);

        if ($message === '') {
            return $this->json_out(array('error' => 'Empty message.'), 422);
        }

        // Wrap length to avoid abuse; the engine is offline & sandboxed.
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        if ($strlen($message) > 500) {
            $message = $substr($message, 0, 500);
        }

        $user = $this->session->userdata('user');
        $reply = $this->site_operator_engine->reply($message, $user);

        return $this->json_out(array(
            'ok'    => TRUE,
            'text'  => $reply['text'],
            'quick' => $reply['quick'],
            'actions' => $reply['actions'],
        ));
    }

    protected function json_out($data, $status = 200)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($data));
        return $this->output;
    }
}
