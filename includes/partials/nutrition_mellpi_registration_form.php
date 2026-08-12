<?php

/**
 * Shared MELLPI PRO FORM CM registration fields.
 * Expects: $profile, $preview, $years, $community, $popSnap, $preschool, $school,
 *          $pregnantStatus, $bns, $hazards, $landUse, $field (callable),
 *          $mellpiScope ('city'|'barangay'), $mellpiUnitLabel (string)
 */
$mellpiScope = $mellpiScope ?? 'city';
$mellpiUnitLabel = $mellpiUnitLabel ?? ($mellpiScope === 'barangay' ? 'Barangay' : 'City/Municipality');
$renderYearMatrix = $renderYearMatrix ?? static function (string $prefix, array $rows, array $data, array $years) use ($field): void {
    echo '<div class="table-responsive"><table class="table table-sm table-bordered">';
    echo '<thead><tr><th>Indicator</th>';
    foreach ($years as $y) {
        echo '<th class="text-center">' . (int) $y . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $lab) {
        echo '<tr><td>' . barangay_h($lab) . '</td>';
        foreach ($years as $y) {
            echo '<td>';
            $field($prefix . '[' . $lab . '][' . $y . ']', $data[$lab][$y] ?? '', 'text', 'mellpi-year-input mx-auto');
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};
?>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Header</h3></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4 form-group">
        <label><?= barangay_h($mellpiUnitLabel) ?></label>
        <?php $field('city_name', $profile['city_name'] ?? ''); ?>
      </div>
      <div class="col-md-4 form-group">
        <label>Province</label>
        <?php $field('province', $profile['province'] ?? 'Bukidnon'); ?>
      </div>
      <div class="col-md-4 form-group">
        <label>Income Class</label>
        <?php $field('income_class', $profile['income_class'] ?? ''); ?>
      </div>
      <div class="col-md-4 form-group">
        <label>Date of Monitoring</label>
        <?php $field('date_of_monitoring', $profile['date_of_monitoring'] ?? date('Y-m-d'), 'date'); ?>
      </div>
      <div class="col-md-4 form-group">
        <label>Period Covered</label>
        <?php $field('period_covered', $profile['period_covered'] ?? ('CY ' . date('Y'))); ?>
      </div>
    </div>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Community Profile</h3></div>
  <div class="card-body">
    <div class="row">
      <?php
      $communityFields = [
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
          'pct_at_risk_pregnant' => '% nutritionally at-risk pregnant women',
          'pct_exclusive_bf_5th_month' => '% exclusive breastfeeding until 5th month',
          'idd_pregnant' => 'IDD Prevalence (Pregnant)',
          'idd_lactating' => 'IDD Prevalence (Lactating)',
          'terrain' => 'Terrain',
      ];
      foreach ($communityFields as $key => $label) :
          ?>
      <div class="col-md-6 form-group">
        <label><?= barangay_h($label) ?></label>
        <?php $field('community[' . $key . ']', $community[$key] ?? ''); ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="mellpi-reg-hint mb-0">Safe water and sanitary toilet counts auto-fill from household surveys when left blank.</p>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Population Snapshot (Estimated / Actual)</h3></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead><tr><th></th><th>Estimated</th><th>Actual</th></tr></thead>
        <tbody>
          <tr>
            <td>0–59 months</td>
            <td><?php $field('population_snapshot[0_59_estimated]', $popSnap['0_59_estimated'] ?? ''); ?></td>
            <td><?php $field('population_snapshot[0_59_actual]', $popSnap['0_59_actual'] ?? ''); ?></td>
          </tr>
          <tr>
            <td>Pregnant</td>
            <td><?php $field('population_snapshot[pregnant_estimated]', $popSnap['pregnant_estimated'] ?? ''); ?></td>
            <td><?php $field('population_snapshot[pregnant_actual]', $popSnap['pregnant_actual'] ?? ''); ?></td>
          </tr>
          <tr>
            <td>Lactating</td>
            <td><?php $field('population_snapshot[lactating_estimated]', $popSnap['lactating_estimated'] ?? ''); ?></td>
            <td><?php $field('population_snapshot[lactating_actual]', $popSnap['lactating_actual'] ?? ''); ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Preschool Nutritional Status (0–59 months)</h3></div>
  <div class="card-body">
    <h6>Weight-for-Age</h6>
    <?php $renderYearMatrix('preschool[wfa]', ['Normal', 'Underweight', 'Severely Underweight', 'Overweight'], $preschool['wfa'] ?? [], $years); ?>
    <h6 class="mt-3">Weight-for-Height/Length</h6>
    <?php $renderYearMatrix('preschool[wfh]', ['Normal', 'Wasted', 'Severely Wasted', 'Overweight', 'Obese'], $preschool['wfh'] ?? [], $years); ?>
    <h6 class="mt-3">Height-for-Age</h6>
    <?php $renderYearMatrix('preschool[hfa]', ['Normal', 'Stunted', 'Severely Stunted', 'Tall'], $preschool['hfa'] ?? [], $years); ?>
    <p class="mellpi-reg-hint mb-0">Current year blanks are filled from e-OPT Plus <?= $mellpiScope === 'barangay' ? 'barangay' : 'city' ?> data when generating the report.</p>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">School Children Nutritional Status</h3></div>
  <div class="card-body">
    <?php $renderYearMatrix('school', ['Normal', 'Wasted', 'Severely Wasted', 'Overweight', 'Obese'], $school, $years); ?>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Pregnant Women Nutritional Status</h3></div>
  <div class="card-body">
    <?php $renderYearMatrix('pregnant_status', ['Normal', 'Nutritionally at-risk', 'Overweight', 'Obese'], $pregnantStatus, $years); ?>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Barangay Nutrition Scholars (BNS)</h3></div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4 form-group">
        <label>Total No. of BNS</label>
        <?php $field('bns[total]', $bns['total'] ?? ''); ?>
      </div>
      <div class="col-md-4 form-group">
        <label>New</label>
        <?php $field('bns[new]', $bns['new'] ?? ''); ?>
      </div>
      <div class="col-md-4 form-group">
        <label>Existing</label>
        <?php $field('bns[existing]', $bns['existing'] ?? ''); ?>
      </div>
    </div>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Hazards</h3></div>
  <div class="card-body">
    <?php for ($i = 0; $i < 5; $i++) :
        $hz = $hazards[$i] ?? ['type_month' => '', 'affected' => ''];
        ?>
    <div class="row">
      <div class="col-md-6 form-group">
        <label>Hazards (Type/Month) #<?= $i + 1 ?></label>
        <?php $field('hazards[' . $i . '][type_month]', $hz['type_month'] ?? ''); ?>
      </div>
      <div class="col-md-6 form-group">
        <label>LGU / Households affected</label>
        <?php $field('hazards[' . $i . '][affected]', $hz['affected'] ?? ''); ?>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<div class="card nutrition-panel mellpi-section-card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Land Use Classification</h3></div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead>
          <tr><th>Classification</th><th>Land Area</th><th>Bgy Covered</th><th>Remarks</th></tr>
        </thead>
        <tbody>
          <?php foreach ([
              'Residential',
              'Commercial',
              'Industrial',
              'Agricultural',
              'Forest land/Mineral land/National Park',
          ] as $lu) :
              $row = $landUse[$lu] ?? ['land_area' => '', 'bgy_covered' => '', 'remarks' => ''];
              ?>
          <tr>
            <td><?= barangay_h($lu) ?></td>
            <td><?php $field('land_use[' . $lu . '][land_area]', $row['land_area'] ?? ''); ?></td>
            <td><?php $field('land_use[' . $lu . '][bgy_covered]', $row['bgy_covered'] ?? ''); ?></td>
            <td><?php $field('land_use[' . $lu . '][remarks]', $row['remarks'] ?? ''); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
