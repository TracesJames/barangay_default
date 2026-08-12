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

$user_id = (string) ($_SESSION['user_id'] ?? '');
$barangayId = trim((string) ($_POST['barangay_id'] ?? ''));
$scope = trim((string) ($_POST['scope'] ?? ''));
if ($scope === 'barangay' && $barangayId === '') {
    $barangayId = trim((string) (barangay_session_id() ?? ''));
}

$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);
$isBns = barangay_user_is_barangay_nutrition_scholar($con, $user_id);
$isCnpc = barangay_user_is_cnpc($con, $user_id);
$isSsa = barangay_user_is_ssa($con, $user_id);

$isBarangayScope = $barangayId !== '';

if ($isBarangayScope) {
    $allowed = $isSsa || $isSuperAdmin || $isCityAdmin || $isNutritionPortalAdmin || $isBnsAdmin || $isBns || $isCnpc;
    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to save barangay MELLPI profile.']);
        exit;
    }
    if ($isBns) {
        $assigned = barangay_user_barangay_id($con, $user_id);
        if ($assigned === null || $assigned !== $barangayId) {
            http_response_code(403);
            echo json_encode(['error' => 'BNS may only save MELLPI for their assigned barangay.']);
            exit;
        }
    }
    if ($isCnpc && !staff_user_can_access_barangay($con, $user_id, $barangayId)) {
        http_response_code(403);
        echo json_encode(['error' => 'CNPC may only save MELLPI for assigned barangays.']);
        exit;
    }
} else {
    if (!$isSuperAdmin && !$isBnsAdmin && !$isCityAdmin && !$isNutritionPortalAdmin && !$isSsa) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized.']);
        exit;
    }
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
    'city_name' => trim((string) ($_POST['city_name'] ?? ($isBarangayScope ? '' : 'City of Valencia'))),
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

if (!nutrition_mellpi_save_profile($con, $data, $user_id, $isBarangayScope ? $barangayId : null)) {
    http_response_code(500);
    echo json_encode(['error' => $isBarangayScope
        ? 'Could not save MELLPI barangay profile.'
        : 'Could not save MELLPI city profile.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => $isBarangayScope
        ? 'MELLPI Barangay Profile Sheet saved.'
        : 'MELLPI City/Municipality Profile Sheet saved.',
]);
