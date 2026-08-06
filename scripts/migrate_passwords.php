<?php

/**
 * One-time migration: hash plaintext passwords in the users table.
 * Run from CLI: php scripts/migrate_passwords.php
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$sql = 'SELECT id, password FROM users';
$result = $con->query($sql);

if (!$result) {
    fwrite(STDERR, "Query failed: {$con->error}\n");
    exit(1);
}

$updated = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $stored = (string) $row['password'];
    if ($stored === '') {
        $skipped++;
        continue;
    }
    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon2')) {
        $skipped++;
        continue;
    }

    $hash = barangay_hash_password($stored);
    $stmt = $con->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bind_param('ss', $hash, $row['id']);
    $stmt->execute();
    $stmt->close();
    $updated++;
}

echo "Password migration complete. Updated: {$updated}, skipped: {$skipped}\n";
