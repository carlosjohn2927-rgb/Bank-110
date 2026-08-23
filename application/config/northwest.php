<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$config['auth_secret'] = getenv('VP_AUTH_SECRET') ?: 'dev-auth-secret-1234567890-abcdef-1234567890';
$config['cache_driver'] = getenv('VP_CACHE_DRIVER') ?: 'file';
$config['cache_path'] = getenv('VP_CACHE_PATH') ?: 'assets/logs/cache';
$config['upload_path'] = getenv('VP_UPLOAD_PATH') ?: 'assets/uploads';
$config['max_upload_kb'] = (int) (getenv('VP_MAX_UPLOAD_KB') ?: 5120);
$config['mail_from'] = getenv('VP_MAIL_FROM') ?: '';
$config['mail_from_name'] = getenv('VP_MAIL_FROM_NAME') ?: 'NorthWest Financial Ltd.';
$config['api_key'] = getenv('VP_API_KEY') ?: '';
$config['third_party_secret'] = getenv('VP_THIRD_PARTY_SECRET') ?: '';

// Ensure critical paths exist to avoid 500 on upload/cache operations
foreach (array($config['cache_path'], $config['upload_path'], $config['upload_path'].'/checks', 'assets/statements', 'assets/logs/ratelimit', 'application/cache') as $p) {
    $full = preg_match('#^([A-Za-z]:)?[\\\\/]#', $p) ? $p : FCPATH.trim($p, '/\\');
    if (!is_dir($full)) {
        @mkdir($full, 0755, TRUE);
    }
}
