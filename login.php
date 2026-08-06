<?php 
include_once 'connection.php';
require_once 'includes/barangay_context.php';
require_once 'includes/nutrition_context.php';
require_once 'includes/csrf.php';
require_once 'includes/session_guard.php';

$loginHub = (isset($_GET['hub']) && $_GET['hub'] === 'nutrition') ? 'nutrition' : 'barangay';
if (isset($_GET['hub']) && in_array($_GET['hub'], ['barangay', 'nutrition'], true)) {
  $_SESSION['preferred_hub'] = $loginHub;
} elseif (!empty($_SESSION['preferred_hub']) && $_SESSION['preferred_hub'] === 'nutrition') {
  $loginHub = 'nutrition';
}
$isNutritionLogin = $loginHub === 'nutrition';
$loginHubTitle = $isNutritionLogin ? 'Nutrition Hub' : 'Barangay Hub';
$loginHubSubtitle = $isNutritionLogin
  ? 'Household surveys, assessments, BNP & city nutrition reports'
  : 'Residents, officials, certificates & barangay administration';
$loginReason = trim((string) ($_GET['reason'] ?? ''));

try{

  if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_type'])) {
    // Drop stale/taken sessions before auto-redirecting into the portal.
    barangay_session_guard_enforce($con, 'login.php');

    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = '$user_id'";
    $query = $con->query($sql) or die ($con->error);
    $row = $query->fetch_assoc();
    $account_type = $row['user_type'];
    if ($account_type == 'admin') {
      $nutritionToken = nutrition_admin_login_token($con, (string) $user_id);
      if ($nutritionToken !== null) {
        echo '<script>
              window.location.href="' . nutrition_admin_login_url($nutritionToken) . '";
          </script>';
      } elseif (barangay_user_is_ssa($con, (string) $user_id) || barangay_user_is_barangay_hub_super_admin($con, (string) $user_id)) {
        if (!empty($_SESSION['preferred_hub']) && $_SESSION['preferred_hub'] === 'nutrition') {
          echo '<script>window.location.href="admin/nutritionSuperDashboard.php";</script>';
        } else {
          echo '<script>window.location.href="admin/superDashboard.php";</script>';
        }
      } elseif (barangay_user_is_city_admin($con, (string) $user_id)) {
        if (!empty($_SESSION['preferred_hub']) && $_SESSION['preferred_hub'] === 'nutrition') {
          echo '<script>window.location.href="admin/barangayHub.php?picker=1&system=nutrition";</script>';
        } else {
          echo '<script>window.location.href="admin/barangayHub.php?picker=1";</script>';
        }
      } else {
        echo '<script>
              window.location.href="admin/dashboard.php";
          </script>';
      }
    
    } elseif ($account_type == 'secretary') {
        echo '<script>
            window.location.href="secretary/dashboard.php";
        </script>';
    
    } else {
        echo '<script>
        window.location.href="resident/dashboard.php";
    </script>';
    
}





}

$sql = "SELECT * FROM `barangay_information` ORDER BY barangay ASC LIMIT 1";
  $query = $con->prepare($sql) or die ($con->error);
  $query->execute();
  $result = $query->get_result();
  $portalBarangay = $result->fetch_assoc();
  if ($portalBarangay) {
      $barangay = $portalBarangay['barangay'];
      $zone = $portalBarangay['zone'];
      $district = $portalBarangay['district'];
      $image = $portalBarangay['image'];
      $image_path = $portalBarangay['image_path'];
      $id = $portalBarangay['id'];
      $postal_address = $portalBarangay['postal_address'];
  } else {
      $barangay = 'City of Valencia Portal';
      $zone = '';
      $district = '';
      $postal_address = '';
  }
  $navLogo = barangay_public_logo_url();

  $slideDir = __DIR__ . '/assets/images/portal-slides';
  $slides = [];
  if (is_dir($slideDir)) {
      foreach (scandir($slideDir) ?: [] as $file) {
          if ($file === '.' || $file === '..') {
              continue;
          }
          $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
          if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
              continue;
          }
          $slides[] = 'assets/images/portal-slides/' . rawurlencode($file);
      }
      sort($slides, SORT_NATURAL | SORT_FLAG_CASE);
  }
  if ($slides === []) {
      $slides[] = 'assets/logo/cover.jpg';
  }

}catch(Exception $e){
  echo $e->getMessage();
  if (!isset($slides) || !is_array($slides) || $slides === []) {
      $slides = ['assets/logo/cover.jpg'];
  }
  if (!isset($navLogo)) {
      $navLogo = 'assets/logo/logo.png';
  }
  if (!isset($postal_address)) {
      $postal_address = '';
  }
}







