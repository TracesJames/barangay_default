<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
barangay_require_mutate_barangay_records($con);

try {
    $blotter_id = trim((string) ($_POST['id'] ?? $_POST['blotter_id'] ?? ''));
    if ($blotter_id === '') {
        http_response_code(422);
        exit('Missing blotter id');
    }

    barangay_assert_blotter_in_scope($con, $blotter_id);

    $sql_blotter = 'SELECT blotter_id, date_incident, date_reported, location_incident FROM blotter_record WHERE blotter_id = ? LIMIT 1';
    $stmt_blotter = $con->prepare($sql_blotter);
    if (!$stmt_blotter) {
        throw new RuntimeException('Database error');
    }
    $stmt_blotter->bind_param('s', $blotter_id);
    $stmt_blotter->execute();
    $row_blotter = $stmt_blotter->get_result()->fetch_assoc();
    $stmt_blotter->close();
    if (!$row_blotter) {
        http_response_code(404);
        exit('Blotter record not found');
    }

    $old_date_incident = $row_blotter['date_incident'];
    $old_date_reported = $row_blotter['date_reported'];
    $old_location_incident = $row_blotter['location_incident'];

    $date_activity = date('j-n-Y g:i A');
    $admin = 'ADMIN: DELETED BLOTTER RECORD - ' . $blotter_id . ' | ' . $old_date_incident . ' ' . $old_date_reported . ' ' . $old_location_incident;
    $status_activity_log = 'delete';
    $sql_activity_log = 'INSERT INTO activity_log (`message`,`date`,`status`) VALUES (?,?,?)';
    $stmt_activity_log = $con->prepare($sql_activity_log);
    if ($stmt_activity_log) {
        $stmt_activity_log->bind_param('sss', $admin, $date_activity, $status_activity_log);
        $stmt_activity_log->execute();
        $stmt_activity_log->close();
    }

    $stmt = $con->prepare('DELETE FROM blotter_record WHERE blotter_id = ?');
    if ($stmt) {
        $stmt->bind_param('s', $blotter_id);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $con->prepare('DELETE FROM blotter_complainant WHERE blotter_main = ?');
    if ($stmt) {
        $stmt->bind_param('s', $blotter_id);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $con->prepare('DELETE FROM blotter_status WHERE blotter_main = ?');
    if ($stmt) {
        $stmt->bind_param('s', $blotter_id);
        $stmt->execute();
        $stmt->close();
    }
} catch (Throwable $e) {
    http_response_code(400);
    exit('Unable to delete blotter record.');
}
