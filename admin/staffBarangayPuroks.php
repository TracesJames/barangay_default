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

foreach (barangay_purok_filter_options($con, $barangayId) as $opt) {
    $raw = trim((string) ($opt['value'] ?? $opt['label'] ?? ''));
    if ($raw === '') {
        continue;
    }
    $label = staff_normalize_purok_label($raw);
    if ($label === '' || isset($seen[$label])) {
        continue;
    }
    $seen[$label] = true;
    $options[] = [
        'value' => $label,
        'label' => $label,
        'input' => nutrition_purok_input_from_label($label),
    ];
}

if (function_exists('nutrition_list_household_puroks')) {
    foreach (nutrition_list_household_puroks($con, $barangayId) as $raw) {
        $label = staff_normalize_purok_label((string) $raw);
        if ($label === '' || isset($seen[$label])) {
            continue;
        }
        $seen[$label] = true;
        $options[] = [
            'value' => $label,
            'label' => $label,
            'input' => nutrition_purok_input_from_label($label),
        ];
    }
}

// Fallback common puroks when barangay has none configured yet.
if ($options === []) {
    for ($i = 1; $i <= 10; $i++) {
        $label = nutrition_purok_label_from_number((string) $i);
        $options[] = [
            'value' => $label,
            'label' => $label,
            'input' => (string) $i,
        ];
    }
}

usort($options, static function (array $a, array $b): int {
    return strnatcasecmp((string) $a['label'], (string) $b['label']);
});

echo json_encode(['ok' => true, 'options' => $options]);
