<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';
require_once '../includes/csrf.php';

$activePage = 'profile';
$nutritionPageTitle = 'Account Profile';
$useCityNutritionShell = ($isSuperAdmin || $isBnsAdmin || $isNutritionPortalAdmin)
    && barangay_session_id() === null;

$profilePhotoSrc = $userAvatarUrl !== ''
    ? $userAvatarUrl
    : '../assets/dist/img/image.png';

$profileScript = <<<'HTML'
<script>
(function ($) {
  function profileErrorMessage(payload, xhr) {
    if (payload && payload.message) return payload.message;
    var raw = (xhr && xhr.responseText) ? String(xhr.responseText).trim() : '';
    if (raw === 'error1') return 'Current password is incorrect.';
    if (raw === 'error2') return 'New password and confirmation do not match (min 8 characters).';
    if (raw === 'errorImage') return 'Could not upload that photo. Use JPG or PNG under 5MB.';
    if (raw === 'error') return 'Username is already taken or save failed.';
    return 'Could not update profile.';
  }

  $('#profileImage').on('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({ title: 'Photo too large', text: 'Please choose an image under 5MB.', type: 'warning' });
      this.value = '';
      return;
    }
    var reader = new FileReader();
    reader.onload = function (e) { $('#profilePreview').attr('src', e.target.result); };
    reader.readAsDataURL(file);
  });

  $('#nutritionProfileForm').on('submit', function (e) {
    e.preventDefault();
    if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();

    var oldPw = $.trim($('#old_password').val() || '');
    var newPw = $.trim($('#new_password').val() || '');
    var confirmPw = $.trim($('#new_confirm_password').val() || '');

    if (oldPw === '') {
      Swal.fire({ title: 'Current password required', text: 'Enter your current password to save profile changes.', type: 'info' });
      return;
    }
    if ((newPw !== '' || confirmPw !== '') && newPw !== confirmPw) {
      Swal.fire({ title: 'Passwords do not match', text: 'New password and confirmation must be the same.', type: 'warning' });
      return;
    }
    if (newPw !== '' && newPw.length < 8) {
      Swal.fire({ title: 'Password too short', text: 'New password must be at least 8 characters.', type: 'warning' });
      return;
    }

    var $btn = $('#nutritionProfileSaveBtn').prop('disabled', true);
    var formData = new FormData(this);
    formData.set('response', 'json');

    $.ajax({
      url: 'saveProfile.php',
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      dataType: 'json',
      success: function (res) {
        if (res && res.ok) {
          Swal.fire({ title: 'Profile updated', type: 'success', timer: 1500, showConfirmButton: false })
            .then(function () { window.location.reload(); });
          return;
        }
        Swal.fire({ title: 'Error', text: profileErrorMessage(res), type: 'error' });
      },
      error: function (xhr) {
        var payload = null;
        try { payload = JSON.parse(xhr.responseText); } catch (err) {}
        Swal.fire({ title: 'Error', text: profileErrorMessage(payload, xhr), type: 'error' });
      },
      complete: function () {
        $btn.prop('disabled', false);
      }
    });
  });
})(jQuery);
</script>
HTML;

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
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260824p">
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
        $nutritionPageDescription = 'Update your Nutrition Portal name, contact number, photo, and password. Current password is required to save.';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>
        <div class="row justify-content-center">
          <div class="col-lg-9 col-xl-8">
            <div class="card nutrition-panel nutrition-account-profile-card mb-4">
              <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="fas fa-user-circle mr-2"></i>Account Profile</h3>
                <?php if ($staffRoleLabel !== '') : ?>
                <span class="badge badge-success nutrition-role-badge"><?= barangay_h($staffRoleLabel) ?></span>
                <?php endif; ?>
              </div>
              <form id="nutritionProfileForm" enctype="multipart/form-data" autocomplete="off">
                <?= csrf_field(); ?>
                <div class="card-body">
                  <div class="nutrition-profile-hero text-center mb-4">
                    <img src="<?= barangay_h($profilePhotoSrc) ?>" alt="Profile" class="img-circle nutrition-profile-photo" id="profilePreview">
                    <div class="mt-3">
                      <label class="btn btn-sm btn-outline-success mb-0">
                        <i class="fas fa-camera mr-1"></i> Change Photo
                        <input type="file" name="image" id="profileImage" accept="image/png,image/jpeg,image/webp" class="d-none">
                      </label>
                      <small class="d-block text-muted mt-2">JPG, PNG, or WebP · max 5MB</small>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label for="profile_username">Username <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="profile_username" name="username" value="<?= barangay_h($username_user) ?>" required maxlength="64">
                    </div>
                    <div class="col-md-6 form-group">
                      <label for="profile_contact">Contact Number</label>
                      <input type="text" class="form-control" id="profile_contact" name="contact_number" value="<?= barangay_h($contact_number_user) ?>" maxlength="32" inputmode="tel">
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="profile_first_name">First Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="profile_first_name" name="first_name" value="<?= barangay_h($first_name_user) ?>" required maxlength="80">
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="profile_middle_name">Middle Name</label>
                      <input type="text" class="form-control" id="profile_middle_name" name="middle_name" value="<?= barangay_h($middle_name_user) ?>" maxlength="80">
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="profile_last_name">Last Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="profile_last_name" name="last_name" value="<?= barangay_h($last_name_user) ?>" required maxlength="80">
                    </div>
                  </div>

                  <hr class="border-secondary">
                  <h5 class="mb-2"><i class="fas fa-key mr-2 text-success"></i>Security</h5>
                  <p class="text-muted small mb-3">Enter your <strong>current password</strong> to save any profile change. Fill new password fields only if you want to change it.</p>
                  <div class="row">
                    <div class="col-md-4 form-group">
                      <label for="old_password">Current Password <span class="text-danger">*</span></label>
                      <input type="password" class="form-control" id="old_password" name="old_password" autocomplete="current-password" required>
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="new_password">New Password</label>
                      <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" minlength="8" placeholder="Optional">
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="new_confirm_password">Confirm New Password</label>
                      <input type="password" class="form-control" id="new_confirm_password" name="new_confirm_password" autocomplete="new-password" minlength="8" placeholder="Optional">
                    </div>
                  </div>
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
                  <small class="text-muted mb-2 mb-sm-0">Changes apply immediately after a successful save.</small>
                  <button type="submit" class="btn btn-success" id="nutritionProfileSaveBtn">
                    <i class="fas fa-save mr-1"></i> Save Profile
                  </button>
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
echo $profileScript;
?>
</body>
</html>
<?php else :
$nutritionPageScript = $profileScript;
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
endif;
