<?php

/**
 * Application security helpers: response headers, login throttling, permission gates.
 */

if (!function_exists('barangay_client_ip')) {
    function barangay_client_ip(): string
    {
        $candidates = [
            (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];
        foreach ($candidates as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            if (str_contains($value, ',')) {
                $value = trim(explode(',', $value)[0]);
            }
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '0.0.0.0';
    }
}

if (!function_exists('barangay_send_security_headers')) {
    /**
     * Safe defaults for admin/resident portals. Does not set a strict CSP (AdminLTE uses inline scripts).
     */
    function barangay_send_security_headers(): void
    {
        if (headers_sent()) {
            return;
        }

        static $sent = false;
        if ($sent) {
            return;
        }
        $sent = true;

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-XSS-Protection: 0');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

if (!function_exists('barangay_login_rate_limit_dir')) {
    function barangay_login_rate_limit_dir(): string
    {
        $candidates = [
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'secure' . DIRECTORY_SEPARATOR . 'login_attempts',
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'barangay_login_attempts',
        ];
        foreach ($candidates as $dir) {
            if (is_dir($dir) || @mkdir($dir, 0700, true)) {
                return $dir;
            }
        }

        return sys_get_temp_dir();
    }
}

if (!function_exists('barangay_login_rate_limit_path')) {
    function barangay_login_rate_limit_path(string $username): string
    {
        $key = hash('sha256', barangay_client_ip() . '|' . strtolower(trim($username)));

        return barangay_login_rate_limit_dir() . DIRECTORY_SEPARATOR . $key . '.json';
    }
}

if (!function_exists('barangay_login_rate_limit_read')) {
    /** @return array{attempts:int, locked_until:int} */
    function barangay_login_rate_limit_read(string $username): array
    {
        $defaults = ['attempts' => 0, 'locked_until' => 0];
        $path = barangay_login_rate_limit_path($username);
        if (!is_file($path)) {
            return $defaults;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $defaults;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $defaults;
        }

        return [
            'attempts' => max(0, (int) ($data['attempts'] ?? 0)),
            'locked_until' => max(0, (int) ($data['locked_until'] ?? 0)),
        ];
    }
}

if (!function_exists('barangay_login_rate_limit_write')) {
    function barangay_login_rate_limit_write(string $username, array $data): void
    {
        $path = barangay_login_rate_limit_path($username);
        @file_put_contents($path, (string) json_encode([
            'attempts' => max(0, (int) ($data['attempts'] ?? 0)),
            'locked_until' => max(0, (int) ($data['locked_until'] ?? 0)),
        ]), LOCK_EX);
    }
}

if (!function_exists('barangay_login_rate_limit_check')) {
    /**
     * @return null|string Token for loginForm.php exit, or null if allowed.
     */
    function barangay_login_rate_limit_check(string $username): ?string
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $maxAttempts = 5;
        $lockSeconds = 900;
        $state = barangay_login_rate_limit_read($username);
        $now = time();

        if ($state['locked_until'] > $now) {
            return 'errorRateLimited';
        }

        if ($state['locked_until'] > 0 && $state['locked_until'] <= $now) {
            barangay_login_rate_limit_write($username, ['attempts' => 0, 'locked_until' => 0]);
        }

        if ($state['attempts'] >= $maxAttempts) {
            barangay_login_rate_limit_write($username, [
                'attempts' => $state['attempts'],
                'locked_until' => $now + $lockSeconds,
            ]);

            return 'errorRateLimited';
        }

        return null;
    }
}

if (!function_exists('barangay_login_rate_limit_fail')) {
    function barangay_login_rate_limit_fail(string $username): void
    {
        $username = trim($username);
        if ($username === '') {
            return;
        }
        $maxAttempts = 5;
        $lockSeconds = 900;
        $state = barangay_login_rate_limit_read($username);
        $attempts = (int) $state['attempts'] + 1;
        $lockedUntil = 0;
        if ($attempts >= $maxAttempts) {
            $lockedUntil = time() + $lockSeconds;
        }
        barangay_login_rate_limit_write($username, [
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ]);
    }
}

if (!function_exists('barangay_login_rate_limit_success')) {
    function barangay_login_rate_limit_success(string $username): void
    {
        $username = trim($username);
        if ($username === '') {
            return;
        }
        @unlink(barangay_login_rate_limit_path($username));
    }
}

if (!function_exists('barangay_require_permission')) {
    function barangay_require_permission(bool $allowed, string $message = 'You do not have permission to perform this action.'): void
    {
        if ($allowed) {
            return;
        }
        if (function_exists('barangay_request_expects_json') && barangay_request_expects_json()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message]);
            exit;
        }
        http_response_code(403);
        exit($message);
    }
}
