<?php
/**
 * Add indexes for large resident datasets.
 */
require_once __DIR__ . '/../connection.php';

$indexes = [
    'residence_status' => [
        'idx_rs_barangay_archive' => 'barangay_id, archive',
        'idx_rs_residence_id' => 'residence_id',
        'idx_rs_archive_flags' => 'archive, voters, pwd, single_parent, senior',
    ],
    'residence_information' => [
        'idx_ri_residence_id' => 'residence_id',
        'idx_ri_birth_date' => 'birth_date',
        'idx_ri_national_number' => 'national_number',
        'idx_ri_name_search' => 'last_name, first_name',
    ],
    'users' => [
        'idx_users_barangay_type' => 'barangay_id, user_type',
    ],
];

foreach ($indexes as $table => $tableIndexes) {
    foreach ($tableIndexes as $indexName => $columns) {
        $check = $con->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
        if ($check && $check->num_rows > 0) {
            echo "Exists: $table.$indexName\n";
            continue;
        }

        $sql = "ALTER TABLE `$table` ADD INDEX `$indexName` ($columns)";
        if ($con->query($sql)) {
            echo "Added: $table.$indexName\n";
        } else {
            echo "Failed: $table.$indexName — {$con->error}\n";
        }
    }
}

echo "Done.\n";
