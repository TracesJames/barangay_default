<?php 



include_once '../connection.php';
include_once '../includes/auth_resident.php';

try{


  if(isset($_REQUEST['residence_id']) && isset($_REQUEST['certificate_id'])){

    $residence_id = $con->real_escape_string($_REQUEST['residence_id']);
    $certificate_id = $con->real_escape_string($_REQUEST['certificate_id']);

    $sql_request_status = "SELECT certificate_request.*, residence_information.first_name, residence_information.middle_name, residence_information.last_name,
    residence_information.image, residence_information.image_path, residence_information.address,  residence_information.gender, residence_information.age,  residence_information.contact_number
    FROM certificate_request INNER JOIN residence_information ON certificate_request.residence_id = residence_information.residence_id WHERE certificate_request.id = ?
    AND certificate_request.residence_id = ?";
    $stmt_request_status = $con->prepare($sql_request_status) or die ($con->error);
    $stmt_request_status->bind_param('ss',$certificate_id,$residence_id);
    $stmt_request_status->execute();
    $result =  $stmt_request_status->get_result();

    $row_request_status = $result->fetch_assoc();
        
    if($row_request_status['image'] != '' || $row_request_status['image'] != null){
      $image = '<img class="img-circle elevation-2" src="'.$row_request_status['image_path'].'" alt="User Avatar">';
    }else{
      $image = '<img class="img-circle elevation-2" src="../assets/dist/img/blank_image.png" alt="User Avatar">';
    }
    

  }




}catch(Exception $e){
  echo $e->getMessage();
}


?>


