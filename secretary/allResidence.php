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
    $first_name_user = $row_user['first_name'];
    $last_name_user = $row_user['last_name'];
    $user_type = $row_user['user_type'];
    $user_image = $row_user['image'];
  
  
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
    .dataTables_wrapper .dataTables_paginate .page-link {
      
        border: none;
    }
    .dataTables_wrapper .dataTables_paginate .page-item .page-link{
        color: #fff ;
        border-color: transparent;
        
        
      }
   
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link{
        color: #fff ;
        border: transparent;
        background: none;
        font-weight: bold;
        background-color: #000;
      }
    .page-link:focus{
   
      outline:0;
      -webkit-box-shadow:none;
      box-shadow:none;
   
    }



    .dataTables_length select{
      border: 1px solid #fff;
      border-top: none;
      border-left: none;
      border-right: none;
      cursor: pointer;
      color: #fff;
 
    }
    .dataTables_length span{
      color: #fff;
      font-weight: 500; 
    }

    .last:after{
      display:none;
      width: 70px;
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px 0;
      position: absolute;
      font-size: 10px;
      z-index: 1;
      margin-left: -20px;
    }
      .last:hover:after{
          display: block;
      }
      .last:after{
          content: "Last Page";
      } 

      .first:after{
        display:none;
        width: 70px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;
        position: absolute;
        font-size: 10px;
        z-index: 1;
        margin-left: -20px;
    }
      .first:hover:after{
          display: block;
      }
      .first:after{
          content: "First Page";
      } 

      .last:after{
          content: "Last Page";
      } 

      .next:after{
        display:none;
        width: 70px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;
        position: absolute;
        font-size: 10px;
        z-index: 1;
        margin-left: -20px;
    }
      .next:hover:after{
          display: block;
      }
      .next:after{
          content: "Next Page";
      } 

      .previous:after{
        display:none;
        width: 80px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 5px;
        position: absolute;
        font-size: 10px;
        z-index: 1;
        margin-left: -20px;
    }
      .previous:hover:after{
          display: block;
      }
      .previous:after{
          content: "Previous Page";
      } 
      .dataTables_info{
        font-size: 13px;
        margin-top: 8px;
        font-weight: 500;
        color: #fff;
      }
      .dataTables_scrollHeadInner, .table{ 
        table-layout: auto;
       width: 100% !important; 
      }

      fieldset {
        border: 3px solid black !important;
        padding: 0 1.4em 1.4em 1.4em !important;
        margin: 0 0 1.5em 0 !important;
        -webkit-box-shadow:  0px 0px 0px 0px #000;
                box-shadow:  0px 0px 0px 0px #000;
      }
    legend {
      font-size: 1.2em !important;
      font-weight: bold !important;
      color: #fff;
      text-align: left !important;
      width:auto;
      padding:0 10px;
      border-bottom:none;
    }
    .select2-container--default .select2-selection--single{
      background-color: transparent;
      height: 38px;
      
      
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered{
      color: #fff;
      
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 75px;
      height: 28px;
    } 

.switch input {
  display:none;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ca2222;
  -webkit-transition: .4s;
  transition: .4s;
  
}

.slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 4px;
  bottom: 4px;
  background-color: #000;
  -webkit-transition: .4s;
  transition: .4s;

}

input:checked + .slider {
  background-color: #2ab934;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(46px);
  -ms-transform: translateX(46px);
  transform: translateX(46px);
}

/*------ ADDED CSS ---------*/
.on
{
  display: none;
  
}
.off{
  color: white;
  position: absolute;
  transform: translate(-50%,-50%);
  top: 50%;
  left: 62%;
  font-size: 8px;
  font-family: Verdana, sans-serif;
}

.on{

  color: white;
  position: absolute;
  transform: translate(-50%,-50%);
  top: 50%;
  left: 40%;
  font-size: 8px;
  font-family: Verdana, sans-serif;
}

  input:checked+ .slider .on{
  display: block;
  }

