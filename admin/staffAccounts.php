<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_accounts.php';

staff_accounts_ensure_schema($con);

$user_id = $_SESSION['user_id'];
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, (string) $user_id);
barangay_require_staff_account_manager(
    $con,
    $isNutritionPortalAdmin ? 'nutritionSuperDashboard.php' : 'dashboard.php'
);

$isSsa = barangay_user_is_ssa($con, (string) $user_id);
$isBarangayHubSa = barangay_user_is_barangay_hub_super_admin($con, (string) $user_id);
$stmt_user = $con->prepare('SELECT first_name, last_name, user_type, image, image_path FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'] ?? '';
$last_name_user = $row_user['last_name'] ?? '';
$user_type = $row_user['user_type'] ?? 'admin';
$user_image = $row_user['image'] ?? '';
$user_image_path = $row_user['image_path'] ?? '';
$isSuperAdmin = staff_account_actor_is_super_admin($con) || $isSsa;
$staffRole = barangay_user_staff_role($con, (string) $user_id);
$staffRoleLabel = staff_role_label($staffRole);
$actorBarangayId = staff_account_actor_barangay_id($con);
$creatableRoles = staff_account_creatable_roles($con);
$barangayOptions = barangay_list_all($con);
$defaultBarangayId = barangay_session_id() ?? '';
$activeRoleFilter = trim((string) ($_GET['role'] ?? ''));
$canAssignBarangay = $isSsa || $isBarangayHubSa || $isNutritionPortalAdmin;

if ($isNutritionPortalAdmin) {
    $allowedNutritionRoles = [
        STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
        STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
    ];
    $creatableRoles = array_values(array_intersect($creatableRoles, $allowedNutritionRoles));
    if ($activeRoleFilter === '' || !in_array($activeRoleFilter, $allowedNutritionRoles, true)) {
        header('Location: staffAccounts.php?role=' . urlencode(STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR));
        exit;
    }
} elseif ($isBarangayHubSa && !$isSsa) {
    $allowedBarangayRoles = [
        STAFF_ROLE_SUPER_ADMIN,
        STAFF_ROLE_ADMIN,
        STAFF_ROLE_BARANGAY_ADMIN,
        STAFF_ROLE_BARANGAY_STAFF,
    ];
    if ($activeRoleFilter !== '' && !in_array($activeRoleFilter, $allowedBarangayRoles, true)) {
        header('Location: staffAccounts.php');
        exit;
    }
}

$isBnsFilter = $activeRoleFilter === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR;
$isBnsAdminFilter = $activeRoleFilter === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN;
$useNutritionSidebar = $isBnsFilter || $isBnsAdminFilter || $isNutritionPortalAdmin
    || $activeRoleFilter === STAFF_ROLE_NUTRITION_SUPER_ADMIN;
