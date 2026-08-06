<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');
nutrition_ensure_module_tables($con);

$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

$settings = nutrition_load_settings($con, $barangayId, (string) ($barangay ?? ''));
if (!nutrition_kobo_is_configured($settings)) {
    http_response_code(400);
    echo json_encode(['error' => 'KoBoToolbox is not configured. Set it up under Nutrition Settings first.']);
    exit;
}

$result = nutrition_kobo_sync_submissions($con, $barangayId, $settings);
if (!$result['ok']) {
    http_response_code(500);
    echo json_encode(['error' => $result['error'] ?? 'KoBoToolbox sync failed.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'KoBoToolbox data synced successfully.',
    'synced' => (int) ($result['synced'] ?? 0),
    'total' => (int) ($result['total'] ?? 0),
]);