input:checked + .slider .off{

  display: none;
}

.slider.round {
  border-radius: 34px;
}
.slider.round:before {
  border-radius: 50%;
}
    
#allResidenceTable_filter{
      display: none;
    }
    .scrollbar::-webkit-scrollbar
{
    width: 6px;
    background-color: #000000;
}
 
.scrollbar::-webkit-scrollbar-thumb
{
   
  --webkit-box-shadow: inset 0 0 6px #6c757d; 
    background-color: #6c757d;
}
  </style>
 
 
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini barangay-portal">
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
          <a href="#" class="d-block text-bold">OFFICIAL</a>
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
                <a href="allOfficial.php" class="nav-link ">
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
          <li class="nav-item menu-open">
            <a href="#" class="nav-link bg-indigo ">
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
                <a href="importResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Import from Excel</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="allResidence.php" class="nav-link active">
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
   

    <!-- Main content -->
    <section class="content mt-3">
      <div class="container-fluid">

    <div class="card">
      <div class="card-body">
          <fieldset>
            <legend>NUMBER OF RESIDENCE <span id="total"></span></legend>
              <div class="mb-3">
                <a href="importResidence.php" class="btn btn-success elevation-2">
                  <i class="fas fa-file-excel"></i> Import from Excel
                </a>
                <a href="newResidence.php" class="btn btn-primary elevation-2">
                  <i class="fas fa-user-plus"></i> New Residence
                </a>
              </div>
              <div class="row">
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">FIRST NAME</span>
                    </div>
                        <input type="search" name="first_name" id="first_name" class="form-control"> 
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">MIDDLE NAME</span>
                    </div>
                        <input type="search" name="middle_name" id="middle_name" class="form-control"> 
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">LAST NAME</span>
                    </div>
                        <input type="search" name="last_name" id="last_name" class="form-control"> 
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">VOTERS</span>
                    </div>
                      <select name="voters" id="voters" class="form-control">
                        <option value="">--SELECT VOTERS--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">AGE</span>
                    </div>
                        <input type="number" name="age" id="age" class="form-control"> 
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">STATUS</span>
                    </div>
                      <select name="status" id="status" class="form-control">
                        <option value="">--SELECT STATUS--</option>
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="INACTIVE">INACTIVE</option>
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">PWD</span>
                    </div>
                      <select name="pwd" id="pwd" class="form-control">
                        <option value="">--SELECT PWD--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">SINGLE PARENT</span>
                    </div>
                      <select name="single_parent" id="single_parent" class="form-control">
                        <option value="">--SELECT PARENT STATUS--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">SENIOR</span>
                    </div>
                      <select name="senior" id="senior" class="form-control">
                        <option value="">--SELECT SENIOR--</option>
                        <option value="YES">YES</option>
                        <option value="NO">NO</option>
                      </select>
                  </div>
                </div>
                <?php require_once '../includes/partials/purok_filter_field.php'; ?>
                <div class="col-sm-4">
                  <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-indigo">RESIDENT NUMBER</span>
                    </div>
                        <input type="text" name="resident_id" id="resident_id" class="form-control"> 
                      </select>
                  </div>
                </div>
                <div class="col-sm-4 text-center mb-4">
                  <button type="button" class="btn btn-warning px-3 elevation-3 text-white" id="search"><i class="fas fa-search"></i> SEARCH</button>
                  <button type="button" class="btn btn-danger px-3 elevation-3" id="reset"><i class="fas fa-undo"></i> RESET</button>
                </div>
                
              </div>
                
                
              
            <table class="table table-striped table-hover " id="allResidenceTable">
              <thead class="bg-black text-uppercase">
                <tr>
                  <th>Image</th>
                  <th>Resident Number</th>
                  <th>Name</th>
                  <th>Age</th>
                  <th>Pwd</th>
                  <th>Single Parent</th>
                  <th>Voters</th>
                  <th>Status</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
            </table>
          </fieldset>
        </div>
      </div>   


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

