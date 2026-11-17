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

// Force password change / expiry before other admin pages.
if (isset($con) && $con instanceof mysqli && !empty($_SESSION['user_id'])) {
    $authScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $passwordPages = ['nutritionAccountProfile.php', 'myProfile.php', 'saveProfile.php'];
    if (!in_array($authScript, $passwordPages, true)
        && function_exists('barangay_user_must_change_password')
        && barangay_user_must_change_password($con, (string) $_SESSION['user_id'])) {
        $_SESSION['force_password_change'] = 1;
        $target = function_exists('barangay_password_change_redirect')
            ? barangay_password_change_redirect($con, (string) $_SESSION['user_id'])
            : 'admin/myProfile.php?force_password=1';
        // Paths from includes/auth are used by admin/* pages → stay under admin/.
        if (str_starts_with($target, 'admin/')) {
            $target = substr($target, strlen('admin/'));
        }
        barangay_deny_access($target);
    }
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
