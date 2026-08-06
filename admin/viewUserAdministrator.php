<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';

try{

if(isset($_REQUEST['user_id'])){
  $user_id = $con->real_escape_string(trim($_REQUEST['user_id']));
  $user_type = 'resident';

  $sql = "SELECT * FROM users WHERE id = ? AND user_type != ?";
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->bind_param('ss',$user_id,$user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();


}


}catch(Exception $e){
  echo $e->getMessage();
}

?>

<style>
   #edit_display_image{
      height: 120px;
      width:auto;
      max-width:500px;
    }
</style>


<!-- Modal -->
<div class="modal fade" id="editUserAdministratorModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="editUserAdministratorForm" method="post" enctype="multipart/form-data" autocomplete="off">

        <div class="modal-header">
            <h5 class="modal-title">Administrator</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
          </div>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-12">
              <input type="hidden" value="<?= $row['id'] ?>" name="user_id">
            </div>
          <div class="col-sm-12 text-center">
              <?php 
              
              if($row['image'] != '' || $row['image'] != null || !empty($row['image'])){
                  echo  ' <img src="'.$row['image_path'].'" class="img-circle " alt="adminImage" id="edit_display_image">';
              }else{
                echo  ' <img src="../assets/dist/img/image.png" class="img-circle " alt="adminImage" id="edit_display_image">';
              }
              
              
              ?>
               
                <input type="file" id="edit_image" name="edit_image" style="display: none;">
              </div>
            <div class="col-sm-12">
               <div class="form-group">
                 <label>First Name</label>
                 <input type="text" name="edit_first_name" id="edit_first_name" class="form-control" value="<?= $row['first_name'] ?>">
                  <input type="hidden" id="edit_first_name_check" value="false">
               </div>
            </div>
            <div class="col-sm-12">
               <div class="form-group">
                 <label>Middle Name</label>
                 <input type="text" name="edit_middle_name" id="edit_middle_name" class="form-control" value="<?= $row['middle_name'] ?>">
                 <input type="hidden" id="edit_middle_name_check" value="false">
               </div>
            </div>
            <div class="col-sm-12">
               <div class="form-group">
                 <label>Last Name</label>
                 <input type="text" name="edit_last_name" id="edit_last_name" class="form-control" value="<?= $row['last_name'] ?>">
                 <input type="hidden" id="edit_last_name_check" value="false">
               </div>
            </div>
            <div class="col-sm-12">
               <div class="form-group">
                 <label>Username</label>
                 <input type="text" name="edit_username" id="edit_username" class="form-control" value="<?= $row['username'] ?>">
                 <input type="hidden" id="edit_username_check" value="false">
               </div>
            </div>
            <div class="col-sm-12">
               <div class="form-group">
                 <label>Password</label>
                 <input type="text" name="edit_password" id="edit_password" class="form-control" value="<?= $row['password'] ?>">
                 <input type="hidden" id="edit_password_check" value="false">
               </div>
            </div>
            <div class="col-sm-12">
               <div class="form-group">
                 <label>Contact Number</label>
                 <input type="number" name="edit_contact_number" maxlength="11" id="edit_contact_number" class="form-control" value="<?= $row['contact_number'] ?>">
                 <input type="hidden" id="edit_contact_number_check" value="false">
               </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary elevation-5 px-3 btn-flat" data-dismiss="modal"><i class="fas fa-times  "></i> CLOSE</button>
        <button type="submit" class="btn btn-success elevation-5 px-3 btn-flat"><i class="fas fa-save"></i> UPDATE</button>
      </div>

      </form>
    </div>
  </div>
</div>



