<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';

barangay_require_post();

try {
    $residenceId = trim((string) ($_POST['residence_id'] ?? $_POST['status_residence'] ?? ''));
    if ($residenceId === '') {
        exit;
    }

    barangay_assert_residence_in_scope($con, $residenceId);

    $user_id = (string) ($_SESSION['user_id'] ?? '');
    $first_name_user = '';
    $last_name_user = '';
    $stmt_user = $con->prepare('SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1');
    if ($stmt_user) {
        $stmt_user->bind_param('s', $user_id);
        $stmt_user->execute();
        $row_user = $stmt_user->get_result()->fetch_assoc() ?: [];
        $first_name_user = (string) ($row_user['first_name'] ?? '');
        $last_name_user = (string) ($row_user['last_name'] ?? '');
        $stmt_user->close();
    }

    $sql_check_status = 'SELECT status FROM residence_status WHERE residence_id = ? LIMIT 1';
    $stmt_check_status = $con->prepare($sql_check_status) or die($con->error);
    $stmt_check_status->bind_param('s', $residenceId);
    $stmt_check_status->execute();
    $row_check_status = $stmt_check_status->get_result()->fetch_assoc();
    $stmt_check_status->close();
    if (!$row_check_status) {
        exit;
    }

    $fromStatus = (string) ($row_check_status['status'] ?? '');
    $data_status = $fromStatus === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

    $sql_update_status = 'UPDATE residence_status SET `status` = ? WHERE residence_id = ?';
    $stmt_update_status = $con->prepare($sql_update_status) or die($con->error);
    $stmt_update_status->bind_param('ss', $data_status, $residenceId);
    $stmt_update_status->execute();
    $stmt_update_status->close();

    $date_activity = date('j-n-Y g:i A');
    $admin = 'OFFICIAL: ' . $first_name_user . ' ' . $last_name_user . ' - ' . $user_id
        . ' | UPDATED RESIDENT STATUS - ' . $residenceId . ' | FROM ' . $fromStatus . ' TO ' . $data_status;
    $status_activity_log = 'update';
    $sql_activity_log = 'INSERT INTO activity_log (`message`,`date`,`status`) VALUES (?,?,?)';
    $stmt_activity_log = $con->prepare($sql_activity_log) or die($con->error);
    $stmt_activity_log->bind_param('sss', $admin, $date_activity, $status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();
} catch (Exception $e) {
    http_response_code(400);
    echo 'Unable to update status.';
}
