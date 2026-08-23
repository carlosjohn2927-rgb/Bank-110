<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

/*
 * Database driver selection via .env:
 *   VP_DB_DRIVER=mysqli (used on cPanel MySQL/MariaDB production)
 *   VP_DB_DRIVER=sqlite (portable single-file mode for local/offline testing;
 *                        optional path via VP_SQLITE_PATH, default
 *                        application/cache/production.sqlite)
 *
 * For local development and to ensure logins work out-of-the-box, we default
 * to sqlite when no MySQL credentials are provided. cPanel production should
 * set VP_DB_HOST / VP_DB_NAME / VP_DB_USER / VP_DB_PASS in .env.
 */
$vp_db_driver_env = getenv('VP_DB_DRIVER');
$vp_db_host_env = getenv('VP_DB_HOST');
$vp_db_name_env = getenv('VP_DB_NAME');
if ($vp_db_driver_env === FALSE || $vp_db_driver_env === '') {
    // If MySQL env vars are missing, default to sqlite for local dev.
    if ($vp_db_host_env === FALSE || $vp_db_host_env === '' || $vp_db_name_env === FALSE || $vp_db_name_env === '') {
        $vp_db_driver_env = 'sqlite';
    } else {
        $vp_db_driver_env = 'mysqli';
    }
}
$vp_db_driver = strtolower((string) $vp_db_driver_env);

if ($vp_db_driver === 'sqlite' || $vp_db_driver === 'pdo_sqlite')
{
    $vp_sqlite_path = (string) (getenv('VP_SQLITE_PATH') ?: '');
    if ($vp_sqlite_path === '')
    {
        $vp_sqlite_path = FCPATH.'application/cache/production.sqlite';
    }
    $vp_sqlite_path = str_replace('\\', '/', $vp_sqlite_path);

    $db['default'] = array(
        'dsn'      => 'sqlite:'.$vp_sqlite_path,
        'hostname' => 'localhost',
        'username' => '',
        'password' => '',
        'database' => $vp_sqlite_path,
        'dbdriver' => 'pdo',
        'dbprefix' => '',
        'pconnect' => FALSE,
        'db_debug' => (ENVIRONMENT !== 'production'),
        'cache_on'  => FALSE,
        'cachedir'  => '',
        'char_set' => 'utf8',
        'dbcollat' => '',
        'swap_pre' => '',
        'encrypt'  => FALSE,
        'compress' => FALSE,
        'stricton' => TRUE,
        'failover' => array(),
        'save_queries' => (ENVIRONMENT !== 'production')
    );
}
else
{
    $db['default'] = array(
        'dsn'      => '',
        'hostname' => getenv('VP_DB_HOST') ?: 'localhost',
        'port'     => (int) (getenv('VP_DB_PORT') ?: 3306),
        'username' => getenv('VP_DB_USER') ?: '',
        'password' => getenv('VP_DB_PASS') ?: '',
        'database' => getenv('VP_DB_NAME') ?: '',
        'dbdriver' => 'mysqli',
        'dbprefix' => '',
        'pconnect' => FALSE,
        'db_debug' => (ENVIRONMENT !== 'production'),
        'cache_on' => FALSE,
        'cachedir' => '',
        'char_set' => 'utf8mb4',
        'dbcollat' => 'utf8mb4_unicode_ci',
        'swap_pre' => '',
        'encrypt'  => FALSE,
        'compress' => FALSE,
        'stricton' => TRUE,
        'failover' => array(),
        'save_queries' => (ENVIRONMENT !== 'production')
    );
}
