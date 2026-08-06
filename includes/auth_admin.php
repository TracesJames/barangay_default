<?php

require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    barangay_start_session();
}

if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    barangay_deny_access('../login.php');
}

require_once __DIR__ . '/barangay_context.php';
require_once __DIR__ . '/session_guard.php';
if (isset($con) && $con instanceof mysqli) {
    barangay_session_guard_enforce($con, '../login.php');
}
barangay_enforce_admin_scope($con);
barangay_assert_request_scope($con);

if (isset($con) && $con instanceof mysqli) {
    require_once __DIR__ . '/admin_barangay_vars.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !barangay_is_datatables_endpoint()) {
    require_once __DIR__ . '/csrf.php';
    csrf_verify();
}

if (barangay_should_release_session_lock()) {
    barangay_release_session_lock();
}
