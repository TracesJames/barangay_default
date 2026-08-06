<?php
/**
 * Add indigenous (IP) flag to residence_status.
 */
require_once __DIR__ . '/../connection.php';

$check = $con->query("SHOW COLUMNS FROM `residence_status` LIKE 'indigenous'");
if ($check && $check->num_rows === 0) {
    $con->query("ALTER TABLE `residence_status` ADD COLUMN `indigenous` VARCHAR(69) NOT NULL DEFAULT 'NO' AFTER `single_parent`");
    echo "Added indigenous column to residence_status\n";
} else {
    echo "indigenous column already exists on residence_status\n";
}

echo "Done.\n";
