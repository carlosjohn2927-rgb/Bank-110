<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mail helper — sends transactional emails (transfer, ticket, welcome,
 * password reset). SMTP is configured through VP_MAIL_* env vars in .env.
 * If SMTP is not configured, emails are logged to the application log instead
 * of failing loudly, so the app keeps working in a development environment.
 */

/**
 * Send a transactional email to a user, respecting their email_alerts
 * preference. Falls back to the recipient's stored email.
 */
function notify_user($user_id, $subject, $content, $required_pref = 'email_alerts', $type_pref = NULL)
{
    $CI =& get_instance();
    try {
        $prefs = $CI->Bank_model->preferences((int)$user_id);
        if ($required_pref && (empty($prefs[$required_pref]) || $prefs[$required_pref] !== '1')) {
            // User has opted out of email alerts.
            return FALSE;
        }
        // Per-category opt-out: e.g. $type_pref='notify_transfers' only sends
        // when that toggle is on (defaults to on when unset).
        if ($type_pref && isset($prefs[$type_pref]) && $prefs[$type_pref] !== '1') {
            return FALSE;
        }
        $user = $CI->Bank_model->profile((int)$user_id);
        if (empty($user['email'])) return FALSE;
        return send_notification_email($user['email'], $subject, $content);
    } catch (\Throwable $e) {
        return FALSE;
    }
}

function send_notification_email($to, $subject, $message_html)
{
    $CI =& get_instance();
    $CI->load->library('email');

    $config = $CI->config->item('email');
    if (empty($config['smtp_host']) && ($config['protocol'] ?? 'smtp') === 'smtp') {
        // No SMTP configured — log it instead of erroring.
        log_message('error', '[notification] (SMTP not configured) To: '.$to.' Subject: '.$subject);
        return FALSE;
    }

    $CI->email->initialize($config);
    $from = $CI->config->item('mail_from');
    $from_name = $CI->config->item('mail_from_name') ?: 'NorthWest Financial';
    if ($from) $CI->email->from($from, $from_name);

    $CI->email->to($to);
    $CI->email->subject($subject);
    $body = notification_template_wrap($subject, $message_html);
    $CI->email->message($body);

    if ($CI->email->send()) return TRUE;
    log_message('error', '[notification] send failed: '.$CI->email->print_debugger());
    return FALSE;
}

function notification_template_wrap($subject, $content)
{
    $institution = 'NorthWest Financial';
    try {
        $CI =& get_instance();
        $settings = $CI->Bank_model->settings();
        if (!empty($settings['institution_name'])) $institution = $settings['institution_name'];
    } catch (\Throwable $e) {}
    return '<div style="font-family:Arial,Helvetica,sans-serif;background:#f2f5f9;padding:28px 16px">'
        .'<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e3eaf2">'
        .'<div style="background:linear-gradient(120deg,#081f3d,#0e4d8f);padding:22px 26px;color:#fff">'
        .'<div style="font-weight:800;font-size:20px;letter-spacing:-.5px">North<span style="color:#4ea1ff">West</span></div></div>'
        .'<div style="padding:26px"><h2 style="margin:0 0 14px;color:#17263a;font-size:18px">'.htmlspecialchars($subject).'</h2>'
        .'<div style="color:#3d4d61;font-size:14px;line-height:1.6">'.$content.'</div>'
        .'<hr style="border:0;border-top:1px solid #e3eaf2;margin:22px 0">'
        .'<p style="color:#8a98aa;font-size:11px;margin:0">© '.date('Y').' '.htmlspecialchars($institution).'. This is a transactional message about your account.</p>'
        .'</div></div></div>';
}
