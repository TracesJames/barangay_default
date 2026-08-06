<?php

/**
 * Render Barangay Nutrition Profile — Families with Pregnant matrix.
 *
 * Expected:
 * - $pregnantReport (from nutrition_pregnant_families_report / city variant)
 * - $barangayName, $calendarYear
 * - $reportMode: 'consolidated' | 'individual' (default consolidated)
 * - $modeSelectable (optional bool)
 * - $isCityWide (optional bool)
 */
$sourceReport = $pregnantReport ?? [];
$reportMode = nutrition_bnp_normalize_mode($reportMode ?? 'consolidated');
$modeSelectable = !empty($modeSelectable);
$isCityWide = !empty($isCityWide);
$barangayName = $barangayName ?? ($barangay ?? '');
$calendarYear = (int) ($calendarYear ?? ($sourceReport['calendar_year'] ?? date('Y')));
$bnsName = trim((string) ($sourceReport['bns_name'] ?? ($nutritionSettings['nutrition_officer'] ?? '')));

$formsToRender = [];
if ($reportMode === 'individual') {
    foreach ($sourceReport['individuals'] ?? [] as $ind) {
        $form = $ind['form'] ?? [];
        $form['bns_name'] = $bnsName;
        $formsToRender[] = [
            'report' => $form,
            'caption' => trim(
                (($ind['barangay'] ?? '') !== '' ? ('Brgy. ' . $ind['barangay'] . ' · ') : '')
                . (($ind['head_name'] ?? '') !== '' ? (string) $ind['head_name'] : 'Household')
                . (($ind['purok'] ?? '') !== '' ? (' · Purok ' . $ind['purok']) : '')
                . (($ind['column'] ?? '') !== '' ? (' · Col. ' . $ind['column']) : '')
            ),
        ];
    }
} else {
    $formsToRender[] = [
        'report' => $sourceReport,
        'caption' => '',
    ];
}

foreach ($formsToRender as $formIndex => $formPack) :
    $pregnantReport = $formPack['report'];
    $householdCaption = (string) ($formPack['caption'] ?? '');
    $columns = $pregnantReport['columns'] ?? nutrition_pregnant_profile_columns();
    $colKeys = array_keys($columns);
    $pageBreak = $formIndex > 0 ? ' style="page-break-before:always;"' : '';
    $assetPrefix = $assetPrefix ?? '../';
    ob_start();
    ?>
    <div class="bnp-title">BARANGAY NUTRITION PROFILE</div>
    <div class="bnp-subtitle">
      <?= $isCityWide ? 'All Barangays, Valencia City' : ('Barangay ' . barangay_h($barangayName) . ', Valencia City') ?>
    </div>
    <div class="bnp-cy">CY <?= (int) $calendarYear ?></div>
    <div class="bnp-focus">FAMILIES WITH PREGNANT</div>
    <div class="bnp-mode-title"><?= $reportMode === 'individual' ? 'INDIVIDUAL' : 'CONSOLIDATED' ?></div>
    <?php require __DIR__ . '/nutrition_bnp_mode_mark.php'; ?>
    <?php if ($householdCaption !== '') : ?>
    <div class="bnp-household-caption" style="margin:.35rem 0 .5rem;font-weight:700;">
      Household: <?= barangay_h($householdCaption) ?>
    </div>
    <?php endif; ?>
    <?php
    $bnpHeaderCenterHtml = ob_get_clean();
    ?>
