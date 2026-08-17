<?php

/**
 * Authenticated CORS for native PHP.
 * Exact origins only — never '*'. Include early in the request lifecycle.
 *
 * Origins: .env FRONTEND_URL / FRONTEND_URLS, or secure config frontend_url / frontend_urls.
 */

if (!function_exists('barangay_cors_allowed_origins')) {
    /**
     * @return array<int, string>
     */
    function barangay_cors_allowed_origins(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $parts = [];
        foreach (['FRONTEND_URL', 'FRONTEND_URLS'] as $key) {
            $raw = getenv($key);
            if ($raw === false || $raw === '') {
                $raw = (string) ($_ENV[$key] ?? '');
            }
            if ($raw !== '') {
                $parts[] = $raw;
            }
        }

        $blob = implode(',', $parts);
        $origins = [];
        foreach (explode(',', $blob) as $item) {
            $item = rtrim(trim($item), '/');
            if ($item !== '' && $item !== '*') {
                $origins[] = $item;
            }
        }

        $cached = array_values(array_unique($origins));

        return $cached;
    }
}

if (!function_exists('barangay_cors_is_cross_origin_enabled')) {
    function barangay_cors_is_cross_origin_enabled(): bool
    {
        return barangay_cors_allowed_origins() !== [];
    }
}

if (!function_exists('barangay_cors_apply')) {
    function barangay_cors_apply(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $origin = rtrim(trim((string) ($_SERVER['HTTP_ORIGIN'] ?? '')), '/');
        $allowed = barangay_cors_allowed_origins();

        if ($origin === '' || $allowed === []) {
            if ($method === 'OPTIONS' && $allowed === []) {
                http_response_code(204);
                exit;
            }

            return;
        }

        if (!in_array($origin, $allowed, true)) {
            if ($method === 'OPTIONS') {
                http_response_code(403);
                exit;
            }

            return;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');

        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

barangay_cors_apply();
