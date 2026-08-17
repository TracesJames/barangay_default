<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_family.php';


try{

  if(isset($_REQUEST['residence_id'])){
  
      $residence_id = $con->real_escape_string(trim($_REQUEST['residence_id']));
      $sql_residence_view = "SELECT residence_information.*, residence_status.* FROM residence_information INNER JOIN residence_status ON  residence_information.residence_id = residence_status.residence_id WHERE residence_information.residence_id = ?";
      $stmt_view_residence = $con->prepare($sql_residence_view) or die ($con->error);
      $stmt_view_residence->bind_param('s',$residence_id);
      $stmt_view_residence->execute();
      $residence_view = $stmt_view_residence->get_result();
      $row_view_residence = $residence_view->fetch_assoc();
      $dependents = residence_load_dependents($con, $residence_id);
      $canEditOrDeletePerson = barangay_user_can_edit_or_delete_person($con, (string) ($_SESSION['user_id'] ?? ''));
      $familyReadOnly = !$canEditOrDeletePerson;

      $scopeBarangayId = $row_view_residence['barangay_id'] ?? barangay_session_id();
      $row_barangay_information = barangay_load_for_scope($con, $scopeBarangayId) ?? [
          'image_path' => '../assets/logo/valencia-city.png',
          'barangay' => $row_view_residence['barangay'] ?? '',
      ];
   
       
  }

  $canEditOrDeletePerson = $canEditOrDeletePerson ?? false;
  $familyReadOnly = $familyReadOnly ?? true;



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
   #edit_gender, #edit_civil_status, #edit_voters, #edit_pwd, #edit_single_parent select {
      /* for Firefox */
      -moz-appearance: none;
      /* for Chrome */
  
      border: none;
      width: 100%;
    }
    #edit_gender, #edit_civil_status, #edit_voters, #edit_pwd, #edit_single_parent, option:focus{
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
    
    #blotterPersonTable{

 padding: 5px;
 margin-bottom: 5px;
}



   
</style>



