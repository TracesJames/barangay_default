<?php
/**
 * Link an existing nutrition household survey to a barangay residence_id.
 */
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_residence_sync.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_is_super_admin($con, $user_id)
    && !barangay_user_is_city_admin($con, $user_id)
    && !barangay_user_is_nutrition_portal_admin($con, $user_id)
    && !barangay_user_is_bns_admin($con, $user_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed.']);
    exit;
}

$surveyId = trim((string) ($_POST['survey_id'] ?? ''));
$residenceId = trim((string) ($_POST['residence_id'] ?? ''));
$surveyBarangayId = trim((string) ($_POST['barangay_id'] ?? ''));

if ($surveyId === '' || $residenceId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Survey and residence are required.']);
    exit;
}

nutrition_ensure_module_tables($con);

$result = nutrition_link_survey_to_residence($con, $surveyId, $residenceId, $surveyBarangayId, $user_id, true);
if (!$result['ok']) {
    $error = (string) ($result['error'] ?? 'Update failed.');
    $code = str_contains($error, 'not found') ? 404 : 400;
    http_response_code($code);
    echo json_encode(['error' => $error]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Household survey linked to resident.',
    'survey_id' => $result['survey_id'] ?? $surveyId,
    'residence_id' => $result['residence_id'] ?? $residenceId,
]);
