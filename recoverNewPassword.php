<?php

include_once 'connection.php';
require_once 'includes/helpers.php';
require_once 'includes/csrf.php';
require_once 'includes/password_reset.php';

barangay_require_post();
csrf_verify();

try {
    $check_username = trim((string) ($_POST['check_username'] ?? ''));
    $reset_token = trim((string) ($_POST['reset_token'] ?? ''));
    $new_password = (string) ($_POST['new_password'] ?? '');
    $new_confirm_password = (string) ($_POST['new_confirm_password'] ?? '');

    if ($new_password === '' || $new_password !== $new_confirm_password) {
        exit('error1');
    }

    $result = barangay_password_reset_consume_token($con, $check_username, $reset_token, $new_password);
    if (!$result['ok']) {
        exit(($result['message'] ?? '') === 'token' ? 'token' : 'error');
    }

    exit('ok');
} catch (Exception $e) {
    http_response_code(400);
    exit('error');
}
