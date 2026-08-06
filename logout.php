<?php

include_once 'connection.php';
require_once 'includes/helpers.php';
require_once 'includes/session_guard.php';
barangay_start_session();

$userId = (string) ($_SESSION['user_id'] ?? '');
$userType = (string) ($_SESSION['user_type'] ?? '');

if ($userId !== '') {
    if ($userType === 'admin') {
        $user_type_log = 'ADMIN';
    } elseif ($userType === 'secretary') {
        $user_type_log = 'OFFICIAL';
    } else {
        $user_type_log = 'RESIDENT';
    }

    $sql_user = "SELECT first_name, last_name FROM users WHERE id = ?";
    $stmt_user = $con->prepare($sql_user) or die($con->error);
    $stmt_user->bind_param('s', $userId);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc() ?: [];
    $first_name = $row_user['first_name'] ?? '';
    $last_name = $row_user['last_name'] ?? '';
    $status_activity_log = 'logout';

    $date_activity = date('j-n-Y g:i A');
    $message = $user_type_log . ': ' . $first_name . ' ' . $last_name . ' | LOGOUT';
    $sql_system_logs = 'INSERT INTO activity_log (`message`, `date`,`status`) VALUES (?,?,?)';
    $query_system_logs = $con->prepare($sql_system_logs) or die($con->error);
    $query_system_logs->bind_param('sss', $message, $date_activity, $status_activity_log);
    $query_system_logs->execute();
    $query_system_logs->close();

    barangay_session_guard_clear($con, $userId);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', !empty($params['secure']), !empty($params['httponly']));
}
session_destroy();

header('Location: login.php');
exit;
