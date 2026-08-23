<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

/*
 * Database driver selection via .env:
 *   VP_DB_DRIVER=mysqli (default, used on cPanel MySQL/MariaDB production)
 *   VP_DB_DRIVER=sqlite (portable single-file mode for local/offline testing;
 *                        optional path via VP_SQLITE_PATH, default
 *                        application/cache/production.sqlite)
 *
 * Normal cPanel deployments never set VP_DB_DRIVER, so they always use MySQL.
 */
$vp_db_driver = strtolower((string) (getenv('VP_DB_DRIVER') ?: 'mysqli'));

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
