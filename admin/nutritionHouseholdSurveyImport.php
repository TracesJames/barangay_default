<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/spreadsheet_import.php';

header('Content-Type: application/json; charset=utf-8');

$respond = static function (int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(405, ['error' => 'Upload the filled Excel form using POST.']);
}

$userId = (string) ($_SESSION['user_id'] ?? '');
$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    $respond(400, ['error' => 'No active barangay selected.']);
}
if (!nutrition_user_can_add_household_surveys($con, $userId)) {
    $respond(403, ['error' => 'You are not allowed to add household surveys.']);
}

$file = $_FILES['survey_file'] ?? [];
$check = barangay_validate_spreadsheet_upload(is_array($file) ? $file : []);
if (!$check['ok']) {
    $respond(400, ['error' => $check['error']]);
}
if ($check['ext'] !== 'xlsx') {
    $respond(400, ['error' => 'Upload the Excel Form as .xlsx (the file downloaded with the "Excel Form" button).']);
}

$result = nutrition_household_survey_parse_form_xlsx((string) $file['tmp_name']);
if (!$result['ok']) {
    $respond(422, ['error' => $result['error']]);
}

$payload = $result['payload'];
$warnings = $result['warnings'] ?? [];

$purokLabel = nutrition_purok_label_from_number((string) $payload['purok_number']);
if ($purokLabel === '' && $payload['purok_number'] !== '') {
    $purokLabel = (string) $payload['purok_number'];
}
if ($purokLabel === '') {
    $warnings[] = 'Purok is blank in the file. Enter the purok to generate the Household No.';
} else {
    if (!staff_user_can_access_purok($con, $userId, $purokLabel)) {
        $respond(403, ['error' => 'The uploaded form is for ' . $purokLabel . ', which is not assigned to you.']);
    }
    $preview = nutrition_household_reference_preview($con, $barangayId, $purokLabel, (string) ($barangay ?? ''));
    $payload['house_hold_id'] = $preview['household_id'];
}
if ($payload['survey_date'] === '') {
    $payload['survey_date'] = date('m/d/Y');
    $warnings[] = 'Survey date was blank or unreadable; set to today.';
}

$respond(200, [
    'ok' => true,
    'payload' => $payload,
    'warnings' => $warnings,
    'members' => count($payload['family_members']),
]);
