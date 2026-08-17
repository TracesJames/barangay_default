<?php
include_once 'connection.php';
require_once 'includes/barangay_context.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_type'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $query = $con->prepare($sql) or die($con->error);
    $query->bind_param('s', $user_id);
    $query->execute();
    $row = $query->get_result()->fetch_assoc();
    $account_type = $row['user_type'] ?? '';
    if ($account_type === 'admin') {
        echo '<script>window.location.href="admin/dashboard.php";</script>';
    } elseif ($account_type === 'secretary') {
        echo '<script>window.location.href="secretary/dashboard.php";</script>';
    } else {
        echo '<script>window.location.href="resident/dashboard.php";</script>';
    }
    exit;
}

$selectedBarangay = barangay_resolve_registration($con);
$showPicker = ($selectedBarangay === null);
$barangays = $showPicker ? barangay_list_all($con) : [];

if (!$showPicker) {
    $barangay = $selectedBarangay['barangay'];
    $zone = $selectedBarangay['zone'];
    $district = $selectedBarangay['district'];
    $image = $selectedBarangay['image'];
    $image_path = $selectedBarangay['image_path'];
    $id = $selectedBarangay['id'];
    $postal_address = $selectedBarangay['postal_address'];
    $default_municipality = $selectedBarangay['address'] ?? '';
    $barangayLogo = barangay_public_logo_url($selectedBarangay);
    $navTitle = $barangay;
} else {
    $barangay = 'City of Valencia Portal';
    $zone = '';
    $district = '';
    $postal_address = '';
    $default_municipality = '';
    $barangayLogo = barangay_public_logo_url();
    $navTitle = 'CITY OF VALENCIA PORTAL';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $showPicker ? 'Select Barangay | Register' : 'Register | ' . barangay_h($barangay) ?></title>
<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/bs-stepper/css/bs-stepper.min.css">
  <link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="assets/plugins/step-wizard/css/smart_wizard_all.min.css">
<?php if ($showPicker) {
  $tailwindDepth = 0;
  require_once 'includes/partials/tailwind_cdn.php';
} else { ?>
  <link rel="stylesheet" href="assets/css/resident-portal.css">
<?php } ?>
<?php require_once 'includes/head_csrf_root.php'; ?>
  <link rel="stylesheet" href="assets/css/public-portal.css">
<?php if ($showPicker) {
  echo '  <link rel="stylesheet" href="assets/css/register-page.css">' . PHP_EOL;
} else {
  echo '  <link rel="stylesheet" href="assets/css/register-page.css">' . PHP_EOL;
} ?>
</head>
<body class="hold-transition layout-top-nav barangay-portal public-portal-page<?= $showPicker ? ' register-picker-page' : ' register-form-page dark-mode' ?>">

<div class="wrapper">
  <?php
  $publicNavActive = 'register';
  $publicNavTitle = $navTitle;
  $publicNavLogo = $barangayLogo;
  $publicBrandHref = $showPicker ? 'index.php' : barangay_register_url($id);
  require_once 'includes/partials/public_navbar.php';
  ?>

  <div class="content-wrapper public-portal-bg<?= $showPicker ? ' register-picker-bg' : '' ?>" id="backGround">
    <div class="content">
<?php if ($showPicker): ?>
      <div id="tailwind-scope" class="register-picker-scope relative z-10 mx-auto max-w-7xl px-5 pb-16 pt-8 text-white">
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
          <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-brand/10 blur-3xl"></div>
          <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-brand/10 blur-3xl"></div>
        </div>
        <div class="relative z-10">
        <header class="mb-8 text-center">
          <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-white/60">
            <i class="fas fa-user-plus text-teal-400"></i> Resident Registration
          </div>
          <h1 class="text-3xl font-extrabold tracking-tight">Select Your Barangay</h1>
          <p class="mt-2 text-white/60">Choose your barangay to continue registration</p>
        </header>

        <div class="mx-auto mb-6 max-w-md">
          <div class="relative">
            <i class="fas fa-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-white/40"></i>
            <input type="search" id="registerBarangaySearch"
              class="w-full rounded-full border border-white/10 bg-white/[0.06] py-2.5 pl-10 pr-4 text-sm text-white placeholder-white/40 outline-none transition focus:border-accent/60 focus:bg-white/[0.08] focus:ring-2 focus:ring-accent/20"
              placeholder="Search barangay…" autocomplete="off">
          </div>
        </div>

        <div id="registerBarangayGrid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <?php foreach ($barangays as $row) :
          $logo = barangay_public_logo_url($row);
          $residents = barangay_count_residents($con, $row['id']);
          $searchKey = strtolower(trim($row['barangay'] . ' ' . $row['zone'] . ' ' . $row['district']));
        ?>
        <a href="<?= barangay_h(barangay_register_url($row['id'])) ?>"
          class="register-card group flex flex-col items-center rounded-2xl border-2 border-white/10 bg-white/[0.04] px-4 pb-5 pt-6 text-center no-underline text-white transition duration-200 hover:-translate-y-1 hover:border-accent/50 hover:bg-accent/10 hover:shadow-xl hover:no-underline"
          data-search="<?= barangay_h($searchKey) ?>">
          <img src="<?= barangay_h($logo) ?>" alt="" class="mb-4 h-20 w-20 rounded-full border-[3px] border-white/20 object-cover shadow-lg sm:h-[88px] sm:w-[88px]">
          <p class="register-card-name mb-0.5 text-sm font-extrabold leading-snug group-hover:text-white sm:text-base"><?= barangay_h($row['barangay']) ?></p>
          <p class="mb-3 text-[0.7rem] leading-relaxed text-white/50"><?= barangay_h($row['zone'] . ' · ' . $row['district']) ?></p>
          <span class="mb-3 inline-block rounded-full bg-white/10 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-white/80">
            <i class="fas fa-users mr-1"></i><?= number_format($residents) ?> residents
          </span>
          <span class="mt-auto w-full rounded-full border border-white/20 bg-white/[0.08] py-2 text-[0.7rem] font-bold uppercase tracking-wider transition group-hover:border-accent/60 group-hover:bg-accent/30">
            Register Here
          </span>
        </a>
        <?php endforeach; ?>
        </div>

        <div id="registerBarangayEmpty" class="hidden py-16 text-center text-white/50">
          <i class="fas fa-search mb-4 block text-4xl opacity-30"></i>
          <p>No barangay matches your search.</p>
        </div>
        </div>
      </div>
<?php else: ?>
      <div class="container-fluid py-5">
        <div class="text-center text-white mb-4">
          <img src="<?= barangay_h($barangayLogo) ?>" alt="<?= barangay_h($barangay) ?>" class="logo barangay-form-logo mb-3">
          <h2 class="font-weight-bold mb-1"><?= barangay_h($barangay) ?></h2>
          <p class="mb-1 opacity-75"><?= barangay_h($zone . ' · ' . $district) ?></p>
          <p class="small text-white-50 mb-2">Resident Registration</p>
          <a href="register.php" class="btn btn-sm btn-outline-light"><i class="fas fa-exchange-alt mr-1"></i> Change Barangay</a>
        </div>

<form id="registerResidentForm" method="POST" enctype="multipart/form-data" autocomplete="off">
<?php require_once 'includes/csrf.php'; echo csrf_field(); ?>
<input type="hidden" name="add_barangay_id" id="add_barangay_id" value="<?= barangay_h($id) ?>">
<div class="row mb-3">
  <div class="col-sm-4">
    <div class="card h-100 register-panel-card">
      <div class="card-body">
        <div class="text-center mb-3">
          <img src="<?= barangay_h($barangayLogo) ?>" alt="<?= barangay_h($barangay) ?>" class="barangay-form-logo">
          <p class="small text-muted mb-0 mt-2 font-weight-bold"><?= barangay_h($barangay) ?></p>
        </div>
        <div class="text-center">
          <img class="profile-user-img img-fluid img-thumbnail" src="assets/dist/img/blank_image.png" alt="User profile picture" style="cursor: pointer;" id="image_residence">
          <input type="file" name="add_image_residence" id="add_image_residence" style="display: none;">
        </div>
        <h3 class="profile-username text-center"><span id="keyup_first_name"></span> <span id="keyup_last_name"></span></h3>
        <div class="row">
          <div class="col-sm-12">
            <div class="form-group">
              <label>Voters</label>
              <select name="add_voters" id="add_voters" class="form-control">
                <option value=""></option>
                <option value="NO">NO</option>
                <option value="YES">YES</option>
              </select>
            </div>
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <label>Gender</label>
              <select name="add_gender" id="add_gender" class="form-control">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <label>Date of Birth</label>
              <input type="date" class="form-control" id="add_birth_date" name="add_birth_date">
            </div>
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <label>Place of Birth</label>
              <input type="text" class="form-control" id="add_birth_place" name="add_birth_place">
            </div>
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <label>PWD</label>
              <select name="add_pwd" id="add_pwd" class="form-control">
                <option value=""></option>
                <option value="NO">NO</option>
                <option value="YES">YES</option>
              </select>
            </div>
          </div>
          <div class="col-sm-12" id="pwd_check" style="display: none;">
            <div class="form-group">
              <label>TYPE OF PWD</label>
              <input type="text" class="form-control" id="add_pwd_info" name="add_pwd_info">
            </div>
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <label>Single Parent</label>
              <select name="add_single_parent" id="add_single_parent" class="form-control">
                <option value=""></option>
                <option value="NO">NO</option>
                <option value="YES">YES</option>
              </select>
            </div>
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <label>Indigenous People (IP)</label>
              <select name="add_indigenous" id="add_indigenous" class="form-control">
                <option value=""></option>
                <option value="NO">NO</option>
                <option value="YES">YES</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-8">
    <div class="card card-tabs h-100 register-panel-card">
      <div class="card-header p-0 pt-1">
        <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="basic-info-tab" data-toggle="pill" href="#basic-info" role="tab" aria-controls="basic-info" aria-selected="true">Basic Info</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="other-info-tab" data-toggle="pill" href="#other-info" role="tab" aria-controls="other-info" aria-selected="false">Other Info</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="guardian-tab" data-toggle="pill" href="#guardian" role="tab" aria-controls="guardian" aria-selected="false">Guardian</a>
          </li>
          <?php require __DIR__ . '/includes/partials/residence_family_tab_nav.php'; ?>
          <li class="nav-item">
            <a class="nav-link" id="account-tab" data-toggle="pill" href="#account" role="tab" aria-controls="account" aria-selected="false">Account</a>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content" id="custom-tabs-one-tabContent">
          <div class="tab-pane fade active show" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
              <p class="lead text-center">Personal Details</p>
              <div class="row">
                <div class="col-sm-12">
                  <div class="form-group">
                    <label>First Name </label>
                    <input type="text" class="form-control" id="add_first_name" name="add_first_name">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" class="form-control" id="add_middle_name" name="add_middle_name">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <label>Last Name </label>
                    <input type="text" class="form-control" id="add_last_name" name="add_last_name">
                  </div>
                </div>
              </div>
                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Suffix</label>
                      <input type="text" class="form-control" id="add_suffix" name="add_suffix">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Civil Status</label>
                      <select name="add_civil_status" id="add_civil_status" class="form-control">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Religion</label>
                      <input type="text" class="form-control" id="add_religion" name="add_religion">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Nationality</label>
                      <input type="text" class="form-control" id="add_nationality" name="add_nationality">
                    </div>
                  </div>
                </div>
          </div>
          <div class="tab-pane fade" id="other-info" role="tabpanel" aria-labelledby="other-info-tab">
                <p class="lead text-center">Address</p>
                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Municipality</label>
                      <input type="text" class="form-control" id="add_municipality" name="add_municipality" value="<?= barangay_h($default_municipality) ?>">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Zip</label>
                      <input type="text" class="form-control" id="add_zip" name="add_zip">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Barangay</label>
                      <input type="text" class="form-control" id="add_barangay" name="add_barangay" value="<?= barangay_h($barangay) ?>" readonly>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>House Number</label>
                      <input type="text" class="form-control" id="add_house_number" name="add_house_number">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>Street</label>
                      <input type="text" class="form-control" id="add_street" name="add_street">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label>PUROK</label>
                      <input type="text" class="form-control" id="add_address" name="add_address" placeholder="Purok / Sitio">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label>Email Address</label>
                      <input type="email" class="form-control" id="add_email_address" name="add_email_address">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label>Contact Number</label>
                      <input type="text" class="form-control" id="add_contact_number" name="add_contact_number">
                    </div>
                  </div>
                </div>
          </div>
          <div class="tab-pane fade" id="guardian" role="tabpanel" aria-labelledby="guardian-tab">
            <p class="lead text-center">Guardian Information</p>
            <?php require __DIR__ . '/includes/partials/residence_minor_guardian_notice.php'; ?>
            <div class="row">
              <div class="col-sm-12 minor-guardian-field">
                <div class="form-group">
                  <label>Father's Name</label>
                  <input type="text" class="form-control" id="add_fathers_name" name="add_fathers_name">
                </div>
              </div>
              <div class="col-sm-12 minor-guardian-field">
                <div class="form-group">
                  <label>Mother's Name</label>
                  <input type="text" class="form-control" id="add_mothers_name" name="add_mothers_name">
                </div>
              </div>
              <div class="col-sm-12 minor-guardian-field">
                <div class="form-group">
                  <label>Guardian</label>
                  <input type="text" class="form-control" id="add_guardian" name="add_guardian">
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Guardian Contact</label>
                  <input type="text" class="form-control" id="add_guardian_contact" name="add_guardian_contact">
                </div>
              </div>
            </div>
          </div>
          <?php require __DIR__ . '/includes/partials/residence_family_tab_content.php'; ?>
          <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
            <p class="lead text-center">Account Credentials</p>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" class="form-control" id="add_username" name="add_username">
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" class="form-control" id="add_password" name="add_password">
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Confirm Password</label>
                  <input type="password" class="form-control" id="add_confirm_password" name="add_confirm_password">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer text-center">
        <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-user-plus mr-1"></i> Register</button>
      </div>
    </div>
  </div>
</div>
</form>
      </div>
<?php endif; ?>
    </div>
  </div>

  <footer class="main-footer text-white barangay-footer">
    <div class="float-right d-none d-sm-block"></div>
  <?php if (!$showPicker && $postal_address !== ''): ?>
  <i class="fas fa-map-marker-alt"></i> <?= barangay_h($postal_address) ?>
  <?php endif; ?>
  </footer>
</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 0; require_once 'includes/scripts_csrf.php'; ?>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<?php if ($showPicker): ?>
<script>
  $(function () {
    $('#registerBarangaySearch').on('input', function () {
      var query = ($(this).val() || '').toLowerCase().trim();
      var visible = 0;
      $('#registerBarangayGrid .register-card').each(function () {
        var match = !query || String($(this).data('search') || '').indexOf(query) !== -1;
        $(this).toggleClass('hidden', !match);
        if (match) { visible++; }
      });
      $('#registerBarangayEmpty').toggleClass('hidden', visible > 0);
    });
  });
</script>
<?php elseif (!$showPicker): ?>
<script src="assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="assets/plugins/jquery-validation/additional-methods.min.js"></script>
<script src="assets/js/residence-family.js"></script>
<script src="assets/js/residence-minor-guardian.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script>
  $(document).ready(function(){
    $("#add_pwd").change(function(){
      var pwd_check = $(this).val();
      if(pwd_check == 'YES'){
        $("#pwd_check").css('display', 'block');
        $("#add_pwd_info").prop('disabled', false);
      }else{
        $("#pwd_check").css('display', 'none');
        $("#add_pwd_info").prop('disabled', true);
      }
    });

    $("#image_residence").click(function(){
      $("#add_image_residence").click();
    });

    $("#add_image_residence").change(function(){
      var reader = new FileReader();
      reader.onload = function(e){
        $("#image_residence").attr('src', e.target.result);
      };
      reader.readAsDataURL(this.files[0]);
    });

    $("#add_first_name").keyup(function(){
      $("#keyup_first_name").text($(this).val());
    });
    $("#add_last_name").keyup(function(){
      $("#keyup_last_name").text($(this).val());
    });

    $.validator.setDefaults({
      submitHandler: function (form) {
        $.ajax({
          url: 'signup/newResidence.php',
          type: 'POST',
          data: new FormData(form),
          processData: false,
          contentType: false,
          cache: false,
          success:function(data){
            if(data == 'errorPassword'){
              Swal.fire({
                title: '<strong class="text-danger">ERROR</strong>',
                icon: 'error',
                html: '<b>Password not Match<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              });
            }else if(data == 'errorUsername'){
              Swal.fire({
                title: '<strong class="text-danger">ERROR</strong>',
                icon: 'error',
                html: '<b>Username is Already Taken<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              });
            }else if(data == 'errorMinorGuardian'){
              Swal.fire({
                title: '<strong class="text-danger">ERROR</strong>',
                icon: 'error',
                html: '<b>Residents 17 years old and below must have a Guardian name or Parent name.<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              }).then(function(){
                $('#guardian-tab').tab('show');
              });
            }else if(data == 'errorBarangay'){
              Swal.fire({
                title: '<strong class="text-danger">ERROR</strong>',
                icon: 'error',
                html: '<b>Invalid barangay. Please register again.<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              }).then(function(){
                window.location.href = 'register.php';
              });
            }else{
              Swal.fire({
                title: '<strong class="text-success">SUCCESS</strong>',
                icon: 'success',
                html: '<b>Registration successful for <?= barangay_h($barangay) ?>. You may now log in.<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
                allowOutsideClick: false,
              }).then(function(){
                window.location.href = 'login.php';
              });
            }
          }
        }).fail(barangayAjaxError);
      }
    });

    $('#registerResidentForm').validate({
      rules: {
        add_voters: { required: true },
        add_pwd: { required: true },
        add_single_parent: { required: true },
        add_indigenous: { required: true },
        add_first_name: { required: true },
        add_last_name: { required: true },
        add_birth_date: { required: true },
        add_username: { required: true },
        add_password: { required: true },
        add_confirm_password: { required: true, equalTo: '#add_password' },
        add_email_address: { email: true },
        add_guardian: { minorGuardianGroup: true },
      },
      errorElement: 'span',
      errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
      },
      highlight: function (element) {
        $(element).addClass('is-invalid');
      },
      unhighlight: function (element) {
        $(element).removeClass('is-invalid');
      }
    });
  });
</script>
<?php endif; ?>
</body>
</html>
