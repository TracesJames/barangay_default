<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
nutrition_ensure_table($con);

header('Content-Type: application/json; charset=utf-8');

if (!nutrition_table_exists($con)) {
    http_response_code(500);
    echo json_encode(['error' => 'Nutrition module is not installed. Run the migration script first.']);
    exit;
}

$residenceId = trim((string) ($_POST['residence_id'] ?? ''));
$assessmentDate = trim((string) ($_POST['assessment_date'] ?? ''));
$weightKg = (float) ($_POST['weight_kg'] ?? 0);
$heightCm = (float) ($_POST['height_cm'] ?? 0);
$muacCm = trim((string) ($_POST['muac_cm'] ?? ''));
$nutritionalStatus = trim((string) ($_POST['nutritional_status'] ?? ''));
$remarks = trim((string) ($_POST['remarks'] ?? ''));
$barangayId = (string) ($barangay_id ?? '');

if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

if ($residenceId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please select a resident.']);
    exit;
}

if ($assessmentDate === '' || strtotime($assessmentDate) === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a valid assessment date.']);
    exit;
}

if ($weightKg <= 0 || $heightCm <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Weight and height must be greater than zero.']);
    exit;
}

$statusOptions = nutrition_status_options();
if (!isset($statusOptions[$nutritionalStatus])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid nutritional status.']);
    exit;
}

$resident = nutrition_load_resident($con, $residenceId, $barangayId);
if ($resident === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Resident not found in this barangay.']);
    exit;
}

$ageYears = nutrition_resident_age_years($resident);
if ($ageYears > nutrition_child_max_age_years()) {
    http_response_code(400);
    echo json_encode(['error' => 'Nutrition profiling is intended for children aged 0–' . nutrition_child_max_age_years() . '.']);
    exit;
}

$bmi = nutrition_calculate_bmi($weightKg, $heightCm);
$assessmentId = (string) hexdec(uniqid());
$assessedBy = (string) ($_SESSION['user_id'] ?? '');
$muacValue = $muacCm !== '' ? (float) $muacCm : 0.0;

$stmt = $con->prepare(
    'INSERT INTO nutrition_assessment
     (assessment_id, residence_id, barangay_id, assessment_date, weight_kg, height_cm, bmi, muac_cm, nutritional_status, remarks, assessed_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save assessment.']);
    exit;
}

$stmt->bind_param(
    'ssssdddssss',
    $assessmentId,
    $residenceId,
    $barangayId,
    $assessmentDate,
    $weightKg,
    $heightCm,
    $bmi,
    $muacValue,
    $nutritionalStatus,
    $remarks,
    $assessedBy
);
$stmt->execute();

if ($stmt->affected_rows <= 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save assessment.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Nutrition assessment saved for ' . trim($resident['first_name'] . ' ' . $resident['last_name']) . '.',
    'redirect' => 'nutritionProfiles.php',
]);
