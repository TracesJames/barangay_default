<?php

/**
 * DB connection — prefers config outside webroot:
 *   C:\xampp\secure\barangay_db\barangay_db.php
 * Fallback: C:\xampp\secure\barangay_db.php (shim), then env BARANGAY_DB_*, then local XAMPP defaults.
 */

// Block direct browser hits to this file (include-only).
if (isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

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
    ];
}

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

try {
    $con = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($con->connect_error) {
        // Temporary fallback for local XAMPP if dedicated user is not ready yet.
        if (DB_USER !== 'root') {
            $fallback = @new mysqli(DB_HOST, 'root', '', DB_NAME);
            if (!$fallback->connect_error) {
                $con = $fallback;
            } else {
                die('Connection failed: ' . $con->connect_error);
            }
        } else {
            die('Connection failed: ' . $con->connect_error);
        }
    }
    $con->set_charset('utf8mb4');
} catch (Exception $e) {
    die('Connection failed.');
}
