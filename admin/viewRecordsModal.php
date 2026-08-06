<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

try{


  if(isset($_REQUEST['blotter_id']) || isset($_REQUEST['record_id'])){

    $record_id = $con->real_escape_string(trim((string) ($_REQUEST['blotter_id'] ?? $_REQUEST['record_id'] ?? '')));
    if ($record_id === '') {
      exit;
    }
    barangay_assert_blotter_in_scope($con, $record_id);
    $sql_record = "SELECT blotter_record.*, blotter_status.person_id, blotter_complainant.complainant_id FROM blotter_record
    INNER JOIN blotter_status ON blotter_record.blotter_id = blotter_status.blotter_main
    INNER JOIN blotter_complainant ON blotter_record.blotter_id = blotter_status.blotter_main WHERE blotter_record.blotter_id = ?";
    $stmt_record = $con->prepare($sql_record) or die ($con->error);
    $stmt_record->bind_param('s',$record_id);
    $stmt_record->execute();
    $result_blotter = $stmt_record->get_result();
    $row_record_blotter = $result_blotter->fetch_assoc();



  }


}catch(Exception $e){
  echo $e->getMessage();
}





?>



<!-- Modal -->
<div class="modal " id="viewBlotterRecordModal" data-backdrop="static" data-keyboard="false"  role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form id="editRecordForm" method="post">

      <div class="modal-body">
        <div class="container-fluid">

          <div class="row">
              <div class="col-sm-12">
             
                
                
        
                  <input type="hidden" name="blotter_id" value="<?=$row_record_blotter['blotter_id']?>">
              
              
              
                 
              </div>
                <div class="col-sm-12">
                    <div class="form-group form-group-sm">
                        <label>Complainant Resident</label>
                      <select name="edit_complainant_residence[]" multiple="multiple" id="edit_complainant_residence" class="select2bs4"  style="width: 100%;">
                        <option value="" id=""></option>
                        <?php 
                        $no = 'NO';
                          $sql_record_resdident_id = "SELECT
                          residence_information.residence_id,
                          residence_information.first_name, 
                          residence_information.middle_name,
                          residence_information.last_name,
                          residence_information.image,   
                          residence_information.image_path
                          FROM residence_information
                          INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id WHERE archive = ?
                          ORDER BY last_name ASC ";
                          $query_record_resident_id = $con->prepare($sql_record_resdident_id) or die ($con->error);
                          $query_record_resident_id->bind_param('s',$no);
                          $query_record_resident_id->execute();
                          $result_resident_id = $query_record_resident_id->get_result();
                          while($row_record_resident_id = $result_resident_id->fetch_assoc()){
                            if($row_record_resident_id['middle_name'] != ''){
                              $record_person_middle = $row_record_resident_id['middle_name'][0].'.'.' '; 
                            }else{
                              $record_person_middle = $row_record_resident_id['middle_name'].' '; 
                            }
                            ?>
                              <option  value="<?= $row_record_resident_id['residence_id'] ?>" <?php

                      
                                $sql_record_while_complainant = "SELECT blotter_record.*, blotter_status.person_id, blotter_complainant.complainant_id FROM blotter_record
                                INNER JOIN blotter_status ON blotter_record.blotter_id = blotter_status.blotter_main
                                INNER JOIN blotter_complainant ON blotter_record.blotter_id = blotter_status.blotter_main WHERE blotter_complainant.blotter_main = ?";
                                $stmt_record_while_complainant = $con->prepare($sql_record_while_complainant) or die ($con->error);
                                $stmt_record_while_complainant->bind_param('s',$record_id);
                                $stmt_record_while_complainant->execute();
                                $result_blotter_while_complainant = $stmt_record_while_complainant->get_result();
                                while($row_record_blotter_while_complainant = $result_blotter_while_complainant->fetch_assoc()){

                                  if($row_record_resident_id['residence_id']  == $row_record_blotter_while_complainant['complainant_id']){
                                    echo  'selected="selected"';
                                  }else{
                                    echo '';
                                  }

                                }
                              

                                   
                                
                              if($row_record_resident_id['image_path'] != '' || $row_record_resident_id['image_path'] != null || !empty($row_record_resident_id['image_path'])){
                                  echo 'data-image="'.$row_record_resident_id['image_path'].'"';
                              }else{
                                echo 'data-image="../assets/dist/img/blank_image.png"';
                              }
                             
                            ?>>
                            <?= $row_record_resident_id['last_name'] .' '. $row_record_resident_id['first_name'] .' '.  $record_person_middle  ?></option>
                            <?php
                          }   
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Complainant Not Resident</label>
                      <textarea name="edit_complainant_not_residence"  id="edit_complainant_not_residence" cols="57"  class="bg-transparent text-white form-control"><?= $row_record_blotter['complainant_not_residence'] ?></textarea>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Complainant Statement</label>
                      <textarea name="edit_complainant_statement" id="edit_complainant_statement" cols="57" rows="3" class="bg-transparent text-white form-control"><?= $row_record_blotter['statement'] ?></textarea>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Respodent</label>
                        <input name="edit_respodent" value="<?= $row_record_blotter['respodent'] ?>" id="edit_respodent"  class=" form-control">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group form-group-sm">
                        <label>Person Involved Resident</label>
                      <select name="edit_person_involed[]" multiple="multiple" id="edit_person_involed" class="select2bs4"  style="width: 100%;">
                        <option></option>
                        <?php 
                          $sql_person_id = "SELECT
                          residence_information.residence_id,
                          residence_information.first_name, 
                          residence_information.middle_name,
                          residence_information.last_name,
                          residence_information.image,   
                          residence_information.image_path
                          FROM residence_information
                          INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id 
                          WHERE archive = ?
                          ORDER BY last_name ASC  ";
                          $query_preson_id = $con->prepare($sql_person_id) or die ($con->error);
                          $query_preson_id->bind_param('s',$no);
                          $query_preson_id->execute();
                         $result_person_id = $query_preson_id->get_result();
                          while($row_person_id = $result_person_id->fetch_assoc()){
                            if($row_person_id['middle_name'] != ''){
                              $middle_person_id = $row_person_id['middle_name'][0].'.'.' '; 
                            }else{
                              $middle_person_id = $row_person_id['middle_name'].' '; 
                            }
                            ?>
                              <option value="<?= $row_person_id['residence_id'] ?>" <?php 


                                    $sql_record_while_person = "SELECT blotter_record.*, blotter_status.person_id, blotter_complainant.complainant_id FROM blotter_record
                                    INNER JOIN blotter_status ON blotter_record.blotter_id = blotter_status.blotter_main
                                    INNER JOIN blotter_complainant ON blotter_record.blotter_id = blotter_status.blotter_main WHERE blotter_status.blotter_main = ?";
                                    $stmt_record_while_person = $con->prepare($sql_record_while_person) or die ($con->error);
                                    $stmt_record_while_person->bind_param('s',$record_id);
                                    $stmt_record_while_person->execute();
                                    $result_blotter_while_complainant = $stmt_record_while_person->get_result();
                                    while($row_record_blotter_while_person = $result_blotter_while_complainant->fetch_assoc()){

                                      if($row_person_id['residence_id']  == $row_record_blotter_while_person['person_id']){
                                        echo  'selected="selected"';
                                      }else{
                                        echo '';
                                      }

}


                              if($row_person_id['image_path'] != '' || $row_person_id['image_path'] != null || !empty($row_person_id['image_path'])){
                                  echo 'data-image="'.$row_person_id['image_path'].'"';
                              }else{
                                echo 'data-image="../assets/dist/img/blank_image.png"';
                              }
             
                            ?>>
                            <?=   $row_person_id['last_name'] .' '.  $row_person_id['first_name'] .' '.  $middle_person_id  ?></option>
                            <?php
                          }   
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Person Involved Not Resident</label>
                      <textarea name="edit_person_involevd_not_resident"  id="edit_person_involevd_not_resident" cols="57"  class="bg-transparent text-white form-control"><?= $row_record_blotter['involved_not_resident'] ?></textarea>
                    </div>
                  </div> 
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Person Involved Statement</label>
                      <textarea name="edit_person_statement" id="edit_person_statement" cols="57" rows="3" class="bg-transparent text-white form-control"><?= $row_record_blotter['statement_person'] ?></textarea>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Location of Incident</label>
                        <input name="edit_location_incident" value="<?= $row_record_blotter['location_incident'] ?>" id="edit_location_incident"  class=" form-control">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Date of Incident</label>
                        <input type="datetime-local" name="edit_date_of_incident" id="edit_date_of_incident" value="<?= $row_record_blotter['date_incident']; ?>"  class=" form-control">
                    </div>
                  </div>  
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Incident</label>
                        <input name="edit_incident" id="edit_incident"  class=" form-control" value="<?= $row_record_blotter['type_of_incident']; ?>">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Status</label>
                        <select name="edit_status" id="edit_status" class="form-control">
                          <option value="NEW" <?= $row_record_blotter['status'] == 'NEW' ? 'selected': '' ?>>NEW</option>
                          <option value="ONGOING"  <?= $row_record_blotter['status'] == 'ONGOING' ? 'selected': '' ?>>ONGOING</option>
                        </select>
                    </div>
                  </div> 
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Date Reported</label>
                        <input  type="datetime-local" name="edit_date_reported" id="edit_date_reported"  class=" form-control" value="<?= $row_record_blotter['date_reported']; ?>">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Remarks</label>
                        <select name="edit_remarks" id="edit_remarks" class="form-control">
                          <option value="OPEN" <?= $row_record_blotter['remarks'] == 'OPEN' ? 'selected': '' ?>>OPEN</option>
                          <option value="CLOSED" <?= $row_record_blotter['remarks'] == 'CLOSED' ? 'selected': '' ?>>CLOSED</option>
                        </select>
                    </div>
                  </div>      

          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-black elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
        <button type="submit" class="btn btn-primary elevation-5 px-3 btn-flat"><i class="fa fa-edit"></i> UPDATE CASE</button>
      </div>

      </form>
    </div>
  </div>
