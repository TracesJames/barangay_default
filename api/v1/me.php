<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

$auth = nutrition_api_authenticate($con, true);
$userId = $auth['user_id'];
$barangays = nutrition_api_accessible_barangays($con, $userId);
$activeBarangayId = $auth['active_barangay_id'];
if ($activeBarangayId === '') {
    $activeBarangayId = nutrition_api_default_barangay_id($con, $userId, $barangays);
}

$puroks = [];
if ($activeBarangayId !== '') {
    $puroks = staff_assigned_purok_labels($con, $userId, $activeBarangayId);
    if ($puroks === [] && barangay_user_is_barangay_nutrition_scholar($con, $userId)) {
        $purokStmt = $con->prepare('SELECT purok FROM purok WHERE barangay_id = ? ORDER BY purok ASC');
        if ($purokStmt) {
            $purokStmt->bind_param('s', $activeBarangayId);
            $purokStmt->execute();
            $res = $purokStmt->get_result();
            while ($p = $res->fetch_assoc()) {
                $label = trim((string) ($p['purok'] ?? ''));
                if ($label !== '') {
                    $puroks[] = $label;
                }
            }
            $purokStmt->close();
        }
    }
}

nutrition_api_send([
    'ok' => true,
    'user' => nutrition_api_user_payload($con, $auth['user']),
    'barangays' => $barangays,
    'puroks' => array_values($puroks),
    'active_barangay_id' => $activeBarangayId,
]);
