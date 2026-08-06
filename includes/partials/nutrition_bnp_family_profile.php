<?php

/**
 * Render BNP family-profile forms (C2–C6, C8–C9) with a single NUMBER column.
 *
 * Expected: $bnpReport from nutrition_bnp_family_profile_report()
 * Optional: $reportMode, $modeSelectable
 */
$sourceReport = $bnpReport ?? [];
$meta = $sourceReport['meta'] ?? [];
$reportMode = nutrition_bnp_normalize_mode($reportMode ?? 'consolidated');
$modeSelectable = !empty($modeSelectable);
$barangayName = $barangayName ?? ($barangay ?? '');
$calendarYear = (int) ($calendarYear ?? ($sourceReport['calendar_year'] ?? date('Y')));
$bnsName = trim((string) ($sourceReport['bns_name'] ?? ''));
$formCode = trim((string) (($meta['form'] ?? '') . ', ' . ($meta['code'] ?? '')));
$labels = $meta['labels'] ?? [];

$formsToRender = [];
if ($reportMode === 'individual') {
    foreach ($sourceReport['individuals'] ?? [] as $ind) {
        $formsToRender[] = [
            'category_totals' => $ind['category_totals'] ?? [],
            'prf' => $ind['prf'] ?? nutrition_bnp_empty_prf_counts(),
            'family_count' => 1,
            'caption' => trim(
                (($ind['head_name'] ?? '') !== '' ? (string) $ind['head_name'] : 'Household')
                . (($ind['purok'] ?? '') !== '' ? (' · Purok ' . $ind['purok']) : '')
                . (($ind['category'] ?? '') !== '' ? (' · ' . $ind['category']) : '')
            ),
        ];
    }
} else {
    $formsToRender[] = [
        'category_totals' => $sourceReport['category_totals'] ?? [],
        'prf' => $sourceReport['prf'] ?? nutrition_bnp_empty_prf_counts(),
        'family_count' => (int) ($sourceReport['family_count'] ?? 0),
        'caption' => '',
    ];
}

foreach ($formsToRender as $formIndex => $pack) :
    $categoryTotals = $pack['category_totals'];
    $prf = $pack['prf'];
    $householdCaption = (string) ($pack['caption'] ?? '');
    $pageBreak = $formIndex > 0 ? ' style="page-break-before:always;"' : '';
    $assetPrefix = $assetPrefix ?? '../';
    ob_start();
    ?>
  <div class="bnp-form-code">*BNS Form <?= barangay_h(trim((string) ($meta['form'] ?? ''))) ?>, Revised May 2026_<?= barangay_h((string) ($meta['code'] ?? '')) ?></div>
  <div class="bnp-title">BARANGAY NUTRITION PROFILE</div>
  <div class="bnp-subtitle"><?= !empty($isCityWide) ? 'All Barangays, Valencia City' : ('Barangay ' . barangay_h($barangayName) . ', Valencia City') ?></div>
  <div class="bnp-cy">CY <?= $calendarYear ?></div>
  <div class="bnp-focus"><?= barangay_h((string) ($meta['title'] ?? '')) ?></div>
  <div class="bnp-mode-title"><?= $reportMode === 'individual' ? 'INDIVIDUAL' : 'CONSOLIDATED' ?></div>
  <?php require __DIR__ . '/nutrition_bnp_mode_mark.php'; ?>
  <?php if ($householdCaption !== '') : ?>
  <div class="bnp-household-caption" style="margin:.35rem 0 .5rem;font-weight:700;text-align:center;">
    Household: <?= barangay_h($householdCaption) ?>
  </div>
  <?php endif; ?>
    <?php
    $bnpHeaderCenterHtml = ob_get_clean();
    ?>
