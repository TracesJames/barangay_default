<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

$auth = nutrition_api_authenticate($con);
$body = nutrition_api_json_body();
$status = trim((string) ($_GET['status'] ?? $body['status'] ?? ''));
if ($status === '' || !isset(nutrition_status_options()[$status])) {
    nutrition_api_error('Valid status is required (normal, underweight, wasted, …).', 422);
}

$override = trim((string) ($_GET['barangay_id'] ?? $body['barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $override !== '' ? $override : null);
$children = nutrition_list_children_by_dashboard_status($con, $status, $ctx['barangay_id']);

// BNS purok filter when assigned.
if (barangay_user_is_barangay_nutrition_scholar($con, $auth['user_id'])) {
    $allowed = staff_assigned_purok_labels($con, $auth['user_id'], $ctx['barangay_id']);
    if ($allowed !== []) {
        $allowedMap = array_fill_keys($allowed, true);
        $children = array_values(array_filter(
            $children,
            static function (array $row) use ($allowedMap): bool {
                $purok = trim((string) ($row['purok'] ?? ''));
                // Assessment rows may have empty purok — keep them.
                return $purok === '' || isset($allowedMap[$purok]);
            }
        ));
    }
}

nutrition_api_send([
    'ok' => true,
    'status' => $status,
    'label' => nutrition_status_label($status),
    'barangay_id' => $ctx['barangay_id'],
    'barangay_name' => $ctx['barangay_name'],
    'count' => count($children),
    'children' => $children,
]);
