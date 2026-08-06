<?php 

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/barangay_context.php';
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

    if (empty($barangay_id)) {
      header('Location: ../login.php');
      exit;
    }

    barangay_release_session_lock();

    $yes= 'YES';
    $no = 'NO';
    $year = [];
    $totalBlotter = [];
    $official_postition = [];
    $position_color = [];
    $total_per_official = [];

    $residentTotals = barangay_scoped_resident_totals($con, $barangay_id);
    $count_voters_yes = $residentTotals['voters_yes'];
    $count_voters_no = $residentTotals['voters_no'];
    $count_single_parent_yes = $residentTotals['single_parent'];
    $count_pwd_yes = $residentTotals['pwd'];
    $count_total_residence = $residentTotals['total'];
    $count_senior = $residentTotals['senior'];

    $count_children = barangay_count_children($con, $barangay_id);

    $sql_blotter = "SELECT date_added AS yyyy, COUNT(blotter_id) AS total
      FROM blotter_record WHERE barangay_id = ? GROUP BY date_added ORDER BY yyyy";
    $stmt_blotter = $con->prepare($sql_blotter) or die ($con->error);
    $stmt_blotter->bind_param('s', $barangay_id);
    $stmt_blotter->execute();
    $result_blotter = $stmt_blotter->get_result();
    if ($result_blotter->num_rows > 0) {
      while ($row_blotter = $result_blotter->fetch_assoc()) {
        $year[] = $row_blotter['yyyy'];
        $totalBlotter[] = (int) $row_blotter['total'];
      }
    } else {
      $year = [date('Y')];
      $totalBlotter = [0];
    }

    $sql_gender ="SELECT COUNT(CASE WHEN gender = 'Male' THEN residence_information.residence_id END) AS male,
    COUNT(CASE WHEN gender = 'Female' THEN residence_information.residence_id END) AS female
    FROM residence_information
    INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id
    WHERE archive = 'NO' AND residence_status.barangay_id = ?";
    $stmt_gender = $con->prepare($sql_gender) or die ($con->error);
    $stmt_gender->bind_param('s', $barangay_id);
    $stmt_gender->execute();
    $result_gender = $stmt_gender->get_result();
    $row_gender = $result_gender->fetch_assoc();
    $genderMale = (int) ($row_gender['male'] ?? 0);
    $genderFemale = (int) ($row_gender['female'] ?? 0);

    $sql_total_blotter = "SELECT COUNT(*) AS total FROM blotter_record WHERE barangay_id = ?";
    $stmt_total_blotter = $con->prepare($sql_total_blotter) or die ($con->error);
    $stmt_total_blotter->bind_param('s', $barangay_id);
    $stmt_total_blotter->execute();
    $total_blotter_record = (int) ($stmt_total_blotter->get_result()->fetch_assoc()['total'] ?? 0);

  $hasOfficialBarangayScope = barangay_column_exists($con, 'official_status', 'barangay_id');

  if ($hasOfficialBarangayScope) {
    $sql_count_official = "SELECT COUNT(official_id) AS total_official FROM official_status WHERE barangay_id = ?";
    $stmt_total_official = $con->prepare($sql_count_official) or die($con->error);
    $stmt_total_official->bind_param('s', $barangay_id);
  } else {
    $sql_count_official = "SELECT COUNT(os.official_id) AS total_official FROM official_status os
      INNER JOIN official_information oi ON os.official_id = oi.official_id
      WHERE oi.barangay = ?";
    $stmt_total_official = $con->prepare($sql_count_official) or die($con->error);
    $stmt_total_official->bind_param('s', $barangay);
  }
  $stmt_total_official->execute();
  $row_total_official = $stmt_total_official->get_result()->fetch_assoc();

  if ($hasOfficialBarangayScope) {
    $sql_official_position = "SELECT COUNT(*) AS dis, position.color, position.position AS official_position
      FROM position
      INNER JOIN official_status ON position.position_id = official_status.position
      WHERE official_status.barangay_id = ?
      GROUP BY official_status.position, position.position, position.color";
    $stmt_official_position = $con->prepare($sql_official_position) or die($con->error);
    $stmt_official_position->bind_param('s', $barangay_id);
  } else {
    $sql_official_position = "SELECT COUNT(*) AS dis, position.color, position.position AS official_position
      FROM position
      INNER JOIN official_status ON position.position_id = official_status.position
      INNER JOIN official_information oi ON official_status.official_id = oi.official_id
      WHERE oi.barangay = ?
      GROUP BY official_status.position, position.position, position.color";
    $stmt_official_position = $con->prepare($sql_official_position) or die($con->error);
    $stmt_official_position->bind_param('s', $barangay);
  }
  $stmt_official_position->execute();
  $result_official_position = $stmt_official_position->get_result();
  if ($result_official_position->num_rows > 0) {
    while ($row_official_position = $result_official_position->fetch_assoc()) {
      $official_postition[] = strtoupper($row_official_position['official_position']);
      $position_color[] = $row_official_position['color'];
      $total_per_official[] = $row_official_position['dis'];
    }
  } else {
    $official_postition[] = ['BLANK'];
    $position_color[] = ['red'];
    $total_per_official[] = ['1'];
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
      <span class="brand-text font-weight-light d-block mt-2">City of Valencia Portal</span>
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
            <a href="dashboard.php" class="nav-link bg-indigo">
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

                
      <div class="row">

<div class="col-sm-4">
  <div class="row">
      <div class="col-sm-12">
      <!-- small box -->
      <div class="small-box bg-info">
        <div class="inner">
          <h3><?= number_format($count_total_residence); ?></h3>
          <p>POPULATION</p>
        </div>
        <div class="icon">
          <i class="fas fa-users"></i>
        </div>
    
      </div>
    </div>
    <!-- ./col -->

    <div class="col-sm-12">
      <!-- small box -->
      <div class="small-box bg-success">
        <div class="inner">
          <h3><?= number_format($count_voters_yes) ?><stlye style="font-size: 20px"></stlye></h3>

          <p>VOTERS</p>
        </div>
        <div class="icon">
          <i class="fas fa-user-check"></i>
        </div>
      
      </div>
    </div>
    <!-- ./col -->

    <div class="col-sm-12 ">
      <!-- small box -->
      <div class="small-box bg-warning ">
        <div class="inner">
          <h3 class="text-white"><?= number_format($count_voters_no); ?></h3>

          <p class="text-white">NON VOTERS</p>
        </div>
        <div class="icon">
          <i class="fas fa-user-times"></i>
        </div>
    
      </div>
    </div>
    <!-- ./col -->

    <div class="col-sm-12">
      <!-- small box -->
      <div class="small-box bg-danger">
        <div class="inner">
          <h3><?= number_format($count_senior) ?></h3>

          <p>SENIOR CITIZEN</p>
        </div>
        <div class="icon">
          <i class="fas fa-blind"></i>
        </div>
  
      </div>
    </div>
    <!-- ./col -->

    <div class="col-sm-12">
      <!-- small box -->
      <div class="small-box bg-teal">
        <div class="inner">
          <h3><?= number_format($count_children) ?></h3>

          <p>CHILDREN (0–17)</p>
        </div>
        <div class="icon">
          <i class="fas fa-child"></i>
        </div>
  
      </div>
    </div>
    <!-- ./col -->

    <div class="col-sm-12">
      <!-- small box -->
      <div class="small-box bg-blue">
        <div class="inner">
          <h3><?= number_format($count_pwd_yes) ?><sup style="font-size: 20px"></sup></h3>

          <p>PERSONS WITH DISABILITIES</p>
        </div>
        <div class="icon">
          <i class="fas fa-wheelchair"></i>
        </div>
      
      </div>
    </div>
    <!-- ./col -->  
    <div class="col-sm-12">
      <!-- small box -->
      <div class="small-box bg-indigo">
        <div class="inner">
          <h3><?= number_format($total_blotter_record) ?><sup style="font-size: 20px"></sup></h3>

          <p>BLOTTER</p>
        </div>
        <div class="icon">
          <i class="fas fa-book"></i>
        </div>
      
      </div>
    </div>
    <!-- ./col -->   
    <div class="col-sm-12">
      <!-- small box -->
      <div class="small-box bg-fuchsia">
        <div class="inner">
          <h3><?= number_format($count_single_parent_yes) ?><sup style="font-size: 20px"></sup></h3>

          <p>SINGLE PARENT</p>
        </div>
        <div class="icon">
          <i class="fas fa-baby"></i>
        </div>
      
      </div>
    </div>
    <!-- ./col -->  

  </div>
</div>
<div class="col-sm-8">

  <div class="row">
    <div class="col-sm-12">

              <!-- USERS LIST -->
          <div class="card card-outline card-indigo"  id="official_body">
            <div class="card-header">
            <h1 class="card-title" style="font-weight:  700;"> <i class="fas fa-users-cog"></i> OFFICIAL MEMBERS <span class="badge badge-secondary text-lg"><?= $row_total_official['total_official']?></span></h1>   

              <div class="card-tools">
              
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body p-0 text-white">
              <div class="row">
                <div class="col-sm-6 scrollOfficial">

                    <ul class="users-list clearfix ">

                          <?php 

                          $sql_official = "SELECT position.color, position.position AS position_official, official_information.first_name, official_information.last_name, official_information.image_path, official_status.status,official_status.official_id FROM  official_status 
                          INNER JOIN official_information ON  official_status.official_id = official_information.official_id
                          INNER JOIN position ON  official_status.position = position.position_id ORDER BY position.position";
                          $stmt_official = $con->prepare($sql_official) or die ($con->error);
                          $stmt_official->execute();
                          $result_official = $stmt_official->get_result();
                          while($row_official = $result_official->fetch_assoc()){

                          if($row_official['image_path'] != ''){

                          if($row_official['status'] == 'ACTIVE'){
                            $official_image = '  <img src="'.$row_official['image_path'].'" class="w-50" style="border: 3px solid lime" alt="Official Image">';
                          }else{
                            $official_image = '  <img src="'.$row_official['image_path'].'" class="w-50" style="border: 3px solid red" alt="Official Image">';
                          }


                          }else{
                          if($row_official['status'] == 'ACTIVE'){
                            $official_image = '  <img src="../assets/dist/img/image.png" class="w-50" style="border: 3px solid lime" alt="Official Image">';
                          }else{
                            $official_image = '  <img src="../assets/dist/img/image.png" class="w-50" style="border: 3px solid red" alt="Official Image">';
                          }


                          }


                          ?>

                          <li id="<?= $row_official['official_id'] ?>" class="viewOfficial" style="cursor: pointer">
                            <?= $official_image; ?>
                            <p class="users-list-name m-0 text-white" ><?= $row_official['first_name'].' '. $row_official['last_name'] ?> </p>
                            <span class="users-list-date text-white" style="font-weight: 900"><?= strtoupper($row_official['position_official']) ?></span>
                          </li>

                          <?php
                          }



                          ?>


                          </ul>
                          <!-- /.users-list -->

                </div>
                <div class="col-sm-6">
              
                  <canvas id="donutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                
                
                </div>
              </div>
            
            </div>
            <!-- /.card-body -->
          
          </div>
          <!--/.card -->
      
    </div>
    <div class="col-sm-12">
        <div class="card card-outline card-indigo">
          <div class="card-body">
            <div class="row">
              <div class="col-sm-6">
                <p class="text-center">
                <strong>BLOTTER YEARLY</strong>
                </p>
                  <canvas id="myChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
              </div>

              <div class="col-sm-6">
              <p class="text-center">
                <strong>GENDER</strong>
                </p>
                <canvas  id="genderChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
              </div>
            </div>
          </div>
        </div>
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

<script src="../assets/plugins/chart.js/Chart.min.js"></script>

<div id="showOfficial"></div>



<script>


let myChart = document.getElementById('myChart').getContext('2d');

let massPopChart = new Chart(myChart,{
  type: 'line',
  data:{
    labels:<?php echo json_encode($year) ?>,
    datasets:[{
      label:'Record',
      fill: true,
      data: <?php echo json_encode($totalBlotter)?>,
      pointBorderColor: "aqua",
      borderWidth: 4,

      borderColor: 'red',
      hoverBorderWith: 4,
      hoverBorderColor: '#fff',
      borderDash: [2, 2],
      backgroundColor:  "rgba(255, 0, 0, 0.4)",

      
    }]
  },
  options:{
    responsive: true,
    
    title:{
      display:false,
      text: "Blotter",
      fontSize: 35,
      fontColor: '#fff',
    },
   
    legend:{
      display: false,
    },
    scales: {
        yAxes: [{
            ticks: {
                fontSize: 15,
                fontColor: '#fff',
                userCallback: function(label, index, labels) {
                     // when the floored value is the same as the value we have a whole number
                     if (Math.floor(label) === label) {
                         return label;
                     }
                 },
            },
            gridLines: {
                color: "#000",
            },
           
        }],
        xAxes: [{
            ticks: {
                fontSize: 15,
                fontColor: '#fff',
            },
            gridLines: {
                color: "#000",
            }
        }]
        
    }

  }
})












</script>

<script>
  new Chart("genderChart", {
  type: "doughnut",
  data: {
    labels: [
      'Male',
      'Female'
    ],
    datasets: [{
      backgroundColor: [
      "blue",
      "#00aba9",  
      ], 
      data: [<?= $genderMale ?>, <?= $genderFemale ?>]
    }]
  },
  options: {
    responsive: true,
    title: {
      display: false,
      text: "Gender",
      fontSize: 35,
      fontColor: '#fff',
    
    
    },
     legend:{
      display: true,
      fontColor: '#fff',
      labels: {
                fontSize: 15,
                fontColor: '#fff',
            }
    },
  
  }
});
</script>

<script>

new Chart("donutChart", {
  type: "pie",
  data: {
    labels: <?php echo json_encode($official_postition)?>,
      datasets: [
        {
          data: <?php echo json_encode($total_per_official)?>,
          backgroundColor : <?php echo json_encode($position_color)?>,
        }
      ]
  },
  options: {
    responsive: true,
    title: {
      display: false,
    
      fontSize: 35,
      fontColor: '#fff',
    
    
    },
     legend:{
      display: true,
      fontColor: '#fff',
      labels: {
                fontSize: 15,
                fontColor: '#fff',
            },
          
    },
  
  }
});
  
</script>
<script>
  $(document).ready(function(){

    $(document).on('click','.viewOfficial', function(){
      

      var official_id = $(this).attr('id');

      $("#showOfficial").html('');

      $.ajax({
          url: 'viewOfficialModal.php',
          type: 'POST',
          dataType: 'html',
          cache: false,
          data: {
            official_id:official_id
          },
          success:function(data){
            $("#showOfficial").html(data);
            $("#viewOfficialModal").modal('show');              
          }
        }).fail(barangayAjaxError)
     

    })
    

  })
</script>



</body>
</html>


              