<?php
/**
 * City Report Pack — one place for monthly LGU exports/prints.
 */
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/audit_log.php';

$user_id = (string) ($_SESSION['user_id'] ?? '');
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);

if (!$isSuperAdmin && !$isCityAdmin && !$isNutritionPortalAdmin) {
    header('Location: dashboard.php');
    exit;
}

barangay_audit_ensure_columns($con);

$stmt_user = $con->prepare('SELECT first_name, last_name, user_type, image FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc() ?: [];
$first_name_user = $row_user['first_name'] ?? '';
$last_name_user = $row_user['last_name'] ?? '';
$user_type = $row_user['user_type'] ?? 'admin';
$user_image = $row_user['image'] ?? '';

$monthLabel = date('F Y');
$barangayCount = count(barangay_list_all($con));

$unlinkedNutrition = 0;
if (barangay_table_exists($con, 'nutrition_household_survey')
    && barangay_column_exists($con, 'nutrition_household_survey', 'residence_id')) {
    $q = $con->query(
        "SELECT COUNT(*) AS c FROM nutrition_household_survey
         WHERE residence_id IS NULL OR TRIM(residence_id) = ''"
    );
    if ($q && ($r = $q->fetch_assoc())) {
        $unlinkedNutrition = (int) ($r['c'] ?? 0);
    }
}

$packs = [
    [
        'title' => 'Population & Residents',
        'desc' => 'Filtered resident listing for city / barangay reports.',
        'links' => [
            ['label' => 'Open Report Builder', 'href' => 'report.php', 'icon' => 'fa-users'],
            ['label' => 'All Residents', 'href' => 'allResidence.php', 'icon' => 'fa-list'],
        ],
    ],
    [
        'title' => 'Certificates',
        'desc' => 'City-wide certificate volume by barangay.',
        'links' => [
            ['label' => 'Barangay Certificates Summary', 'href' => 'barangayCertificates.php', 'icon' => 'fa-certificate'],
        ],
    ],
    [
        'title' => 'Nutrition City Pack',
        'desc' => 'BNP / EOPT / MELLPI and pregnant-family city prints.',
        'links' => [
            ['label' => 'City Nutrition Print Report', 'href' => 'nutritionSuperPrintReport.php', 'icon' => 'fa-print'],
            ['label' => 'Pregnant Families (City)', 'href' => 'nutritionSuperPregnantFamiliesPrint.php', 'icon' => 'fa-female'],
            ['label' => 'Nutrition Super Dashboard', 'href' => 'nutritionSuperDashboard.php', 'icon' => 'fa-leaf'],
        ],
    ],
    [
        'title' => 'Operations Health',
        'desc' => 'Audit trail and backup controls.',
        'links' => [
            ['label' => 'Audit Trail / System Logs', 'href' => 'systemLog.php', 'icon' => 'fa-history'],
            ['label' => 'Backup / Restore', 'href' => 'backupRestore.php', 'icon' => 'fa-database'],
            ['label' => 'Unlinked Nutrition Surveys (' . $unlinkedNutrition . ')', 'href' => 'nutritionUnlinkedHouseholds.php', 'icon' => 'fa-link'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>City Report Pack | <?= barangay_h($monthLabel) ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed barangay-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><span class="nav-link">City Report Pack</span></li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="barangayHub.php" class="brand-link text-center"><span class="brand-text font-weight-light">Barangay Hub</span></a>
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column">
          <li class="nav-item"><a href="barangayHub.php" class="nav-link"><i class="nav-icon fas fa-th"></i><p>Hub</p></a></li>
          <li class="nav-item"><a href="cityReportPack.php" class="nav-link active"><i class="nav-icon fas fa-file-alt"></i><p>City Report Pack</p></a></li>
          <li class="nav-item"><a href="systemLog.php" class="nav-link"><i class="nav-icon fas fa-history"></i><p>Audit Trail</p></a></li>
          <li class="nav-item"><a href="backupRestore.php" class="nav-link"><i class="nav-icon fas fa-database"></i><p>Backup</p></a></li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1>City Report Pack</h1>
        <p class="text-muted mb-0"><?= barangay_h($monthLabel) ?> · <?= number_format($barangayCount) ?> barangays · <?= barangay_h(trim($first_name_user . ' ' . $last_name_user)) ?></p>
      </div>
    </section>
    <section class="content">
      <div class="container-fluid">
        <div class="alert alert-info">
          Use this pack for monthly LGU reporting. Open each module, filter as needed, then Print / Export.
          Daily automated backups: see <code>docs/BACKUP_RESTORE.md</code>.
        </div>
        <div class="row">
          <?php foreach ($packs as $pack): ?>
            <div class="col-md-6 mb-3">
              <div class="card card-outline card-primary h-100">
                <div class="card-header"><h3 class="card-title mb-0"><?= barangay_h($pack['title']) ?></h3></div>
                <div class="card-body">
                  <p class="text-muted"><?= barangay_h($pack['desc']) ?></p>
                  <div class="d-flex flex-wrap" style="gap:.5rem;">
                    <?php foreach ($pack['links'] as $link): ?>
                      <a class="btn btn-sm btn-outline-light" href="<?= barangay_h($link['href']) ?>">
                        <i class="fas <?= barangay_h($link['icon']) ?>"></i> <?= barangay_h($link['label']) ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>
  <footer class="main-footer"><strong>&copy; <?= date('Y') ?> City of Valencia</strong></footer>
</div>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
</body>
</html>
