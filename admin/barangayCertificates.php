<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/csrf.php';

if (!barangay_user_is_super_admin($con, (string) $_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

barangay_clear_active();

$user_id = $_SESSION['user_id'];
$stmt_user = $con->prepare('SELECT first_name, last_name, image FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'] ?? 'Admin';
$last_name_user = $row_user['last_name'] ?? '';
$user_image = $row_user['image'] ?? '';
$staffRoleLabel = 'Super Admin';
$activePage = 'certificates';
$brandLogo = barangay_default_logo_url('../');

$certificateSummaries = barangay_certificate_summary_rows($con);
$totalCertificates = array_sum(array_column($certificateSummaries, 'total'));
$totalIssued = array_sum(array_column($certificateSummaries, 'issued'));
$totalPending = array_sum(array_column($certificateSummaries, 'pending'));
$totalRejected = array_sum(array_column($certificateSummaries, 'rejected'));
$selectedBarangayId = trim($_GET['barangay_id'] ?? '');
$certificateListExpanded = $selectedBarangayId !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificates by Barangay | Super Admin</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/super-dashboard.css">
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-super-dashboard barangay-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link text-white" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><h5 class="nav-link text-white mb-0">Certificates by Barangay</h5></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a></li>
    </ul>
  </nav>

  <?php require __DIR__ . '/../includes/partials/super_admin_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">
        <div class="super-dashboard-welcome">
          <h1>Certificates Issued per Barangay</h1>
          <p>City-wide list of resident certificate requests across all Valencia City barangays.</p>
          <div class="super-dashboard-actions">
            <a href="superDashboard.php" class="btn btn-sm"><i class="fas fa-city"></i> Super Dashboard</a>
            <a href="barangayHub.php?picker=1" class="btn btn-sm"><i class="fas fa-th-large"></i> Barangay Hub</a>
          </div>
        </div>

        <div class="dashboard-stats">
          <div class="dashboard-stat dashboard-stat--population">
            <i class="fas fa-certificate dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($totalCertificates) ?></div>
            <div class="dashboard-stat-label">Total Requests</div>
          </div>
          <div class="dashboard-stat dashboard-stat--voters">
            <i class="fas fa-check-circle dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($totalIssued) ?></div>
            <div class="dashboard-stat-label">Issued (Accepted)</div>
          </div>
          <div class="dashboard-stat dashboard-stat--children">
            <i class="fas fa-clock dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($totalPending) ?></div>
            <div class="dashboard-stat-label">Pending</div>
          </div>
          <div class="dashboard-stat dashboard-stat--blotter">
            <i class="fas fa-times-circle dashboard-stat-icon"></i>
            <div class="dashboard-stat-value"><?= number_format($totalRejected) ?></div>
            <div class="dashboard-stat-label">Rejected</div>
          </div>
        </div>

        <div class="card dashboard-panel">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-1"></i> Summary by Barangay</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover mb-0 super-barangay-table">
              <thead>
                <tr>
                  <th>Barangay</th>
                  <th class="text-center">Total</th>
                  <th class="text-center">Issued</th>
                  <th class="text-center">Pending</th>
                  <th class="text-center">Rejected</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($certificateSummaries as $summary) : ?>
                <tr>
                  <td>
                    <img src="<?= barangay_h($summary['logo']) ?>" alt="" class="barangay-logo-sm mr-2">
                    <strong><?= barangay_h($summary['barangay']) ?></strong>
                  </td>
                  <td class="text-center"><?= number_format((int) $summary['total']) ?></td>
                  <td class="text-center"><span class="badge badge-success"><?= number_format((int) $summary['issued']) ?></span></td>
                  <td class="text-center"><span class="badge badge-warning"><?= number_format((int) $summary['pending']) ?></span></td>
                  <td class="text-center"><span class="badge badge-danger"><?= number_format((int) $summary['rejected']) ?></span></td>
                  <td class="text-center">
                    <a href="barangayCertificates.php?barangay_id=<?= barangay_h($summary['id']) ?>#certificate-list" class="btn btn-xs btn-primary">
                      <i class="fas fa-eye"></i> View List
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card dashboard-panel certificate-requests-panel<?= $certificateListExpanded ? '' : ' collapsed-card' ?>" id="certificate-list">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0"><i class="fas fa-certificate mr-1"></i> All Certificate Requests <span class="badge badge-success bg-lime" id="total"><?= number_format($totalCertificates) ?></span></h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool text-light" data-card-widget="collapse" title="Expand / collapse">
                <i class="fas fa-<?= $certificateListExpanded ? 'minus' : 'plus' ?>"></i>
              </button>
            </div>
          </div>
          <div class="card-body certificate-requests-body">
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="text-sm mb-1" for="barangay_id">Filter by Barangay</label>
                <select id="barangay_id" class="form-control">
                  <option value="">All Barangays</option>
                  <?php foreach ($certificateSummaries as $summary) : ?>
                  <option value="<?= barangay_h($summary['id']) ?>" <?= $selectedBarangayId === (string) $summary['id'] ? 'selected' : '' ?>>
                    <?= barangay_h($summary['barangay']) ?> (<?= number_format((int) $summary['total']) ?>)
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="text-sm mb-1" for="status">Status</label>
                <select id="status" class="form-control">
                  <option value="">All Status</option>
                  <option value="PENDING">PENDING</option>
                  <option value="ACCEPTED">ACCEPTED</option>
                  <option value="REJECTED">REJECTED</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="text-sm mb-1" for="searching">Search</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="searching" placeholder="Name, purpose, resident ID…" autocomplete="off">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-secondary" id="reset"><i class="fas fa-undo"></i></button>
                  </div>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-striped text-sm" id="certificateTable">
                <thead>
                  <tr>
                    <th>Barangay</th>
                    <th>Resident ID</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Date Request</th>
                    <th>Date Issued</th>
                    <th>Date Expired</th>
                    <th>Status</th>
                    <th class="text-center">Tools</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<div id="show_status"></div>
<script>
$(function () {
  var certificateTable;
  var certificateTableLoaded = false;
  var $certificateList = $('#certificate-list');

  function loadCertificateTable() {
    if ($.fn.DataTable.isDataTable('#certificateTable')) {
      $('#certificateTable').DataTable().destroy();
    }

    certificateTable = $('#certificateTable').DataTable({
      processing: true,
      serverSide: true,
      ordering: false,
      autoWidth: false,
      pageLength: 10,
      lengthMenu: [[10, 25, 50], [10, 25, 50]],
      ajax: {
        url: 'barangayCertificateTable.php',
        type: 'POST',
        data: function (d) {
          d.barangay_id = $('#barangay_id').val();
          d.status = $('#status').val();
          d.date_request = '';
          d.date_issued = '';
          d.date_expired = '';
        }
      },
      drawCallback: function (data) {
        $('#total').text(data.json.total || '0');
        $('[data-toggle="tooltip"]').tooltip();
      }
    });
    certificateTableLoaded = true;
  }

  function ensureCertificateTable() {
    if (!certificateTableLoaded) {
      loadCertificateTable();
      return;
    }
    certificateTable.ajax.reload();
  }

  if (!$certificateList.hasClass('collapsed-card')) {
    loadCertificateTable();
  }

  $certificateList.on('expanded.lte.cardwidget', function () {
    ensureCertificateTable();
  });

  $('#searching').on('keyup', function () {
    if (!certificateTableLoaded) {
      return;
    }
    certificateTable.search($(this).val()).draw();
  });

  $('#barangay_id, #status').on('change', function () {
    if (!certificateTableLoaded) {
      if (!$certificateList.hasClass('collapsed-card')) {
        loadCertificateTable();
      }
      return;
    }
    certificateTable.ajax.reload();
    $('#searching').trigger('keyup');
  });

  $('#reset').on('click', function () {
    $('#barangay_id').val('');
    $('#status').val('');
    $('#searching').val('');
    if (!certificateTableLoaded) {
      if (!$certificateList.hasClass('collapsed-card')) {
        loadCertificateTable();
      }
      return;
    }
    certificateTable.ajax.reload();
  });

  $(document).on('click', '.acceptStatus', function () {
    var id = $(this).data('id');
    var residence_id = $(this).attr('id');
    $('#show_status').load('certificateRequestStatus.php?id=' + encodeURIComponent(id) + '&residence_id=' + encodeURIComponent(residence_id));
  });
});
</script>
</body>
</html>
