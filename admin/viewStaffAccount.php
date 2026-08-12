<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_accounts.php';

$userId = trim((string) ($_REQUEST['user_id'] ?? ''));
$row = $userId !== '' ? staff_account_load($con, $userId) : null;

if (!$row || !staff_account_can_manage($con, $row, 'edit')) {
    http_response_code(403);
    exit('Access denied.');
}

$role = staff_account_resolve_role($row);
$canChangePassword = true;
$assignableRoles = staff_account_assignable_roles_on_edit($con);
$canChangeRole = $assignableRoles !== [];
$isSelf = isset($_SESSION['user_id']) && (string) $_SESSION['user_id'] === (string) $row['id'];
$barangayOptions = barangay_list_all($con);
$assignedBarangayIds = $role === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR
    ? staff_assigned_barangay_ids($con, (string) $row['id'])
    : [];
$cityWideRoles = [
    STAFF_ROLE_SSA,
    STAFF_ROLE_SUPER_ADMIN,
    STAFF_ROLE_NUTRITION_SUPER_ADMIN,
    STAFF_ROLE_ADMIN,
    STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
];
$needsBarangay = staff_role_requires_barangay($role);
$isCnpc = $role === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR;
$barangayNameById = [];
foreach ($barangayOptions as $brgy) {
    $barangayNameById[(string) $brgy['id']] = (string) $brgy['barangay'];
}
$cnpcAssignedLabels = [];
foreach ($assignedBarangayIds as $assignedId) {
    $cnpcAssignedLabels[] = $barangayNameById[(string) $assignedId] ?? (string) $assignedId;
}
?>

