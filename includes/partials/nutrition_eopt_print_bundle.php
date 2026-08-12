<?php

/**
 * Printable e-OPT Plus Community Level Tool sheets (Region 10 ver2).
 * Expected: $eoptReport from nutrition_eopt_build_report()
 */
$eopt = $eoptReport ?? [];
$meta = $eopt['meta'] ?? [];
$totals = $eopt['totals'] ?? [];
$children = $eopt['children'] ?? [];
$lists = $eopt['lists'] ?? [];
$wfa = $eopt['wfa'] ?? [];
$hfa = $eopt['hfa'] ?? [];
$wfl = $eopt['wfl'] ?? [];
$muac = $eopt['muac'] ?? [];
$dqc = $eopt['dqc'] ?? [];
$prevalence = $eopt['prevalence'] ?? [];
$bands = nutrition_eopt_age_bands();
$isCityWide = !empty($meta['is_city_wide']);
$placeLine = $isCityWide
    ? 'All Barangays, ' . ($meta['municipality'] ?? 'City of Valencia')
    : ('Barangay ' . ($meta['barangay'] ?? '') . ', ' . ($meta['municipality'] ?? 'City of Valencia'));

$wfaIp = $eopt['wfa_ip'] ?? [];
$hfaIp = $eopt['hfa_ip'] ?? [];
$wflIp = $eopt['wfl_ip'] ?? [];
$muacIp = $eopt['muac_ip'] ?? [];
$measuredByBand = $totals['measured_by_band'] ?? [];
$den059 = max((int) ($measuredByBand['0-59'] ?? ($totals['measured'] ?? 0)), 0);
$den023 = max((int) ($measuredByBand['0-23'] ?? ($totals['age_0_23'] ?? 0)), 0);

$cell = static function (array $matrix, string $status, string $band, string $sex): string {
    return number_format((int) ($matrix[$status][$band][$sex] ?? 0));
};

$prevPct = static function (int $count, int $den): string {
    if ($den < 1) {
        return '0.00%';
    }

    return number_format(($count / $den) * 100, 2) . '%';
};

/**
 * Form 1B / Nut_StatusTool aligned columns:
 * age bands (B/G/T) · 0–59 Total + Prev · F1K Total + Prev · IP Boys/Girls/Total
 *
 * @param array<string, string> $statuses statusKey => label
 */
$form1bStatusRows = static function (
    array $matrix,
    array $ipMatrix,
    array $statuses,
    int $den059,
    int $den023,
    bool $blankMuac05 = false
) use ($bands, $cell, $prevPct): void {
    foreach ($statuses as $status => $label) {
        echo '<tr><td class="eopt-item">' . barangay_h($label) . '</td>';
        foreach ($bands as $band) {
            if ($blankMuac05 && $band === '0-5') {
                echo '<td class="eopt-blank"></td><td class="eopt-blank"></td><td class="eopt-blank"></td>';
                continue;
            }
            echo '<td class="eopt-num">' . $cell($matrix, $status, $band, 'M') . '</td>';
            echo '<td class="eopt-num">' . $cell($matrix, $status, $band, 'F') . '</td>';
            echo '<td class="eopt-num">' . $cell($matrix, $status, $band, 'T') . '</td>';
        }
        $t059 = (int) ($matrix[$status]['0-59']['T'] ?? 0);
        $t023 = (int) ($matrix[$status]['0-23']['T'] ?? 0);
        echo '<td class="eopt-num eopt-summary">' . number_format($t059) . '</td>';
        echo '<td class="eopt-num eopt-summary">' . $prevPct($t059, $den059) . '</td>';
        echo '<td class="eopt-num">' . number_format($t023) . '</td>';
        echo '<td class="eopt-num">' . $prevPct($t023, $den023) . '</td>';
        echo '<td class="eopt-num">' . $cell($ipMatrix, $status, '0-59', 'M') . '</td>';
        echo '<td class="eopt-num">' . $cell($ipMatrix, $status, '0-59', 'F') . '</td>';
        echo '<td class="eopt-num">' . $cell($ipMatrix, $status, '0-59', 'T') . '</td>';
        echo '</tr>';
    }
};

