<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/nutrition_mellpi.php';
require_once '../includes/csrf.php';

nutrition_ensure_module_tables($con);
nutrition_mellpi_ensure_table($con);

$user_id = (string) $_SESSION['user_id'];
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);

if (!$isSuperAdmin && !$isBnsAdmin && !$isCityAdmin && !$isNutritionPortalAdmin) {
    header('Location: nutritionDashboard.php');
    exit;
}

barangay_clear_active();
csrf_token();
barangay_release_session_lock();

$stmt_user = $con->prepare('SELECT first_name, last_name, image, image_path, user_type FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc() ?: [];
$first_name_user = $row_user['first_name'] ?? 'Admin';
$last_name_user = $row_user['last_name'] ?? '';
$user_image = $row_user['image'] ?? '';
$user_image_path = $row_user['image_path'] ?? '';
$staffRoleLabel = $isNutritionPortalAdmin
    ? staff_role_label(STAFF_ROLE_NUTRITION_SUPER_ADMIN)
    : staff_role_label(barangay_user_staff_role($con, $user_id));
$activePage = 'mellpi_city_profile';
$brandLogo = barangay_default_logo_url('../');

$profile = nutrition_mellpi_load_profile($con);
$preview = nutrition_mellpi_build_report($con);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MELLPI City Profile Registration | Valencia City</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/super-dashboard.css?v=20260720b">
  <style>
    .mellpi-reg-hint { font-size:.82rem; color:#94a3b8; }
    .mellpi-live-pill {
      display:inline-block; background:#14532d; color:#bbf7d0; border-radius:999px;
      padding:.15rem .6rem; font-size:.75rem; margin-left:.35rem;
    }
    .mellpi-section-card .card-header { background:#0f172a; }
    .mellpi-year-input { max-width:90px; }
  </style>
<?php require_once '../includes/head_csrf.php'; ?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260805n">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal nutrition-portal nutrition-super-dashboard">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark nutrition-navbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white mb-0">MELLPI PRO · City Profile Registration</h5>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link text-white" href="nutritionSuperPrintReport.php" target="_blank"><i class="fas fa-print mr-1"></i> City Report</a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
      </li>
    </ul>
  </nav>

  <?php require __DIR__ . '/../includes/partials/super_nutrition_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
          <div>
            <h2 class="mb-1"><i class="fas fa-clipboard-list mr-2"></i>MELLPI PRO FORM CM Registration</h2>
            <p class="text-muted mb-0">
              Register City/Municipality Profile Sheet data for Valencia City.
              Live totals (population, households, barangays, current preschool status, BNS count) auto-fill when blank.
            </p>
          </div>
          <div class="mt-2">
            <span class="mellpi-live-pill">Live pop: <?= number_format((int) ($preview['summary']['total_population'] ?? 0)) ?></span>
            <span class="mellpi-live-pill">HH: <?= number_format((int) ($preview['summary']['no_of_households'] ?? 0)) ?></span>
            <span class="mellpi-live-pill">Brgy: <?= number_format((int) ($preview['summary']['no_of_barangays'] ?? 0)) ?></span>
          </div>
        </div>

        <form id="mellpiCityProfileForm">
          <?= csrf_field(); ?>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Header</h3></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4 form-group">
                  <label>City/Municipality</label>
                  <?php $field('city_name', $profile['city_name'] ?? 'City of Valencia'); ?>
                </div>
                <div class="col-md-4 form-group">
                  <label>Province</label>
                  <?php $field('province', $profile['province'] ?? 'Bukidnon'); ?>
                </div>
                <div class="col-md-4 form-group">
                  <label>Income Class</label>
                  <?php $field('income_class', $profile['income_class'] ?? ''); ?>
                </div>
                <div class="col-md-4 form-group">
                  <label>Date of Monitoring</label>
                  <?php $field('date_of_monitoring', $profile['date_of_monitoring'] ?? date('Y-m-d'), 'date'); ?>
                </div>
                <div class="col-md-4 form-group">
                  <label>Period Covered</label>
                  <?php $field('period_covered', $profile['period_covered'] ?? ('CY ' . date('Y'))); ?>
                </div>
              </div>
            </div>
          </div>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Community Profile</h3></div>
            <div class="card-body">
              <div class="row">
                <?php
                $communityFields = [
                    'income_classification' => 'Income Classification',
                    'hh_safe_water' => 'No. of households with access to safe water',
                    'hh_sanitary_toilets' => 'No. of households with sanitary toilets',
                    'day_care_centers' => 'No. of Day Care Centers',
                    'public_elementary_schools' => 'No. of public elementary schools',
                    'public_secondary_schools' => 'No. of public secondary schools',
                    'barangay_health_stations' => 'No. of Barangay Health Stations',
                    'retail_outlets' => 'No. of retail outlets/sari-sari stores',
                    'bakeries' => 'No. of bakeries',
                    'public_markets' => 'No. of public markets',
                    'transport_terminals' => 'No. of transport terminals',
                    'pct_at_risk_pregnant' => '% nutritionally at-risk pregnant women',
                    'pct_exclusive_bf_5th_month' => '% exclusive breastfeeding until 5th month',
                    'idd_pregnant' => 'IDD Prevalence (Pregnant)',
                    'idd_lactating' => 'IDD Prevalence (Lactating)',
                    'terrain' => 'Terrain',
                ];
                foreach ($communityFields as $key => $label) :
                    ?>
                <div class="col-md-6 form-group">
                  <label><?= barangay_h($label) ?></label>
                  <?php $field('community[' . $key . ']', $community[$key] ?? ''); ?>
                </div>
                <?php endforeach; ?>
              </div>
              <p class="mellpi-reg-hint mb-0">Safe water and sanitary toilet counts auto-fill from household surveys when left blank.</p>
            </div>
          </div>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Population Snapshot (Estimated / Actual)</h3></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead><tr><th></th><th>Estimated</th><th>Actual</th></tr></thead>
                  <tbody>
                    <tr>
                      <td>0–59 months</td>
                      <td><?php $field('population_snapshot[0_59_estimated]', $popSnap['0_59_estimated'] ?? ''); ?></td>
                      <td><?php $field('population_snapshot[0_59_actual]', $popSnap['0_59_actual'] ?? ''); ?></td>
                    </tr>
                    <tr>
                      <td>Pregnant</td>
                      <td><?php $field('population_snapshot[pregnant_estimated]', $popSnap['pregnant_estimated'] ?? ''); ?></td>
                      <td><?php $field('population_snapshot[pregnant_actual]', $popSnap['pregnant_actual'] ?? ''); ?></td>
                    </tr>
                    <tr>
                      <td>Lactating</td>
                      <td><?php $field('population_snapshot[lactating_estimated]', $popSnap['lactating_estimated'] ?? ''); ?></td>
                      <td><?php $field('population_snapshot[lactating_actual]', $popSnap['lactating_actual'] ?? ''); ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <?php
          $renderYearMatrix = static function (string $prefix, array $rows, array $data, array $years) use ($field): void {
              echo '<div class="table-responsive"><table class="table table-sm table-bordered">';
              echo '<thead><tr><th>Indicator</th>';
              foreach ($years as $y) {
                  echo '<th class="text-center">' . (int) $y . '</th>';
              }
              echo '</tr></thead><tbody>';
              foreach ($rows as $lab) {
                  echo '<tr><td>' . barangay_h($lab) . '</td>';
                  foreach ($years as $y) {
                      echo '<td>';
                      $field($prefix . '[' . $lab . '][' . $y . ']', $data[$lab][$y] ?? '', 'text', 'mellpi-year-input mx-auto');
                      echo '</td>';
                  }
                  echo '</tr>';
              }
              echo '</tbody></table></div>';
          };
          ?>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Preschool Nutritional Status (0–59 months)</h3></div>
            <div class="card-body">
              <h6>Weight-for-Age</h6>
              <?php $renderYearMatrix('preschool[wfa]', ['Normal', 'Underweight', 'Severely Underweight', 'Overweight'], $preschool['wfa'] ?? [], $years); ?>
              <h6 class="mt-3">Weight-for-Height/Length</h6>
              <?php $renderYearMatrix('preschool[wfh]', ['Normal', 'Wasted', 'Severely Wasted', 'Overweight', 'Obese'], $preschool['wfh'] ?? [], $years); ?>
              <h6 class="mt-3">Height-for-Age</h6>
              <?php $renderYearMatrix('preschool[hfa]', ['Normal', 'Stunted', 'Severely Stunted', 'Tall'], $preschool['hfa'] ?? [], $years); ?>
              <p class="mellpi-reg-hint mb-0">Current year blanks are filled from e-OPT Plus city data when generating the report.</p>
            </div>
          </div>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">School Children Nutritional Status</h3></div>
            <div class="card-body">
              <?php $renderYearMatrix('school', ['Normal', 'Wasted', 'Severely Wasted', 'Overweight', 'Obese'], $school, $years); ?>
            </div>
          </div>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Pregnant Women Nutritional Status</h3></div>
            <div class="card-body">
              <?php $renderYearMatrix('pregnant_status', ['Normal', 'Nutritionally at-risk', 'Overweight', 'Obese'], $pregnantStatus, $years); ?>
            </div>
          </div>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Barangay Nutrition Scholars (BNS)</h3></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4 form-group">
                  <label>Total No. of BNS</label>
                  <?php $field('bns[total]', $bns['total'] ?? ''); ?>
                </div>
                <div class="col-md-4 form-group">
                  <label>New</label>
                  <?php $field('bns[new]', $bns['new'] ?? ''); ?>
                </div>
                <div class="col-md-4 form-group">
                  <label>Existing</label>
                  <?php $field('bns[existing]', $bns['existing'] ?? ''); ?>
                </div>
              </div>
            </div>
          </div>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Hazards</h3></div>
            <div class="card-body">
              <?php for ($i = 0; $i < 5; $i++) :
                  $hz = $hazards[$i] ?? ['type_month' => '', 'affected' => ''];
                  ?>
              <div class="row">
                <div class="col-md-6 form-group">
                  <label>Hazards (Type/Month) #<?= $i + 1 ?></label>
                  <?php $field('hazards[' . $i . '][type_month]', $hz['type_month'] ?? ''); ?>
                </div>
                <div class="col-md-6 form-group">
                  <label>LGU / Households affected</label>
                  <?php $field('hazards[' . $i . '][affected]', $hz['affected'] ?? ''); ?>
                </div>
              </div>
              <?php endfor; ?>
            </div>
          </div>

          <div class="card nutrition-panel mellpi-section-card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Land Use Classification</h3></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead>
                    <tr><th>Classification</th><th>Land Area</th><th>Bgy Covered</th><th>Remarks</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ([
                        'Residential',
                        'Commercial',
                        'Industrial',
                        'Agricultural',
                        'Forest land/Mineral land/National Park',
                    ] as $lu) :
                        $row = $landUse[$lu] ?? ['land_area' => '', 'bgy_covered' => '', 'remarks' => ''];
                        ?>
                    <tr>
                      <td><?= barangay_h($lu) ?></td>
                      <td><?php $field('land_use[' . $lu . '][land_area]', $row['land_area'] ?? ''); ?></td>
                      <td><?php $field('land_use[' . $lu . '][bgy_covered]', $row['bgy_covered'] ?? ''); ?></td>
                      <td><?php $field('land_use[' . $lu . '][remarks]', $row['remarks'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="mb-4 text-right">
            <a href="nutritionSuperPrintReport.php" target="_blank" class="btn btn-outline-light mr-2">
              <i class="fas fa-print mr-1"></i> Preview in City Report
            </a>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save mr-1"></i> Save MELLPI City Profile
            </button>
          </div>
        </form>
      </div>
    </section>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/js/barangay-ui.js"></script>
<script>
(function () {
  $('#mellpiCityProfileForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    $.post('saveNutritionMellpiCityProfile.php', $(this).serialize())
      .done(function (res) {
        Swal.fire('Saved', (res && res.message) ? res.message : 'MELLPI city profile saved.', 'success');
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
</body>
</html>
