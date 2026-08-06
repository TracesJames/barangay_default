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
    $first_name_user = $row_user['first_name'];
    $last_name_user = $row_user['last_name'];
    $user_type = $row_user['user_type'];
    $user_image = $row_user['image'];


    $sql_resident = "SELECT residence_information.*, residence_status.* FROM residence_information
    INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id
     WHERE residence_information.residence_id = '$user_id'";
    $query_resident = $con->query($sql_resident) or die ($con->error);
    $row_resident = $query_resident->fetch_assoc();

    $sql_user_resident = "SELECT username, password FROM users  WHERE id = '$user_id'";
   
    $query_user_resident = $con->query($sql_user_resident) or die ($con->error);
    $row_suer_resident = $query_user_resident->fetch_assoc();


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


<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed layout-footer-fixed barangay-portal">
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
    <a href="#" class="dropdown-item">
      <!-- Message Start -->
      <div class="media">
        <?php 
          if($row_resident['image_path'] != '' || $row_resident['image_path'] != null || !empty($row_resident['image_path'])){
            echo '<img src="'.$row_resident['image_path'].'" class="img-size-50 mr-3 img-circle" alt="User Image" style="width:50px;height:50px;object-fit:cover;">';
          }else{
            echo '<img src="../assets/dist/img/blank_image.png" class="img-size-50 mr-3 img-circle" alt="User Image" style="width:50px;height:50px;object-fit:cover;">';
          }
        ?>
      
        <div class="media-body">
          <h3 class="dropdown-item-title py-3">
            <?= ucfirst($row_resident['first_name']) .' '. ucfirst($row_resident['last_name']) ?>
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
          echo ' <img src="../assets/logo/black.png" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }

      ?>
      <span class="brand-text font-weight-light"></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
    

 
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
            <a href="personalInformation.php" class="nav-link  ">
              <i class="nav-icon fas fa-address-book"></i>
              <p>
                Personal Information
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="myRecord.php" class="nav-link">
              <i class="nav-icon fas fa-server"></i>            
              <p>
                Blotter Record
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="changePassword.php" class="nav-link bg-indigo">
              <i class="nav-icon fas fa-lock"></i>
              <p>
                Change Password
              </p>
            </a>
          </li>
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
      <div class="container-fluid">
          <form id="editUserInfoForm" method="post" autocomplete="off">

              <input type="hidden" name="user_id" id="user_id" value="<?= $_SESSION['user_id'] ?>">
                <div class="card ">
                
                  <div class="card-body">


                  <div class="col-sm-12">
                        <div class="form-group">
                          <label>Username</label>
                          <input type="text" name="username" id="username" class="form-control" value="<?= $row_suer_resident['username'] ?>">
                        </div>
                      </div>
   
                      <div class="col-sm-12">
                          <label for="">Old Password</label>
                          <div class="input-group mb-3" id="current_password_show">
                            <div class="input-group-prepend">
                              <div class="input-group-text">
                              <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                              </div>
                            </div>
                            <input type="password" name="current_password" id="current_password" class="form-control">
                          </div>
                      </div>

                      <div class="col-sm-12">
                        <label>New Password</label>
                          <div class="input-group mb-3" id="new_password_show">
                            <div class="input-group-prepend">
                              <div class="input-group-text">
                              <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                              </div>
                            </div>
                            <input type="password" name="new_password" id="new_password" class="form-control">
                          </div>
                      </div>

                      <div class="col-sm-12">
                          <label>Re-type New Password</label>
                            <div class="input-group mb-3" id="retype_password_show">
                              <div class="input-group-prepend">
                                <div class="input-group-text">
                                <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                </div>
                              </div>
                              <input type="password" name="retype_password" id="retype_password" class="form-control">
                            </div>
                      </div>
        
                      <div class="col-sm-12">
                        <button type="submit" class="btn btn-success elevation-5 px-3"><i class="fas fa-save"></i> SAVE</button>
                      </div>



                  </div>
                </div>

                <!-- <div class="card">
                    <div class="card-header">
                      <div class="card-title">
                        <h3>Activity Log</h3>
                      </div>
                    </div>
                  <div class="card-body">
                    
                    <table class="table" id="tableActivityLogUser">
                      <thead>
                        <tr>
                          <th>Message</th>
                          <th>Date</th>
                        </tr>
                      </thead>
                    </table>
                  </div>
                </div> -->


          </form>



      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

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
<script src="../assets/plugins/popper/umd/popper.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../assets/plugins/jszip/jszip.min.js"></script>
<script src="../assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="../assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<script src="../assets/plugins/moment/moment.min.js"></script>
<script src="../assets/plugins/chart.js/Chart.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../assets/plugins/jquery-validation/additional-methods.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery-validate.bootstrap-tooltip.min.js"></script>


