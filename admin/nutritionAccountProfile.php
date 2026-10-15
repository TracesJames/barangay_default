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

$profileFullName = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
    $first_name_user,
    $middle_name_user !== '' && strtolower($middle_name_user) !== 'none' ? $middle_name_user : '',
    $last_name_user,
]))));
$profileInitials = strtoupper(substr($first_name_user, 0, 1) . substr($last_name_user, 0, 1));
$profileContact = trim((string) $contact_number_user);
if (strtolower($profileContact) === 'none') {
    $profileContact = '';
}
$profileMiddleName = strtolower(trim((string) $middle_name_user)) === 'none' ? '' : (string) $middle_name_user;

$profileScope = $useCityNutritionShell || barangay_session_id() === null
    ? 'City-wide · all barangays'
    : 'Barangay ' . $barangay;

$profileLastSeen = '';
$stmtSeen = $con->prepare('SELECT session_last_seen FROM users WHERE id = ? LIMIT 1');
if ($stmtSeen) {
    $stmtSeen->bind_param('s', $user_id);
    $stmtSeen->execute();
    $seen = (string) ($stmtSeen->get_result()->fetch_assoc()['session_last_seen'] ?? '');
    $stmtSeen->close();
    if ($seen !== '' && strtotime($seen) !== false) {
        $profileLastSeen = date('M j, Y · g:i A', strtotime($seen));
    }
}

$profileCssHref = '../assets/css/account-profile.css?v=20261006b';

