<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/csrf.php';

nutrition_admin_redirect_if_needed($con, (string) $_SESSION['user_id']);

if (barangay_user_is_nutrition_scoped_account($con, (string) $_SESSION['user_id'])) {
    header('Location: nutritionSuperDashboard.php');
    exit;
}

if (!barangay_user_is_super_admin($con, (string) $_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

barangay_clear_active();
csrf_token();
barangay_release_session_lock();

$user_id = $_SESSION['user_id'];
$stmt_user = $con->prepare('SELECT first_name, last_name, image, user_type FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'] ?? 'Admin';
$last_name_user = $row_user['last_name'] ?? '';
$user_type = $row_user['user_type'] ?? 'admin';
$user_image = $row_user['image'] ?? '';
$staffRoleLabel = staff_role_label(barangay_user_staff_role($con, (string) $user_id));
$activePage = 'super_dashboard';

$hubTotals = barangay_hub_totals($con);
$barangayRows = barangay_super_dashboard_rows($con);
$barangayCount = count($barangayRows);
$totalOfficials = array_sum(array_column($barangayRows, 'officials'));
$brandLogo = barangay_default_logo_url('../');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin Dashboard | City of Valencia Portal</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/super-dashboard.css?v=20260720b">
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-dashboard barangay-super-dashboard barangay-portal">
<div class="wrapper">
  <div class="preloader flex-column justify-content-center align-items-center">
    <i class="fas fa-spinner fa-spin fa-2x text-white" aria-hidden="true"></i>
  </div>

  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white mb-0">City of Valencia Portal · Super Admin</h5>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link text-white" data-toggle="dropdown" href="#"><i class="far fa-user"></i></a>
        <div class="dropdown-menu dropdown-menu-right">
          <a href="myProfile.php" class="dropdown-item"><?= barangay_h(ucfirst($first_name_user) . ' ' . ucfirst($last_name_user)) ?></a>
          <div class="dropdown-divider"></div>
          <a href="../logout.php" class="dropdown-item">LOGOUT</a>
        </div>
      </li>
    </ul>
  </nav>

  <?php require __DIR__ . '/../includes/partials/super_admin_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header"></section>
    <section class="content">
      <div class="container-fluid">
        <div class="super-dashboard-welcome">
          <h1>Super Admin Dashboard</h1>
          <p>City-wide overview across all <?= number_format($barangayCount) ?> barangays in Valencia City, Bukidnon.</p>
          <div class="dashboard-date"><i class="far fa-calendar-alt mr-1"></i> <?= date('l, F j, Y') ?></div>
          <div class="super-dashboard-actions">
            <a href="barangayHub.php?picker=1" class="btn btn-sm"><i class="fas fa-th-large"></i> Manage Barangays</a>
            <a href="nutritionSuperDashboard.php" class="btn btn-sm"><i class="fas fa-apple-alt"></i> Nutrition Dashboard</a>
            <a href="staffAccounts.php" class="btn btn-sm"><i class="fas fa-user-shield"></i> Staff Accounts</a>
            <a href="systemLog.php" class="btn btn-sm"><i class="fas fa-history"></i> System Logs</a>
          </div>
        </div>

        <h2 class="super-section-heading"><i class="fas fa-bolt mr-2"></i>Quick Actions</h2>
        <div class="super-workflow-grid">
          <a href="barangayHub.php?picker=1" class="super-workflow-card super-workflow-card--barangay">
            <span class="super-workflow-card-icon"><i class="fas fa-th-large"></i></span>
            <span class="super-workflow-card-title">Barangay Dashboard</span>
            <span class="super-workflow-card-desc">Open any barangay portal to manage officials, residents, certificates, and blotter.</span>
          </a>
          <a href="nutritionSuperDashboard.php" class="super-workflow-card super-workflow-card--nutrition">
            <span class="super-workflow-card-icon"><i class="fas fa-apple-alt"></i></span>
            <span class="super-workflow-card-title">Nutrition Dashboard</span>
            <span class="super-workflow-card-desc">Switch into Nutrition Profiling for surveys, assessments, and reports.</span>
          </a>
          <a href="staffAccounts.php" class="super-workflow-card super-workflow-card--accounts">
            <span class="super-workflow-card-icon"><i class="fas fa-user-shield"></i></span>
            <span class="super-workflow-card-title">Staff &amp; BNS Accounts</span>
            <span class="super-workflow-card-desc">Create Super Admin, Admin, Barangay Staff, BNS, and BNS Admin accounts.</span>
          </a>
          <a href="barangayCertificates.php" class="super-workflow-card super-workflow-card--system">
            <span class="super-workflow-card-icon"><i class="fas fa-certificate"></i></span>
            <span class="super-workflow-card-title">Certificates</span>
            <span class="super-workflow-card-desc">Review city-wide certificate requests across all barangays.</span>
          </a>
        </div>

        <h2 class="super-section-heading"><i class="fas fa-chart-bar mr-2"></i>City Overview</h2>
        <div class="dashboard-stats">
          <div class="dashboard-stat dashboard-stat--barangays">
            <i class="fas fa-map-marked-alt dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($barangayCount) ?></div>
            <div class="dashboard-stat-label">Barangays</div>
          </div>
          <div class="dashboard-stat dashboard-stat--population">
            <i class="fas fa-users dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) ($hubTotals['population'] ?? 0)) ?></div>
            <div class="dashboard-stat-label">Total Population</div>
          </div>
          <div class="dashboard-stat dashboard-stat--voters">
            <i class="fas fa-user-check dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) ($hubTotals['voters'] ?? 0)) ?></div>
            <div class="dashboard-stat-label">Registered Voters</div>
          </div>
          <div class="dashboard-stat dashboard-stat--senior">
            <i class="fas fa-blind dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) ($hubTotals['senior'] ?? 0)) ?></div>
            <div class="dashboard-stat-label">Senior Citizens</div>
          </div>
          <div class="dashboard-stat dashboard-stat--children">
            <i class="fas fa-child dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) ($hubTotals['children'] ?? 0)) ?></div>
            <div class="dashboard-stat-label">Children (0–17)</div>
          </div>
          <div class="dashboard-stat dashboard-stat--pwd">
            <i class="fas fa-wheelchair dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) ($hubTotals['pwd'] ?? 0)) ?></div>
            <div class="dashboard-stat-label">PWD</div>
          </div>
          <div class="dashboard-stat dashboard-stat--single">
            <i class="fas fa-user-tie dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) $totalOfficials) ?></div>
            <div class="dashboard-stat-label">Officials</div>
          </div>
          <div class="dashboard-stat dashboard-stat--ip">
            <i class="fas fa-feather-alt dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) ($hubTotals['indigenous'] ?? 0)) ?></div>
            <div class="dashboard-stat-label">Indigenous People</div>
          </div>
          <div class="dashboard-stat dashboard-stat--blotter">
            <i class="fas fa-book dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format((int) ($hubTotals['blotter'] ?? 0)) ?></div>
            <div class="dashboard-stat-label">Blotter Records</div>
          </div>
        </div>

        <div class="card dashboard-panel">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-1"></i> All Barangays</h3>
            <div class="card-tools">
              <a href="barangayHub.php?picker=1" class="btn btn-sm btn-primary"><i class="fas fa-th-large mr-1"></i> Open Hub</a>
            </div>
          </div>
          <div class="card-body">
            <table id="superBarangayTable" class="table table-bordered table-striped super-barangay-table">
              <thead>
                <tr>
                  <th>Barangay</th>
                  <th>Zone</th>
                  <th>Population</th>
                  <th>Officials</th>
                  <th>Certificates</th>
                  <th>Blotter</th>
                  <th>Admin Account</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($barangayRows as $row) { ?>
                <tr>
                  <td>
                    <img src="<?= barangay_h($row['logo']) ?>" alt="" class="barangay-logo-sm mr-2">
                    <strong><?= barangay_h($row['barangay']) ?></strong>
                  </td>
                  <td><?= barangay_h($row['zone']) ?></td>
                  <td><?= number_format((int) $row['population']) ?></td>
                  <td><?= number_format((int) $row['officials']) ?></td>
                  <td><?= number_format((int) ($row['certificates'] ?? 0)) ?></td>
                  <td><?= number_format((int) $row['blotter']) ?></td>
                  <td><code><?= barangay_h($row['admin_username'] ?? '—') ?></code></td>
                  <td>
                    <form method="post" action="selectBarangay.php" class="d-inline js-open-barangay-form">
                      <?= csrf_field(); ?>
                      <input type="hidden" name="barangay_id" value="<?= barangay_h($row['id']) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt mr-1"></i> Open
                      </button>
                    </form>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-sm">
    <strong>City of Valencia Portal</strong> — Super Admin
    <div class="float-right d-none d-sm-inline-block">v1.0</div>
  </footer>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script>
function hideSuperPreloader() {
  var preloader = document.querySelector('.preloader');
  if (preloader) {
    preloader.style.height = '0';
    preloader.style.overflow = 'hidden';
    preloader.style.pointerEvents = 'none';
  }
}

$(function () {
  hideSuperPreloader();
  $(window).on('load', hideSuperPreloader);
  setTimeout(hideSuperPreloader, 2000);

  $('#superBarangayTable').DataTable({
    responsive: true,
    autoWidth: false,
    order: [[0, 'asc']],
    pageLength: 10
  });

  $(document).on('submit', '.js-open-barangay-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    if (typeof barangaySyncCsrfForms === 'function') {
      barangaySyncCsrfForms();
    }
    $.ajax({
      url: $form.attr('action') || 'selectBarangay.php',
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function (res) {
        window.location.href = res.redirect || 'dashboard.php';
      }
    }).fail(function () {
      alert('Could not open barangay dashboard. Please refresh and try again.');
    });
  });
});
</script>
</body>
</html>