?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | <?= barangay_h($loginHubTitle) ?></title>
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">

  <!-- Theme style -->
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">
<?php require_once 'includes/head_csrf_root.php'; ?>
  <link rel="stylesheet" href="assets/css/public-portal.css?v=20260730a">
</head>
<body class="hold-transition layout-top-nav barangay-portal public-portal-page public-portal-page--slideshow<?= $isNutritionLogin ? ' public-portal-page--nutrition' : '' ?>">


<div class="wrapper">

  <?php
  $publicNavActive = 'login';
  $publicNavTitle = 'CITY OF VALENCIA PORTAL';
  $publicNavLogo = $navLogo;
  require_once 'includes/partials/public_navbar.php';
  ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper public-portal-bg">
    <div class="public-slideshow" aria-hidden="true">
      <div class="public-slideshow__track">
        <?php foreach ($slides as $i => $slideUrl): ?>
          <div
            class="public-slideshow__slide<?= $i === 0 ? ' is-active' : '' ?>"
            style="background-image: url('<?= barangay_h($slideUrl) ?>');"
          ></div>
        <?php endforeach; ?>
      </div>
      <div class="public-slideshow__veil"></div>
    </div>

    <div class="content px-4">
      <div class="public-auth-wrap">
        <div class="row justify-content-center w-100 mx-0">
          <form id="loginForm" method="post" class="w-100" style="max-width: 460px;">
          <?= csrf_field(); ?>
          <input type="hidden" name="force_login" id="force_login" value="0">
          <div class="card barangay-login-card">
            <div class="card-body text-center">
              <div class="col-sm-12">
                <img src="<?= barangay_h($navLogo) ?>" alt="logo" class="img-circle logo">
              </div>
              <div class="col-sm-12">
                <h1 class="card-text">City of Valencia Portal</h1>
                <p class="public-login-hub-sub mb-3">Sign in to Barangay Hub or Nutrition Hub</p>
                <div class="public-login-hub-toggle" role="tablist" aria-label="Select hub">
                  <a href="login.php?hub=barangay"
                     class="public-login-hub-option<?= !$isNutritionLogin ? ' is-active' : '' ?><?= !$isNutritionLogin ? ' public-login-hub-option--barangay' : '' ?>"
                     role="tab"
                     aria-selected="<?= !$isNutritionLogin ? 'true' : 'false' ?>">
                    <i class="fas fa-city" aria-hidden="true"></i> Barangay Hub
                  </a>
                  <a href="login.php?hub=nutrition"
                     class="public-login-hub-option<?= $isNutritionLogin ? ' is-active' : '' ?><?= $isNutritionLogin ? ' public-login-hub-option--nutrition' : '' ?>"
                     role="tab"
                     aria-selected="<?= $isNutritionLogin ? 'true' : 'false' ?>">
                    <i class="fas fa-leaf" aria-hidden="true"></i> Nutrition Hub
                  </a>
                </div>
                <p class="public-login-hub-sub mt-2 mb-0" id="loginHubHint"><?= barangay_h($loginHubSubtitle) ?></p>
              </div>
              <input type="hidden" name="preferred_hub" id="preferred_hub" value="<?= barangay_h($loginHub) ?>">
             
              <div class="col-sm-12 mt-4">
                <div class="form-group">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" id="username" name="username" class="form-control" placeholder="USERNAME OR RESIDENT NUMBER" >
                  </div>
                </div>
              </div>
              <div class="col-sm-12 mt-4">
                <div  class="form-group">
                  <div class="input-group mb-3" id="show_hide_password">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                    </div>
                    <input type="password"  id="password" name="password" class="form-control" placeholder="PASSWORD"  style="border-right: none;">
                    <div class="input-group-append bg">
                      <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                    </div>
                  </div>
                </div>
              </div>
            <div class="col-sm-12 text-right">
                    <a href="forgot.php">Forgot Password</a>
            </div>
            <div class="col-sm-12 mt-4">
                <button type="submit" class="btn btn-flat bg-blue btn-lg btn-block">Sign In</button>
            </div>
          </div>
          </form>
        </div>

  
      

      </div>


      <br>
        <br>
        <br>
        
       
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

 

 


</div>
<!-- ./wrapper -->
<footer class="main-footer text-white barangay-footer">
    <div class="float-right d-none d-sm-block">
    
    </div>
  <i class="fas fa-map-marker-alt"></i> <?= $postal_address ?> 
  </footer>




<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 0; require_once 'includes/scripts_csrf.php'; ?>

<!-- Bootstrap -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
(function () {
  var slides = document.querySelectorAll('.public-slideshow__slide');
  if (slides.length < 2) return;
  var index = 0;
  setInterval(function () {
    slides[index].classList.remove('is-active');
    index = (index + 1) % slides.length;
    slides[index].classList.add('is-active');
  }, 5500);
})();
</script>

