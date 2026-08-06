<?php
$row = $row_view_residence ?? [];
$dependents = $dependents ?? [];
$familyReadOnly = !empty($familyReadOnly);
$disabledAttr = $familyReadOnly ? ' disabled' : '';
$familyPrefix = 'edit_';
?>
<fieldset class="mt-3">
  <legend>Dependents</legend>
  <div id="dependents-list" data-prefix="<?= barangay_h($familyPrefix) ?>">
    <?php if ($dependents === []): ?>
      <?php $dependents = [[]]; ?>
    <?php endif; ?>
    <?php foreach ($dependents as $dependent): ?>
      <div class="dependent-row border rounded p-3 mb-3">
        <div class="row">
          <div class="col-sm-4">
            <label>First Name</label>
            <input type="text" class="editInfo form-control form-control-sm residence-family-name" name="<?= $familyPrefix ?>dependent_first_name[]" value="<?= barangay_h($dependent['first_name'] ?? '') ?>"<?= $disabledAttr ?>>
          </div>
          <div class="col-sm-4">
            <label>Middle Name</label>
            <input type="text" class="editInfo form-control form-control-sm residence-family-name" name="<?= $familyPrefix ?>dependent_middle_name[]" value="<?= barangay_h($dependent['middle_name'] ?? '') ?>"<?= $disabledAttr ?>>
          </div>
          <div class="col-sm-4">
            <label>Last Name</label>
            <input type="text" class="editInfo form-control form-control-sm residence-family-name" name="<?= $familyPrefix ?>dependent_last_name[]" value="<?= barangay_h($dependent['last_name'] ?? '') ?>"<?= $disabledAttr ?>>
          </div>
          <div class="col-sm-3">
            <label>Suffix</label>
            <input type="text" class="editInfo form-control form-control-sm residence-family-name" name="<?= $familyPrefix ?>dependent_suffix[]" value="<?= barangay_h($dependent['suffix'] ?? '') ?>"<?= $disabledAttr ?>>
          </div>
          <div class="col-sm-3">
            <label>Birth Date</label>
            <input type="date" class="editInfo form-control form-control-sm" name="<?= $familyPrefix ?>dependent_birth_date[]" value="<?= barangay_h($dependent['birth_date'] ?? '') ?>"<?= $disabledAttr ?>>
          </div>
          <div class="col-sm-3">
            <label>Gender</label>
            <select class="editInfo form-control form-control-sm" name="<?= $familyPrefix ?>dependent_gender[]"<?= $disabledAttr ?>>
              <option value=""></option>
              <option value="Male" <?= (($dependent['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= (($dependent['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
            </select>
          </div>
          <div class="col-sm-3">
            <label>Relationship</label>
            <input type="text" class="editInfo form-control form-control-sm" name="<?= $familyPrefix ?>dependent_relationship[]" value="<?= barangay_h($dependent['relationship'] ?? '') ?>"<?= $disabledAttr ?>>
          </div>
          <div class="col-sm-6">
            <label>Contact Number</label>
            <input type="text" maxlength="11" class="editInfo form-control form-control-sm residence-family-number" name="<?= $familyPrefix ?>dependent_contact_number[]" value="<?= barangay_h($dependent['contact_number'] ?? '') ?>"<?= $disabledAttr ?>>
          </div>
          <?php if (!$familyReadOnly): ?>
          <div class="col-sm-6 text-right">
            <button type="button" class="btn btn-outline-danger btn-sm remove-dependent-row mt-4">
              <i class="fas fa-trash"></i> Remove
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$familyReadOnly): ?>
  <button type="button" class="btn btn-outline-light btn-sm" id="add-dependent-row">
    <i class="fas fa-plus"></i> Add Dependent
  </button>
  <?php endif; ?>
</fieldset>
