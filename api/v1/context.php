<?php

require_once __DIR__ . '/bootstrap.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    nutrition_api_error('Method not allowed. Use POST.', 405);
}

$auth = nutrition_api_authenticate($con);
$body = nutrition_api_json_body();
$barangayId = trim((string) ($body['barangay_id'] ?? ''));
if ($barangayId === '') {
    nutrition_api_error('barangay_id is required.', 422);
}
if (!nutrition_api_user_can_access_barangay($con, $auth['user_id'], $barangayId)) {
    nutrition_api_error('You cannot access this barangay.', 403);
}
if (!nutrition_api_set_token_barangay($con, $auth['token_id'], $barangayId)) {
    nutrition_api_error('Could not update active barangay.', 500);
}

$puroks = nutrition_api_purok_options($con, $auth['user_id'], $barangayId);
$name = $barangayId;
$stmt = $con->prepare('SELECT barangay FROM barangay_information WHERE id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('s', $barangayId);
    $stmt->execute();
    $name = (string) ($stmt->get_result()->fetch_assoc()['barangay'] ?? $barangayId);
    $stmt->close();
}

nutrition_api_send([
    'ok' => true,
    'active_barangay_id' => $barangayId,
    'barangay_name' => $name,
    'puroks' => $puroks,
    'message' => 'Active barangay updated for this token.',
]);
