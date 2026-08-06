<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

$names = ['Barangay', 'Barangay San Jose', 'Barangay San Roque'];

foreach ($names as $name) {
    $select = $con->prepare('SELECT id, barangay FROM barangay_information WHERE barangay = ?');
    $select->bind_param('s', $name);
    $select->execute();
    $row = $select->get_result()->fetch_assoc();
    if (!$row) {
        echo "Not found: $name\n";
        continue;
    }

    $result = barangay_delete($con, (string) $row['id']);
    if ($result['ok']) {
        echo 'Deleted: ' . $row['barangay'] . ' (' . $row['id'] . ")\n";
        continue;
    }

    echo 'Failed: ' . $row['barangay'] . ' — ' . ($result['error'] ?? 'unknown error') . "\n";
}

$count = $con->query('SELECT COUNT(*) AS c FROM barangay_information')->fetch_assoc()['c'] ?? 0;
echo "Remaining barangays: $count\n";
