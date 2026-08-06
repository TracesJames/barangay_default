<?php
/**
 * Purok filter dropdown for resident lists (scoped to active barangay).
 *
 * @var string $purokFilterId
 * @var array<int, array{value:string,label:string}> $barangayPurokOptions
 */
if (!function_exists('barangay_h')) {
    require_once __DIR__ . '/../helpers.php';
}

$purokFilterId = $purokFilterId ?? 'purok';
$barangayPurokOptions = $barangayPurokOptions ?? [];
?>
<div class="col-sm-4">
  <div class="input-group mb-3">
    <div class="input-group-prepend">
      <span class="input-group-text bg-indigo">PUROK</span>
    </div>
    <select name="<?= barangay_h($purokFilterId) ?>" id="<?= barangay_h($purokFilterId) ?>" class="form-control">
      <option value="">--SELECT PUROK--</option>
      <?php foreach ($barangayPurokOptions as $purokOption) : ?>
      <option value="<?= barangay_h($purokOption['value']) ?>"><?= barangay_h($purokOption['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
