<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Base URL. VP_BASE_URL from .env wins. If it is missing or still the
 * "yourdomain" placeholder, auto-detect from the incoming request so the site
 * renders with correct absolute asset/link URLs even before .env is configured
 * (otherwise every page after "/" gets broken relative asset paths).
 */
$vp_base_url = rtrim((string) (getenv('VP_BASE_URL') ?: ''), '/');
if ($vp_base_url === '' || stripos($vp_base_url, 'yourdomain') !== FALSE)
{
	$auto_scheme = ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
		|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
		|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'))
		? 'https' : 'http';
	$auto_host = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '')
		? $_SERVER['HTTP_HOST']
		: (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
	$vp_base_url = $auto_scheme.'://'.$auto_host;
}
$config['base_url'] = $vp_base_url.'/';
$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';
$config['language'] = 'english';
$config['charset'] = 'UTF-8';
$config['enable_hooks'] = FALSE;
$config['subclass_prefix'] = 'MY_';
$config['composer_autoload'] = FALSE;
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\\-';
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';
$config['allow_get_array'] = TRUE;
$config['log_threshold'] = (int) (getenv('VP_LOG_THRESHOLD') !== FALSE ? getenv('VP_LOG_THRESHOLD') : (getenv('CI_ENV') === 'production' ? 1 : 2));
// Ensure log directory exists to prevent 500 when logging fails
$vp_log_path = FCPATH.'assets/logs/';
if (!is_dir($vp_log_path)) {
	@mkdir($vp_log_path, 0755, TRUE);
}
$config['log_path'] = $vp_log_path;
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';
$config['error_views_path'] = '';
$config['cache_path'] = '';
$config['cache_query_string'] = FALSE;
$config['encryption_key'] = getenv('VP_ENCRYPTION_KEY') ?: 'dev-encryption-key-1234567890-abcdef-123456';
$config['sess_driver'] = getenv('VP_SESSION_DRIVER') ?: 'files';
$config['sess_cookie_name'] = 'northwest_session';
$config['sess_expiration'] = (int) (getenv('VP_SESSION_EXPIRATION') ?: 7200);
$vp_session_path = getenv('VP_SESSION_PATH') ?: 'assets/logs/sessions';
$vp_session_full = preg_match('#^([A-Za-z]:)?[\\\\/]#', $vp_session_path) ? $vp_session_path : FCPATH.trim($vp_session_path, '/\\\\');
// Ensure session directory exists and is writable — missing dir causes 500 on login (session open failure)
if (!is_dir($vp_session_full)) {
	@mkdir($vp_session_full, 0755, TRUE);
}
$config['sess_save_path'] = $vp_session_full;
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = TRUE;
$config['cookie_prefix'] = 'nw_';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = filter_var(getenv('VP_COOKIE_SECURE') !== FALSE ? getenv('VP_COOKIE_SECURE') : FALSE, FILTER_VALIDATE_BOOLEAN);
$config['cookie_httponly'] = TRUE;
$config['cookie_samesite'] = 'Lax';
$config['standardize_newlines'] = FALSE;
$config['global_xss_filtering'] = FALSE;
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'nw_csrf_token';
$config['csrf_cookie_name'] = 'nw_csrf_cookie';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = FALSE;
$config['csrf_exclude_uris'] = array('chat');
$config['compress_output'] = FALSE;
$config['time_reference'] = 'local';
$config['rewrite_short_tags'] = FALSE;
$config['proxy_ips'] = '';
