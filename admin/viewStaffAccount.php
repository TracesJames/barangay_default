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
            <input type="text" class="form-control" value="<?= barangay_h(staff_account_role_label($role)) ?>" readonly>
          </div>
          <?php if ($role !== STAFF_ROLE_SUPER_ADMIN && $role !== STAFF_ROLE_ADMIN && $role !== STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN): ?>
          <div class="form-group">
            <label>Barangay</label>
            <input type="text" class="form-control" value="<?= barangay_h((string) ($row['barangay_name'] ?? '')) ?>" readonly>
          </div>
          <?php elseif ($role === STAFF_ROLE_ADMIN || $role === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN): ?>
          <div class="form-group">
            <label>Barangay</label>
            <input type="text" class="form-control" value="All Barangays" readonly>
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
