<?php

/**
 * MELLPI PRO FORM CM — City/Municipality Profile Sheet (print/view).
 * Expected: $mellpiReport from nutrition_mellpi_build_report()
 */
$m = $mellpiReport ?? [];
$meta = $m['meta'] ?? [];
$summary = $m['summary'] ?? [];
$community = $m['community'] ?? [];
$popSnap = $m['population_snapshot'] ?? [];
$years = $m['years'] ?? [(int) date('Y') - 2, (int) date('Y') - 1, (int) date('Y')];
$preschool = $m['preschool'] ?? [];
$school = $m['school'] ?? [];
$pregnantStatus = $m['pregnant_status'] ?? [];
$bns = $m['bns'] ?? [];
$hazards = $m['hazards'] ?? [];
$landUse = $m['land_use'] ?? [];
$assetPrefix = $assetPrefix ?? '../';
$nncLogo = $assetPrefix . 'assets/logo/national-nutrition-council.png';

$val = static function ($v): string {
    $s = trim((string) $v);
    return $s === '' ? '' : barangay_h($s);
};
$num = static function ($v): string {
    $s = trim((string) $v);
    if ($s === '') {
        return '';
    }
    if (is_numeric($s)) {
        return number_format((float) $s);
    }

    return barangay_h($s);
};

