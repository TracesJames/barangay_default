<?php
/**
 * Link an existing nutrition household survey to a barangay residence_id.
 */
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/audit_log.php';

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

$stmt = $con->prepare(
    'SELECT survey_id, barangay_id, residence_id FROM nutrition_household_survey WHERE survey_id = ? LIMIT 1'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load survey.']);
    exit;
}
$stmt->bind_param('s', $surveyId);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$survey) {
    http_response_code(404);
    echo json_encode(['error' => 'Survey not found.']);
    exit;
}

$surveyBarangayId = $surveyBarangayId !== '' ? $surveyBarangayId : (string) ($survey['barangay_id'] ?? '');

$resCheck = $con->prepare(
    'SELECT ri.residence_id
     FROM residence_information ri
     INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
     WHERE ri.residence_id = ? AND rs.archive = \'NO\' LIMIT 1'
);
if (!$resCheck) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not validate resident.']);
    exit;
}
$resCheck->bind_param('s', $residenceId);
$resCheck->execute();
$residentOk = (bool) $resCheck->get_result()->fetch_assoc();
$resCheck->close();

if (!$residentOk) {
    http_response_code(400);
    echo json_encode(['error' => 'Resident not found or archived.']);
    exit;
}

if (barangay_column_exists($con, 'residence_status', 'barangay_id') && $surveyBarangayId !== '') {
    $brgyCheck = $con->prepare(
        'SELECT residence_id FROM residence_status WHERE residence_id = ? AND barangay_id = ? LIMIT 1'
    );
    if ($brgyCheck) {
        $brgyCheck->bind_param('ss', $residenceId, $surveyBarangayId);
        $brgyCheck->execute();
        $sameBrgy = (bool) $brgyCheck->get_result()->fetch_assoc();
        $brgyCheck->close();
        if (!$sameBrgy) {
            http_response_code(400);
            echo json_encode(['error' => 'Resident must belong to the same barangay as the survey.']);
            exit;
        }
    }
}

$upd = $con->prepare(
    'UPDATE nutrition_household_survey SET residence_id = ? WHERE survey_id = ? LIMIT 1'
);
if (!$upd) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not link survey.']);
    exit;
}
$upd->bind_param('ss', $residenceId, $surveyId);
$upd->execute();
$ok = $upd->affected_rows >= 0;
$upd->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed.']);
    exit;
}

barangay_audit_log($con, 'Linked nutrition household survey to residence ' . $residenceId, 'nutrition_link', [
    'user_id' => $user_id,
    'barangay_id' => $surveyBarangayId,
    'entity_type' => 'nutrition_household_survey',
    'entity_id' => $surveyId,
]);

echo json_encode([
    'ok' => true,
    'message' => 'Household survey linked to resident.',
    'survey_id' => $surveyId,
    'residence_id' => $residenceId,
]);
