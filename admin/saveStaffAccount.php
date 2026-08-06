<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_accounts.php';
require_once '../includes/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('errorMethod');
}

$userId = trim((string) ($_POST['user_id'] ?? ''));
$isEdit = $userId !== '';
$actorId = (string) ($_SESSION['user_id'] ?? '');

if (barangay_user_is_nutrition_portal_admin($con, $actorId)) {
    $allowedRoles = [
        STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
        STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
    ];
    if (!$isEdit) {
        $requestedRole = trim((string) ($_POST['staff_role'] ?? ''));
        if (!in_array($requestedRole, $allowedRoles, true)) {
            http_response_code(403);
            exit('errorRole');
        }
    } else {
        $check = $con->prepare('SELECT staff_role FROM users WHERE id = ? LIMIT 1');
        if ($check) {
            $check->bind_param('s', $userId);
            $check->execute();
            $existingRole = (string) (($check->get_result()->fetch_assoc()['staff_role'] ?? ''));
            $check->close();
            if (!in_array($existingRole, $allowedRoles, true)) {
                http_response_code(403);
                exit('errorRole');
            }
        }
    }
}

if ($isEdit) {
    $result = staff_account_update($con, $userId, [
        'first_name' => $_POST['first_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'username' => $_POST['username'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
        'password' => $_POST['password'] ?? '',
        'password_changed' => ($_POST['password_changed'] ?? '') === '1' ? '1' : '',
    ]);
} else {
    $result = staff_account_create($con, [
        'staff_role' => $_POST['staff_role'] ?? '',
        'barangay_id' => $_POST['barangay_id'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
    ]);
}

if (!$result['ok']) {
    http_response_code(422);
    exit($result['error'] ?? 'errorSave');
}

exit('success');
