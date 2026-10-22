<?php

/**
 * Shared bootstrap for Nutrition Portal mobile API (v1).
 * Does not use web session auth / CSRF.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// CORS for Flutter web / local tools. Native mobile apps usually send no Origin.
$origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
$allowedOrigins = [];
if (getenv('FRONTEND_URL') !== false && trim((string) getenv('FRONTEND_URL')) !== '') {
    $allowedOrigins[] = rtrim(trim((string) getenv('FRONTEND_URL')), '/');
}
if (getenv('FRONTEND_URLS') !== false && trim((string) getenv('FRONTEND_URLS')) !== '') {
    foreach (explode(',', (string) getenv('FRONTEND_URLS')) as $part) {
        $part = rtrim(trim($part), '/');
        if ($part !== '') {
            $allowedOrigins[] = $part;
        }
    }
}
// Local Flutter web / Chrome defaults when FRONTEND_URL unset.
$localDevOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:7357',
    'http://127.0.0.1:7357',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
];
foreach ($localDevOrigins as $devOrigin) {
    if (!in_array($devOrigin, $allowedOrigins, true)) {
        $allowedOrigins[] = $devOrigin;
    }
}
// Also allow any localhost / 127.0.0.1 port in development (XAMPP).
$isLocalOrigin = $origin !== '' && (bool) preg_match(
    '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#i',
    $origin
);
if ($origin !== '' && ($isLocalOrigin || in_array(rtrim($origin, '/'), $allowedOrigins, true))) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}
if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once dirname(__DIR__, 2) . '/connection.php';
require_once dirname(__DIR__, 2) . '/includes/barangay_context.php';
require_once dirname(__DIR__, 2) . '/includes/staff_permissions.php';
require_once dirname(__DIR__, 2) . '/includes/nutrition_context.php';
require_once dirname(__DIR__, 2) . '/includes/api_auth.php';

nutrition_api_ensure_tokens_table($con);

if (function_exists('nutrition_ensure_module_tables')) {
    nutrition_ensure_module_tables($con);
}
