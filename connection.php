<?php

/**
 * DB connection — prefers config outside webroot:
 *   C:\xampp\secure\barangay_db\barangay_db.php
 * Fallback: C:\xampp\secure\barangay_db.php (shim), then env BARANGAY_DB_*, then local XAMPP defaults.
 *
 * CORS: .env FRONTEND_URL / FRONTEND_URLS (includes/env.php), then config frontend_url / frontend_urls.
 */

// Block direct browser hits to this file (include-only).
if (isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/includes/env.php';

$secureRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'secure'; // C:\xampp\secure when app is htdocs\barangay_default
$dbConfigCandidates = [
    $secureRoot . DIRECTORY_SEPARATOR . 'barangay_db' . DIRECTORY_SEPARATOR . 'barangay_db.php',
    $secureRoot . DIRECTORY_SEPARATOR . 'barangay_db.php',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'secure' . DIRECTORY_SEPARATOR . 'barangay_db' . DIRECTORY_SEPARATOR . 'barangay_db.php',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'secure' . DIRECTORY_SEPARATOR . 'barangay_db.php',
];

$dbConfig = null;
foreach ($dbConfigCandidates as $candidate) {
    $real = realpath($candidate);
    if ($real !== false && is_file($real)) {
        // Discard accidental UTF-8 BOM / whitespace from config so sessions stay writable.
        ob_start();
        $dbConfig = require $real;
        ob_end_clean();
        break;
    }
}

if (!is_array($dbConfig)) {
    $dbConfig = [
        'host' => getenv('BARANGAY_DB_HOST') ?: 'localhost',
        'user' => getenv('BARANGAY_DB_USER') ?: 'barangay_app',
        'password' => getenv('BARANGAY_DB_PASSWORD') !== false ? (string) getenv('BARANGAY_DB_PASSWORD') : '',
        'name' => getenv('BARANGAY_DB_NAME') ?: 'barangay',
        'frontend_url' => getenv('FRONTEND_URL') ?: '',
    ];
}

if (getenv('FRONTEND_URL') === false || getenv('FRONTEND_URL') === '') {
    $frontendUrl = trim((string) ($dbConfig['frontend_url'] ?? ''));
    if ($frontendUrl !== '') {
        putenv('FRONTEND_URL=' . $frontendUrl);
        $_ENV['FRONTEND_URL'] = $frontendUrl;
    }
}
if (getenv('FRONTEND_URLS') === false || getenv('FRONTEND_URLS') === '') {
    $frontendUrls = trim((string) ($dbConfig['frontend_urls'] ?? ''));
    if ($frontendUrls !== '') {
        putenv('FRONTEND_URLS=' . $frontendUrls);
        $_ENV['FRONTEND_URLS'] = $frontendUrls;
    }
}

require_once __DIR__ . '/includes/cors.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/security.php';
barangay_send_security_headers();

if (!defined('DB_HOST')) {
    define('DB_HOST', (string) ($dbConfig['host'] ?? 'localhost'));
}
if (!defined('DB_USER')) {
    define('DB_USER', (string) ($dbConfig['user'] ?? 'barangay_app'));
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', (string) ($dbConfig['password'] ?? ''));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', (string) ($dbConfig['name'] ?? 'barangay'));
}

if (!function_exists('barangay_die_connection_failed')) {
    function barangay_die_connection_failed(string $detail): void
    {
        $wantsJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_POST['ajax'])
            || isset($_GET['ajax'])
            || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        http_response_code(503);
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Database is not running. Start MySQL in XAMPP, then refresh this page.',
                'detail' => 'Connection failed: ' . $detail,
            ]);
            exit;
        }
        exit('Connection failed: ' . $detail);
    }
}

$previousMysqliReport = mysqli_report(MYSQLI_REPORT_OFF);
try {
    $con = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $connectError = $con->connect_error ?: '';
    if ($connectError !== '') {
        // Temporary fallback for local XAMPP if dedicated user is not ready yet.
        if (DB_USER !== 'root') {
            $fallback = @new mysqli(DB_HOST, 'root', '', DB_NAME);
            if (!$fallback->connect_error) {
                $con = $fallback;
                $connectError = '';
            }
        }
    }
    if ($connectError !== '' || !($con instanceof mysqli) || $con->connect_error) {
        $detail = $connectError !== '' ? $connectError : ($con->connect_error ?? 'unknown error');
        barangay_die_connection_failed($detail);
    }
    $con->set_charset('utf8mb4');
} catch (Throwable $e) {
    barangay_die_connection_failed($e->getMessage());
} finally {
    mysqli_report($previousMysqliReport);
}
