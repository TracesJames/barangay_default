<?php

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

$check = $con->query("SHOW COLUMNS FROM `users` LIKE 'staff_role'");
if ($check && $check->num_rows === 0) {
    $con->query("ALTER TABLE `users` ADD COLUMN `staff_role` VARCHAR(69) NOT NULL DEFAULT '' AFTER `user_type`");
    echo "Added staff_role column to users\n";
} else {
    echo "staff_role column already exists\n";
}

if (!barangay_column_exists($con, 'users', 'staff_role')) {
    exit("Migration failed.\n");
}

$con->query("UPDATE users SET staff_role = 'super_admin' WHERE user_type = 'admin' AND (barangay_id IS NULL OR barangay_id = '') AND (staff_role = '' OR staff_role IS NULL)");
$con->query("UPDATE users SET staff_role = 'barangay_admin' WHERE user_type = 'admin' AND barangay_id IS NOT NULL AND barangay_id != '' AND (staff_role = '' OR staff_role IS NULL)");
$con->query("UPDATE users SET staff_role = 'barangay_staff' WHERE user_type = 'secretary' AND (staff_role = '' OR staff_role IS NULL)");

echo "Backfilled staff_role values\n";
echo "Done.\n";
