<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');
nutrition_ensure_module_tables($con);

$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

$purokInput = trim((string) ($_GET['purok'] ?? $_POST['purok'] ?? ''));
$purokLabel = nutrition_purok_label_from_number($purokInput);
if ($purokLabel === '' && $purokInput !== '') {
    $purokLabel = trim($purokInput);
}
if ($purokLabel === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please select a Purok / Sitio from the official list.']);
    exit;
}

$count = max(1, (int) ($_GET['count'] ?? $_POST['count'] ?? 1));
$preview = nutrition_household_reference_preview(
    $con,
    $barangayId,
    $purokLabel,
    (string) ($barangay ?? ''),
    $count
);

echo json_encode([
    'ok' => true,
    'household_id' => $preview['household_id'],
    'resident_number' => $preview['resident_number'],
    'resident_numbers' => $preview['resident_numbers'],
    'psfc_code' => $preview['psfc_code'],
    'purok_code' => $preview['purok_code'],
    'series' => $preview['series'],
    'format' => 'PSGC-Purok-#####',
]);
