<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
try{
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){
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
    $user_image = $row_user['image'] ?? '';
    $user_image_path = $row_user['image_path'] ?? '';
    $userAvatarUrl = barangay_user_avatar_url($user_image, $user_image_path, '../');
    if ($userAvatarUrl === '') {
      $userAvatarUrl = '../assets/dist/img/image.png';
    }
    $isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
    $isCityAdmin = barangay_user_is_city_admin($con, $user_id);

    // Nutrition Portal accounts never enter Barangay Portal dashboards.
    if (barangay_user_is_nutrition_scoped_account($con, (string) $user_id)) {
      if (barangay_user_is_barangay_nutrition_scholar($con, (string) $user_id)) {
        header('Location: nutritionDashboard.php');
      } else {
        header('Location: nutritionSuperDashboard.php');
      }
      exit;
    }

    if ($isSuperAdmin && barangay_session_id() === null) {
      header('Location: superDashboard.php');
      exit;
    }

    if ($isCityAdmin && barangay_session_id() === null) {
      header('Location: barangayHub.php?picker=1');
      exit;
    }

    if (!$isSuperAdmin && !$isCityAdmin && empty($barangay_id)) {
      header('Location: barangayHub.php?picker=1');
      exit;
    }

    require_once '../includes/csrf.php';
    csrf_token();
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
    $count_indigenous_yes = $residentTotals['indigenous'];
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
  if($result_official_position->num_rows > 0){
    while($row_official_position = $result_official_position->fetch_assoc()){
      $official_postition[] = strtoupper($row_official_position['official_position']);
      $position_color[] = $row_official_position['color'];
      $total_per_official[] = $row_official_position['dis'];
    }
  }else{
    $official_postition[] = 'No Officials';
    $position_color[] = '#6c757d';
    $total_per_official[] = 1;
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
  <title>Dashboard | City of Valencia Portal</title>

 
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
  <link rel="stylesheet" href="../assets/css/dashboard.css">
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-dashboard barangay-portal">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <i class="fas fa-spinner fa-spin fa-2x text-white" aria-hidden="true"></i>
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
              <img src="<?= barangay_h($userAvatarUrl) ?>" class="img-size-50 mr-3 img-circle" alt="User Image" style="width:50px;height:50px;object-fit:cover;" onerror="this.src='../assets/dist/img/image.png'">
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
      <span class="brand-text font-weight-light d-block mt-2">City of Valencia Portal</span>
      <small class="d-block text-teal"><?= barangay_h($barangay) ?></small>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
    

    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="<?= barangay_h($userAvatarUrl) ?>" class="img-circle elevation-5 img-bordered-sm" alt="User Image" onerror="this.src='../assets/dist/img/image.png'">
        </div>
        <div class="info text-center">
          <a href="myProfile.php" class="d-block text-bold"><?= htmlspecialchars(ucfirst($first_name_user) . ' ' . ucfirst($last_name_user), ENT_QUOTES, 'UTF-8') ?></a>
          <small class="text-muted text-uppercase"><?= htmlspecialchars($user_type, ENT_QUOTES, 'UTF-8') ?></small>
        </div>
      </div>
      <!-- Sidebar Menu -->
      <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
          <?php if ($isSuperAdmin) { ?>
          <li class="nav-item">
            <a href="superDashboard.php" class="nav-link">
              <i class="nav-icon fas fa-city"></i>
              <p>Super Admin Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="barangayHub.php?picker=1" class="nav-link">
              <i class="nav-icon fas fa-th-large"></i>
              <p>All Barangays</p>
            </a>
          </li>
          <?php } ?>
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
            <a href="settings.php" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p>
                Settings
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="cityReportPack.php" class="nav-link">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>
                City Report Pack
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

        <div class="dashboard-welcome">
          <div class="row align-items-center">
            <div class="col-auto d-none d-md-block">
              <?php
                $dashLogo = barangay_logo_url($activeBarangay, '../');
              ?>
              <img src="<?= barangay_h($dashLogo) ?>" alt="" class="rounded-circle" style="width:72px;height:72px;object-fit:cover;border:3px solid rgba(255,255,255,.3);">
            </div>
            <div class="col-lg-8">
              <h1>Welcome back, <?= htmlspecialchars(ucfirst($first_name_user), ENT_QUOTES, 'UTF-8') ?>!</h1>
              <p><?= htmlspecialchars($barangay . ' · ' . $zone . ' · ' . $district, ENT_QUOTES, 'UTF-8') ?> — Barangay administration overview</p>
              <div class="dashboard-date"><i class="far fa-calendar-alt mr-1"></i> <?= date('l, F j, Y') ?></div>
              <div class="dashboard-actions">
                <?php if ($isSuperAdmin) { ?>
                <a href="superDashboard.php" class="btn btn-sm"><i class="fas fa-city"></i> Super Admin</a>
                <a href="barangayHub.php?picker=1" class="btn btn-sm"><i class="fas fa-th-large"></i> All Barangays</a>
                <?php } ?>
                <a href="newResidence.php" class="btn btn-sm"><i class="fas fa-user-plus"></i> New Resident</a>
                <a href="nutritionDashboard.php" class="btn btn-sm btn-success"><i class="fas fa-seedling"></i> Nutrition Profiling</a>
                <a href="newOfficial.php" class="btn btn-sm"><i class="fas fa-user-tie"></i> New Official</a>
                <a href="blotterRecord.php" class="btn btn-sm"><i class="fas fa-clipboard"></i> Blotter</a>
                <?php if (!empty($canIssueCertificate)) { ?>
                <a href="requestCertificate.php" class="btn btn-sm"><i class="fas fa-certificate"></i> Certificates</a>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>

        <div class="dashboard-stats">
          <a href="allResidence.php" class="dashboard-stat dashboard-stat--population dashboard-stat-link">
            <i class="fas fa-users dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_total_residence ?? 0) ?></div>
            <div class="dashboard-stat-label">Population</div>
          </a>
          <a href="allResidence.php?filter=voters" class="dashboard-stat dashboard-stat--voters dashboard-stat-link">
            <i class="fas fa-user-check dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_voters_yes ?? 0) ?></div>
            <div class="dashboard-stat-label">Registered Voters</div>
          </a>
          <a href="allResidence.php?filter=non_voters" class="dashboard-stat dashboard-stat--nonvoters dashboard-stat-link">
            <i class="fas fa-user-times dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_voters_no ?? 0) ?></div>
            <div class="dashboard-stat-label">Non-Voters</div>
          </a>
          <a href="allResidence.php?filter=senior" class="dashboard-stat dashboard-stat--senior dashboard-stat-link">
            <i class="fas fa-blind dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_senior ?? 0) ?></div>
            <div class="dashboard-stat-label">Senior Citizens</div>
          </a>
          <a href="allResidence.php?filter=children" class="dashboard-stat dashboard-stat--children dashboard-stat-link">
            <i class="fas fa-child dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_children ?? 0) ?></div>
            <div class="dashboard-stat-label">Children (0–17)</div>
          </a>
          <a href="allResidence.php?filter=pwd" class="dashboard-stat dashboard-stat--pwd dashboard-stat-link">
            <i class="fas fa-wheelchair dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_pwd_yes ?? 0) ?></div>
            <div class="dashboard-stat-label">Persons with Disability</div>
          </a>
          <a href="blotterRecord.php" class="dashboard-stat dashboard-stat--blotter dashboard-stat-link">
            <i class="fas fa-book dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($total_blotter_record ?? 0) ?></div>
            <div class="dashboard-stat-label">Blotter Records</div>
          </a>
          <a href="allResidence.php?filter=single_parent" class="dashboard-stat dashboard-stat--single dashboard-stat-link">
            <i class="fas fa-baby dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_single_parent_yes ?? 0) ?></div>
            <div class="dashboard-stat-label">Single Parents</div>
          </a>
          <a href="allResidence.php?filter=indigenous" class="dashboard-stat dashboard-stat--ip dashboard-stat-link">
            <i class="fas fa-feather-alt dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($count_indigenous_yes ?? 0) ?></div>
            <div class="dashboard-stat-label">Indigenous People (IP)</div>
          </a>
        </div>

        <div class="row">
          <div class="col-lg-7">
            <div class="card dashboard-panel" id="official_body">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">
                  <i class="fas fa-users-cog"></i> Official Members
                  <span class="badge-count"><?= (int) ($row_total_official['total_official'] ?? 0) ?></span>
                </h3>
                <div class="card-tools">
                  <a href="allOfficial.php" class="btn btn-tool text-light" title="View all"><i class="fas fa-external-link-alt"></i></a>
                  <button type="button" class="btn btn-tool text-light" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="officials-scroll">
                      <ul class="officials-grid">
                        <?php
                        if ($hasOfficialBarangayScope) {
                          $sql_official = "SELECT position.color, position.position AS position_official, official_information.first_name, official_information.last_name, official_information.image_path, official_status.status, official_status.official_id FROM official_status
                            INNER JOIN official_information ON official_status.official_id = official_information.official_id
                            INNER JOIN position ON official_status.position = position.position_id
                            WHERE official_status.barangay_id = ?
                            ORDER BY position.position";
                          $stmt_official = $con->prepare($sql_official) or die($con->error);
                          $stmt_official->bind_param('s', $barangay_id);
                        } else {
                          $sql_official = "SELECT position.color, position.position AS position_official, official_information.first_name, official_information.last_name, official_information.image_path, official_status.status, official_status.official_id FROM official_status
                            INNER JOIN official_information ON official_status.official_id = official_information.official_id
                            INNER JOIN position ON official_status.position = position.position_id
                            WHERE official_information.barangay = ?
                            ORDER BY position.position";
                          $stmt_official = $con->prepare($sql_official) or die($con->error);
                          $stmt_official->bind_param('s', $barangay);
                        }
                        $stmt_official->execute();
                        $result_official = $stmt_official->get_result();
                        $official_count = 0;
                        while ($row_official = $result_official->fetch_assoc()) {
                          $official_count++;
                          $is_active = $row_official['status'] === 'ACTIVE';
                          $status_class = $is_active ? 'is-active' : 'is-inactive';
                          if ($row_official['image_path'] != '') {
                            $img_src = $row_official['image_path'];
                          } else {
                            $img_src = '../assets/dist/img/image.png';
                          }
                        ?>
                        <li id="<?= htmlspecialchars($row_official['official_id'], ENT_QUOTES, 'UTF-8') ?>" class="official-card viewOfficial <?= $status_class ?>">
                          <img src="<?= htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8') ?>" alt="Official">
                          <p class="official-card-name"><?= htmlspecialchars($row_official['first_name'] . ' ' . $row_official['last_name'], ENT_QUOTES, 'UTF-8') ?></p>
                          <span class="official-card-role"><?= htmlspecialchars(strtoupper($row_official['position_official']), ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                        <?php } ?>
                      </ul>
                      <?php if ($official_count === 0) { ?>
                      <div class="officials-empty">
                        <i class="fas fa-user-tie d-block"></i>
                        <p>No officials registered yet.</p>
                        <a href="newOfficial.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Official</a>
                      </div>
                      <?php } ?>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="chart-panel">
                      <div class="chart-panel-title">Officials by Position</div>
                      <div class="chart-wrap"><canvas id="donutChart"></canvas></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card dashboard-panel">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Demographics</h3>
              </div>
              <div class="card-body">
                <div class="chart-panel mb-3">
                  <div class="chart-panel-title">Gender Distribution</div>
                  <div class="chart-wrap"><canvas id="genderChart"></canvas></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card dashboard-panel">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> Blotter Records by Year</h3>
              </div>
              <div class="card-body">
                <div class="chart-panel">
                  <div class="chart-wrap" style="height: 280px;"><canvas id="myChart"></canvas></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
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
setTimeout(function () {
  var preloader = document.querySelector('.preloader');
  if (preloader && preloader.offsetHeight > 0) {
    preloader.style.height = '0';
    preloader.style.overflow = 'hidden';
  }
}, 8000);
</script>



<script>
var chartGrid = 'rgba(255, 255, 255, 0.08)';
var chartText = '#c8cdd8';

var myChart = document.getElementById('myChart').getContext('2d');
new Chart(myChart, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($year) ?>,
    datasets: [{
      label: 'Blotter records',
      fill: true,
      data: <?php echo json_encode($totalBlotter) ?>,
      pointBackgroundColor: '#14b8a6',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 5,
      pointHoverRadius: 7,
      borderWidth: 3,
      borderColor: '#14b8a6',
      backgroundColor: 'rgba(20, 184, 166, 0.15)'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    legend: { display: false },
    scales: {
      yAxes: [{
        ticks: {
          fontColor: chartText,
          beginAtZero: true,
          userCallback: function (label) {
            if (Math.floor(label) === label) { return label; }
          }
        },
        gridLines: { color: chartGrid, zeroLineColor: chartGrid }
      }],
      xAxes: [{
        ticks: { fontColor: chartText },
        gridLines: { color: chartGrid }
      }]
    },
    tooltips: {
      backgroundColor: '#1e2433',
      titleFontColor: '#fff',
      bodyFontColor: '#c8cdd8',
      cornerRadius: 8
    }
  }
});
</script>

<script>
new Chart('genderChart', {
  type: 'doughnut',
  data: {
    labels: ['Male', 'Female'],
    datasets: [{
      backgroundColor: ['#14b8a6', '#6610f2'],
      borderColor: '#252b3b',
      borderWidth: 3,
      data: [<?= (int) ($genderMale ?? 0) ?>, <?= (int) ($genderFemale ?? 0) ?>]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutoutPercentage: 65,
    legend: {
      display: true,
      position: 'bottom',
      labels: { fontColor: chartText, padding: 16, boxWidth: 12 }
    },
    tooltips: {
      backgroundColor: '#1e2433',
      cornerRadius: 8
    }
  }
});
</script>

<script>
new Chart('donutChart', {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($official_postition) ?>,
    datasets: [{
      data: <?php echo json_encode($total_per_official) ?>,
      backgroundColor: <?php echo json_encode($position_color) ?>,
      borderColor: '#252b3b',
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutoutPercentage: 55,
    legend: {
      display: true,
      position: 'bottom',
      labels: { fontColor: chartText, fontSize: 11, padding: 12, boxWidth: 10 }
    },
    tooltips: {
      backgroundColor: '#1e2433',
      cornerRadius: 8
    }
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


              