<script>
  $(document).ready(function(){
   

   

    $(function () {
        $.validator.setDefaults({
          submitHandler: function (form) {
            
                  $.ajax({
                    url: 'editUserInfo.php',
                    type: 'POST',
                    data: $(form).serialize(),
                    cache: false,
                    success:function(data){

                      if(data == 'errorPassword'){
                          Swal.fire({
                            title: '<strong class="text-danger">ERROR</strong>',
                            type: 'error',
                            html: '<b>Old Password is Wrong<b>',
                            width: '400px',
                            confirmButtonColor: '#6610f2',
                          })
                      }else if(data == 'errorUsername'){

                        Swal.fire({
                            title: '<strong class="text-danger">ERROR</strong>',
                            type: 'error',
                            html: '<b>Username is Already Taken<b>',
                            width: '400px',
                            confirmButtonColor: '#6610f2',
                          })

                      }else if (data == 'errorNot'){
                        Swal.fire({
                            title: '<strong class="text-danger">ERROR</strong>',
                            type: 'error',
                            html: '<b>Password not Match<b>',
                            width: '400px',
                            confirmButtonColor: '#6610f2',
                          })
                       
                      }else{
                        Swal.fire({
                          title: '<strong class="text-success">SUCCESS</strong>',
                          type: 'success',
                          html: '<b>Updated Residence has Successfully<b>',
                          width: '400px',
                          confirmButtonColor: '#6610f2',
                          allowOutsideClick: false,
                          showConfirmButton: false,
                          timer: 2000,
                        }).then(()=>{
                          window.location.reload();
                        })
                      }

                      
                    }
                }).fail(barangayAjaxError)
             
            
          }
        });
      $('#editUserInfoForm').validate({
        rules: {
          username: {
            required: true,
            minlength: 6
          },
          new_password: {
            required: true,
            minlength: 6
          },
          retype_password: {
            required: true,
            minlength: 6
          },
          current_password: {
            required: true,
           
          },
          
        },
        messages: {
          username: {
            required: "<span class='text-danger text-bold'>Username is Required</span>",
            minlength: "<span class='text-danger'>Username must be at least 6 characters long</span>"
          },
          new_password: {
            required: "<span class='text-danger text-bold'>New Password is Required</span>",
            minlength: "<span class='text-danger'>New Password must be at least 6 characters long</span>"
          },
          retype_password: {
            required: "<span class='text-danger text-bold'>Confirm Password is Required</span>",
            minlength: "<span class='text-danger'>Confirm Password must be at least 6 characters long</span>"
          },
          current_password: {
            required: "<span class='text-danger text-bold'>Current Password is Required</span>",
            
          },
          
        },
        tooltip_options: {
          '_all_': {
            placement: 'bottom',
            html:true,
          },
          
        },
      });
    })





    function tableActivityLogUser(){
      var user_id = $("#user_id").val();
      var tableActivityLogUser = $("#tableActivityLogUser").DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        autoWidth: false,
        order:[],
        ajax:{
          url: 'tableActivityLog.php',
          type: 'POST',
          data:{
            user_id:user_id,
          }
        }

      })
    }
  })
</script>

<script>
  $(document).ready(function() {
    $("#current_password_show a").on('click', function(event) {
        event.preventDefault();
        if($('#current_password_show input').attr("type") == "text"){
            $('#current_password_show input').attr('type', 'password');
            $('#current_password_show i').addClass( "fa-eye-slash" );
            $('#current_password_show i').removeClass( "fa-eye" );
        }else if($('#current_password_show input').attr("type") == "password"){
            $('#current_password_show input').attr('type', 'text');
            $('#current_password_show i').removeClass( "fa-eye-slash" );
            $('#current_password_show i').addClass( "fa-eye" );
        }
    });
    
    $("#new_password_show a").on('click', function(event) {
        event.preventDefault();
        if($('#new_password_show input').attr("type") == "text"){
            $('#new_password_show input').attr('type', 'password');
            $('#new_password_show i').addClass( "fa-eye-slash" );
            $('#new_password_show i').removeClass( "fa-eye" );
        }else if($('#new_password_show input').attr("type") == "password"){
            $('#new_password_show input').attr('type', 'text');
            $('#new_password_show i').removeClass( "fa-eye-slash" );
            $('#new_password_show i').addClass( "fa-eye" );
        }
    });
    $("#retype_password_show a").on('click', function(event) {
        event.preventDefault();
        if($('#retype_password_show input').attr("type") == "text"){
            $('#retype_password_show input').attr('type', 'password');
            $('#retype_password_show i').addClass( "fa-eye-slash" );
            $('#retype_password_show i').removeClass( "fa-eye" );
        }else if($('#retype_password_show input').attr("type") == "password"){
            $('#retype_password_show input').attr('type', 'text');
            $('#retype_password_show i').removeClass( "fa-eye-slash" );
            $('#retype_password_show i').addClass( "fa-eye" );
        }
    });
});
</script>

</body>
</html>