$activePage = $isBnsFilter ? 'bns' : ($isBnsAdminFilter ? 'bns_admin' : 'staff_accounts');
$brandLogo = $sidebarLogo ?? barangay_default_logo_url('../');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Staff Accounts | <?= $useNutritionSidebar ? 'Nutrition Portal' : 'City of Valencia Portal' ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/super-dashboard.css?v=20260720b">
<?php if ($useNutritionSidebar) : ?>
<?php endif; ?>
<?php require_once '../includes/head_csrf.php'; ?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260805n">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal <?= $useNutritionSidebar ? 'nutrition-portal nutrition-super-dashboard' : 'barangay-dashboard barangay-super-dashboard' ?>">
<div class="wrapper">
  <div class="preloader flex-column justify-content-center align-items-center">
    <i class="fas fa-spinner fa-spin fa-2x text-white" aria-hidden="true"></i>
  </div>

  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link text-white" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><h5 class="nav-link text-white mb-0"><?= $useNutritionSidebar ? 'Nutrition Portal' : 'City of Valencia Portal' ?> · Staff Accounts</h5></li>
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

  <?php if ($useNutritionSidebar) : ?>
    <?php require __DIR__ . '/../includes/partials/super_nutrition_sidebar.php'; ?>
  <?php else : ?>
    <?php require __DIR__ . '/../includes/partials/super_admin_sidebar.php'; ?>
  <?php endif; ?>

  <div class="content-wrapper">
    <section class="content mt-3">
      <div class="container-fluid">
        <div class="card dashboard-panel">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i> Account Management<?= $isNutritionPortalAdmin ? ' · Nutrition Hub' : '' ?></h3>
            <?php if ($creatableRoles !== []): ?>
            <div class="card-tools">
              <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createStaffAccountModal">
                <i class="fas fa-user-plus"></i> New Account
              </button>
            </div>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="mb-1">Filter by Role</label>
                <select id="role_filter" class="form-control">
                  <option value="">All Roles</option>
                  <?php if ($isSsa) : ?>
                  <optgroup label="System · Both Hubs">
                    <option value="<?= STAFF_ROLE_SSA ?>" <?= $activeRoleFilter === STAFF_ROLE_SSA ? 'selected' : '' ?>>Super Super Admin (SSA)</option>
                  </optgroup>
                  <?php endif; ?>
                  <?php if ($isSsa || $isBarangayHubSa) : ?>
                  <optgroup label="Barangay Hub">
                    <?php if ($isSsa) : ?>
                    <option value="<?= STAFF_ROLE_SUPER_ADMIN ?>" <?= $activeRoleFilter === STAFF_ROLE_SUPER_ADMIN ? 'selected' : '' ?>>Super Admin (SA)</option>
                    <?php endif; ?>
                    <option value="<?= STAFF_ROLE_ADMIN ?>" <?= $activeRoleFilter === STAFF_ROLE_ADMIN ? 'selected' : '' ?>>Admin (A)</option>
                    <option value="<?= STAFF_ROLE_BARANGAY_ADMIN ?>" <?= $activeRoleFilter === STAFF_ROLE_BARANGAY_ADMIN ? 'selected' : '' ?>>Barangay Admin</option>
                    <option value="<?= STAFF_ROLE_BARANGAY_STAFF ?>" <?= $activeRoleFilter === STAFF_ROLE_BARANGAY_STAFF ? 'selected' : '' ?>>Barangay Staff</option>
                  </optgroup>
                  <?php endif; ?>
                  <?php if ($isSsa || $isNutritionPortalAdmin) : ?>
                  <optgroup label="Nutrition Hub">
                    <?php if ($isSsa) : ?>
                    <option value="<?= STAFF_ROLE_NUTRITION_SUPER_ADMIN ?>" <?= $activeRoleFilter === STAFF_ROLE_NUTRITION_SUPER_ADMIN ? 'selected' : '' ?>>Super Admin (SA)</option>
                    <?php endif; ?>
                    <option value="<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN ?>" <?= $activeRoleFilter === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN ? 'selected' : '' ?>>Admin (A)</option>
                    <option value="<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR ?>" <?= $activeRoleFilter === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR ? 'selected' : '' ?>>BNS (per Barangay)</option>
                  </optgroup>
                  <?php endif; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="mb-1">Filter by Barangay</label>
                <select id="barangay_filter" class="form-control">
                  <option value="">All Barangays</option>
                  <?php foreach ($barangayOptions as $brgy): ?>
                  <option value="<?= barangay_h($brgy['id']) ?>" <?= $defaultBarangayId === (string) $brgy['id'] ? 'selected' : '' ?>><?= barangay_h($brgy['barangay']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <fieldset>
              <legend>Staff Accounts <span id="total"></span></legend>
              <table class="table table-striped table-hover" id="staffAccountsTable" style="width:100%;">
                <thead class="bg-indigo">
                  <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Barangay</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
              </table>
            </fieldset>
          </div>
        </div>

        <div class="card dashboard-panel">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Roles</h3>
          </div>
          <div class="card-body">
            <ul class="mb-0 pl-3">
              <?php if ($isNutritionPortalAdmin) : ?>
              <li><strong>Admin — Nutrition Hub (A)</strong> — city-wide Nutrition Hub admin (pick any barangay).</li>
              <li><strong>BNS (per Barangay)</strong> — one Nutrition Scholar account for a single barangay. Choose the barangay when creating.</li>
              <?php else : ?>
              <li><strong>Super Super Admin (SSA)</strong> — sees all of Barangay Hub and Nutrition Hub; full system access; manage all accounts.</li>
              <li><strong>Super Admin — Barangay Hub (SA)</strong> — Barangay Hub only; manage Barangay Hub Admin, Barangay Admin, and Staff accounts.</li>
              <li><strong>Super Admin — Nutrition Hub (SA)</strong> — Nutrition Hub only; manage Nutrition Hub Admin and BNS accounts.</li>
              <li><strong>Admin — Barangay Hub (A)</strong> — Barangay Hub operations (officials, residents, blotter, reports). No Nutrition Hub, certificates issue, backup, or account deletion.</li>
              <li><strong>Admin — Nutrition Hub (A)</strong> — Nutrition Hub city-wide (pick any barangay, surveys, settings, MELLPI). No Barangay Hub portal.</li>
              <li><strong>BNS (per Barangay)</strong> — one Nutrition Scholar account scoped to a single barangay.</li>
              <li><strong>Barangay Admin / Staff</strong> — one barangay only on the Barangay Hub portal.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong>Copyright &copy; <?= date('Y') ?></strong>
  </footer>
</div>

<div id="staffAccountModalHost"></div>

<?php if ($creatableRoles !== []): ?>
<div class="modal fade" id="createStaffAccountModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="createStaffAccountForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title"><?= $isNutritionPortalAdmin ? 'New Nutrition Hub Account' : 'New Staff Account' ?></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Role</label>
            <select name="staff_role" id="create_staff_role" class="form-control" required>
              <option value="">Select role</option>
              <?php foreach ($creatableRoles as $role): ?>
              <option value="<?= barangay_h($role) ?>" <?= $activeRoleFilter === $role ? 'selected' : '' ?>><?= barangay_h(staff_account_role_label($role)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($canAssignBarangay): ?>
          <div class="form-group" id="create_barangay_group">
            <label>Barangay <small class="text-muted">(required for BNS)</small></label>
            <select name="barangay_id" id="create_barangay_id" class="form-control">
              <option value="">Select barangay</option>
              <?php foreach ($barangayOptions as $brgy): ?>
              <option value="<?= barangay_h($brgy['id']) ?>"><?= barangay_h($brgy['barangay']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php else: ?>
          <input type="hidden" name="barangay_id" value="<?= barangay_h($actorBarangayId ?? '') ?>">
          <?php endif; ?>
          <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middle_name" class="form-control">
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required minlength="4">
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password">
          </div>
          <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact_number" class="form-control staff-contact" maxlength="11" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>

<script>
(function ($) {
  $.fn.inputFilter = function (inputFilter) {
    return this.on('input keydown keyup mousedown mouseup select contextmenu drop', function () {
      if (inputFilter(this.value)) {
        this.oldValue = this.value;
        this.oldSelectionStart = this.selectionStart;
        this.oldSelectionEnd = this.selectionEnd;
      } else if (this.hasOwnProperty('oldValue')) {
        this.value = this.oldValue;
        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
      } else {
        this.value = '';
      }
    });
  };
})(jQuery);

$(function () {
  $('.staff-contact').inputFilter(function (value) {
    return /^-?\d*$/.test(value);
  });

  $('#create_staff_role').on('change', function () {
    var role = $(this).val();
    var needsBarangay = role === '<?= STAFF_ROLE_BARANGAY_STAFF ?>'
      || role === '<?= STAFF_ROLE_BARANGAY_ADMIN ?>'
      || role === '<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR ?>';
    $('#create_barangay_group').toggle(needsBarangay);
    $('#create_barangay_id').prop('required', needsBarangay);
  }).trigger('change');

  function staffAccountsTable() {
    return $('#staffAccountsTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: 'staffAccountsTable.php',
        type: 'POST',
        data: function (d) {
          d.role_filter = $('#role_filter').val();
          d.barangay_filter = $('#barangay_filter').length ? $('#barangay_filter').val() : '';
        }
      },
      order: [[1, 'asc']],
      columns: [
        { orderable: false, searchable: false },
        null, null, null, null,
        { orderable: false, searchable: false, className: 'text-center' }
      ],
      drawCallback: function (settings) {
        var json = settings.json || {};
        $('#total').text('(' + (json.total || '0') + ')');
      }
    });
  }

  var table = staffAccountsTable();

  $('#role_filter, #barangay_filter').on('change', function () {
    table.ajax.reload();
  });

  $('#createStaffAccountForm').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
      url: 'saveStaffAccount.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function (data) {
        if ((data || '').toString().trim() !== 'success') {
          Swal.fire({ icon: 'error', title: 'Error', text: (data || 'Unable to create account.').toString().trim(), confirmButtonColor: '#6610f2' });
          return;
        }
        Swal.fire({ icon: 'success', title: 'Account created', timer: 1500, showConfirmButton: false }).then(function () {
          $('#createStaffAccountModal').modal('hide');
          $('#createStaffAccountForm')[0].reset();
          table.ajax.reload();
        });
      }
    }).fail(barangayAjaxError);
  });

  $(document).on('click', '.viewStaffAccount', function () {
    var userId = $(this).data('id');
    $.get('viewStaffAccount.php', { user_id: userId }, function (html) {
      $('#staffAccountModalHost').html(html);
      $('#editStaffAccountModal').modal('show');
    }).fail(barangayAjaxError);
  });

  $(document).on('click', '.resetStaffPassword', function () {
    var userId = $(this).data('id');
    Swal.fire({
      title: 'Reset Password',
      input: 'password',
      inputLabel: 'New password (min 6 characters)',
      inputAttributes: { minlength: 6, autocapitalize: 'off', autocorrect: 'off' },
      showCancelButton: true,
      confirmButtonColor: '#6610f2'
    }).then(function (result) {
      if (!result.value) return;
      $.post('resetStaffAccountPassword.php', { user_id: userId, password: result.value }, function (data) {
        if ((data || '').toString().trim() !== 'success') {
          Swal.fire({ icon: 'error', title: 'Error', text: (data || '').toString().trim(), confirmButtonColor: '#6610f2' });
          return;
        }
        Swal.fire({ icon: 'success', title: 'Password reset', timer: 1500, showConfirmButton: false });
      }).fail(barangayAjaxError);
    });
  });

  $(document).on('click', '.deleteStaffAccount', function () {
    var userId = $(this).data('id');
    Swal.fire({
      title: 'Delete account?',
      text: 'This cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Yes, delete'
    }).then(function (result) {
      if (!result.value) return;
      $.post('deleteStaffAccount.php', { user_id: userId }, function (data) {
        if ((data || '').toString().trim() !== 'success') {
          Swal.fire({ icon: 'error', title: 'Error', text: (data || '').toString().trim(), confirmButtonColor: '#6610f2' });
          return;
        }
        Swal.fire({ icon: 'success', title: 'Deleted', timer: 1500, showConfirmButton: false });
        table.ajax.reload();
      }).fail(barangayAjaxError);
    });
  });
});
</script>
</body>
</html>
