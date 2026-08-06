<?php 


include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/residence_family.php';


try{

  if(isset($_REQUEST['residence_id']) && $_REQUEST['residence_id'] !=''){

  
      $residence_id = $con->real_escape_string(trim($_REQUEST['residence_id']));
      $sql_residence_view = "SELECT residence_information.*, residence_status.* FROM residence_information INNER JOIN residence_status ON  residence_information.residence_id = residence_status.residence_id WHERE residence_information.residence_id = ?";
      $stmt_view_residence = $con->prepare($sql_residence_view) or die ($con->error);
      $stmt_view_residence->bind_param('s',$residence_id);
      $stmt_view_residence->execute();
      $residence_view = $stmt_view_residence->get_result();
      $row_view_residence = $residence_view->fetch_assoc();
      $dependents = residence_load_dependents($con, $residence_id);



      $sql_barangay_information = "SELECT * FROM `barangay_information`";
      $stmt_barangay_information = $con->prepare($sql_barangay_information) or die ($con->error);
      $stmt_barangay_information->execute();
      $result_barangay_information = $stmt_barangay_information->get_result();
      $row_barangay_information = $result_barangay_information->fetch_assoc();
   
       
  }



}catch(Exception $e){
  echo $e->getMessage();
}



?>

<style>
 
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

fieldset {
        border: 3px solid black !important;
        padding: 0 1.4em 1.4em 1.4em !important;
        margin: 0 0 1.5em 0 !important;
        -webkit-box-shadow:  0px 0px 0px 0px #000;
                box-shadow:  0px 0px 0px 0px #000;
      }
    legend {
      font-size: 1.2em !important;
      font-weight: bold !important;
      color: #fff;
      text-align: left !important;
      width:auto;
      padding:0 10px;
      border-bottom:none;
    }
    .editInfo {
    background-color:rgba(0, 0, 0, 0);
    color:#fff;
    border: none;
    outline:none;
    width: 100%;
    }
   #edit_gender, #edit_civil_status, #edit_voters, #edit_pwd, select {
      /* for Firefox */
      -moz-appearance: none;
      /* for Chrome */
  
      border: none;
      width: 100%;
    }
    #edit_gender, #edit_civil_status, #edit_voters, #edit_pwd, option:focus{
      outline:none;
      border:none;
      box-shadow:none;
    }

    /* For IE10 */
    #edit_gender, #edit_civil_status, #edit_voters, #edit_pwd, select::-ms-expand {
      display: none;
    }
    #display_edit_image_residence{
      width: 140px;
      height: 140px;
      max-width: 140px;
      object-fit: cover;
      object-position: center;
      border-radius: 50%;
    }
    #barangay_logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }
    

   
</style>



