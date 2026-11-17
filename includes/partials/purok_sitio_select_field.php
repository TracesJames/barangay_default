<?php
/**
 * Locked Purok / Sitio select from master `purok` list.
 *
 * Expects:
 * - $con (mysqli)
 * - $purokSelectBarangayId (string)
 * Optional:
 * - $purokSelectUserId (string) for BNS filtering
 * - $purokSelectName (default add_purok_id)
 * - $purokSelectSelected (purok_id or label)
 * - $purokSelectRequired (bool, default true)
 * - $purokSelectLabel (default "Purok / Sitio")
 * - $purokSelectId (default add_purok_id)
 */
$purokSelectBarangayId = (string) ($purokSelectBarangayId ?? '');
$purokSelectUserId = (string) ($purokSelectUserId ?? ($_SESSION['user_id'] ?? ''));
$purokSelectName = (string) ($purokSelectName ?? 'add_purok_id');
$purokSelectId = (string) ($purokSelectId ?? $purokSelectName);
$purokSelectSelected = trim((string) ($purokSelectSelected ?? ''));
$purokSelectRequired = isset($purokSelectRequired) ? (bool) $purokSelectRequired : true;
$purokSelectLabel = (string) ($purokSelectLabel ?? 'Purok / Sitio');

$purokOptions = [];
if ($purokSelectBarangayId !== '' && function_exists('barangay_purok_options_for_user')) {
    $purokOptions = barangay_purok_options_for_user($con, $purokSelectUserId, $purokSelectBarangayId);
} elseif ($purokSelectBarangayId !== '' && function_exists('barangay_list_puroks')) {
    foreach (barangay_list_puroks($con, $purokSelectBarangayId) as $row) {
        $label = trim((string) ($row['purok'] ?? ''));
        if ($label === '') {
            continue;
        }
        $purokOptions[] = [
            'purok_id' => trim((string) ($row['purok_id'] ?? '')),
            'value' => $label,
            'label' => $label,
        ];
    }
}
?>
<label for="<?= barangay_h($purokSelectId) ?>"<?= $purokSelectRequired ? ' class="nr-required"' : '' ?>>
  <?= barangay_h($purokSelectLabel) ?><?= $purokSelectRequired ? ' <span class="text-danger">*</span>' : '' ?>
</label>
<select
  class="form-control"
  id="<?= barangay_h($purokSelectId) ?>"
  name="<?= barangay_h($purokSelectName) ?>"
  <?= $purokSelectRequired ? 'required' : '' ?>
>
  <option value="">Select Purok / Sitio…</option>
  <?php foreach ($purokOptions as $opt) :
      $optId = trim((string) ($opt['purok_id'] ?? ''));
      $optLabel = trim((string) ($opt['label'] ?? $opt['value'] ?? ''));
      if ($optLabel === '') {
          continue;
      }
      $optValue = $optId !== '' ? $optId : $optLabel;
      $selected = $purokSelectSelected !== ''
          && (strcasecmp($purokSelectSelected, $optValue) === 0
              || strcasecmp($purokSelectSelected, $optLabel) === 0
              || strcasecmp($purokSelectSelected, $optId) === 0);
      ?>
  <option value="<?= barangay_h($optValue) ?>"<?= $selected ? ' selected' : '' ?>><?= barangay_h($optLabel) ?></option>
  <?php endforeach; ?>
</select>
<?php if ($purokOptions === []) : ?>
<small class="text-danger d-block mt-1">No Purok / Sitio configured for this barangay yet.</small>
<?php else : ?>
<small class="text-muted d-block mt-1">Locked to the official barangay list.</small>
<?php endif; ?>