$yearCols = static function (array $row) use ($years, $num): void {
    foreach ($years as $y) {
        echo '<td class="mellpi-num">' . $num($row[$y] ?? '') . '</td>';
    }
};
?>
<div class="mellpi-form">
  <div class="mellpi-header">
    <div class="mellpi-logo">
      <img src="<?= barangay_h($nncLogo) ?>" alt="National Nutrition Council">
    </div>
    <div class="mellpi-title-block">
      <div class="mellpi-form-code"><?= barangay_h((string) ($meta['form'] ?? 'MELLPI PRO FORM CM')) ?></div>
      <div class="mellpi-title"><?= barangay_h((string) ($meta['title'] ?? 'CITY/MUNICIPALITY PROFILE SHEET')) ?></div>
    </div>
  </div>

  <table class="mellpi-meta-table">
    <tr>
      <td>City/Municipality: <strong><?= $val($meta['city_name'] ?? '') ?></strong></td>
      <td>Province: <strong><?= $val($meta['province'] ?? '') ?></strong></td>
      <td>Income Class: <strong><?= $val($meta['income_class'] ?? '') ?></strong></td>
    </tr>
    <tr>
      <td>Date of Monitoring: <strong><?= $val($meta['date_of_monitoring'] ?? '') ?></strong></td>
      <td colspan="2">Period Covered: <strong><?= $val($meta['period_covered'] ?? '') ?></strong></td>
    </tr>
  </table>

  <table class="mellpi-summary">
    <tr>
      <th>TOTAL POPULATION</th>
      <th>NO. OF HOUSEHOLDS</th>
      <th>NO. OF BARANGAYS</th>
    </tr>
    <tr>
      <td class="mellpi-num"><?= number_format((int) ($summary['total_population'] ?? 0)) ?></td>
      <td class="mellpi-num"><?= number_format((int) ($summary['no_of_households'] ?? 0)) ?></td>
      <td class="mellpi-num"><?= number_format((int) ($summary['no_of_barangays'] ?? 0)) ?></td>
    </tr>
  </table>

  <div class="mellpi-grid">
    <div class="mellpi-col">
      <table class="mellpi-table">
        <thead><tr><th colspan="2">Community Profile</th></tr></thead>
        <tbody>
          <?php
          $leftFields = [
              'income_classification' => 'Income Classification',
              'hh_safe_water' => 'No. of households with access to safe water',
              'hh_sanitary_toilets' => 'No. of households with sanitary toilets',
              'day_care_centers' => 'No. of Day Care Centers',
              'public_elementary_schools' => 'No. of public elementary schools',
              'public_secondary_schools' => 'No. of public secondary schools',
              'barangay_health_stations' => 'No. of Barangay Health Stations',
              'retail_outlets' => 'No. of retail outlets/sari-sari stores',
              'bakeries' => 'No. of bakeries',
              'public_markets' => 'No. of public markets',
              'transport_terminals' => 'No. of transport terminals',
              'pct_at_risk_pregnant' => 'Percent of nutritionally at-risk pregnant women',
              'pct_exclusive_bf_5th_month' => 'Percent of lactating mothers exclusively breastfeeding until the 5th month',
              'idd_pregnant' => 'IDD Prevalence (Pregnant)',
              'idd_lactating' => 'IDD Prevalence (Lactating)',
              'terrain' => 'Terrain',
          ];
          foreach ($leftFields as $key => $label) :
              ?>
          <tr>
            <td><?= barangay_h($label) ?></td>
            <td class="mellpi-num"><?= $num($community[$key] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mellpi-col">
      <table class="mellpi-table">
        <thead>
          <tr><th colspan="3"><?= (int) ($meta['calendar_year'] ?? date('Y')) ?> Population</th></tr>
          <tr><th></th><th>Estimated</th><th>Actual</th></tr>
        </thead>
        <tbody>
          <tr>
            <td>0–59 mos</td>
            <td class="mellpi-num"><?= $num($popSnap['0_59_estimated'] ?? '') ?></td>
            <td class="mellpi-num"><?= $num($popSnap['0_59_actual'] ?? '') ?></td>
          </tr>
          <tr>
            <td>Pregnant</td>
            <td class="mellpi-num"><?= $num($popSnap['pregnant_estimated'] ?? '') ?></td>
            <td class="mellpi-num"><?= $num($popSnap['pregnant_actual'] ?? '') ?></td>
          </tr>
          <tr>
            <td>Lactating</td>
            <td class="mellpi-num"><?= $num($popSnap['lactating_estimated'] ?? '') ?></td>
            <td class="mellpi-num"><?= $num($popSnap['lactating_actual'] ?? '') ?></td>
          </tr>
        </tbody>
      </table>

      <div class="mellpi-section-title">Nutritional Status of Preschool Children (0–59 months)</div>
      <table class="mellpi-table">
        <thead>
          <tr>
            <th>Indicator</th>
            <?php foreach ($years as $y) : ?><th><?= (int) $y ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr class="mellpi-sub"><td colspan="<?= 1 + count($years) ?>"><em>Weight-for-Age</em></td></tr>
          <?php foreach (['Normal', 'Underweight', 'Severely Underweight', 'Overweight'] as $lab) : ?>
          <tr><td><?= barangay_h($lab) ?></td><?php $yearCols($preschool['wfa'][$lab] ?? []); ?></tr>
          <?php endforeach; ?>
          <tr class="mellpi-sub"><td colspan="<?= 1 + count($years) ?>"><em>Weight-for-Height/Length</em></td></tr>
          <?php foreach (['Normal', 'Wasted', 'Severely Wasted', 'Overweight', 'Obese'] as $lab) : ?>
          <tr><td><?= barangay_h($lab) ?></td><?php $yearCols($preschool['wfh'][$lab] ?? []); ?></tr>
          <?php endforeach; ?>
          <tr class="mellpi-sub"><td colspan="<?= 1 + count($years) ?>"><em>Height-for-Age</em></td></tr>
          <?php foreach (['Normal', 'Stunted', 'Severely Stunted', 'Tall'] as $lab) : ?>
          <tr><td><?= barangay_h($lab) ?></td><?php $yearCols($preschool['hfa'][$lab] ?? []); ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="mellpi-section-title">Nutritional Status of School Children</div>
      <table class="mellpi-table">
        <thead>
          <tr>
            <th>Indicator</th>
            <?php foreach ($years as $y) : ?><th><?= (int) $y ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach (['Normal', 'Wasted', 'Severely Wasted', 'Overweight', 'Obese'] as $lab) : ?>
          <tr><td><?= barangay_h($lab) ?></td><?php $yearCols($school[$lab] ?? []); ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="mellpi-section-title">Nutritional Status of Pregnant Women</div>
      <table class="mellpi-table">
        <thead>
          <tr>
            <th>Indicator</th>
            <?php foreach ($years as $y) : ?><th><?= (int) $y ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach (['Normal', 'Nutritionally at-risk', 'Overweight', 'Obese'] as $lab) : ?>
          <tr><td><?= barangay_h($lab) ?></td><?php $yearCols($pregnantStatus[$lab] ?? []); ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <table class="mellpi-table" style="margin-top:8px;">
        <thead><tr><th colspan="2">Barangay Nutrition Scholars (BNS)</th></tr></thead>
        <tbody>
          <tr><td>Total No. of Barangay Nutrition Scholars</td><td class="mellpi-num"><?= $num($bns['total'] ?? '') ?></td></tr>
          <tr><td>New</td><td class="mellpi-num"><?= $num($bns['new'] ?? '') ?></td></tr>
          <tr><td>Existing</td><td class="mellpi-num"><?= $num($bns['existing'] ?? '') ?></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mellpi-section-title">Hazards</div>
  <table class="mellpi-table">
    <thead>
      <tr><th style="width:50%;">Hazards (Type/Month)</th><th>LGU / Households affected</th></tr>
    </thead>
    <tbody>
      <?php foreach ($hazards as $row) : ?>
      <tr>
        <td><?= $val($row['type_month'] ?? '') ?></td>
        <td><?= $val($row['affected'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="mellpi-section-title">Land Use Classification</div>
  <table class="mellpi-table">
    <thead>
      <tr>
        <th>Classification</th>
        <th>Land Area</th>
        <th>Bgy Covered</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($landUse as $label => $row) : ?>
      <tr>
        <td><?= barangay_h((string) $label) ?></td>
        <td class="mellpi-num"><?= $val($row['land_area'] ?? '') ?></td>
        <td class="mellpi-num"><?= $val($row['bgy_covered'] ?? '') ?></td>
        <td><?= $val($row['remarks'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
