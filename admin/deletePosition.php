<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
barangay_require_mutate_barangay_records($con);

try {
    $position_id = trim((string) ($_POST['position_id'] ?? $_REQUEST['position_id'] ?? ''));
    if ($position_id === '') {
        http_response_code(422);
        exit('Missing position id');
    }

    $stmt_check_position = $con->prepare('SELECT position FROM official_status WHERE position = ?');
    if ($stmt_check_position) {
        $stmt_check_position->bind_param('s', $position_id);
        $stmt_check_position->execute();
        $count_check_position = $stmt_check_position->get_result()->num_rows;
        $stmt_check_position->close();
        if ($count_check_position > 0) {
            exit('error');
        }
    }

    $stmt_check_position_end = $con->prepare('SELECT position FROM official_end_status WHERE position = ?');
    if ($stmt_check_position_end) {
        $stmt_check_position_end->bind_param('s', $position_id);
        $stmt_check_position_end->execute();
        $count_check_position_end = $stmt_check_position_end->get_result()->num_rows;
        $stmt_check_position_end->close();
        if ($count_check_position_end > 0) {
            exit('error');
        }
    }

    $stmt_position = $con->prepare('SELECT position FROM position WHERE position_id = ?');
    if (!$stmt_position) {
        throw new RuntimeException('Database error');
    }
    $stmt_position->bind_param('s', $position_id);
    $stmt_position->execute();
    $row_position = $stmt_position->get_result()->fetch_assoc();
    $stmt_position->close();
    if (!$row_position) {
        http_response_code(404);
        exit('Position not found');
    }
    $old_position = (string) ($row_position['position'] ?? '');

    $date_activity = date('j-n-Y g:i A');
    $admin = strtoupper('ADMIN') . ': DELETED POSITION - ' . $position_id . ' | ' . $old_position;
    $status_activity_log = 'delete';
    $sql_activity_log = 'INSERT INTO activity_log (`message`,`date`,`status`) VALUES (?,?,?)';
    $stmt_activity_log = $con->prepare($sql_activity_log);
    if ($stmt_activity_log) {
        $stmt_activity_log->bind_param('sss', $admin, $date_activity, $status_activity_log);
        $stmt_activity_log->execute();
        $stmt_activity_log->close();
    }

    $stmt_delete_position = $con->prepare('DELETE FROM position WHERE position_id = ?');
    if ($stmt_delete_position) {
        $stmt_delete_position->bind_param('s', $position_id);
        $stmt_delete_position->execute();
        $stmt_delete_position->close();
    }
} catch (Throwable $e) {
    http_response_code(400);
    exit('Unable to delete position.');
}
