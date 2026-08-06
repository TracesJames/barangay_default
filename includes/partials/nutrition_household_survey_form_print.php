<?php

/**
 * Printable blank household nutrition survey form.
 *
 * Expected variables:
 * - $barangay, $district, $psgcCode, $reportHeader
 * - $relationshipOptions (array)
 * - $memberRows (int)
 * - $prefillPurok (string, optional)
 * - $certHeader (array)
 * - $nutritionOfficer (string, optional)
 * - $showActions (bool)
 */
$memberRows = max(3, min(12, (int) ($memberRows ?? 6)));
$relationshipOptions = $relationshipOptions ?? nutrition_relationship_options();
$prefillPurok = trim((string) ($prefillPurok ?? ''));
$nutritionOfficer = trim((string) ($nutritionOfficer ?? ''));
$showActions = (bool) ($showActions ?? true);
$certHeader = $certHeader ?? barangay_certificate_header(['barangay' => $barangay ?? '']);
$reportHeader = trim((string) ($reportHeader ?? ('Barangay ' . ($barangay ?? '') . ' Nutrition Profiling')));
$psgcCode = trim((string) ($psgcCode ?? ''));

$feedingOptions = [
    'Exclusive Breastfeeding',
    'Mixed Feeding',
    'Bottle Feeding',
    'Others',
];
?>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,600;0,700;0,800&display=swap');
  .nutrition-form-print { font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111; font-size: 12px; line-height: 1.35; }
  .nutrition-form-print h1 { font-size: 18px; margin: 0 0 4px; text-align: center; text-transform: uppercase; }
  .nutrition-form-print .form-subtitle { text-align: center; color: #444; margin-bottom: 14px; }
  .nutrition-form-print .form-meta { text-align: center; font-size: 11px; color: #555; margin-bottom: 16px; }
  .nutrition-form-print .section-title {
    font-size: 13px;
    font-weight: bold;
    margin: 14px 0 8px;
    padding: 4px 8px;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
  }
  .nutrition-form-print .field-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 8px 12px;
    margin-bottom: 8px;
  }
  .nutrition-form-print .field { min-height: 22px; }
  .nutrition-form-print .field label {
    display: block;
    font-size: 10px;
    color: #555;
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
  }
  .nutrition-form-print .field .line {
    display: block;
    border-bottom: 1px solid #111;
    min-height: 18px;
    padding-bottom: 2px;
  }
  .nutrition-form-print .field .line.prefill { font-weight: bold; }
  .nutrition-form-print .col-3 { grid-column: span 3; }
  .nutrition-form-print .col-4 { grid-column: span 4; }
  .nutrition-form-print .col-6 { grid-column: span 6; }
  .nutrition-form-print .col-8 { grid-column: span 8; }
  .nutrition-form-print .col-12 { grid-column: span 12; }
  .nutrition-form-print .checkbox-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    margin: 6px 0 10px;
  }
  .nutrition-form-print .checkbox-item { white-space: nowrap; }
  .nutrition-form-print .box { display: inline-block; width: 12px; height: 12px; border: 1px solid #111; margin-right: 4px; vertical-align: -2px; }
  .nutrition-form-print table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 11px; }
  .nutrition-form-print th, .nutrition-form-print td { border: 1px solid #999; padding: 5px 4px; vertical-align: top; }
  .nutrition-form-print th { background: #f3f4f6; text-align: left; font-size: 10px; }
  .nutrition-form-print .cell-blank { height: 28px; }
  .nutrition-form-print .notes {
    margin-top: 12px;
    padding: 8px 10px;
    border: 1px dashed #bbb;
    font-size: 10px;
    color: #555;
  }
  .nutrition-form-print .signature-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 22px;
  }
  .nutrition-form-print .signature-line {
    border-top: 1px solid #111;
    padding-top: 4px;
    text-align: center;
    font-size: 10px;
  }
  .nutrition-form-print .no-print {
    margin-bottom: 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  @media print {
    .nutrition-form-print .no-print { display: none !important; }
    .nutrition-form-print { font-size: 11px; }
    .nutrition-form-print .page-break { page-break-before: always; }
  }
</style>

<div class="nutrition-form-print">
  <?php if ($showActions) : ?>
  <div class="no-print">
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
    <a href="nutritionHouseholdSurveyFormExcel.php?layout=form<?= $prefillPurok !== '' ? '&amp;purok=' . rawurlencode($prefillPurok) : '' ?><?= $memberRows > 0 ? '&amp;members=' . (int) $memberRows : '' ?>">Download Excel Form</a>
    <a href="nutritionHouseholdSurveyForm.php?download=1<?= $prefillPurok !== '' ? '&amp;purok=' . rawurlencode($prefillPurok) : '' ?>">Download HTML File</a>
    <a href="nutritionHouseholdSurvey.php">Back to Survey</a>
  </div>
  <?php endif; ?>

  <h1>Survey Form (Family Profile)</h1>
  <div class="form-subtitle">City Nutrition Committee · C.Y. <?= date('Y') ?></div>
  <div class="form-meta">
    <?= barangay_h($certHeader['country']) ?> · <?= barangay_h($certHeader['province']) ?> · <?= barangay_h($certHeader['city']) ?><br>
    <?= barangay_h($certHeader['barangay_line']) ?><?= $district !== '' ? ' · ' . barangay_h($district) : '' ?>
    <?php if ($psgcCode !== '') : ?><br>Barangay PSGC: <strong><?= barangay_h($psgcCode) ?></strong><?php endif; ?>
    <?php if ($nutritionOfficer !== '') : ?><br>Nutrition Officer: <?= barangay_h($nutritionOfficer) ?><?php endif; ?>
  </div>

  <div class="section-title">I. Survey Information</div>
  <div class="field-grid">
    <div class="field col-6"><label>Name of Barangay</label><span class="line prefill"><?= barangay_h($barangay ?? '') ?></span></div>
    <div class="field col-6">
      <label>Purok</label>
      <span class="line<?= $prefillPurok !== '' ? ' prefill' : '' ?>"><?= $prefillPurok !== '' ? barangay_h($prefillPurok) : '&nbsp;' ?></span>
    </div>
    <div class="field col-6"><label>Name of BNS</label><span class="line">&nbsp;</span></div>
    <div class="field col-6"><label>Household No.</label><span class="line">&nbsp;</span></div>
    <div class="field col-6"><label>No. of Household members</label><span class="line">&nbsp;</span></div>
    <div class="field col-6"><label>Survey Date</label><span class="line">&nbsp;</span></div>
  </div>

  <div class="section-title">II. Household Head</div>
  <div class="field-grid">
    <div class="field col-3"><label>Last Name</label><span class="line">&nbsp;</span></div>
    <div class="field col-3"><label>First Name</label><span class="line">&nbsp;</span></div>
    <div class="field col-3"><label>Middle Name</label><span class="line">&nbsp;</span></div>
    <div class="field col-3"><label>Suffix</label><span class="line">&nbsp;</span></div>
    <div class="field col-4"><label>Birthday</label><span class="line">&nbsp;</span></div>
    <div class="field col-4">
      <label>Gender</label>
      <div class="checkbox-row">
        <span class="checkbox-item"><span class="box"></span> Male</span>
        <span class="checkbox-item"><span class="box"></span> Female</span>
      </div>
    </div>
    <div class="field col-4"><label>Occupation</label><span class="line">&nbsp;</span></div>
  </div>
  <div class="checkbox-row">
    <span class="checkbox-item"><span class="box"></span> 4P’s Member</span>
    <span class="checkbox-item"><span class="box"></span> IP’s Member</span>
    <span class="checkbox-item"><span class="box"></span> N/A</span>
    <span class="checkbox-item"><span class="box"></span> PWD</span>
    <span class="checkbox-item"><span class="box"></span> Solo Parent</span>
  </div>

  <div class="section-title">III. Living Conditions</div>
  <div class="field-grid">
    <div class="field col-6">
      <label><strong>Housing</strong></label>
    </div>
    <div class="field col-6">
      <label><strong>Sanitation</strong></label>
    </div>
    <div class="field col-6">
      <label>III-A. Type of House</label>
      <div class="checkbox-row">
        <?php foreach (nutrition_prf_house_ownership_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:4px;">Others, pls. specify: _________________</div>
    </div>
    <div class="field col-6">
      <label>III-B. Type of Toilet</label>
      <div class="checkbox-row">
        <?php foreach (nutrition_prf_toilet_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field col-6">
      <label>III-E. Types of Dwelling Unit</label>
      <div class="checkbox-row">
        <?php foreach (nutrition_prf_dwelling_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field col-6">
      <label>III-C. Type of Garbage Disposal</label>
      <div class="checkbox-row">
        <?php foreach (nutrition_prf_garbage_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:4px;font-size:10px;">If Uncollected:
        <?php foreach (nutrition_prf_garbage_uncollected_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field col-12">
      <label><strong>Water &amp; Food Security</strong></label>
    </div>
    <div class="field col-12">
      <label>III-D. Type of Water Source</label>
      <div class="checkbox-row">
        <?php foreach (nutrition_prf_water_source_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field col-12">
      <label>III-F. Food Production Activities (check all that apply)</label>
      <div class="checkbox-row">
        <?php foreach (nutrition_prf_food_production_activity_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field col-12">
      <label>III-G. Check below if:</label>
      <div class="checkbox-row">
        <span class="checkbox-item"><span class="box"></span> HH using Iodized Salt</span>
        <span class="checkbox-item"><span class="box"></span> HH using Products with Sangkap Pinoy Seal</span>
        <span class="checkbox-item"><span class="box"></span> HH with Carenderia/Eatery</span>
        <span class="checkbox-item"><span class="box"></span> HH with Sari-Sari Store</span>
      </div>
    </div>
  </div>

  <div class="section-title page-break">IV. Family Planning &amp; Feeding Practices</div>
  <div class="field-grid">
    <div class="field col-12">
      <label><strong>Family Planning</strong></label>
    </div>
    <div class="field col-12">
      <label>IV-A. Practices Family Planning</label>
      <div class="checkbox-row">
        <span class="checkbox-item"><span class="box"></span> Yes</span>
        <span class="checkbox-item"><span class="box"></span> No</span>
      </div>
      <div style="margin-top:4px;">If yes, check method(s):
        <?php foreach (nutrition_prf_family_planning_method_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="field col-12">
      <label><strong>Complementary Feeding (6–23 months)</strong></label>
    </div>
    <div class="field col-6">
      <label>IV-B. Common Meals Given</label>
      <div class="checkbox-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
        <?php foreach (nutrition_prf_complementary_meal_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:4px;">Others, pls. specify: _________________</div>
    </div>
    <div class="field col-6">
      <label>IV-C. Common Snacks Given</label>
      <div class="checkbox-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
        <?php foreach (nutrition_prf_complementary_snack_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:4px;">Others, pls. specify: _________________</div>
    </div>

    <div class="field col-12">
      <label><strong>Child Physical Activity</strong></label>
    </div>
    <div class="field col-12">
      <label>IV-D. Physical Activity of children (1–5 years old)</label>
      <div class="checkbox-row">
        <?php foreach (nutrition_prf_physical_activity_options() as $opt) : ?>
        <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:4px;">Others, pls. specify: _________________</div>
    </div>
  </div>

  <div class="section-title">V. Family Members</div>
  <p style="margin:0 0 6px;font-size:11px;">For children 0–5 years: record Weight (kg) and Height/Length (cm). WFA / HFA / WFH use boy/girl growth standards.</p>
  <table>
    <thead>
      <tr>
        <th style="width:16%;">Name</th>
        <th style="width:10%;">Relationship</th>
        <th style="width:7%;">Gender</th>
        <th style="width:9%;">Birthday</th>
        <th style="width:7%;">Pregnant</th>
        <th style="width:7%;">Lactating</th>
        <th style="width:7%;">Weight</th>
        <th style="width:7%;">Height</th>
        <th style="width:30%;">WFA / HFA / WFH · P/L feeding</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($i = 0; $i < $memberRows; $i++) : ?>
      <tr>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
        <td class="cell-blank"></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <div class="notes">
    <strong>Pregnant nutritional status:</strong>
    <?php foreach (nutrition_prf_pregnant_status_options() as $opt) : ?>
    <span class="checkbox-item"><span class="box"></span> <?= barangay_h($opt) ?></span>
    <?php endforeach; ?>.
    Feeding methods:
    <?php foreach ($feedingOptions as $idx => $option) : ?><?= $idx > 0 ? ', ' : '' ?><span class="box"></span> <?= barangay_h($option) ?><?php endforeach; ?>.
  </div>

  <div class="section-title">VI. Acknowledgement</div>
  <p style="margin:8px 0 14px;">I certify that the information provided above is true and correct to the best of my knowledge.</p>
  <div class="signature-grid">
    <div>
      <div style="height:36px;"></div>
      <div class="signature-line">Signature of Household Head</div>
    </div>
    <div>
      <div style="height:36px;"></div>
      <div class="signature-line">Date</div>
    </div>
    <div>
      <div style="height:36px;"></div>
      <div class="signature-line">BNS / Nutrition Officer</div>
    </div>
  </div>
</div>
