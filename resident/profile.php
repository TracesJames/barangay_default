<?php 

include_once '../connection.php';
include_once '../includes/auth_resident.php';
try{
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident'){

    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $username = $row_user['username'];
    $old_password = $row_user['password'];
    $first_name_user = $row_user['first_name'];
    $last_name_user = $row_user['last_name'];
    $user_type = $row_user['user_type'];
    $user_image = $row_user['image'];


    $sql_resident = "SELECT * FROM residence_information WHERE residence_id = '$user_id'";
    $query_resident = $con->query($sql_resident) or die ($con->error);
    $row_resident = $query_resident->fetch_assoc();


    if($row_resident['image'] != ''){
      $iamge_resident = '<img src="'.$row_resident['image_path'].'" alt="resident Image" id="residentImage">';
    }else{
      $iamge_resident = '<img src="../assets/dist/img/blank_image.png" alt="resident Image" id="residentImage">';
    }



  }else{
   echo '<script>
          window.location.href = "../login.php";
        </script>';
  }

}catch(Exception $e){
  echo $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>

 
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/resident-portal.css">

  <style>
    


    
    #barangay_logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }
    #residentImage{ 
      height: 150px;
      width:auto;
      max-width:500px;
    }

    .logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }
    .content-wrapper{
      background-image: url('../assets/logo/cover.jpg');
      background-repeat:no-repeat;
background-size:contain;
background-size: cover;
background-position:center;
width: 100%;
  height: auto;
        animation-name: example;
        animation-duration: 5s;
       
       
    }


@keyframes example {
  from {opacity: 0;}
  to {opacity: 1.5;}
}





 
  </style>
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition layout-top-nav barangay-portal">

<div class="wrapper">

  <?php
  $residentNavActive = 'profile';
  require_once '../includes/partials/resident_navbar.php';
  ?>
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" >
    <!-- Content Header (Page header) -->
 
    
  
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content  " >
    <div class="container-fluid pt-5 resident-profile-banner">
      <br>
      <br>
        <div class="row justify-content-center">
          <form id="changeProfile" method="post">
          <div class="card resident-panel-card">
            <div class="card-body text-white">
              <div class="col-sm-12 text-center">
              <?=$iamge_resident ?>
              </div>
              <div class="col-sm-12">
                <h5 class="card-text" style="font-weight: 1000; color: #0036af">RESIDENT NUMBER - <?= $user_id; ?></h5>
              </div>

              <div class="col-sm-12 mt-4">
                <div class="form-group">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" id="username" name="username" class="form-control" placeholder="USERNAME" value="<?= $username ?>">
                  </div>
                </div>
              </div>
              <div class="col-sm-12 mt-4">
                <div  class="form-group">
                  <div class="input-group mb-3" id="show_hide_password_old">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                    </div>
                    <input type="password"  id="old_password" name="old_password" class="form-control" placeholder="OLD PASSWORD"  style="border-right: none;">
                    <div class="input-group-append bg">
                      <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-12 mt-4">
                <div  class="form-group">
                  <div class="input-group mb-3" id="show_hide_password">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                    </div>
                    <input type="password"  id="new_password" name="new_password" class="form-control" placeholder="NEW PASSWORD"  style="border-right: none;">
                    <div class="input-group-append bg">
                      <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-12 mt-4">
                <div  class="form-group">
                  <div class="input-group mb-3" id="show_hide_password_confirm">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                    </div>
                    <input type="password"  id="edit_confirm_password" name="edit_confirm_password" class="form-control" placeholder="CONFIRM PASSWORD"  style="border-right: none;" >
                    <div class="input-group-append bg">
                      <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                    </div>
                  </div>
                </div>
              </div>
            <div class="col-sm-12 mt-4">
                <button type="submit" class="btn btn-flat bg-blue btn-lg btn-block elevation-5">CHANGE PROFILE</button>
            </div>
          </div>
          </form>
        </div>

  
      

      </div>


      <br>
        <br>
        <br>



        </div>


     
          
               
      
     
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

 
  <footer class="main-footer barangay-footer text-white">
    <div class="float-right d-none d-sm-block">
    
    </div>
  <i class="fas fa-map-marker-alt"></i> <?= $postal_address ?> 
  </footer>
 


