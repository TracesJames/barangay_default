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
          <?php
          $mellpiScope = 'city';
          $mellpiUnitLabel = 'City/Municipality';
          require __DIR__ . '/../includes/partials/nutrition_mellpi_registration_form.php';
          ?>

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
