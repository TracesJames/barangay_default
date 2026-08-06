<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();

try {
    $residenceId = trim((string) ($_POST['residence_id'] ?? $_POST['status_residence'] ?? ''));
    if ($residenceId === '') {
        exit;
    }

    barangay_assert_residence_in_scope($con, $residenceId);

    $sql_check_status = 'SELECT status FROM residence_status WHERE residence_id = ? LIMIT 1';
    $stmt_check_status = $con->prepare($sql_check_status) or die($con->error);
    $stmt_check_status->bind_param('s', $residenceId);
    $stmt_check_status->execute();
    $row_check_status = $stmt_check_status->get_result()->fetch_assoc();
    $stmt_check_status->close();
    if (!$row_check_status) {
        exit;
    }

    $data_status = ($row_check_status['status'] ?? '') === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

    $sql_update_status = 'UPDATE residence_status SET `status` = ? WHERE residence_id = ?';
    $stmt_update_status = $con->prepare($sql_update_status) or die($con->error);
    $stmt_update_status->bind_param('ss', $data_status, $residenceId);
    $stmt_update_status->execute();
    $stmt_update_status->close();
} catch (Exception $e) {
    http_response_code(400);
    echo 'Unable to update status.';
}
