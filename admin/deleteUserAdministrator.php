<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
barangay_require_staff_account_management($con);

try {
    $user_id = trim((string) ($_POST['user_id'] ?? $_REQUEST['user_id'] ?? ''));
    if ($user_id === '') {
        http_response_code(422);
        exit('Missing user id');
    }

    if ($user_id === (string) ($_SESSION['user_id'] ?? '')) {
        http_response_code(422);
        exit('You cannot delete your own account.');
    }

    $stmt_check_admin = $con->prepare('SELECT first_name, middle_name, last_name FROM users WHERE id = ?');
    if (!$stmt_check_admin) {
        throw new RuntimeException('Database error');
    }
    $stmt_check_admin->bind_param('s', $user_id);
    $stmt_check_admin->execute();
    $row_check_admin = $stmt_check_admin->get_result()->fetch_assoc();
    $stmt_check_admin->close();
    if (!$row_check_admin) {
        http_response_code(404);
        exit('User not found');
    }

    $old_first_name = (string) ($row_check_admin['first_name'] ?? '');
    $old_last_name = (string) ($row_check_admin['last_name'] ?? '');

    $date_activity = date('j-n-Y g:i A');
    $admin = strtoupper('ADMIN') . ': DELETED ADMINISTRATOR - ' . $user_id . ' | ' . $old_first_name . ' ' . $old_last_name;
    $status_activity_log = 'delete';
    $sql_activity_log = 'INSERT INTO activity_log (`message`,`date`,`status`) VALUES (?,?,?)';
    $stmt_activity_log = $con->prepare($sql_activity_log);
    if ($stmt_activity_log) {
        $stmt_activity_log->bind_param('sss', $admin, $date_activity, $status_activity_log);
        $stmt_activity_log->execute();
        $stmt_activity_log->close();
    }

    $stmt_delete_user = $con->prepare('DELETE FROM users WHERE id = ?');
    if ($stmt_delete_user) {
        $stmt_delete_user->bind_param('s', $user_id);
        $stmt_delete_user->execute();
        $stmt_delete_user->close();
    }
} catch (Throwable $e) {
    http_response_code(400);
    exit('Unable to delete administrator.');
}
