<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_household_survey_commit.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (string) ($_SESSION['user_id'] ?? '');
$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

$result = nutrition_commit_household_survey(
    $con,
    $userId,
    $barangayId,
    (string) ($barangay ?? ''),
    $_POST
);

if (empty($result['ok'])) {
    http_response_code((int) ($result['status'] ?? 400));
    echo json_encode(['error' => (string) ($result['error'] ?? 'Could not save survey.')]);
    exit;
}

unset($result['status']);
echo json_encode($result);
