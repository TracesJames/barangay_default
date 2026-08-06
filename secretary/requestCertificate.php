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

  .select2-container--default .select2-selection--single{
    background-color: transparent;
    height: 38px;
    
    
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{
    color: #fff;
    
  }

 
#certificateTable{
  width: 100% !important;
}

#certificateTable_filter{
      display: none;
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
            <a href="requestCertificate.php" class="nav-link bg-indigo">
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



            <div class="card card-indigo card-tabs">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="certificate-tabs" data-toggle="pill" href="#certificate-tabs" role="tab" aria-controls="certificate-tabs" aria-selected="true">Certificate <span class="badge badge-success bg-lime" id="total"></span></a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                  <div class="tab-pane fade show active" id="certificate-tabs" role="tabpanel" aria-labelledby="certificate-tabs">
                      <div class="row align-items-center mb-2">
                        <div class="col-sm-6">
                          <div class="input-group mb-3 mb-sm-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text bg-indigo">SEARCH</span>
                            </div>
                            <input type="text" class="form-control" id="searching" autocomplete="off">
                            <div class="input-group-append">
                              <span class="input-group-text bg-red" id="reset" type="button"><i class="fas fa-undo"></i> RESET</span>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-6 text-sm-right">
                          <button type="button" class="btn bg-black elevation-5 px-3 btn-flat" data-toggle="modal" data-target="#newCertificateRequestModal">
                            <i class="fas fa-plus"></i> New Request
                          </button>
                        </div>
                      </div>
                      <div class="table-responsive">
                      <table class="table table-hover table-striped text-sm" id="certificateTable" >
                        <thead>
                          <tr>
                            <th>Resident Number</th>
                            <th>Name</th>
              
                            <th>Purpose</th>
                            <th>

                            <select name="date_request" id="date_request" class="form-control form-control-sm">
                                <option value="">Date Request</option>
                                    <?php 
                                    $blank_request = '';
                                    $sql_date_request = "SELECT date_request FROM certificate_request WHERE  date_request != ? GROUP BY date_request";
                                    $stmt_date_request = $con->prepare($sql_date_request) or die ($con->error);
                                    $stmt_date_request->bind_param('s',$blank_request);
                                    $stmt_date_request->execute();
                                    $result_date_request = $stmt_date_request->get_result();
                                    while($row_date_request = $result_date_request->fetch_assoc()){
                                        echo '<option value="'.$row_date_request['date_request'].'">'.date("m/d/Y", strtotime($row_date_request['date_request'])).'</option>';
                                    }
                                    
                                    ?>
                            </select>

                            </th>
                            <th>
                                      <select name="date_issued" id="date_issued" class="form-control form-control-sm">
                                        <option value="">Date Issued</option>
                                              <?php 
                                              $blank_issued = '';
                                              $sql_date_issued = "SELECT date_issued FROM certificate_request WHERE  date_issued != ? GROUP BY date_issued";
                                              $stmt_date_issued = $con->prepare($sql_date_issued) or die ($con->error);
                                              $stmt_date_issued->bind_param('s',$blank_issued);
                                              $stmt_date_issued->execute();
                                              $result_date_issued = $stmt_date_issued->get_result();
                                              while($row_date_issued = $result_date_issued->fetch_assoc()){
                                                  echo '<option value="'.$row_date_issued['date_issued'].'">'.date("m/d/Y", strtotime($row_date_issued['date_issued'])).'</option>';
                                              }
                                              
                                              ?>
                                      </select>
                            </th>
                            <th>
                                    <select name="date_expired" id="date_expired" class="form-control form-control-sm">
                                      <option value="">Date Expired</option>
                                          <?php 
                                          $blank_expired = '';
                                          $sql_date_expired = "SELECT date_expired FROM certificate_request WHERE date_expired != ? GROUP BY date_expired";
                                          $stmt_date_expired = $con->prepare($sql_date_expired) or die ($con->error);
                                          $stmt_date_expired->bind_param('s',$blank_expired);
                                          $stmt_date_expired->execute();
                                          $result_date_expired = $stmt_date_expired->get_result();
                                          while($row_date_expired = $result_date_expired->fetch_assoc()){
                                              echo '<option value="'.$row_date_expired['date_expired'].'">'.date("m/d/Y", strtotime($row_date_expired['date_expired'])).'</option>';
                                          }
                                          
                                          ?>
                                    </select>
                            </th>
                            <th>
                                  <select name="status" id="status" class="form-control form-control-sm">
                                    <option value="">Status</option>
                                          <?php 
                                        
                                          $sql_status = "SELECT status FROM certificate_request GROUP BY status";
                                          $stmt_status = $con->prepare($sql_status) or die ($con->error);
                                          $stmt_status->execute();
                                          $result_status = $stmt_status->get_result();
                                          while($row_status = $result_status->fetch_assoc()){
                                              echo '<option value="'.$row_status['status'].'">'.$row_status['status'].'</option>';
                                          }
                                          
                                          ?>
                                </select>
                            </th>
                            <th class="text-center">Tools</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                      </div>
                  </div>
                  
                </div>
              </div>
              <!-- /.card -->
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
      <?php  
      
      $date_today = date("Y-m-d");  

      if('2021-14-11' > $date_today){
        echo 'greater than';
      }else{
        echo 'less than';
      }
      
      
      ?>
    </div>
  </footer>
</div>
<!-- ./wrapper -->

