<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');
nutrition_ensure_module_tables($con);

$userId = (string) ($_SESSION['user_id'] ?? '');
$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

require_once __DIR__ . '/../includes/nutrition_bnp_reports.php';
$formC1 = nutrition_bnp_load_form_c1($con, $barangayId);
$schoolOnly = (string) ($_POST['bnp_c1_section'] ?? '') === 'school';

if ($schoolOnly) {
    if (!nutrition_user_can_edit_form_c1_school($con, $userId, $barangayId)) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to enter these school figures.']);
        exit;
    }
    $existing = nutrition_load_settings($con, $barangayId, (string) ($barangay ?? ''));
    foreach (nutrition_bnp_form_c1_school_keys() as $key) {
        $formC1[$key] = trim((string) ($_POST['bnp_c1_' . $key] ?? ''));
    }
    $children = str_replace(',', '', $formC1['grade1']);
    $weighed = str_replace(',', '', $formC1['school_weighed']);
    if ($formC1['school_weighing_pct'] === '' && is_numeric($children) && (float) $children > 0 && is_numeric($weighed)) {
        $formC1['school_weighing_pct'] = (string) (round(((float) $weighed / (float) $children) * 1000) / 10);
    }
    $data = [
        'nutrition_officer' => trim((string) ($existing['nutrition_officer'] ?? '')),
        'contact_number' => trim((string) ($existing['contact_number'] ?? '')),
        'assessment_frequency' => trim((string) ($existing['assessment_frequency'] ?? 'Monthly')),
        'report_header' => trim((string) ($existing['report_header'] ?? '')),
        'psfc_code' => nutrition_barangay_psgc_code($con, $barangayId, (string) ($barangay ?? '')),
        'enable_household_survey' => ($existing['enable_household_survey'] ?? 'YES') === 'NO' ? 'NO' : 'YES',
        'enable_barangay_survey' => ($existing['enable_barangay_survey'] ?? 'YES') === 'NO' ? 'NO' : 'YES',
        'bnp_form_c1' => json_encode($formC1, JSON_UNESCAPED_UNICODE),
    ];
    if ($data['report_header'] === '') {
        $data['report_header'] = 'Barangay ' . ($barangay ?? 'Nutrition') . ' Nutrition Profiling';
    }
    if (!nutrition_save_settings($con, $barangayId, $data)) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not save the school figures.']);
        exit;
    }
    $returnPage = basename(str_replace('\\', '/', (string) ($_POST['return_page'] ?? '')));
    if (!in_array($returnPage, ['nutritionBnpReport.php', 'nutritionSchoolEntry.php'], true)) {
        $returnPage = 'nutritionBnpReport.php';
    }
    $backQuery = ['c1_saved' => '1'];
    if ($returnPage === 'nutritionBnpReport.php') {
        $backQuery['type'] = 'all_hh';
    }
    foreach (['purok', 'date_from', 'date_to', 'mode'] as $returnKey) {
        $returnValue = trim((string) ($_POST['return_' . $returnKey] ?? ''));
        if ($returnValue !== '') {
            $backQuery[$returnKey] = $returnValue;
        }
    }
    $back = $returnPage . '?' . http_build_query($backQuery);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['ok' => true, 'message' => 'School figures saved. They will print on BNP Form C1 items 16–20.']);
        exit;
    }
    header('Location: ' . $back);
    exit;
}

if (!nutrition_user_can_save_settings($con, $userId)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to change nutrition settings.']);
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
];
foreach (array_keys($formC1) as $key) {
    $formC1[$key] = trim((string) ($_POST['bnp_c1_' . $key] ?? ''));
}
$data['bnp_form_c1'] = json_encode($formC1, JSON_UNESCAPED_UNICODE);

if ($data['report_header'] === '') {
    $data['report_header'] = 'Barangay ' . ($barangay ?? 'Nutrition') . ' Nutrition Profiling';
}

if (!nutrition_save_settings($con, $barangayId, $data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save settings.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Nutrition settings saved.']);
