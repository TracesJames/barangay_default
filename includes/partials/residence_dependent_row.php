<?php
$dependent = $dependent ?? [];
?>
<div class="dependent-row border rounded p-3 mb-3">
  <div class="row">
    <div class="col-sm-4">
      <div class="form-group mb-2">
        <label>First Name</label>
        <input type="text" class="form-control residence-family-name" name="<?= $familyPrefix ?>dependent_first_name[]" value="<?= barangay_h($dependent['first_name'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-4">
      <div class="form-group mb-2">
        <label>Middle Name</label>
        <input type="text" class="form-control residence-family-name" name="<?= $familyPrefix ?>dependent_middle_name[]" value="<?= barangay_h($dependent['middle_name'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-4">
      <div class="form-group mb-2">
        <label>Last Name</label>
        <input type="text" class="form-control residence-family-name" name="<?= $familyPrefix ?>dependent_last_name[]" value="<?= barangay_h($dependent['last_name'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group mb-2">
        <label>Suffix</label>
        <input type="text" class="form-control residence-family-name" name="<?= $familyPrefix ?>dependent_suffix[]" value="<?= barangay_h($dependent['suffix'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group mb-2">
        <label>Birth Date</label>
        <input type="date" class="form-control" name="<?= $familyPrefix ?>dependent_birth_date[]" value="<?= barangay_h($dependent['birth_date'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group mb-2">
        <label>Gender</label>
        <select class="form-control" name="<?= $familyPrefix ?>dependent_gender[]">
          <option value=""></option>
          <option value="Male" <?= (($dependent['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= (($dependent['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
        </select>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group mb-2">
        <label>Relationship</label>
        <input type="text" class="form-control" name="<?= $familyPrefix ?>dependent_relationship[]" value="<?= barangay_h($dependent['relationship'] ?? '') ?>" placeholder="Son, Daughter, etc.">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group mb-2">
        <label>Contact Number</label>
        <input type="text" class="form-control residence-family-number" maxlength="11" name="<?= $familyPrefix ?>dependent_contact_number[]" value="<?= barangay_h($dependent['contact_number'] ?? '') ?>">
      </div>
    </div>
    <div class="col-sm-6 text-right">
      <button type="button" class="btn btn-outline-danger btn-sm remove-dependent-row mt-4">
        <i class="fas fa-trash"></i> Remove
      </button>
    </div>
  </div>
</div>
