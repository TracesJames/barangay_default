<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';
require_once '../includes/nutrition_mellpi.php';

$activePage = 'mellpi_barangay_profile';
$nutritionPageTitle = 'MELLPI Barangay Profile';
$nutritionIncludeScriptsCsrf = true;
$nutritionExtraCss = ['../assets/plugins/sweetalert2/css/sweetalert2.min.css'];
$nutritionExtraJs = [
    '../assets/plugins/sweetalert2/js/sweetalert2.all.min.js',
    '../assets/js/barangay-ui.js',
];

nutrition_mellpi_ensure_table($con);

$profile = nutrition_mellpi_load_profile($con, (string) $barangay_id);
if (trim((string) ($profile['city_name'] ?? '')) === '') {
    $profile['city_name'] = (string) $barangay;
}
$preview = nutrition_mellpi_build_report($con, (string) $barangay_id);
$years = $profile['years'] ?? [(int) date('Y') - 2, (int) date('Y') - 1, (int) date('Y')];
$community = $profile['community'] ?? [];
$popSnap = $profile['population_snapshot'] ?? [];
$preschool = $profile['preschool'] ?? [];
$school = $profile['school'] ?? [];
$pregnantStatus = $profile['pregnant_status'] ?? [];
$bns = $profile['bns'] ?? [];
$hazards = $profile['hazards'] ?? [];
$landUse = $profile['land_use'] ?? [];

$field = static function (string $name, $value, string $type = 'text', string $extraClass = ''): void {
    $typeAttr = $type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text');
    $step = $type === 'number' ? ' step="any"' : '';
    echo '<input type="' . $typeAttr . '" class="form-control form-control-sm ' . barangay_h($extraClass) . '" name="'
        . barangay_h($name) . '" value="' . barangay_h((string) $value) . '"' . $step . '>';
};

$mellpiScope = 'barangay';
$mellpiUnitLabel = 'Barangay';

$nutritionPageScript = <<<'HTML'
<style>
  .mellpi-reg-hint { font-size:.82rem; color:#94a3b8; }
  .mellpi-live-pill {
    display:inline-block; background:#14532d; color:#bbf7d0; border-radius:999px;
    padding:.15rem .6rem; font-size:.75rem; margin-left:.35rem;
  }
  .mellpi-section-card .card-header { background:#0f172a; }
  .mellpi-year-input { max-width:90px; }
</style>
<script>
(function () {
  $('#mellpiBarangayProfileForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
    $.post('saveNutritionMellpiCityProfile.php', $(this).serialize())
      .done(function (res) {
        Swal.fire('Saved', (res && res.message) ? res.message : 'MELLPI barangay profile saved.', 'success');
      })
      .fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Could not save profile.';
        Swal.fire('Error', msg, 'error');
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });
})();
</script>
HTML;

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
?>
        <?php
        $nutritionPageIcon = 'fa-clipboard-list';
        $nutritionPageHeading = 'MELLPI PRO FORM CM Registration';
        $nutritionPageDescription = 'Barangay Profile Sheet for ' . $barangay . '. Live totals auto-fill when blanks are left empty.';
        $nutritionPageActions = '
            <span class="mellpi-live-pill">Live pop: ' . number_format((int) ($preview['summary']['total_population'] ?? 0)) . '</span>
            <span class="mellpi-live-pill">HH: ' . number_format((int) ($preview['summary']['no_of_households'] ?? 0)) . '</span>
            <a href="nutritionEoptPrint.php" target="_blank" class="btn btn-outline-light btn-sm ml-1">
              <i class="fas fa-notes-medical mr-1"></i> e-OPT Plus
            </a>
            <a href="nutritionBnpReport.php" class="btn btn-outline-light btn-sm">
              <i class="fas fa-book mr-1"></i> BNP Reports
            </a>';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>

        <form id="mellpiBarangayProfileForm">
          <?= csrf_field(); ?>
          <input type="hidden" name="barangay_id" value="<?= barangay_h((string) $barangay_id) ?>">
          <input type="hidden" name="scope" value="barangay">

          <?php require __DIR__ . '/../includes/partials/nutrition_mellpi_registration_form.php'; ?>

          <div class="mb-4 text-right">
            <a href="nutritionBnpReport.php?type=eopt" class="btn btn-outline-light mr-2">
              <i class="fas fa-notes-medical mr-1"></i> Open e-OPT Plus
            </a>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save mr-1"></i> Save MELLPI Barangay Profile
            </button>
          </div>
        </form>
<?php
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
