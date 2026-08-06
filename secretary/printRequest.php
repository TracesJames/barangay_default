<?php 

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
try{

  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'secretary'){

    $row_barangay_information = barangay_load_active($con) ?? [];
    require_once '../includes/certificate_print_setup.php';

    if(isset($_REQUEST['request']) && isset($_REQUEST['purpose'])){
      $resident_id = $con->real_escape_string(($_REQUEST['request']));
      $id = $con->real_escape_string(($_REQUEST['purpose']));


      $sql = "SELECT certificate_request.certificate_type, certificate_request.purpose, residence_information.first_name, residence_information.middle_name, residence_information.last_name,
      residence_information.age, residence_information.civil_status,residence_information.gender
      FROM certificate_request LEFT JOIN residence_information ON certificate_request.residence_id = residence_information.residence_id
      WHERE id = '$id' AND certificate_request.residence_id = '$resident_id'";
      $query = $con->query($sql) or die ($con->error);
      $row = $query->fetch_assoc();

      if($row['gender'] == 'Male'){
        $gender = 'He';
      }else{
        $gender = 'She';
      }

      date_default_timezone_set('Asia/Manila');
      $today = date('jS');   
      $month = date("F");
      $year = date("Y");

      if($row['middle_name'] != ''){
        $middle_name_resident = $row['middle_name'][0].'. ';
      }else{
        $middle_name_resident = '';
      }

    }

   




    
  
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

 

  <!-- Theme style -->
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<?php require_once '../includes/certificate_print_styles.php'; ?>
<?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>

<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body>


 

<?php 


if($count_official != 0){

  ?>

<div class="container certificate-document" data-report-fit="root">

<div class="d-flex justify-content-around">
  <div class="certificate-logo-wrap">
    <?= $image ?>
  </div>
  <div class=" text-center" style="font-size:17pt; font-weight: 500">
    <br>
    <?php include __DIR__ . '/../includes/certificate_letterhead.php'; ?>
  </div>
  <div class="certificate-logo-wrap">
    <?= $cityLogoHtml ?>
  </div>
</div>

<hr style="height: 10px; background-color: skyblue;">
    <br>
    <br>
  
  
  <div class="container text-lg transparentbox certificate-body" id="qwe">
    <?= $certificateWatermarkHtml ?? '' ?>
    
    <div class="d-flex justify-content-center"><h1 class="font-weight-bolder" style="font-size: 50px;">CERTIFICATION</h1> </div>
    <span style="font-size:20pt">TO WHOM IT MAY CONCERN:</span>
    <br>
    <br>

    <p class="pl-5 ml-5" style="font-size:20pt; padding: 0; margin: 0;">This is certify that <b><u style="text-transform: uppercase"><?= $row['first_name'] .' '.  $middle_name_resident . $row['last_name'].', '. $row['age']?></u></b> years of age, <?= $row['civil_status'] ?></p>
    <p class="pl-5"  style="font-size:20pt; ">whose signature appears below is a bonafide resident of this Barangay with postal address <b><?= $row_barangay_information['postal_address'] ?></b></p>
 
    <p class="pl-5 ml-5" style="font-size:20pt; padding: 0; margin: 0;">He/She is a person of good moral character and a law-abiding citizen of </p>
    <p class="pl-5"  style="font-size:20pt; "><?= barangay_h(barangay_certificate_location_label($row_barangay_information)) ?>. As per record, He/She has no derogatroy, no criminal record has been file against him/her in the Barangay as of this date.</p>
    <br>
    <p class="pl-5 ml-5" style="font-size:20pt; padding: 0; margin: 0;">This certification is being issued upon the request of the person </p>
    <p class="pl-5"  style="font-size:20pt; ">mentioned above for <b><?= strtoupper($row['purpose']) ?>.</b></p>

    <p class="pl-5 ml-5" style="font-size:20pt; padding: 0; margin: 0;">Done in the <?= barangay_h(barangay_certificate_header($row_barangay_information)['done_in']) ?> this <b><u> <?= '_'.$today .'_' ?> </u> day of <?= $month ?>, <u><?= $year?></u>.<b>  </p>
    <br>
    
    <div class="d-flex justify-content-around">
      <div> 
     
      <br><br>
        <br>
        <br>
        <br>
       
        <hr style="height: 5x; background-color: black; margin: 0; padding:0;">
        <p style="margin: 0; padding:0;">
      
     
      SIGNATURE OVER PRINTED NAME</p>
    </div>
     <div> <p></p></div>
     
      <div class="punong-barangay-block text-center">
      <?= $official_image ?>
      <p><?= barangay_h($punongBarangayName !== '' ? $punongBarangayName : trim(($row_official['first_name'] ?? '') . ' ' . $official_middle_name . ' ' . ($row_official['last_name'] ?? ''))) ?><br><span class="punong-title">Punong Barangay</span></p>
      </div>
    
    </div>

    <div class="text-center">
      <p class="mb-0">Barangay Officials</p>
      <p class="p-0 m-0" style="font-weight: 700"> 
          <?php
          $i = 0;
          $officialDisplayWhere = barangay_officials_where_clause($con);
          $sql_official_display = "SELECT official_status.position, position.position as official_position, official_information.first_name, official_information.middle_name, official_information.last_name FROM official_status
          INNER JOIN position ON official_status.position = position.position_id 
          INNER JOIN official_information ON official_status.official_id = official_information.official_id";
          if ($officialDisplayWhere !== []) {
            $sql_official_display .= ' WHERE ' . implode(' AND ', $officialDisplayWhere);
          }
          $sql_official_display .= ' ORDER BY position.position ASC';
                $query_official_display = $con->query($sql_official_display) or die ($con->error);
          while($row_official_display = $query_official_display->fetch_assoc()){ 
            
            if($row_official_display['middle_name'] != ''){
              $official_middle_name_display = $row_official_display['middle_name'][0].'.';
            }else{
              $official_middle_name_display = ' ';
            }
            
            ?>
            
                  <?= ucfirst($row_official_display['official_position']) .'. '. $row_official_display['first_name'].' ' . $official_middle_name_display .' '. $row_official_display['last_name'] ?>,
          
              <?php
              $i++;
              if($i % 3 == 0) {
                  echo '<br />';
              }
          }
          ?>
          </p>
          <p class="m-0 p-0" style="font-size: 15px"> VALID WITH SIGNATURE OF PUNONG BARANGAY ONLY.</p>
          <p class="m-0 p-0" style="font-size: 10px"><?= $row_barangay_information['barangay'] ?> ASENSO GARANTISADO</p>
          <p class="m-0 p-0" style="font-size: 10px">Note: Not Valid Without Barangay Dry Seal</p>
  
    </div>
 
  </div>


</div>





  <?php

}else{

  echo '<h1 style="font-size: 150px">NO CHAIRMAN OR OFFICAL</h1>';

}


?>













 
 
 
<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>

<!-- Bootstrap -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.js"></script>

<script>
$(document).ready(function(){
  
   
    var printContents = $("body").html();
     var originalContents = document.body.innerHTML;
     document.body.innerHTML = printContents;
     window.print();
     document.body.innerHTML = originalContents;
     setTimeout(function(){ 
             window.close();
  }, 5000);
  
})
</script>


</body>
</html>


              