<!-- Modal -->
<div class="modal" id="viewResidenceModal"  role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
    <form id="editResidenceForm" method="post" enctype="multipart/form-data">
      <div class="modal-body">
        
        <div class="container-fluid">
         
        
        <div class="dynamic_form">
       
        <table width="80%" style="font-size:9pt;" class="table table-borderless">
          <tbody>
            <tr>
              <td class="text-center">
                <?php
                $modalBarangayLogo = barangay_admin_logo_url($row_barangay_information);
                echo '<img alt="barangay_logo" src="' . barangay_h($modalBarangayLogo) . '" class="img-circle" id="barangay_logo">';
                ?>
                
              </td>
              <td class="text-center">
                <div style="font-size:10pt;">
                  Application for Authority proof that you are a resident of this barangay<br>
                  <?= $row_barangay_information['barangay'].' '. $row_barangay_information['zone'].' '.$row_barangay_information['district'] ?><br>
                  <?= $row_barangay_information['address'];?>
                  <br>
                  <br>
                  <br>
                  <h4>RESIDENT NO. <?= $row_view_residence['residence_id'] ?></h4>
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
                  echo '<span class="badge bg-success text-md mt-1">'.$row_view_residence['status'].'</span>
                  ';
                }else{
                  echo '<span class="badge bg-danger text-md mt-1">'.$row_view_residence['status'].'</span>';
                }

                ?>
               
                <br>
               
              </td>
            </tr>
          </tbody>
        </table>
        <input type="hidden" id="edit_residence_id" name="edit_residence_id" value="<?= $row_view_residence['residence_id'];?>">
      

  <div class="table-responsive">
  <table  style="font-size:11pt;" class="table table-bordered">
    <tbody>
      
      <tr>
        <td colspan="3">
          <div class="d-flex justify-content-between">
            <div> FIRST NAME<br>
              <input type="text"  class="editInfo form-control form-control-sm"  value="<?= $row_view_residence['first_name'] ?>" id="edit_first_name" name="edit_first_name" size="30"> 
              <input type="hidden" value="false" id="edit_first_name_check"> 
            </div>
            <div>MIDDLE NAME<br>
              <input type="text"  class="editInfo  form-control form-control-sm " value="<?= $row_view_residence['middle_name'] ?>" id="edit_middle_name" name="edit_middle_name" size="20"> 
              <input type="hidden" id="edit_middle_name_check" value="false">
            </div>
            <div>      
              LAST NAME<br>
              <input type="text"  class="editInfo  form-control form-control-sm"  value="<?= $row_view_residence['last_name'] ?>" id="edit_last_name" name="edit_last_name" size="20"> 
              <input type="hidden" value="false" id="edit_last_name_check">
            </div>
            <div>      
              SUFFIX<br>
              <input type="text"  class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['suffix'] ?>" id="edit_suffix" name="edit_suffix" size="5">  
              <input type="hidden" id="edit_suffix_check" value="false">
            </div>
          </div>
        </td>
      <td>
       VOTERS
        <br>
        <select name="edit_voters" id="edit_voters" class="form-control">
          <option value="NO" <?= $row_view_residence['voters'] == 'NO'? 'selected': '' ?>>NO</option>
          <option value="YES" <?= $row_view_residence['voters'] == 'YES'? 'selected': '' ?>>YES</option>
        </select>
        <input type="hidden" value="false" id="edit_voters_check">
      </td>
    </tr>
    <tr>
      <td>
         DATE OF BIRTH
          <br>
          
          <input type="date" class="editInfo  form-control form-control-sm" value="<?= barangay_html_date($row_view_residence['birth_date'] ?? '') ?>" name="edit_birth_date" id="edit_birth_date"/>
          <input type="hidden" id="edit_birth_date_check" value='false'>
      </td>
      <td>
        PLACE OF BIRTH
          <br>
        
        <input type="text" class="editInfo  form-control form-control-sm" value=" <?= $row_view_residence['birth_place'] ?>"  name="edit_birth_place" id="edit_birth_place" > 
        <input type="hidden" id="edit_birth_place_check" value="false">
      </td>
      <td >
        AGE
          <br>
       
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['age'] ?>"  name="edit_age" id="edit_age" disabled> 
      </td>
      <td >
        SINGLE PARENT
          <br>
          <select name="edit_single_parent" id="edit_single_parent" class="form-control">
            <option value="YES" <?= $row_view_residence['single_parent'] == 'YES'? 'selected': '' ?>>YES</option>
            <option value="NO" <?= $row_view_residence['single_parent'] == 'NO'? 'selected': '' ?>>NO</option>
        </select>
        <input type="hidden" id="edit_single_parent_check" value="false">
      </td>
      <td >
        INDIGENOUS PEOPLE (IP)
          <br>
          <select name="edit_indigenous" id="edit_indigenous" class="form-control">
            <option value="YES" <?= ($row_view_residence['indigenous'] ?? 'NO') == 'YES'? 'selected': '' ?>>YES</option>
            <option value="NO" <?= ($row_view_residence['indigenous'] ?? 'NO') == 'NO'? 'selected': '' ?>>NO</option>
        </select>
        <input type="hidden" id="edit_indigenous_check" value="false">
      </td>
   
   
    </tr>
    <tr>
    <td >
        PWD
          <br>
          <select name="edit_pwd" id="edit_pwd" class="form-control">
            <option value="YES" <?= $row_view_residence['pwd'] == 'YES'? 'selected': '' ?>>YES</option>
            <option value="NO" <?= $row_view_residence['pwd'] == 'NO'? 'selected': '' ?>>NO</option>
        </select>
        <input type="hidden" id="edit_pwd_check" value="false">
      </td>
    <td >
        TYPE OF PWD
          <br>
          <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['pwd_info'] ?>"  name="edit_pwd_info" id="edit_pwd_info" <?= $row_view_residence['pwd_info'] == ''? 'disabled': '' ?>> 
        <input type="hidden" id="edit_pwd_info_check" value="false">
      </td>
      <td>
        GENDER
        <br>
        <select name="edit_gender" id="edit_gender" class="form-control">
          <option value="Male" <?= $row_view_residence['gender'] == 'Male'? 'selected': '' ?>>Male</option>
          <option value="Female" <?= $row_view_residence['gender'] == 'Female'? 'selected': '' ?>>Female</option>
        </select>
        <input type="hidden" id="edit_gender_check" value="false">
      </td>
      <td>
        CIVIL STATUS
        <br>
        <select name="edit_civil_status" id="edit_civil_status" class="form-control">
          <option value="Single" <?= $row_view_residence['civil_status'] == 'Single'? 'selected': ''; ?>>Single</option>
          <option value="Married" <?= $row_view_residence['civil_status'] == 'Married'? 'selected': ''; ?>>Married</option>
        </select>
        <input type="hidden" id="edit_civil_status_check" value="false">
      </td>
    
         
    </tr>

    <tr>
    <td >
        RELIGION
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['religion'] ?>" name="edit_religion" id="edit_religion">
        <input type="hidden" id="edit_religion_check" value="false">
      </td> 
    <td>
        NATIONALITY
        <br>
          <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['nationality'] ?>" name="edit_nationality" id="edit_nationality">
          <input type="hidden" id="edit_nationality_check" value="false">
      </td> 
      <td>
       MUNICIPALITY
        <br>
       <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['municipality'] ?>" name="edit_municipality" id="edit_municipality">
       <input type="hidden" id="edit_municipality_check" value="false">
      </td>
      <td>
        ZIP
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['zip'] ?>" name="edit_zip" id="edit_zip">
        <input type="hidden" id="edit_zip_check" value="false">
      </td>
     
    </tr>

    <tr>
    <td>
        BARANGAY
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['barangay'] ?>" name="edit_barangay" id="edit_barangay">
        <input type="hidden" id="edit_barangay_check" value="false">
      </td>
      <td>
        HOUSE NUMBER
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['house_number'] ?>" name="edit_house_number" id="edit_house_number">
        <input type="hidden" id="edit_house_number_check" value="false">
      </td>
      <td>
        STREET
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['street'] ?>" name="edit_street" id="edit_street">
        <input type="hidden" id="edit_street_check" value="false">
      </td>
      <td colspan="2">
        ADDRESS
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['address'] ?>" name="edit_address" id="edit_address">
        <input type="hidden" id="edit_address_check" value="false">
      </td>      
    </tr>

    <tr>
      <td colspan="2">
        EMAIL ADDRESS
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['email_address'] ?>" name="edit_email_address" id="edit_email_address">
        <input type="hidden" id="edit_email_address_check" value="false">
      </td>
      <td colspan="2">
        CONTACT NUMBER
        <br>
        <input type="text" maxlength="11" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['contact_number'] ?>" name="edit_contact_number" id="edit_contact_number">
        <input type="hidden" id="edit_contact_number_check" value="false">
      </td>         
    </tr>

    <tr>
      <td colspan="2">
        FATHER'S NAME
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['fathers_name'] ?>" name="edit_fathers_name" id="edit_fathers_name">
        <input type="hidden" id="edit_fathers_name_check" value="false">
      </td>
      <td colspan="2">
        MOTHER'S NAME
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['mothers_name'] ?>" name="edit_mothers_name" id="edit_mothers_name">
        <input type="hidden" id="edit_mothers_name_check" value="false">
      </td>         
    </tr>

    <tr>
      <td colspan="2">
        GUARDIAN
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_view_residence['guardian'] ?>" name="edit_guardian" id="edit_guardian">
        <input type="hidden" id="edit_guardian_check" value="false">
      </td>
      <td colspan="2">
        GUARDIAN CONTACT
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" maxlength="11" value="<?= $row_view_residence['guardian_contact'] ?>" name="edit_guardian_contact" id="edit_guardian_contact">
        <input type="hidden" id="edit_guardian_contact_check" value="false">
      </td>         
    </tr>

    <?php require __DIR__ . '/../includes/partials/residence_family_modal_fields.php'; ?>
  
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
                  <th class="d-none test">Color</th>
                  <th>Blotter Number</th>
                  <th>Status</th>
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



  