$profileScript = <<<'HTML'
<script>
(function ($) {
  var $form = $('#nutritionProfileForm');
  var $saveBtn = $('#nutritionProfileSaveBtn');
  var $discardBtn = $('#nutritionProfileDiscardBtn');
  var $dirtyNote = $('#acctDirtyNote');
  var originalPhoto = $('#profilePreview').attr('src');
  var trackedFields = '#profile_username, #profile_contact, #profile_first_name, #profile_middle_name, #profile_last_name, #new_password, #new_confirm_password';
  var initialValues = {};
  var saving = false;

  $(trackedFields).each(function () { initialValues[this.id] = this.value; });

  function profileErrorMessage(payload, xhr) {
    if (payload && payload.message) return payload.message;
    var raw = (xhr && xhr.responseText) ? String(xhr.responseText).trim() : '';
    if (raw === 'error1') return 'Current password is incorrect.';
    if (raw === 'error2') return 'New password and confirmation do not match (min 8 characters).';
    if (raw === 'errorImage') return 'Could not upload that photo. Use JPG, PNG or WebP under 5MB.';
    if (raw === 'error') return 'Username is already taken or save failed.';
    return 'Could not update profile.';
  }

  function isDirty() {
    var changed = false;
    $(trackedFields).each(function () {
      if (this.value !== initialValues[this.id]) changed = true;
    });
    var file = $('#profileImage')[0];
    return changed || !!(file && file.files && file.files.length);
  }

  function refreshDirty() {
    var dirty = isDirty();
    $form.toggleClass('is-dirty', dirty);
    $saveBtn.prop('disabled', !dirty || saving);
    $discardBtn.prop('disabled', !dirty || saving);
    $dirtyNote.html(dirty
      ? '<i class="fas fa-circle text-warning mr-1"></i>You have unsaved changes'
      : '<i class="fas fa-check-circle text-success mr-1"></i>All changes saved');
  }

  function refreshNamePreview() {
    var parts = [$('#profile_first_name').val(), $('#profile_middle_name').val(), $('#profile_last_name').val()]
      .map(function (v) { return $.trim(v || ''); })
      .filter(function (v) { return v !== ''; });
    $('[data-profile-name]').text(parts.join(' ') || 'Your name');
    var username = $.trim($('#profile_username').val() || '');
    $('[data-profile-username]').text(username ? '@' + username : '');
    var first = $.trim($('#profile_first_name').val() || '');
    var last = $.trim($('#profile_last_name').val() || '');
    $('[data-profile-initials]').text(((first.charAt(0) || '') + (last.charAt(0) || '')).toUpperCase());
  }

  function passwordScore(pw) {
    var score = 0;
    if (pw.length >= 8) score++;
    if (pw.length >= 12) score++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.min(score, 4);
  }

  function refreshPasswordUi() {
    var pw = $('#new_password').val() || '';
    var confirm = $('#new_confirm_password').val() || '';
    var labels = ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'];
    var score = pw === '' ? -1 : passwordScore(pw);
    var $meter = $('#acctPwMeter');
    $meter.attr('data-score', score).find('.acct-pw-meter-label').text(pw === '' ? 'Not changing password' : labels[score]);

    var rules = {
      length: pw.length >= 8,
      mixed: /[a-z]/.test(pw) && /[A-Z]/.test(pw),
      number: /\d/.test(pw),
      match: pw !== '' && pw === confirm
    };
    $.each(rules, function (key, ok) {
      $('[data-pw-rule="' + key + '"]').toggleClass('is-met', ok)
        .find('i').attr('class', ok ? 'fas fa-check-circle' : 'far fa-circle');
    });
    $('#acctPwRules').toggleClass('d-none', pw === '' && confirm === '');
    $('#new_confirm_password').toggleClass('is-invalid', confirm !== '' && pw !== confirm);
  }

  $(document).on('click', '[data-toggle-password]', function () {
    var $input = $('#' + $(this).data('toggle-password'));
    var show = $input.attr('type') === 'password';
    $input.attr('type', show ? 'text' : 'password');
    $(this).attr('aria-pressed', show ? 'true' : 'false')
      .attr('title', show ? 'Hide password' : 'Show password')
      .find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
  });

  $('#profileImage').on('change', function () {
    var file = this.files && this.files[0];
    if (!file) { refreshDirty(); return; }
    if (!/^image\/(png|jpe?g|webp)$/i.test(file.type)) {
      Swal.fire({ title: 'Unsupported file', text: 'Please choose a JPG, PNG or WebP image.', type: 'warning' });
      this.value = '';
      refreshDirty();
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({ title: 'Photo too large', text: 'Please choose an image under 5MB.', type: 'warning' });
      this.value = '';
      refreshDirty();
      return;
    }
    var reader = new FileReader();
    reader.onload = function (e) {
      $('#profilePreview').attr('src', e.target.result);
      $('#acctPhotoPending').removeClass('d-none');
    };
    reader.readAsDataURL(file);
    refreshDirty();
  });

  $('#profile_contact').on('input', function () {
    var digits = this.value.replace(/\D/g, '').slice(0, 11);
    if (this.value !== digits) this.value = digits;
    $(this).toggleClass('is-invalid', digits !== '' && digits.length !== 11);
  });

  $(trackedFields).on('input change', function () {
    refreshDirty();
    refreshNamePreview();
    refreshPasswordUi();
  });

  $discardBtn.on('click', function () {
    $(trackedFields).each(function () { this.value = initialValues[this.id]; });
    $('#profileImage').val('');
    $('#old_password').val('');
    $('#profilePreview').attr('src', originalPhoto);
    $('#acctPhotoPending').addClass('d-none');
    refreshNamePreview();
    refreshPasswordUi();
    refreshDirty();
  });

  $(window).on('beforeunload', function () {
    if (!saving && isDirty()) return 'You have unsaved changes.';
  });

  $form.on('submit', function (e) {
    e.preventDefault();
    if (!isDirty()) return;
    if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();

    var oldPw = $.trim($('#old_password').val() || '');
    var newPw = $.trim($('#new_password').val() || '');
    var confirmPw = $.trim($('#new_confirm_password').val() || '');

    if ($.trim($('#profile_username').val()) === '' || $.trim($('#profile_first_name').val()) === '' || $.trim($('#profile_last_name').val()) === '') {
      Swal.fire({ title: 'Missing details', text: 'Username, first name and last name are required.', type: 'info' });
      return;
    }
    var contact = $.trim($('#profile_contact').val() || '');
    if (contact !== '' && !/^\d{11}$/.test(contact)) {
      $('#profile_contact').addClass('is-invalid').trigger('focus');
      Swal.fire({ title: 'Invalid contact number', text: 'Contact number must be exactly 11 digits.', type: 'warning' });
      return;
    }
    if (oldPw === '') {
      $('#old_password').addClass('is-invalid').trigger('focus');
      Swal.fire({ title: 'Current password required', text: 'Enter your current password to confirm these changes.', type: 'info' });
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

    saving = true;
    refreshDirty();
    var originalLabel = $saveBtn.html();
    $saveBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving…');
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
          Swal.fire({ title: 'Profile updated', text: 'Your changes have been saved.', type: 'success', timer: 1500, showConfirmButton: false })
            .then(function () { window.location.reload(); });
          return;
        }
        saving = false;
        Swal.fire({ title: 'Could not save', text: profileErrorMessage(res), type: 'error' });
      },
      error: function (xhr) {
        saving = false;
        var payload = null;
        try { payload = JSON.parse(xhr.responseText); } catch (err) {}
        var message = profileErrorMessage(payload, xhr);
        if (payload && payload.code === 'error1') {
          $('#old_password').addClass('is-invalid').val('').trigger('focus');
        }
        Swal.fire({ title: 'Could not save', text: message, type: 'error' });
      },
      complete: function () {
        $saveBtn.html(originalLabel);
        refreshDirty();
      }
    });
  });

  $('#old_password').on('input', function () { $(this).removeClass('is-invalid'); });

  refreshNamePreview();
  refreshPasswordUi();
  refreshDirty();
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
  <link rel="stylesheet" href="<?= barangay_h($profileCssHref) ?>">
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
    $nutritionExtraCss = ['../assets/plugins/sweetalert2/css/sweetalert2.min.css', $profileCssHref];
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
        $nutritionPageDescription = 'Manage your personal details, sign-in credentials and display preferences for the Nutrition Portal.';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>
        <form id="nutritionProfileForm" class="acct-profile" enctype="multipart/form-data" autocomplete="off" novalidate>
          <?= csrf_field(); ?>
          <div class="row">
            <!-- Identity summary -->
            <div class="col-xl-4 col-lg-5 mb-4">
              <div class="card nutrition-panel acct-identity-card">
                <div class="acct-identity-cover"></div>
                <div class="card-body text-center">
                  <label class="acct-avatar" for="profileImage" title="Change photo">
                    <img src="<?= barangay_h($profilePhotoSrc) ?>" alt="Profile photo of <?= barangay_h($profileFullName) ?>" id="profilePreview"
                         onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                    <span class="acct-avatar-initials d-none" data-profile-initials><?= barangay_h($profileInitials) ?></span>
                    <span class="acct-avatar-overlay"><i class="fas fa-camera"></i><span>Change</span></span>
                  </label>
                  <input type="file" name="image" id="profileImage" accept="image/png,image/jpeg,image/webp" class="d-none">
                  <span class="badge badge-warning acct-photo-pending d-none" id="acctPhotoPending">New photo · save to apply</span>

                  <h3 class="acct-identity-name" data-profile-name><?= barangay_h($profileFullName) ?></h3>
                  <div class="acct-identity-username" data-profile-username>@<?= barangay_h($username_user) ?></div>
                  <?php if ($staffRoleLabel !== '') : ?>
                  <span class="badge acct-role-badge"><i class="fas fa-user-shield mr-1"></i><?= barangay_h($staffRoleLabel) ?></span>
                  <?php endif; ?>
                </div>
                <ul class="acct-meta-list">
                  <li>
                    <span class="acct-meta-icon"><i class="fas fa-map-marker-alt"></i></span>
                    <span><small>Scope</small><?= barangay_h($profileScope) ?></span>
                  </li>
                  <li>
                    <span class="acct-meta-icon"><i class="fas fa-phone"></i></span>
                    <span><small>Contact number</small><?= $profileContact !== '' ? barangay_h($profileContact) : '<em class="text-muted">Not set</em>' ?></span>
                  </li>
                  <li>
                    <span class="acct-meta-icon"><i class="fas fa-clock"></i></span>
                    <span><small>Last active</small><?= $profileLastSeen !== '' ? barangay_h($profileLastSeen) : '<em class="text-muted">This session</em>' ?></span>
                  </li>
                </ul>
                <div class="acct-identity-footer">
                  <i class="fas fa-info-circle mr-1"></i>Photo: JPG, PNG or WebP, up to 5&nbsp;MB.
                </div>
              </div>
            </div>

            <!-- Editable sections -->
            <div class="col-xl-8 col-lg-7">
              <div class="card nutrition-panel acct-section mb-4">
                <div class="card-header">
                  <h3 class="card-title"><i class="fas fa-id-card mr-2"></i>Personal information</h3>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label for="profile_username">Username <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">@</span></div>
                        <input type="text" class="form-control" id="profile_username" name="username" value="<?= barangay_h($username_user) ?>" required maxlength="64" autocomplete="username" spellcheck="false">
                      </div>
                      <small class="form-text text-muted">Used to sign in. Must be unique.</small>
                    </div>
                    <div class="col-md-6 form-group">
                      <label for="profile_contact">Contact number</label>
                      <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                        <input type="text" class="form-control" id="profile_contact" name="contact_number" value="<?= barangay_h($profileContact) ?>" maxlength="11" minlength="11" pattern="\d{11}" inputmode="numeric" placeholder="09XXXXXXXXX" autocomplete="tel">
                        <div class="invalid-feedback">Contact number must be exactly 11 digits.</div>
                      </div>
                      <small class="form-text text-muted">11 digits, numbers only (e.g. 09171234567).</small>
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="profile_first_name">First name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="profile_first_name" name="first_name" value="<?= barangay_h($first_name_user) ?>" required maxlength="80" autocomplete="given-name">
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="profile_middle_name">Middle name</label>
                      <input type="text" class="form-control" id="profile_middle_name" name="middle_name" value="<?= barangay_h($profileMiddleName) ?>" maxlength="80" autocomplete="additional-name" placeholder="Optional">
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="profile_last_name">Last name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="profile_last_name" name="last_name" value="<?= barangay_h($last_name_user) ?>" required maxlength="80" autocomplete="family-name">
                    </div>
                  </div>
                </div>
              </div>

              <div class="card nutrition-panel acct-section mb-4">
                <div class="card-header">
                  <h3 class="card-title"><i class="fas fa-key mr-2"></i>Change password</h3>
                  <div class="card-tools"><span class="badge badge-secondary">Optional</span></div>
                </div>
                <div class="card-body">
                  <p class="text-muted small mb-3">Leave these blank to keep your current password.</p>
                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label for="new_password">New password</label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" minlength="8">
                        <div class="input-group-append">
                          <button type="button" class="btn btn-outline-secondary" data-toggle-password="new_password" title="Show password" aria-pressed="false"><i class="fas fa-eye"></i></button>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 form-group">
                      <label for="new_confirm_password">Confirm new password</label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="new_confirm_password" name="new_confirm_password" autocomplete="new-password" minlength="8">
                        <div class="input-group-append">
                          <button type="button" class="btn btn-outline-secondary" data-toggle-password="new_confirm_password" title="Show password" aria-pressed="false"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="invalid-feedback">Passwords do not match.</div>
                      </div>
                    </div>
                  </div>
                  <div class="acct-pw-meter" id="acctPwMeter" data-score="-1" aria-live="polite">
                    <div class="acct-pw-meter-track"><span></span><span></span><span></span><span></span></div>
                    <span class="acct-pw-meter-label">Not changing password</span>
                  </div>
                  <ul class="acct-pw-rules d-none" id="acctPwRules">
                    <li data-pw-rule="length"><i class="far fa-circle"></i>At least 8 characters</li>
                    <li data-pw-rule="mixed"><i class="far fa-circle"></i>Upper- and lowercase letters</li>
                    <li data-pw-rule="number"><i class="far fa-circle"></i>At least one number</li>
                    <li data-pw-rule="match"><i class="far fa-circle"></i>Both passwords match</li>
                  </ul>
                </div>
              </div>

              <div class="card nutrition-panel acct-save-bar mb-4">
                <div class="card-body">
                  <div class="row align-items-end">
                    <div class="col-md-6 form-group mb-md-0">
                      <label for="old_password"><i class="fas fa-lock mr-1"></i>Current password <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="old_password" name="old_password" autocomplete="current-password" required placeholder="Required to save any change">
                        <div class="input-group-append">
                          <button type="button" class="btn btn-outline-secondary" data-toggle-password="old_password" title="Show password" aria-pressed="false"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="invalid-feedback">Enter your correct current password.</div>
                      </div>
                    </div>
                    <div class="col-md-6 text-md-right">
                      <div class="acct-dirty-note mb-2" id="acctDirtyNote" aria-live="polite"></div>
                      <button type="button" class="btn btn-outline-light mr-1" id="nutritionProfileDiscardBtn" disabled>
                        <i class="fas fa-undo mr-1"></i> Discard
                      </button>
                      <button type="submit" class="btn btn-success" id="nutritionProfileSaveBtn" disabled>
                        <i class="fas fa-save mr-1"></i> Save changes
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>

        <div class="row">
          <div class="col-xl-8 offset-xl-4 col-lg-7 offset-lg-5">
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