<div class="bnp-pregnant-report"<?= $pageBreak ?>>
  <?php require __DIR__ . '/nutrition_bnp_header_logos.php'; ?>
  <div class="bnp-header text-center">
    <?php require __DIR__ . '/nutrition_bnp_officer_line.php'; ?>
  </div>

  <table class="bnp-table">
    <thead>
      <tr>
        <th class="bnp-item-head">ITEM</th>
        <?php foreach ($colKeys as $col) : ?>
        <th class="bnp-num-head">Total (<?= barangay_h($col) ?>)</th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <tr class="bnp-section">
        <td colspan="6"><strong>1. Total No. of Families with:</strong></td>
      </tr>
      <?php foreach ($columns as $col => $label) :
          $count = (int) ($pregnantReport['pregnant_totals'][$col] ?? 0);
          ?>
      <tr>
        <td class="bnp-item"><?= barangay_h($col . ') ' . $label) ?></td>
        <?php foreach ($colKeys as $ck) : ?>
        <td class="bnp-num"><?= $ck === $col ? number_format($count) : '' ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>

      <tr class="bnp-section">
        <td colspan="6"><strong>2. House:</strong></td>
      </tr>
      <?php
      $houseLabels = [
          'Owned' => 'A) Owned',
          'Rented' => 'B) Rented',
          'Others' => 'C) Others (pls. specify)',
      ];
      foreach ($houseLabels as $key => $label) :
          $totals = $pregnantReport['house'][$key] ?? nutrition_pregnant_zero_columns();
          ?>
      <tr>
        <td class="bnp-item"><?= barangay_h($label) ?></td>
        <?php foreach ($colKeys as $ck) : ?>
        <td class="bnp-num"><?= number_format((int) ($totals[$ck] ?? 0)) ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>

      <tr class="bnp-section">
        <td colspan="6"><strong>3. Types of Dwelling Unit:</strong></td>
      </tr>
      <?php
      $dwellingLabels = [
          'Concrete' => 'A) Concrete',
          'Semi-concrete' => 'B) Semi-Concrete',
          'Wood' => 'C) Wood',
          'Makeshift/Barong-barong' => 'D) Makeshift/Barongbarong',
      ];
      foreach ($dwellingLabels as $key => $label) :
          $totals = $pregnantReport['dwelling'][$key] ?? nutrition_pregnant_zero_columns();
          ?>
      <tr>
        <td class="bnp-item"><?= barangay_h($label) ?></td>
        <?php foreach ($colKeys as $ck) : ?>
        <td class="bnp-num"><?= number_format((int) ($totals[$ck] ?? 0)) ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>

      <tr class="bnp-section">
        <td colspan="6"><strong>4. No. of Household Garbage Disposal Practice:</strong></td>
      </tr>
      <?php
      $garbageLabels = [
          'Collected' => 'a. Collected / Segregated',
          'Burning' => 'b.1. Uncollected — Burning',
          'Dumping' => 'b.2. Uncollected — Dumping',
          'Composting' => 'b.3. Uncollected — Composting',
      ];
      foreach ($garbageLabels as $key => $label) :
          $totals = $pregnantReport['garbage'][$key] ?? nutrition_pregnant_zero_columns();
          ?>
      <tr>
        <td class="bnp-item"><?= barangay_h($label) ?></td>
        <?php foreach ($colKeys as $ck) : ?>
        <td class="bnp-num"><?= number_format((int) ($totals[$ck] ?? 0)) ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>

      <tr class="bnp-section">
        <td colspan="6"><strong>5. Households, by type of toilet facility:</strong></td>
      </tr>
      <?php
      $toiletLabels = [
          'Water Sealed' => 'a. Water sealed toilet',
          'Covered Pit' => 'b. Covered Pit',
          'Open Pit' => 'c. Open Pit',
          'No Toilet' => 'd. No Toilet',
      ];
      foreach ($toiletLabels as $key => $label) :
          $totals = $pregnantReport['toilet'][$key] ?? nutrition_pregnant_zero_columns();
          ?>
      <tr>
        <td class="bnp-item"><?= barangay_h($label) ?></td>
        <?php foreach ($colKeys as $ck) : ?>
        <td class="bnp-num"><?= number_format((int) ($totals[$ck] ?? 0)) ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>

      <tr class="bnp-section">
        <td colspan="6"><strong>6. Households, by source of drinking water:</strong></td>
      </tr>
      <?php
      $waterLabels = [
          'Pipe Water System' => 'a. Pipe Water System (Level III)',
          'Communal Water Source' => 'b. Communal source piped water system',
          'Mineral' => 'c. Mineral water / water dispensing stores',
          'Well' => 'd. Well',
          'Spring' => 'e. Spring',
      ];
      foreach ($waterLabels as $key => $label) :
          $totals = $pregnantReport['water'][$key] ?? nutrition_pregnant_zero_columns();
          ?>
      <tr>
        <td class="bnp-item"><?= barangay_h($label) ?></td>
        <?php foreach ($colKeys as $ck) : ?>
        <td class="bnp-num"><?= number_format((int) ($totals[$ck] ?? 0)) ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>

      <tr class="bnp-section">
        <td colspan="6"><strong>7. Household with:</strong></td>
      </tr>
      <?php
      $foodLabels = [
          'Vegetable Garden' => 'a. Vegetable Garden',
          'Livestock and/or Poultry' => 'b. Livestock and/or Poultry',
          'Fish Pond' => 'c. Fish Pond',
          'Others' => 'd. Others: Ex. (Fruit Trees, etc.)',
      ];
      foreach ($foodLabels as $key => $label) :
          $totals = $pregnantReport['food'][$key] ?? nutrition_pregnant_zero_columns();
          ?>
      <tr>
        <td class="bnp-item"><?= barangay_h($label) ?></td>
        <?php foreach ($colKeys as $ck) : ?>
        <td class="bnp-num"><?= number_format((int) ($totals[$ck] ?? 0)) ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="bnp-family-size-wrap">
    <div class="bnp-section-title"><strong>8. Family Size</strong></div>
    <table class="bnp-table bnp-family-size">
      <thead>
        <tr>
          <th>Number of Family Members</th>
          <th colspan="5">Number of Families with Corresponding Size</th>
        </tr>
        <tr>
          <th></th>
          <?php foreach ($colKeys as $i => $col) : ?>
          <th>Col. <?= (int) ($i + 1) ?> (<?= barangay_h($col) ?>)</th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php for ($n = 15; $n >= 1; $n--) :
            $totals = $pregnantReport['family_size'][$n] ?? nutrition_pregnant_zero_columns();
            ?>
        <tr>
          <td class="bnp-item"><?= (int) $n ?></td>
          <?php foreach ($colKeys as $ck) : ?>
          <td class="bnp-num"><?= number_format((int) ($totals[$ck] ?? 0)) ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>

  <div class="bnp-occupation">
    <strong>9. Most Common Occupation:</strong>
    <span><?= barangay_h((string) ($pregnantReport['most_common_occupation'] ?? '')) ?: '______________________________' ?></span>
  </div>

  <div class="bnp-footnote">
    <?php if ($reportMode === 'individual') : ?>
    Individual household form<?= $householdCaption !== '' ? ': <strong>' . barangay_h($householdCaption) . '</strong>' : '' ?>
    · Columns A–E = Normal, Teenage, Underweight, Overweight, Others
    <?php else : ?>
    Total families with pregnant: <strong><?= number_format((int) ($pregnantReport['family_count'] ?? 0)) ?></strong>
    · Columns A–E = Normal, Teenage, Underweight, Overweight, Others
    <?php endif; ?>
  </div>
  <?php require __DIR__ . '/nutrition_bnp_signatories.php'; ?>
</div>
<?php endforeach; ?>

<?php if ($reportMode === 'individual' && empty($sourceReport['individuals'])) : ?>
<div class="bnp-pregnant-report">
  <div class="bnp-header text-center">
    <div class="bnp-title">BARANGAY NUTRITION PROFILE</div>
    <div class="bnp-focus">FAMILIES WITH PREGNANT</div>
    <?php require __DIR__ . '/nutrition_bnp_mode_mark.php'; ?>
  </div>
  <p class="text-muted mb-0">No pregnant households found for Individual mode.</p>
</div>
<?php endif; ?>
