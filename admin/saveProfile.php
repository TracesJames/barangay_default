<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$wantJson = (
    strtolower((string) ($_POST['response'] ?? '')) === 'json'
    || str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
);

$respond = static function (string $code, string $message = '', int $http = 200) use ($wantJson): void {
    if ($wantJson) {
        http_response_code($http);
        header('Content-Type: application/json; charset=utf-8');
        $ok = ($code === 'ok');
        echo json_encode([
            'ok' => $ok,
            'code' => $code,
            'message' => $message !== '' ? $message : $code,
        ]);
        exit;
    }
    if ($code === 'ok') {
        exit;
    }
    exit($code);
};

$user_id = (string) ($_SESSION['user_id'] ?? '');
if ($user_id === '') {
    $respond('errorAuth', 'Not signed in.', 401);
}

$sql_user = 'SELECT id, username, password, image, image_path FROM users WHERE id = ? LIMIT 1';
$stmt_user = $con->prepare($sql_user);
if (!$stmt_user) {
    $respond('error', 'Could not load account.', 500);
}
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$row_user) {
    $respond('errorAuth', 'Account not found.', 404);
}

$password_user = (string) ($row_user['password'] ?? '');

try {
    $username = trim((string) ($_POST['username'] ?? ''));
    $first_name = trim((string) ($_POST['first_name'] ?? ''));
    $middle_name = trim((string) ($_POST['middle_name'] ?? ''));
    $last_name = trim((string) ($_POST['last_name'] ?? ''));
    $contact_number = trim((string) ($_POST['contact_number'] ?? ''));
    $old_password = (string) ($_POST['old_password'] ?? '');
    $new_password = (string) ($_POST['new_password'] ?? '');
    $new_confirm_password = (string) ($_POST['new_confirm_password'] ?? '');

    if ($username === '' || $first_name === '' || $last_name === '') {
        $respond('error', 'Username, first name, and last name are required.', 400);
    }

    $new_image_name = (string) ($row_user['image'] ?? '');
    $new_image_path = (string) ($row_user['image_path'] ?? '');

    if (!empty($_FILES['image']['name'])) {
        $sql_check_image_user = 'SELECT image, image_path FROM users WHERE id = ? LIMIT 1';
        $stmt_check_image_user = $con->prepare($sql_check_image_user);
        if ($stmt_check_image_user) {
            $stmt_check_image_user->bind_param('s', $user_id);
            $stmt_check_image_user->execute();
            $row_check_image_user = $stmt_check_image_user->get_result()->fetch_assoc() ?: [];
            $stmt_check_image_user->close();
            if (!empty($row_check_image_user['image_path']) && is_file((string) $row_check_image_user['image_path'])) {
                @unlink((string) $row_check_image_user['image_path']);
            }
        }

        require_once '../includes/upload_helper.php';
        $upload = barangay_store_image_upload($_FILES['image']);
        if (!$upload['ok']) {
            $respond('errorImage', 'Invalid or failed photo upload.', 400);
        }
        $new_image_name = (string) $upload['filename'];
        $new_image_path = (string) $upload['path'];
    }

    $sql_check_username = 'SELECT username FROM users WHERE username = ? AND id != ? LIMIT 1';
    $stmt_check_username = $con->prepare($sql_check_username);
    if (!$stmt_check_username) {
        $respond('error', 'Could not validate username.', 500);
    }
    $stmt_check_username->bind_param('ss', $username, $user_id);
    $stmt_check_username->execute();
    $count_check_username = $stmt_check_username->get_result()->num_rows;
    $stmt_check_username->close();

    if ($count_check_username > 0) {
        $respond('error', 'Username is already taken.', 409);
    }

    if ($old_password === '' || !barangay_verify_password($old_password, $password_user)) {
        $respond('error1', 'Current password is incorrect.', 400);
    }

    $changingPassword = ($new_password !== '' || $new_confirm_password !== '');
    if ($changingPassword) {
        if ($new_password !== $new_confirm_password) {
            $respond('error2', 'New password and confirmation do not match.', 400);
        }
        if (strlen($new_password) < 8) {
            $respond('error2', 'New password must be at least 8 characters.', 400);
        }
        $pass = barangay_hash_password($new_password);
    } else {
        $pass = $password_user;
    }

    $sql_update = 'UPDATE users SET username = ?, password = ?, first_name = ?, middle_name = ?, last_name = ?, contact_number = ?, image = ?, image_path = ? WHERE id = ?';
    $stmt_update = $con->prepare($sql_update);
    if (!$stmt_update) {
        $respond('error', 'Could not save profile.', 500);
    }
    $stmt_update->bind_param(
        'sssssssss',
        $username,
        $pass,
        $first_name,
        $middle_name,
        $last_name,
        $contact_number,
        $new_image_name,
        $new_image_path,
        $user_id
    );
    if (!$stmt_update->execute()) {
        $stmt_update->close();
        $respond('error', 'Could not save profile.', 500);
    }
    $stmt_update->close();

    $respond('ok', 'Profile updated.');
} catch (Throwable $e) {
    $respond('error', 'Could not update profile.', 500);
}
