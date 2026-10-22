<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/session_guard.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    nutrition_api_error('Method not allowed. Use POST.', 405);
}

$auth = nutrition_api_authenticate($con, true);
$userId = (string) ($auth['user_id'] ?? '');
nutrition_api_revoke_token($con, $auth['token_id']);
// If no other mobile tokens remain, clear the shared single-session lock.
if ($userId !== '' && !nutrition_api_user_has_active_mobile_session($con, $userId)) {
    barangay_session_guard_clear($con, $userId);
}

nutrition_api_send([
    'ok' => true,
    'message' => 'Logged out. Token revoked.',
]);
