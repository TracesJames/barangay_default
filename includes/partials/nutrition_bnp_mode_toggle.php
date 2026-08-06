<?php

/**
 * BNP Individual / Consolidated mode radios for filter forms.
 *
 * Expected: $reportMode ('individual'|'consolidated')
 */
$reportMode = nutrition_bnp_normalize_mode($reportMode ?? ($_GET['mode'] ?? 'consolidated'));
?>
<div class="form-group col-md-3 mb-2">
  <label class="d-block">Report mode</label>
  <div class="d-flex flex-wrap align-items-center" style="gap:.75rem;min-height:38px;">
    <label class="mb-0 font-weight-normal">
      <input type="radio" name="mode" value="individual" <?= $reportMode === 'individual' ? 'checked' : '' ?>>
      Individual
    </label>
    <label class="mb-0 font-weight-normal">
      <input type="radio" name="mode" value="consolidated" <?= $reportMode === 'consolidated' ? 'checked' : '' ?>>
      Consolidated
    </label>
  </div>
</div>
