<?php

/**
 * Manual entry for BNP Form C1 items 16–20.
 *
 * Expects: $formC1Settings (array), $c1SchoolReadonly (bool, optional)
 */
$formC1Settings = is_array($formC1Settings ?? null) ? $formC1Settings : [];
$c1SchoolReadonly = !empty($c1SchoolReadonly);
$c1Val = static function (string $key) use ($formC1Settings): string {
    return barangay_h((string) ($formC1Settings[$key] ?? ''));
};
$c1Input = static function (string $key, string $extraClass = '') use ($c1Val, $c1SchoolReadonly): void {
    $readonly = $c1SchoolReadonly ? ' readonly' : '';
    echo '<input type="text" class="form-control form-control-sm text-center bnp-c1-input ' . barangay_h($extraClass) . '" name="bnp_c1_'
        . barangay_h($key) . '" value="' . $c1Val($key) . '" inputmode="decimal"' . $readonly . '>';
};
?>
<div class="bnp-c1-school-entry">
  <style>
    .bnp-c1-school-entry .bnp-c1-table { width:100%; border-collapse:collapse; background:#fff; color:#111; margin:0; }
    .bnp-c1-school-entry .bnp-c1-table th,
    .bnp-c1-school-entry .bnp-c1-table td { border:1px solid #444; padding:.4rem .55rem; vertical-align:middle; }
    .bnp-c1-school-entry .bnp-c1-table thead th:first-child { background:#eef6ee; text-align:left; }
    .bnp-c1-school-entry .bnp-c1-table thead th { background:#fff; text-align:center; font-weight:700; width:18%; }
    body.dark-mode .bnp-c1-school-entry .form-control.bnp-c1-input,
    .bnp-c1-school-entry .form-control.bnp-c1-input {
      border:1px solid #cbd5e1;
      box-shadow:none;
      background:#fff;
      color:#111;
      min-width:4.5rem;
    }
    body.dark-mode .bnp-c1-school-entry .form-control.bnp-c1-input:focus,
    .bnp-c1-school-entry .form-control.bnp-c1-input:focus { background:#fff; color:#111; border-color:#166534; }
    body.dark-mode .bnp-c1-school-entry .form-control.bnp-c1-input[readonly],
    .bnp-c1-school-entry .form-control.bnp-c1-input[readonly] { background:#f8fafc; color:#111; }
  </style>
  <div class="table-responsive">
    <table class="bnp-c1-table">
      <thead>
        <tr>
          <th>16. Total number of Educational Institutions</th>
          <th>Public</th>
          <th>Private</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>&nbsp;&nbsp;&nbsp;a. Number of Day Care Centers</td>
          <td><?php $c1Input('daycare_public'); ?></td>
          <td><?php $c1Input('daycare_private'); ?></td>
        </tr>
        <tr>
          <td>&nbsp;&nbsp;&nbsp;b. Number of Elementary Schools</td>
          <td><?php $c1Input('elementary_public'); ?></td>
          <td><?php $c1Input('elementary_private'); ?></td>
        </tr>
        <tr>
          <td>17. Total number of children enrolled in kindergarten (DepEd-supervised)</td>
          <td colspan="2"><?php $c1Input('kindergarten'); ?></td>
        </tr>
        <tr>
          <td>18. Total number of school children</td>
          <td colspan="2"><?php $c1Input('grade1', 'c1-school-children'); ?></td>
        </tr>
        <tr>
          <td>19. Total number of school children weighed at the start of the school year</td>
          <td colspan="2"><?php $c1Input('school_weighed', 'c1-school-weighed'); ?></td>
        </tr>
        <tr>
          <td>20. Percent (%) weighing coverage of school weighing</td>
          <td colspan="2"><?php $c1Input('school_weighing_pct', 'c1-school-pct'); ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<?php if (!$c1SchoolReadonly) : ?>
<script>
document.addEventListener('input', function (event) {
  var target = event.target;
  if (!target || !target.classList) return;
  if (!target.classList.contains('c1-school-children') && !target.classList.contains('c1-school-weighed')) return;
  var root = target.closest('.bnp-c1-school-entry');
  if (!root) return;
  var children = root.querySelector('.c1-school-children');
  var weighed = root.querySelector('.c1-school-weighed');
  var pct = root.querySelector('.c1-school-pct');
  if (!children || !weighed || !pct || document.activeElement === pct) return;
  var total = parseFloat(String(children.value).replace(/,/g, ''));
  var done = parseFloat(String(weighed.value).replace(/,/g, ''));
  if (!isFinite(total) || total <= 0 || !isFinite(done) || done < 0) return;
  pct.value = String(Math.round((done / total) * 1000) / 10);
});
</script>
<?php endif; ?>
