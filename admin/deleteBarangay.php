<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
require_once '../includes/barangay_context.php';

header('Content-Type: application/json; charset=utf-8');

if (!barangay_user_is_super_admin($con, (string) $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Only the system administrator can delete barangays.']);
    exit;
}

$barangayId = trim($_POST['barangay_id'] ?? '');
if ($barangayId === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Barangay ID is required.']);
    exit;
}

$result = barangay_delete($con, $barangayId);
if (!$result['ok']) {
    http_response_code(409);
    echo json_encode([
        'error' => $result['error'] ?? 'Could not delete barangay.',
        'linked' => $result['linked'] ?? null,
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'barangay' => $result['barangay'] ?? '',
]);
