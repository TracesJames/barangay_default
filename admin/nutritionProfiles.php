<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
nutrition_ensure_table($con);

$user_id = (string) $_SESSION['user_id'];
$stmt_user = $con->prepare('SELECT first_name, last_name, image, user_type FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'] ?? 'Admin';
$last_name_user = $row_user['last_name'] ?? '';
$user_type = $row_user['user_type'] ?? 'admin';
$user_image = $row_user['image'] ?? '';
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);

if (barangay_session_id() === null && ($isSuperAdmin || $isCityAdmin)) {
    header('Location: barangayHub.php?picker=1&system=nutrition');
    exit;
}

$activePage = 'profiles';
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$statusOptions = nutrition_status_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nutrition Profiles | <?= barangay_h($barangay) ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<?php require_once '../includes/head_csrf.php'; ?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260805n">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal nutrition-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark nutrition-navbar">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link text-white" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><h5 class="nav-link text-white mb-0">Nutrition Profiles</h5></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><a class="nav-link text-white" href="nutritionAssess.php"><i class="fas fa-plus mr-1"></i> New Assessment</a></li>
    </ul>
  </nav>

  <?php require_once '../includes/partials/nutrition_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <?php
        $nutritionPageIcon = 'fa-clipboard-list';
        $nutritionPageHeading = 'Nutrition Profiles';
        $nutritionPageDescription = 'Browse the latest nutrition assessments for residents in ' . $barangay . '. Filter by status to focus on at-risk cases.';
        $nutritionPageActions = '<a href="nutritionAssess.php" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> New Assessment</a>';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>
        <div class="card nutrition-panel">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0"><i class="fas fa-clipboard-list mr-2"></i>Latest Nutrition Assessments</h3>
            <div class="d-flex flex-wrap gap-2">
              <select id="nutritionStatusFilter" class="form-control form-control-sm nutrition-filter-select">
                <option value="">All statuses</option>
                <option value="at_risk" <?= $statusFilter === 'at_risk' ? 'selected' : '' ?>>At-risk only</option>
                <?php foreach ($statusOptions as $value => $label) : ?>
                <option value="<?= barangay_h($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= barangay_h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="card-body">
            <table id="nutritionProfilesTable" class="table table-bordered table-striped table-dark" style="width:100%">
              <thead>
                <tr>
                  <th>Resident ID</th>
                  <th>Name</th>
                  <th>Age</th>
                  <th>Date</th>
                  <th>Weight (kg)</th>
                  <th>Height (cm)</th>
                  <th>BMI</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script>
$(function () {
  var statusFilter = <?= json_encode($statusFilter, JSON_UNESCAPED_UNICODE) ?>;
  var table = $('#nutritionProfilesTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: 'nutritionProfilesTable.php',
      type: 'POST',
      data: function (d) {
        d.status_filter = $('#nutritionStatusFilter').val() || statusFilter;
      }
    },
    order: [[3, 'desc']],
    columns: [
      { data: 0 },
      { data: 1 },
      { data: 2 },
      { data: 3 },
      { data: 4 },
      { data: 5 },
      { data: 6 },
      { data: 7, orderable: false },
      { data: 8, orderable: false, searchable: false }
    ]
  });

  $('#nutritionStatusFilter').on('change', function () {
    table.ajax.reload();
  });
});
</script>
</body>
</html>
