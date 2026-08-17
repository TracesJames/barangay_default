<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activePage = 'settings';
$nutritionPageTitle = 'Settings';
$canSaveNutritionSettings = nutrition_user_can_save_settings($con, (string) ($_SESSION['user_id'] ?? ''));
$nutritionIncludeScriptsCsrf = true;
$nutritionExtraCss = ['../assets/plugins/sweetalert2/css/sweetalert2.min.css'];
$nutritionExtraJs = [
    '../assets/plugins/sweetalert2/js/sweetalert2.all.min.js',
    '../assets/js/barangay-ui.js',
];

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
?>
        <?php
        $nutritionPageIcon = 'fa-cog';
        $nutritionPageHeading = 'Settings';
        $nutritionPageDescription = 'Configure nutrition officer details, PSGC, assessment frequency, and KoBoToolbox integration for ' . $barangay . '.';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card nutrition-panel">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Nutrition Profiling Settings</h3>
              </div>
              <form id="nutritionSettingsForm">
                <?= csrf_field(); ?>
                <div class="card-body">
                  <?php if (!$canSaveNutritionSettings) : ?>
                  <p class="alert alert-info py-2">View only — Nutrition Admin (A) cannot change settings.</p>
                  <?php endif; ?>
                  <p class="text-muted">Configure nutrition profiling options for <strong><?= barangay_h($barangay) ?></strong>.</p>
                  <div class="form-group">
                    <label>Nutrition Officer Name</label>
                    <input type="text" class="form-control" name="nutrition_officer" value="<?= barangay_h($nutritionSettings['nutrition_officer'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" class="form-control" name="contact_number" value="<?= barangay_h($nutritionSettings['contact_number'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Assessment Frequency</label>
                    <select class="form-control" name="assessment_frequency">
                      <?php foreach (['Weekly', 'Monthly', 'Quarterly', 'Semi-Annual', 'Annual'] as $freq) : ?>
                      <option value="<?= barangay_h($freq) ?>" <?= ($nutritionSettings['assessment_frequency'] ?? '') === $freq ? 'selected' : '' ?>><?= barangay_h($freq) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Barangay PSGC Code</label>
                    <input type="text" class="form-control" value="<?= barangay_h($nutritionSettings['psfc_code'] ?? nutrition_barangay_psgc_code($con, (string) $barangay_id, (string) $barangay)) ?>" readonly>
                    <small class="text-muted">
                      Official PSA code for <strong><?= barangay_h($barangay) ?></strong>.
                      City of Valencia PSGC: <code><?= barangay_h(nutrition_city_psgc_code()) ?></code>.
                      Used in household IDs: <code>PSGC-Purok-00001</code>
                      (<a href="https://psa.gov.ph/classification/psgc/barangays/<?= barangay_h(nutrition_city_psgc_code()) ?>" target="_blank" rel="noopener">PSA masterlist</a>)
                    </small>
                  </div>
                  <input type="hidden" name="psfc_code" value="<?= barangay_h($nutritionSettings['psfc_code'] ?? nutrition_barangay_psgc_code($con, (string) $barangay_id, (string) $barangay)) ?>">
                  <div class="form-group">
                    <label>Report Header</label>
                    <input type="text" class="form-control" name="report_header" value="<?= barangay_h($nutritionSettings['report_header'] ?? '') ?>">
                  </div>
                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label>Enable Household Survey</label>
                      <select class="form-control" name="enable_household_survey">
                        <option value="YES" <?= ($nutritionSettings['enable_household_survey'] ?? 'YES') === 'YES' ? 'selected' : '' ?>>Yes</option>
                        <option value="NO" <?= ($nutritionSettings['enable_household_survey'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
                      </select>
                    </div>
                    <div class="col-md-6 form-group">
                      <label>Enable Barangay Survey</label>
                      <select class="form-control" name="enable_barangay_survey">
                        <option value="YES" <?= ($nutritionSettings['enable_barangay_survey'] ?? 'YES') === 'YES' ? 'selected' : '' ?>>Yes</option>
                        <option value="NO" <?= ($nutritionSettings['enable_barangay_survey'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
                      </select>
                    </div>
                  </div>

                  <hr class="my-4">
                  <h5 class="mb-3"><i class="fas fa-file-alt mr-2"></i>BNP Form C1 — Manual Fields</h5>
                  <p class="text-muted small">Fill barangay-level counts that are not taken from household surveys (schools, FIC, workers, stores). These print on the official ALL HOUSEHOLDS form.</p>
                  <?php
                  require_once __DIR__ . '/../includes/nutrition_bnp_reports.php';
                  $formC1Settings = nutrition_bnp_load_form_c1($con, (string) $barangay_id);
                  $c1Fields = [
                      'daycare_public' => 'Day Care Centers (Public)',
                      'daycare_private' => 'Day Care Centers (Private)',
                      'elementary_public' => 'Elementary Schools (Public)',
                      'elementary_private' => 'Elementary Schools (Private)',
                      'kindergarten' => 'Kindergarten enrolled (DepEd)',
                      'grade1' => 'School children',
                      'school_weighed' => 'School children weighed (start of SY)',
                      'school_weighing_pct' => 'School weighing coverage %',
                      'school_sev_wasted' => 'School status — Severely Wasted',
                      'school_wasted' => 'School status — Wasted',
                      'school_normal' => 'School status — Normal',
                      'school_ow' => 'School status — Overweight',
                      'school_ob' => 'School status — Obese',
                      'fic' => 'Fully immunized children (FIC)',
                      'sari_sari' => 'Sari-sari stores',
                      'bns_count' => 'Barangay Nutrition Scholars',
                      'bhw_count' => 'Barangay Health Workers',
                      'midwife_count' => 'Rural Health Midwife',
                      'ip_pregnant' => 'IP — Pregnant Women (override)',
                      'ip_6_23' => 'IP — 6–23 months children (override)',
                  ];
                  ?>
                  <div class="row">
                    <?php foreach ($c1Fields as $key => $label) : ?>
                    <div class="col-md-6 form-group">
                      <label><?= barangay_h($label) ?></label>
                      <input type="text" class="form-control" name="bnp_c1_<?= barangay_h($key) ?>" value="<?= barangay_h((string) ($formC1Settings[$key] ?? '')) ?>" inputmode="decimal">
                    </div>
                    <?php endforeach; ?>
                  </div>

                  <hr class="my-4">
                  <h5 class="mb-3"><i class="fas fa-tablet-alt mr-2"></i>KoBoToolbox Integration</h5>
                  <p class="text-muted small">Connect a KoBoToolbox form for field data collection. Submissions can be synced into the Barangay Nutrition Survey report.</p>
                  <div class="form-group">
                    <label>Enable KoBoToolbox</label>
                    <select class="form-control" name="kobo_enabled">
                      <option value="NO" <?= ($nutritionSettings['kobo_enabled'] ?? 'NO') === 'NO' ? 'selected' : '' ?>>No</option>
                      <option value="YES" <?= ($nutritionSettings['kobo_enabled'] ?? '') === 'YES' ? 'selected' : '' ?>>Yes</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>KoBo Server URL</label>
                    <input type="url" class="form-control" name="kobo_server_url" value="<?= barangay_h($nutritionSettings['kobo_server_url'] ?? 'https://kf.kobotoolbox.org') ?>" placeholder="https://kf.kobotoolbox.org">
                  </div>
                  <div class="form-group">
                    <label>KoBo API Token</label>
                    <input type="password" class="form-control" name="kobo_api_token" value="<?= barangay_h($nutritionSettings['kobo_api_token'] ?? '') ?>" placeholder="Paste API token from KoBo Account Settings">
                    <small class="text-muted">Found in KoBoToolbox → Account Settings → Security → API Token</small>
                  </div>
                  <div class="form-group">
                    <label>Form Asset UID</label>
                    <input type="text" class="form-control" name="kobo_asset_uid" value="<?= barangay_h($nutritionSettings['kobo_asset_uid'] ?? '') ?>" placeholder="e.g. aBcdEfGhIjKlMnOpQrStUv">
                    <small class="text-muted">Project UID from your KoBo form URL</small>
                  </div>
                  <div class="form-group mb-0">
                    <label>Public Form Link (Enketo)</label>
                    <input type="url" class="form-control" name="kobo_form_url" value="<?= barangay_h($nutritionSettings['kobo_form_url'] ?? '') ?>" placeholder="https://ee.kobotoolbox.org/...">
                    <small class="text-muted">Optional link for enumerators to collect data on mobile or web</small>
                  </div>
                </div>
                <?php if ($canSaveNutritionSettings) : ?>
                <div class="card-footer text-right">
                  <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Save Settings</button>
                </div>
                <?php endif; ?>
              </form>
            </div>
          </div>
        </div>
<?php
$nutritionPageScript = $canSaveNutritionSettings
    ? <<<'HTML'
<script>
$('#nutritionSettingsForm').on('submit', function (e) {
  e.preventDefault();
  if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
  $.post('saveNutritionSettings.php', $(this).serialize(), function (res) {
    Swal.fire({ title: 'Settings saved', text: res.message || 'Nutrition settings updated.', type: 'success' })
      .then(function () { window.location.reload(); });
  }, 'json').fail(function (xhr) {
    var msg = 'Could not save settings.';
    try { var data = JSON.parse(xhr.responseText); if (data.error) msg = data.error; } catch (e) {}
    Swal.fire({ title: 'Error', text: msg, type: 'error' });
  });
});
</script>
HTML
    : <<<'HTML'
<script>
$('#nutritionSettingsForm').find('input:not([type=hidden]), select, textarea').prop('disabled', true);
</script>
HTML;
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