<script>
  $(document).ready(function() {
    var preferredHub = <?= json_encode($loginHub) ?>;

    function hubAwareAdminUrl(defaultUrl) {
      if (preferredHub === 'nutrition') {
        return 'admin/barangayHub.php?picker=1&system=nutrition';
      }
      return defaultUrl;
    }

    function hubAwareSuperUrl() {
      if (preferredHub === 'nutrition') {
        return 'admin/nutritionSuperDashboard.php';
      }
      return 'admin/superDashboard.php';
    }

    $("#loginForm").submit(function(e){
      e.preventDefault();
      var username = $("#username").val();
      var password = $("#password").val();
      if(username == '' || password == ''){
        barangayWarning('Required', 'Username and password are required.');
      }else{
        $.ajax({
          url: 'loginForm.php',
          type: 'POST',
          data: $(this).serialize(),
          success:function(data){
              data = (data || '').toString().trim();
              if(data == 'errorUsername' || data == 'errorPassword'){
                $('#force_login').val('0');
                barangayError('Login failed', 'Incorrect username or password.');
              }else if(data == 'errorAlreadyLoggedIn'){
                Swal.fire({
                  title: 'Already signed in',
                  html: '<p class="mb-0">This account is already logged in on another browser or device.</p><p class="mt-2 mb-0">Only one active session is allowed. End the other session and continue here?</p>',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonText: 'End other session & sign in',
                  cancelButtonText: 'Cancel',
                  width: '440px'
                }).then(function(result){
                  if(result.value || result.isConfirmed){
                    $('#force_login').val('1');
                    $('#loginForm').trigger('submit');
                  }else{
                    $('#force_login').val('0');
                  }
                });
              }else if(data == 'admin_barangay'){
                $('#force_login').val('0');
                Swal.fire({
                  title: 'Success',
                  html: '<b>Login successful</b>',
                  icon: 'success',
                  width: '400px',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = preferredHub === 'nutrition'
                    ? 'admin/nutritionDashboard.php'
                    : 'admin/dashboard.php';
                })
              }else if(data == 'nutrition_dashboard'){
                $('#force_login').val('0');
                Swal.fire({
                  title: 'Success',
                  html: '<b>Login successful</b>',
                  icon: 'success',
                  width: '400px',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = 'admin/nutritionDashboard.php';
                })
              }else if(data == 'nutrition_admin'){
                $('#force_login').val('0');
                Swal.fire({
                  title: 'Success',
                  html: '<b>Login successful</b>',
                  icon: 'success',
                  width: '400px',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = 'admin/nutritionSuperDashboard.php';
                })
              }else if(data == 'super_admin'){
                $('#force_login').val('0');
                Swal.fire({
                  title: 'Success',
                  html: '<b>Login successful</b>',
                  icon: 'success',
                  width: '400px',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = hubAwareSuperUrl();
                })
              }else if(data == 'admin'){
                $('#force_login').val('0');
                Swal.fire({
                  title: 'Success',
                  html: '<b>Login successful</b>',
                  icon: 'success',
                  width: '400px',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = hubAwareAdminUrl('admin/barangayHub.php');
                })
              }else if(data == 'secretary'){
                $('#force_login').val('0');
                Swal.fire({
                  title: 'Success',
                  html: '<b>Login successful</b>',
                  icon: 'success',
                  width: '400px',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = 'secretary/dashboard.php';
                })
              }else if(data == 'resident'){
                $('#force_login').val('0');
                Swal.fire({
                  title: 'Success',
                  html: '<b>Login successful</b>',
                  icon: 'success',
                  width: '400px',
                  showConfirmButton: false,
                  allowOutsideClick: false,
                  timer: 2000
                }).then(()=>{
                  window.location.href = 'resident/dashboard.php';
                })
              }
          }
        }).fail(barangayAjaxError);
      }
    })

    <?php if ($loginReason === 'session_taken'): ?>
    Swal.fire({
      title: 'Signed out',
      html: '<b>This account was signed in on another browser or device. Only one session is allowed at a time.</b>',
      icon: 'warning',
      width: '440px',
      confirmButtonText: 'OK'
    });
    <?php endif; ?>


    $("#show_hide_password a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password input').attr("type") == "text"){
            $('#show_hide_password input').attr('type', 'password');
            $('#show_hide_password i').addClass( "fa-eye-slash" );
            $('#show_hide_password i').removeClass( "fa-eye" );
        }else if($('#show_hide_password input').attr("type") == "password"){
            $('#show_hide_password input').attr('type', 'text');
            $('#show_hide_password i').removeClass( "fa-eye-slash" );
            $('#show_hide_password i').addClass( "fa-eye" );
        }
    });
});
</script>


</body>
</html>