</div>

<script>
  $(document).ready(function(){
 

    $(function () {
        $.validator.setDefaults({
          submitHandler: function (form) {
     
          var edit_complainant = $("#edit_complainant_residence").val();
          var edit_complainant_not_residence = $("#edit_complainant_not_residence").val();
          var edit_complainant_statement = $("#edit_complainant_statement").val();
          var edit_person_statement = $("#edit_person_statement").val();
          var edit_person_involed = $("#edit_person_involed").val();
          var edit_person_involevd_not_resident = $("#edit_person_involevd_not_resident").val();
          

            if(edit_complainant == '' && edit_complainant_not_residence == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Complainant is Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }
            
            if(edit_complainant_statement == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Complainant is Statement Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }

            if(edit_person_involed == '' && edit_person_involevd_not_resident == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Person Involved is Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }

            if(edit_person_statement == ''){
              Swal.fire({
                title: '<strong class="text-danger">Ooppss..</strong>',
                type: 'error',
                html: '<b>Person Involved Statement is Required<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              return false;
            }

            $.ajax({
              url: 'editBlotterRecord.php',
              type: 'POST',
              data: $(form).serialize(),
              cache: false,
              success:function(){
                $("#viewBlotterRecordModal").modal('hide');
                Swal.fire({
                  title: '<strong class="text-success">SUCCESS</strong>',
                  type: 'success',
                  html: '<b>Added Record Blotter has Successfully<b>',
                  width: '400px',
                  confirmButtonColor: '#6610f2',
                  allowOutsideClick: false,
                  showConfirmButton: false,
                  timer: 2000,
                }).then(()=>{
                  $("#blotterRecordTable").DataTable().ajax.reload();
                 
                  $('#edit_complainant_residence').empty();
                 $('#edit_person_involed').empty();
                })

              }
            }).fail(barangayAjaxError)

     

          }
        });
      $('#editRecordForm').validate({
        ignore: "",
        rules: {
          edit_date_reported: {
            required: true,
           
          },
          edit_incident: {
            required: true,
           
          },

        },
        messages: {
          edit_date_reported: {
            required: "Please provide a Date Reported is Required",
            
          },
          edit_incident: {
            required: "Incident is Required",
           
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


    $("#edit_complainant_residence, #edit_person_involed").on('select2:select', function(e){
      var residence_id = e.params.data.id;
      $("#show_residence").html('');
      $.ajax({
          url: 'showResidence.php',
          type: 'POST',
          data:{
            residence_id:residence_id,
          },
          cache: false,
          success:function(data){
            $("#show_residence").html(data);
            $("#viewResidenceModal").modal('show');
           
          }
      }).fail(barangayAjaxError)
      
    })



  })
</script>

<script>
$(document).ready(function(){
 

  $("#edit_complainant_residence").on('change', function(e){
    var subject = [];
  
      	subject.push($(this).val());
      var selected_values = subject.join(",");
       console.log(selected_values);

      $.ajax({
        url: 'showPerson.php',
            type: 'POST',
            data:  {
              selected_values:selected_values
            },
          cache: false,
          success:function(data){
            $("#edit_person_involed").html(data);
          
          }
      }).fail(barangayAjaxError)
      
    })
     
   
  


});
</script>

<script>
    $('#edit_complainant_residence').select2({
      templateResult: formatState,
      templateSelection: formatState,
      theme: 'bootstrap4',
      dropdownParent: $('.modal'),
  
      language: {
          noResults: function (params) {
            return "No Record";
          }
        },
      
    }) 
    function formatState (opt) {
          if (!opt.id) {
              return opt.text.toUpperCase();
          } 
          var optimage = $(opt.element).attr('data-image'); 
          if(!optimage){
            return opt.text.toUpperCase();
          } else {                    
              var $opt = $(
                '<span><img class="img-circle  pb-1" src="' + optimage + '" width="20px" /> ' + opt.text.toUpperCase() + '</span>'
              );
              return $opt;
          }
      };
</script>


<script>
    $('#edit_person_involed').select2({
      templateResult: formatState,
      templateSelection: formatState,
      theme: 'bootstrap4',
      dropdownParent: $('.modal'),
  
      language: {
          noResults: function (params) {
            return "No Record";
          }
        },
      
    }) 
    function formatState (opt) {
          if (!opt.id) {
              return opt.text.toUpperCase();
          } 
          var optimage = $(opt.element).attr('data-image'); 
          if(!optimage){
            return opt.text.toUpperCase();
          } else {                    
              var $opt = $(
                '<span><img class="img-circle  pb-1" src="' + optimage + '" width="20px" /> ' + opt.text.toUpperCase() + '</span>'
              );
              return $opt;
          }
      };
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



  $("#edit_complainant_not_residence, #edit_person_involevd_not_resident").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
  $("#edit_complainant_statement, #edit_respodent,#edit_incident,#edit_location_incident,#edit_person_statement").inputFilter(function(value) {
  return /^[0-9a-z, ,-]*$/i.test(value); 
  });

</script>