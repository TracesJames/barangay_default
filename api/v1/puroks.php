<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

$auth = nutrition_api_authenticate($con);
$body = nutrition_api_json_body();
$barangayId = trim((string) ($_GET['barangay_id'] ?? $body['barangay_id'] ?? $auth['active_barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $barangayId !== '' ? $barangayId : null);
$puroks = nutrition_api_purok_options($con, $auth['user_id'], $ctx['barangay_id']);

nutrition_api_send([
    'ok' => true,
    'barangay_id' => $ctx['barangay_id'],
    'barangay_name' => $ctx['barangay_name'],
    'puroks' => $puroks,
]);
