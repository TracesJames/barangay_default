<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');

$barangayId = (string) ($barangay_id ?? '');
$residenceId = trim((string) ($_GET['residence_id'] ?? $_POST['residence_id'] ?? ''));

if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

if ($residenceId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Residence ID is required.']);
    exit;
}

$prefill = nutrition_load_resident_survey_prefill($con, $residenceId, $barangayId);
if ($prefill === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Resident not found in this barangay.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'resident' => $prefill,
]);
