<?php
/**
 * config.php
 * Carga variables del archivo .env (que NUNCA se sube a Git)
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        die('Error: archivo .env no encontrado. Copia .env.example a .env y configura tus credenciales.');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || $line === '') continue;
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

loadEnv(__DIR__ . '/.env');
