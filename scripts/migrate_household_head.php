<?php

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

if (!barangay_column_exists($con, 'residence_status', 'household_head')) {
    $con->query("ALTER TABLE residence_status ADD COLUMN household_head VARCHAR(69) NOT NULL DEFAULT 'NO' AFTER single_parent");
    echo "Added residence_status.household_head\n";
} else {
    echo "residence_status.household_head already exists\n";
}

$indexName = 'idx_residence_status_household_head';
$indexCheck = $con->query("SHOW INDEX FROM residence_status WHERE Key_name = '$indexName'");
if ($indexCheck && $indexCheck->num_rows === 0) {
    $con->query("ALTER TABLE residence_status ADD INDEX $indexName (barangay_id, household_head, archive)");
    echo "Added index $indexName\n";
}

echo "Done.\n";