<div class="modal fade" id="editStaffAccountModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="editStaffAccountForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Edit <?= barangay_h(staff_account_role_label($role)) ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" value="<?= barangay_h($row['id']) ?>">
          <div class="form-group">
            <label>Role</label>
            <?php if ($canChangeRole && !$isSelf) : ?>
            <select name="staff_role" id="edit_staff_role" class="form-control" required
                    data-requires-barangay="<?= htmlspecialchars(json_encode([
                        STAFF_ROLE_BARANGAY_ADMIN,
                        STAFF_ROLE_BARANGAY_STAFF,
                        STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
                    ]), ENT_QUOTES, 'UTF-8') ?>"
                    data-cnpc-role="<?= barangay_h(STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) ?>">
              <?php foreach ($assignableRoles as $optionRole) : ?>
              <option value="<?= barangay_h($optionRole) ?>" <?= $optionRole === $role ? 'selected' : '' ?>>
                <?= barangay_h(staff_account_role_label($optionRole)) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Super Super Admin can change this account to any role.</small>
            <?php else : ?>
            <input type="text" class="form-control" value="<?= barangay_h(staff_account_role_label($role)) ?>" readonly>
            <input type="hidden" name="staff_role" value="<?= barangay_h($role) ?>">
            <?php if ($isSelf && $canChangeRole) : ?>
            <small class="form-text text-warning">You cannot change your own role.</small>
            <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="form-group" id="edit_barangay_readonly_group" style="<?= ($canChangeRole && !$isSelf) ? 'display:none;' : '' ?>">
            <?php if (in_array($role, $cityWideRoles, true)) : ?>
            <label>Barangay</label>
            <input type="text" class="form-control" value="All Barangays / City-wide" readonly>
            <?php elseif ($isCnpc) : ?>
            <label>Assigned barangays</label>
            <input type="text" class="form-control" value="<?= barangay_h($cnpcAssignedLabels !== [] ? implode(', ', $cnpcAssignedLabels) : 'None assigned') ?>" readonly>
            <?php elseif ($needsBarangay) : ?>
            <label>Barangay</label>
            <input type="text" class="form-control" value="<?= barangay_h((string) ($row['barangay_name'] ?? '')) ?>" readonly>
            <?php endif; ?>
          </div>

          <?php if ($canChangeRole && !$isSelf) : ?>
          <div class="form-group" id="edit_barangay_group" style="<?= $needsBarangay ? '' : 'display:none;' ?>">
            <label>Barangay <small class="text-muted">(required for Barangay Admin / Staff / BNS)</small></label>
            <select name="barangay_id" id="edit_barangay_id" class="form-control">
              <option value="">Select barangay</option>
              <?php foreach ($barangayOptions as $brgy) : ?>
              <option value="<?= barangay_h($brgy['id']) ?>" <?= (string) ($row['barangay_id'] ?? '') === (string) $brgy['id'] ? 'selected' : '' ?>>
                <?= barangay_h($brgy['barangay']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" id="edit_cnpc_barangay_group" style="<?= $isCnpc ? '' : 'display:none;' ?>">
            <label>Assigned barangays <small class="text-muted">(CNPC — select one or many)</small></label>
            <div class="cnpc-barangay-checklist border rounded p-2" style="max-height: 220px; overflow-y: auto;">
              <?php foreach ($barangayOptions as $brgy) :
                  $cid = 'edit_cnpc_brgy_' . preg_replace('/\W+/', '_', (string) $brgy['id']);
                  $checked = in_array((string) $brgy['id'], array_map('strval', $assignedBarangayIds), true);
                  ?>
              <div class="custom-control custom-checkbox">
                <input type="checkbox"
                       class="custom-control-input edit-cnpc-barangay-check"
                       name="barangay_ids[]"
                       id="<?= barangay_h($cid) ?>"
                       value="<?= barangay_h($brgy['id']) ?>"
                       <?= $checked ? 'checked' : '' ?>>
                <label class="custom-control-label" for="<?= barangay_h($cid) ?>"><?= barangay_h($brgy['barangay']) ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?= barangay_h($row['first_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middle_name" class="form-control" value="<?= barangay_h($row['middle_name']) ?>">
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?= barangay_h($row['last_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= barangay_h($row['username']) ?>" required minlength="4">
          </div>
          <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact_number" class="form-control staff-contact" maxlength="11" value="<?= barangay_h($row['contact_number']) ?>" required>
          </div>
          <?php if ($canChangePassword): ?>
          <div class="form-group">
            <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
            <input type="password" name="password" id="edit_staff_password" class="form-control" minlength="6" autocomplete="new-password">
            <input type="hidden" name="password_changed" id="edit_staff_password_changed" value="0">
          </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(function () {
  function syncEditRoleFields() {
    var $role = $('#edit_staff_role');
    if (!$role.length) {
      return;
    }
    var role = $role.val() || '';
    var cnpc = String($role.data('cnpc-role') || '');
    var needs = [];
    try {
      needs = JSON.parse(String($role.attr('data-requires-barangay') || '[]'));
    } catch (e) {
      needs = [];
    }
    var isCnpc = role === cnpc;
    var needsBrgy = needs.indexOf(role) !== -1;
    $('#edit_barangay_group').toggle(needsBrgy);
    $('#edit_cnpc_barangay_group').toggle(isCnpc);
    $('#edit_barangay_readonly_group').hide();
    if (needsBrgy) {
      $('#edit_barangay_id').prop('required', true);
    } else {
      $('#edit_barangay_id').prop('required', false);
    }
  }

  $('#edit_staff_role').on('change', syncEditRoleFields);
  syncEditRoleFields();

  $('#edit_staff_password').on('input', function () {
    $('#edit_staff_password_changed').val($(this).val() !== '' ? '1' : '0');
  });

  $('#editStaffAccountForm').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
      url: 'saveStaffAccount.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function (data) {
        if ((data || '').toString().trim() !== 'success') {
          Swal.fire({ icon: 'error', title: 'Error', text: (data || 'Unable to save.').toString().trim(), confirmButtonColor: '#6610f2' });
          return;
        }
        Swal.fire({ icon: 'success', title: 'Saved', timer: 1500, showConfirmButton: false }).then(function () {
          $('#editStaffAccountModal').modal('hide');
          $('#staffAccountsTable').DataTable().ajax.reload();
        });
      }
    }).fail(barangayAjaxError);
  });

  $('.staff-contact').inputFilter(function (value) {
    return /^-?\d*$/.test(value);
  });
});
</script>
