<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_mellpi.php';

header('Content-Type: application/json; charset=utf-8');

nutrition_ensure_module_tables($con);
nutrition_mellpi_ensure_table($con);

$user_id = (string) $_SESSION['user_id'];
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);

if (!$isSuperAdmin && !$isBnsAdmin && !$isCityAdmin && !$isNutritionPortalAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized.']);
    exit;
}

/**
 * @param mixed $value
 * @return array<string, mixed>|string
 */
$sanitizeTree = static function ($value) use (&$sanitizeTree) {
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = $sanitizeTree($v);
        }

        return $out;
    }

    return trim((string) $value);
};

$data = [
    'city_name' => trim((string) ($_POST['city_name'] ?? 'City of Valencia')),
    'province' => trim((string) ($_POST['province'] ?? 'Bukidnon')),
    'income_class' => trim((string) ($_POST['income_class'] ?? '')),
    'date_of_monitoring' => trim((string) ($_POST['date_of_monitoring'] ?? date('Y-m-d'))),
    'period_covered' => trim((string) ($_POST['period_covered'] ?? '')),
    'community' => $sanitizeTree($_POST['community'] ?? []),
    'population_snapshot' => $sanitizeTree($_POST['population_snapshot'] ?? []),
    'preschool' => $sanitizeTree($_POST['preschool'] ?? []),
    'school' => $sanitizeTree($_POST['school'] ?? []),
    'pregnant_status' => $sanitizeTree($_POST['pregnant_status'] ?? []),
    'bns' => $sanitizeTree($_POST['bns'] ?? []),
    'hazards' => array_values(is_array($_POST['hazards'] ?? null) ? $sanitizeTree($_POST['hazards']) : []),
    'land_use' => $sanitizeTree($_POST['land_use'] ?? []),
];

if (!nutrition_mellpi_save_profile($con, $data, $user_id)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save MELLPI city profile.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'MELLPI City/Municipality Profile Sheet saved.']);
