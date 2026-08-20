<?php
/**
 * Bulk auto-link unlinked nutrition household surveys to residents (head name match).
 */
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_residence_sync.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_is_super_admin($con, $user_id)
    && !barangay_user_is_city_admin($con, $user_id)
    && !barangay_user_is_nutrition_portal_admin($con, $user_id)
    && !barangay_user_is_bns_admin($con, $user_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed.']);
    exit;
}

nutrition_ensure_module_tables($con);

$barangayId = trim((string) ($_POST['barangay_id'] ?? ''));
$limit = (int) ($_POST['limit'] ?? 100);
$limit = max(1, min(500, $limit));

if (barangay_user_is_bns_admin($con, $user_id)) {
    $sessionBrgy = (string) ($barangay_id ?? '');
    if ($sessionBrgy === '') {
        http_response_code(400);
        echo json_encode(['error' => 'No active barangay selected.']);
        exit;
    }
    $barangayId = $sessionBrgy;
}

$stats = nutrition_auto_link_unlinked_surveys($con, $barangayId !== '' ? $barangayId : null, $limit, $user_id);

echo json_encode([
    'ok' => true,
    'message' => sprintf(
        'Auto-link complete: %d linked, %d no match, %d skipped.',
        $stats['linked'],
        $stats['no_match'],
        $stats['skipped']
    ),
    'stats' => $stats,
]);
