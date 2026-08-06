<?php
/**
 * Appearance & accessibility controls.
 * Uses localStorage via assets/js/appearance-prefs.js
 *
 * On Nutrition Portal: Default = Forest Green (dark theme).
 * On Barangay Portal: Default = Teal.
 */
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$appearanceIsNutrition = !empty($appearanceNutritionPortal)
    || strpos($scriptName, 'nutrition') !== false
    || strpos($scriptName, 'Nutrition') !== false;

$appearanceAccents = $appearanceIsNutrition
    ? [
        'default' => ['label' => 'Forest Green', 'color' => '#16a34a'],
        'pastel-red' => ['label' => 'Coral', 'color' => '#FFADAD'],
        'pastel-orange' => ['label' => 'Peach', 'color' => '#FFD6A5'],
        'pastel-yellow' => ['label' => 'Yellow', 'color' => '#FDFFB6'],
        'pastel-green' => ['label' => 'Mint', 'color' => '#CAFFBF'],
        'pastel-cyan' => ['label' => 'Cyan', 'color' => '#9BF6FF'],
        'pastel-blue' => ['label' => 'Sky', 'color' => '#A0C4FF'],
        'pastel-purple' => ['label' => 'Lavender', 'color' => '#BDB2FF'],
        'pastel-pink' => ['label' => 'Pink', 'color' => '#FFC6FF'],
    ]
    : [
        'default' => ['label' => 'Default', 'color' => '#14b8a6'],
        'pastel-red' => ['label' => 'Coral', 'color' => '#FFADAD'],
        'pastel-orange' => ['label' => 'Peach', 'color' => '#FFD6A5'],
        'pastel-yellow' => ['label' => 'Yellow', 'color' => '#FDFFB6'],
        'pastel-green' => ['label' => 'Mint', 'color' => '#CAFFBF'],
        'pastel-cyan' => ['label' => 'Cyan', 'color' => '#9BF6FF'],
        'pastel-blue' => ['label' => 'Sky', 'color' => '#A0C4FF'],
        'pastel-purple' => ['label' => 'Lavender', 'color' => '#BDB2FF'],
        'pastel-pink' => ['label' => 'Pink', 'color' => '#FFC6FF'],
    ];
?>
<div class="card dashboard-panel appearance-panel mt-3" data-appearance-panel<?= $appearanceIsNutrition ? ' data-portal="nutrition"' : ' data-portal="barangay"' ?>>
  <div class="card-header">
    <h3 class="card-title mb-0">
      <i class="fas fa-palette mr-2"></i>Appearance &amp; Accessibility
    </h3>
  </div>
  <div class="card-body">
    <p class="text-muted mb-3" style="font-size:0.9rem;">
      <?php if ($appearanceIsNutrition) : ?>
        <strong>Recommended:</strong> Forest Green — matches the Nutrition Portal dark theme.
        Pastels only tint buttons and badges; the dark green shell stays for readability.
      <?php else : ?>
        Choose a dashboard color and accessibility options. Changes apply <strong>immediately</strong>
        across Barangay Hub (navbar, sidebar, buttons, cards). Preferences are saved in this browser.
      <?php endif; ?>
    </p>

    <h6 class="text-uppercase mb-2" style="letter-spacing:0.06em;font-size:0.75rem;opacity:0.85;">
      <?= $appearanceIsNutrition ? 'Dashboard color (Nutrition)' : 'Dashboard color (Pastel palette)' ?>
    </h6>
    <div class="appearance-accent-preview mb-3" data-appearance-preview role="status" aria-live="polite">
      <?= $appearanceIsNutrition ? 'Forest Green · #16a34a' : 'Default · #14b8a6' ?>
    </div>
    <div class="accent-swatch-grid mb-4" role="listbox" aria-label="Dashboard accent color">
      <?php foreach ($appearanceAccents as $key => $meta) : ?>
      <button type="button"
              class="accent-swatch"
              data-accent-choice="<?= barangay_h($key) ?>"
              role="option"
              aria-label="<?= barangay_h($meta['label']) ?> theme">
        <span class="accent-swatch-dot" style="background:<?= barangay_h($meta['color']) ?>"></span>
        <span class="accent-swatch-label"><?= barangay_h($meta['label']) ?></span>
      </button>
      <?php endforeach; ?>
    </div>

    <h6 class="text-uppercase mb-2" style="letter-spacing:0.06em;font-size:0.75rem;opacity:0.85;">Accessibility</h6>

    <div class="form-group mb-3">
      <label for="appearance_text_size">Text size</label>
      <select name="appearance_text_size" id="appearance_text_size" class="form-control" style="max-width:280px;">
        <option value="normal">Default</option>
        <option value="large">Large</option>
        <option value="xlarge">Extra large</option>
      </select>
      <small class="form-text text-muted">Increases UI text for easier reading.</small>
    </div>

    <div class="a11y-option">
      <input type="checkbox" name="appearance_high_contrast" id="appearance_high_contrast" class="mt-1">
      <label for="appearance_high_contrast">
        <strong>High contrast</strong>
        <small>Stronger borders, brighter text, and clearer focus outlines.</small>
      </label>
    </div>

    <div class="a11y-option">
      <input type="checkbox" name="appearance_reduce_motion" id="appearance_reduce_motion" class="mt-1">
      <label for="appearance_reduce_motion">
        <strong>Reduce motion</strong>
        <small>Minimize animations and transitions.</small>
      </label>
    </div>

    <div class="appearance-actions">
      <button type="button" class="btn btn-outline-light btn-sm" data-appearance-reset>
        <i class="fas fa-undo mr-1"></i> Reset to <?= $appearanceIsNutrition ? 'Forest Green' : 'default' ?>
      </button>
    </div>
  </div>
</div>
