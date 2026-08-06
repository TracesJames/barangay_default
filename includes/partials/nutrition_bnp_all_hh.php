<?php

/**
 * BNP Form C1 — ALL HOUSEHOLDS (official layout, Region/City of Valencia)
 * Expected: $bnpReport from nutrition_bnp_all_hh_report()
 */
$ind = $bnpReport['indicators'] ?? [];
$prf = $bnpReport['prf'] ?? nutrition_bnp_empty_prf_counts();
$formC1 = $bnpReport['form_c1'] ?? nutrition_bnp_form_c1_defaults();
$meta = $bnpReport['meta'] ?? nutrition_bnp_resolve_type('all_hh');
$barangayName = $barangayName ?? ($barangay ?? '');
$calendarYear = (int) ($calendarYear ?? ($bnpReport['calendar_year'] ?? date('Y')));
$bnsName = trim((string) ($bnpReport['bns_name'] ?? ''));
$pct = static function (int $part, int $whole): string {
    if ($whole <= 0) {
        return '0.00%';
    }

    return number_format(($part / $whole) * 100, 2) . '%';
};
$blank = static function ($v): string {
    $v = trim((string) $v);
    if ($v === '') {
        return '';
    }

    return is_numeric($v) ? number_format((float) $v) : barangay_h($v);
};
$weighed = (int) ($ind['weighed_0_59'] ?? 0);
$totalPop = (int) ($ind['actual_population'] ?? 0);
$actualHH = (int) ($ind['actual_households'] ?? 0);
$surveyedHH = (int) ($ind['households_surveyed'] ?? 0);
$pop059 = (int) ($ind['pop_0_59'] ?? 0);
$age05 = (int) ($ind['age_0_5'] ?? 0);
$assetPrefix = $assetPrefix ?? '../';
$reportMode = nutrition_bnp_normalize_mode($reportMode ?? 'consolidated');
$modeSelectable = !empty($modeSelectable);

$num = static function (int $n): string {
    return number_format($n);
};

ob_start();
?>
  <div class="bnp-form-code">*BNS Form C1, Revised May 2026_CNHPDD012</div>
  <div class="bnp-title">BARANGAY NUTRITION PROFILE</div>
  <div class="bnp-subtitle"><?= !empty($isCityWide) ? 'All Barangays, Valencia City' : ('Barangay ' . barangay_h($barangayName) . ', Valencia City') ?></div>
  <div class="bnp-cy">CY <?= $calendarYear ?></div>
  <div class="bnp-focus">ALL HOUSEHOLDS</div>
  <div class="bnp-mode-title"><?= $reportMode === 'individual' ? 'INDIVIDUAL' : 'CONSOLIDATED' ?></div>
  <?php require __DIR__ . '/nutrition_bnp_mode_mark.php'; ?>
