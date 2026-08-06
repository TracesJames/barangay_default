<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$typeKey = trim((string) ($_GET['type'] ?? 'all_hh'));
$meta = nutrition_bnp_resolve_type($typeKey);
if ($meta === null) {
    $typeKey = 'all_hh';
    $meta = nutrition_bnp_resolve_type($typeKey);
}

$filters = [
    'purok' => trim((string) ($_GET['purok'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$calendarYear = (int) ($_GET['year'] ?? date('Y'));
if ($calendarYear < 2000 || $calendarYear > 2100) {
    $calendarYear = (int) date('Y');
}
$reportMode = nutrition_bnp_normalize_mode($_GET['mode'] ?? 'consolidated');
$modeSelectable = false;
$autoDownload = (string) ($_GET['download'] ?? '') === '1';

$bnpReport = nutrition_bnp_build_report($con, (string) $barangay_id, $typeKey, $filters);
$barangayName = (string) $barangay;
$isCityWide = false;
$pregnantReport = $bnpReport;

// Prefer assigned BNS staff account, then settings / report value.
$bnsName = trim((string) ($bnpReport['bns_name'] ?? ''));
if ($bnsName === '') {
    $bnsName = trim((string) ($nutritionSettings['nutrition_officer'] ?? ''));
}
if ($bnsName === '') {
    $bnsAccounts = nutrition_bns_accounts_by_barangay($con);
    $assignedBns = $bnsAccounts[(string) $barangay_id] ?? null;
    if ($assignedBns) {
        $bnsName = trim((string) ($assignedBns['display_name'] ?? ''));
    }
}
$bnpReport['bns_name'] = $bnsName;
$pregnantReport['bns_name'] = $bnsName;
$punongBarangayName = barangay_punong_barangay_name($con, (string) $barangay_id, (string) $barangay);

$safeBarangay = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $barangayName) ?: 'barangay';
$safeSheet = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($meta['sheet'] ?? $typeKey)) ?: 'BNP';
$pdfFilename = 'BNP-' . $safeSheet . '-' . $safeBarangay . '-' . $calendarYear . '-' . $reportMode . '.pdf';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= barangay_h((string) ($meta['sheet'] ?? 'BNP')) ?> | <?= barangay_h($barangay) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,600;0,700;0,800&display=swap');
    body { font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#111; margin:16px; background:#fff; }
    .no-print { margin-bottom:10px; display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .no-print button {
      padding:8px 14px;
      border:0;
      border-radius:6px;
      cursor:pointer;
      color:#fff;
      font-weight:600;
    }
    .btn-print { background:#16a34a; }
    .btn-pdf { background:#1d4ed8; }
    .btn-pdf[disabled] { opacity:.7; cursor:wait; }
    .bnp-header-banner {
      display:grid;
      gap:10px;
      align-items:center;
      margin:0 0 10px;
      padding-bottom:8px;
      border-bottom:2px solid #166534;
    }
    .bnp-header-banner--city {
      grid-template-columns:minmax(100px,120px) minmax(0,1fr) minmax(100px,120px);
    }
    .bnp-header-banner--barangay {
      grid-template-columns:minmax(90px,130px) minmax(0,1fr) minmax(150px,210px);
    }
    .bnp-logo-side { display:flex; align-items:center; gap:8px; }
    .bnp-logo-side-left { justify-content:flex-start; }
    .bnp-logo-side-right { justify-content:flex-end; }
    .bnp-header-center { text-align:center; min-width:0; }
    .bnp-logo-cell { text-align:center; }
    .bnp-logo-circle {
      width:70px;
      height:70px;
      border-radius:50%;
      overflow:hidden;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:transparent;
    }
    .bnp-logo-img {
      width:100%;
      height:100%;
      object-fit:contain;
      object-position:center;
      background:transparent;
      border:0;
      display:block;
    }
    .bnp-logo-caption { margin-top:3px; font-size:7.5pt; font-weight:700; color:#14532d; }
    .bnp-form-code { font-size:9pt; color:#444; }
    .bnp-title { font-size:16pt; font-weight:800; text-align:center; letter-spacing:.04em; }
    .bnp-subtitle,.bnp-cy { text-align:center; font-weight:700; font-size:11pt; }
    .bnp-focus { text-align:center; font-weight:800; text-decoration:underline; margin:4px 0 4px; font-size:12pt; }
    .bnp-mode-title { text-align:center; font-weight:800; letter-spacing:.06em; color:#b91c1c; margin:0 0 6px; font-size:12pt; }
    .bnp-bns { margin-bottom:10px; font-size:10pt; }
    .bnp-table { width:100%; border-collapse:collapse; font-size:9.5pt; margin-bottom:10px; }
    .bnp-table th,.bnp-table td { border:1px solid #222; padding:3px 5px; }
    .bnp-table th { background:#f0f0f0; text-align:center; }
    .bnp-num { text-align:center; width:16%; }
    .bnp-section td { background:#e8f5e9; font-weight:700; }
    .bnp-sub td { background:#f8fafc; }
    .bnp-section-title { margin:8px 0 4px; font-size:10pt; }
    .bnp-occupation { margin:8px 0; font-size:10pt; }
    .bnp-sign { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:14px; font-size:9pt; }
    .bnp-footnote { margin-top:8px; font-size:8.5pt; color:#444; }
    .bnp-pregnant-report .bnp-title { font-size:16pt; }
    .bnp-mode { text-align:center; margin:4px 0 8px; }
    .bnp-mode label { margin:0 10px; font-weight:700; }
    @media print { body { margin:6px; } .no-print { display:none !important; } }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body<?= $autoDownload ? '' : ' onload="window.print()"' ?>>
  <div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">Print</button>
    <button type="button" class="btn-pdf" id="bnpDownloadPdfBtn">Download PDF</button>
    <span id="bnpPdfStatus" style="color:#444;font-size:13px;"></span>
  </div>

  <div id="bnpPrintRoot" data-report-fit="a4">
  <?php
  $layout = (string) ($meta['layout'] ?? '');
  if ($layout === 'all_hh') {
      require __DIR__ . '/../includes/partials/nutrition_bnp_all_hh.php';
  } elseif ($layout === 'pregnant') {
      require __DIR__ . '/../includes/partials/nutrition_pregnant_families_report.php';
  } else {
      require __DIR__ . '/../includes/partials/nutrition_bnp_family_profile.php';
  }
  ?>
  </div>

  <script src="../assets/plugins/jsPDF/html2canvas.min.js"></script>
  <script src="../assets/plugins/jsPDF/jspdf.umd.min.js"></script>
  <script src="../assets/js/nutrition-print-pdf.js?v=20260730a"></script>
  <script>
  (function () {
    var filename = <?= json_encode($pdfFilename) ?>;
    var autoDownload = <?= $autoDownload ? 'true' : 'false' ?>;
    var btn = document.getElementById('bnpDownloadPdfBtn');
    var statusEl = document.getElementById('bnpPdfStatus');

    function setStatus(text) {
      if (statusEl) statusEl.textContent = text || '';
    }

    function downloadPdf() {
      if (typeof window.nutritionDownloadPrintPdf !== 'function') {
        setStatus('PDF helper failed to load. Use Print → Save as PDF instead.');
        return;
      }
      window.nutritionDownloadPrintPdf({
        root: document.getElementById('bnpPrintRoot'),
        filename: filename,
        button: btn,
        setStatus: setStatus,
        scale: 1.5
      });
    }

    if (btn) {
      btn.addEventListener('click', downloadPdf);
    }
    if (autoDownload) {
      window.addEventListener('load', function () {
        setTimeout(downloadPdf, 400);
      });
    }
  })();
  </script>
</body>
</html>