/** @deprecated kept for any older callers expecting $statusRows */
$statusRows = static function (array $matrix, array $statuses) use ($bands, $cell): void {
    foreach ($statuses as $status => $label) {
        echo '<tr><td class="eopt-item">' . barangay_h($label) . '</td>';
        foreach ($bands as $band) {
            echo '<td class="eopt-num">' . $cell($matrix, $status, $band, 'M') . '</td>';
            echo '<td class="eopt-num">' . $cell($matrix, $status, $band, 'F') . '</td>';
            echo '<td class="eopt-num">' . $cell($matrix, $status, $band, 'T') . '</td>';
        }
        echo '<td class="eopt-num">' . $cell($matrix, $status, '0-59', 'M') . '</td>';
        echo '<td class="eopt-num">' . $cell($matrix, $status, '0-59', 'F') . '</td>';
        echo '<td class="eopt-num">' . $cell($matrix, $status, '0-59', 'T') . '</td>';
        echo '<td class="eopt-num">' . $cell($matrix, $status, '0-23', 'T') . '</td>';
        echo '</tr>';
    }
};

$fmtNum = static function ($v): string {
    if ($v === null || $v === '') {
        return '—';
    }

    return is_float($v) || is_int($v) ? (string) $v : barangay_h((string) $v);
};

