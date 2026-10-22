<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

$auth = nutrition_api_authenticate($con);
$barangays = nutrition_api_accessible_barangays($con, $auth['user_id']);

nutrition_api_send([
    'ok' => true,
    'barangays' => $barangays,
    'active_barangay_id' => $auth['active_barangay_id'],
]);
