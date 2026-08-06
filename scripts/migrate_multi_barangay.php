<?php
/**
 * Add barangay_id to operational tables and seed sample barangays.
 */
require_once __DIR__ . '/../connection.php';

$tables = [
    'residence_status' => 'residence_id',
    'official_status' => 'official_id',
    'blotter_record' => 'blotter_id',
    'purok' => 'purok_id',
];

foreach ($tables as $table => $afterColumn) {
    $check = $con->query("SHOW COLUMNS FROM `$table` LIKE 'barangay_id'");
    if ($check && $check->num_rows === 0) {
        $con->query("ALTER TABLE `$table` ADD COLUMN `barangay_id` VARCHAR(255) NULL AFTER `$afterColumn`");
        echo "Added barangay_id to $table\n";
    } else {
        echo "barangay_id already exists on $table\n";
    }
}

$defaultRow = $con->query('SELECT id FROM barangay_information ORDER BY barangay ASC LIMIT 1')->fetch_assoc();
$defaultId = $defaultRow['id'] ?? null;

if ($defaultId) {
    foreach (array_keys($tables) as $table) {
        $stmt = $con->prepare("UPDATE `$table` SET barangay_id = ? WHERE barangay_id IS NULL OR barangay_id = ''");
        $stmt->bind_param('s', $defaultId);
        $stmt->execute();
        echo "Backfilled $table: {$stmt->affected_rows} row(s)\n";
    }
}

$countResult = $con->query('SELECT COUNT(*) AS total FROM barangay_information');
$count = (int) ($countResult->fetch_assoc()['total'] ?? 0);

if ($count === 0) {
    echo "No barangays found. Run scripts/seed_valencia_barangays.php to seed Valencia City barangays.\n";
}

echo "Migration complete.\n";