<div id="imagemodal" class="modal " tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="background-color: #000">
      <div class="modal-body">
      <button type="button" class="close" data-dismiss="modal" style="color: #fff;"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      <img src="" class="imagepreview img-circle" style="width: 100%;" >
      </div>
    </div>
  </div>
</div>



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
<script src="../assets/js/residence-family.js"></script>
<script src="../assets/plugins/jquery-validation/jquery-validate.bootstrap-tooltip.min.js"></script>
<script src="../assets/plugins/inputmask/min/jquery.inputmask.bundle.min.js"></script>
<script src="../assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<div id="displayResidence"></div>
<script>
  $(document).ready(function(){

    filterData();
    editStatus();
    viewResidence();
    deleteResidence();
   


    function deleteResidence(){
      $(document).on('click','.deleteResidence',function(){
        var residence_id = $(this).attr('id');
        Swal.fire({
            title: '<strong class="text-danger">Are you sure?</strong>',
            html: "You want archive this Resident?",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            allowOutsideClick: false,
            confirmButtonText: 'Yes, archive it!',
            width: '400px',
          }).then((result) => {
            if (result.value) {
              $.ajax({
                url: 'deleteResidence.php',
                type: 'POST',
                data: {
                  residence_id:residence_id,
                },
                cache: false,
                success:function(data){
                  Swal.fire({
                    title: '<strong class="text-success">Success</strong>',
                    type: 'success',
                    html: '<b>Archive Resident has Successfully<b>',
                    width: '400px',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000
                  }).then(()=>{
                    $("#allResidenceTable").DataTable().ajax.reload();
                  })
                }
              }).fail(barangayAjaxError)
            }
          })

      })
    }


    function editStatus(){
      $(document).on('click','.editStatus',function(){
      var status_residence = $(this).attr('id');
      var data_status = $(this).attr('data-status');

        $.ajax({
            url: 'editStatusResidence.php',
            type: 'POST',
            cache: false,
            data: {
              status_residence:status_residence,data_status:data_status,
            },
            success:function(data){

            }
        }).fail(barangayAjaxError)

      })
    }


    function viewResidence(){
      $(document).on('click','.viewResidence',function(){
      var residence_id = $(this).attr('id');

        $("#displayResidence").html('');
        
        $.ajax({
          url: 'viewResidenceModal.php',
          type: 'POST',
          dataType: 'html',
          cache: false,
          data: {
            residence_id:residence_id
          },
          success:function(data){
            $("#displayResidence").html(data);
            $("#viewResidenceModal").modal('show');
            if (typeof initResidenceFamilyForm === 'function') {
              initResidenceFamilyForm();
            }
               
               
          }
        }).fail(barangayAjaxError)

      })
    }
    




    function filterData(){
        var status = $("#status").val();
        var voters = $("#voters").val();
        var age = $("#age").val();
        var pwd = $("#pwd").val();
        var senior = $("#senior").val();
        var first_name = $("#first_name").val();
        var middle_name = $("#middle_name").val();
        var last_name = $("#last_name").val();
        var single_parent = $("#single_parent").val();
        var resident_id = $("#resident_id").val();
        var purok = $("#purok").val();
        var allResidenceTable = $("#allResidenceTable").DataTable({
          processing: true,
          serverSide: true,
          responsive: true,
          searching: false,
          scrollY: '665',
          ajax:{
            url: 'allResidenceTable.php',
            type: 'POST',
            data:{
              voters:voters,
              status:status,
              age:age,
              pwd:pwd,
              senior:senior,
              first_name:first_name,
              middle_name:middle_name,
              last_name:last_name,
              single_parent:single_parent,
              resident_id:resident_id,
              purok:purok
            },
          },
          dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'d-flex flex-sm-row-reverse flex-column border-top '<'px-2 'p><'px-2'i> <'px-2'l> >",
          order:[],
          columnDefs:[
            {
              orderable: false,
              targets: "_all",
            },
            {
              targets: 8,
              className: "text-center",
            },
            {
              targets: 7,
              className: "text-center",
            },
            {
              targets: 6,
              className: "text-center",
            },
            {
              targets: 5,
              className: "text-center",
            },
          ],
          pagingType: "full_numbers",
            language: {
              paginate: {
                next: '<i class="fas fa-angle-right text-white"></i>',
                previous: '<i class="fas fa-angle-left text-white"></i>', 
                first: '<i class="fa fa-angle-double-left text-white"></i>',
                last: '<i class="fa fa-angle-double-right text-white"  ></i>'        
              }, 
              lengthMenu: '<div class="mt-3 pr-2"> <span class="text-sm mb-3 pr-2">Rows per page:</span> <select>'+
                          '<option value="10">10</option>'+
                          '<option value="20">20</option>'+
                          '<option value="30">30</option>'+
                          '<option value="40">40</option>'+
                          '<option value="50">50</option>'+
                          '<option value="-1">All</option>'+
                          '</select></div>',
              info:  " _START_ - _END_ of _TOTAL_ ",
            },
            drawCallback:function(data)  {
              $('#total').text(data.json.total);
              $('.dataTables_paginate').addClass("mt-2 mt-md-2 pt-1");
              $('.dataTables_paginate ul.pagination').addClass("pagination-md");
              $('body').find('.dataTables_scrollBody').addClass("scrollbar");                         
            }
            
      })
       
    }

    $(document).on('click', '#search',function(){
      var status = $("#status").val();
      var voters = $("#voters").val();
      var age = $("#age").val();
      var pwd = $("#pwd").val();
      var senior = $("#senior").val();
      var first_name = $("#first_name").val();
      var middle_name = $("#middle_name").val();
      var last_name = $("#last_name").val();
      var resident_id = $("#resident_id").val();
      var single_parent = $("#single_parent").val();
      var purok = $("#purok").val();
      if(status != '' || voters != '' || age != '' || first_name != '' ||  middle_name != '' || last_name != '' || pwd != '' || senior != '' || resident_id != '' || single_parent != '' || purok != ''){
        $("#allResidenceTable").DataTable().destroy();
        filterData();
      }
    })
    $(document).on('click', '#reset',function(){
      var status = $("#status").val();
      var voters = $("#voters").val();
      var age = $("#age").val();
      var pwd = $("#pwd").val();
      var senior = $("#senior").val();
      var first_name = $("#first_name").val()
      var middle_name = $("#middle_name").val()
      var last_name = $("#last_name").val()
      var resident_id = $("#resident_id").val();
      var single_parent = $("#single_parent").val();
      var purok = $("#purok").val();
      if(status != '' || voters != '' || age != '' || first_name != '' || middle_name != '' || last_name != '' || pwd != '' || senior != '' || resident_id != '' || single_parent != '' || purok != ''){
        $("#status").val('');
        $("#voters").val('');
        $("#age").val('');
        $("#pwd").val('');
        $("#senior").val('');
        $("#first_name").val('');
        $("#middle_name").val('');
        $("#last_name").val('');
        $("#resident_id").val('');
        $("#single_parent").val('');
        $("#purok").val('');
        $("#allResidenceTable").DataTable().destroy();
        filterData();
      }else{
        $("#allResidenceTable").DataTable().destroy();
        filterData();
      }
    })
    
    $(document).on('click', '.pop',function() {
			$('.imagepreview').attr('src', $(this).find('img').attr('src'));
			$('#imagemodal').modal('show');   
		});

    $("#age").on("input", function() {
      if (/^0/.test(this.value)) {
        this.value = this.value.replace(/^0/, "")
      }
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




  $("#first_name, #middle_name, #last_name").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  $("#resident_id").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });
  
 

</script>


</body>
</html>
