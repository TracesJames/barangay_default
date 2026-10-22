<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

$auth = nutrition_api_authenticate($con);
$body = nutrition_api_json_body();
$override = trim((string) ($_GET['barangay_id'] ?? $body['barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $override !== '' ? $override : null);
$totals = nutrition_scoped_totals($con, $ctx['barangay_id']);

nutrition_api_send([
    'ok' => true,
    'barangay_id' => $ctx['barangay_id'],
    'barangay_name' => $ctx['barangay_name'],
    'totals' => [
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
        'this_month' => (int) ($totals['this_month'] ?? 0),
        'pregnant' => (int) ($totals['pregnant'] ?? 0),
        'teenage_pregnant' => (int) ($totals['teenage_pregnant'] ?? 0),
    ],
]);
