<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
require_once '../includes/staff_accounts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('errorMethod');
}

$userId = trim((string) ($_POST['user_id'] ?? $_REQUEST['user_id'] ?? ''));
if ($userId === '') {
    http_response_code(422);
    exit('errorMissing');
}

$result = staff_account_delete($con, $userId);
if (!$result['ok']) {
    http_response_code(422);
    exit($result['error'] ?? 'errorDelete');
}

exit('success');
