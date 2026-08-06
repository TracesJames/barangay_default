<?php

/**
 * Official BNP form header checkboxes for Individual / Consolidated.
 *
 * Expected:
 * - $reportMode
 * - $modeSelectable (bool) — when true, boxes can be toggled (mutually exclusive)
 * - $bnpModeSwitchQuery (optional array) — query params preserved when switching mode
 */
$reportMode = nutrition_bnp_normalize_mode($reportMode ?? 'consolidated');
$modeSelectable = !empty($modeSelectable);
$bnpModeSwitchQuery = is_array($bnpModeSwitchQuery ?? null) ? $bnpModeSwitchQuery : [];

$bnpModeHref = static function (string $mode) use ($bnpModeSwitchQuery): string {
    $query = $bnpModeSwitchQuery;
    $query['mode'] = $mode;
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'nutritionBnpReport.php'));

    return $script . '?' . http_build_query(array_filter(
        $query,
        static fn ($v) => $v !== null && $v !== ''
    ));
};
?>
<div class="bnp-mode"<?= $modeSelectable ? ' data-bnp-mode-switch="1"' : '' ?>>
  <label class="bnp-mode-option">
    <input type="checkbox"
           class="bnp-mode-check"
           data-mode="individual"
           <?= $reportMode === 'individual' ? 'checked' : '' ?>
           <?= $modeSelectable ? '' : 'disabled' ?>
           <?= $modeSelectable ? 'data-href="' . barangay_h($bnpModeHref('individual')) . '"' : '' ?>>
    Individual
  </label>
  <label class="bnp-mode-option ml-3">
    <input type="checkbox"
           class="bnp-mode-check"
           data-mode="consolidated"
           <?= $reportMode === 'consolidated' ? 'checked' : '' ?>
           <?= $modeSelectable ? '' : 'disabled' ?>
           <?= $modeSelectable ? 'data-href="' . barangay_h($bnpModeHref('consolidated')) . '"' : '' ?>>
    Consolidated
  </label>
</div>
<?php if ($modeSelectable) : ?>
<script>
(function () {
  if (window.__bnpModeSwitchBound) return;
  window.__bnpModeSwitchBound = true;
  document.addEventListener('change', function (e) {
    var input = e.target;
    if (!input || !input.classList || !input.classList.contains('bnp-mode-check')) return;
    var wrap = input.closest('[data-bnp-mode-switch]');
    if (!wrap) return;
    var mode = input.getAttribute('data-mode') || 'consolidated';
    // Unchecking the active box switches to the other mode.
    if (!input.checked) {
      mode = mode === 'consolidated' ? 'individual' : 'consolidated';
    }
    var target = wrap.querySelector('.bnp-mode-check[data-mode="' + mode + '"]');
    var href = target && target.getAttribute('data-href');
    if (href) {
      window.location.href = href;
    }
  });
})();
</script>
<?php endif; ?>
