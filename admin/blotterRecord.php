<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';
try{
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){

    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $first_name_user = $row_user['first_name']?? '';
    $last_name_user = $row_user['last_name']?? '';
    $user_type = $row_user['user_type']?? '';
    $user_image = $row_user['image']?? '';
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
  <link rel="stylesheet" href="../assets/plugins/jquery-ui/jquery-ui.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <style>
    .dark-mode .select2-selection{
      background-color: #343a40;
        border-color: #6c757d;
    }
    
    .modal-body{
        height: 80vh;
        overflow-y: auto;
    }
    .modal-body::-webkit-scrollbar {
        width: 5px;
    }                                                    
                            
    .modal-body::-webkit-scrollbar-thumb {
        background: #6c757d; 
        --webkit-box-shadow: inset 0 0 6px #6c757d; 
    }
    .modal-body::-webkit-scrollbar-thumb:window-inactive {
      background: #6c757d; 
    }
  
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
          <a href="#" class="d-block text-bold"><?= strtoupper($user_type) ?></a>
        </div>
      </div>
      <!-- Sidebar Menu -->
      <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link ">
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
                <a href="newOfficial.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Official</p>
                </a>
              </li>
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
          <?php if (!empty($canIssueCertificate)) { ?>
          <li class="nav-item ">
            <a href="requestCertificate.php" class="nav-link">
              <i class="nav-icon fas fa-certificate"></i>
              <p>
                Certificate
              </p>
            </a>
          </li>
          <?php } ?>
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
              <li class="nav-item">
                <a href="staffAccounts.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Staff Accounts</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="position.php" class="nav-link">
              <i class="nav-icon fas fa-user-tie"></i>
              <p>
                Position
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="blotterRecord.php" class="nav-link bg-indigo">
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
            <a href="settings.php" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p>
                Settings
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
          <li class="nav-item">
            <a href="backupRestore.php" class="nav-link">
              <i class="nav-icon fas fa-database"></i>
              <p>
                Backup/Restore
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
      <div class="container-fluid">

           
            <div class="card">
              <div class="card-header border-transparent">
                <h3 class="card-title">Lis of Records</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-tool bg-black btn-flat" id="addRecord" data-toggle="modal" data-target="#blotterRecordModal">
                    <i class="fas fa-plus"></i> New Record
                  </button>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body ">
                <table class="table table-striped table-hover " id="blotterRecordTable">             
                  <thead>
                    <tr>
                      <th><input type="checkbox" id="select_all"></th>
                      <th>Blotter Number</th>
                      <th>Status</th>
                      <th>Remarks</th>
                      <th>Incident</th>
                      <th>Location of Incident</th>
                      <th>Date Incident</th>
                      <th>Date Reported</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
              <div class="card-footer">
                  <div class="row ">
                    <div class="col-md-12 ">
                        <a type="button" data-toggle="tooltip" data-placement="right" title="Delete" style="border-radius: 20px; height: 40px; width: 10%;" class="btn btn-app elevation-3 p-1 m-0 bg-danger" id="delete_records">
                          <span style="height: 20px;  font-size: 1.1em; border-radius: 20px;" class="badge bg-indigo mr-2 mb-5" id="select_count"> 0 </span>
                          <i class="fas fa-trash-alt m-0 pt-1" ></i>
                        </a>
                    </div>
                  </div>
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
<div class="modal hide fade" id="blotterRecordModal" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form id="addNewRecordForm" method="post">

      <div class="modal-body">
        <div class="container-fluid">

          <div class="row">
                <div class="col-sm-12">
                    <div class="form-group form-group-sm">
                        <label>Complainant Resident</label>
                      <select name="complainant_residence[]" multiple="multiple" id="complainant_residence" class="select2bs4"  style="width: 100%;">
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Complainant Not Resident</label>
                      <textarea name="complainant_not_residence" id="complainant_not_residence" cols="57"  class="bg-transparent text-white form-control"></textarea>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Complainant Statement</label>
                      <textarea name="complainant_statement" id="complainant_statement" cols="57" rows="3" class="bg-transparent text-white form-control"></textarea>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Respondent</label>
                        <input name="respodent" id="respodent"  class=" form-control">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group form-group-sm">
                        <label>Person Involved Resident</label>
                      <select name="person_involed[]" multiple="multiple" id="person_involed" class="select2bs4"  style="width: 100%;">
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Person Involved Not Resident</label>
                      <textarea name="person_involevd_not_resident" id="person_involevd_not_resident" cols="57"  class="bg-transparent text-white form-control"></textarea>
                    </div>
                  </div> 
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Person Involved Statement</label>
                      <textarea name="person_statement" id="person_statement" cols="57" rows="3" class="bg-transparent text-white form-control"></textarea>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Location of Incident</label>
                        <input name="location_incident" id="location_incident"  class=" form-control">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Date of Incident</label>
                        <input type="datetime-local" name="date_of_incident" id="date_of_incident"  class=" form-control">
                    </div>
                  </div>  
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Incident</label>
                        <input name="incident" id="incident"  class=" form-control">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Status</label>
                        <select name="status" id="status" class="form-control">
                          <option value="NEW">NEW</option>
                          <option value="ONGOING">ONGOING</option>
                        </select>
                    </div>
                  </div> 
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Date Reported</label>
                        <input  type="datetime-local" name="date_reported" id="date_reported"  class=" form-control">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Remarks</label>
                        <select name="remarks" id="remarks" class="form-control">
                          <option value="OPEN">OPEN</option>
                          <option value="CLOSED">CLOSED</option>
                        </select>
                    </div>
                  </div>    
                  
         

          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-black elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
        <button type="submit" class="btn btn-primary elevation-5 px-3 btn-flat"><i class="fa fa-book-dead"></i> NEW RECORD</button>
      </div>
      
      </form>
    </div>
  </div>
</div>


<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php
$barangay_script_depth = 1;
require_once '../includes/scripts_csrf.php';
?>

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
<div id="show_residence"></div>
<div id="show_records"></div>




<script>
  $(document).ready(function(){

    blotterRecordTable();


    $(document).on('show.bs.modal', '.modal', function () {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
  

    $(document).on('click','.viewRecords', function(){

      var record_id = $(this).attr('id');
      

      $("#show_records").html('');

        $.ajax({
          url: 'viewRecordsModal.php',
          type: 'POST',
          data:{
            record_id:record_id,
          },
          cache: false,
          success:function(data){
            $("#show_records").html(data);
            $("#viewBlotterRecordModal").modal('show');
   
          }
        }).fail(barangayAjaxError)
    })

    function blotterRecordTable(){
      var blotterRecordTable = $("#blotterRecordTable").DataTable({

        processing: true,
        serverSide: true,
        order:[],
        autoWidth: false,
        responsive: true,
        ajax:{
          url: 'blotterRecordTable.php',
          type: 'POST',
        },
        columnDefs:[
          {
            targets: 0,
            orderable: false,
          },
          {
            targets: 8,
            orderable: false,
          },
         
        ]


      })
    }

  
   
    $("#complainant_residence, #person_involed").on('select2:select', function(e){
      var residence_id = e.params.data.id;
      $("#show_residence").html('');


      if(residence_id != ''){

        $.ajax({
          url: 'showResidenceInfo.php',
          type: 'POST',
          data:{
            residence_id:residence_id,
          },
          cache: false,
          success:function(data){
            $("#show_residence").html(data);
            $("#viewResidenceModal").modal('show');
          }
      }).fail(barangayAjaxError)

      }

     
      
    })


    $(function () {
        $.validator.setDefaults({
          submitHandler: function (form) {
     
          var complainant = $("#complainant_residence").val();
          var complainant_not_residence = $("#complainant_not_residence").val();
          var complainant_statement = $("#complainant_statement").val();
          var person_statement = $("#person_statement").val();
          var person_involed = $("#person_involed").val();
          var person_involevd_not_resident = $("#person_involevd_not_resident").val();
          

            if(complainant == '' && complainant_not_residence == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Complainant is Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }
            
            if(complainant_statement == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Complainant is Statement Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }

            if(person_involed == '' && person_involevd_not_resident == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Person Involved is Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }

            if(person_statement == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Person Involved Statement is Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }

            $.ajax({
              url: 'addNewBlotterRecord.php',
              type: 'POST',
              data: $(form).serialize(),
              cache: false,
              success:function(){

                Swal.fire({
                  title: '<strong class="text-success">SUCCESS</strong>',
                  type: 'success',
                  html: '<b>Added Record Blotter has Successfully<b>',
                  width: '400px',
                  confirmButtonColor: '#6610f2',
                  allowOutsideClick: false,
                  showConfirmButton: false,
                  timer: 2000,
                }).then(()=>{
                  $("#addNewRecordForm")[0].reset();
                  $("#blotterRecordTable").DataTable().ajax.reload();
                  $("#blotterRecordModal").modal('hide');
                  $("#complainant_residence").val([]).trigger("change")
                  $("#person_involed").val([]).trigger("change")
                 
                })

              }
            }).fail(barangayAjaxError)

     

          }
        });
      $('#addNewRecordForm').validate({
        ignore: "",
        rules: {
          date_reported: {
            required: true,
           
          },
          incident: {
            required: true,
           
          },
          date_of_incident: {
            required: true,
           
          },

        },
        messages: {
          date_reported: {
            required: "Please provide a Date Reported is Required",
            
          },
          incident: {
            required: "Incident is Required",
           
          },
          date_of_incident: {
            required: "Date Incident is Required",
           
          },
        
        
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
          element.closest('.form-group-sm').append(error);
        },
        highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        }
      });
    })


    $("#addRecord").on('click',function(){
      $("#addNewRecordForm")[0].reset();
      $(".select2-selection__choice").css('display', 'none')
      
    })

 

    
  })
</script>
<script>
  function blotterFormatState(opt) {
    if (!opt.id) {
      return opt.text ? opt.text.toUpperCase() : opt.text;
    }
    var image = opt.image || $(opt.element).data('image');
    if (!image) {
      return opt.text.toUpperCase();
    }
    return $('<span><img class="img-circle pb-1" src="' + image + '" width="20px" /> ' + opt.text.toUpperCase() + '</span>');
  }

  function blotterResidentSelect2($el, excludeFn) {
    $el.select2({
      theme: 'bootstrap4',
      placeholder: 'Type at least 2 characters to search',
      minimumInputLength: 2,
      allowClear: true,
      ajax: {
        url: 'blotterResidentSearch.php',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term || '',
            page: params.page || 1,
            exclude: excludeFn ? excludeFn() : []
          };
        },
        processResults: function (data) {
          return data;
        }
      },
      templateResult: blotterFormatState,
      templateSelection: blotterFormatState
    });
  }

  $(document).ready(function () {
    blotterResidentSelect2($('#complainant_residence'), function () { return []; });
    blotterResidentSelect2($('#person_involed'), function () {
      return $('#complainant_residence').val() || [];
    });

    $('#complainant_residence').on('change', function () {
      $('#person_involed').val(null).trigger('change');
    });
  });
