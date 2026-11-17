<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/csrf.php';

$sessionUserId = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_can_manage_puroks($con, $sessionUserId)) {
    http_response_code(403);
    exit('You do not have permission to manage Purok / Sitio.');
}

$useNutritionShell = barangay_user_can_access_nutrition_portal($con, $sessionUserId)
    && !barangay_user_is_barangay_hub_super_admin($con, $sessionUserId);

if ($useNutritionShell) {
    require_once '../includes/partials/nutrition_init.php';
    $activePage = 'manage_puroks';
    $nutritionPageTitle = 'Manage Purok / Sitio';
    $nutritionIncludeScriptsCsrf = true;
}

$barangayId = (string) ($barangay_id ?? barangay_session_id() ?? '');
$barangayName = (string) ($barangay ?? '');
if ($barangayId === '') {
    header('Location: ' . ($useNutritionShell ? 'nutritionSuperDashboard.php' : 'dashboard.php'));
    exit;
}
if ($barangayName === '') {
    $loaded = function_exists('barangay_load_by_id') ? barangay_load_by_id($con, $barangayId) : null;
    $barangayName = (string) ($loaded['barangay'] ?? 'Barangay');
}

barangay_purok_ensure_active_column($con);
$puroks = barangay_list_puroks($con, $barangayId, true);
$flash = trim((string) ($_GET['msg'] ?? ''));
$flashError = trim((string) ($_GET['error'] ?? ''));

