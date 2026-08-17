<?php 

include_once '../connection.php';
include_once '../includes/auth_resident.php';
try{
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident'){

    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $first_name_user = $row_user['first_name'];
    $last_name_user = $row_user['last_name'];
    $user_type = $row_user['user_type'];
    $user_image = $row_user['image'];


    $sql_resident = "SELECT residence_information.*, residence_status.* FROM residence_information
    INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id
     WHERE residence_information.residence_id = '$user_id'";
    $query_resident = $con->query($sql_resident) or die ($con->error);
    $row_resident = $query_resident->fetch_assoc();



  }else{
   echo '<script>
          window.location.href = "../login.php";
        </script>';
  }

}catch(Exception $e){
  echo $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>

 
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">

  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/css/resident-portal.css">
  <style>
    


    
    #barangay_logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }

    .logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }
    .wrapper{
      background-image: url('../assets/logo/cover.jpg');
      background-repeat:no-repeat;

background-size: cover;
background-position:center;
width: 100%;
  height: auto;
        animation-name: example;
        animation-duration: 5s;
       
       
    }


@keyframes example {
  from {opacity: 0;}
  to {opacity: 1.5;}
}

.dark-mode .custom-control-label::before, .dark-mode .custom-file-label, .dark-mode .custom-file-label::after, .dark-mode .custom-select, .dark-mode .form-control:not(.form-control-navbar):not(.form-control-sidebar), .dark-mode .input-group-text {
      background-color: transparent;
    color: #fff;
}


    .editInfo {
    background-color:rgba(0, 0, 0, 0);
    color:#fff;
    border: none;
    outline:none;
    width: 100%;
    }
    .editInfo:focus {
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
      background-color: transparent;
    color: #fff;
    }
    #edit_gender, #edit_civil_status, #edit_voters, #edit_pwd, #edit_single_parent, option:focus{
      outline:none;
      border:none;
      box-shadow:none;
      background-color: transparent;
    color: #fff;
    }

    /* For IE10 */
    #edit_gender, #edit_civil_status, #edit_voters, #edit_pwd,#edit_single_parent select::-ms-expand {
      display: none;
      background-color: transparent;
    color: #fff;
    }
    select option {

    background: #343a40;
    color: #fff;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.4);
}
#display_edit_image_residence{
      width: 140px;
      height: 140px;
      max-width: 140px;
      object-fit: cover;
      object-position: center;
      border-radius: 50%;
    }



  </style>
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="layout-top-nav dark-mode barangay-portal">

