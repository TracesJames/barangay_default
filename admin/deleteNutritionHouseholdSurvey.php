<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');
nutrition_ensure_module_tables($con);

$userId = (string) ($_SESSION['user_id'] ?? '');
nutrition_ensure_super_admin_for_manage($con, $userId);

$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

$surveyId = trim((string) ($_POST['survey_id'] ?? ''));
if ($surveyId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Survey record is required.']);
    exit;
}

if (!nutrition_delete_household_survey($con, $surveyId, $barangayId)) {
    $exists = nutrition_load_household_survey_by_id($con, $surveyId, $barangayId);
    http_response_code($exists === null ? 404 : 500);
    echo json_encode([
        'error' => $exists === null
            ? 'Survey record not found for this barangay.'
            : 'Could not delete household survey.',
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Household survey deleted.',
]);