<!-- Modal -->
<div class="modal fade" id="showStatusRequestModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <form id="editRequestForm" method="post">

        <div class="modal-header">
            <h5 class="modal-title"><i class="far fa-user"></i> Profile</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
          </div>
      <div class="modal-body">
        <div class="container-fluid">

                <input type="hidden" value="<?= $residence_id ?>" name="residence_id" id="residence_id">
                <input type="hidden" value="<?= $certificate_id ?>" name="certificate_id" id="certificate_id">
    

            <div class="card card-widget widget-user-2">
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-black">
                <div class="widget-user-image">
                  <?= $image ?>
                </div>
                <!-- /.widget-user-image -->
                <h3 class="widget-user-username"></h3>
                <h5 class="widget-user-desc pt-3"><?= $row_request_status['first_name'].' '.  $row_request_status['last_name']  ?></h5>
              </div>
              <div class="card-footer p-0">
                <ul class="nav flex-column ">
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="RESIDENT ID">
                    <span href="#" class="nav-link">
                      <i class="fas fa-id-card-alt text-yellow text-lg"></i> <span class="float-right "><?= $row_request_status['residence_id']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="ADDRESS">
                    <span href="#" class="nav-link">
                      <i class="fas fa-map-marker-alt text-yellow text-lg"></i> <span class="float-right "><?= $row_request_status['address']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="GENDER">
                    <span href="#" class="nav-link" >
                      <i class="fas fa-venus-mars text-yellow text-lg"></i> <span class="float-right "><?= $row_request_status['gender']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="AGE">
                    <span href="#" class="nav-link" >
                      <i class="fa fa-child text-yellow text-lg" ></i> <span class="float-right "><?= $row_request_status['age']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="CONTACT NUMBER">
                    <span href="#" class="nav-link" >
                      <i class="fa fa-phone text-yellow text-lg" ></i> <span class="float-right "><?= $row_request_status['contact_number']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="STATUS">
                    <span href="#" class="nav-link" >
                      <i class="fa fa-exclamation text-yellow text-lg" ></i> 
                      <?php 
                      if($row_request_status['status'] == 'REJECTED'){
                          echo ' <span class="float-right badge badge-danger"> '.$row_request_status['status'].'</span> ';
                        }elseif($row_request_status['status'] == 'PENDING') {
                          echo ' <span class="float-right badge badge-warning"> '.$row_request_status['status'].'</span> ';
                        }else{
                          echo ' <span class="float-right badge badge-success"> '.$row_request_status['status'].'</span> ';
                        }
                      
                      ?>
                      </span>
                  </li>
                </ul>
              </div>
            </div>

            <div class="row">
             
                <div class="col-sm-12">
                  <div class="form-group form-group-sm">
                    <label>Purpose</label>
                    <input type="text" name="purpose" id="edit_purpose" class="form-control" value="<?= $row_request_status['purpose'] ?>"     <?= $row_request_status['status'] != 'PENDING'? 'disabled': '' ?>>
                  </div>
                </div>
               
                <?php 
                
                if($row_request_status['status'] != 'PENDING'){
                  echo '
                  <style>
                         #showStatusRequestModal .modal-body{
                            height: 74vh;
                            overflow-y: auto;
                        }
                      #showStatusRequestModal  .modal-body::-webkit-scrollbar {
                            width: 5px;
                        }                                                    
                                                
                       #showStatusRequestModal .modal-body::-webkit-scrollbar-thumb {
                            background: #6c757d; 
                            --webkit-box-shadow: inset 0 0 6px #6c757d; 
                        }
                       #showStatusRequestModal .modal-body::-webkit-scrollbar-thumb:window-inactive {
                          background: #6c757d; 
                        }
                    </style>
                      <div class="col-sm-12">
                        <div class="form-group form-group-sm">
                          <label>Message</label>
                          <textarea name="message" id="message" class="form-control" cols="5" rows="2" disabled>'.$row_request_status['message'].'</textarea>
                        </div>
                      </div>
                        <div class="col-sm-6">
                          <div class="form-group form-group-sm">
                            <label>Date Issued</label>
                            <input type="date" name="date_issued" id="date_issued" class="form-control" value="'.$row_request_status['date_issued'].'" disabled>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group form-group-sm">
                            <label>Date Expired</label>
                            <input type="date" name="date_expired" id="date_expired" class="form-control" value="'.$row_request_status['date_expired'].'" disabled>
                          </div>
                        </div> ';
                      
                }
                
                ?>
                
              
            </div>

        </div>
      </div>
      <div class="modal-footer">
       
      <?= $row_request_status['status'] == 'PENDING' ?  ' <button type="submit" class="btn bg-primary btn-flat elevation-5 px-3"><i class="fas fa-edit"></i> EDIT</button>': '';  ?>

     
      <button type="button" class="btn bg-black btn-flat elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
      </div>

      </form>
    </div>
  </div>
</div>

<script>
  $(document).ready(function(){






    $("#editRequestForm").submit(function(e){
        e.preventDefault();
        
        
        var edit_purpose = $("#edit_purpose").val();
        
        if(edit_purpose == ''){
            
                     Swal.fire({
                      title: '<strong class="text-danger">ERROR</strong>',
                      type: 'error',
                      html: '<b>PURPOSE IS REQUIRED<b>',
                      width: '400px',
                      confirmButtonColor: '#6610f2',
                    })
            
        }else{
             Swal.fire({
                title: '<strong class="text-info">ARE YOU SURE?</strong>',
                html: "You want Edit this Request?",
                type: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                allowOutsideClick: false,
                confirmButtonText: 'Yes, Edit it!',
                width: '400px',
              }).then((result) => {
                if (result.value) {
                  $.ajax({
                    url: 'requestStatus.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    cache: false,
                    success:function(data){
                      Swal.fire({
                        title: '<strong class="text-success">Success</strong>',
                        type: 'success',
                        html: '<b>Edited Request has Successfully<b>',
                        width: '400px',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 2000
                      }).then(()=>{
                        $("#tableRequest").DataTable().ajax.reload();
                        $("#showStatusRequestModal").modal('hide');
                      })
                    }
                  }).fail(barangayAjaxError)
                }
              })
            
        }

           

    })
    $('[data-toggle="tooltip"]').tooltip();


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



  $("#edit_purpose").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  


</script>