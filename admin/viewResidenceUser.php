<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

$row = null;

try {
  if (isset($_REQUEST['residence_id'])) {
    $residence_id = trim((string) $_REQUEST['residence_id']);

    $sql = "SELECT residence_information.first_name,
      residence_information.middle_name,
      residence_information.last_name,
      residence_information.image,
      residence_information.image_path,
      users.username,
      users.password,
      users.contact_number,
      users.id
      FROM residence_information
      INNER JOIN users ON residence_information.residence_id = users.id
      WHERE users.id = ?";
    $stmt = $con->prepare($sql) or die($con->error);
    $stmt->bind_param('s', $residence_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: null;
  }
} catch (Exception $e) {
  echo $e->getMessage();
}

if ($row === null) {
  echo '<div class="p-3 text-danger">Resident user account not found.</div>';
  return;
}
?>

<!-- Modal -->
<div class="modal fade" id="displayUserModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="editUserForm" method="post">

        <div class="modal-header">
          <h5 class="modal-title">User Info</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-12 text-center">
                <?php
                $hasImage = trim((string) ($row['image'] ?? '')) !== '';
                if ($hasImage) {
                  echo '<img src="' . barangay_h((string) $row['image_path']) . '" alt="residence_image" class="img-circle" width="120" id="display_edit_image_residence">
                        <input type="file" id="edit_image_residence" name="edit_image_residence" style="display: none;">';
                } else {
                  echo '<img src="../assets/dist/img/blank_image.png" alt="residence_image" class="img-circle" width="120" id="display_edit_image_residence">
                        <input type="file" id="edit_image_residence" name="edit_image_residence" style="display: none;">';
                }
                ?>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" name="edit_first_name" id="edit_first_name" class="form-control" value="<?= barangay_h((string) ($row['first_name'] ?? '')) ?>">
                  <input type="hidden" id="edit_first_name_check" value="false">
                </div>
              </div>
              <input type="hidden" name="user_id" id="user_id" value="<?= barangay_h((string) ($row['id'] ?? '')) ?>">
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Middle Name</label>
                  <input type="text" name="edit_middle_name" id="edit_middle_name" class="form-control" value="<?= barangay_h((string) ($row['middle_name'] ?? '')) ?>">
                  <input type="hidden" id="edit_middle_name_check" value="false">
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" name="edit_last_name" id="edit_last_name" class="form-control" value="<?= barangay_h((string) ($row['last_name'] ?? '')) ?>">
                  <input type="hidden" id="edit_last_name_check" value="false">
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" name="edit_username" id="edit_username" class="form-control" value="<?= barangay_h((string) ($row['username'] ?? '')) ?>">
                  <input type="hidden" id="edit_username_check" value="false">
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" name="edit_password" id="edit_password" class="form-control" value="" placeholder="Leave blank to keep current password" autocomplete="new-password">
                  <input type="hidden" id="edit_password_check" value="false">
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <label>Contact Number</label>
                  <input type="text" maxlength="11" name="edit_contact_number" id="edit_contact_number" class="form-control" value="<?= barangay_h((string) ($row['contact_number'] ?? '')) ?>">
                  <input type="hidden" id="edit_contact_number_check" value="false">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn bg-black btn-flat elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
          <button type="submit" class="btn btn-success btn-flat elevation-5 px-3"><i class="fas fa-edit"></i> EDIT USER</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    var edit_first_name = $("#edit_first_name").val();
    var edit_middle_name = $("#edit_middle_name").val();
    var edit_last_name = $("#edit_last_name").val();
    var edit_username = $("#edit_username").val();
    var edit_password = $("#edit_password").val();
    var edit_contact_number = $("#edit_contact_number").val();

    $("#edit_first_name").change(function () {
      $("#edit_first_name_check").val($(this).val() == edit_first_name ? 'false' : 'true');
    });
    $("#edit_middle_name").change(function () {
      $("#edit_middle_name_check").val($(this).val() == edit_middle_name ? 'false' : 'true');
    });
    $("#edit_last_name").change(function () {
      $("#edit_last_name_check").val($(this).val() == edit_last_name ? 'false' : 'true');
    });
    $("#edit_username").change(function () {
      $("#edit_username_check").val($(this).val() == edit_username ? 'false' : 'true');
    });
    $("#edit_password").on('input change', function () {
      var val = $.trim($(this).val() || '');
      $("#edit_password_check").val(val === '' ? 'false' : 'true');
    });
    $("#edit_contact_number").change(function () {
      $("#edit_contact_number_check").val($(this).val() == edit_contact_number ? 'false' : 'true');
    });

    $.validator.setDefaults({
      submitHandler: function (form) {
        Swal.fire({
          title: '<strong class="text-warning">Are you sure?</strong>',
          html: "<b>You want edit this user?</b>",
          type: 'info',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, edit it!',
          allowOutsideClick: false,
          width: '400px',
        }).then((result) => {
          if (result.value) {
            $.ajax({
              url: 'editUserResidence.php',
              type: 'POST',
              data: $(form).serialize()
                + "&edit_first_name_check=" + $("#edit_first_name_check").val()
                + "&edit_middle_name_check=" + $("#edit_middle_name_check").val()
                + "&edit_last_name_check=" + $("#edit_last_name_check").val()
                + "&edit_username_check=" + $("#edit_username_check").val()
                + "&edit_password_check=" + $("#edit_password_check").val()
                + "&edit_contact_number_check=" + $("#edit_contact_number_check").val(),
              cache: false,
              success: function (data) {
                if (data == 'error') {
                  Swal.fire({
                    title: '<strong class="text-danger">ERROR</strong>',
                    type: 'error',
                    html: '<b>Username is Already Taken<b>',
                    width: '400px',
                    confirmButtonColor: '#6610f2',
                    allowOutsideClick: false,
                  });
                } else {
                  Swal.fire({
                    title: '<strong class="text-success">SUCCESS</strong>',
                    type: 'success',
                    html: '<b>Updated User has Successfully<b>',
                    width: '400px',
                    confirmButtonColor: '#6610f2',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    timer: 2000,
                  });
                  $("#userTableResidence").DataTable().ajax.reload();
                }
              }
            }).fail(barangayAjaxError);
          }
        });
      }
    });

    $('#editUserForm').validate({
      rules: {
        edit_first_name: { required: true, minlength: 2 },
        edit_last_name: { required: true, minlength: 2 },
        edit_username: { required: true, minlength: 6 },
        edit_password: { required: false, minlength: 6 },
        edit_contact_number: { required: true, minlength: 11 },
      },
      messages: {
        edit_first_name: {
          required: "Please provide a First Name",
          minlength: "First Name must be at least 2 characters long"
        },
        edit_last_name: {
          required: "Please provide a Last Name",
          minlength: "Last Name must be at least 2 characters long"
        },
        edit_username: {
          required: "Please provide a Username",
          minlength: "Username must be at least 6 characters long"
        },
        edit_password: {
          minlength: "Password must be at least 6 characters long"
        },
        edit_contact_number: {
          required: "Please provide a Contact Number",
          minlength: "Input Exact Contact Number"
        },
      },
      errorElement: 'span',
      errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
        element.closest('.form-group-sm').append(error);
      },
      highlight: function (element) {
        $(element).addClass('is-invalid');
      },
      unhighlight: function (element) {
        $(element).removeClass('is-invalid');
      }
    });
  });
</script>

<script>
(function ($) {
  $.fn.inputFilter = function (inputFilter) {
    return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
      if (inputFilter(this.value)) {
        this.oldValue = this.value;
        this.oldSelectionStart = this.selectionStart;
        this.oldSelectionEnd = this.selectionEnd;
      } else if (this.hasOwnProperty("oldValue")) {
        this.value = this.oldValue;
        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
      } else {
        this.value = "";
      }
    });
  };
}(jQuery));

$("#edit_contact_number").inputFilter(function (value) {
  return /^-?\d*$/.test(value);
});

$("#edit_first_name, #edit_middle_name, #edit_last_name").inputFilter(function (value) {
  return /^[a-z, ]*$/i.test(value);
});
</script>
