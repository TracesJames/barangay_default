<?php

/**
 * CLI: set must_change_password=1 for accounts listed in
 * C:\xampp\secure\barangay_ops\rotated_bns_passwords.json
 *
 * Usage (from project root):
 *   php scripts/flag_rotated_bns_must_change_password.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';
require_once dirname(__DIR__) . '/includes/api_auth.php';

$jsonPath = 'C:\\xampp\\secure\\barangay_ops\\rotated_bns_passwords.json';
if (!is_file($jsonPath)) {
    fwrite(STDERR, "Missing $jsonPath\n");
    exit(1);
}

$raw = (string) file_get_contents($jsonPath);
// Normalize encoding / strip BOM.
if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
    $raw = substr($raw, 3);
}
if (!mb_check_encoding($raw, 'UTF-8')) {
    $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
}
$raw = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{0080}-\x{FFFF}]/u', '', $raw) ?? $raw;
$data = json_decode(trim($raw), true);
if (!is_array($data)) {
    // Fallback: flag known usernames from a simple scan.
    fwrite(STDERR, "JSON parse warning: " . json_last_error_msg() . " — trying username scan.\n");
    if (preg_match_all('/"username"\s*:\s*"([^"]+)"/', $raw, $m)) {
        $data = ['accounts' => array_map(static fn ($u) => ['username' => $u], $m[1])];
    } else {
        fwrite(STDERR, "Invalid JSON in $jsonPath\n");
        exit(1);
    }
}

$accounts = $data['accounts'] ?? [];
if (!is_array($accounts) || $accounts === []) {
    echo "No accounts listed.\n";
    exit(0);
}

nutrition_api_ensure_must_change_password_column($con);

$flagged = 0;
$missing = 0;
foreach ($accounts as $row) {
    if (!is_array($row)) {
        continue;
    }
    $username = trim((string) ($row['username'] ?? ''));
    if ($username === '') {
        continue;
    }
    $stmt = $con->prepare('SELECT id, username FROM users WHERE username = ? OR id = ? LIMIT 1');
    if (!$stmt) {
        fwrite(STDERR, "Lookup failed for $username\n");
        continue;
    }
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) {
        echo "MISSING\t$username\n";
        $missing++;
        continue;
    }
    $userId = (string) $user['id'];
    if (nutrition_api_set_must_change_password($con, $userId, true)) {
        echo "FLAGGED\t$username\t$userId\n";
        $flagged++;
    } else {
        echo "FAILED\t$username\t$userId\n";
    }
}

echo "Done. flagged=$flagged missing=$missing\n";
