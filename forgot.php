<?php 
include_once 'connection.php';
require_once 'includes/barangay_context.php';

try{

          if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_type'])) {


            $user_id = $_SESSION['user_id'];
            $sql = "SELECT * FROM users WHERE id = '$user_id'";
            $query = $con->query($sql) or die ($con->error);
            $row = $query->fetch_assoc();
            $account_type = $row['user_type'];
            if ($account_type == 'admin') {
            echo '<script>
                    window.location.href="admin/dashboard.php";
                </script>';
            
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

}catch(Exception $e){
  echo $e->getMessage();
}







?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password | City of Valencia Portal</title>
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">

  <!-- Theme style -->
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/sweetalert2/css/sweetalert2.min.css">
<?php require_once 'includes/head_csrf_root.php'; ?>
  <link rel="stylesheet" href="assets/css/public-portal.css">
</head>
<body class="hold-transition layout-top-nav barangay-portal public-portal-page">


<div class="wrapper">

  <?php
  $publicNavActive = '';
  $publicNavTitle = 'CITY OF VALENCIA PORTAL';
  $publicNavLogo = $navLogo;
  require_once 'includes/partials/public_navbar.php';
  ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper public-portal-bg">
    <div class="public-ambient" aria-hidden="true">
      <div class="public-ambient-glow public-ambient-glow--tl"></div>
      <div class="public-ambient-glow public-ambient-glow--br"></div>
    </div>

    <div class="content px-4">
      <div class="public-auth-wrap">
        <div class="row justify-content-center w-100 mx-0">
         <form id="recoverForm" method="post" class="w-100" style="max-width: 460px;">
          <?= csrf_field(); ?>
          <div class="card barangay-login-card">
            <div class="card-body text-center">
              <div class="col-sm-12">
                <img src="<?= barangay_h($navLogo) ?>" alt="logo" class="img-circle logo">
              </div>
              <div class="col-sm-12">
                <h1 class="card-text">FORGOT PASSWORD</h1>
              </div>
             
              <div class="col-sm-12 mt-4">
                <div class="form-group">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" id="username" name="username" class="form-control" placeholder="USERNAME OR RESIDENT NUMBER">
                  </div>
                </div>
              </div>
            <div class="col-sm-12 mt-4">
                <button type="submit" class="btn btn-flat bg-blue btn-lg btn-block" >Recover Account</button>
            </div>
          </div>
          </form>
        </div>

  
      

      </div>

<br>
<br>
<br>
<br>
<br>
      <br>
        <br>
        <br>
        
       
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->


 


</div>
<!-- ./wrapper -->





<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 0; require_once 'includes/scripts_csrf.php'; ?>

<!-- Bootstrap -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.js"></script>
<script src="assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="assets/plugins/jquery-validation/additional-methods.min.js"></script>
<div id="show_number"></div>

<script>
  $(document).ready(function(){



   



      $("#recoverForm").submit(function(e){
          e.preventDefault();
        var username = $("#username").val();

        $("#show_number").html('');
        
        if(username != ''){


    
          $.ajax({
            url: 'recoverAccount.php',
            type: 'POST',
            data:{username:username},
            cache: false,
            success:function(data){
              $("#show_number").html(data);
              $("#recoverModal").modal('show');

            }
          })
          
         

        }else{

          Swal.fire({
            title: '<strong class="text-warning">TYPE YOUR USERNAME</strong>',
            type: 'error',
            showConfirmButton: true,
          })

        }



      })
 


    
 



    


    


  })
</script>


</body>
</html>