$renderBody = static function () use ($con, $barangayId, $barangayName, $puroks, $flash, $flashError, $useNutritionShell): void {
    ?>
        <?php if ($flash !== '') : ?>
        <div class="alert alert-success"><?= barangay_h($flash) ?></div>
        <?php endif; ?>
        <?php if ($flashError !== '') : ?>
        <div class="alert alert-danger"><?= barangay_h($flashError) ?></div>
        <?php endif; ?>

        <div class="row">
          <div class="col-lg-5">
            <div class="card <?= $useNutritionShell ? 'nutrition-panel' : '' ?>">
              <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-plus mr-2"></i>Add Purok / Sitio</h3>
              </div>
              <form method="post" action="saveManagePurok.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="barangay_id" value="<?= barangay_h($barangayId) ?>">
                <div class="card-body">
                  <div class="form-group">
                    <label for="purok_name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="purok_name" name="purok_name" maxlength="120" required placeholder="e.g. PUROK 1 or PUROK SITIO BANTAL 1">
                  </div>
                  <div class="form-group mb-0">
                    <label for="purok_leader">Leader (optional)</label>
                    <input type="text" class="form-control" id="purok_leader" name="purok_leader" maxlength="120">
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Add</button>
                </div>
              </form>
            </div>
            <div class="card <?= $useNutritionShell ? 'nutrition-panel' : '' ?>">
              <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-layer-group mr-2"></i>Bulk add</h3>
              </div>
              <form method="post" action="saveManagePurok.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="bulk_add">
                <input type="hidden" name="barangay_id" value="<?= barangay_h($barangayId) ?>">
                <div class="card-body">
                  <label for="bulk_names">One per line</label>
                  <textarea class="form-control" id="bulk_names" name="bulk_names" rows="6" placeholder="PUROK 1&#10;PUROK 2 | Juan Dela Cruz&#10;PUROK SITIO BANTAL 1"></textarea>
                  <small class="form-text text-muted">Optional leader: <code>NAME | Leader</code> or <code>NAME, Leader</code></small>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-outline-success"><i class="fas fa-list mr-1"></i> Add list</button>
                </div>
              </form>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="card <?= $useNutritionShell ? 'nutrition-panel' : '' ?>">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Official List — <?= barangay_h($barangayName) ?></h3>
                <span class="badge badge-success"><?= number_format(count($puroks)) ?></span>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-striped mb-0">
                    <thead>
                      <tr>
                        <th>Purok / Sitio</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th class="text-right" style="width:10rem;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($puroks === []) : ?>
                      <tr><td colspan="4" class="text-center text-muted py-4">No Purok / Sitio yet. Add one to lock registration and surveys.</td></tr>
                      <?php else : ?>
                      <?php foreach ($puroks as $row) :
                          $pid = (string) ($row['purok_id'] ?? '');
                          $pname = (string) ($row['purok'] ?? '');
                          $pleader = (string) ($row['leader'] ?? '');
                          $isActive = (int) ($row['is_active'] ?? 1) === 1;
                          $usage = barangay_purok_usage_counts($con, $barangayId, $pid, $pname);
                          $referenced = $usage['total'] > 0;
                          ?>
                      <tr class="<?= $isActive ? '' : 'table-secondary' ?>">
                        <td>
                          <form method="post" action="saveManagePurok.php" class="form-inline">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="rename">
                            <input type="hidden" name="barangay_id" value="<?= barangay_h($barangayId) ?>">
                            <input type="hidden" name="purok_id" value="<?= barangay_h($pid) ?>">
                            <input type="text" class="form-control form-control-sm mr-2 mb-1" name="purok_name" value="<?= barangay_h($pname) ?>" maxlength="120" required style="min-width:12rem;">
                            <input type="text" class="form-control form-control-sm mr-2 mb-1" name="purok_leader" value="<?= barangay_h($pleader) ?>" maxlength="120" placeholder="Leader" style="min-width:8rem;">
                            <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Save"><i class="fas fa-save"></i></button>
                          </form>
                        </td>
                        <td class="align-middle small text-nowrap">
                          <span title="Residents"><?= (int) $usage['residents'] ?>R</span>
                          · <span title="Surveys"><?= (int) $usage['surveys'] ?>S</span>
                          · <span title="BNS assignments"><?= (int) $usage['bns'] ?>B</span>
                        </td>
                        <td class="align-middle">
                          <?php if ($isActive) : ?>
                          <span class="badge badge-success">Active</span>
                          <?php else : ?>
                          <span class="badge badge-secondary">Inactive</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-right align-middle text-nowrap">
                          <?php if ($isActive) : ?>
                          <form method="post" action="saveManagePurok.php" class="d-inline" onsubmit="return confirm('Deactivate this Purok / Sitio? History is kept.');">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="barangay_id" value="<?= barangay_h($barangayId) ?>">
                            <input type="hidden" name="purok_id" value="<?= barangay_h($pid) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate"><i class="fas fa-ban"></i></button>
                          </form>
                          <?php else : ?>
                          <form method="post" action="saveManagePurok.php" class="d-inline">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="barangay_id" value="<?= barangay_h($barangayId) ?>">
                            <input type="hidden" name="purok_id" value="<?= barangay_h($pid) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate"><i class="fas fa-check"></i></button>
                          </form>
                          <?php endif; ?>
                          <?php if (!$referenced) : ?>
                          <form method="post" action="saveManagePurok.php" class="d-inline" onsubmit="return confirm('Permanently delete this Purok / Sitio?');">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="barangay_id" value="<?= barangay_h($barangayId) ?>">
                            <input type="hidden" name="purok_id" value="<?= barangay_h($pid) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                          </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
    <?php
};

if ($useNutritionShell) {
    require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
    $nutritionPageIcon = 'fa-map-marker-alt';
    $nutritionPageHeading = 'Manage Purok / Sitio';
    $nutritionPageDescription = 'Official locked list for ' . $barangayName . '. Surveys and residence registration must pick from this list.';
    require __DIR__ . '/../includes/partials/nutrition_page_header.php';
    $renderBody();
    require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Purok / Sitio — <?= barangay_h($barangayName) ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode layout-top-nav">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand-md navbar-dark">
    <div class="container">
      <a href="dashboard.php" class="navbar-brand"><i class="fas fa-arrow-left mr-2"></i>Back</a>
      <span class="navbar-text text-white">Manage Purok / Sitio — <?= barangay_h($barangayName) ?></span>
    </div>
  </nav>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container">
        <h1 class="m-0">Manage Purok / Sitio</h1>
        <p class="text-muted mb-0">Official locked list used by residence registration and nutrition surveys.</p>
      </div>
    </div>
    <div class="content">
      <div class="container">
        <?php $renderBody(); ?>
      </div>
    </div>
  </div>
</div>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
</body>
</html>
