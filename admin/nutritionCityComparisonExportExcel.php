<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_export_xlsx.php';

$userId = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_can_open_nutrition_city_hub($con, $userId)) {
    http_response_code(403);
    exit('City comparison export is only for Nutrition city hub roles.');
}

$filters = nutrition_export_filters_from_request($_GET);
$limitIds = barangay_user_is_cnpc($con, $userId)
    ? staff_assigned_barangay_ids($con, $userId)
    : null;
$rows = nutrition_cnpc_comparison_rows($con, $limitIds, $filters);
$sheets = nutrition_cnpc_comparison_export_sheets(
    $rows,
    (string) ($filters['date_from'] ?? ''),
    (string) ($filters['date_to'] ?? '')
);
$base = 'City_Nutrition_Comparison_'
    . ($filters['month'] !== '' ? $filters['month'] : date('Ymd'));

try {
    nutrition_export_stream_sheets($sheets, $base);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Excel export failed: ' . $e->getMessage());
}
