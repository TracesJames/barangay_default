<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_accounts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('errorMethod');
}

$userId = trim((string) ($_POST['user_id'] ?? ''));
$password = trim((string) ($_POST['password'] ?? ''));

if ($userId === '' || $password === '') {
    http_response_code(422);
    exit('errorMissing');
}

$result = staff_account_reset_password($con, $userId, $password);
if (!$result['ok']) {
    http_response_code(422);
    exit($result['error'] ?? 'errorReset');
}

exit('success');
