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

$surveyPeriod = trim((string) ($_POST['survey_period'] ?? ''));
$surveyDate = trim((string) ($_POST['survey_date'] ?? ''));
if ($surveyPeriod === '' || $surveyDate === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Survey period and date are required.']);
    exit;
}

$surveyId = (string) hexdec(uniqid());
$totalHouseholds = max(0, (int) ($_POST['total_households'] ?? 0));
$householdsSurveyed = max(0, (int) ($_POST['households_surveyed'] ?? 0));
$childrenScreened = max(0, (int) ($_POST['children_screened'] ?? 0));
$malnourishedCases = max(0, (int) ($_POST['malnourished_cases'] ?? 0));
$atRiskCases = max(0, (int) ($_POST['at_risk_cases'] ?? 0));
$programsConducted = trim((string) ($_POST['programs_conducted'] ?? ''));
$challenges = trim((string) ($_POST['challenges'] ?? ''));
$recommendations = trim((string) ($_POST['recommendations'] ?? ''));
$surveyedBy = (string) ($_SESSION['user_id'] ?? '');

$stmt = $con->prepare(
    'INSERT INTO nutrition_barangay_survey
     (survey_id, barangay_id, survey_period, survey_date, total_households, households_surveyed,
      children_screened, malnourished_cases, at_risk_cases, programs_conducted, challenges, recommendations, surveyed_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param(
    'ssssiiiiissss',
    $surveyId,
    $barangayId,
    $surveyPeriod,
    $surveyDate,
    $totalHouseholds,
    $householdsSurveyed,
    $childrenScreened,
    $malnourishedCases,
    $atRiskCases,
    $programsConducted,
    $challenges,
    $recommendations,
    $surveyedBy
);
$stmt->execute();

echo json_encode(['ok' => true, 'message' => 'Barangay nutrition survey saved.']);
