<?php

require_once __DIR__ . '/bootstrap.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    nutrition_api_error('Method not allowed. Use POST.', 405);
}

$auth = nutrition_api_authenticate($con, true);
$userId = (string) ($auth['user_id'] ?? '');
$tokenId = (string) ($auth['token_id'] ?? '');

$body = nutrition_api_json_body();
$currentPassword = (string) ($body['current_password'] ?? $body['old_password'] ?? '');
$newPassword = (string) ($body['new_password'] ?? '');
$confirmPassword = (string) ($body['confirm_password'] ?? $body['new_confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    nutrition_api_error('Current password, new password, and confirmation are required.', 422);
}

if ($newPassword !== $confirmPassword) {
    nutrition_api_error('New password and confirmation do not match.', 422, ['code' => 'mismatch']);
}

$stmt = $con->prepare('SELECT `id`, `username`, `password` FROM `users` WHERE `id` = ? LIMIT 1');
if (!$stmt) {
    nutrition_api_error('Could not load account.', 500);
}
$stmt->bind_param('s', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    nutrition_api_error('User account not found.', 401);
}

$policyError = barangay_validate_password_policy($newPassword, (string) ($row['username'] ?? ''));
if ($policyError !== null) {
    nutrition_api_error($policyError, 422, ['code' => 'weak']);
}

if (hash_equals($currentPassword, $newPassword)) {
    nutrition_api_error('New password must be different from the current password.', 422, ['code' => 'unchanged']);
}

$stored = (string) ($row['password'] ?? '');
if (!barangay_verify_password($currentPassword, $stored)) {
    nutrition_api_error('Current password is incorrect.', 400, ['code' => 'bad_current']);
}

$hash = barangay_hash_password($newPassword);
$upd = $con->prepare('UPDATE `users` SET `password` = ? WHERE `id` = ? LIMIT 1');
if (!$upd) {
    nutrition_api_error('Could not update password.', 500);
}
$upd->bind_param('ss', $hash, $userId);
if (!$upd->execute()) {
    $upd->close();
    nutrition_api_error('Could not update password.', 500);
}
$upd->close();
barangay_mark_password_changed($con, $userId, false);

// Keep this device signed in; revoke other mobile tokens for the account.
nutrition_api_ensure_tokens_table($con);
$now = date('Y-m-d H:i:s');
$revoke = $con->prepare(
    'UPDATE nutrition_api_tokens
     SET revoked_at = ?
     WHERE user_id = ?
       AND token_id <> ?
       AND revoked_at IS NULL'
);
if ($revoke) {
    $revoke->bind_param('sss', $now, $userId, $tokenId);
    $revoke->execute();
    $revoke->close();
}

nutrition_api_send([
    'ok' => true,
    'message' => 'Password updated. Other mobile sessions were signed out.',
]);