<div class="bnp-form"<?= $pageBreak ?>>
  <?php require __DIR__ . '/nutrition_bnp_header_logos.php'; ?>
  <?php require __DIR__ . '/nutrition_bnp_officer_line.php'; ?>

  <table class="bnp-table">
    <thead>
      <tr>
        <th style="width:70%;">ITEM</th>
        <th style="width:30%;">NUMBER</th>
      </tr>
    </thead>
    <tbody>
      <tr class="bnp-section"><td colspan="2"><strong>1. Total No. of Families with:</strong></td></tr>
      <?php foreach ($labels as $col => $label) : ?>
      <tr>
        <td><?= barangay_h($label) ?></td>
        <td class="bnp-num"><?= number_format((int) ($categoryTotals[$col] ?? 0)) ?></td>
      </tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="2"><strong>2. House:</strong></td></tr>
      <?php foreach (['Owned' => 'A) Owned', 'Rented' => 'B) Rented', 'Others' => 'C) Others (pls. specify)'] as $k => $lab) : ?>
      <tr><td><?= barangay_h($lab) ?></td><td class="bnp-num"><?= number_format((int) ($prf['house'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="2"><strong>3. Types of Dwelling Unit:</strong></td></tr>
      <?php foreach ([
          'Concrete' => 'A) Concrete',
          'Semi-concrete' => 'B) Semi-Concrete',
          'Wood' => 'C) Wood',
          'Makeshift/Barong-barong' => 'D) Makeshift/Barongbarong',
      ] as $k => $lab) : ?>
      <tr><td><?= barangay_h($lab) ?></td><td class="bnp-num"><?= number_format((int) ($prf['dwelling'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="2"><strong>4. No. of Household Garbage Disposal Practice:</strong></td></tr>
      <tr><td>a. Collected/ Segregated</td><td class="bnp-num"><?= number_format((int) ($prf['garbage']['Collected'] ?? 0)) ?></td></tr>
      <tr><td>b.1. Uncollected — Burning</td><td class="bnp-num"><?= number_format((int) ($prf['garbage']['Burning'] ?? 0)) ?></td></tr>
      <tr><td>b.2. Uncollected — Dumping</td><td class="bnp-num"><?= number_format((int) ($prf['garbage']['Dumping'] ?? 0)) ?></td></tr>
      <tr><td>b.3. Uncollected — Composting</td><td class="bnp-num"><?= number_format((int) ($prf['garbage']['Composting'] ?? 0)) ?></td></tr>

      <tr class="bnp-section"><td colspan="2"><strong>5. Households, by type of toilet facility:</strong></td></tr>
      <tr class="bnp-sub"><td colspan="2"><em>1. Type of Sanitary Facility</em></td></tr>
      <?php foreach ($prf['toilet_sanitary'] ?? [] as $lab => $n) : ?>
      <tr><td><?= barangay_h($lab) ?></td><td class="bnp-num"><?= number_format((int) $n) ?></td></tr>
      <?php endforeach; ?>
      <tr class="bnp-sub"><td colspan="2"><em>2. Type of Unsanitary Facility</em></td></tr>
      <?php foreach ($prf['toilet_unsanitary'] ?? [] as $lab => $n) : ?>
      <tr><td><?= barangay_h($lab) ?></td><td class="bnp-num"><?= number_format((int) $n) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="2"><strong>6. Households, by source of drinking water:</strong></td></tr>
      <?php foreach ([
          'Pipe Water System' => 'a. Pipe Water System (Level III)',
          'Communal Water Source' => 'b. Communal source piped water system',
          'Mineral' => 'c. Mineral water/water dispensing stores',
          'Well' => 'd. Well',
          'Spring' => 'e. Spring',
      ] as $k => $lab) : ?>
      <tr><td><?= barangay_h($lab) ?></td><td class="bnp-num"><?= number_format((int) ($prf['water'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>

      <tr class="bnp-section"><td colspan="2"><strong>7. Household with:</strong></td></tr>
      <?php foreach ([
          'Vegetable Garden' => 'a. Vegetable Garden',
          'Livestock and/or Poultry' => 'b. Livestock and/or Poultry',
          'Fish Pond' => 'c. Fish Pond',
          'Others' => 'd. Others: Ex. (Fruit Trees, etc.)',
      ] as $k => $lab) : ?>
      <tr><td><?= barangay_h($lab) ?></td><td class="bnp-num"><?= number_format((int) ($prf['food'][$k] ?? 0)) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="bnp-section-title"><strong>8. Family Size</strong></div>
  <table class="bnp-table">
    <thead>
      <tr>
        <th>Number of Family Members</th>
        <th>Number of Families with Corresponding Size</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($n = 15; $n >= 1; $n--) : ?>
      <tr>
        <td><?= (int) $n ?></td>
        <td class="bnp-num"><?= number_format((int) ($prf['family_size'][$n] ?? 0)) ?></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <div class="bnp-occupation">
    <strong>9. Most Common Occupation:</strong>
    <?= barangay_h((string) ($prf['most_common_occupation'] ?? '')) ?: '______________________________' ?>
  </div>

  <?php require __DIR__ . '/nutrition_bnp_signatories.php'; ?>
  <div class="bnp-footnote">
    <?php if ($reportMode === 'individual') : ?>
    Individual household form<?= $householdCaption !== '' ? ': <strong>' . barangay_h($householdCaption) . '</strong>' : '' ?>
    <?php else : ?>
    Total qualifying families: <strong><?= number_format((int) ($pack['family_count'] ?? 0)) ?></strong>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if ($reportMode === 'individual' && empty($sourceReport['individuals'])) : ?>
<div class="bnp-form">
  <div class="bnp-title">BARANGAY NUTRITION PROFILE</div>
  <div class="bnp-focus"><?= barangay_h((string) ($meta['title'] ?? '')) ?></div>
  <?php require __DIR__ . '/nutrition_bnp_mode_mark.php'; ?>
  <p class="text-muted mb-0">No qualifying households found for Individual mode.</p>
</div>
<?php endif; ?>