<?php
$bnpHeaderCenterHtml = ob_get_clean();
?>
<div class="bnp-form">
  <?php require __DIR__ . '/nutrition_bnp_header_logos.php'; ?>
  <?php require __DIR__ . '/nutrition_bnp_officer_line.php'; ?>

  <table class="bnp-table bnp-table-c1">
    <thead>
      <tr>
        <th style="width:70%">INDICATORS</th>
        <th colspan="2">Number</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>1. Total Actual Population</td><td class="bnp-num" colspan="2"><?= $num($totalPop) ?></td></tr>
      <tr><td>2. Number of Actual Households (Stayed at least 6 consecutive months)</td><td class="bnp-num" colspan="2"><?= $num($actualHH) ?></td></tr>
      <tr><td>3. Households surveyed during Family Profile Survey (August of Previous Year)</td><td class="bnp-num" colspan="2"><?= $num($surveyedHH) ?></td></tr>
      <tr class="bnp-section"><td colspan="3"><strong>4. Total number of women who are:</strong></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;a. Pregnant</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['pregnant_women'] ?? 0)) ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;b. Lactating</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['lactating_women'] ?? 0)) ?></td></tr>
      <tr><td>5. Total number of households with children aged 0-59 months old</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['hh_with_0_59'] ?? 0)) ?></td></tr>
      <tr><td>6. Actual population of 0-59 months old children</td><td class="bnp-num" colspan="2"><?= $num($pop059) ?></td></tr>
      <tr><td>7. Total number of under five year old children weighed during OPT Plus</td><td class="bnp-num" colspan="2"><?= $num($weighed) ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;a. Percent (%) weighing during (OPT Plus)</td><td class="bnp-num" colspan="2"><?= barangay_h((string) ($ind['weighing_percent'] ?? 0)) ?>%</td></tr>

      <tr class="bnp-section"><td><strong>b. Number and Percent (%) of under five year old children according to Nutritional Status</strong></td><td class="bnp-num"><strong>No.</strong></td><td class="bnp-num"><strong>%</strong></td></tr>
      <tr class="bnp-sub"><td colspan="3"><em>WEIGHT FOR AGE</em></td></tr>
      <?php foreach (['SUW' => '1. Severely Underweight', 'UW' => '2. Underweight', 'OW' => '3. Overweight', 'Normal' => '4. Normal'] as $k => $lab) :
          $n = (int) ($ind['wfa'][$k] ?? 0); ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num"><?= $num($n) ?></td><td class="bnp-num"><?= $pct($n, $weighed) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-sub"><td colspan="3"><em>WEIGHT FOR HEIGHT</em></td></tr>
      <?php foreach (['Sev Wasted' => '1. Severely Wasted', 'Wasted' => '2. Wasted', 'OB' => '3. Obese', 'OW' => '4. Overweight', 'Normal' => '5. Normal'] as $k => $lab) :
          $n = (int) ($ind['wfh'][$k] ?? 0); ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num"><?= $num($n) ?></td><td class="bnp-num"><?= $pct($n, $weighed) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-sub"><td colspan="3"><em>HEIGHT FOR AGE</em></td></tr>
      <?php foreach (['Severely Stunted' => '1. Severely Stunted', 'Stunted' => '2. Stunted', 'Normal' => '3. Normal', 'Tall' => '4. Tall'] as $k => $lab) :
          $n = (int) ($ind['hfa'][$k] ?? 0); ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num"><?= $num($n) ?></td><td class="bnp-num"><?= $pct($n, $weighed) ?></td></tr>
      <?php endforeach; ?>

      <tr><td>8. Total number of infants 0-5 months old</td><td class="bnp-num" colspan="2"><?= $num($age05) ?></td></tr>
      <tr><td>9. Total number of infants 6-11 months old</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['age_6_11'] ?? 0)) ?></td></tr>
      <tr><td>10. Total number of children 0-23 months old</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['age_0_23'] ?? 0)) ?></td></tr>
      <tr><td>11. Total number of children aged 12-59 months old</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['age_12_59'] ?? 0)) ?></td></tr>
      <tr><td>12. Total number of 24-59 months old children</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['age_24_59'] ?? 0)) ?></td></tr>
      <tr><td>13. Total number of families with underweight and severely underweight 0-59 mos children</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['fam_uw_suw'] ?? 0)) ?></td></tr>
      <tr><td>14. Total number of families with severely wasted and wasted 0-59 mos children</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['fam_wasted'] ?? 0)) ?></td></tr>
      <tr><td>15. Total number of families stunted and severely stunted 0-59 mos children</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['fam_stunted'] ?? 0)) ?></td></tr>

      <tr class="bnp-section"><td><strong>16. Total number of Educational Institutions</strong></td><td class="bnp-num"><strong>Public</strong></td><td class="bnp-num"><strong>Private</strong></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;a. Number of Day Care Centers</td><td class="bnp-num"><?= $blank($formC1['daycare_public'] ?? '') ?></td><td class="bnp-num"><?= $blank($formC1['daycare_private'] ?? '') ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;b. Number of Elementary Schools</td><td class="bnp-num"><?= $blank($formC1['elementary_public'] ?? '') ?></td><td class="bnp-num"><?= $blank($formC1['elementary_private'] ?? '') ?></td></tr>
      <tr><td>17. Total number of children enrolled in kindergarten (DepEd-supervised)</td><td class="bnp-num" colspan="2"><?= $blank($formC1['kindergarten'] ?? '') ?></td></tr>
      <tr><td>18. Total number of school children</td><td class="bnp-num" colspan="2"><?= $blank($formC1['grade1'] ?? '') ?></td></tr>
      <tr><td>19. Total number of school children weighed at the start of the school year</td><td class="bnp-num" colspan="2"><?= $blank($formC1['school_weighed'] ?? '') ?></td></tr>
      <tr><td>20. Percent (%) weighing coverage of school weighing</td><td class="bnp-num" colspan="2"><?= $blank($formC1['school_weighing_pct'] ?? '') !== '' ? barangay_h((string) $formC1['school_weighing_pct']) . '%' : '' ?></td></tr>

      <tr class="bnp-section"><td><strong>21. Number and percent (%) of school children according to Nutritional Status (Public) Kinder to Grade 6 Only</strong></td><td class="bnp-num"><strong>No.</strong></td><td class="bnp-num"><strong>%</strong></td></tr>
      <?php
      $schoolRows = [
          'a. Severely Wasted' => 'school_sev_wasted',
          'b. Wasted' => 'school_wasted',
          'c. Normal' => 'school_normal',
          'd. Overweight' => 'school_ow',
          'e. Obese' => 'school_ob',
      ];
      $schoolDenom = 0;
      foreach ($schoolRows as $key) {
          $v = trim((string) ($formC1[$key] ?? ''));
          if (is_numeric($v)) {
              $schoolDenom += (int) $v;
          }
      }
      foreach ($schoolRows as $lab => $key) :
          $v = trim((string) ($formC1[$key] ?? ''));
          $n = is_numeric($v) ? (int) $v : null;
          ?>
      <tr>
        <td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td>
        <td class="bnp-num"><?= $n !== null ? $num($n) : '' ?></td>
        <td class="bnp-num"><?= $n !== null ? $pct($n, max($schoolDenom, 1)) : '' ?></td>
      </tr>
      <?php endforeach; ?>

      <tr><td>22. 0-5 months old infants who are exclusively breastfeed</td><td class="bnp-num"><?= $num((int) ($ind['exclusive_bf'] ?? 0)) ?></td><td class="bnp-num"><?= $pct((int) ($ind['exclusive_bf'] ?? 0), $age05) ?></td></tr>
      <tr><td>23. 6-23 months old children who are given complementary foods</td><td class="bnp-num"><?= $num((int) ($ind['complementary_6_23'] ?? 0)) ?></td><td class="bnp-num"><?= $pct((int) ($ind['complementary_6_23'] ?? 0), max((int) ($ind['age_0_23'] ?? 0) - $age05, 0)) ?></td></tr>
      <tr><td>24. 6-23 months old children who continue breastfeeding</td><td class="bnp-num" colspan="2"></td></tr>
      <tr><td>25. Fully immunized children (FIC)</td><td class="bnp-num" colspan="2"><?= $blank($formC1['fic'] ?? '') ?></td></tr>

      <tr class="bnp-section"><td colspan="3"><strong>26. Households, by type of toilet facility (Data source from BSI):</strong></td></tr>
      <tr class="bnp-sub"><td colspan="3"><em>1. Type of Sanitary Facility</em></td></tr>
      <?php
      $sanitary = [
          'a. Pour/Flush type with septic tank' => 'Pour/Flush type with septic tank',
          'b. Pour Flush Toilet connected to septic tank and sewerage system' => 'Pour Flush Toilet connected to septic tank and sewerage system',
          'c. Ventilated Pit (VIP) Latrine' => 'Ventilated Pit (VIP) Latrine',
      ];
      foreach ($sanitary as $lab => $key) :
          $n = (int) ($prf['toilet_sanitary'][$key] ?? 0);
          ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num" colspan="2"><?= $num($n) ?></td></tr>
      <?php endforeach; ?>
      <tr class="bnp-sub"><td colspan="3"><em>2. Type of Unsanitary Facility</em></td></tr>
      <?php
      $unsanitary = [
          'a. Water-sealed toilet w/o septic tank' => 'Water-sealed toilet w/o septic tank',
          'b. Overhung Latrine' => 'Overhung Latrine',
          'c. Open Pit Latrine' => 'Open Pit Latrine',
          'd. Without toilet' => 'Without toilet',
      ];
      foreach ($unsanitary as $lab => $key) :
          $n = (int) ($prf['toilet_unsanitary'][$key] ?? 0);
          ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num" colspan="2"><?= $num($n) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="3"><strong>27. Households, by type of garbage disposal:</strong></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;a. Collected/ Segregated</td><td class="bnp-num" colspan="2"><?= $num((int) ($prf['garbage']['Collected'] ?? 0)) ?></td></tr>
      <tr class="bnp-section"><td>&nbsp;&nbsp;&nbsp;b. Uncollected</td><td class="bnp-num"><strong>Segregated</strong></td><td class="bnp-num"><strong>Unsegregated</strong></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;b.1. Burning</td><td class="bnp-num"><?= $num((int) ($prf['garbage']['Burning_seg'] ?? 0)) ?></td><td class="bnp-num"><?= $num((int) ($prf['garbage']['Burning_unseg'] ?? 0)) ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;b.2. Dumping</td><td class="bnp-num"><?= $num((int) ($prf['garbage']['Dumping_seg'] ?? 0)) ?></td><td class="bnp-num"><?= $num((int) ($prf['garbage']['Dumping_unseg'] ?? 0)) ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;b.3. Composting</td><td class="bnp-num"><?= $num((int) ($prf['garbage']['Composting_seg'] ?? 0)) ?></td><td class="bnp-num"><?= $num((int) ($prf['garbage']['Composting_unseg'] ?? 0)) ?></td></tr>

      <tr class="bnp-section"><td colspan="3"><strong>28. Households, by source of drinking water:</strong></td></tr>
      <?php foreach ([
          'Pipe Water System' => 'a. Pipe Water System (level III)',
          'Communal Water Source' => 'b. Communal source piped water system',
          'Mineral' => 'c. Mineral water/water dispensing stores',
          'Well' => 'd. Well',
          'Spring' => 'e. Spring',
      ] as $k => $lab) : ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num" colspan="2"><?= $num((int) ($prf['water'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="3"><strong>29. Household with:</strong></td></tr>
      <?php foreach ([
          'Vegetable Garden' => 'a. Vegetable Garden Only',
          'Livestock and/or Poultry' => 'b. Livestock and/or Poultry',
          'Fish Pond' => 'c. Fish Pond',
          'Others' => 'd. Others: Ex. (Fruit Trees, etc.)',
      ] as $k => $lab) : ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num" colspan="2"><?= $num((int) ($prf['food'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="3"><strong>30. Household, by type of dwelling unit:</strong></td></tr>
      <?php foreach ([
          'Concrete' => 'a. Concrete',
          'Semi-concrete' => 'b. Semi-Concrete',
          'Wood' => 'c. Wood',
          'Makeshift/Barong-barong' => 'd. Makeshift/barongbarong',
      ] as $k => $lab) : ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num" colspan="2"><?= $num((int) ($prf['dwelling'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="3"><strong>31. Household with:</strong></td></tr>
      <?php foreach (['Owned' => 'a. Owned', 'Rented' => 'b. Rented', 'Others' => 'c. Others, (pls. specify)'] as $k => $lab) : ?>
      <tr><td>&nbsp;&nbsp;&nbsp;<?= barangay_h($lab) ?></td><td class="bnp-num" colspan="2"><?= $num((int) ($prf['house'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>

      <tr><td>32. Total number of households using iodized salt</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['iodized_salt_hh'] ?? 0)) ?></td></tr>
      <tr><td>33. Total number of sari-sari store</td><td class="bnp-num" colspan="2"><?php
        $sariC1 = trim((string) ($formC1['sari_sari'] ?? ''));
        if ($sariC1 !== '') {
            echo $blank($sariC1);
        } else {
            echo $num((int) ($ind['sari_sari_hh'] ?? 0));
        }
      ?></td></tr>

      <tr class="bnp-section"><td colspan="3"><strong>34. Number of Health and Nutrition Workers:</strong></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;A. BARANGAY NUTRITION SCHOLARS</td><td class="bnp-num" colspan="2"><?= $blank($formC1['bns_count'] ?? '') ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;B. BARANGAY HEALTH WORKERS</td><td class="bnp-num" colspan="2"><?= $blank($formC1['bhw_count'] ?? '') ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;C. RURAL HEALTH MIDWIFE</td><td class="bnp-num" colspan="2"><?= $blank($formC1['midwife_count'] ?? '') ?></td></tr>

      <tr><td>35. Total number of household beneficiaries of Pantawid Pamilya Pilipino Program (4Ps)</td><td class="bnp-num" colspan="2"><?= $num((int) ($ind['four_ps'] ?? 0)) ?></td></tr>

      <tr class="bnp-section"><td colspan="3"><strong>36. Number of households belonging to Indigenous People:</strong> <?= $num((int) ($ind['ip'] ?? 0)) ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;A. Pregnant Women</td><td class="bnp-num" colspan="2"><?= $blank($formC1['ip_pregnant'] ?? '') !== '' ? $blank($formC1['ip_pregnant']) : $num((int) ($ind['ip_pregnant'] ?? 0)) ?></td></tr>
      <tr><td>&nbsp;&nbsp;&nbsp;B. 6-23 months children</td><td class="bnp-num" colspan="2"><?= $blank($formC1['ip_6_23'] ?? '') !== '' ? $blank($formC1['ip_6_23']) : $num((int) ($ind['ip_6_23'] ?? 0)) ?></td></tr>
    </tbody>
  </table>

  <?php require __DIR__ . '/nutrition_bnp_signatories.php'; ?>
</div>
