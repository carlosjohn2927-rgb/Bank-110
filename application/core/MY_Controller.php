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
        $data['impersonating'] = (bool) $this->session->userdata('impersonation_admin');
        $data['impersonation_admin'] = $this->session->userdata('impersonation_admin');
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
        catch (\Throwable $e) { return array(); }
    }

    protected function unread_count()
    {
        if (!$this->user) return 0;
        try { return (int)$this->Bank_model->unread_notification_count((int)$this->user['id']); }
        catch (\Throwable $e) { return 0; }
    }

    protected function recent_notifications()
    {
        if (!$this->user) return array();
        try {
            if (($this->user['role'] ?? '') === 'admin') {
                return $this->Bank_model->all_transactions(NULL, 5);
            }
            return $this->Bank_model->recent_activity((int)$this->user['id'], 6);
        } catch (\Throwable $e) {
            return array();
        }
    }

    protected function require_customer()
    {
        if (!$this->user || ($this->user['role'] ?? '') !== 'customer') {
            $this->session->set_flashdata('error', 'Please sign in to continue.');
            redirect('user/login');
        }
        $this->require_db();
    }

    protected function require_admin()
    {
        if (!$this->user || ($this->user['role'] ?? '') !== 'admin') {
            $this->session->set_flashdata('error', 'Administrator authentication required.');
            redirect('login');
        }
        $this->require_db();
    }

    /**
     * Is the database connection usable? In production db_debug is disabled, so
     * a failed connection leaves $this->db->conn_id empty instead of raising an
     * error. We detect that here so the app can degrade gracefully.
     */
    protected function db_ok()
    {
        if (!isset($this->db) || empty($this->db->conn_id)) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Guarantee the database is reachable for the current request. Used by the
     * auth gates so a database outage shows a clear, branded "service
     * unavailable" page instead of a blank screen (the failure mode caused by
     * calling a model method on a dead connection).
     */
    protected function require_db()
    {
        if ($this->db_ok()) {
            return;
        }
        log_message('error', 'Database unavailable: connection failed (check VP_DB_* in .env).');
        $this->render_db_unavailable();
    }

    /**
     * Render a standalone 503 page for a database outage. Standalone (no layout,
     * no helpers that touch the database) so it always renders even when the
     * rest of the app cannot.
     */
    protected function render_db_unavailable()
    {
        $setup_url = function_exists('site_url') ? site_url('setup/check') : '/setup/check';
        if (ENVIRONMENT === 'development') {
            $hint = 'Check VP_DB_HOST / VP_DB_NAME / VP_DB_USER / VP_DB_PASS in .env, then import database/production.sql.';
        } else {
            $hint = 'Our team has been notified. Please try again in a few moments.';
        }
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<meta name="robots" content="noindex">'
            .'<title>Service temporarily unavailable &middot; NorthWest Financial</title>'
            .'<style>*{box-sizing:border-box}body{margin:0;font-family:\'Segoe UI\',Arial,sans-serif;'
            .'background:#0e2240;color:#17263a;display:grid;place-items:center;min-height:100vh;padding:24px}'
            .'.card{width:min(520px,94vw);background:#fff;border-radius:18px;padding:40px 34px;'
            .'box-shadow:0 30px 70px rgba(0,0,0,.35);text-align:center}'
            .'.mark{font-weight:800;font-size:22px;letter-spacing:-.5px;margin-bottom:18px;color:#0e2240}'
            .'.mark span{color:#3b82f6}h1{margin:0 0 10px;font-size:22px}.sub{color:#5b6b7e;font-size:14px;line-height:1.6;margin:0 0 22px}'
            .'.hint{color:#7d8a99;font-size:12px;line-height:1.6;margin:0 0 18px}'
            .'.btn{display:inline-block;background:#1468e5;color:#fff;padding:12px 22px;border-radius:10px;'
            .'text-decoration:none;font-weight:700;font-size:14px}.foot{margin-top:22px;color:#9aa7b6;font-size:11px}</style></head>'
            .'<body><div class="card"><div class="mark">North<span>West</span></div>'
            .'<h1>We&rsquo;ll be right back</h1>'
            .'<p class="sub">We&rsquo;re performing a quick upgrade to our systems. Your accounts and money are safe.</p>'
            .'<p class="hint">'.htmlspecialchars($hint, ENT_QUOTES, 'UTF-8').'</p>'
            .'<a class="btn" href="/">Try again</a>'
            .'<div class="foot">&copy; '.date('Y').' NorthWest Financial &middot; '
            .'Administrator? Visit <a href="'.htmlspecialchars($setup_url, ENT_QUOTES, 'UTF-8').'">the setup check</a>.</div>'
            .'</div></body></html>';
        // Native headers + echo (no dependency on CI output internals) so this
        // always renders even when triggered from a controller constructor.
        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            header('Retry-After: 300');
        }
        echo $html;
        exit(EXIT_ERROR);
    }

    protected function json($data, $status = 200)
    {
        return $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($data));
    }
}
