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

$appearanceDefaultLabel = $appearanceIsNutrition ? 'Forest Green' : 'Teal';
$appearanceAccents = [
    'default' => ['label' => $appearanceDefaultLabel, 'color' => $appearanceIsNutrition ? '#16a34a' : '#14b8a6'],
    'pastel-red' => ['label' => 'Coral', 'color' => '#FFADAD'],
    'pastel-orange' => ['label' => 'Peach', 'color' => '#FFD6A5'],
    'pastel-yellow' => ['label' => 'Yellow', 'color' => '#FDFFB6'],
    'pastel-green' => ['label' => 'Mint', 'color' => '#CAFFBF'],
    'pastel-cyan' => ['label' => 'Cyan', 'color' => '#9BF6FF'],
    'pastel-blue' => ['label' => 'Sky', 'color' => '#A0C4FF'],
    'pastel-purple' => ['label' => 'Lavender', 'color' => '#BDB2FF'],
    'pastel-pink' => ['label' => 'Pink', 'color' => '#FFC6FF'],
];

$appearanceTextSizes = [
    'normal' => ['label' => 'Default', 'sample' => '1rem'],
    'large' => ['label' => 'Large', 'sample' => '1.2rem'],
    'xlarge' => ['label' => 'Extra large', 'sample' => '1.45rem'],
];

$appearanceToggles = [
    'appearance_high_contrast' => ['icon' => 'fa-adjust', 'title' => 'High contrast', 'hint' => 'Stronger borders, brighter text, and clearer focus outlines.'],
    'appearance_underline_links' => ['icon' => 'fa-underline', 'title' => 'Underline links', 'hint' => 'Makes links easy to spot without relying on color.'],
    'appearance_reduce_motion' => ['icon' => 'fa-pause-circle', 'title' => 'Reduce motion', 'hint' => 'Minimize animations and transitions. Follows your device setting until you change it.'],
];
?>
<div class="card dashboard-panel appearance-panel mt-3" data-appearance-panel data-portal="<?= $appearanceIsNutrition ? 'nutrition' : 'barangay' ?>">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
    <h3 class="card-title mb-0">
      <i class="fas fa-palette mr-2"></i>Appearance &amp; Accessibility
    </h3>
    <span class="appearance-status ml-auto" data-appearance-status role="status" aria-live="polite">
      <i class="fas fa-laptop mr-1"></i>Saved in this browser
    </span>
  </div>
  <div class="card-body">
    <p class="appearance-intro mb-3">
      <?php if ($appearanceIsNutrition) : ?>
        <strong>Recommended:</strong> Forest Green matches the Nutrition Portal dark theme.
        Pastels only tint buttons and badges; the dark green shell stays for readability.
      <?php else : ?>
        Pick a dashboard color and accessibility options. Changes apply <strong>immediately</strong>
        across Barangay Hub and are remembered on this device.
      <?php endif; ?>
    </p>

    <div class="row">
      <div class="col-xl-7">
        <h6 class="appearance-section-title">
          <i class="fas fa-fill-drip mr-1"></i><?= $appearanceIsNutrition ? 'Dashboard color (Nutrition)' : 'Dashboard color' ?>
        </h6>
        <div class="accent-swatch-grid mb-4" role="radiogroup" aria-label="Dashboard accent color" data-accent-group>
          <?php foreach ($appearanceAccents as $key => $meta) : ?>
          <button type="button"
                  class="accent-swatch"
                  data-accent-choice="<?= barangay_h($key) ?>"
                  role="radio"
                  aria-checked="false"
                  tabindex="-1"
                  title="<?= barangay_h($meta['label'] . ' · ' . $meta['color']) ?>">
            <span class="accent-swatch-dot" style="background:<?= barangay_h($meta['color']) ?>">
              <i class="fas fa-check" aria-hidden="true"></i>
            </span>
            <span class="accent-swatch-label"><?= barangay_h($meta['label']) ?></span>
            <?php if ($key === 'default') : ?>
            <span class="accent-swatch-tag">Recommended</span>
            <?php endif; ?>
          </button>
          <?php endforeach; ?>
        </div>

        <h6 class="appearance-section-title"><i class="fas fa-text-height mr-1"></i>Text size</h6>
        <div class="text-size-group mb-4" role="radiogroup" aria-label="Text size">
          <?php foreach ($appearanceTextSizes as $value => $meta) : ?>
          <label class="text-size-option">
            <input type="radio" name="appearance_text_size" value="<?= barangay_h($value) ?>" class="sr-only">
            <span class="text-size-sample" style="font-size:<?= barangay_h($meta['sample']) ?>" aria-hidden="true">Aa</span>
            <span class="text-size-label"><?= barangay_h($meta['label']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <h6 class="appearance-section-title"><i class="fas fa-universal-access mr-1"></i>Accessibility</h6>
        <?php foreach ($appearanceToggles as $name => $meta) : ?>
        <label class="a11y-option" for="<?= barangay_h($name) ?>">
          <span class="a11y-option-icon"><i class="fas <?= barangay_h($meta['icon']) ?>" aria-hidden="true"></i></span>
          <span class="a11y-option-text">
            <strong><?= barangay_h($meta['title']) ?></strong>
            <small><?= barangay_h($meta['hint']) ?></small>
          </span>
          <span class="custom-control custom-switch a11y-option-switch">
            <input type="checkbox" class="custom-control-input" name="<?= barangay_h($name) ?>" id="<?= barangay_h($name) ?>">
            <span class="custom-control-label"><span class="sr-only"><?= barangay_h($meta['title']) ?></span></span>
          </span>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="col-xl-5">
        <h6 class="appearance-section-title"><i class="fas fa-eye mr-1"></i>Live preview</h6>
        <div class="appearance-preview" aria-hidden="true">
          <div class="appearance-preview-bar">
            <span class="appearance-preview-dot"></span>
            <span class="appearance-preview-title"><?= $appearanceIsNutrition ? 'Nutrition Portal' : 'Barangay Hub' ?></span>
            <span class="appearance-preview-chip" data-appearance-preview><?= barangay_h($appearanceDefaultLabel) ?></span>
          </div>
          <div class="appearance-preview-body">
            <div class="appearance-preview-heading">Household summary</div>
            <p class="appearance-preview-text">
              12 households surveyed this month. <a href="#" tabindex="-1">View report</a>
            </p>
            <div class="appearance-preview-stats">
              <div><strong>48</strong><span>Members</span></div>
              <div><strong>17</strong><span>Children</span></div>
              <div><strong>3</strong><span>At risk</span></div>
            </div>
            <div class="appearance-preview-actions">
              <span class="btn btn-sm btn-success">Save</span>
              <span class="btn btn-sm btn-outline-success">Cancel</span>
              <span class="badge badge-success">Active</span>
            </div>
          </div>
        </div>
        <small class="form-text text-muted mt-2">
          The preview updates as you change settings. Everything here only affects this browser.
        </small>
      </div>
    </div>

    <div class="appearance-actions">
      <button type="button" class="btn btn-outline-light btn-sm" data-appearance-reset>
        <i class="fas fa-undo mr-1"></i> Reset to <?= barangay_h($appearanceDefaultLabel) ?> &amp; defaults
      </button>
    </div>
  </div>
</div>
