<?php

/**
 * Load KEY=VALUE pairs from .env files into getenv / $_ENV.
 * Does not override variables already set in the process environment.
 */

if (!function_exists('barangay_load_env_file')) {
    function barangay_load_env_file(string $path): void
    {
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key === '') {
                continue;
            }
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $existing = getenv($key);
            if ($existing !== false && $existing !== '') {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('barangay_load_env')) {
    function barangay_load_env(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $secureEnv = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'secure'
            . DIRECTORY_SEPARATOR . 'barangay_db' . DIRECTORY_SEPARATOR . '.env';
        $projectEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

        barangay_load_env_file($secureEnv);
        barangay_load_env_file($projectEnv);
    }
}

barangay_load_env();
