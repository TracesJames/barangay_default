<?php
$familyPrefix = $familyPrefix ?? 'add_';
$spouseRow = $spouseRow ?? [];
$dependents = $dependents ?? [];
?>
<div class="tab-pane fade" id="spouse-info" role="tabpanel" aria-labelledby="spouse-info-tab">
  <p class="lead text-center">Spouse Information</p>
  <div class="row">
    <div class="col-sm-12">
      <div class="form-group">
        <label>First Name</label>
        <input type="text" class="form-control residence-family-name" id="<?= $familyPrefix ?>spouse_first_name" name="<?= $familyPrefix ?>spouse_first_name" value="<?= barangay_h($spouseRow['spouse_first_name'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-12">
      <div class="form-group">
        <label>Middle Name</label>
        <input type="text" class="form-control residence-family-name" id="<?= $familyPrefix ?>spouse_middle_name" name="<?= $familyPrefix ?>spouse_middle_name" value="<?= barangay_h($spouseRow['spouse_middle_name'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-12">
      <div class="form-group">
        <label>Last Name</label>
        <input type="text" class="form-control residence-family-name" id="<?= $familyPrefix ?>spouse_last_name" name="<?= $familyPrefix ?>spouse_last_name" value="<?= barangay_h($spouseRow['spouse_last_name'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label>Suffix</label>
        <input type="text" class="form-control residence-family-name" id="<?= $familyPrefix ?>spouse_suffix" name="<?= $familyPrefix ?>spouse_suffix" value="<?= barangay_h($spouseRow['spouse_suffix'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label>Birth Date</label>
        <input type="date" class="form-control" id="<?= $familyPrefix ?>spouse_birth_date" name="<?= $familyPrefix ?>spouse_birth_date" value="<?= barangay_h($spouseRow['spouse_birth_date'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label>Occupation</label>
        <input type="text" class="form-control" id="<?= $familyPrefix ?>spouse_occupation" name="<?= $familyPrefix ?>spouse_occupation" value="<?= barangay_h($spouseRow['spouse_occupation'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label>Employer</label>
        <input type="text" class="form-control" id="<?= $familyPrefix ?>spouse_employer_name" name="<?= $familyPrefix ?>spouse_employer_name" value="<?= barangay_h($spouseRow['spouse_employer_name'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-12">
      <div class="form-group">
        <label>Contact Number</label>
        <input type="text" class="form-control residence-family-number" maxlength="11" id="<?= $familyPrefix ?>spouse_contact" name="<?= $familyPrefix ?>spouse_contact" value="<?= barangay_h($spouseRow['spouse_contact'] ?? '') ?>">
      </div>
    </div>
  </div>
  <div class="nr-tab-nav">
    <button type="button" class="btn btn-outline-secondary btn-sm nr-btn-prev"><i class="fas fa-arrow-left"></i> Back</button>
    <button type="button" class="btn btn-outline-primary btn-sm nr-btn-next">Next <i class="fas fa-arrow-right"></i></button>
  </div>
</div>

<div class="tab-pane fade" id="dependents-info" role="tabpanel" aria-labelledby="dependents-info-tab">
  <p class="lead text-center">Dependents</p>
  <div id="dependents-list" data-prefix="<?= barangay_h($familyPrefix) ?>">
    <?php if ($dependents === []): ?>
      <?php $dependents = [[]]; ?>
    <?php endif; ?>
    <?php foreach ($dependents as $dependent): ?>
      <?php include __DIR__ . '/residence_dependent_row.php'; ?>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-outline-light btn-sm mt-2" id="add-dependent-row">
    <i class="fas fa-plus"></i> Add Dependent
  </button>
  <div class="nr-tab-nav">
    <button type="button" class="btn btn-outline-secondary btn-sm nr-btn-prev"><i class="fas fa-arrow-left"></i> Back</button>
    <span class="text-muted small align-self-center">Ready to save? Use the button below.</span>
  </div>
</div>