<div class="modal fade" id="newCertificateRequestModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="staffCertificateRequestForm" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Create Certificate Request</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Resident</label>
            <select name="residence_id" id="staff_residence_id" class="form-control" style="width: 100%;" required></select>
            <small class="text-muted">Type at least 2 characters to search by name or resident number.</small>
          </div>
          <div class="form-group">
            <label>Purpose</label>
            <input type="text" name="purpose" id="staff_purpose" class="form-control text-uppercase" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn bg-black btn-flat elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
          <button type="submit" class="btn btn-success btn-flat elevation-5 px-3"><i class="fas fa-save"></i> SUBMIT</button>
        </div>
      </form>
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

<div id="show_status"></div>

<script>
  $(document).ready(function(){

    $('#staff_residence_id').select2({
      theme: 'bootstrap4',
      placeholder: 'Select resident',
      allowClear: true,
      minimumInputLength: 2,
      dropdownParent: $('#newCertificateRequestModal'),
      ajax: {
        url: '../admin/certificateResidentSearch.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term || '',
            page: params.page || 1
          };
        },
        processResults: function (data) {
          return data;
        }
      }
    });

    $('#staffCertificateRequestForm').on('submit', function (e) {
      e.preventDefault();
      if (!$('#staff_residence_id').val()) {
        Swal.fire({
          title: '<strong class="text-danger">ERROR</strong>',
          type: 'error',
          html: '<b>Please select a resident.<b>',
          width: '400px',
          confirmButtonColor: '#6610f2',
        });
        return;
      }

      $.ajax({
        url: '../admin/createCertificateRequest.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function (response) {
          var resultText = (response || '').toString().trim();
          if (resultText !== 'success') {
            var errorMessage = 'Unable to create the certificate request.';
            if (resultText === 'errorPurpose') {
              errorMessage = 'Purpose is required.';
            } else if (resultText === 'errorResident') {
              errorMessage = 'Please select a resident.';
            } else if (resultText === 'errorNotRegistered') {
              errorMessage = 'The selected resident is not registered.';
            }

            Swal.fire({
              title: '<strong class="text-danger">ERROR</strong>',
              type: 'error',
              html: '<b>' + errorMessage + '<b>',
              width: '400px',
              confirmButtonColor: '#6610f2',
            });
            return;
          }

          Swal.fire({
            title: '<strong class="text-success">Success</strong>',
            type: 'success',
            html: '<b>Certificate request created successfully.<b>',
            width: '400px',
            showConfirmButton: false,
            allowOutsideClick: false,
            timer: 2000
          }).then(function () {
            $('#staffCertificateRequestForm')[0].reset();
            $('#staff_residence_id').val(null).trigger('change');
            $('#newCertificateRequestModal').modal('hide');
            if ($.fn.DataTable.isDataTable('#certificateTable')) {
              $('#certificateTable').DataTable().ajax.reload();
            } else {
              certificateTable();
            }
          });
        }
      }).fail(barangayAjaxError);
    });

    $('#newCertificateRequestModal').on('hidden.bs.modal', function () {
      $('#staffCertificateRequestForm')[0].reset();
      $('#staff_residence_id').val(null).trigger('change');
    });

    certificateTable();

    function certificateTable(){
      var date_request = $("#date_request").val();
      var date_issued = $("#date_issued").val();
      var date_expired = $("#date_expired").val();
      var status = $("#status").val();
      var certificateTable = $("#certificateTable").DataTable({
        processing: true,
        serverSide: true,
        order:[],
        autoWidth: false,
        ordering: false,
        dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'f>>" +
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
            },
        ajax:{
          url: 'certificateTable.php',
          type: 'POST',
          data:{
            date_request:date_request,
            date_issued:date_issued,
            date_expired:date_expired,
            status:status
          }
        },
        drawCallback: function (data) {
            $('#total').text(data.json.total);
            $('[data-toggle="tooltip"]').tooltip();
            $('.dataTables_paginate').addClass("mt-2 mt-md-2 pt-1");
            $('.dataTables_paginate ul.pagination').addClass("pagination-md");   
               
          },
          
      })
      $('#searching').keyup(function(){
        certificateTable.search($(this).val()).draw() ;
      })
   
    }

    $(document).on('change',"#date_request, #date_issued, #date_expired, #status",function(){
          $("#certificateTable").DataTable().destroy();
          certificateTable();
          $("#searching").keyup();
    })
    $(document).on('click','#reset',function(){

        if($("#date_request").val() != '' ||  $("#date_issued").val() !=  '' || $("#date_expired").val() != '' ||  $("#status").val() != '' ||  $("#searching").val() != ''){
            $("#date_request").val('');
            $("#date_issued").val('');
            $("#date_expired").val('');
            $("#status").val('');
            $("#searching").val('');
            $("#certificateTable").DataTable().destroy();
                certificateTable();
            $("#searching").keyup();
        }

    })


    $(document).on('click','.acceptStatus',function(){

      $("#show_status").html('');

      var residence_id = $(this).attr('id');
      var certificate_id = $(this).data('id');

      $.ajax({
        url: 'certificateRequestStatus.php',
        type: 'POST',
        data:{
          residence_id:residence_id,
          certificate_id:certificate_id,
        },
        success:function(data){
          $("#show_status").html(data);
          $("#showStatusRequestModal").modal('show');
        }
      }).fail(barangayAjaxError)

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

 
 
  $("#searching").inputFilter(function(value) {
  return /^[0-9a-z, ]*$/i.test(value); 
  });

  $("#staff_purpose").inputFilter(function(value) {
    return /^[0-9a-z, ]*$/i.test(value);
  });

</script>


</body>
</html>
