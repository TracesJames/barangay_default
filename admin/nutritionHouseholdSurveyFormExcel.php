<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activeBarangay = barangay_require_active($con, 'barangayHub.php?picker=1&system=nutrition');
$prefillPurok = trim((string) ($_GET['purok'] ?? ''));
$memberRows = (int) ($_GET['members'] ?? 6);
$layout = trim((string) ($_GET['layout'] ?? 'form'));
if (!in_array($layout, ['form', 'bulk'], true)) {
    $layout = 'form';
}

nutrition_stream_household_survey_xlsx_form(
    $con,
    $activeBarangay,
    $prefillPurok,
    30,
    $memberRows,
    $layout
);
