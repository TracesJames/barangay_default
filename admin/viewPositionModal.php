<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';


try{

  if(isset($_REQUEST['position_id'])){
  
      $position_id = $con->real_escape_string(trim($_REQUEST['position_id']));
      $sql_view_position = "SELECT * FROM position WHERE position_id = ?";
      $stmt_view_position = $con->prepare($sql_view_position) or die ($con->error);
      $stmt_view_position->bind_param('s',$position_id);
      $stmt_view_position->execute();
      $position_view = $stmt_view_position->get_result();
      $row_view_position = $position_view->fetch_assoc();

  }



}catch(Exception $e){
  echo $e->getMessage();
}



?>


<div class="modal fade" id="viewPositionModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    <form id="editPositionForm" method="post">  
      <div class="modal-body">
        <div class="container-fluid">
         <div class="row">
           <div class="col-sm-12">
             <input type="hidden" name="edit_position_id" value="<?= $row_view_position['position_id'] ?>" >
           </div>
           <div class="col-sm-12">
             <div class="form-group">
               <label>Position</label>
               <input type="text" name="edit_position" id="edit_position" class="form-control text-uppercase" value="<?= $row_view_position['position'] ?>">
               
             </div>
           </div>
           <div class="col-sm-12">
             <div class="form-group">
               <label>Limit</label>
               <input type="text"   maxlength="2" name="edit_limit" id="edit_limit" class="form-control" value="<?= $row_view_position['position_limit'] ?>">
             </div>
           </div>
           <div class="col-sm-12">
             <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="edit_description" id="edit_description" rows="3"><?= $row_view_position['position_description'] ?></textarea>
              </div>
           </div>
         </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-black btn-flat px-3 elevation-5" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
        <button type="submit" class="btn btn-success px-3 btn-flat elevation-5"><i class="fas fa-edit"></i> EDIT</button>
      </div>

      </form>   
    </div>
  </div>

  <script>
  $(document).ready(function(){

    $(function () {
        $.validator.setDefaults({
          submitHandler: function (form) {
            Swal.fire({
              title: '<strong class="text-warning">Are you sure?</strong>',
              html: "<b>You want edit this position?</b>",
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
                    url: 'editPosition.php',
                    type: 'POST',
                    data: $(form).serialize(),
                    cache: false,
                    success:function(data){
                      if(data == 'error'){

                        Swal.fire({
                          title: '<strong class="text-danger">ERROR</strong>',
                          type: 'error',
                          html: '<b>Position is already Exist<b>',
                          width: '400px',
                          confirmButtonColor: '#6610f2',
                        })

                      }else{

                        Swal.fire({
                          title: '<strong class="text-success">SUCCESS</strong>',
                          type: 'success',
                          html: '<b>Updated Position has Successfully<b>',
                          width: '400px',
                          confirmButtonColor: '#6610f2',
                          allowOutsideClick: false,
                          showConfirmButton: false,
                          timer: 2000,
                        }).then(()=>{
                          $("#positionTable").DataTable().ajax.reload();
                          $("#viewPositionModal").modal('hide');
                        })

                      }
                     
                    }
                }).fail(barangayAjaxError)
              }
            })
            
          }
        });
      $('#editPositionForm').validate({
        rules: {
          edit_position: {
            required: true,
            minlength: 2
          },
          edit_limit: {
            required: true,
           
          },
        },
        messages: {
          edit_position: {
            required: "Position is Required",
            minlength: "Position must be at least 2 characters long"
          },
          edit_limit: {
            required: "Position Limit is Required",
           
          },
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
          element.closest('.form-group-sm').append(error);
        },
        highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        }
      
      });
    })

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

$("#edit_description").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });

  
$("#edit_position").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  $("#edit_limit").inputFilter(function(value) {
  return /^[0-9]*$/i.test(value); 
  });



  $("#edit_limit").on("input", function() {
      if (/^0/.test(this.value)) {
        this.value = this.value.replace(/^0/, "")
      }
    })

</script>