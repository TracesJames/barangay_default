<?php

require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    barangay_start_session();
}

if (!function_exists('csrf_token')) {
    /**
     * Return the request CSRF token, creating and persisting it if needed.
     * Safe to call after barangay_release_session_lock()/session_write_close():
     * a closed session is reopened briefly so a newly minted token is written to disk.
     */
    function csrf_token(): string
    {
        static $cached = null;
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $reopened = false;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            barangay_start_session();
            $reopened = true;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $cached = (string) $_SESSION['csrf_token'];

        // Persist a newly available token when the page had already released the session lock.
        if ($reopened) {
            session_write_close();
        }

        return $cached;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . barangay_h(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_request_token')) {
    function csrf_request_token(): string
    {
        $token = $_POST['csrf_token'] ?? '';
        if ($token !== '') {
            return (string) $token;
        }

        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strtolower((string) $name) === 'x-csrf-token' && $value !== '') {
                        return (string) $value;
                    }
                }
            }
        }

        return '';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): void
    {
        $token = csrf_request_token();
        if ($token === '' || empty($_SESSION['csrf_token'])
            || !hash_equals($_SESSION['csrf_token'], $token)) {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            http_response_code(403);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Invalid CSRF token. Please refresh the page and try again.']);
                exit;
            }
            exit('Invalid CSRF token');
        }
    }
}
