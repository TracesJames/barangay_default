<?php

require_once __DIR__ . '/bootstrap.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    nutrition_api_error('Method not allowed. Use GET.', 405);
}

$auth = nutrition_api_authenticate($con);
$override = trim((string) ($_GET['barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $override !== '' ? $override : null);

$limit = (int) ($_GET['limit'] ?? 100);
$limit = max(1, min(500, $limit));

$rows = nutrition_list_incomplete_household_surveys($con, $ctx['barangay_id'], $limit);
$list = [];
foreach ($rows as $row) {
    $purok = (string) ($row['purok_label'] ?? '');
    if (!staff_user_can_access_purok($con, $auth['user_id'], $purok)) {
        continue;
    }
    $list[] = [
        'survey_id' => (string) ($row['survey_id'] ?? ''),
        'house_hold_id' => (string) ($row['house_hold_id'] ?? ''),
        'purok_label' => $purok,
        'household_head' => (string) ($row['household_head'] ?? ''),
        'head_last_name' => (string) ($row['head_last_name'] ?? ''),
        'head_first_name' => (string) ($row['head_first_name'] ?? ''),
        'survey_date' => (string) ($row['survey_date'] ?? ''),
        'children_count' => (int) ($row['children_count'] ?? 0),
        'members_count' => (int) ($row['members_count'] ?? 0),
        'member_rows' => (int) ($row['member_rows'] ?? 0),
        'assessed_rows' => (int) ($row['assessed_rows'] ?? 0),
        'issue' => (string) ($row['issue'] ?? 'incomplete'),
        'issue_label' => (string) ($row['issue_label'] ?? 'Incomplete survey data'),
    ];
}

nutrition_api_send([
    'ok' => true,
    'barangay_id' => $ctx['barangay_id'],
    'barangay_name' => $ctx['barangay_name'],
    'count' => count($list),
    'incomplete' => $list,
]);
