<?php

if (!function_exists('barangay_app_base_path')) {
    function barangay_app_base_path(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        if (preg_match('#^/([^/]+)/(?:admin|secretary|resident|signup)(?:/|$)#', $script, $matches)) {
            $base = '/' . $matches[1] . '/';
            return $base;
        }

        $dir = dirname($script);
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            $base = '/';
            return $base;
        }

        $base = rtrim($dir, '/') . '/';
        return $base;
    }
}

if (!function_exists('barangay_start_session')) {
    function barangay_start_session(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
        $crossOrigin = function_exists('barangay_cors_is_cross_origin_enabled')
            && barangay_cors_is_cross_origin_enabled();

        // SameSite=None requires Secure; only when FRONTEND_URL(S) is configured.
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => barangay_app_base_path(),
            'secure' => $crossOrigin ? true : $isHttps,
            'httponly' => true,
            'samesite' => $crossOrigin ? 'None' : 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('barangay_release_session_lock')) {
    /**
     * Release the session file lock before slow work so other tabs/requests are not blocked.
     */
    function barangay_release_session_lock(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}

if (!function_exists('barangay_should_release_session_lock')) {
    function barangay_should_release_session_lock(): bool
    {
        return barangay_is_datatables_endpoint();
    }
}

if (!function_exists('barangay_is_datatables_endpoint')) {
    /**
     * True for read-only DataTables JSON endpoints (script name ends with Table.php).
     * CSRF must not be skipped for other POSTs merely because draw=1 is present.
     */
    function barangay_is_datatables_endpoint(): bool
    {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return (bool) preg_match('/Table\.php$/i', $script);
    }
}

if (!function_exists('barangay_request_expects_json')) {
    function barangay_request_expects_json(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (str_contains($accept, 'application/json')) {
            return true;
        }
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($requestedWith === 'xmlhttprequest') {
            return true;
        }

        return isset($_POST['ajax']) || isset($_GET['ajax']);
    }
}

if (!function_exists('barangay_require_post')) {
    /**
     * Reject non-POST requests for state-changing endpoints (blocks GET CSRF).
     */
    function barangay_require_post(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            return;
        }
        http_response_code(405);
        header('Allow: POST');
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: text/plain; charset=utf-8');
            exit('Method not allowed');
        }
        exit('Method not allowed');
    }
}

if (!function_exists('barangay_deny_access')) {
    function barangay_deny_access(string $loginPath = '../login.php'): void
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit('Unauthorized');
        }
        header('Location: ' . $loginPath);
        exit;
    }
}

if (!function_exists('barangay_h')) {
    function barangay_h(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('barangay_html_date')) {
    /** HTML date input value (Y-m-d). Empty for missing or invalid dates. */
    function barangay_html_date(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }
        $ts = strtotime($value);

        return $ts ? date('Y-m-d', $ts) : '';
    }
}

if (!function_exists('barangay_verify_password')) {
  function barangay_verify_password(string $plain, string $stored): bool
  {
    if ($stored === '') {
      return false;
    }
    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon2')) {
      return password_verify($plain, $stored);
    }
    return hash_equals($stored, $plain);
  }
}

if (!function_exists('barangay_hash_password')) {
  function barangay_hash_password(string $plain): string
  {
    return password_hash($plain, PASSWORD_DEFAULT);
  }
}
