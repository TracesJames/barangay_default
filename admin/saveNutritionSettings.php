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

$data = [
    'nutrition_officer' => trim((string) ($_POST['nutrition_officer'] ?? '')),
    'contact_number' => trim((string) ($_POST['contact_number'] ?? '')),
    'assessment_frequency' => trim((string) ($_POST['assessment_frequency'] ?? 'Monthly')),
    'report_header' => trim((string) ($_POST['report_header'] ?? '')),
    'psfc_code' => nutrition_barangay_psgc_code($con, $barangayId, (string) ($barangay ?? '')),
    'enable_household_survey' => ($_POST['enable_household_survey'] ?? 'YES') === 'NO' ? 'NO' : 'YES',
    'enable_barangay_survey' => ($_POST['enable_barangay_survey'] ?? 'YES') === 'NO' ? 'NO' : 'YES',
    'kobo_enabled' => ($_POST['kobo_enabled'] ?? 'NO') === 'YES' ? 'YES' : 'NO',
    'kobo_server_url' => nutrition_kobo_normalize_server_url(trim((string) ($_POST['kobo_server_url'] ?? ''))),
    'kobo_api_token' => trim((string) ($_POST['kobo_api_token'] ?? '')),
    'kobo_asset_uid' => trim((string) ($_POST['kobo_asset_uid'] ?? '')),
    'kobo_form_url' => trim((string) ($_POST['kobo_form_url'] ?? '')),
];

require_once __DIR__ . '/../includes/nutrition_bnp_reports.php';
$formC1 = nutrition_bnp_form_c1_defaults();
foreach (array_keys($formC1) as $key) {
    $formC1[$key] = trim((string) ($_POST['bnp_c1_' . $key] ?? ''));
}
$data['bnp_form_c1'] = json_encode($formC1, JSON_UNESCAPED_UNICODE);

$existingSettings = nutrition_load_settings($con, $barangayId, (string) ($barangay ?? ''));
if ($data['kobo_api_token'] === '' && ($existingSettings['kobo_api_token'] ?? '') !== '') {
    $data['kobo_api_token'] = (string) $existingSettings['kobo_api_token'];
}

if ($data['report_header'] === '') {
    $data['report_header'] = 'Barangay ' . ($barangay ?? 'Nutrition') . ' Nutrition Profiling';
}

if (!nutrition_save_settings($con, $barangayId, $data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save settings.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Nutrition settings saved.']);
