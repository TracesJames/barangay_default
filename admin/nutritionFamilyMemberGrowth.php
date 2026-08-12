<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

// Discard accidental BOM/whitespace from includes so JSON stays parseable.
ob_start();
require_once '../includes/nutrition_context.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$gender = trim((string) ($_GET['gender'] ?? $_POST['gender'] ?? ''));
$birthDate = nutrition_normalize_date_to_ymd(trim((string) ($_GET['birth_date'] ?? $_POST['birth_date'] ?? '')));
$weightKg = max(0, (float) ($_GET['weight_kg'] ?? $_POST['weight_kg'] ?? 0));
$heightCm = max(0, (float) ($_GET['height_cm'] ?? $_POST['height_cm'] ?? 0));
$referenceDate = nutrition_normalize_date_to_ymd(trim((string) ($_GET['date_measured'] ?? $_POST['date_measured'] ?? '')));
if ($referenceDate === null) {
    $referenceDate = nutrition_normalize_date_to_ymd(trim((string) ($_GET['survey_date'] ?? $_POST['survey_date'] ?? date('Y-m-d'))));
}
if ($referenceDate === null) {
    $referenceDate = date('Y-m-d');
}

if ($birthDate === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Birthday is required for growth assessment (use MM/DD/YYYY).']);
    exit;
}

$ageMonths = nutrition_age_in_months($birthDate, $referenceDate);
if ($ageMonths === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid birthday or survey date. Use MM/DD/YYYY.']);
    exit;
}

$years = intdiv($ageMonths, 12);
$months = $ageMonths % 12;
$ageLabel = $years . 'y ' . $months . 'm (' . $ageMonths . ' months)';

$growthGender = in_array($gender, ['Male', 'Female'], true) ? $gender : '';
$growth = nutrition_family_member_growth_assessment(
    $growthGender,
    $birthDate,
    $weightKg,
    $heightCm,
    $referenceDate !== '' ? $referenceDate : null
);

$sexLabel = $gender === 'Female' ? 'girl' : ($gender === 'Male' ? 'boy' : '');

echo json_encode([
    'ok' => true,
    'age_months' => $ageMonths,
    'age_label' => $ageLabel,
    'is_child_0_to_5' => nutrition_is_child_0_to_5($ageMonths),
    'sex_label' => $sexLabel,
    'expected_weight_kg' => $growth['expected_weight_kg'],
    'expected_height_cm' => $growth['expected_height_cm'],
    'weight_for_age' => $growth['weight_for_age'],
    'height_for_age' => $growth['height_for_age'],
    'weight_for_height' => $growth['weight_for_height'],
]);
