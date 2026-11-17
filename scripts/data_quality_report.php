<?php

/**
 * CLI data-quality report for Hub + Nutrition.
 *
 * php scripts/data_quality_report.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$root = dirname(__DIR__);
require_once $root . '/connection.php';
require_once $root . '/includes/staff_permissions.php';

echo "=== Data quality report " . date('c') . " ===\n";
echo "DB " . DB_HOST . ':' . DB_PORT . '/' . DB_NAME . "\n\n";

$bnsRole = STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR;
echo "-- BNS with 0 purok assignments --\n";
$sql = "SELECT u.id, u.username,
            CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,'')) AS name,
            IFNULL(bi.barangay, '(none)') AS barangay
        FROM users u
        LEFT JOIN barangay_information bi ON bi.id = u.barangay_id
        WHERE u.staff_role = ?
          AND (SELECT COUNT(*) FROM staff_purok_assignment spa WHERE spa.user_id = u.id) = 0
        ORDER BY barangay, u.username";
$stmt = $con->prepare($sql);
$zeroBns = 0;
if ($stmt) {
    $stmt->bind_param('s', $bnsRole);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $zeroBns++;
        echo sprintf(
            "  %s | %s | %s | user_id=%s\n",
            $row['barangay'],
            $row['username'],
            trim((string) $row['name']),
            $row['id']
        );
    }
    $stmt->close();
}
echo $zeroBns === 0 ? "  (none)\n" : "  total: {$zeroBns}\n";
echo "\n";

echo "-- Surveys with children_count > 0 but no family member rows --\n";
$q = "SELECT nhs.survey_id,
            IFNULL(bi.barangay, '') AS barangay,
            nhs.house_hold_id,
            nhs.purok_label,
            nhs.children_count,
            nhs.members_count,
            nhs.survey_date,
            nhs.household_head
        FROM nutrition_household_survey nhs
        LEFT JOIN barangay_information bi ON bi.id = nhs.barangay_id
        WHERE nhs.children_count > 0
          AND IFNULL(nhs.moved_out, 'NO') <> 'YES'
          AND NOT EXISTS (
              SELECT 1 FROM nutrition_household_family_member m
              WHERE m.survey_id = nhs.survey_id
          )
        ORDER BY barangay, nhs.house_hold_id";
$incomplete = 0;
$res = $con->query($q);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $incomplete++;
        echo sprintf(
            "  %s | %s | purok=%s | children=%d | head=%s | survey=%s\n",
            $row['barangay'],
            $row['house_hold_id'],
            $row['purok_label'],
            (int) $row['children_count'],
            $row['household_head'],
            $row['survey_id']
        );
    }
}
echo $incomplete === 0 ? "  (none)\n" : "  total: {$incomplete}\n";
echo "\nAction: open each survey in Nutrition Hub and re-save members so e-OPT/status counts are complete.\n";
echo "Action: assign puroks for zero-purok BNS under Staff Accounts.\n";
exit(0);
