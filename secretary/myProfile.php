<?php 

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
try{
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'secretary'){

    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $username_user = $row_user['username'];
    $first_name_user = $row_user['first_name'];
    $middle_name_user = $row_user['middle_name'];
    $last_name_user = $row_user['last_name'];
    $contact_number_user = $row_user['contact_number'];
    $user_type = $row_user['user_type'];
    $user_image = $row_user['image'];
    $user_image_path = $row_user['image_path'];

    if($user_image != ''){
      $image_user = '<img src="'.$user_image_path.'" alt="userImage" class="img-circle " width="15%" id="display_image" style="cursor: pointer">';
    }else{
      $image_user = '<img src="../assets/dist/img/image.png" alt="userImage" class="img-circle " width="15%" id="display_image" style="cursor: pointer">';
    }



    $sql = "SELECT * FROM `barangay_information`";
    $query = $con->prepare($sql) or die ($con->error);
    $query->execute();
    $result = $query->get_result();
    while($row = $result->fetch_assoc()){
        $barangay = $row['barangay'];
        $zone = $row['zone'];
        $district = $row['district'];
        $image = $row['image'];
        $image_path = $row['image_path'];
        $id = $row['id'];
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
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <style>
   #official_body .scrollOfficial{
    height: 52vh;
    overflow-y: auto;
    }
   #official_body .scrollOfficial::-webkit-scrollbar {
        width: 5px;
    }                                                    
                            
  #official_body  .scrollOfficial::-webkit-scrollbar-thumb {
        background: #6c757d; 
        --webkit-box-shadow: inset 0 0 6px #6c757d; 
    }
  #official_body  .scrollOfficial::-webkit-scrollbar-thumb:window-inactive {
      background: #6c757d; 
    }
  </style>
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__wobble " src="../assets/dist/img/loader.gif" alt="AdminLTELogo" height="70" width="70">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <h5><a class="nav-link text-white" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block" style="font-variant: small-caps;">
        <h5 class="nav-link text-white" ><?= $barangay ?></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" >-</h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" ><?= $zone ?></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" >-</h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" ><?= $district ?></h5>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="myProfile.php" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <?php 
                if($user_image != '' || $user_image != null || !empty($user_image)){
                  echo '<img src="../assets/dist/img/'.$user_image.'" class="img-size-50 mr-3 img-circle" alt="User Image" style="width:50px;height:50px;object-fit:cover;">';
                }else{
                  echo '<img src="../assets/dist/img/image.png" class="img-size-50 mr-3 img-circle" alt="User Image" style="width:50px;height:50px;object-fit:cover;">';
                }
              ?>
            
              <div class="media-body">
                <h3 class="dropdown-item-title py-3">
                  <?= ucfirst($first_name_user) .' '. ucfirst($last_name_user) ?>
                </h3>
              </div>
            </div>
            <!-- Message End -->
          </a>         
          <div class="dropdown-divider"></div>
          <a href="../logout.php" class="dropdown-item dropdown-footer">LOGOUT</a>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
    <!-- Brand Logo -->
    <a href="#" class="brand-link text-center">
    <?php 
        if($image != '' || $image != null || !empty($image)){
          echo '<img src="'.$image_path.'" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }else{
          echo ' <img src="../assets//logo//logo.png" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }

      ?>
      <span class="brand-text font-weight-light"></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
    

    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../assets/dist/img/logo.png" class="img-circle elevation-5 img-bordered-sm" alt="User Image">
        </div>
        <div class="info text-center">
          <a href="#" class="d-block text-bold"><?= strtoupper($user_type) ?></a>
        </div>
      </div>
      <!-- Sidebar Menu -->
      <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>
              Barangay Official
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
             
              <li class="nav-item">
                <a href="allOfficial.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>List of Official</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="officialEndTerm.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Official End Term</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link ">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Residence
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="newResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Residence</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="allResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>All Residence</p>
                </a>
              </li>              <li class="nav-item">
                <a href="familyHouseholdHead.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Family House Hold</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="archiveResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Archive Residence</p>
                </a>
              </li>
            </ul>
          </li>
          
          <li class="nav-item ">
            <a href="requestCertificate.php" class="nav-link">
              <i class="nav-icon fas fa-certificate"></i>
              <p>
                Certificate
              </p>
            </a>
          </li>
          <li class="nav-item ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user-shield"></i>
              <p>
                Users
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="usersResident.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Resident</p>
                </a>
              </li>

            </ul>
          </li>
       
          <li class="nav-item">
            <a href="blotterRecord.php" class="nav-link">
              <i class="nav-icon fas fa-clipboard"></i>
              <p>
                Blotter Record
              </p>
            </a>
          </li>
          <?php /* super-admin-only-nav */ if (!empty($isSuperAdmin)) : ?>

          <li class="nav-item">
            <a href="report.php" class="nav-link">
              <i class="nav-icon fas fa-bookmark"></i>
              <p>
                Reports
              </p>
            </a>
          </li>
         
          <li class="nav-item">
            <a href="systemLog.php" class="nav-link">
              <i class="nav-icon fas fa-history"></i>
              <p>
                System Logs
              </p>
            </a>
          </li>
          <?php endif; ?>
</ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid ">

                     


      <form id="changeProfile" method="post" enctype="multipart/form-data">
          
            <div class="card mx-5">
                <div class="card-header">
                  <div class="card-title font-weight-bold">
                    <i class="far fa-user"></i> PROFILE
                  </div>
                </div>
              <div class="card-body">
                  <div class="row">
                    <div class="col-sm-12 text-center mb-3">
                        <?= $image_user; ?>
                        <input type="file" id="image" name="image" class="d-none">
                    </div>
                  
                    <div class="col-sm-12 ">
                      <div class="form-group">
                        <div class="input-group mb-3">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span>
                          </div>
                          <input type="text" id="first_name" name="first_name" class="form-control" placeholder="FIRST NAME" value="<?= $first_name_user ?>" required pattern=".{2,}" oninvalid="setCustomValidity('2 Minimum Characters Required')"    onchange="try{setCustomValidity('')}catch(e){}">
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12 ">
                      <div class="form-group">
                        <div class="input-group mb-3">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span>
                          </div>
                          <input type="text" id="middle_name" name="middle_name" class="form-control" placeholder="MIDDLE NAME" value="<?= $middle_name_user ?>">
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12 ">
                      <div class="form-group">
                        <div class="input-group mb-3">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-user"></i></span>
                          </div>
                          <input type="text" id="last_name" name="last_name" class="form-control" placeholder="LAST NAME" value="<?= $last_name_user ?>" required pattern=".{2,}" oninvalid="setCustomValidity('2 Minimum Characters Required')"    onchange="try{setCustomValidity('')}catch(e){}">
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12 ">
                      <div class="form-group">
                        <div class="input-group mb-3">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-phone"></i></span>
                          </div>
                          <input type="text" id="contact_number" name="contact_number" class="form-control" placeholder="CONTACT NUMBER" value="<?= $contact_number_user ?>" required  pattern=".{11,}"  maxlength="11"  oninvalid="setCustomValidity('Please Input Exact Contact Number')"    onchange="try{setCustomValidity('')}catch(e){}">
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12 ">
                      <div class="form-group">
                        <div class="input-group mb-3">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-user-lock"></i></span>
                          </div>
                          <input type="text" id="username" name="username" class="form-control" placeholder="USERNAME" value="<?= $username_user ?>"  required pattern=".{6,}" oninvalid="setCustomValidity('6 Minimum Characters Required')"    onchange="try{setCustomValidity('')}catch(e){}">
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12 ">
                      <div  class="form-group">
                        <div class="input-group mb-3" id="show_hide_password_old">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                          </div>
                          <input type="password"  id="old_password" name="old_password" class="form-control" placeholder="OLD PASSWORD"  style="border-right: none;" required>
                          <div class="input-group-append bg">
                            <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12 ">
                      <div  class="form-group">
                        <div class="input-group mb-3" id="show_hide_password">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                          </div>
                          <input type="password"  id="new_password" name="new_password" class="form-control" placeholder="NEW PASSWORD"  style="border-right: none;"  pattern=".{6,}"  oninvalid="setCustomValidity('6 Minimum Characters Required')"    onchange="try{setCustomValidity('')}catch(e){}">
                          <div class="input-group-append bg">
                            <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12 ">
                      <div  class="form-group">
                        <div class="input-group mb-3" id="show_hide_password_confirm">
                          <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span>
                          </div>
                          <input type="password"  id="new_confirm_password" name="new_confirm_password" class="form-control" placeholder="CONFIRM PASSWORD"  style="border-right: none;" >
                          <div class="input-group-append bg">
                            <span class="input-group-text bg-transparent"> <a href="" style=" text-decoration:none;"><i class="fas fa-eye-slash" aria-hidden="true"></i></a></span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group">
                            <button type="submit" class="btn btn-success elevation-5 px-3 btn-flat"><i class="fas fa-share-square"></i> SAVE</button>
                        </div>
                    </div>
                  </div>
              </div>
            </div>
   

     
       
            </form>

            
        
    

      
     
          
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

 

  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?> - <?php echo date('Y', strtotime('+1 year'));  ?> </strong>
    
    <div class="float-right d-none d-sm-inline-block">
    </div>
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

<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>

<script>
  $(document).ready(function(){
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


    $("#display_image").click(function(){
      $("#image").click();
    })



    function displayImge(input){
      if(input.files && input.files[0]){
        var reader = new FileReader();
        var image = $("#image").val().split('.').pop().toLowerCase();

        if(image != ''){
          if(jQuery.inArray(image,['gif','png','jpg','jpeg']) == -1){
            Swal.fire({
              title: '<strong class="text-danger">ERROR</strong>',
              type: 'error',
              html: '<b>Invalid Image File<b>',
              width: '400px',
              confirmButtonColor: '#6610f2',
            })
            $("#image").val('');
         
            return false;
          }
        }

        reader.onload = function(e){
          $("#display_image").attr('src',e.target.result);
          $("#display_image").hide();
          $("#display_image").fadeIn(650);
        }

        reader.readAsDataURL(input.files[0]);

      }
    }  

    $("#image").change(function(){
      displayImge(this);
    })



    $("#changeProfile").submit(function(e){
        e.preventDefault();

        Swal.fire({
              title: '<strong class="text-warning">ARE YOU SURE</strong>',
              html: "<b>You want edit  your profile?</b>",
              type: 'info',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, edit it!',
              allowOutsideClick: false,
              width: '400px',
            }).then((result) => {



              $.ajax({
                  url: 'saveProfile.php',
                  type: 'POST',
                  data: new FormData(this),
                  processData: false,
                  contentType: false,
                  cache: false,
                  success:function(data){

                    if(data == 'error'){

                      Swal.fire({
                        title: '<strong class="text-danger">ERROR</strong>',
                        type: 'error',
                        html: '<b>Username is Already Exist<b>',
                        width: '400px',
                        confirmButtonColor: '#6610f2',
                      })

                  }else if(data == 'error1'){
                      Swal.fire({
                        title: '<strong class="text-danger">ERROR</strong>',
                        type: 'error',
                        html: '<b>Old Password Not Match<b>',
                        width: '400px',
                        confirmButtonColor: '#6610f2',
                      })
                    }else if(data == 'error2'){

                      Swal.fire({
                        title: '<strong class="text-danger">ERROR</strong>',
                        type: 'error',
                        html: '<b>New Password and Confirm Password Not Match<b>',
                        width: '400px',
                        confirmButtonColor: '#6610f2',
                      })

                    }else{

                      Swal.fire({
                        title: '<strong class="text-success">SUCCESS</strong>',
                        type: 'success',
                        html: '<b>Updated Profile has Successfully <b>',
                        width: '400px',
                        showConfirmButton:false,
                        timer: 2000,
                      }).then(()=>{
                        $("#new_password").val('');
                        $("#new_confirm_password").val('');
                        $("#old_password").val('');
                      })

                  
                    }
                  }
                }).fail(barangayAjaxError)





            })

        


    })




  })
</script>

<script>
// Restricts input for each element in the set of matched elements to the given inputFilter.
(function($) {
  $.fn.inputFilter = function(inputFilter) {
    return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
      if (inputFilter(this.value)) {
        this.oldValue = this.value;
        this.oldSelectionStart = this.selectionStart;
        this.oldSelectionEnd = this.selectionEnd;
      } else if (this.hasOwnProperty("oldValue")) {
        this.value = this.oldValue;
        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
      } else {
        this.value = "";
      }
    });
  };
}(jQuery));

 
  $("#contact_number").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });


  $("#first_name, #middle_name, #last_name").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
 

</script>

</body>
</html>


              