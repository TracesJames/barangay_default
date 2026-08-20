<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$filters = [
    'purok' => trim((string) ($_GET['purok'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$calendarYear = (int) ($_GET['year'] ?? date('Y'));
if ($calendarYear < 2000 || $calendarYear > 2100) {
    $calendarYear = (int) date('Y');
}

$pregnantReport = nutrition_pregnant_families_report($con, (string) $barangay_id, $filters);
$barangayName = (string) $barangay;
$reportMode = nutrition_bnp_normalize_mode($_GET['mode'] ?? 'consolidated');
$modeSelectable = false;
$isCityWide = false;
$nutritionSettings = $nutritionSettings ?? nutrition_load_settings($con, (string) $barangay_id, (string) $barangay);
$bnsName = trim((string) ($pregnantReport['bns_name'] ?? ($nutritionSettings['nutrition_officer'] ?? '')));
if ($bnsName === '') {
    $bnsAccounts = nutrition_bns_accounts_by_barangay($con);
    $assignedBns = $bnsAccounts[(string) $barangay_id] ?? null;
    if ($assignedBns) {
        $bnsName = trim((string) ($assignedBns['display_name'] ?? ''));
    }
}
$pregnantReport['bns_name'] = $bnsName;
$punongBarangayName = barangay_punong_barangay_name($con, (string) $barangay_id, (string) $barangay);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Families with Pregnant | <?= barangay_h($barangay) ?></title>
  <style>
    @import url('../assets/css/local-fonts.css');
    body { font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111; margin: 18px; }
    .no-print { margin-bottom: 12px; }
    .bnp-title { font-size: 18pt; font-weight: 800; letter-spacing: .04em; }
    .bnp-header-banner {
      display: grid;
      gap: 10px;
      align-items: center;
      margin: 0 0 10px;
      padding-bottom: 8px;
      border-bottom: 2px solid #166534;
    }
    .bnp-header-banner--city {
      grid-template-columns: minmax(100px, 120px) minmax(0, 1fr) minmax(100px, 120px);
    }
    .bnp-header-banner--barangay {
      grid-template-columns: minmax(90px, 130px) minmax(0, 1fr) minmax(150px, 210px);
    }
    .bnp-logo-side { display: flex; align-items: center; gap: 8px; }
    .bnp-logo-side-left { justify-content: flex-start; }
    .bnp-logo-side-right { justify-content: flex-end; }
    .bnp-header-center { text-align: center; min-width: 0; }
    .bnp-logo-cell { text-align: center; }
    .bnp-logo-circle {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      overflow: hidden;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: transparent;
    }
    .bnp-logo-img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      object-position: center;
      background: transparent;
      border: 0;
      display: block;
    }
    .bnp-logo-caption { margin-top: 3px; font-size: 7.5pt; font-weight: 700; color: #14532d; }
    .bnp-subtitle, .bnp-cy { font-size: 12pt; font-weight: 700; }
    .bnp-focus { font-size: 13pt; font-weight: 800; text-decoration: underline; margin: 6px 0; }
    .bnp-mode { margin: 6px 0 10px; }
    .bnp-mode label { margin-right: 18px; font-size: 11pt; }
    .bnp-bns { margin-bottom: 12px; font-size: 11pt; }
    .bnp-table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 12px; }
    .bnp-table th, .bnp-table td { border: 1px solid #222; padding: 4px 6px; }
    .bnp-table th { background: #f0f0f0; text-align: center; }
    .bnp-item { text-align: left; }
    .bnp-num { text-align: center; width: 11%; }
    .bnp-section td { background: #e8f5e9; font-weight: 700; }
    .bnp-section-title { margin: 10px 0 4px; font-size: 11pt; }
    .bnp-occupation { margin-top: 10px; font-size: 11pt; }
    .bnp-footnote { margin-top: 8px; font-size: 9pt; color: #444; }
    @media print {
      body { margin: 8px; }
      .no-print { display: none !important; }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body onload="window.print()">
  <button type="button" class="btn no-print" onclick="window.print()" style="padding:8px 14px;background:#16a34a;color:#fff;border:0;border-radius:6px;cursor:pointer;">
    Print / Save as PDF
  </button>
  <div id="pregnantPrintRoot" data-report-fit="root">
  <?php require __DIR__ . '/../includes/partials/nutrition_pregnant_families_report.php'; ?>
  </div>
</body>
</html>
