<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';
require_once '../includes/nutrition_bnp_reports.php';
require_once '../includes/nutrition_export_xlsx.php';

$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    exit('No active barangay selected.');
}

$typeKey = trim((string) ($_GET['type'] ?? 'all_hh'));
if (in_array($typeKey, ['eopt', 'eopt_plus'], true)) {
    header('Location: nutritionEoptExportExcel.php?' . http_build_query($_GET));
    exit;
}
if (nutrition_bnp_resolve_type($typeKey) === null) {
    $typeKey = 'all_hh';
}

$filters = nutrition_export_filters_from_request($_GET);
$filters = nutrition_apply_bns_report_filters($con, (string) $user_id, $barangayId, $filters);
$report = nutrition_bnp_build_report($con, $barangayId, $typeKey, $filters);
if ($report === []) {
    http_response_code(400);
    exit('Unknown BNP report type.');
}

$sheets = nutrition_bnp_export_sheets($report, $typeKey);
$base = 'BNP_' . $typeKey . '_' . preg_replace('/\s+/', '_', (string) $barangay) . '_'
    . ($filters['month'] !== '' ? $filters['month'] : date('Ymd'));

try {
    nutrition_export_stream_sheets($sheets, $base);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Excel export failed: ' . $e->getMessage());
}
