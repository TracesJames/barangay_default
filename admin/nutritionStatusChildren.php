<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_can_access_nutrition_portal($con, $userId)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Access denied.']);
    exit;
}

$status = trim((string) ($_GET['status'] ?? $_POST['status'] ?? ''));
if ($status === '' || !isset(nutrition_status_options()[$status])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid nutritional status.']);
    exit;
}

$sessionBarangayId = trim((string) ($barangay_id ?? ''));
$wantCity = strtolower(trim((string) ($_GET['scope'] ?? $_POST['scope'] ?? ''))) === 'city';
$canCityHub = barangay_user_can_open_nutrition_city_hub($con, $userId);

// City hub chips must list city-wide even if a barangay is still active in session.
$cityWide = $wantCity && $canCityHub;
$barangayId = $cityWide ? '' : $sessionBarangayId;

if ($barangayId === '' && !$cityWide && !$canCityHub) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No active barangay selected.']);
    exit;
}
if ($barangayId === '' && !$cityWide && $canCityHub) {
    // Hub role with no barangay and no explicit city scope → city-wide.
    $cityWide = true;
}

$children = [];
if ($barangayId !== '') {
    $children = nutrition_list_children_by_dashboard_status($con, $status, $barangayId);
} elseif (barangay_user_is_cnpc($con, $userId) && function_exists('staff_assigned_barangay_ids')) {
    $assigned = staff_assigned_barangay_ids($con, $userId);
    if (!is_array($assigned) || $assigned === []) {
        $children = [];
    } else {
        foreach ($assigned as $bid) {
            foreach (nutrition_list_children_by_dashboard_status($con, $status, (string) $bid) as $row) {
                $children[] = $row;
            }
        }
        usort($children, static function (array $a, array $b): int {
            $cmp = strcasecmp((string) ($a['barangay'] ?? ''), (string) ($b['barangay'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });
    }
} else {
    $children = nutrition_list_children_by_dashboard_status($con, $status, null);
}

echo json_encode([
    'ok' => true,
    'status' => $status,
    'label' => nutrition_status_label($status),
    'count' => count($children),
    'city_wide' => $cityWide,
    'children' => $children,
], JSON_UNESCAPED_UNICODE);
