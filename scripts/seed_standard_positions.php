<?php
/**
 * Ensure each barangay can have the standard council structure:
 * 1 Chairman, 7 Kagawad, 1 Secretary, 1 Treasurer, 1 IP Representative, 1 SK Chairman, 7 SK Kagawad.
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

date_default_timezone_set('Asia/Manila');

foreach (barangay_standard_positions() as $standard) {
    $name = $standard['position'];
    $limit = (string) $standard['limit'];
    $description = $standard['description'];
    $color = $standard['color'];

    $stmt = $con->prepare('SELECT position_id FROM position WHERE LOWER(position) = LOWER(?) LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $positionId = $existing['position_id'];
        $update = $con->prepare(
            'UPDATE position SET position = ?, position_limit = ?, position_description = ? WHERE position_id = ?'
        );
        $update->bind_param('ssss', $name, $limit, $description, $positionId);
        $update->execute();
        echo "Updated position: {$name} (limit {$limit})\n";
        continue;
    }

    $date = new DateTime();
    $positionId = str_shuffle((string) hexdec(uniqid())) . $date->format('mdYHisv');

    $insert = $con->prepare(
        'INSERT INTO position (position_id, position, position_limit, position_description, color) VALUES (?, ?, ?, ?, ?)'
    );
    $insert->bind_param('sssss', $positionId, $name, $limit, $description, $color);
    $insert->execute();
    echo "Added position: {$name} (limit {$limit})\n";
}

echo "Standard positions seeded.\n";
