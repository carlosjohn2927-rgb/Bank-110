<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Setup / install self-check.
 *
 * Visit /setup/check to see whether the application is fully installed and
 * configured. This is a diagnostic helper for deployment — it does not modify
 * anything. Remove or restrict it before production if you prefer.
 */
class Setup extends CI_Controller
{
    public function check()
    {
        $checks = array();
        $all_ok = TRUE;

        // 1) Critical secrets
        $enc = (string)$this->config->item('encryption_key');
        $auth = (string)$this->config->item('auth_secret');
        $checks[] = $this->row('Encryption key set', $enc !== '', 'Set VP_ENCRYPTION_KEY in .env');
        $checks[] = $this->row('Auth secret set', $auth !== '', 'Set VP_AUTH_SECRET in .env');
        $base = (string)getenv('VP_BASE_URL');
        $checks[] = $this->row('Base URL configured', $base !== '' && !strpos($base,'yourdomain'), 'Set VP_BASE_URL to your real domain');

        // 2) Database connection + tables
        $db_ok = FALSE; $tables_ok = FALSE;
        try {
            $this->load->database();
            $db_ok = (bool)$this->db->conn_id;
        } catch (Exception $e) { $db_ok = FALSE; }
        if ($db_ok) {
            $required = array('users','accounts','transactions','transfers','cards','loans','beneficiaries','support_tickets','settings','audit_logs','customer_profiles','password_resets','user_preferences','exchange_rates');
            $have = array();
            try { $rows = $this->db->query('SHOW TABLES')->result_array(); foreach($rows as $r) $have[] = reset($r); } catch (Exception $e) {}
            $have = array_map('strtolower', $have);
            $missing_tables = array_diff($required, $have);
            $tables_ok = empty($missing_tables);
            $checks[] = $this->row('Database connected', $db_ok, 'Check VP_DB_* in .env');
            $checks[] = $this->row('Required tables present (' . (count($required)-count($missing_tables)) . '/' . count($required) . ')', $tables_ok, 'Import database/production.sql (missing: '.implode(', ',$missing_tables).')');
        } else {
            $checks[] = $this->row('Database connected', FALSE, 'Check VP_DB_HOST/NAME/USER/PASS in .env');
            $checks[] = $this->row('Required tables present', FALSE, 'Database unavailable — cannot check tables');
        }

        // 3) Writable directories
        $writable = array('assets/logs','assets/logs/cache','assets/logs/sessions','assets/uploads');
        foreach ($writable as $dir) {
            $path = FCPATH.$dir;
            $ok = is_dir($path) && is_writable($path);
            $checks[] = $this->row('Writable: '.$dir, $ok, 'Ensure directory exists and is writable (0755)');
        }

        // 4) Config sanity
        $checks[] = $this->row('CI_ENV set', (string)getenv('CI_ENV') !== '', 'Set CI_ENV in .env');
        $checks[] = $this->row('PHP Version is 8.2 or newer (Current: '.PHP_VERSION.')', version_compare(PHP_VERSION, '8.2.0', '>='), 'Switch to PHP 8.2+ in cPanel MultiPHP Manager');

        foreach ($checks as $c) { if (!$c['ok']) $all_ok = FALSE; }

        $this->load->view('setup_check', array('checks'=>$checks, 'all_ok'=>$all_ok));
    }

    private function row($label, $ok, $hint)
    {
        return array('label'=>$label, 'ok'=>(bool)$ok, 'hint'=>$hint);
    }
}
