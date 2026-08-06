<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_family.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$residenceId = trim((string) ($_POST['residence_id'] ?? ''));
$householdHead = strtoupper(trim((string) ($_POST['household_head'] ?? 'YES')));

if ($residenceId === '') {
    exit('errorResident');
}

if (!in_array($householdHead, ['YES', 'NO'], true)) {
    exit('errorStatus');
}

barangay_assert_residence_in_scope($con, $residenceId);

if (!residence_is_registered($con, $residenceId)) {
    exit('errorNotRegistered');
}

if (!residence_set_household_head($con, $residenceId, $householdHead)) {
    exit('errorUpdate');
}

exit('success');
