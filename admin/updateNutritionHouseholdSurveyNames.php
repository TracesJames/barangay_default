<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');
nutrition_ensure_module_tables($con);

$userId = (string) ($_SESSION['user_id'] ?? '');
nutrition_ensure_can_edit_household_survey_names($con, $userId);

$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));

if ($action === 'household_head') {
    $surveyId = trim((string) ($_POST['survey_id'] ?? ''));
    $headLastName = trim((string) ($_POST['head_last_name'] ?? ''));
    $headFirstName = trim((string) ($_POST['head_first_name'] ?? ''));
    $headMiddleName = trim((string) ($_POST['head_middle_name'] ?? ''));
    $headSuffix = trim((string) ($_POST['head_suffix'] ?? ''));

    if ($surveyId === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Survey record is required.']);
        exit;
    }

    if ($headLastName === '' || $headFirstName === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Last name and first name are required.']);
        exit;
    }

    $nameValidationError = nutrition_validate_household_survey_names(
        $con,
        $headLastName,
        $headFirstName,
        $headMiddleName,
        $headSuffix,
        [],
        $surveyId
    );
    if ($nameValidationError !== null) {
        http_response_code(409);
        echo json_encode(['error' => $nameValidationError]);
        exit;
    }

    if (!nutrition_update_household_head_names(
        $con,
        $surveyId,
        $barangayId,
        $headLastName,
        $headFirstName,
        $headMiddleName,
        $headSuffix
    )) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not update household head name.']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Household head name updated.',
        'household_head' => nutrition_format_household_head_name(
            $headLastName,
            $headFirstName,
            $headMiddleName,
            $headSuffix
        ),
    ]);
    exit;
}

if ($action === 'family_member') {
    $memberId = trim((string) ($_POST['member_id'] ?? ''));
    $memberName = trim((string) ($_POST['member_name'] ?? ''));

    if ($memberId === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Family member record is required.']);
        exit;
    }

    if ($memberName === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Member name is required.']);
        exit;
    }

    $duplicateMember = nutrition_find_duplicate_person_name(
        $con,
        $memberName,
        '',
        $memberId
    );
    if ($duplicateMember !== null) {
        http_response_code(409);
        echo json_encode([
            'error' => 'The name "' . $memberName . '" is already recorded in the system.'
                . nutrition_duplicate_name_error_suffix($duplicateMember),
        ]);
        exit;
    }

    if (!nutrition_update_family_member_name($con, $memberId, $barangayId, $memberName)) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not update family member name.']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Family member name updated.',
        'member_name' => $memberName,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid update action.']);