<script>
  $(document).ready(function(){

    $(function () {

        var edit_first_name = $("#edit_first_name").val()
        var edit_middle_name = $("#edit_middle_name").val()
        var edit_last_name = $("#edit_last_name").val()
        var edit_username = $("#edit_username").val()
        var edit_password = $("#edit_password").val()
        var edit_contact_number = $("#edit_contact_number").val()


        $("#edit_first_name").change(function(){
          var newFirstName = $(this).val();

          if(!(newFirstName == edit_first_name)){
            $("#edit_first_name_check").val('true');
          }else{
            $("#edit_first_name_check").val('false');
          }
        })

        $("#edit_middle_name").change(function(){
          var newMiddleName = $(this).val();

          if(!(newMiddleName == edit_middle_name)){
            $("#edit_middle_name_check").val('true');
          }else{
            $("#edit_middle_name_check").val('false');
          }
        })

        $("#edit_last_name").change(function(){
          var newLastName = $(this).val();

          if(!(newLastName == edit_last_name)){
            $("#edit_last_name_check").val('true');
          }else{
            $("#edit_last_name_check").val('false');
          }
        })

        $("#edit_username").change(function(){
          var newusername = $(this).val();

          if(!(newusername == edit_username)){
            $("#edit_username_check").val('true');
          }else{
            $("#edit_username_check").val('false');
          }
        })

        $("#edit_password").change(function(){
          var newPassword = $(this).val();

          if(!(newPassword == edit_password)){
            $("#edit_password_check").val('true');
          }else{
            $("#edit_password_check").val('false');
          }
        })

        $("#edit_contact_number").change(function(){
          var newContactNumber = $(this).val();

          if(!(newContactNumber == edit_contact_number)){
            $("#edit_contact_number_check").val('true');
          }else{
            $("#edit_contact_number_check").val('false');
          }
        })

        $.validator.setDefaults({
          submitHandler: function (form) {
            Swal.fire({
              title: '<strong class="text-warning">Are you sure?</strong>',
              html: "<b>You want add this user?</b>",
              type: 'info',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, add it!',
              allowOutsideClick: false,
              width: '400px',
            }).then((result) => {
              if (result.value) {

                var formData = new FormData(form)
                formData.append("edit_first_name_check",$("#edit_first_name_check").val())
                formData.append("edit_middle_name_check",$("#edit_middle_name_check").val())
                formData.append("edit_last_name_check",$("#edit_last_name_check").val())
                formData.append("edit_username_check",$("#edit_username_check").val())
                formData.append("edit_password_check",$("#edit_password_check").val())
                formData.append("edit_contact_number_check",$("#edit_contact_number_check").val())
                
                  $.ajax({
                    url: 'editUserAdministrator.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    success:function(data){


                      if(data == 'error'){

                        Swal.fire({
                          title: '<strong class="text-danger">ERROR</strong>',
                          type: 'error',
                          html: '<b>Username is Already Exist<b>',
                          width: '400px',
                          confirmButtonColor: '#6610f2',
                        })
                      }else{
                        Swal.fire({
                          title: '<strong class="text-success">SUCCESS</strong>',
                          type: 'success',
                          html: '<b>Updated Admistator has Successfully<b>',
                          width: '400px',
                          confirmButtonColor: '#6610f2',
                          allowOutsideClick: false,
                          showConfirmButton: false,
                          timer: 2000,
                        }).then(()=>{
                          
                        
                          $("#userTableAdministrator").DataTable().ajax.reload();
                          
                      })
                    }

                     
                    }
                }).fail(barangayAjaxError)
              }
            })
            
          }
        });
      $('#editUserAdministratorForm').validate({
        rules: {
          edit_first_name: {
            required: true,
            minlength: 2
          },
          edit_last_name: {
            required: true,
            minlength: 2
          },
          edit_username: {
            required: true,
            minlength: 6
          },
          edit_password: {
            required: true,
            minlength: 6
          },
          edit_contact_number: {
            required: true,
            minlength: 11
          },
       
        },
        messages: {
          edit_first_name: {
            required: "<span class='text-danger text-bold'>First Name is Required</span>",
            minlength: "<span class='text-danger'>First Name must be at least 2 characters long</span>"
          },
          edit_last_name: {
            required: "<span class='text-danger text-bold'>Last Name is Required</span>",
            minlength: "<span class='text-danger'>Last Name must be at least 2 characters long</span>"
          },
          edit_username: {
            required: "<span class='text-danger text-bold'>Username is Required</span>",
            minlength: "<span class='text-danger'>Username must be at least 6 characters long</span>"
          },
          edit_password: {
            required: "<span class='text-danger text-bold'>Password is Required</span>",
            minlength: "<span class='text-danger'>Password must be at least 6 characters long</span>"
          },
          edit_contact_number: {
            required: "<span class='text-danger text-bold'>Contact Number is Required</span>",
            minlength: "<span class='text-danger'>Input Exact Contact Number</span>"
          
          },
  
        },
        tooltip_options: {
          '_all_': {
            placement: 'bottom',
            html:true,
          },
          
        },
      });
    })

    $('#edit_display_image').on('click',function(){
      $("#edit_image").click();
    })
    $("#edit_image").change(function(){
      DsiplayImage(this);
      })

      function DsiplayImage(input){
        if(input.files && input.files[0]){
          var reader = new FileReader();
          var image = $("#edit_image").val().split('.').pop().toLowerCase();

          if(image != ''){
            if(jQuery.inArray(image, ['gif','png','jpeg','jpg']) == -1){
              Swal.fire({
                title: '<strong class="text-danger">ERROR</strong>',
                type: 'error',
                html: '<b>Invalid Image File<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              $("#edit_image").val('');
           
              return false;
            }
          }
            reader.onload = function(e){
              $("#edit_display_image").attr('src', e.target.result);
              $("#edit_display_image").hide();
              $("#edit_display_image").fadeIn(650);
            }
            reader.readAsDataURL(input.files[0]);
        }
      }


  })
</script>

<script>
// Restricts input for each element in the set of matched elements to the given inputFilter.
(function($) {
  $.fn.inputFilter = function(inputFilter) {
    return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
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

 
$("#edit_contact_number").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });

  $("#edit_first_name, #edit_middle_name, #edit_last_name").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
 

</script>