</div>


               
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="closeModal" class="btn btn-secondary btn-flat elevation-5" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
        <?php if (!empty($canEditOrDeletePerson)): ?>
        <button type="submit" class="btn btn-primary btn-flat elevation-5"><i class="fas fa-edit"></i> EDIT DETAILS</button>
        <?php endif; ?>
      </div>

      </form>
    </div>
  </div>
</div>

<div id="viewResidentRecord"></div>

<script>
  $(document).ready(function(){
    var canEditOrDeletePerson = <?= !empty($canEditOrDeletePerson) ? 'true' : 'false' ?>;
    if (!canEditOrDeletePerson) {
      $('#editResidenceForm').find('input:not([type=hidden]), select, textarea').prop('disabled', true);
      $('#display_edit_image_residence').css('cursor', 'default');
    }

    if (typeof initResidenceFamilyForm === 'function') {
      initResidenceFamilyForm();
    }
    
    blotterPersonTable()
    deleteResidenceRecord();
    deleteResidenceRecordPerson();
    viewResidentRecord()

   
   

    function viewResidentRecord(){

      $(document).on('click','.viewRecord',function(){

        var id = $(this).attr('id');
        $('#viewResidentRecord').html('')
        
        $.ajax({
        url: 'viewRecordResident.php',
        type: 'POST',
        data:{
          id:id,
        
        },
        success:function(data){
          $('#viewResidentRecord').html(data)
          $("#viewResidentRecordModal").modal('show');
           
        }
      }).fail(barangayAjaxError)
        


      })


      

    }
 

    

    


    function deleteResidenceRecord(){
      $(document).on('click','.deleteRecordComplainant',function(){
       
     var complainant_id =   $(this).attr('data-id');
     var blotter_id =   $(this).attr('id');

        Swal.fire({
            title: '<strong class="text-danger">Are you sure?</strong>',
            html: "You want Delete Record",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            allowOutsideClick: false,
            confirmButtonText: 'Yes, Delete it!',
            width: '400px',
          }).then((result) => {
            if (result.value) {
              $.ajax({
                url: 'deleteComplainantRecord.php',
                type: 'POST',
                data:{
                  complainant_id:complainant_id,
                  blotter_id:blotter_id
                },
                success:function(data){
                    Swal.fire({
                      title: '<strong class="text-success">SUCCESS</strong>',
                      type: 'success',
                      html: '<b>Delete Resident Record has Successfully<b>',
                      width: '400px',
                      confirmButtonColor: '#6610f2',
                      allowOutsideClick: false,
                      showConfirmButton: false,
                      timer: 2000,
                    }).then(()=>{
                      
                      $("#blotterPersonTable").DataTable().ajax.reload();
                    })
                }
              }).fail(barangayAjaxError)
            }
          })

      })
    }


    function deleteResidenceRecordPerson(){
      $(document).on('click','.deleteRecordPerson',function(){
       
     var person_id =   $(this).attr('data-id');
     var blotter_id =   $(this).attr('id');

        Swal.fire({
            title: '<strong class="text-danger">Are you sure?</strong>',
            html: "You want Delete Record",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            allowOutsideClick: false,
            confirmButtonText: 'Yes, Delete it!',
            width: '400px',
          }).then((result) => {
            if (result.value) {
              $.ajax({
                url: 'deletePersonRecord.php',
                type: 'POST',
                data:{
                  person_id:person_id,
                  blotter_id:blotter_id
                },
                success:function(data){
                    Swal.fire({
                      title: '<strong class="text-success">SUCCESS</strong>',
                      type: 'success',
                      html: '<b>Delete Resident Record has Successfully<b>',
                      width: '400px',
                      confirmButtonColor: '#6610f2',
                      allowOutsideClick: false,
                      showConfirmButton: false,
                      timer: 2000,
                    }).then(()=>{
                      
                      $("#blotterPersonTable").DataTable().ajax.reload();
                    })
                }
              }).fail(barangayAjaxError)
            }
          })

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
            
        },
        // drawCallback: function (settings) {
        //   $(".viewRecord").tooltip({
      
        //       delay: 500,
        //       placement: 'left',
        //       title: userDetails,
        //       html: true

        //     })
        //   },
    

  })

      // function userDetails(){
      //     var id = $(this).attr('id');
      //     var data_id = $(this).data('id');
      //     var tooltipText = '';
      //     $.ajax({
      //       url: 'viewRecordResident.php',
      //       type: 'POST',
      //       async: false,
      //       data:{
      //         id:id,
      //         data_id:data_id,
      //       },
      //       success:function(data){
      //         tooltipText = data;
      //       }

      //     })

      //     return tooltipText;
      //   }
}

    $(function () {

      $("#edit_pwd").change(function(){
        if (!canEditOrDeletePerson) {
          return;
        }
        var edit_pwd_one = $(this).val();


        if(edit_pwd_one == 'YES'){
          $("#edit_pwd_info").prop('disabled', false)
        }else{
          $("#edit_pwd_info").prop('disabled', true)
        }


      })

           var edit_first_name = $("#edit_first_name").val();
            var edit_last_name = $("#edit_last_name").val();
            var edit_term_from = $("#edit_term_from").val();
            var edit_term_to = $("#edit_term_to").val();
            var edit_voters = $("#edit_voters").val();
            var edit_pwd = $("#edit_pwd").val();
            var edit_birth_date = $("#edit_birth_date").val();
            var edit_birth_place = $("#edit_birth_place").val();
            var edit_middle_name = $("#edit_middle_name").val();
            var edit_suffix = $("#edit_suffix").val();
            var edit_gender = $("#edit_gender").val();
            var edit_vivil_status = $("#edit_vivil_status").val();
            var edit_nationality = $("#edit_nationality").val();
            var edit_municipality = $("#edit_municipality").val();
            var edit_zip = $("#edit_zip").val();
            var edit_barangay = $("#edit_barangay").val();
            var edit_house_number = $("#edit_house_number").val();
            var edit_street = $("#edit_street").val();
            var edit_address = $("#edit_address").val();
            var edit_email_address = $("#edit_email_address").val();
            var edit_contact_number = $("#edit_contact_number").val();
            var edit_fathers_name = $("#edit_fathers_name").val();
            var edit_mothers_name = $("#edit_mothers_name").val();
            var edit_guardian = $("#edit_guardian").val();
            var edit_guardian_contact = $("#edit_guardian_contact").val();
            var edit_pwd_info = $("#edit_pwd_info").val();
            var edit_single_parent = $("#edit_single_parent").val();
            var edit_indigenous = $("#edit_indigenous").val();


            $("#edit_pwd_info").change(function(){

              var newPwdIfo = $(this).val();

              if(!(newPwdIfo == edit_pwd_info )){

                $("#edit_pwd_info_check").val('true');

              }else{

                $("#edit_pwd_info_check").val('false');
              }

            })

            $("#edit_single_parent").change(function(){

              var newSingleParent = $(this).val();

              if(!(newSingleParent == edit_single_parent )){

                $("#edit_single_parent_check").val('true');

              }else{

                $("#edit_single_parent_check").val('false');
              }

            })

            $("#edit_indigenous").change(function(){

              var newIndigenous = $(this).val();

              if(!(newIndigenous == edit_indigenous )){

                $("#edit_indigenous_check").val('true');

              }else{

                $("#edit_indigenous_check").val('false');
              }

            })


            $("#edit_first_name").change(function(){

                var newFirstName = $(this).val();

                if(!(newFirstName == edit_first_name )){

                  $("#edit_first_name_check").val('true');

                }else{

                  $("#edit_first_name_check").val('false');
                }

            })



              $("#edit_last_name").change(function(){

                var newLastName = $(this).val();

                if(!(newLastName == edit_last_name )){

                  $("#edit_last_name_check").val('true');

                }else{

                  $("#edit_last_name_check").val('false');

                }

              })

          

                $("#edit_voters").change(function(){

                  var newVoters = $(this).val();

                  if(!(newVoters == edit_voters )){

                  $("#edit_voters_check").val('true');

                  }else{

                  $("#edit_voters_check").val('false');

                  }

                })

                $("#edit_pwd").change(function(){

                  var newPwd = $(this).val();

                  if(!(newPwd == edit_pwd )){

                  $("#edit_pwd_check").val('true');

                  }else{

                  $("#edit_pwd_check").val('false');

                  }

                })

                $("#edit_birth_date").change(function(){

                  var newBday = $(this).val();

                  if(!(newBday == edit_birth_date )){

                  $("#edit_birth_date_check").val('true');

                  }else{

                  $("#edit_birth_date_check").val('false');

                  }

                })

                $("#edit_birth_place").change(function(){

                  var newBplace = $(this).val();

                  if(!(newBplace == edit_birth_place )){

                  $("#edit_birth_place_check").val('true');

                  }else{

                  $("#edit_birth_place_check").val('false');

                  }

                })

                $("#edit_middle_name").change(function(){

                  var newMiddleName = $(this).val();

                  if(!(newMiddleName == edit_middle_name )){

                  $("#edit_middle_name_check").val('true');

                  }else{

                  $("#edit_middle_name_check").val('false');

                  }

                })

                $("#edit_suffix").change(function(){

                  var new_suffix = $(this).val();

                  if(!(new_suffix == edit_suffix )){

                  $("#edit_suffix_check").val('true');

                  }else{

                  $("#edit_suffix_check").val('false');

                  }

                })

                $("#edit_gender").change(function(){

                  var newGender = $(this).val();

                  if(!(newGender == edit_gender )){

                  $("#edit_gender_check").val('true');

                  }else{

                    $("#edit_gender_check").val('false');

                  }

                })

                $("#edit_civil_status").change(function(){

                  var newCivil = $(this).val();

                  if(!(newCivil == edit_civil_status )){

                  $("#edit_civil_status_check").val('true');

                  }else{

                    $("#edit_civil_status_check").val('false');
                  }

                })


                $("#edit_religion").change(function(){

                  var newReligion = $(this).val();

                  if(!(newReligion == edit_religion )){

                  $("#edit_religion_check").val('true');

                  }else{

                    $("#edit_religion_check").val('false');
                  }

                  })


                $("#edit_nationality").change(function(){

                var newNationality = $(this).val();

                if(!(newNationality == edit_nationality )){

                $("#edit_nationality_check").val('true');

                }else{

                $("#edit_nationality_check").val('false');
                }

                })

                $("#edit_municipality").change(function(){

                var newMunicipality = $(this).val();

                if(!(newMunicipality == edit_municipality )){

                $("#edit_municipality_check").val('true');

                }else{

                $("#edit_municipality_check").val('false');
                }

                })



                $("#edit_zip").change(function(){

                var newZip = $(this).val();

                if(!(newZip == edit_zip )){

                $("#edit_zip_check").val('true');

                }else{

                $("#edit_zip_check").val('false');
                }

                })


                $("#edit_barangay").change(function(){

                var newBarangay = $(this).val();

                if(!(newBarangay == edit_barangay )){

                $("#edit_barangay_check").val('true');

                }else{

                $("#edit_barangay_check").val('false');
                }

                })

                $("#edit_house_number").change(function(){

                var newHnumber = $(this).val();

                if(!(newHnumber == edit_house_number )){

                $("#edit_house_number_check").val('true');

                }else{

                $("#edit_house_number_check").val('false');
                }

                })

                $("#edit_street").change(function(){

                var newStreet = $(this).val();

                if(!(newStreet == edit_street )){

                $("#edit_street_check").val('true');

                }else{

                $("#edit_street_check").val('false');
                }

                })

                $("#edit_address").change(function(){

                var newAddress = $(this).val();

                if(!(newAddress == edit_address )){

                $("#edit_address_check").val('true');

                }else{

                $("#edit_address_check").val('false');
                }

                })

                $("#edit_email_address").change(function(){

                var newEmail = $(this).val();

                if(!(newEmail == edit_email_address )){

                $("#edit_email_address_check").val('true');

                }else{

                $("#edit_email_address_check").val('false');
                }

                })

                $("#edit_contact_number").change(function(){

                var newNumber = $(this).val();

                if(!(newNumber == edit_contact_number )){

                $("#edit_contact_number_check").val('true');

                }else{

                $("#edit_contact_number_check").val('false');
                }

                })

                $("#edit_fathers_name").change(function(){

                var newtatay = $(this).val();

                if(!(newtatay == edit_fathers_name )){

                $("#edit_fathers_name_check").val('true');

                }else{

                $("#edit_fathers_name_check").val('false');
                }

                })

                $("#edit_mothers_name").change(function(){

                var newNanay = $(this).val();

                if(!(newNanay == edit_mothers_name )){

                $("#edit_mothers_name_check").val('true');

                }else{

                $("#edit_mothers_name_check").val('false');
                }

                })

                $("#edit_guardian").change(function(){

                var newGuardian = $(this).val();

                if(!(newGuardian == edit_guardian )){

                $("#edit_guardian_check").val('true');

                }else{

                $("#edit_guardian_check").val('false');
                }

                })

                $("#edit_guardian_contact").change(function(){

                var newGcontact = $(this).val();

                if(!(newGcontact == edit_guardian_contact )){

                $("#edit_guardian_contact_check").val('true');

                }else{

                  $("#edit_guardian_contact_check").val('false');
                }

                })



        $.validator.setDefaults({
          submitHandler: function (form) {
            Swal.fire({
              title: '<strong class="text-warning">Are you sure?</strong>',
              html: "<b>You want edit this details?</b>",
              type: 'info',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, edit it!',
              allowOutsideClick: false,
              width: '400px',
            }).then((result) => {
              if (result.value) {

                var formData = new FormData(form)
                  
                  formData.append("edit_first_name_check",$("#edit_first_name_check").val())
                  formData.append("edit_last_name_check",$("#edit_last_name_check").val())
                  formData.append("edit_voters_check",$("#edit_voters_check").val())
                  formData.append("edit_pwd_check",$("#edit_pwd_check").val())
                  formData.append("edit_birth_date_check",$("#edit_birth_date_check").val())
                  formData.append("edit_birth_place_check",$("#edit_birth_place_check").val())
                  formData.append("edit_middle_name_check",$("#edit_middle_name_check").val())
                  formData.append("edit_suffix_check",$("#edit_suffix_check").val())
                  formData.append("edit_gender_check",$("#edit_gender_check").val())
                  formData.append("edit_civil_status_check",$("#edit_civil_status_check").val())
                  formData.append("edit_religion_check",$("#edit_religion_check").val())
                  formData.append("edit_nationality_check",$("#edit_nationality_check").val())
                  formData.append("edit_municipality_check",$("#edit_municipality_check").val())
                  formData.append("edit_zip_check",$("#edit_zip_check").val())
                  formData.append("edit_barangay_check",$("#edit_barangay_check").val())
                  formData.append("edit_house_number_check",$("#edit_house_number_check").val())
                  formData.append("edit_street_check",$("#edit_street_check").val())
                  formData.append("edit_address_check",$("#edit_address_check").val())
                  formData.append("edit_email_address_check",$("#edit_email_address_check").val())
                  formData.append("edit_contact_number_check",$("#edit_contact_number_check").val())
                  formData.append("edit_fathers_name_check",$("#edit_fathers_name_check").val())
                  formData.append("edit_mothers_name_check",$("#edit_mothers_name_check").val())
                  formData.append("edit_guardian_check",$("#edit_guardian_check").val())
                  formData.append("edit_guardian_contact_check",$("#edit_guardian_contact_check").val())
                  formData.append("edit_pwd_info_check",$("#edit_pwd_info_check").val())
                  formData.append("edit_single_parent_check",$("#edit_single_parent_check").val())
                  formData.append("edit_indigenous_check",$("#edit_indigenous_check").val())
                  

                  $.ajax({
                    url: 'editResidence.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    success:function(data){
                      Swal.fire({
                        title: '<strong class="text-success">SUCCESS</strong>',
                        type: 'success',
                        html: '<b>Updated Residence has Successfully<b>',
                        width: '400px',
                        confirmButtonColor: '#6610f2',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        timer: 2000,
                      }).then(()=>{
                        
                        $("#allResidenceTable").DataTable().ajax.reload();
                        $("#archiveResidenceTable").DataTable().ajax.reload();
                        
                      })
                    }
                }).fail(barangayAjaxError)
              }
            })
            
          }
        });
      $('#editResidenceForm').validate({
        rules: {
          edit_first_name: {
            required: true,
            minlength: 2
          },
          edit_last_name: {
            required: true,
            minlength: 2
          },
          edit_birth_date: {
            required: true,
          },
          edit_address:{
            required: true,
          },
          edit_contact_number:{
            required: true,
            minlength: 11
          },
          edit_email_address:{
            email: true,
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

          edit_birth_date: {
            required: "<span class='text-danger text-bold'>Birth Date is Required</span>",
          },
          edit_address: {
            required: "<span class='text-danger text-bold'>Address is Required</span>",
          },
          edit_contact_number: {
            required: "<span class='text-danger text-bold'>Contact Number is Required</span>",
            minlength: "<span class='text-danger'>Input Exact Contact Number</span>"
          },
          edit_email_address:{
            email:"<span class='text-danger text-bold'>Enter Valid Email!</span>",
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

    
    $('#display_edit_image_residence').on('click',function(){
      if (!canEditOrDeletePerson) {
        return;
      }
      $("#edit_image_residence").click();
    })
    $("#edit_image_residence").change(function(){
        editDsiplayImage(this);
      })

    


      function editDsiplayImage(input){
        if(input.files && input.files[0]){
          var reader = new FileReader();
          var edit_image_residence = $("#edit_image_residence").val().split('.').pop().toLowerCase();

          if(edit_image_residence != ''){
            if(jQuery.inArray(edit_image_residence, ['gif','png','jpeg','jpg']) == -1){
              Swal.fire({
                title: '<strong class="text-danger">ERROR</strong>',
                type: 'error',
                html: '<b>Invalid Image File<b>',
                width: '400px',
                confirmButtonColor: '#6610f2',
              })
              $("#edit_image_residence").val('');
              $("#display_edit_image_residence").attr('src', '<?= $row_view_residence['image_path'] ?>');
              return false;
            }
          }
            reader.onload = function(e){
              $("#display_edit_image_residence").attr('src', e.target.result);
              $("#display_edit_image_residence").hide();
              $("#display_edit_image_residence").fadeIn(650);
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

 
  $("#edit_contact_number, #edit_zip, #edit_guardian_contact, #edit_spouse_contact, #edit_age, .residence-family-number").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });


  $("#edit_first_name, #edit_middle_name, #edit_last_name, #edit_suffix, #edit_religion, #edit_nationality, #edit_municipality, #edit_fathers_name, #edit_mothers_name, #edit_guardian, #edit_spouse_first_name, #edit_spouse_middle_name, #edit_spouse_last_name, #edit_spouse_suffix, .residence-family-name").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
  $("#edit_street, #edit_birth_place, #edit_house_number").inputFilter(function(value) {
  return /^[0-9a-z, ,-]*$/i.test(value); 
  });

</script>