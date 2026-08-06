<?php

include_once 'connection.php';
require_once 'includes/helpers.php';
barangay_start_session();
require_once 'includes/csrf.php';
require_once 'includes/password_reset.php';

barangay_require_post();
csrf_verify();

header('Content-Type: text/html; charset=utf-8');

$username = trim((string) ($_POST['username'] ?? ''));
$contact = trim((string) ($_POST['contact_number'] ?? ''));
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

// Step 1: username only → ask for full contact (do not reveal if user exists)
if ($contact === '') {
    if ($username === '') {
        echo '<h5 class="text-center">Enter your username to continue.</h5>';
        exit;
    }
    ?>
<div class="modal fade" id="recoverModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="recoverRequestTokenForm" method="post">
        <?= csrf_field(); ?>
        <div class="modal-body">
          <input type="hidden" name="username" value="<?= barangay_h($username) ?>">
          <p class="mb-3">Enter the <strong>full contact number</strong> on your account. If it matches, a one-time reset code will be created (valid 15 minutes).</p>
          <div class="form-group">
            <label for="contact_number_full">Contact number</label>
            <input type="text" name="contact_number" id="contact_number_full" class="form-control" maxlength="15" placeholder="09XXXXXXXXX" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn bg-black elevation-5 px-3 btn-flat" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
          <button type="submit" class="btn btn-primary btn-flat elevation-5 px-3"><i class="fas fa-key"></i> Request code</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  $('#recoverRequestTokenForm').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
      url: 'recoverAccount.php',
      type: 'POST',
      data: $(this).serialize(),
      cache: false,
      success: function (data) {
        $('#show_number').html(data);
        $('#recoverModal').modal('show');
      }
    }).fail(typeof barangayAjaxError === 'function' ? barangayAjaxError : undefined);
  });
})();
</script>
    <?php
    exit;
}

$result = barangay_password_reset_issue_token($con, $username, $contact, $ip);

if (!$result['ok']) {
    $msg = ($result['message'] ?? '') === 'rate_limited'
        ? 'Too many reset attempts. Try again in 15 minutes.'
        : 'Unable to verify account. Check username and full contact number.';
    echo '<div class="modal fade" id="recoverModal" data-backdrop="static" data-keyboard="false" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><h5 class="text-center text-danger">' . barangay_h($msg) . '</h5></div><div class="modal-footer"><button type="button" class="btn bg-black" data-dismiss="modal">CLOSE</button></div></div></div></div>';
    exit;
}

$token = (string) ($result['token'] ?? '');
$safeUser = (string) ($result['username'] ?? $username);
?>
<div class="modal fade" id="recoverModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="recoverPasswordForm" method="post">
        <?= csrf_field(); ?>
        <div class="modal-body">
          <div class="container-fluid">
            <input type="hidden" name="check_username" id="check_username" value="<?= barangay_h($safeUser) ?>">
            <input type="hidden" name="reset_token" id="reset_token" value="<?= barangay_h($token) ?>">
            <div class="alert alert-info">
              One-time reset code issued. It expires in <strong>15 minutes</strong> and can be used once.
              Keep this page open until you save your new password.
            </div>
            <div class="form-group">
              <label>Reset code (auto-filled)</label>
              <input type="text" class="form-control" value="<?= barangay_h($token) ?>" readonly>
            </div>
            <div class="form-group">
              <div class="input-group mb-3" id="show_hide_password">
                <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span></div>
                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="NEW PASSWORD (min 8)" style="border-right:none;" required minlength="8">
                <div class="input-group-append"><span class="input-group-text bg-transparent"><a href="#" style="text-decoration:none;"><i class="fas fa-eye-slash"></i></a></span></div>
              </div>
            </div>
            <div class="form-group">
              <div class="input-group mb-3" id="show_hide_password_confirm">
                <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-key"></i></span></div>
                <input type="password" id="new_confirm_password" name="new_confirm_password" class="form-control" placeholder="CONFIRM PASSWORD" style="border-right:none;" required minlength="8">
                <div class="input-group-append"><span class="input-group-text bg-transparent"><a href="#" style="text-decoration:none;"><i class="fas fa-eye-slash"></i></a></span></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn bg-black elevation-5 px-3 btn-flat" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
          <button type="submit" class="btn btn-primary btn-flat elevation-5 px-3"><i class="fas fa-save"></i> SAVE</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
$(function () {
  $('#recoverPasswordForm').on('submit', function (e) {
    e.preventDefault();
    var $form = $(this);
    var pass = $('#new_password').val();
    var confirm = $('#new_confirm_password').val();
    if (pass.length < 8 || pass !== confirm) {
      Swal.fire({ title: 'Error', type: 'error', html: '<b>Passwords must match and be at least 8 characters.</b>' });
      return;
    }
    Swal.fire({
      title: 'Save new password?',
      type: 'info',
      showCancelButton: true,
      confirmButtonText: 'Yes, save it!',
      allowOutsideClick: false
    }).then(function (result) {
      if (!result.value) return;
      $.ajax({
        url: 'recoverNewPassword.php',
        type: 'POST',
        data: $form.serialize(),
        cache: false,
        success: function (data) {
          if (data === 'error' || data === 'token') {
            Swal.fire({ title: 'Error', type: 'error', html: '<b>Reset code invalid or expired. Request a new one.</b>' });
          } else if (data === 'error1') {
            Swal.fire({ title: 'Error', type: 'error', html: '<b>Passwords do not match.</b>' });
          } else {
            Swal.fire({
              title: 'Success',
              type: 'success',
              html: '<b>Password updated.</b>',
              timer: 2000,
              showConfirmButton: false,
              allowOutsideClick: false
            }).then(function () { window.location.href = 'login.php'; });
          }
        }
      }).fail(typeof barangayAjaxError === 'function' ? barangayAjaxError : undefined);
    });
  });

  $('#show_hide_password a, #show_hide_password_confirm a').on('click', function (event) {
    event.preventDefault();
    var wrap = $(this).closest('.input-group');
    var input = wrap.find('input');
    var icon = $(this).find('i');
    if (input.attr('type') === 'password') {
      input.attr('type', 'text');
      icon.removeClass('fa-eye-slash').addClass('fa-eye');
    } else {
      input.attr('type', 'password');
      icon.removeClass('fa-eye').addClass('fa-eye-slash');
    }
  });
});
</script>
