<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/nutrition_context.php';

$barangayId = trim($_POST['barangay_id'] ?? '');
if ($barangayId === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Missing barangay']);
    exit;
}

$row = barangay_load_by_id($con, $barangayId);
if ($row === null) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Barangay not found']);
    exit;
}

$userBarangayId = barangay_user_barangay_id($con, (string) $_SESSION['user_id']);
if ($userBarangayId !== null && $userBarangayId !== $barangayId) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

barangay_set_active($barangayId);

$redirect = nutrition_allowed_redirect(trim((string) ($_POST['redirect'] ?? 'dashboard.php')));
if (barangay_user_is_nutrition_portal_admin($con, (string) $_SESSION['user_id'])) {
    $redirect = 'nutritionDashboard.php';
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'redirect' => $redirect]);
    exit;
}

header('Location: ' . $redirect);
exit;
