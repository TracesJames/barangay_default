<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_bnp_reports.php';

nutrition_ensure_module_tables($con);

$user_id = (string) $_SESSION['user_id'];
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);

if (!$isSuperAdmin && !$isBnsAdmin && !$isCityAdmin && !$isNutritionPortalAdmin) {
    header('Location: nutritionDashboard.php');
    exit;
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

$pregnantReport = nutrition_city_pregnant_families_report($con, $filters);
$barangayName = 'All Barangays';
$reportMode = nutrition_bnp_normalize_mode($_GET['mode'] ?? 'consolidated');
$modeSelectable = true;
$isCityWide = true;
$nutritionSettings = ['nutrition_officer' => ''];
$barangayRows = $pregnantReport['barangay_rows'] ?? [];
usort($barangayRows, static function (array $a, array $b): int {
    return strcasecmp((string) ($a['barangay'] ?? ''), (string) ($b['barangay'] ?? ''));
});
$cityNutritionHeadName = 'Hazel Dondonayos, RND';
$cityNutritionHeadTitle = 'City Nutrition Head';
$cityMayorName = 'Hon. Amie G. Galario';
$cityMayorTitle = 'City Mayor / CNC Chairperson';
$bnsName = '';
$bnpModeSwitchQuery = [
    'year' => $calendarYear,
    'purok' => $filters['purok'],
    'date_from' => $filters['date_from'],
    'date_to' => $filters['date_to'],
];

$summaryFamilies = 0;
$summaryTotals = nutrition_pregnant_zero_columns();
foreach ($barangayRows as $row) {
    $summaryFamilies += (int) ($row['family_count'] ?? 0);
    foreach (($row['pregnant_totals'] ?? []) as $col => $count) {
        $summaryTotals[$col] = (int) ($summaryTotals[$col] ?? 0) + (int) $count;
    }
}

$modeQuery = $_GET;
$modeQuery['mode'] = 'consolidated';
$consolidatedHref = 'nutritionSuperPregnantFamiliesPrint.php?' . http_build_query($modeQuery);
$modeQuery['mode'] = 'individual';
$individualHref = 'nutritionSuperPregnantFamiliesPrint.php?' . http_build_query($modeQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>City Families with Pregnant Report | Valencia City</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,600;0,700;0,800&display=swap');
    body { font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111; margin: 18px; }
    .no-print { margin-bottom: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .no-print .btn {
      padding: 8px 14px;
      border: 0;
      border-radius: 6px;
      cursor: pointer;
      font: inherit;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .no-print .btn-print { background: #16a34a; color: #fff; }
    .no-print .btn-mode { background: #e5e7eb; color: #111; }
    .no-print .btn-mode.is-active { background: #166534; color: #fff; }
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
    .bnp-mode-title { font-size: 11pt; font-weight: 800; margin-bottom: 4px; }
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
    .bnp-brgy-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 18px; }
    .bnp-brgy-table th, .bnp-brgy-table td { border: 1px solid #333; padding: 4px 6px; }
    .bnp-brgy-table th { background: #eef2ff; }
    .bnp-brgy-table tfoot td { font-weight: 700; background: #f8fafc; }
    .bnp-brgy-heading { margin: 12px 0 8px; font-size: 14pt; }
    @media print {
      body { margin: 8px; }
      .no-print { display: none !important; }
      .bnp-brgy-break { page-break-before: always; }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body>
  <div class="no-print">
    <button type="button" class="btn btn-print" onclick="window.print()">Print / Save as PDF</button>
    <a class="btn btn-mode<?= $reportMode === 'consolidated' ? ' is-active' : '' ?>" href="<?= barangay_h($consolidatedHref) ?>">Consolidated</a>
    <a class="btn btn-mode<?= $reportMode === 'individual' ? ' is-active' : '' ?>" href="<?= barangay_h($individualHref) ?>">Individual</a>
    <span style="font-size:0.9rem;opacity:0.75;">
      <?= number_format((int) ($pregnantReport['family_count'] ?? 0)) ?> families · CY <?= (int) $calendarYear ?>
    </span>
  </div>

  <div id="cityPregnantPrintRoot" data-report-fit="root">
  <?php require __DIR__ . '/../includes/partials/nutrition_pregnant_families_report.php'; ?>

  <?php if ($reportMode === 'consolidated') : ?>
  <div class="bnp-brgy-break"></div>
  <h2 class="bnp-brgy-heading">Per-Barangay Summary (<?= count($barangayRows) ?> barangays)</h2>
  <table class="bnp-brgy-table">
    <thead>
      <tr>
        <th>Barangay</th>
        <th>Families</th>
        <th>A Normal</th>
        <th>B Teenage</th>
        <th>C Underweight</th>
        <th>D Overweight</th>
        <th>E Others</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($barangayRows === []) : ?>
      <tr><td colspan="7">No barangay data.</td></tr>
      <?php else : ?>
        <?php foreach ($barangayRows as $row) :
            $totals = $row['pregnant_totals'] ?? nutrition_pregnant_zero_columns();
            ?>
      <tr>
        <td><?= barangay_h((string) ($row['barangay'] ?? '')) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($row['family_count'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($totals['A'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($totals['B'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($totals['C'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($totals['D'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($totals['E'] ?? 0)) ?></td>
      </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <?php if ($barangayRows !== []) : ?>
    <tfoot>
      <tr>
        <td>City Total</td>
        <td style="text-align:center;"><?= number_format($summaryFamilies) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($summaryTotals['A'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($summaryTotals['B'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($summaryTotals['C'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($summaryTotals['D'] ?? 0)) ?></td>
        <td style="text-align:center;"><?= number_format((int) ($summaryTotals['E'] ?? 0)) ?></td>
      </tr>
    </tfoot>
    <?php endif; ?>
  </table>
  <?php endif; ?>
  </div>
</body>
</html>