$monitorTable = static function (array $rows, bool $showMuac = false): void {
    ?>
  <table class="eopt-table">
    <thead>
      <tr>
        <th>Child Seq.</th>
        <th>Address / Purok</th>
        <th>Mother / Caregiver</th>
        <th>Full Name of Child</th>
        <th>Sex</th>
        <th>Birthdate</th>
        <th>Age (mos.)</th>
        <?php if ($showMuac) : ?>
        <th>MUAC (cm)</th>
        <th>MUAC Status</th>
        <?php else : ?>
        <th>Weight (kg)</th>
        <th>Length/Height (cm)</th>
        <th>WFA</th>
        <th>HFA</th>
        <th>WFL/H</th>
        <?php endif; ?>
        <th>Barangay</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($rows === []) : ?>
      <tr><td colspan="<?= $showMuac ? 10 : 13 ?>" class="eopt-empty">No matching children.</td></tr>
      <?php else : ?>
      <?php foreach ($rows as $row) : ?>
      <tr>
        <td class="eopt-num"><?= (int) ($row['list_seq'] ?? $row['seq'] ?? 0) ?></td>
        <td><?= barangay_h((string) ($row['address'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['caregiver'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['child_name'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['sex'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['birth_date'] ?? '')) ?></td>
        <td class="eopt-num"><?= (int) ($row['age_months'] ?? 0) ?></td>
        <?php if ($showMuac) : ?>
        <td class="eopt-num"><?= $row['muac_cm'] !== null ? number_format((float) $row['muac_cm'], 1) : '' ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['muac'] ?? '')) ?></td>
        <?php else : ?>
        <td class="eopt-num"><?= $row['weight_kg'] !== null ? number_format((float) $row['weight_kg'], 2) : '' ?></td>
        <td class="eopt-num"><?= $row['height_cm'] !== null ? number_format((float) $row['height_cm'], 1) : '' ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['wfa'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['hfa'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['wfl'] ?? '')) ?></td>
        <?php endif; ?>
        <td><?= barangay_h((string) ($row['barangay'] ?? '')) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
    <?php
};

$prevRow = static function (string $label, array $row): void {
    $prev = isset($row['prevalence']) ? number_format((float) $row['prevalence'], 2) . '%' : '—';
    echo '<tr>';
    echo '<td class="eopt-item">' . barangay_h($label) . '</td>';
    echo '<td class="eopt-num">' . number_format((int) ($row['count'] ?? 0)) . '</td>';
    echo '<td class="eopt-num">' . number_format((int) ($row['denominator'] ?? 0)) . '</td>';
    echo '<td class="eopt-num">' . barangay_h($prev) . '</td>';
    echo '</tr>';
};
?>

<div class="eopt-section">
  <div class="eopt-sheet-title">e-OPT Plus · Nutritional Status Tool (Nut_StatusTool)</div>
  <div class="eopt-meta">
    <?= barangay_h((string) ($meta['version'] ?? '')) ?> · <?= barangay_h($placeLine) ?> ·
    <?= barangay_h((string) ($meta['province'] ?? '')) ?> · <?= barangay_h((string) ($meta['region'] ?? '')) ?> ·
    CY <?= (int) ($meta['calendar_year'] ?? date('Y')) ?><br>
    Measured preschoolers (0–59 months): <strong><?= number_format((int) ($totals['measured'] ?? 0)) ?></strong>
    · Boys <?= (int) ($totals['boys'] ?? 0) ?> · Girls <?= (int) ($totals['girls'] ?? 0) ?>
    · IP <?= (int) ($totals['ip'] ?? 0) ?>
  </div>
  <table class="eopt-table eopt-compact">
    <thead>
      <tr>
        <th>Seq.</th>
        <th>Address</th>
        <th>Mother / Caregiver</th>
        <th>Child Name</th>
        <th>IP</th>
        <th>Sex</th>
        <th>DOB</th>
        <th>Date Measured</th>
        <th>Wt (kg)</th>
        <th>Ht (cm)</th>
        <th>Age</th>
        <th>WFA</th>
        <th>HFA</th>
        <th>WFL/H</th>
        <th>MUAC</th>
        <th>Edema</th>
        <th>Barangay</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($children === []) : ?>
      <tr><td colspan="17" class="eopt-empty">No measured children 0–59 months found.</td></tr>
      <?php else : ?>
      <?php foreach ($children as $row) : ?>
      <tr>
        <td class="eopt-num"><?= (int) ($row['seq'] ?? 0) ?></td>
        <td><?= barangay_h((string) ($row['address'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['caregiver'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['child_name'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['ip'] ?? 'NO')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['sex'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['birth_date'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['date_measured'] ?? '')) ?></td>
        <td class="eopt-num"><?= $row['weight_kg'] !== null ? number_format((float) $row['weight_kg'], 2) : '' ?></td>
        <td class="eopt-num"><?= $row['height_cm'] !== null ? number_format((float) $row['height_cm'], 1) : '' ?></td>
        <td class="eopt-num"><?= (int) ($row['age_months'] ?? 0) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['wfa'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['hfa'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['wfl'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) (($row['muac'] ?? '') !== '' ? $row['muac'] : ($row['muac_cm'] !== null ? number_format((float) $row['muac_cm'], 1) : ''))) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['edema'] ?? 'NONE')) ?></td>
        <td><?= barangay_h((string) ($row['barangay'] ?? '')) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="eopt-section eopt-break">
  <div class="eopt-sheet-title">OPT Plus Form 1A</div>
  <div class="eopt-focus">Working / Measurement List for OPT Plus Visit<br>
    (0–23 mos: measure length lying down · 24 mos &amp; above: measure height standing)</div>
  <div class="eopt-meta">
    <?= barangay_h($placeLine) ?> · Province: <?= barangay_h((string) ($meta['province'] ?? '')) ?> ·
    Date printed: <?= barangay_h((string) ($meta['printed_date'] ?? date('M d, Y'))) ?>
  </div>
  <table class="eopt-table">
    <thead>
      <tr>
        <th>Child Seq. (Previous OPT)</th>
        <th>Address (Purok)</th>
        <th>Mother / Caregiver</th>
        <th>Child's Full Name</th>
        <th>IP</th>
        <th>Sex</th>
        <th>Date of Birth</th>
        <th>Actual Date of Measurement</th>
        <th>WEIGHT (kg)</th>
        <th>HEIGHT (cm)</th>
        <th>Age in Months</th>
        <th>Nutritional Status (WFL/H)</th>
        <th>MUAC (cm)</th>
        <th>Edema</th>
        <th>Disability</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($children === []) : ?>
      <tr><td colspan="15" class="eopt-empty">No children to print.</td></tr>
      <?php else : ?>
      <?php foreach ($children as $row) : ?>
      <tr>
        <td class="eopt-num"><?= (int) ($row['seq'] ?? 0) ?></td>
        <td><?= barangay_h((string) ($row['address'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['caregiver'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['child_name'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['ip'] ?? 'NO')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['sex'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['birth_date'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['date_measured'] ?? '')) ?></td>
        <td class="eopt-num"><?= $row['weight_kg'] !== null ? number_format((float) $row['weight_kg'], 2) : '' ?></td>
        <td class="eopt-num"><?= $row['height_cm'] !== null ? number_format((float) $row['height_cm'], 1) : '' ?></td>
        <td class="eopt-num"><?= (int) ($row['age_months'] ?? 0) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['wfl'] ?? '')) ?></td>
        <td class="eopt-num"><?= $row['muac_cm'] !== null ? number_format((float) $row['muac_cm'], 1) : '' ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['edema'] ?? 'NONE')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['disability'] ?? 'NO')) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="eopt-section eopt-break eopt-landscape">
  <div class="eopt-sheet-title">OPT Plus Form 1B · Nutritional Status Summary</div>
  <div class="eopt-focus">Summary Sheet of the Nutritional Status of 0–59 Month-Old Children</div>
  <div class="eopt-meta eopt-form1b-meta">
    <?= barangay_h($placeLine) ?> · <?= barangay_h((string) ($meta['province'] ?? '')) ?> ·
    <?= barangay_h((string) ($meta['region'] ?? '')) ?><br>
    <strong>Total Boys:</strong> <?= (int) ($totals['boys'] ?? 0) ?>
    · <strong>Total Girls:</strong> <?= (int) ($totals['girls'] ?? 0) ?>
    · <strong>Total WFA:</strong> <?= (int) ($totals['wfa_classified'] ?? 0) ?>
    · <strong>Total HFA:</strong> <?= (int) ($totals['hfa_classified'] ?? 0) ?>
    · <strong>Total WFL/H:</strong> <?= (int) ($totals['wfl_classified'] ?? 0) ?>
    · <strong>Total MUAC:</strong> <?= (int) ($totals['muac_measured'] ?? 0) ?>
    · <strong>IP preschoolers:</strong> <?= (int) ($totals['ip'] ?? 0) ?>
  </div>
  <table class="eopt-table eopt-matrix eopt-form1b">
    <thead>
      <tr>
        <th rowspan="2" class="eopt-item-col">ACRONYMS &amp; ABBREVIATIONS</th>
        <?php foreach ($bands as $band) : ?>
        <th colspan="3"><?= barangay_h($band) ?> Months</th>
        <?php endforeach; ?>
        <th colspan="2">Birth to 5 Years<br>(0–59 Months)</th>
        <th colspan="2">F1K<br>(0–23 Months)</th>
        <th colspan="3"># IP Children</th>
      </tr>
      <tr>
        <?php foreach ($bands as $_) : ?>
        <th>Boys</th><th>Girls</th><th>Total</th>
        <?php endforeach; ?>
        <th>Total</th>
        <th>Prev</th>
        <th>Total</th>
        <th>Prev</th>
        <th>Boys</th>
        <th>Girls</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <tr class="eopt-section-row"><td colspan="<?= 1 + (count($bands) * 3) + 7 ?>"><strong>WEIGHT FOR AGE (WFA)</strong></td></tr>
      <?php
      $form1bStatusRows($wfa, $wfaIp, [
          'Normal' => 'WFA - Normal',
          'UW' => 'WFA - MUW (Moderately Underweight)',
          'SUW' => 'WFA - SUW (Severely Underweight)',
      ], $den059, $den023);
      ?>
      <tr class="eopt-note-row">
        <td colspan="<?= 1 + (count($bands) * 3) + 7 ?>">
          <em>No Obese/Overweight classification in the WFA. Following international standards, we use WL/H to classify overweight and obesity in children.</em>
        </td>
      </tr>

      <tr class="eopt-section-row"><td colspan="<?= 1 + (count($bands) * 3) + 7 ?>"><strong>HEIGHT FOR AGE (HFA)</strong></td></tr>
      <?php
      $form1bStatusRows($hfa, $hfaIp, [
          'Normal' => 'HFA - Normal',
          'Tall' => 'HFA - Tall',
          'St' => 'HFA - MSt (Moderately Stunted)',
          'SSt' => 'HFA - SSt (Severely Stunted)',
      ], $den059, $den023);
      ?>

      <tr class="eopt-section-row"><td colspan="<?= 1 + (count($bands) * 3) + 7 ?>"><strong>WEIGHT FOR LENGTH/HEIGHT (WFL/H)</strong></td></tr>
      <?php
      $form1bStatusRows($wfl, $wflIp, [
          'Normal' => 'WFL/H - Normal',
          'OW' => 'WFL/H - OW (Overweight)',
          'Ob' => 'WFL/H - Ob (Obese)',
          'MW' => 'WFL/H - MW/MAM',
          'SW' => 'WFL/H - SW/SAM',
      ], $den059, $den023);
      ?>

      <tr class="eopt-section-row"><td colspan="<?= 1 + (count($bands) * 3) + 7 ?>"><strong>MUAC</strong></td></tr>
      <?php
      $form1bStatusRows($muac, $muacIp, [
          'Normal' => 'MUAC - Normal',
          'MW' => 'MUAC - MW/MAM',
          'SW' => 'MUAC - SW/SAM',
      ], $den059, $den023, true);
      ?>

      <?php
      // Total (WFA) = Normal + MUW + SUW (+ OW/OB if present in matrix)
      $wfaTotalKeys = ['Normal', 'UW', 'SUW', 'OW', 'OB'];
      echo '<tr class="eopt-total-row"><td class="eopt-item"><strong>Total (WFA)</strong></td>';
      foreach ($bands as $band) {
          $b = $g = $t = 0;
          foreach ($wfaTotalKeys as $key) {
              $b += (int) ($wfa[$key][$band]['M'] ?? 0);
              $g += (int) ($wfa[$key][$band]['F'] ?? 0);
              $t += (int) ($wfa[$key][$band]['T'] ?? 0);
          }
          echo '<td class="eopt-num">' . number_format($b) . '</td>';
          echo '<td class="eopt-num">' . number_format($g) . '</td>';
          echo '<td class="eopt-num">' . number_format($t) . '</td>';
      }
      $t059 = $t023 = $ipB = $ipG = $ipT = 0;
      foreach ($wfaTotalKeys as $key) {
          $t059 += (int) ($wfa[$key]['0-59']['T'] ?? 0);
          $t023 += (int) ($wfa[$key]['0-23']['T'] ?? 0);
          $ipB += (int) ($wfaIp[$key]['0-59']['M'] ?? 0);
          $ipG += (int) ($wfaIp[$key]['0-59']['F'] ?? 0);
          $ipT += (int) ($wfaIp[$key]['0-59']['T'] ?? 0);
      }
      echo '<td class="eopt-num eopt-summary">' . number_format($t059) . '</td>';
      echo '<td class="eopt-num eopt-summary">' . $prevPct($t059, $den059) . '</td>';
      echo '<td class="eopt-num">' . number_format($t023) . '</td>';
      echo '<td class="eopt-num">' . $prevPct($t023, $den023) . '</td>';
      echo '<td class="eopt-num">' . number_format($ipB) . '</td>';
      echo '<td class="eopt-num">' . number_format($ipG) . '</td>';
      echo '<td class="eopt-num">' . number_format($ipT) . '</td>';
      echo '</tr>';
      ?>
    </tbody>
  </table>
  <p class="eopt-note">
    Aligned with DOH/NNC OPT Plus Form 1B / Nut_StatusTool (Region 10 e-OPT Plus).
    Prevalence (Prev) = status count ÷ measured children in the same age group.
    MUAC is not applied to 0–5 months (blank cells).
  </p>
</div>

<div class="eopt-section eopt-break">
  <div class="eopt-sheet-title">OPT Plus Form 1C</div>
  <div class="eopt-focus">List of Affected / At-risk 0–59 Month-Old Children</div>
  <div class="eopt-meta">
    <?= barangay_h($placeLine) ?> · CY <?= (int) ($meta['calendar_year'] ?? date('Y')) ?><br>
    MUW <?= (int) ($totals['uw'] ?? 0) ?> · SUW <?= (int) ($totals['suw'] ?? 0) ?> ·
    MSt <?= (int) ($totals['st'] ?? 0) ?> · SSt <?= (int) ($totals['sst'] ?? 0) ?> ·
    MW/MAM <?= (int) ($totals['mw'] ?? 0) ?> · SW/SAM <?= (int) ($totals['sw'] ?? 0) ?> ·
    OW <?= (int) ($totals['ow'] ?? 0) ?> · Ob <?= (int) ($totals['ob'] ?? 0) ?> ·
    Total affected/at-risk: <strong><?= number_format((int) ($totals['at_risk'] ?? 0)) ?></strong>
  </div>
  <table class="eopt-table">
    <thead>
      <tr>
        <th rowspan="2">Seq.</th>
        <th rowspan="2">Address</th>
        <th rowspan="2">Mother / Caregiver</th>
        <th rowspan="2">Child Name</th>
        <th rowspan="2">Sex</th>
        <th rowspan="2">Age (mos.)</th>
        <th colspan="4">Nutritional Status</th>
        <th rowspan="2">Barangay</th>
      </tr>
      <tr>
        <th>WFA</th>
        <th>HFA</th>
        <th>WFL/H</th>
        <th>MUAC</th>
      </tr>
    </thead>
    <tbody>
      <?php if (($lists['at_risk'] ?? []) === []) : ?>
      <tr><td colspan="11" class="eopt-empty">No at-risk preschoolers recorded.</td></tr>
      <?php else : ?>
      <?php foreach ($lists['at_risk'] as $row) : ?>
      <tr>
        <td class="eopt-num"><?= (int) ($row['list_seq'] ?? 0) ?></td>
        <td><?= barangay_h((string) ($row['address'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['caregiver'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['child_name'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['sex'] ?? '')) ?></td>
        <td class="eopt-num"><?= (int) ($row['age_months'] ?? 0) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['wfa'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['hfa'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['wfl'] ?? '')) ?></td>
        <td class="eopt-num"><?= barangay_h((string) ($row['muac'] ?? '')) ?></td>
        <td><?= barangay_h((string) ($row['barangay'] ?? '')) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="eopt-section eopt-break">
  <div class="eopt-sheet-title">NutStatusBrgy · Sex-Disaggregated Summary (0–23 and 0–59)</div>
  <div class="eopt-meta"><?= barangay_h($placeLine) ?> · For presentations / briefings</div>
  <table class="eopt-table eopt-matrix">
    <thead>
      <tr>
        <th rowspan="2">Indicator</th>
        <th colspan="4">0–23 Months (F1K)</th>
        <th colspan="4">0–59 Months</th>
      </tr>
      <tr>
        <th>Boys</th><th>Girls</th><th>Total</th><th>Prev %</th>
        <th>Boys</th><th>Girls</th><th>Total</th><th>Prev %</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $nsRows = [
          ['UW', 'wfa', 'Moderately Underweight (MUW)'],
          ['SUW', 'wfa', 'Severely Underweight (SUW)'],
          ['St', 'hfa', 'Moderately Stunted (MSt)'],
          ['SSt', 'hfa', 'Severely Stunted (SSt)'],
          ['MW', 'wfl', 'Moderately Wasted (MW/MAM)'],
          ['SW', 'wfl', 'Severely Wasted (SW/SAM)'],
          ['OW', 'wfl', 'Overweight (OW)'],
          ['Ob', 'wfl', 'Obese (Ob)'],
      ];
      $den023 = max((int) ($wfa['Normal']['0-23']['T'] ?? 0)
          + (int) ($wfa['UW']['0-23']['T'] ?? 0)
          + (int) ($wfa['SUW']['0-23']['T'] ?? 0)
          + (int) ($wfa['OW']['0-23']['T'] ?? 0)
          + (int) ($wfa['OB']['0-23']['T'] ?? 0), 1);
      // Prefer measured 0-23 from totals when available
      $den023 = max((int) ($totals['age_0_23'] ?? 0), 1);
      $den059 = max((int) ($totals['measured'] ?? 0), 1);
      foreach ($nsRows as [$code, $matrixKey, $label]) {
          $matrix = $matrixKey === 'wfa' ? $wfa : ($matrixKey === 'hfa' ? $hfa : $wfl);
          $b023 = (int) ($matrix[$code]['0-23']['M'] ?? 0);
          $g023 = (int) ($matrix[$code]['0-23']['F'] ?? 0);
          $t023 = (int) ($matrix[$code]['0-23']['T'] ?? 0);
          $b059 = (int) ($matrix[$code]['0-59']['M'] ?? 0);
          $g059 = (int) ($matrix[$code]['0-59']['F'] ?? 0);
          $t059 = (int) ($matrix[$code]['0-59']['T'] ?? 0);
          echo '<tr>';
          echo '<td class="eopt-item">' . barangay_h($label) . '</td>';
          echo '<td class="eopt-num">' . $b023 . '</td><td class="eopt-num">' . $g023 . '</td><td class="eopt-num">' . $t023 . '</td>';
          echo '<td class="eopt-num">' . number_format(($t023 / $den023) * 100, 2) . '%</td>';
          echo '<td class="eopt-num">' . $b059 . '</td><td class="eopt-num">' . $g059 . '</td><td class="eopt-num">' . $t059 . '</td>';
          echo '<td class="eopt-num">' . number_format(($t059 / $den059) * 100, 2) . '%</td>';
          echo '</tr>';
      }
      ?>
    </tbody>
  </table>
</div>

<div class="eopt-section eopt-break">
  <div class="eopt-sheet-title">Graphs · Prevalence Summary</div>
  <div class="eopt-meta"><?= barangay_h($placeLine) ?> · Denominator = measured 0–59 month-old children</div>
  <table class="eopt-table">
    <thead>
      <tr><th>Nutritional Status</th><th>Counts</th><th>Denominator</th><th>Prevalence</th></tr>
    </thead>
    <tbody>
      <?php
      $prevRow('Moderately Wasted (MW/MAM)', $prevalence['mw'] ?? []);
      $prevRow('Severely Wasted (SW/SAM)', $prevalence['sw'] ?? []);
      $prevRow('Wasted (MW or SW)', $prevalence['wasted'] ?? []);
      $prevRow('Moderately Stunted (MSt)', $prevalence['st'] ?? []);
      $prevRow('Severely Stunted (SSt)', $prevalence['sst'] ?? []);
      $prevRow('Stunted (MSt or SSt)', $prevalence['stunted'] ?? []);
      $prevRow('Moderately Underweight (MUW)', $prevalence['uw'] ?? []);
      $prevRow('Severely Underweight (SUW)', $prevalence['suw'] ?? []);
      $prevRow('Underweight (MUW or SUW)', $prevalence['underweight'] ?? []);
      $prevRow('Overweight / Obese', $prevalence['ow_ob'] ?? []);
      ?>
    </tbody>
  </table>
</div>

<div class="eopt-section eopt-break">
  <div class="eopt-sheet-title">DQC Summary · Data Quality Check</div>
  <div class="eopt-meta"><?= barangay_h($placeLine) ?> · Completeness and distribution checks</div>
  <table class="eopt-table">
    <thead>
      <tr><th>Statistic</th><th>Weight (kg)</th><th>Height (cm)</th><th>Age (mos.)</th></tr>
    </thead>
    <tbody>
      <?php
      $wStat = $dqc['weight'] ?? [];
      $hStat = $dqc['height'] ?? [];
      $aStat = $dqc['age'] ?? [];
      foreach (['mean' => 'Mean', 'median' => 'Median', 'min' => 'Minimum', 'max' => 'Maximum', 'sd' => 'Std Dev'] as $key => $label) :
          ?>
      <tr>
        <td class="eopt-item"><?= barangay_h($label) ?></td>
        <td class="eopt-num"><?= $fmtNum($wStat[$key] ?? null) ?></td>
        <td class="eopt-num"><?= $fmtNum($hStat[$key] ?? null) ?></td>
        <td class="eopt-num"><?= $fmtNum($aStat[$key] ?? null) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <table class="eopt-table">
    <thead><tr><th>Completeness / Issues</th><th>Count</th></tr></thead>
    <tbody>
      <?php
      $missing = $dqc['missing'] ?? [];
      $dqRows = [
          'Children with no weight' => $missing['no_weight'] ?? 0,
          'Children with no length/height' => $missing['no_height'] ?? 0,
          'Weight but no height' => $missing['weight_no_height'] ?? 0,
          'Height but no weight' => $missing['height_no_weight'] ?? 0,
          'No sex data' => $missing['no_sex'] ?? 0,
          'No date of birth' => $missing['no_birth_date'] ?? 0,
          'No MUAC' => $missing['no_muac'] ?? 0,
          'No caregiver or address' => $missing['no_caregiver_or_address'] ?? 0,
          'Possible name+birthdate duplicates' => $dqc['duplicates'] ?? 0,
      ];
      foreach ($dqRows as $label => $count) :
          ?>
      <tr>
        <td class="eopt-item"><?= barangay_h($label) ?></td>
        <td class="eopt-num"><?= number_format((int) $count) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$monitorDefs = [
    ['key' => 'age_0_23', 'title' => 'Monitoring List — Children 0–23 Months Old', 'muac' => false],
    ['key' => 'mw', 'title' => 'Monitoring List — Moderately Wasted (MAM) 0–59 Months', 'muac' => false],
    ['key' => 'sw', 'title' => 'Monitoring List — Severely Wasted (SAM) 0–59 Months', 'muac' => false],
    ['key' => 'mst_sst', 'title' => 'Monitoring List — Moderately or Severely Stunted 0–59 Months', 'muac' => false],
    ['key' => 'ow_ob', 'title' => 'Monitoring List — Overweight or Obese 0–59 Months', 'muac' => false],
    ['key' => 'muw_suw_mst_sst', 'title' => 'Monitoring List — MUW/SUW + MSt/SSt 0–59 Months', 'muac' => false],
    ['key' => 'mst_sst_mw_sw', 'title' => 'Monitoring List — MSt/SSt + MW/SW 0–59 Months', 'muac' => false],
    ['key' => 'mst_sst_ow_ob', 'title' => 'Monitoring List — MSt/SSt + OW/Ob 0–59 Months', 'muac' => false],
    ['key' => 'muac', 'title' => 'Monitoring List — Children Measured Using MUAC', 'muac' => true],
    ['key' => 'uw', 'title' => 'List_UW — Moderately Underweight (MUW)', 'muac' => false],
    ['key' => 'suw', 'title' => 'List_SUW — Severely Underweight (SUW)', 'muac' => false],
];
foreach ($monitorDefs as $def) :
    $rows = $lists[$def['key']] ?? [];
    ?>
<div class="eopt-section eopt-break">
  <div class="eopt-sheet-title"><?= barangay_h($def['title']) ?></div>
  <div class="eopt-meta">
    <?= barangay_h($placeLine) ?> · Count: <strong><?= number_format(count($rows)) ?></strong> ·
    CY <?= (int) ($meta['calendar_year'] ?? date('Y')) ?>
  </div>
  <?php $monitorTable($rows, (bool) $def['muac']); ?>
</div>
<?php endforeach; ?>