</script>




<script>
/* delete subject records*/
$(document).ready(function() {
  $('#delete_records').on('click', function() {
    var subject = [];
    $(".sub_checkbox:checked").each(function() {
      subject.push($(this).attr('id'));
    });

    if (subject.length <= 0) {
      Swal.fire({
        title: '<strong class="text-info">NOTE</strong>',
        html: '<b>Please Select Record to Delete!<b>',
        type: "info",
        showConfirmButton: false,
        confirmButtonColor: '#6610f2',
        width: '400px',
        showConfirmButton: true,
        allowOutsideClick: false,
      });
    } else {
      Swal.fire({
        title: '<strong class="text-info">ARE YOU SURE?</strong>',
        html: "<b>You want delete selected Record?</b>",
        type: 'info',
        showCancelButton: true,
        confirmButtonColor: '#6610f2',
        width: '400px',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        allowOutsideClick: false,
      }).then((result) => {
        if (result.value) {
          var selected_values = subject.join(",");
          $.ajax({
            type: "POST",
            url: "deleteBlotterRecord.php",
            cache: false,
            data: 'id=' + selected_values,
            success: function(data) {
              Swal.fire({
                title: '<strong class="text-success">SUCESS</strong>',
                text: "Deleted Blotter Record Successfully",
                type: 'success',
                timer: 1500,
                width: '400px',
                showConfirmButton: false,
                allowOutsideClick: false,
              }).then(() => {
                $("#blotterRecordTable").DataTable().ajax.reload();
                $("#select_count").text('0');
                $('#select_all').prop('checked', false);
              });
            }
          }).fail(barangayAjaxError);
        }
      });
    }
  });
});

$(document).on('click', '#select_all', function() {
  $(".sub_checkbox").prop("checked", this.checked);
  $("#select_count").html($("input.sub_checkbox:checked").length);
});
$(document).on('click', '.sub_checkbox', function() {
  if ($('.sub_checkbox:checked').length == $('.sub_checkbox').length) {
    $('#select_all').prop('checked', true);
  } else {
    $('#select_all').prop('checked', false);
  }
  $("#select_count").html($("input.sub_checkbox:checked").length);
});
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



  $("#complainant_not_residence, #person_involevd_not_resident").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
  $("#complainant_statement, #respodent,#incident,#location_incident,#person_statement").inputFilter(function(value) {
  return /^[0-9a-z, ,-]*$/i.test(value); 
  });

</script>




</body>
</html>
