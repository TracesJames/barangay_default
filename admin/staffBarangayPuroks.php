<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_accounts.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');

$barangayId = trim((string) ($_GET['barangay_id'] ?? $_POST['barangay_id'] ?? ''));
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing barangay', 'options' => []]);
    exit;
}

if (!staff_user_can_access_barangay($con, (string) ($_SESSION['user_id'] ?? ''), $barangayId)
    && !barangay_user_can_pick_barangay($con, (string) ($_SESSION['user_id'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied', 'options' => []]);
    exit;
}

$options = [];
$seen = [];
foreach (barangay_list_puroks($con, $barangayId) as $row) {
    $label = trim((string) ($row['purok'] ?? ''));
    if ($label === '') {
        continue;
    }
    $key = function_exists('barangay_purok_master_key')
        ? barangay_purok_master_key($label)
        : mb_strtolower($label);
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $options[] = [
        'value' => $label,
        'label' => $label,
        'purok_id' => trim((string) ($row['purok_id'] ?? '')),
        'input' => nutrition_purok_input_from_label($label) ?: $label,
    ];
}

usort($options, static function (array $a, array $b): int {
    return strnatcasecmp((string) $a['label'], (string) $b['label']);
});

$assignments = function_exists('staff_purok_bns_assignments')
    ? staff_purok_bns_assignments($con, $barangayId)
    : ['all' => [], 'by_purok' => []];
$allNames = $assignments['all'] ?? [];
$byPurok = $assignments['by_purok'] ?? [];
foreach ($options as &$option) {
    $value = (string) ($option['value'] ?? '');
    $notes = [];
    if ($allNames !== []) {
        $notes[] = 'All-scope BNS: ' . implode(', ', $allNames);
    }
    $specific = $byPurok[$value] ?? $byPurok[staff_normalize_purok_label($value)] ?? [];
    if (is_array($specific) && $specific !== []) {
        $notes[] = 'Assigned: ' . implode(', ', $specific);
    }
    $option['note'] = implode(' · ', $notes);
}
unset($option);

echo json_encode([
    'ok' => true,
    'barangay_id' => $barangayId,
    'options' => $options,
]);