<!-- Modal -->
<div class="modal fade " id="viewResidenceModal"  role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">

      <div class="modal-body">
        <div class="container-fluid">
         
        
        <div class="dynamic_form">
        <input type="hidden" id="edit_residence_id" name="edit_residence_id" value="<?= $row_view_residence['residence_id'];?>">
        <table width="80%" style="font-size:9pt;" class="table table-borderless">
          <tbody>
            <tr>
              <td class="text-center">
                <?php 
                
                if($row_barangay_information['image_path'] != '' || $row_barangay_information['image_path'] != null || !empty($row_barangay_information['image_path'])){
                    echo '<img alt="barangay_logo" src="'.$row_barangay_information['image_path'].'" class="img-circle"  id="barangay_logo">';
                }else{
                  echo '<img alt="barangay_logo" src="../assets/logo/blank.png" class="img-circle"  id="barangay_logo">';
                }
                
                ?>
                
              </td>
              <td class="text-center">
                <div style="font-size:10pt;">
                  Application for Authority proof that you are a resident of this barangay<br>
                  <?= $row_barangay_information['barangay'].' '. $row_barangay_information['zone'].' '.$row_barangay_information['district'] ?><br>
                  <?= $row_barangay_information['address'];?>
                  <br>
              
                </div>
              </td>
              <td  class="text-center">
                <?php 
                
                if($row_view_residence['image'] != '' || $row_view_residence['image'] != null || !empty($row_view_residence['image'])){
                  echo '<img src="'.$row_view_residence['image_path'].'" style="cursor: pointer" alt="residence_image" class="img-thumbnail" width="120" id="display_edit_image_residence">
                          <input type="file" id="edit_image_residence" name="edit_image_residence" style="display: none;">';
                }else{
                  echo '<img src="../assets/dist/img/blank_image.png" style="cursor: pointer" alt="residence_image" class="img-thumbnail" width="120" id="display_edit_image_residence">
                        <input type="file" id="edit_image_residence" name="edit_image_residence" style="display: none;">';
                }
                echo '<br>';
                if($row_view_residence['status'] == 'ACTIVE'){
                  echo '<span class="badge bg-success text-md mt-1">'.$row_view_residence['status'].'</span>';
                }else{
                  echo '<span class="badge bg-danger text-md mt-1">'.$row_view_residence['status'].'</span>';
                }

                ?>
               
                <br>
               
              </td>
            </tr>
          </tbody>
        </table>
        <input type="hidden" name="edit_residence_id" value="<?= $row_view_residence['residence_id'];?>">
      

  <div class="table-responsive">
  <table  style="font-size:11pt;" class="table table-bordered">
    <tbody>
      
      <tr>
        <td colspan="3">
          <div class="d-flex justify-content-between">
            <div> FIRST NAME<br>
              <input type="text" disabled  class="editInfo form-control form-control-sm"  value="<?= $row_view_residence['first_name'] ?>" id="edit_first_name" name="edit_first_name" size="30"> 

            </div>
            <div>MIDDLE NAME<br>
              <input type="text" disabled  class="editInfo  form-control form-control-sm " value="<?= $row_view_residence['middle_name'] ?>" id="edit_middle_name" name="edit_middle_name" size="20"> 
            </div>
            <div>      
              LAST NAME<br>
              <input type="text" disabled class="editInfo  form-control form-control-sm"  value="<?= $row_view_residence['last_name'] ?>" id="edit_last_name" name="edit_last_name" size="20"> 
            </div>
            <div>      
              SUFFIX<br>
              <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['suffix'] ?>" id="edit_suffix" name="edit_suffix" size="5">  
            </div>
          </div>
        </td>
      <td>
       VOTERS
        <br>
        <select name="edit_voters" id="edit_voters" class="form-control" disabled>
          <option value="NO" <?= $row_view_residence['voters'] == 'NO'? 'selected': '' ?>>NO</option>
          <option value="YES" <?= $row_view_residence['voters'] == 'YES'? 'selected': '' ?>>YES</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>
         DATE OF BIRTH
          <br>
          
          <input type="date" disabled class="editInfo  form-control form-control-sm" value="<?php echo strftime('%Y-%m-%d',strtotime($row_view_residence['birth_date'])); ?>" name="edit_birth_date" id="edit_birth_date"/>
                     
      </td>
      <td>
        PLACE OF BIRTH
          <br>
        
        <input type="text" disabled class="editInfo  form-control form-control-sm" value=" <?= $row_view_residence['birth_place'] ?>"  name="edit_birth_place" id="edit_birth_place" > 
      </td>
      <td >
        AGE
          <br>
       
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['age'] ?>"  name="edit_age" id="edit_age" disabled> 
      </td>
      <td >
        PWD
          <br>
          <select name="edit_pwd" id="edit_pwd" disabled class="form-control">
            <option value="YES" <?= $row_view_residence['pwd'] == 'YES'? 'selected': '' ?>>YES</option>
            <option value="NO" <?= $row_view_residence['pwd'] == 'NO'? 'selected': '' ?>>NO</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>
        GENDER
        <br>
        <select name="edit_gender" disabled id="edit_gender" class="form-control">
          <option value="Male" <?= $row_view_residence['gender'] == 'Male'? 'selected': '' ?>>Male</option>
          <option value="Female" <?= $row_view_residence['gender'] == 'Female'? 'selected': '' ?>>Female</option>
        </select>
      </td>
      <td>
        CIVIL STATUS
        <br>
        <select name="edit_civil_status" disabled id="edit_civil_status" class="form-control">
          <option value="Single" <?= $row_view_residence['civil_status'] == 'Single'? 'selected': ''; ?>>Single</option>
          <option value="Married" <?= $row_view_residence['civil_status'] == 'Married'? 'selected': ''; ?>>Married</option>
        </select>
      </td>
      <td >
        RELIGION
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['religion'] ?>" name="edit_religion" id="edit_religion">
      </td> 
      <td>
        NATIONALITY
        <br>
          <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['nationality'] ?>" name="edit_nationality" id="edit_nationality">
      </td>     
    </tr>

    <tr>
      <td>
       MUNICIPALITY
        <br>
       <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['municipality'] ?>" name="edit_municipality" id="edit_municipality">
      </td>
      <td>
        ZIP
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['zip'] ?>" name="edit_zip" id="edit_zip">
      </td>
      <td colspan="2">
        BARANGAY
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['barangay'] ?>" name="edit_barangay" id="edit_barangay">
      </td>
    </tr>

    <tr>
      <td>
        HOUSE NUMBER
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['house_number'] ?>" name="edit_house_number" id="edit_house_number">
      </td>
      <td>
        STREET
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['street'] ?>" name="edit_street" id="edit_street">
      </td>
      <td colspan="2">
        ADDRESS
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['address'] ?>" name="edit_address" id="edit_address">
      </td>      
    </tr>

    <tr>
      <td colspan="2">
        EMAIL ADDRESS
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['email_address'] ?>" name="edit_email_address" id="edit_email_address">
      </td>
      <td colspan="2">
        CONTACT NUMBER
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['contact_number'] ?>" name="edit_contact_number" id="edit_contact_number">
      </td>         
    </tr>

    <tr>
      <td colspan="2">
        FATHER'S NAME
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['fathers_name'] ?>" name="edit_fathers_name" id="edit_fathers_name">
      </td>
      <td colspan="2">
        MOTHER'S NAME
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['mothers_name'] ?>" name="edit_mothers_name" id="edit_mothers_name">
      </td>         
    </tr>

    <tr>
      <td colspan="2">
        GUARDIAN
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['guardian'] ?>" name="edit_guardian" id="edit_guardian">
      </td>
      <td colspan="2">
        GUARDIAN CONTACT
        <br>
        <input type="text" disabled class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['guardian_contact'] ?>" name="edit_guardian_contact" id="edit_guardian_contact">
      </td>         
    </tr>

    <?php $familyReadOnly = true; require __DIR__ . '/../includes/partials/residence_family_modal_fields.php'; ?>
  
   </tbody>
  </table>
  </div>
  <?php require __DIR__ . '/../includes/partials/residence_dependents_modal.php'; ?>
  <fieldset>
            <legend>CASE INVOLVED <span></span></legend>

            <div class="table-responsive" >
            <table class="table table-sm" id="blotterPersonTable" style="font-size: 13px; font-weight: 200px;">
              <thead>
                <tr>
                  <th class="d-none">Color</th>
                  <th>Blotter Number</th>
                  <th class="text-left">Status</th>
                  <th>Remarks</th>
                  <th>Incident</th>
                  <th>Location of Incident</th>
                  <th>Date Incident</th>
                  <th>Date Reported</th>
               
                </tr>
              </thead>
            </table>
            </div>

            
  </fieldset>         

 </table>

  

