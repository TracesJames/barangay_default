<?php

include_once '../connection.php';
include_once '../includes/auth_certificate_staff.php';
require_once '../includes/residence_family.php';

try {
    $residenceId = trim((string) ($_POST['residence_id'] ?? ''));
    $purpose = trim((string) ($_POST['purpose'] ?? ''));

    if ($residenceId === '') {
        exit('errorResident');
    }

    if ($purpose === '') {
        exit('errorPurpose');
    }

    barangay_assert_residence_in_scope($con, $residenceId);
    residence_require_certificate_access_or_exit($con, $residenceId);

    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $uniqid = uniqid(mt_rand() . $date->format('mdYHisv') . rand());
    $dateIssued = '';
    $dateExpire = '';
    $status = 'PENDING';
    $dateRequest = $date->format('m/d/Y');

    $sql = 'INSERT INTO `certificate_request`(`id`, `residence_id`, `purpose`, `date_request`, `date_issued`, `date_expired`, `status`) VALUES (?,?,?,?,?,?,?)';
    $stmt = $con->prepare($sql) or die($con->error);
    $stmt->bind_param('sssssss', $uniqid, $residenceId, $purpose, $dateRequest, $dateIssued, $dateExpire, $status);
    $stmt->execute();
    $stmt->close();

    $sqlResident = 'SELECT first_name, last_name FROM residence_information WHERE residence_id = ? LIMIT 1';
    $stmtResident = $con->prepare($sqlResident) or die($con->error);
    $stmtResident->bind_param('s', $residenceId);
    $stmtResident->execute();
    $rowResident = $stmtResident->get_result()->fetch_assoc();
    $stmtResident->close();

    $staffId = (string) $_SESSION['user_id'];
    $staffType = strtoupper((string) $_SESSION['user_type']);
    $residentName = trim(($rowResident['first_name'] ?? '') . ' ' . ($rowResident['last_name'] ?? ''));
    $statusActivityLog = 'create';
    $dateActivity = date('j-n-Y g:i A');
    $message = $staffType . ' - ' . $staffId . ': CREATED CERTIFICATE REQUEST FOR ' . $residenceId . ' ' . $residentName . ' | PURPOSE - ' . strtoupper($purpose);

    $sqlLog = 'INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?,?,?)';
    $stmtLog = $con->prepare($sqlLog) or die($con->error);
    $stmtLog->bind_param('sss', $message, $dateActivity, $statusActivityLog);
    $stmtLog->execute();
    $stmtLog->close();

    exit('success');
} catch (Exception $e) {
    echo $e->getMessage();
}