</div>
<!-- ./wrapper -->


<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>

<!-- Bootstrap -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../assets/plugins/jquery-validation/additional-methods.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>


<script>

  $(document).ready(function(){

    
 $(function () {
        $.validator.setDefaults({
          submitHandler: function (form) {

              var newPassword = $("#new_password").val();
              var edit_confirm_password = $("#edit_confirm_password").val();

              if(newPassword != edit_confirm_password){


                  
                        Swal.fire({
                            title: '<strong class="text-danger">ERROR</strong>',
                            type: 'error',
                            html: '<b>NEW PASSWORD AND CONFIRM PASSWORD NOT MATCH<b>',
                            width: '400px',
                            confirmButtonColor: '#6610f2',
                          })



              }else{


                $.ajax({
                    url: 'changeProfile.php',
                    type: 'POST',
                    data: new FormData(form),
                    processData: false,
                    contentType: false,
                    cache: false,
                    success:function(data){

                      if(data == 'error1'){
                          Swal.fire({
                            title: '<strong class="text-danger">ERROR</strong>',
                            type: 'error',
                            html: '<b>Username is Already Exist<b>',
                            width: '400px',
                            confirmButtonColor: '#6610f2',
                          })
                      }else if(data == 'error2'){

                        Swal.fire({
                            title: '<strong class="text-danger">ERROR</strong>',
                            type: 'error',
                            html: '<b>OLD PASSWORD IS WORNG<b>',
                            width: '400px',
                            confirmButtonColor: '#6610f2',
                          })

                      }else{
                        
                        Swal.fire({
                          title: '<strong class="text-success">SUCCESS</strong>',
                          type: 'success',
                          html: '<b>Updated Account has Successfully<b>',
                          width: '400px',
                          confirmButtonColor: '#6610f2',
                          allowOutsideClick: false,
                          showConfirmButton: false,
                          timer: 2000,
                        }).then(()=>{
                          $("#old_password").val('');
                          $("#new_password").val('');
                          $("#edit_confirm_password").val('');
                          
                       

                        })
                        

                      
                      }
                      
                    }
                }).fail(barangayAjaxError)

              }

               

           
          }
        });
      $('#changeProfile').validate({
  
        rules: {
          username: {
            required: true,
            minlength: 6
          },
          old_password: {
            required: true,
         
          },
          new_password: {
            minlength: 6
         
          },
         
        
        
        },
        messages: {
          username: {
            required: "This Field is required",
            minlength: "Username must be at least 6 characters long"
          },
          old_password: {
            required: "This Field is required",
        
          },
          new_password: {
            minlength: "Minimum Characters 6",
        
          },
        
         
          
            
        },
   
     
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
        
        },
        highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        },
      
      });
      
    })
    
   
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
    $("#show_hide_password_confirm a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password_confirm input').attr("type") == "text"){
            $('#show_hide_password_confirm input').attr('type', 'password');
            $('#show_hide_password_confirm i').addClass( "fa-eye-slash" );
            $('#show_hide_password_confirm i').removeClass( "fa-eye" );
        }else if($('#show_hide_password_confirm input').attr("type") == "password"){
            $('#show_hide_password_confirm input').attr('type', 'text');
            $('#show_hide_password_confirm i').removeClass( "fa-eye-slash" );
            $('#show_hide_password_confirm i').addClass( "fa-eye" );
        }
    });
    $("#show_hide_password_old a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password_old input').attr("type") == "text"){
            $('#show_hide_password_old input').attr('type', 'password');
            $('#show_hide_password_old i').addClass( "fa-eye-slash" );
            $('#show_hide_password_old i').removeClass( "fa-eye" );
        }else if($('#show_hide_password_old input').attr("type") == "password"){
            $('#show_hide_password_old input').attr('type', 'text');
            $('#show_hide_password_old i').removeClass( "fa-eye-slash" );
            $('#show_hide_password_old i').addClass( "fa-eye" );
        }
    });
  })
</script>


</body>
</html>