<div class="wrapper  p-0 maring-0 bg-transparent" >

  <?php require_once '../includes/partials/resident_navbar.php'; ?>
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper"  style="background-color: transparent">
    <!-- Content Header (Page header) -->
 
    
  
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content  " >
  



    <div class="container-fluid pt-5">
        <form id="editResidenceForm" method="post" enctype="multipart/form-data">

          <div class="card card-widget widget-user resident-panel-card">
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-dark pl-5">
                <h3 class="widget-user-username"><br></h3>
                <h5 class="widget-user-desc">RESIDENT NO. <?= $row_resident['residence_id'] ?></h5>
              </div>
              <div class="widget-user-image tex">
                
              <?php 
                if($row_resident['image_path'] != '' || $row_resident['image_path'] != null || !empty($row_resident['image_path'])){
                  echo '<img src="'.$row_resident['image_path'].'" class="img-circle elevation-2" alt="User Image" id="display_edit_image_residence">';
                }else{
                  echo '<img src="../assets/dist/img/blank_image.png" class="img-circle elevation-2" alt="User Image" id="display_edit_image_residence">';
                }
              ?>

                    <input type="file" name="edit_image_residence" id="edit_image_residence" style="display: none;">

              
              </div>
              <div class="card-footer mt-4">
              <div class="table-responsive">
              <input type="hidden" name="edit_residence_id" value="<?= $row_resident['residence_id'];?>">
              <table  style="font-size:11pt;" class="table table-bordered">
    <tbody>
      
      <tr>
        <td colspan="3">
          <div class="d-flex justify-content-between">
            <div> FIRST NAME<br>
              <input type="text"  class="editInfo form-control form-control-sm"  value="<?= $row_resident['first_name'] ?>" id="edit_first_name" name="edit_first_name" size="30"> 
              <input type="hidden" value="false" id="edit_first_name_check"> 
            </div>
            <div>MIDDLE NAME<br>
              <input type="text"  class="editInfo  form-control form-control-sm " value="<?= $row_resident['middle_name'] ?>" id="edit_middle_name" name="edit_middle_name" size="20"> 
              <input type="hidden" id="edit_middle_name_check" value="false">
            </div>
            <div>      
              LAST NAME<br>
              <input type="text"  class="editInfo  form-control form-control-sm"  value="<?= $row_resident['last_name'] ?>" id="edit_last_name" name="edit_last_name" size="20"> 
              <input type="hidden" value="false" id="edit_last_name_check">
            </div>
            <div>      
              SUFFIX<br>
              <input type="text"  class="editInfo  form-control form-control-sm" value="<?= $row_resident['suffix'] ?>" id="edit_suffix" name="edit_suffix" size="5">  
              <input type="hidden" id="edit_suffix_check" value="false">
            </div>
          </div>
        </td>
      <td>
       VOTERS
        <br>
        <select name="edit_voters" id="edit_voters" class="form-control">
          <option value="NO" <?= $row_resident['voters'] == 'NO'? 'selected': '' ?>>NO</option>
          <option value="YES" <?= $row_resident['voters'] == 'YES'? 'selected': '' ?>>YES</option>
        </select>
        <input type="hidden" value="false" id="edit_voters_check">
      </td>
    </tr>
    <tr>
      <td>
         DATE OF BIRTH
          <br>
          
          <input type="date" class="editInfo  form-control form-control-sm" value="<?= barangay_html_date($row_resident['birth_date'] ?? '') ?>" name="edit_birth_date" id="edit_birth_date"/>
          <input type="hidden" id="edit_birth_date_check" value='false'>
      </td>
      <td>
        PLACE OF BIRTH
          <br>
        
        <input type="text" class="editInfo  form-control form-control-sm" value=" <?= $row_resident['birth_place'] ?>"  name="edit_birth_place" id="edit_birth_place" > 
        <input type="hidden" id="edit_birth_place_check" value="false">
      </td>
      <td >
        AGE
          <br>
       
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['age'] ?>"  name="edit_age" id="edit_age" disabled> 
      </td>
      <td >
        SINGLE PARENT
          <br>
          <select name="edit_single_parent" id="edit_single_parent" class="form-control">
            <option value="YES" <?= $row_resident['single_parent'] == 'YES'? 'selected': '' ?>>YES</option>
            <option value="NO" <?= $row_resident['single_parent'] == 'NO'? 'selected': '' ?>>NO</option>
        </select>
        <input type="hidden" id="edit_single_parent_check" value="false">
      </td>
   
   
    </tr>
    <tr>
    <td >
        PWD
          <br>
          <select name="edit_pwd" id="edit_pwd" class="form-control">
            <option value="YES" <?= $row_resident['pwd'] == 'YES'? 'selected': '' ?>>YES</option>
            <option value="NO" <?= $row_resident['pwd'] == 'NO'? 'selected': '' ?>>NO</option>
        </select>
        <input type="hidden" id="edit_pwd_check" value="false">
      </td>
    <td >
        TYPE OF PWD
          <br>
          <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['pwd_info'] ?>"  name="edit_pwd_info" id="edit_pwd_info" <?= $row_resident['pwd_info'] == ''? 'disabled': '' ?>> 
        <input type="hidden" id="edit_pwd_info_check" value="false">
      </td>
      <td>
        GENDER
        <br>
        <select name="edit_gender" id="edit_gender" class="form-control">
          <option value="Male" <?= $row_resident['gender'] == 'Male'? 'selected': '' ?>>Male</option>
          <option value="Female" <?= $row_resident['gender'] == 'Female'? 'selected': '' ?>>Female</option>
        </select>
        <input type="hidden" id="edit_gender_check" value="false">
      </td>
      <td>
        CIVIL STATUS
        <br>
        <select name="edit_civil_status" id="edit_civil_status" class="form-control">
          <option value="Single" <?= $row_resident['civil_status'] == 'Single'? 'selected': ''; ?>>Single</option>
          <option value="Married" <?= $row_resident['civil_status'] == 'Married'? 'selected': ''; ?>>Married</option>
        </select>
        <input type="hidden" id="edit_civil_status_check" value="false">
      </td>
    
         
    </tr>

    <tr>
    <td >
        RELIGION
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['religion'] ?>" name="edit_religion" id="edit_religion">
        <input type="hidden" id="edit_religion_check" value="false">
      </td> 
    <td>
        NATIONALITY
        <br>
          <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['nationality'] ?>" name="edit_nationality" id="edit_nationality">
          <input type="hidden" id="edit_nationality_check" value="false">
      </td> 
      <td>
       MUNICIPALITY
        <br>
       <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['municipality'] ?>" name="edit_municipality" id="edit_municipality">
       <input type="hidden" id="edit_municipality_check" value="false">
      </td>
      <td>
        ZIP
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['zip'] ?>" name="edit_zip" id="edit_zip">
        <input type="hidden" id="edit_zip_check" value="false">
      </td>
     
    </tr>

    <tr>
    <td>
        BARANGAY
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['barangay'] ?>" name="edit_barangay" id="edit_barangay">
        <input type="hidden" id="edit_barangay_check" value="false">
      </td>
      <td>
        HOUSE NUMBER
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['house_number'] ?>" name="edit_house_number" id="edit_house_number">
        <input type="hidden" id="edit_house_number_check" value="false">
      </td>
      <td>
        STREET
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['street'] ?>" name="edit_street" id="edit_street">
        <input type="hidden" id="edit_street_check" value="false">
      </td>
      <td colspan="2">
        ADDRESS
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['address'] ?>" name="edit_address" id="edit_address">
        <input type="hidden" id="edit_address_check" value="false">
      </td>      
    </tr>

    <tr>
      <td colspan="2">
        EMAIL ADDRESS
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['email_address'] ?>" name="edit_email_address" id="edit_email_address">
        <input type="hidden" id="edit_email_address_check" value="false">
      </td>
      <td colspan="2">
        CONTACT NUMBER
        <br>
        <input type="text" maxlength="11" class="editInfo  form-control form-control-sm" value="<?= $row_resident['contact_number'] ?>" name="edit_contact_number" id="edit_contact_number">
        <input type="hidden" id="edit_contact_number_check" value="false">
      </td>         
    </tr>

    <tr>
      <td colspan="2">
        FATHER'S NAME
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['fathers_name'] ?>" name="edit_fathers_name" id="edit_fathers_name">
        <input type="hidden" id="edit_fathers_name_check" value="false">
      </td>
      <td colspan="2">
        MOTHER'S NAME
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['mothers_name'] ?>" name="edit_mothers_name" id="edit_mothers_name">
        <input type="hidden" id="edit_mothers_name_check" value="false">
      </td>         
    </tr>

    <tr>
      <td colspan="2">
        GUARDIAN
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" value="<?= $row_resident['guardian'] ?>" name="edit_guardian" id="edit_guardian">
        <input type="hidden" id="edit_guardian_check" value="false">
      </td>
      <td colspan="2">
        GUARDIAN CONTACT
        <br>
        <input type="text" class="editInfo  form-control form-control-sm" maxlength="11" value="<?= $row_resident['guardian_contact'] ?>" name="edit_guardian_contact" id="edit_guardian_contact">
        <input type="hidden" id="edit_guardian_contact_check" value="false">
      </td>         
    </tr>
  
   </tbody>
  </table>
  </div>
                <button type="submit" class="btn btn-success elevation-5 px-3"><i class="fas fa-edit"></i>  UPDATE</button>
            </div>

        </div>





          
      




        
        </form>  
      </div><!--/. container-fluid -->

    


     
          
               
      
     
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  
 

 
  <footer class="main-footer barangay-footer text-white">
    <div class="float-right d-none d-sm-block">
    
    </div>
  <i class="fas fa-map-marker-alt"></i> <?= $postal_address ?> 
  </footer>
 


