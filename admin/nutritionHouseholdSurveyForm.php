<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$prefillPurok = trim((string) ($_GET['purok'] ?? ''));
$memberRows = (int) ($_GET['members'] ?? 6);
$download = (string) ($_GET['download'] ?? '') === '1';
$excel = (string) ($_GET['excel'] ?? '') === '1';
$autoPdf = (string) ($_GET['pdf'] ?? '') === '1';

if ($excel) {
    $excelQuery = http_build_query(array_filter([
        'purok' => $prefillPurok,
        'members' => $memberRows > 0 ? $memberRows : null,
        'layout' => 'form',
    ]));
    header('Location: nutritionHouseholdSurveyFormExcel.php' . ($excelQuery !== '' ? '?' . $excelQuery : ''));
    exit;
}

$psgcCode = nutrition_barangay_psgc_code($con, (string) $barangay_id, (string) $barangay);
$reportHeader = trim((string) ($nutritionSettings['report_header'] ?? ('Barangay ' . $barangay . ' Nutrition Profiling')));
$relationshipOptions = nutrition_relationship_options();
$certHeader = barangay_certificate_header(['barangay' => $barangay]);

$safeBarangay = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $barangay) ?: 'barangay';
$filename = 'Household-Nutrition-Survey-' . $safeBarangay . '.html';
$safePurok = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $prefillPurok);
$pdfFilename = 'Household-Nutrition-Survey-Form-' . $safeBarangay . ($safePurok !== '' ? '-Purok-' . $safePurok : '') . '.pdf';

if ($download) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Household Nutrition Survey Form | <?= barangay_h($barangay) ?></title>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body<?= ($download || $autoPdf) ? '' : ' onload="window.print()"' ?>>
  <div id="householdFormPrintRoot" data-report-fit="root">
  <?php
  $showActions = !$download;
  $nutritionOfficer = (string) ($nutritionSettings['nutrition_officer'] ?? '');
  require __DIR__ . '/../includes/partials/nutrition_household_survey_form_print.php';
  ?>
  </div>
  <?php if (!$download) : ?>
  <script src="../assets/plugins/jsPDF/html2canvas.min.js"></script>
  <script src="../assets/plugins/jsPDF/jspdf.umd.min.js"></script>
  <script src="../assets/js/nutrition-print-pdf.js?v=20260730a"></script>
  <script>
  (function () {
    var filename = <?= json_encode($pdfFilename) ?>;
    var btn = document.getElementById('householdFormPdfBtn');
    var statusEl = document.getElementById('householdFormPdfStatus');

    function setStatus(text) {
      if (statusEl) statusEl.textContent = text || '';
    }

    function downloadPdf() {
      if (typeof window.nutritionDownloadPrintPdf !== 'function') {
        setStatus('PDF helper failed to load. Use Print → Save as PDF instead.');
        return;
      }
      window.nutritionDownloadPrintPdf({
        root: document.getElementById('householdFormPrintRoot'),
        filename: filename,
        button: btn,
        setStatus: setStatus,
        scale: 2
      });
    }

    if (btn) {
      btn.addEventListener('click', downloadPdf);
    }
    <?php if ($autoPdf) : ?>
    window.addEventListener('load', function () {
      setTimeout(downloadPdf, 400);
    });
    <?php endif; ?>
  })();
  </script>
  <?php endif; ?>
</body>
</html>
