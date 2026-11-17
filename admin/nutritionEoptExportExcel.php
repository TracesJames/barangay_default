<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';
require_once '../includes/nutrition_eopt_reports.php';
require_once '../includes/nutrition_export_xlsx.php';

$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    exit('No active barangay selected.');
}

$filters = nutrition_export_filters_from_request($_GET);
$filters = nutrition_apply_bns_report_filters($con, (string) $user_id, $barangayId, $filters);
$report = nutrition_eopt_build_report($con, $barangayId, $filters);
$sheets = nutrition_eopt_export_sheets($report);
$base = 'eOPT_' . preg_replace('/\s+/', '_', (string) $barangay) . '_'
    . ($filters['month'] !== '' ? $filters['month'] : date('Ymd'));

try {
    nutrition_export_stream_sheets($sheets, $base);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Excel export failed: ' . $e->getMessage());
}
