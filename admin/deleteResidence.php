<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
barangay_require_mutate_barangay_records($con);

if (!barangay_user_can_edit_or_delete_person($con, (string) ($_SESSION['user_id'] ?? ''))) {
    http_response_code(403);
    exit('Only Super Super Admin and Super Admin can delete a person.');
}

try {
    $residence_id = trim((string) ($_POST['residence_id'] ?? $_REQUEST['residence_id'] ?? ''));
    if ($residence_id === '') {
        http_response_code(422);
        exit('Missing residence id');
    }

    barangay_assert_residence_in_scope($con, $residence_id);

    $archive_status = 'YES';
    $residence_status = 'INACTIVE';
    $date_archive = date('m/d/Y h:i A');

    $stmt_check_resident = $con->prepare(
        'SELECT ri.first_name, ri.last_name
         FROM residence_information ri
         INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
         WHERE ri.residence_id = ?'
    );
    if (!$stmt_check_resident) {
        throw new RuntimeException('Database error');
    }
    $stmt_check_resident->bind_param('s', $residence_id);
    $stmt_check_resident->execute();
    $row_resident_check = $stmt_check_resident->get_result()->fetch_assoc();
    $stmt_check_resident->close();
    if (!$row_resident_check) {
        http_response_code(404);
        exit('Resident not found');
    }

    $first_name = (string) ($row_resident_check['first_name'] ?? '');
    $last_name = (string) ($row_resident_check['last_name'] ?? '');

    $stmt_archive = $con->prepare(
        'UPDATE residence_status SET `archive` = ?, `date_archive` = ?, `status` = ? WHERE `residence_id` = ?'
    );
    if ($stmt_archive) {
        $stmt_archive->bind_param('ssss', $archive_status, $date_archive, $residence_status, $residence_id);
        $stmt_archive->execute();
        $stmt_archive->close();
    }

    $date_activity = date('j-n-Y g:i A');
    $admin = strtoupper('ADMIN') . ': DELETED RESIDENT - ' . $residence_id . ' | - ' . $first_name . ' ' . $last_name;
    $status_activity_log = 'delete';
    $sql_activity_log = 'INSERT INTO activity_log (`message`,`date`,`status`) VALUES (?,?,?)';
    $stmt_activity_log = $con->prepare($sql_activity_log);
    if ($stmt_activity_log) {
        $stmt_activity_log->bind_param('sss', $admin, $date_activity, $status_activity_log);
        $stmt_activity_log->execute();
        $stmt_activity_log->close();
    }
} catch (Throwable $e) {
    http_response_code(400);
    exit('Unable to delete resident.');
}
