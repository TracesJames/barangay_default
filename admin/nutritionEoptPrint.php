<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';
require_once '../includes/nutrition_eopt_reports.php';

$filters = [
    'purok' => trim((string) ($_GET['purok'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$calendarYear = (int) ($_GET['year'] ?? date('Y'));
if ($calendarYear < 2000 || $calendarYear > 2100) {
    $calendarYear = (int) date('Y');
}

$eoptReport = nutrition_eopt_build_report($con, (string) $barangay_id, $filters);
$meta = $eoptReport['meta'] ?? [];
$safeBarangay = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $barangay) ?: 'barangay';
$pdfFilename = 'eOPT-Plus-' . $safeBarangay . '-' . $calendarYear . '.pdf';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>e-OPT Plus | <?= barangay_h($barangay) ?></title>
  <style>
    @import url('../assets/css/local-fonts.css');
    body { font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#111; margin:16px; background:#fff; }
    .no-print { margin-bottom:10px; display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .no-print button, .no-print a.btn-linkish {
      padding:8px 14px; border:0; border-radius:6px; cursor:pointer; color:#fff; font-weight:600; text-decoration:none; display:inline-block;
    }
    .btn-print { background:#16a34a; }
    .btn-close { background:#64748b; }
    .eopt-cover { text-align:center; margin:0 0 14px; padding-bottom:10px; border-bottom:2px solid #1e3a8a; }
    .eopt-cover h1 { font-size:15pt; margin:0 0 6px; color:#1e3a8a; }
    .eopt-cover p { margin:3px 0; font-size:10pt; }
    .eopt-section { margin-bottom:14px; }
    .eopt-break { page-break-before: always; break-before: page; }
    .eopt-sheet-title { font-size:12pt; font-weight:800; text-align:center; margin:0 0 4px; }
    .eopt-focus { text-align:center; font-weight:700; font-size:9.5pt; margin:0 0 5px; }
    .eopt-meta { text-align:center; font-size:9pt; margin-bottom:6px; }
    .eopt-table { width:100%; border-collapse:collapse; font-size:8pt; margin-bottom:8px; table-layout:fixed; }
    .eopt-table th,.eopt-table td { border:1px solid #222; padding:2px 3px; vertical-align:top; word-wrap:break-word; overflow-wrap:anywhere; }
    .eopt-table th { background:#eef2ff; text-align:center; }
    .eopt-table thead { display: table-header-group; }
    .eopt-matrix th,.eopt-matrix td { font-size:7pt; padding:1px 2px; }
    .eopt-num { text-align:center; white-space:nowrap; }
    .eopt-item { text-align:left; }
    .eopt-empty { text-align:center; color:#555; font-style:italic; }
    .eopt-section-row td { background:#dbeafe; font-weight:700; }
    .eopt-note { font-size:7.5pt; color:#444; margin-top:5px; }
    .eopt-compact { font-size:7.5pt; }
    .eopt-form1b .eopt-item-col { min-width:140px; text-align:left; }
    .eopt-form1b .eopt-blank { background:#111; color:#111; }
    .eopt-form1b .eopt-summary { background:#111; color:#fff; font-weight:700; }
    .eopt-form1b .eopt-total-row td { background:#111; color:#fff; font-weight:700; }
    .eopt-form1b .eopt-note-row td { background:#f8fafc; font-size:6.5pt; }
    .eopt-form1b-meta { font-size:8.5pt; }
    @page {
      size: A4 portrait;
      margin: 10mm 8mm;
    }
    @page eopt-landscape {
      size: A4 landscape;
      margin: 10mm 8mm;
    }
    .eopt-landscape {
      page: eopt-landscape;
    }
    @media print {
      .no-print { display:none !important; }
      body { margin: 0; }
      #eoptPrintRoot {
        width: auto;
        max-width: none;
        margin: 0;
        padding: 0;
      }
      .eopt-landscape {
        page: eopt-landscape;
        page-break-before: always;
        break-before: page;
      }
      .eopt-landscape .eopt-form1b {
        table-layout: auto;
        font-size: 7.5pt;
      }
      .eopt-landscape .eopt-form1b th,
      .eopt-landscape .eopt-form1b td {
        padding: 2px 3px;
        font-size: 7pt;
      }
      .eopt-landscape .eopt-item-col {
        min-width: 160px;
        width: 18%;
      }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body>
  <div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">Print / Save PDF</button>
    <button type="button" class="btn-close" onclick="window.close()">Close</button>
    <span style="color:#555;font-size:13px;"><?= barangay_h($pdfFilename) ?></span>
  </div>

  <div id="eoptPrintRoot" data-report-fit="root">
    <section class="eopt-cover">
      <h1>e-OPT Plus Community Level Tool</h1>
      <p><strong><?= barangay_h((string) ($meta['version'] ?? 'Region 10 · ver2')) ?></strong></p>
      <p>Barangay <?= barangay_h($barangay) ?>, <?= barangay_h((string) ($meta['municipality'] ?? 'City of Valencia')) ?>, <?= barangay_h((string) ($meta['province'] ?? 'Bukidnon')) ?></p>
      <p>Form 1A · Form 1B · Form 1C · NutStatusBrgy · DQC · Graphs · Monitoring Lists</p>
      <p>Measured preschoolers (0–59 mos): <strong><?= number_format((int) ($eoptReport['totals']['measured'] ?? 0)) ?></strong>
         · At-risk: <strong><?= number_format((int) ($eoptReport['totals']['at_risk'] ?? 0)) ?></strong></p>
    </section>

    <?php require __DIR__ . '/../includes/partials/nutrition_eopt_print_bundle.php'; ?>
  </div>
</body>
</html>
