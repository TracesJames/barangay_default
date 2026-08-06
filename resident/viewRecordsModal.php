<?php 


include_once '../connection.php';
include_once '../includes/auth_resident.php';

try{


  if(isset($_REQUEST['record_id'])){

    $record_id = $con->real_escape_string(trim($_REQUEST['record_id']));
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

<style>
  .dark-mode .select2-selection{
      background-color: #343a40;
        border-color: #6c757d;
    }
    .select2-container--bootstrap4.select2-container--disabled .select2-selection, .select2-container--bootstrap4.select2-container--disabled.select2-container--focus .select2-selection{
      background-color: transparent;
      background: transparent;
    
    }
    .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice{
      border: none;
    }
     
.modal-body{
    height: 80vh;
    overflow-y: auto;
}
.modal-body::-webkit-scrollbar {
    width: 5px;
}                                                    
                         
.modal-body::-webkit-scrollbar-thumb {
    background: #6c757d; 
    --webkit-box-shadow: inset 0 0 6px #6c757d; 
}
.modal-body::-webkit-scrollbar-thumb:window-inactive {
  background: #6c757d; 
}
</style>

<!-- Modal -->
<div class="modal " id="viewBlotterRecordModal" data-backdrop="static" data-keyboard="false"  role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form id="editRecordForm" method="post">

      <div class="modal-body">
        <div class="container-fluid">

          <div class="row">
              <div class="col-sm-12">
             
                
                
        
   
              
              
              
                 
              </div>
                <div class="col-sm-12">
                    <div class="form-group form-group-sm">
                        <label>Complainant Resident</label>
                      <select name="edit_complainant_residence[]" multiple="multiple" id="edit_complainant_residence" class="select2bs4"  style="width: 100%;" disabled>
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
                      <textarea name="edit_complainant_not_residence" disabled id="edit_complainant_not_residence" cols="57"  class="bg-transparent text-white form-control"><?= $row_record_blotter['complainant_not_residence'] ?></textarea>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Complainant Statement</label>
                      <textarea name="edit_complainant_statement" disabled id="edit_complainant_statement" cols="57" rows="3" class="bg-transparent text-white form-control"><?= $row_record_blotter['statement'] ?></textarea>
                    </div>
                  </div>
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Respodent</label>
                        <input name="edit_respodent" disabled value="<?= $row_record_blotter['respodent'] ?>" id="edit_respodent"  class=" form-control">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group form-group-sm">
                        <label>Person Involved Resident</label>
                      <select name="edit_person_involed[]"disabled  multiple="multiple" id="edit_person_involed" class="select2bs4"  style="width: 100%;">
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
                      <textarea name="edit_person_involevd_not_resident" disabled  id="edit_person_involevd_not_resident" cols="57"  class="bg-transparent text-white form-control"><?= $row_record_blotter['involved_not_resident'] ?></textarea>
                    </div>
                  </div> 
                  <div class="col-sm-12 ">
                    <div class="form-group form-group-sm">
                      <label>Person Involved Statement</label>
                      <textarea name="edit_person_statement" disabled id="edit_person_statement" cols="57" rows="3" class="bg-transparent text-white form-control"><?= $row_record_blotter['statement_person'] ?></textarea>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Location of Incident</label>
                        <input name="edit_location_incident" disabled value="<?= $row_record_blotter['location_incident'] ?>" id="edit_location_incident"  class=" form-control">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Date of Incident</label>
                        <input type="datetime-local" disabled name="edit_date_of_incident" id="edit_date_of_incident" value="<?= $row_record_blotter['date_incident']; ?>"  class=" form-control">
                    </div>
                  </div>  
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Incident</label>
                        <input name="edit_incident" disabled id="edit_incident"  class=" form-control" value="<?= $row_record_blotter['type_of_incident']; ?>">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Status</label>
                        <select name="edit_status" id="edit_status" class="form-control" disabled>
                          <option value="NEW" <?= $row_record_blotter['status'] == 'NEW' ? 'selected': '' ?>>NEW</option>
                          <option value="ONGOING"  <?= $row_record_blotter['status'] == 'ONGOING' ? 'selected': '' ?>>ONGOING</option>
                        </select>
                    </div>
                  </div> 
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Date Reported</label>
                        <input  type="datetime-local" disabled name="edit_date_reported" id="edit_date_reported"  class=" form-control" value="<?= $row_record_blotter['date_reported']; ?>">
                    </div>
                  </div>   
                  <div class="col-sm-6">
                    <div class="form-group form-group-sm">
                      <label>Remarks</label>
                        <select name="edit_remarks" id="edit_remarks" class="form-control" disabled>
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
      </div>

      </form>
    </div>
  </div>
</div>


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