<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/session_guard.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    nutrition_api_error('Method not allowed. Use POST.', 405);
}

$body = nutrition_api_json_body();
$username = trim((string) ($body['username'] ?? ''));
$password = (string) ($body['password'] ?? '');
$deviceLabel = trim((string) ($body['device_label'] ?? 'Mobile App'));
$forceLogin = !empty($body['force_login']) || !empty($body['force']);

if ($username === '' || $password === '') {
    nutrition_api_error('Username and password are required.', 422);
}

$rateLimited = barangay_login_rate_limit_check($username);
if ($rateLimited !== null) {
    nutrition_api_error('Too many failed attempts. Try again later.', 429, ['code' => 'rate_limited']);
}

$hasBarangayId = barangay_column_exists($con, 'users', 'barangay_id');
$sql = 'SELECT `id`, `username`, `password`, `user_type`, `first_name`, `middle_name`, `last_name`'
    . ($hasBarangayId ? ', `barangay_id`' : '')
    . ' FROM `users` WHERE (username = ? OR id = ?) LIMIT 1';
$stmt = $con->prepare($sql);
if (!$stmt) {
    nutrition_api_error('Login query failed.', 500);
}
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !barangay_verify_password($password, (string) ($row['password'] ?? ''))) {
    barangay_login_rate_limit_fail($username);
    nutrition_api_error('Incorrect username or password.', 401);
}

$userId = (string) ($row['id'] ?? '');
if (!nutrition_api_user_may_use_mobile($con, $userId)) {
    barangay_login_rate_limit_fail($username);
    nutrition_api_error('This account is not allowed on the Nutrition mobile app.', 403);
}

// Block double login while another web/mobile session is still active (SSA may multi-login).
$allowsMulti = barangay_session_guard_allows_multi_login($con, $userId);
if (!$allowsMulti && !$forceLogin) {
    $activeWeb = barangay_session_guard_is_active_elsewhere($con, $userId, null);
    $activeMobile = nutrition_api_user_has_active_mobile_session($con, $userId);
    if ($activeWeb || $activeMobile) {
        nutrition_api_error(
            'This account is already logged in on another device or browser. '
            . 'End the other session, or confirm to continue here (that will sign out the other session).',
            409,
            ['code' => 'already_logged_in']
        );
    }
}

barangay_login_rate_limit_success($username);

$barangays = nutrition_api_accessible_barangays($con, $userId);
$activeBarangayId = nutrition_api_default_barangay_id($con, $userId, $barangays);
$puroks = [];
if ($activeBarangayId !== '') {
    $puroks = staff_assigned_purok_labels($con, $userId, $activeBarangayId);
    // Empty assignment = all puroks in barangay for BNS "all" scope.
    if ($puroks === [] && barangay_user_is_barangay_nutrition_scholar($con, $userId)) {
        $purokStmt = $con->prepare(
            'SELECT purok FROM purok WHERE barangay_id = ? ORDER BY purok ASC'
        );
        if ($purokStmt) {
            $purokStmt->bind_param('s', $activeBarangayId);
            $purokStmt->execute();
            $res = $purokStmt->get_result();
            while ($p = $res->fetch_assoc()) {
                $label = trim((string) ($p['purok'] ?? ''));
                if ($label !== '') {
                    $puroks[] = $label;
                }
            }
            $purokStmt->close();
        }
    }
}

try {
    // Take over sole web session token (non-SSA) and issue a fresh mobile token
    // (issue_token revokes any previous mobile tokens).
    if (!$allowsMulti) {
        // DB-only lock so web login is blocked while mobile session is active.
        barangay_session_guard_issue($con, $userId, false);
    }
    $issued = nutrition_api_issue_token($con, $userId, $deviceLabel, $activeBarangayId);
} catch (Throwable $e) {
    nutrition_api_error('Could not issue access token.', 500);
}

nutrition_api_send([
    'ok' => true,
    'token' => $issued['token'],
    'expires_at' => $issued['expires_at'],
    'token_type' => 'Bearer',
    'user' => nutrition_api_user_payload($con, $row),
    'barangays' => $barangays,
    'puroks' => array_values($puroks),
    'active_barangay_id' => $activeBarangayId,
]);
