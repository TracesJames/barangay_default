<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_bnp_reports.php';
require_once '../includes/nutrition_eopt_reports.php';
require_once '../includes/nutrition_mellpi.php';

nutrition_ensure_module_tables($con);

$user_id = (string) $_SESSION['user_id'];
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);

if (!barangay_user_can_open_nutrition_city_hub($con, $user_id)) {
    header('Location: ' . (barangay_user_can_access_barangay_hub($con, $user_id) ? 'dashboard.php' : 'nutritionDashboard.php'));
    exit;
}

date_default_timezone_set('Asia/Manila');

$filters = [
    'purok' => trim((string) ($_GET['purok'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$calendarYear = (int) ($_GET['year'] ?? date('Y'));
if ($calendarYear < 2000 || $calendarYear > 2100) {
    $calendarYear = (int) date('Y');
}

$reportMode = 'consolidated';
$modeSelectable = false;
$isCityWide = true;
$barangayName = 'All Barangays';
$nutritionSettings = ['nutrition_officer' => ''];
$assetPrefix = '../';

$cityBnpReports = nutrition_bnp_city_all_reports($con, $filters);
$eoptReport = nutrition_eopt_build_report($con, null, $filters);
$mellpiReport = nutrition_mellpi_build_report($con);

$cityNutritionHeadName = 'Hazel Dondonayos, RND';
$cityNutritionHeadTitle = 'City Nutrition Head';
$cityMayorName = 'Hon. Amie G. Galario';
$cityMayorTitle = 'City Mayor / CNC Chairperson';
$bnsName = '';
$generatedAt = date('F j, Y g:i A');
$autoDownload = (string) ($_GET['download'] ?? '') === '1';
$autoPrint = (string) ($_GET['print'] ?? '') === '1';
$pdfFilename = 'City-Nutrition-Report-Valencia-' . $calendarYear . '-consolidated.pdf';
$cityLogoUrl = barangay_default_logo_url('../');
$nncLogoUrl = '../assets/logo/national-nutrition-council.png';
$bnpSheetLabels = [];
foreach ($cityBnpReports as $pack) {
    $sheet = trim((string) (($pack['meta']['sheet'] ?? '') ?: ($pack['meta']['title'] ?? $pack['key'])));
    if ($sheet !== '') {
        $bnpSheetLabels[] = $sheet;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>City BNP + e-OPT Plus Report | Valencia City</title>
  <style>
    @import url('../assets/css/local-fonts.css');
    @page {
      size: A4 portrait;
      margin: 12mm 10mm 14mm 10mm;
    }
    * { box-sizing: border-box; }
    html {
      width: 100%;
      max-width: 100%;
      -webkit-text-size-adjust: 100%;
      text-size-adjust: 100%;
    }
    body {
      font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color:#111;
      margin: 0;
      padding: 0;
      width: 100%;
      max-width: 100%;
      background:#e5e9ee;
      overflow-x: hidden;
    }
    .no-print {
      position: sticky;
      top: 0;
      z-index: 40;
      margin: 0;
      padding: 10px 12px;
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      align-items:center;
      background: rgba(17, 24, 39, 0.92);
      color: #f8fafc;
      backdrop-filter: blur(6px);
    }
    .no-print button {
      padding:8px 14px;
      border:0;
      border-radius:6px;
      cursor:pointer;
      color:#fff;
      font-weight:600;
      min-height: 40px;
    }
    .no-print .btn-print { background:#16a34a; }
    .no-print .btn-pdf { background:#1d4ed8; }
    .no-print .btn-pdf[disabled] { opacity:.7; cursor:wait; }
    .no-print #cityReportPdfStatus {
      font-size: 12px;
      opacity: .9;
      flex: 1 1 180px;
    }

    #cityReportPrintRoot {
      width: 210mm;
      max-width: 210mm;
      margin: 0 auto;
      padding: 10mm 9mm;
      background: #fff;
    }

    .report-cover {
      min-height: 240mm;
      display:flex;
      flex-direction:column;
      justify-content:center;
      text-align:center;
      page-break-after: always;
      break-after: page;
      padding: 18mm 8mm;
    }
    .report-cover-logos {
      display:flex;
      justify-content:center;
      align-items:center;
      gap:36px;
      margin-bottom:22px;
      flex-wrap: wrap;
    }
    .report-cover-logos img {
      width:92px;
      height:92px;
      max-width: 28vw;
      object-fit:contain;
    }
    .report-cover h1 {
      font-size:18pt;
      margin:0 0 8px;
      letter-spacing:.04em;
      color:#14532d;
      line-height:1.25;
    }
    .report-cover .eyebrow {
      font-size:10pt;
      font-weight:700;
      letter-spacing:.12em;
      text-transform:uppercase;
      color:#166534;
      margin:0 0 10px;
    }
    .report-cover .subtitle {
      font-size:11.5pt;
      font-weight:700;
      margin:0 0 6px;
    }
    .report-cover .meta-line {
      margin:4px 0;
      font-size:10.5pt;
      color:#333;
    }
    .report-toc {
      margin:28px auto 0;
      max-width:420px;
      text-align:left;
      border:1px solid #166534;
      padding:12px 16px;
      background:#f8faf8;
    }
    .report-toc h2 {
      margin:0 0 8px;
      font-size:11pt;
      text-align:center;
      color:#14532d;
    }
    .report-toc ol {
      margin:0;
      padding-left:20px;
      font-size:10pt;
      line-height:1.55;
    }

    .section-banner {
      text-align:center;
      margin:0 0 12px;
      padding:10px 8px 12px;
      border-bottom:3px solid #166534;
      page-break-after: avoid;
      break-after: avoid;
    }
    .section-banner--blue { border-bottom-color:#1d4ed8; }
    .section-banner h2 {
      margin:0 0 4px;
      font-size:14pt;
      letter-spacing:.03em;
    }
    .section-banner p { margin:2px 0; font-size:10pt; }

    .page-break { page-break-before: always; break-before: page; }
    .page-break-after { page-break-after: always; break-after: page; }
    .avoid-break { page-break-inside: avoid; break-inside: avoid; }

    .mellpi-form { margin-bottom: 12px; }
    .mellpi-header {
      display:flex;
      align-items:center;
      gap:12px;
      margin-bottom:10px;
      padding-bottom:8px;
      border-bottom:2px solid #166534;
    }
    .mellpi-logo img { width:64px; height:64px; object-fit:contain; }
    .mellpi-form-code { font-size:9pt; color:#444; }
    .mellpi-title { font-size:12.5pt; font-weight:800; letter-spacing:.03em; }
    .mellpi-meta-table, .mellpi-summary, .mellpi-table {
      width:100%;
      border-collapse:collapse;
      font-size:8.5pt;
      margin-bottom:8px;
      table-layout:fixed;
    }
    .mellpi-meta-table td, .mellpi-summary th, .mellpi-summary td,
    .mellpi-table th, .mellpi-table td {
      border:1px solid #222;
      padding:3px 5px;
      vertical-align:top;
      word-wrap:break-word;
      overflow-wrap:anywhere;
    }
    .mellpi-summary th, .mellpi-table th { background:#e8f5e9; text-align:center; }
    .mellpi-num { text-align:center; }
    .mellpi-grid {
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
      margin-bottom:8px;
    }
    .mellpi-section-title { font-size:9.5pt; font-weight:800; margin:8px 0 4px; }
    .mellpi-sub td { background:#f8fafc; font-style:italic; }

    .bnp-form-break { page-break-before: always; break-before: page; }
    .bnp-header-banner {
      display:grid;
      gap:8px;
      align-items:center;
      margin:0 0 8px;
      padding-bottom:6px;
      border-bottom:2px solid #166534;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .bnp-header-banner--city {
      grid-template-columns:minmax(90px,110px) minmax(0,1fr) minmax(90px,110px);
    }
    .bnp-header-banner--barangay {
      grid-template-columns:minmax(80px,120px) minmax(0,1fr) minmax(140px,200px);
    }
    .bnp-logo-side { display:flex; align-items:center; gap:6px; }
    .bnp-logo-side-left { justify-content:flex-start; }
    .bnp-logo-side-right { justify-content:flex-end; }
    .bnp-header-center { text-align:center; min-width:0; }
    .bnp-logo-cell { text-align:center; }
    .bnp-logo-circle {
      width:64px;
      height:64px;
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
    .bnp-logo-caption { margin-top:2px; font-size:7pt; font-weight:700; color:#14532d; }
    .bnp-form-code { font-size:8.5pt; color:#444; }
    .bnp-title { font-size:14pt; font-weight:800; text-align:center; letter-spacing:.03em; }
    .bnp-subtitle,.bnp-cy { text-align:center; font-weight:700; font-size:10pt; }
    .bnp-focus { text-align:center; font-weight:800; text-decoration:underline; margin:3px 0; font-size:11pt; }
    .bnp-mode-title { text-align:center; font-weight:800; letter-spacing:.05em; color:#b91c1c; margin:0 0 4px; font-size:11pt; }
    .bnp-bns { margin-bottom:8px; font-size:9.5pt; }
    .bnp-table { width:100%; border-collapse:collapse; font-size:9pt; margin-bottom:8px; table-layout:fixed; }
    .bnp-table th,.bnp-table td { border:1px solid #222; padding:2px 4px; word-wrap:break-word; overflow-wrap:anywhere; }
    .bnp-table th { background:#f0f0f0; text-align:center; }
    .bnp-table thead { display: table-header-group; }
    .bnp-num { text-align:center; width:15%; white-space:nowrap; }
    .bnp-section td { background:#e8f5e9; font-weight:700; }
    .bnp-sub td { background:#f8fafc; }
    .bnp-section-title { margin:6px 0 3px; font-size:9.5pt; }
    .bnp-occupation { margin:6px 0; font-size:9.5pt; }
    .bnp-sign { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:12px; font-size:8.5pt; page-break-inside:avoid; }
    .bnp-footnote { margin-top:6px; font-size:8pt; color:#444; }
    .bnp-pregnant-report .bnp-title { font-size:14pt; }
    .bnp-mode { text-align:center; margin:3px 0 6px; }
    .bnp-mode label { margin:0 8px; font-weight:700; }

    .eopt-cover {
      page-break-before: always;
      break-before: page;
      text-align:center;
      margin:0 0 14px;
      padding:14px 8px 12px;
      border-bottom:3px solid #1d4ed8;
    }
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
    /* Form 1B prints on A4 landscape (named page) */
    @page eopt-landscape {
      size: A4 landscape;
      margin: 10mm 8mm 10mm 8mm;
    }
    .eopt-landscape {
      page: eopt-landscape;
    }
    @media screen {
      .eopt-landscape .eopt-form1b {
        font-size: 6.5pt;
      }
      .eopt-landscape .eopt-form1b th,
      .eopt-landscape .eopt-form1b td {
        padding: 1px 2px;
      }
    }

    .city-sign-page {
      page-break-before: always;
      break-before: page;
      min-height: 220mm;
      display:flex;
      flex-direction:column;
      justify-content:center;
      padding:20mm 10mm;
    }
    .city-sign-page h2 {
      text-align:center;
      font-size:13pt;
      margin:0 0 28px;
      color:#14532d;
    }
    .city-sign-block {
      display:flex;
      justify-content:space-around;
      align-items:flex-end;
      gap:28px;
      page-break-inside:avoid;
      break-inside:avoid;
    }
    .city-sign-block .line {
      min-width:230px;
      text-align:center;
      font-size:10pt;
    }
    .city-sign-block hr {
      height:1.5px;
      background:#111;
      border:0;
      margin:56px auto 8px;
      max-width:250px;
    }
    .city-sign-note {
      text-align:center;
      margin-top:36px;
      font-size:9pt;
      font-weight:700;
      line-height:1.45;
    }

    /* Screen: stack dense layouts on narrower preview before scale kicks in */
    @media screen and (max-width: 900px) {
      .mellpi-grid { grid-template-columns: 1fr; }
      .bnp-header-banner--city,
      .bnp-header-banner--barangay {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
      }
      .bnp-logo-side-left,
      .bnp-logo-side-right { justify-content: center; }
      .bnp-sign { grid-template-columns: 1fr; }
      .city-sign-block {
        flex-direction: column;
        align-items: center;
        gap: 36px;
      }
      .city-sign-block .line { min-width: 0; width: 100%; max-width: 280px; }
      .report-cover { min-height: auto; padding: 24px 8px; }
      .city-sign-page { min-height: auto; padding: 28px 10px; }
    }

    @media print {
      @page {
        size: A4 portrait;
        margin: 12mm 10mm 14mm 10mm;
      }
      @page eopt-landscape {
        size: A4 landscape;
        margin: 10mm 8mm 10mm 8mm;
      }
      html, body {
        width: auto;
        max-width: none;
        background: #fff !important;
        overflow: visible !important;
      }
      body {
        margin: 0;
        padding: 0;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      #cityReportPrintRoot {
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
      .no-print { display:none !important; }
      .mellpi-grid { grid-template-columns:1fr 1fr; }
      .bnp-header-banner--city {
        grid-template-columns:minmax(90px,110px) minmax(0,1fr) minmax(90px,110px);
      }
      .bnp-header-banner--barangay {
        grid-template-columns:minmax(80px,120px) minmax(0,1fr) minmax(140px,200px);
      }
      .bnp-sign { grid-template-columns: repeat(3, 1fr); }
      .city-sign-block {
        flex-direction: row;
        align-items: flex-end;
      }
      .report-cover { min-height: 240mm; }
      .city-sign-page { min-height: 220mm; }
      thead { display: table-header-group; }
      tr, img { page-break-inside: avoid; }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body<?= $autoPrint && !$autoDownload ? ' onload="window.print()"' : '' ?>>
  <div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">Print</button>
    <button type="button" class="btn-pdf" id="cityReportDownloadPdfBtn">Download PDF</button>
    <span id="cityReportPdfStatus">Tip: Use Print for Form 1B landscape pages. Download PDF may take 1–2 minutes.</span>
  </div>

  <div id="cityReportPrintRoot" data-report-fit="a4">

  <!-- 1. COVER -->
  <section class="report-cover" data-pdf-section="1">
    <div class="report-cover-logos">
      <img src="<?= barangay_h($nncLogoUrl) ?>" alt="National Nutrition Council">
      <img src="<?= barangay_h($cityLogoUrl) ?>" alt="City of Valencia">
    </div>
    <p class="eyebrow">City of Valencia · Bukidnon</p>
    <h1>BARANGAY NUTRITION PROFILE<br>+ e-OPT PLUS</h1>
    <p class="subtitle">City-Wide Consolidated Report</p>
    <p class="meta-line">All Barangays · Calendar Year <?= (int) $calendarYear ?></p>
    <p class="meta-line">Generated: <?= barangay_h($generatedAt) ?></p>

    <div class="report-toc">
      <h2>Report Contents</h2>
      <ol>
        <li>MELLPI PRO Form CM — City/Municipality Profile</li>
        <li>Barangay Nutrition Profile (BNP) Forms
          <?php if ($bnpSheetLabels !== []) : ?>
          <br><span style="font-size:9pt;color:#444;"><?= barangay_h(implode(' · ', $bnpSheetLabels)) ?></span>
          <?php else : ?>
          <br><span style="font-size:9pt;color:#444;">C1–C9 consolidated sheets</span>
          <?php endif; ?>
        </li>
        <li>e-OPT Plus Community Level Tool
          <br><span style="font-size:9pt;color:#444;">Nut_StatusTool · Summary · BNS Printout · Forms 1A/1B · At-risk lists</span>
        </li>
        <li>Certification / Signatories</li>
      </ol>
    </div>
  </section>

  <!-- 2. MELLPI -->
  <section class="report-section" data-pdf-section="1">
    <div class="section-banner">
      <h2>Part I — MELLPI PRO Form CM</h2>
      <p>City / Municipality Profile Sheet · Valencia City</p>
    </div>
    <?php require __DIR__ . '/../includes/partials/nutrition_mellpi_city_profile.php'; ?>
  </section>

  <!-- 3. BNP FORMS -->
  <?php foreach ($cityBnpReports as $index => $pack) :
      $meta = $pack['meta'];
      $typeKey = (string) $pack['key'];
      $bnpReport = $pack['report'];
      $pregnantReport = $bnpReport;
      $layout = (string) ($meta['layout'] ?? '');
      $sheetLabel = trim((string) (($meta['sheet'] ?? '') ?: ($meta['title'] ?? strtoupper($typeKey))));
      ?>
  <section class="bnp-form-break report-section" data-pdf-section="1">
    <div class="section-banner">
      <h2>Part II — Barangay Nutrition Profile</h2>
      <p><?= barangay_h($sheetLabel) ?> · City-Wide Consolidated · CY <?= (int) $calendarYear ?></p>
    </div>
    <?php
    if ($layout === 'all_hh') {
        require __DIR__ . '/../includes/partials/nutrition_bnp_all_hh.php';
    } elseif ($layout === 'pregnant') {
        require __DIR__ . '/../includes/partials/nutrition_pregnant_families_report.php';
    } else {
        require __DIR__ . '/../includes/partials/nutrition_bnp_family_profile.php';
    }
    ?>
  </section>
  <?php endforeach; ?>

  <!-- 4. e-OPT -->
  <section class="eopt-cover" data-pdf-section="1">
    <h1>Part III — e-OPT Plus Community Level Tool</h1>
    <p><strong>Region 10 · City-Wide Consolidated Printout</strong></p>
    <p>Nut_StatusTool · Form 1A · Form 1B · Form 1C · NutStatusBrgy · DQC · Graphs · Monitoring Lists</p>
    <p>Measured preschoolers (0–59 mos): <strong><?= number_format((int) ($eoptReport['totals']['measured'] ?? 0)) ?></strong></p>
  </section>

  <div data-pdf-section="1">
  <?php require __DIR__ . '/../includes/partials/nutrition_eopt_print_bundle.php'; ?>
  </div>

  <!-- 5. SIGNATORIES -->
  <section class="city-sign-page" data-pdf-section="1">
    <h2>Part IV — Certification</h2>
    <div class="city-sign-block">
      <div class="line">
        <hr>
        <div>
          <strong><?= barangay_h($cityNutritionHeadName) ?></strong><br>
          <?= barangay_h($cityNutritionHeadTitle) ?><br>
          City Nutrition Council · City of Valencia
        </div>
      </div>
      <div class="line">
        <hr>
        <div>
          <strong><?= barangay_h($cityMayorName) ?></strong><br>
          Noted by<br>
          <?= barangay_h($cityMayorTitle) ?>
        </div>
      </div>
    </div>
    <p class="city-sign-note">
      VALID WITH SIGNATURE OF THE CITY NUTRITION HEAD AND CITY MAYOR / CNC CHAIRPERSON.<br>
      Valencia City Nutrition Profiling System — BNP + e-OPT Plus Consolidated Report · CY <?= (int) $calendarYear ?><br>
      Note: Not Valid Without Official Dry Seal Where Required
    </p>
  </section>

  </div>

  <script src="../assets/plugins/jsPDF/html2canvas.min.js"></script>
  <script src="../assets/plugins/jsPDF/jspdf.umd.min.js"></script>
  <script src="../assets/js/nutrition-print-pdf.js?v=20260730a"></script>
  <script>
  (function () {
    var filename = <?= json_encode($pdfFilename) ?>;
    var autoDownload = <?= $autoDownload ? 'true' : 'false' ?>;
    var btn = document.getElementById('cityReportDownloadPdfBtn');
    var statusEl = document.getElementById('cityReportPdfStatus');
    var root = document.getElementById('cityReportPrintRoot');

    function setStatus(text) {
      if (statusEl) statusEl.textContent = text || '';
    }

    function downloadPdf() {
      if (typeof window.nutritionDownloadPrintPdf !== 'function') {
        setStatus('PDF helper failed to load. Use Print → Save as PDF instead.');
        return;
      }
      var run = function () {
        return window.nutritionDownloadPrintPdf({
          root: root,
          filename: filename,
          button: btn,
          setStatus: setStatus,
          scale: 1
        });
      };
      if (window.barangayReportFit && typeof window.barangayReportFit.withPrintLayout === 'function') {
        window.barangayReportFit.withPrintLayout(run);
      } else {
        run();
      }
    }

    if (btn) {
      btn.addEventListener('click', downloadPdf);
    }
    if (autoDownload) {
      window.addEventListener('load', function () {
        setTimeout(downloadPdf, 800);
      });
    }
  })();
  </script>
</body>
</html>