</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>

<!-- Bootstrap -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/popper/umd/popper.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../assets/plugins/jszip/jszip.min.js"></script>
<script src="../assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="../assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<script src="../assets/plugins/moment/moment.min.js"></script>
<script src="../assets/plugins/chart.js/Chart.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../assets/plugins/jquery-validation/additional-methods.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery-validate.bootstrap-tooltip.min.js"></script>

<script>
  $(document).ready(function(){

   

    $(function () {


      $("#edit_pwd").change(function(){
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
              html: "<b>You want edit your details?</b>",
              type: 'info',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, Edit it!',
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
                        html: '<b>Updated Your Details has Successfully<b>',
                        width: '400px',
                        confirmButtonColor: '#6610f2',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        timer: 2000,
                      }).then(()=>{
                        
                          window.location.reload();
                        
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
              $("#display_edit_image_residence").attr('src', '<?= $row_resident['image_path'] ?>');
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

 
  $("#edit_contact_number, #edit_zip, #edit_guardian_contact, #edit_age").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });


  $("#edit_first_name, #edit_middle_name, #edit_last_name, #edit_suffix, #edit_religion, #edit_nationality, #edit_municipality, #edit_fathers_name, #edit_mothers_name, #edit_guardian").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
  $("#edit_street, #edit_birth_place, #edit_house_number").inputFilter(function(value) {
  return /^[0-9a-z, ,-]*$/i.test(value); 
  });

</script>

</body>
</html>
