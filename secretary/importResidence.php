<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_import.php';
require_once '../includes/csrf.php';

$activeBarangay = barangay_require_active($con, '../admin/barangayHub.php?picker=1');
$importResult = $_SESSION['residence_import_result'] ?? null;
unset($_SESSION['residence_import_result']);

$user_id = $_SESSION['user_id'];
$stmt_user = $con->prepare('SELECT first_name, last_name, user_type, image FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'] ?? '';
$last_name_user = $row_user['last_name'] ?? '';
$user_type = $row_user['user_type'] ?? 'secretary';
$user_image = $row_user['image'] ?? '';

$processUrl = 'importResidenceProcess.php';
$templateUrl = 'downloadResidenceImportTemplate.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Import Residents | <?= barangay_h($barangay) ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/css/barangay.css">
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link text-white" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><h5 class="nav-link text-white mb-0"><?= barangay_h($barangay) ?></h5></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><a class="nav-link" href="myProfile.php"><i class="far fa-user"></i></a></li>
      <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
    <a href="dashboard.php" class="brand-link text-center">
      <img src="<?= barangay_h($sidebarLogo) ?>" class="img-circle elevation-5 img-bordered-sm" alt="<?= barangay_h($barangay) ?>" style="width:70%;">
    </a>
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview">
          <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
          <li class="nav-item menu-open">
            <a href="#" class="nav-link bg-indigo"><i class="nav-icon fas fa-users"></i><p>Residence<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="newResidence.php" class="nav-link"><i class="fas fa-circle nav-icon text-red"></i><p>New Residence</p></a></li>
              <li class="nav-item"><a href="importResidence.php" class="nav-link active"><i class="fas fa-circle nav-icon text-red"></i><p>Import from Excel</p></a></li>
              <li class="nav-item"><a href="allResidence.php" class="nav-link"><i class="fas fa-circle nav-icon text-red"></i><p>All Residence</p></a></li>
              <li class="nav-item"><a href="familyHouseholdHead.php" class="nav-link"><i class="fas fa-circle nav-icon text-red"></i><p>Family House Hold</p></a></li>
              <li class="nav-item"><a href="archiveResidence.php" class="nav-link"><i class="fas fa-circle nav-icon text-red"></i><p>Archive Residence</p></a></li>
            </ul>
          </li>
          <li class="nav-item"><a href="allResidence.php" class="nav-link"><i class="nav-icon fas fa-arrow-left"></i><p>Back to Residents</p></a></li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <section class="content mt-3">
      <div class="container-fluid">
        <?php require __DIR__ . '/../includes/partials/residence_import_panel.php'; ?>
      </div>
    </section>
  </div>
</div>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
</body>
</html>
