<?php
/**
 * Tiny dependency-free .env loader for portable shared-hosting deployments.
 * Existing web-server environment variables always take precedence.
 */
function northwest_load_env($path)
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strncmp($line, 'export ', 7) === 0) $line = trim(substr($line, 7));
        $position = strpos($line, '=');
        if ($position === FALSE) continue;

        $name = trim(substr($line, 0, $position));
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name)) continue;
        if (getenv($name) !== FALSE) continue;

        $value = trim(substr($line, $position + 1));
        if (strlen($value) >= 2) {
            $first = $value[0]; $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
                if ($first === '"') $value = stripcslashes($value);
            } else {
                $value = preg_replace('/\s+#.*$/', '', $value);
            }
        }
        $value = str_replace(array('\\n','\\r'), array("\n","\r"), $value);
        putenv($name.'='.$value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

northwest_load_env(dirname(__DIR__).'/.env');
