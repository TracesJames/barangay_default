<?php
require_once __DIR__ . '/../connection.php';

$check = $con->query("SHOW COLUMNS FROM `users` LIKE 'barangay_id'");
if ($check && $check->num_rows === 0) {
    $con->query("ALTER TABLE `users` ADD COLUMN `barangay_id` VARCHAR(255) NULL AFTER `user_type`");
    echo "Added barangay_id to users\n";
} else {
    echo "barangay_id already exists on users\n";
}

echo "Done.\n";
