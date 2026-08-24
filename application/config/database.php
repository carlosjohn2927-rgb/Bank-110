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
$vp_ci_env = getenv('CI_ENV') ?: (isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');

if ($vp_db_driver_env === FALSE || $vp_db_driver_env === '') {
    // In production we MUST default to mysqli so a missing .env shows a clear
    // "service unavailable" page instead of creating an empty sqlite file that
    // then causes a fatal error (Call to member function row_array() on bool)
    // and a generic HTTP 500 on login.
    if (strtolower((string)$vp_ci_env) === 'production') {
        $vp_db_driver_env = 'mysqli';
    } else {
        // Local dev convenience: sqlite when MySQL vars are missing
        if ($vp_db_host_env === FALSE || $vp_db_host_env === '' || $vp_db_name_env === FALSE || $vp_db_name_env === '') {
            $vp_db_driver_env = 'sqlite';
        } else {
            $vp_db_driver_env = 'mysqli';
        }
    }
}
$vp_db_driver = strtolower((string) $vp_db_driver_env);

if ($vp_db_driver === 'sqlite' || $vp_db_driver === 'pdo_sqlite')
{
    $vp_sqlite_path = (string) (getenv('VP_SQLITE_PATH') ?: '');
    if ($vp_sqlite_path === '') {
        $vp_sqlite_path = FCPATH.'application/cache/production.sqlite';
    }
    // Resolve relative paths against the application front-controller directory
    // so the file is always created in a known, writable location regardless of
    // the PHP process's current working directory.
    if (!preg_match('#^([A-Za-z]:[\\\\/]|/)#', $vp_sqlite_path)) {
        $vp_sqlite_path = FCPATH.ltrim($vp_sqlite_path, '/\\');
    }
    $vp_sqlite_path = str_replace('\\', '/', $vp_sqlite_path);

    // Ensure the directory for the sqlite file exists to avoid PDO creation failure
    $sqlite_dir = dirname($vp_sqlite_path);
    if (!is_dir($sqlite_dir)) {
        @mkdir($sqlite_dir, 0755, TRUE);
    }

    // Auto-initialize the SQLite database from the bundled schema when it is
    // missing or empty (no `users` table). Without this, PDO silently creates an
    // empty file on first connect, every query fails, and login shows the
    // "Our services are temporarily unavailable" message. This runs only for
    // the portable sqlite driver (cPanel/MySQL production uses production.sql).
    if (!function_exists('northwest_sqlite_needs_init')) {
        function northwest_sqlite_needs_init($path)
        {
            if (!file_exists($path) || filesize($path) === 0) {
                return TRUE;
            }
            try {
                $probe = new PDO('sqlite:'.$path);
                $probe->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $row = $probe->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
                $probe = NULL;
                return $row === FALSE;
            } catch (\Throwable $e) {
                return TRUE;
            }
        }
    }
    if (!function_exists('northwest_sqlite_init')) {
        function northwest_sqlite_init($path)
        {
            $schema = dirname(__DIR__, 2).'/database/sqlite_schema.sql';
            if (!is_readable($schema)) {
                if (function_exists('log_message')) {
                    log_message('error', 'SQLite init: schema file not readable at '.$schema);
                }
                return FALSE;
            }
            $sql = file_get_contents($schema);
            if ($sql === FALSE || trim($sql) === '') {
                return FALSE;
            }
            try {
                $pdo = new PDO('sqlite:'.$path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec('PRAGMA foreign_keys = ON');
                $pdo->beginTransaction();
                $pdo->exec($sql);
                $pdo->commit();
                $pdo = NULL;
                if (function_exists('log_message')) {
                    log_message('info', 'SQLite database initialized from sqlite_schema.sql at '.$path);
                }
                return TRUE;
            } catch (\Throwable $e) {
                if (isset($pdo) && $pdo && $pdo->inTransaction()) {
                    try { $pdo->rollBack(); } catch (\Throwable $re) {}
                }
                if (function_exists('log_message')) {
                    log_message('error', 'SQLite init failed: '.$e->getMessage());
                }
                return FALSE;
            }
        }
    }
    if (northwest_sqlite_needs_init($vp_sqlite_path)) {
        northwest_sqlite_init($vp_sqlite_path);
    }

    $db['default'] = array(
        'dsn'      => 'sqlite:'.$vp_sqlite_path,
        'hostname' => 'localhost',
        'username' => '',
        'password' => '',
        'database' => $vp_sqlite_path,
        'dbdriver' => 'pdo',
        'dbprefix' => '',
        'pconnect' => FALSE,
        'db_debug' => FALSE, // Always FALSE for sqlite to prevent fatal on missing tables — db_ok() will handle gracefully
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
