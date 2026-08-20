<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$filters = [
    'purok' => trim((string) ($_GET['purok'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];

$report = nutrition_household_consolidated_report($con, (string) $barangay_id, $filters);
$summary = $report['summary'] ?? [];
$householdRows = $report['households'] ?? [];
$reportHeader = trim((string) ($nutritionSettings['report_header'] ?? ('Barangay ' . $barangay . ' Nutrition Profiling')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consolidated Household Report | <?= barangay_h($barangay) ?></title>
  <style>
    @import url('../assets/css/local-fonts.css');
    body { font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111; margin: 24px; }
    h1, h2, h3 { margin: 0 0 8px; }
    .meta { color: #555; margin-bottom: 20px; }
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
    .stat { border: 1px solid #ddd; border-radius: 8px; padding: 10px; text-align: center; }
    .stat strong { display: block; font-size: 1.4rem; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; vertical-align: top; }
    th { background: #f3f4f6; text-align: left; }
    .household-block { page-break-inside: avoid; margin-bottom: 16px; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; background: #e5e7eb; }
    @media print { body { margin: 12px; } }
    @media (max-width: 768px) {
      .stats { grid-template-columns: 1fr 1fr; }
      table { font-size: 11px; }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body onload="window.print()">
  <div id="householdSurveyPrintRoot" data-report-fit="root">
  <h1><?= barangay_h($reportHeader) ?></h1>
  <div class="meta">
    <div><strong>Consolidated Household Nutrition Report</strong></div>
    <div><?= barangay_h($barangay . ', ' . $district) ?></div>
    <div>Generated <?= date('F j, Y g:i A') ?></div>
    <?php if (($nutritionSettings['nutrition_officer'] ?? '') !== '') : ?>
    <div>Nutrition Officer: <?= barangay_h($nutritionSettings['nutrition_officer']) ?></div>
    <?php endif; ?>
    <?php if ($filters['purok'] !== '' || $filters['date_from'] !== '' || $filters['date_to'] !== '') : ?>
    <div>
      Filters:
      <?php if ($filters['purok'] !== '') : ?>Purok <?= barangay_h($filters['purok']) ?><?php endif; ?>
      <?php if ($filters['date_from'] !== '') : ?> · From <?= barangay_h($filters['date_from']) ?><?php endif; ?>
      <?php if ($filters['date_to'] !== '') : ?> · To <?= barangay_h($filters['date_to']) ?><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="stats">
    <div class="stat"><strong><?= number_format((int) ($summary['households'] ?? 0)) ?></strong>Households</div>
    <div class="stat"><strong><?= number_format((int) ($summary['family_members'] ?? 0)) ?></strong>Family Members</div>
    <div class="stat"><strong><?= number_format((int) ($summary['malnourished'] ?? 0)) ?></strong>Malnourished</div>
    <div class="stat"><strong><?= number_format((int) ($summary['at_risk_members'] ?? 0)) ?></strong>At-Risk Members</div>
    <div class="stat"><strong><?= number_format((int) ($summary['pregnant'] ?? 0)) ?></strong>Pregnant</div>
    <div class="stat"><strong><?= number_format((int) ($summary['lactating'] ?? 0)) ?></strong>Lactating</div>
    <div class="stat"><strong><?= number_format((int) ($summary['four_ps'] ?? 0)) ?></strong>4Ps Households</div>
    <div class="stat"><strong><?= number_format((int) ($summary['solo_parent'] ?? 0)) ?></strong>Solo Parent Households</div>
  </div>

  <?php if ($householdRows === []) : ?>
  <p>No household surveys recorded.</p>
  <?php else : ?>
  <?php foreach ($householdRows as $row) :
      $survey = $row['survey'];
      $members = $row['members'];
      $headDisplay = nutrition_household_head_display($survey);
      $badges = nutrition_household_member_badges($survey);
      ?>
  <div class="household-block">
    <h3><?= barangay_h((string) ($survey['house_hold_id'] ?? '')) ?> · <?= barangay_h($headDisplay) ?></h3>
    <div class="meta">
      Survey Date: <?= barangay_h(date('M j, Y', strtotime((string) $survey['survey_date']))) ?> ·
      Purok: <?= barangay_h((string) ($survey['purok_label'] ?? '')) ?> ·
      Gender: <?= barangay_h((string) ($survey['gender'] ?? '')) ?> ·
      Occupation: <?= barangay_h((string) ($survey['occupation'] ?? '')) ?>
      <?php if ($badges !== []) : ?> · <?= barangay_h(implode(', ', $badges)) ?><?php endif; ?>
    </div>
    <?php if ($members === []) : ?>
    <p><em>No family members recorded.</em></p>
    <?php else : ?>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Relationship</th>
          <th>Gender</th>
          <th>Birthday</th>
          <th>Status</th>
          <th>Weight</th>
          <th>Height</th>
          <th>WFA</th>
          <th>HFA</th>
          <th>WFH</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $member) :
            $memberBadges = [];
            if (strtoupper((string) ($member['is_pregnant'] ?? 'NO')) === 'YES') {
                $memberBadges[] = 'Pregnant';
            }
            if (strtoupper((string) ($member['is_lactating'] ?? 'NO')) === 'YES') {
                $memberBadges[] = 'Lactating';
            }
            ?>
        <tr>
          <td><?= barangay_h((string) ($member['member_name'] ?? '')) ?></td>
          <td><?= barangay_h((string) ($member['relationship'] ?? '')) ?></td>
          <td><?= barangay_h((string) ($member['gender'] ?? '')) ?></td>
          <td><?= ($member['birth_date'] ?? '') !== '' ? barangay_h(date('M j, Y', strtotime((string) $member['birth_date']))) : '—' ?></td>
          <td><?= $memberBadges !== [] ? barangay_h(implode(', ', $memberBadges)) : '—' ?></td>
          <td><?= ($member['weight_kg'] ?? '') !== '' && $member['weight_kg'] !== null ? barangay_h((string) $member['weight_kg']) . ' kg' : '—' ?></td>
          <td><?= ($member['height_cm'] ?? '') !== '' && $member['height_cm'] !== null ? barangay_h((string) $member['height_cm']) . ' cm' : '—' ?></td>
          <td><?= barangay_h((string) ($member['weight_for_age'] ?? '—')) ?></td>
          <td><?= barangay_h((string) ($member['height_for_age'] ?? '—')) ?></td>
          <td><?= barangay_h((string) ($member['weight_for_height'] ?? '—')) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
  </div>
</body>
</html>
