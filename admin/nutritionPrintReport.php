<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$snapshot = nutrition_report_snapshot($con, (string) $barangay_id, (string) $barangay);
$totals = $snapshot['totals'];
$atRisk = $totals['underweight'] + $totals['wasted'] + $totals['severely_wasted'] + $totals['stunted'] + $totals['overweight'] + $totals['obese'];
$reportHeader = $nutritionSettings['report_header'] ?? ('Barangay ' . $barangay . ' Nutrition Profiling');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Nutrition Report | <?= barangay_h($barangay) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,600;0,700;0,800&display=swap');
    body { font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111; margin: 24px; }
    h1, h2 { margin: 0 0 8px; }
    .meta { color: #555; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 14px; }
    th { background: #e8f5e9; }
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
    .stat { border: 1px solid #ccc; padding: 12px; text-align: center; }
    .stat strong { display: block; font-size: 24px; }
    @media print { .no-print { display: none; } }
    @media (max-width: 768px) {
      .stats { grid-template-columns: 1fr 1fr; }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body onload="window.print()">
  <button class="no-print" onclick="window.print()">Print</button>
  <div id="nutritionPrintRoot" data-report-fit="root">
  <h1><?= barangay_h($reportHeader) ?></h1>
  <div class="meta">
    <div><?= barangay_h($barangay . ', ' . $district) ?></div>
    <div>Generated: <?= barangay_h(date('F j, Y g:i A')) ?></div>
    <?php if (($nutritionSettings['nutrition_officer'] ?? '') !== '') : ?>
    <div>Nutrition Officer: <?= barangay_h($nutritionSettings['nutrition_officer']) ?></div>
    <?php endif; ?>
  </div>

  <div class="stats">
    <div class="stat"><strong><?= number_format($totals['children']) ?></strong><?= barangay_h(nutrition_children_age_label()) ?></div>
    <div class="stat"><strong><?= number_format($totals['assessed']) ?></strong>Assessed</div>
    <div class="stat"><strong><?= number_format($totals['pending']) ?></strong>Pending</div>
    <div class="stat"><strong><?= number_format($atRisk) ?></strong>At-Risk</div>
  </div>

  <h2>Nutritional Status Summary</h2>
  <table>
    <thead><tr><th>Status</th><th>Count</th></tr></thead>
    <tbody>
      <?php foreach (nutrition_status_options() as $key => $label) : ?>
      <tr><td><?= barangay_h($label) ?></td><td><?= number_format((int) ($totals[$key] ?? 0)) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2>Household Nutrition Surveys</h2>
  <table>
    <thead><tr><th>Date</th><th>Household ID</th><th>Household Head</th><th>Purok</th><th>Gender</th><th>Occupation</th></tr></thead>
    <tbody>
      <?php if ($snapshot['household_surveys'] === []) : ?>
      <tr><td colspan="6">No records.</td></tr>
      <?php else : ?>
      <?php foreach ($snapshot['household_surveys'] as $row) : ?>
      <tr>
        <td><?= barangay_h(date('M j, Y', strtotime((string) $row['survey_date']))) ?></td>
        <td><?= barangay_h((string) ($row['house_hold_id'] ?? '')) ?></td>
        <td><?= barangay_h(nutrition_household_head_display($row)) ?></td>
        <td><?= barangay_h((string) ($row['purok_label'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['gender'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['occupation'] ?? '')) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <h2>Barangay Nutrition Surveys</h2>
  <table>
    <thead><tr><th>Period</th><th>Screened</th><th>Malnourished</th><th>At-Risk</th></tr></thead>
    <tbody>
      <?php if ($snapshot['barangay_surveys'] === []) : ?>
      <tr><td colspan="4">No records.</td></tr>
      <?php else : ?>
      <?php foreach ($snapshot['barangay_surveys'] as $row) : ?>
      <tr>
        <td><?= barangay_h((string) $row['survey_period']) ?></td>
        <td><?= number_format((int) ($row['children_screened'] ?? 0)) ?></td>
        <td><?= number_format((int) ($row['malnourished_cases'] ?? 0)) ?></td>
        <td><?= number_format((int) ($row['at_risk_cases'] ?? 0)) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</body>
</html>
