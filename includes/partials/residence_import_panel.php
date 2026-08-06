<?php
/** @var array<string, mixed>|null $importResult */
/** @var string $barangay */
/** @var string $processUrl */
/** @var string $templateUrl */
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-file-excel mr-2"></i>Import Residents from Excel</h3>
  </div>
  <div class="card-body">
  <?php if (!empty($importResult)): ?>
    <div class="alert <?= (int) ($importResult['failed'] ?? 0) > 0 ? 'alert-warning' : 'alert-success' ?>">
      <strong>Import complete.</strong>
      <?= (int) ($importResult['inserted'] ?? 0) ?> resident(s) added,
      <?= (int) ($importResult['failed'] ?? 0) ?> failed.
    </div>
    <?php if (!empty($importResult['errors'])): ?>
      <div class="alert alert-danger">
        <strong>Issues:</strong>
        <ul class="mb-0 pl-3">
          <?php foreach (array_slice($importResult['errors'], 0, 20) as $error): ?>
            <li><?= barangay_h($error) ?></li>
          <?php endforeach; ?>
          <?php if (count($importResult['errors']) > 20): ?>
            <li>...and <?= count($importResult['errors']) - 20 ?> more.</li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <p class="text-muted">
    Upload a spreadsheet for <strong><?= barangay_h($barangay) ?></strong>. Accepted formats: <code>.csv</code> and <code>.xlsx</code>.
    Residents are saved to this barangay only.
  </p>

  <div class="mb-4">
    <a href="<?= barangay_h($templateUrl) ?>" class="btn btn-outline-success">
      <i class="fas fa-download"></i> Download Excel Registration Form
    </a>
    <a href="allResidence.php" class="btn btn-outline-secondary ml-2">
      <i class="fas fa-list"></i> View All Residents
    </a>
  </div>

  <div class="table-responsive mb-4">
    <table class="table table-sm table-bordered">
      <thead class="bg-light">
        <tr>
          <th>Column</th>
          <th>Required</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>first_name, last_name, birth_date</td><td>Yes</td><td>birth_date: YYYY-MM-DD</td></tr>
        <tr><td>voters, pwd, single_parent, indigenous</td><td>No</td><td>YES or NO (default NO)</td></tr>
        <tr><td>purok</td><td>No</td><td>Must match an existing purok name in this barangay</td></tr>
        <tr><td>guardian / fathers_name / mothers_name</td><td>For minors</td><td>Required when age is under 18</td></tr>
      </tbody>
    </table>
  </div>

  <form action="<?= barangay_h($processUrl) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="import_file">Spreadsheet file</label>
      <input type="file" name="import_file" id="import_file" class="form-control-file" accept=".csv,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
    </div>
    <button type="submit" class="btn btn-success">
      <i class="fas fa-upload"></i> Upload and Import
    </button>
  </form>
  </div>
</div>
