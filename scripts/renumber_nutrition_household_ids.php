<?php
/**
 * Renumber nutrition household IDs so the 5-digit series is unique per barangay
 * (across all puroks). Format stays: {PSGC}-P{n}-{#####}
 *
 * Usage: php scripts/renumber_nutrition_household_ids.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/nutrition_context.php';

nutrition_ensure_module_tables($con);

$result = $con->query(
    "SELECT survey_id, barangay_id, house_hold_id, purok_label, date_created
     FROM nutrition_household_survey
     ORDER BY barangay_id ASC, date_created ASC, survey_id ASC"
);
if (!$result) {
    fwrite(STDERR, "Query failed: " . $con->error . PHP_EOL);
    exit(1);
}

$rowsByBarangay = [];
while ($row = $result->fetch_assoc()) {
    $bid = (string) ($row['barangay_id'] ?? '');
    if ($bid === '') {
        continue;
    }
    if (!isset($rowsByBarangay[$bid])) {
        $rowsByBarangay[$bid] = [];
    }
    $rowsByBarangay[$bid][] = $row;
}

$update = $con->prepare(
    'UPDATE nutrition_household_survey SET house_hold_id = ? WHERE survey_id = ?'
);
if (!$update) {
    fwrite(STDERR, "Prepare failed: " . $con->error . PHP_EOL);
    exit(1);
}

$changed = 0;
$unchanged = 0;

foreach ($rowsByBarangay as $barangayKey => $rows) {
    $barangayId = (string) $barangayKey;
    $barangayName = '';
    $nameStmt = $con->prepare('SELECT barangay FROM barangay_information WHERE id = ? LIMIT 1');
    if ($nameStmt) {
        $nameStmt->bind_param('s', $barangayId);
        $nameStmt->execute();
        $nameRow = $nameStmt->get_result()->fetch_assoc();
        $nameStmt->close();
        $barangayName = (string) ($nameRow['barangay'] ?? '');
    }

    $psfc = nutrition_barangay_psgc_code($con, $barangayId, $barangayName);
    if ($psfc === '') {
        fwrite(STDERR, "Skip barangay {$barangayId}: no PSGC\n");
        continue;
    }

    $series = 1;
    foreach ($rows as $row) {
        $surveyId = (string) ($row['survey_id'] ?? '');
        $oldId = (string) ($row['house_hold_id'] ?? '');
        $purokLabel = trim((string) ($row['purok_label'] ?? ''));

        $purokCode = '';
        if (preg_match('/-(P\d+)-/', $oldId, $m)) {
            $purokCode = $m[1];
        }
        if ($purokCode === '') {
            $purokCode = nutrition_purok_code_from_label($purokLabel !== '' ? $purokLabel : '1');
        }
        if ($purokCode === '') {
            $purokCode = 'P1';
        }

        $newId = nutrition_format_household_reference($psfc, $purokCode, $series);
        $series++;

        if ($newId === $oldId) {
            $unchanged++;
            continue;
        }

        $update->bind_param('ss', $newId, $surveyId);
        if (!$update->execute()) {
            fwrite(STDERR, "Failed {$surveyId}: {$update->error}\n");
            continue;
        }
        $changed++;
        echo "{$oldId} -> {$newId}\n";
    }
}

$update->close();
echo PHP_EOL . "Done. Updated: {$changed}, unchanged: {$unchanged}" . PHP_EOL;
