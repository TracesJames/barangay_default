<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/staff_permissions.php';

// Prevent notices/warnings from breaking JSON clients.
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || isset($_POST['ajax'])
    || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

$jsonExit = static function (array $payload, int $code = 200) use ($isAjax): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if ($isAjax) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
    if ($code >= 400) {
        http_response_code($code);
        echo (string) ($payload['error'] ?? 'Request failed');
        exit;
    }
};

$barangayId = trim((string) ($_POST['barangay_id'] ?? ''));
if ($barangayId === '') {
    $jsonExit(['error' => 'Missing barangay'], 400);
}

$row = barangay_load_by_id($con, $barangayId);
if ($row === null) {
    $jsonExit(['error' => 'Barangay not found'], 404);
}

$userId = (string) ($_SESSION['user_id'] ?? '');
$canPickAny = function_exists('barangay_user_can_pick_barangay')
    && barangay_user_can_pick_barangay($con, $userId);

if (!$canPickAny) {
    $allowed = function_exists('staff_user_can_access_barangay')
        && staff_user_can_access_barangay($con, $userId, $barangayId);
    if (!$allowed) {
        $jsonExit(['error' => 'Access denied for this barangay'], 403);
    }
}

barangay_set_active($barangayId);

$redirect = nutrition_allowed_redirect(trim((string) ($_POST['redirect'] ?? 'dashboard.php')));
$requested = trim((string) ($_POST['redirect'] ?? ''));
if ($requested !== '' && str_contains($requested, 'nutrition')) {
    $redirect = 'nutritionDashboard.php';
} elseif (
    $requested === ''
    && (
        barangay_user_is_nutrition_portal_admin($con, $userId)
        || barangay_user_is_bns_admin($con, $userId)
        || barangay_user_is_cnpc($con, $userId)
    )
) {
    $redirect = 'nutritionDashboard.php';
}

if ($isAjax) {
    $jsonExit(['ok' => true, 'redirect' => $redirect]);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Location: ' . $redirect);
exit;
