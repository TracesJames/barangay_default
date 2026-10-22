<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

$auth = nutrition_api_authenticate($con);
$body = nutrition_api_json_body();
$override = trim((string) ($_GET['barangay_id'] ?? $body['barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $override !== '' ? $override : null);

$filters = [];
if (barangay_user_is_barangay_nutrition_scholar($con, $auth['user_id'])) {
    $assigned = staff_assigned_purok_labels($con, $auth['user_id'], $ctx['barangay_id']);
    if ($assigned !== []) {
        $filters['purok_in'] = $assigned;
    }
}

$totals = nutrition_scoped_totals($con, $ctx['barangay_id']);
$rows = nutrition_list_household_surveys($con, $ctx['barangay_id'], 200, $filters);
$consolidated = [];
foreach ($rows as $row) {
    $consolidated[] = [
        'survey_id' => (string) ($row['survey_id'] ?? ''),
        'house_hold_id' => (string) ($row['house_hold_id'] ?? ''),
        'purok_label' => (string) ($row['purok_label'] ?? ''),
        'household_head' => (string) ($row['household_head'] ?? ''),
        'survey_date' => (string) ($row['survey_date'] ?? ''),
        'children_count' => (int) ($row['children_count'] ?? 0),
        'members_count' => (int) ($row['members_count'] ?? 0),
        'has_pregnant' => (string) ($row['has_pregnant'] ?? 'NO'),
        'has_lactating' => (string) ($row['has_lactating'] ?? 'NO'),
        'toilet_type' => (string) ($row['toilet_type'] ?? ''),
        'water_source' => (string) ($row['water_source'] ?? ''),
    ];
}

nutrition_api_send([
    'ok' => true,
    'barangay_id' => $ctx['barangay_id'],
    'barangay_name' => $ctx['barangay_name'],
    'summary' => [
        'households' => count($consolidated),
        'children' => (int) ($totals['children'] ?? 0),
        'assessed' => (int) ($totals['assessed'] ?? 0),
        'pending' => (int) ($totals['pending'] ?? 0),
        'at_risk' => (int) ($totals['at_risk'] ?? 0),
        'normal' => (int) ($totals['normal'] ?? 0),
        'underweight' => (int) ($totals['underweight'] ?? 0),
        'wasted' => (int) ($totals['wasted'] ?? 0),
        'severely_wasted' => (int) ($totals['severely_wasted'] ?? 0),
        'stunted' => (int) ($totals['stunted'] ?? 0),
        'overweight' => (int) ($totals['overweight'] ?? 0),
        'obese' => (int) ($totals['obese'] ?? 0),
        'pregnant' => (int) ($totals['pregnant'] ?? 0),
        'teenage_pregnant' => (int) ($totals['teenage_pregnant'] ?? 0),
    ],
    'consolidated' => $consolidated,
]);