</div>


               
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-black btn-flat elevation-5 btn-flat" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
      </div>
      </div>
    </div>
  </div>
</div>

<div id="show_resident_record"></div>

<script>  
$(document).ready(function(){ 

  blotterPersonTable();
  viewResidentRecord()


  function viewResidentRecord(){

        $(document).on('click','.viewRecord',function(){

            var id = $(this).attr('id');
            $('#show_resident_record').html('')
            
            $.ajax({
            url: 'viewRecordResident.php',
            type: 'POST',
            data:{
              id:id,
            
            },
            success:function(data){
              $('#show_resident_record').html(data)
              $("#viewResidentRecordModal").modal('show');
              
            }
          }).fail(barangayAjaxError)
            


          })


        }

  function blotterPersonTable(){

    var edit_residence_id = $("#edit_residence_id").val();
    var blotterPersonTable = $("#blotterPersonTable").DataTable({
    
  processing: true,
  serverSide: true,
  order:[],
  searching: false,
  info: false,
  paging: false,
  lengthChange: false,
  columnDefs:[
    {
      targets: '_all',
      orderable: false,
    },
    {
      targets: 3,
     className: 'text-left',
    },
    {
      targets: 0,
     className: 'd-none',
    }
    
  ],
  ajax:{
    url: 'blotterPersonTable.php',
    type: 'POST',
    data:{
      edit_residence_id:edit_residence_id
    }
  },
        fnRowCallback: function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
          if ( aData[0] == "1" )  {
          $('td', nRow).css('background-color', '#20c997');
        
        }else {
          $('td', nRow).css('background-color', '#000');
          }
          
      }

  

})
}




  
});  
</script>
