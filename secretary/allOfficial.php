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
      border-color:#CCC;
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
      <img src="<?= barangay_h($sidebarLogo) ?>" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="<?= barangay_h($barangay) ?>" style="width: 70%;">
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
          <li class="nav-item menu-open">
            <a href="#" class="nav-link  bg-indigo">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>
              Barangay Official
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
             
              <li class="nav-item">
                <a href="allOfficial.php" class="nav-link active">
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

 

    <!-- Main content -->
    <section class="content mt-3">
      <div class="container-fluid">

    <div class="card">
        <!-- <div class="card-header">
            <div class="card-title">
              <button type="button" class="btn bg-black elevation-5 btn-flat px-3" data-toggle="modal" data-target="#newOfficialModal"><i class="fas fa-plus"></i> NEW OFFICIAL</button>
            </div>
        </div> -->
      <div class="card-body">
          <fieldset>
            <legend>NUMBER OF OFFICIAL <span id="total"></span></legend>
              <div class="table-responsive mt-2">
              <table class="table table-striped table-hover " id="officialTable" style="width: 100%;">
                  <thead class="bg-black text-uppercase">
                  <tr>
                    <th>Image</th>
                    <th>
                      <select name="position" id="position" class="form-control form-control-sm text-uppercase">
                      <option value="">All Position</option>
                        <?php 
                        
                        $sql_position = "SELECT position_id, position FROM position";
                        $stmt = $con->prepare($sql_position) or die ($con->error);
                        $stmt->execute();
                        $result_position = $stmt->get_result();
                        while($row_position = $result_position->fetch_assoc()){
                          echo ' <option value="'.$row_position['position_id'].'">'.$row_position['position'].'</option>';
                        }
                        
                        ?>
                       
                      
                      </select>
                    </th>
                    <th>Official Number</th>
                    <th>Name</th>
                    
                    <th>PWD</th>
                    <th>SINGLE PARENT</th>
                    <th>Voters</th>
                    <th>
                      Status
                    </th>
                    <th class="text-center">Action</th>
                  </tr>
                  </thead>

                </table>
              </div>
            
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



    
    <!-- Modal -->
    <!-- <div class="modal fade" id="newOfficialModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <div class="container-fluid">
              <div class="row">
                   <div class="col-sm-6">
                        <div class="row">
                          <div class="col-sm-12 text-center">
                            <div class="form-group form-group-sm">
                              <img src="../assets/dist/img/image.png" alt="image" class="img-cricle w-25">
                              <input type="file" name="add_image" id="add_image" class="form-control d-none">
                            </div>
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Position</label>
                                <select name="position" id="position" class="form-control text-uppercase"> 
                                    <option value="">SELECT POSITION</option>
                                  <?php 
                          
                                        $add_position = "SELECT position_id, position FROM position";
                                        $stmt_add_position = $con->prepare($add_position) or die ($con->error);
                                        $stmt_add_position->execute();
                                        $result_add_position = $stmt_add_position->get_result();
                                        while($row_add_position = $result_add_position->fetch_assoc()){
                                          echo ' <option value="'.$row_add_position['position_id'].'">'.$row_add_position['position'].'</option>';
                                        }
                                        
                                    ?>
                                </select>
                            </div> 
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group form-group-sm">
                              <label>Start Term</label>
                              <input type="date" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group form-group-sm">
                              <label>Start Term</label>
                              <input type="date" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Date of Birth</label>
                              <input type="date" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Place of Birth</label>
                              <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Civil Status</label>
                                <select name="" id="" class="form-control">
                                  <option value="Single">Single</option>
                                  <option value="Married">Married</option>
                                </select>
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Religion</label>
                               <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>House Number</label>
                               <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                        </div>
                    </div> 
                   <div class="col-sm-6">
                        <div class="row " style="margin-top: 21.7px;">
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>First Name</label>
                              <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Middle Name</label>
                              <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Last Name</label>
                              <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Suffix</label>
                              <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Gender</label>
                                <select name="gender" id="gender" class="form-control">
                                  <option value="Male">Male</option>
                                  <option value="Female">Female</option>
                                </select>
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Nationality</label>
                               <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                          <div class="col-sm-12">
                            <div class="form-group form-group-sm">
                              <label>Municipality</label>
                               <input type="text" name="" id="" class="form-control">
                            </div> 
                          </div>
                      </div>                     
                   </div>     
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn bg-black btn-flat elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
            <button type="button" class="btn btn-primary">Save</button>
          </div>
        </div>
      </div>
    </div>
     -->
  



<div id="imagemodal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
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
<script src="../assets/plugins/jquery-validation/jquery-validate.bootstrap-tooltip.min.js"></script>
<script src="../assets/plugins/inputmask/min/jquery.inputmask.bundle.min.js"></script>
<script src="../assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>



<script>
  $(document).ready(function(){

    officialTable()

    editStatus();

 
  

    function editStatus(){
      $(document).on('click','.editStatus',function(){
      var official_id = $(this).attr('id');
 

        $.ajax({
            url: 'editStatusOfficial.php',
            type: 'POST',
            cache: false,
            data: {
              official_id:official_id,
            },
            success:function(data){

            }
        }).fail(barangayAjaxError)

      })
    }


    function officialTable(){

      var position = $("#position").val();
      var official_table = $("#officialTable").DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        responsive: true,
        scrollY: '665',
        ajax:{
          url: 'allOfficialTable.php',
          type: 'POST',
          data:{
            position:position
          },
        },
        order:[],
        columnDefs:[
          {
            targets: 8,
            orderable: false,
            className: 'text-center',
          },
          {
            targets: 0,
            orderable: false,
           
          },
          {
            targets: 1,
            orderable: false,
            className: 'text-center text-lg text-uppercase',
          },
          {
            targets: 5,
            orderable: false,
            className: 'text-center',
           
          },
          {
            targets: 6,
            orderable: false,
            className: 'text-center',
           
          },
          {
            targets: 7,
            orderable: false,
            className: 'text-center',
           
          },
          
        ],
        dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'>f>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'d-flex flex-sm-row-reverse flex-column border-top '<'px-2 'p><'px-2'i> <'px-2'l> >",
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
              search: 'SEARCH:',
            },
        drawCallback:function(data)  {
              $('#total').text(data.json.total);
              $('.dataTables_paginate').addClass("mt-2 mt-md-2 pt-1");
              $('.dataTables_paginate ul.pagination').addClass("pagination-md");   
                              
            },
 
      })

    }


    $(document).on('change', '#position',function(){
      var position = $(this).val();
   
      $('#officialTable').DataTable().destroy();
      if(position != ''){
        
        officialTable()
      }else{
       
        officialTable();
      }

    })

    $(document).on('click', '.pop',function() {
			$('.imagepreview').attr('src', $(this).find('img').attr('src'));
			$('#imagemodal').modal('show');   
		});

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


  
  $("#searching").inputFilter(function(value) {
  return /^[0-9a-z]*$/i.test(value); 
  });

</script>



</body>
</html>
