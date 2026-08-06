<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';
require_once '../includes/csrf.php';

$activePage = 'profile';
$nutritionPageTitle = 'Account Profile';
$useCityNutritionShell = ($isSuperAdmin || $isBnsAdmin || $isNutritionPortalAdmin)
    && barangay_session_id() === null;

if ($useCityNutritionShell) {
    $brandLogo = barangay_default_logo_url('../');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Account Profile | Nutrition Portal</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/super-dashboard.css?v=20260720b">
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
        <h5 class="nav-link text-white mb-0">Nutrition Portal · Account Profile</h5>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
      </li>
    </ul>
  </nav>
  <?php require __DIR__ . '/../includes/partials/super_nutrition_sidebar.php'; ?>
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
<?php
} else {
    $nutritionExtraCss = ['../assets/plugins/sweetalert2/css/sweetalert2.min.css'];
    $nutritionIncludeScriptsCsrf = true;
    $nutritionExtraJs = [
        '../assets/plugins/sweetalert2/js/sweetalert2.all.min.js',
        '../assets/js/barangay-ui.js',
    ];
    require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
}
?>
        <?php
        $nutritionPageIcon = 'fa-user-circle';
        $nutritionPageHeading = 'Account Profile';
        $nutritionPageDescription = 'Update your account name, contact number, and profile photo for the Nutrition Portal.';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card nutrition-panel">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Account Profile</h3>
              </div>
              <form id="nutritionProfileForm" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <div class="card-body">
                  <div class="text-center mb-4">
                    <?php if ($user_image !== '') : ?>
                      <img src="<?= barangay_h($user_image_path !== '' ? $user_image_path : '../assets/dist/img/' . $user_image) ?>" alt="Profile" class="img-circle nutrition-profile-photo" id="profilePreview">
                    <?php else : ?>
                      <img src="../assets/dist/img/image.png" alt="Profile" class="img-circle nutrition-profile-photo" id="profilePreview">
                    <?php endif; ?>
                    <div class="mt-3">
                      <label class="btn btn-sm btn-outline-success mb-0">
                        <i class="fas fa-camera mr-1"></i> Change Photo
                        <input type="file" name="image" id="profileImage" accept="image/*" class="d-none">
                      </label>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label>Username</label>
                      <input type="text" class="form-control" name="username" value="<?= barangay_h($username_user) ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                      <label>Contact Number</label>
                      <input type="text" class="form-control" name="contact_number" value="<?= barangay_h($contact_number_user) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                      <label>First Name</label>
                      <input type="text" class="form-control" name="first_name" value="<?= barangay_h($first_name_user) ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                      <label>Middle Name</label>
                      <input type="text" class="form-control" name="middle_name" value="<?= barangay_h($middle_name_user) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                      <label>Last Name</label>
                      <input type="text" class="form-control" name="last_name" value="<?= barangay_h($last_name_user) ?>" required>
                    </div>
                  </div>

                  <hr>
                  <h5 class="mb-3">Change Password</h5>
                  <div class="row">
                    <div class="col-md-4 form-group">
                      <label>Current Password</label>
                      <input type="password" class="form-control" name="old_password" autocomplete="current-password">
                    </div>
                    <div class="col-md-4 form-group">
                      <label>New Password</label>
                      <input type="password" class="form-control" name="new_password" autocomplete="new-password">
                    </div>
                    <div class="col-md-4 form-group">
                      <label>Confirm Password</label>
                      <input type="password" class="form-control" name="new_confirm_password" autocomplete="new-password">
                    </div>
                  </div>
                </div>
                <div class="card-footer text-right">
                  <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Save Profile</button>
                </div>
              </form>
            </div>

            <?php
            $appearanceNutritionPortal = true;
            require __DIR__ . '/../includes/partials/appearance_accessibility_panel.php';
            ?>
          </div>
        </div>
<?php if ($useCityNutritionShell) : ?>
      </div>
    </section>
  </div>
  <footer class="main-footer text-sm">
    <strong>Nutrition Portal</strong> — Valencia City
  </footer>
</div>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/js/barangay-ui.js"></script>
<?php
$barangay_script_depth = 1;
require_once '../includes/scripts_csrf.php';
?>
<script>
$('#profileImage').on('change', function () {
  var file = this.files && this.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function (e) { $('#profilePreview').attr('src', e.target.result); };
  reader.readAsDataURL(file);
});
$('#nutritionProfileForm').on('submit', function (e) {
  e.preventDefault();
  if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
  $.ajax({
    url: 'saveProfile.php',
    type: 'POST',
    data: new FormData(this),
    contentType: false,
    processData: false,
    success: function () {
      Swal.fire({ title: 'Profile updated', type: 'success', timer: 1500, showConfirmButton: false })
        .then(function () { window.location.reload(); });
    },
    error: function () {
      Swal.fire({ title: 'Error', text: 'Could not update profile.', type: 'error' });
    }
  });
});
</script>
</body>
</html>
<?php else :
$nutritionPageScript = <<<'HTML'
<script>
$('#profileImage').on('change', function () {
  var file = this.files && this.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function (e) { $('#profilePreview').attr('src', e.target.result); };
  reader.readAsDataURL(file);
});

$('#nutritionProfileForm').on('submit', function (e) {
  e.preventDefault();
  if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
  $.ajax({
    url: 'saveProfile.php',
    type: 'POST',
    data: new FormData(this),
    contentType: false,
    processData: false,
    success: function () {
      Swal.fire({ title: 'Profile updated', type: 'success', timer: 1500, showConfirmButton: false })
        .then(function () { window.location.reload(); });
    },
    error: function () {
      Swal.fire({ title: 'Error', text: 'Could not update profile.', type: 'error' });
    }
  });
});
</script>
HTML;
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
endif;
