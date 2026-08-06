<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';

$activeBarangay = barangay_require_active($con, 'barangayHub.php');
$barangay_id = $activeBarangay['id'];

try{





  if(!empty($_POST['complainant_residence'])){
    $complainant_resident = $_POST['complainant_residence'];
   
  }else{
    $complainant_resident = [
      ''
    ];
  }

  if(!empty($_POST['person_involed'])){
    $person_involed = $_POST['person_involed'];
   
  }else{
    $person_involed = [
      ''
    ];
  }

  $complainant_not_residence = $con->real_escape_string($_POST['complainant_not_residence']);
  $complainant_statement = $con->real_escape_string($_POST['complainant_statement']);
  $respodent = $con->real_escape_string($_POST['respodent']);
  $person_statement = $con->real_escape_string($_POST['person_statement']);
  $person_involevd_not_resident = $con->real_escape_string($_POST['person_involevd_not_resident']);
  $location_incident = $con->real_escape_string($_POST['location_incident']);
  $date_of_incident = $con->real_escape_string($_POST['date_of_incident']);
  $incident = $con->real_escape_string($_POST['incident']);
  $status = $con->real_escape_string($_POST['status']);
  $date_reported = $con->real_escape_string($_POST['date_reported']);
  $remarks = $con->real_escape_string($_POST['remarks']);


  $date_report =  date("Y");

    date_default_timezone_set('Asia/Manila');
    $date_main = new DateTime();
    $asd = hexdec(uniqid());
    $blotter_id_main = rand($date_main->format("mdYHisv"),$asd);
    



  foreach($complainant_resident as $resident){
 

  
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $blotter_id = rand($date->format("mdYHIsv"),2);
    $sql_blotter_complainant = "INSERT INTO `blotter_complainant`(`id`,`blotter_main`,`complainant_id`) VALUES (?,?,?)";
    $query_blotter_complainant = $con->prepare($sql_blotter_complainant) or die ($con->error);
    $query_blotter_complainant->bind_param('sss',$blotter_id,$blotter_id_main,$resident);
    $query_blotter_complainant->execute();
    $query_blotter_complainant->close();

    if($resident != ''){

      $sql_resident_complainant = "SELECT first_name, last_name FROM residence_information WHERE residence_id = ?";
      $stmt_resident_complaiannt = $con->prepare($sql_resident_complainant) or die ($con->error);
      $stmt_resident_complaiannt->bind_param('s',$resident);
      $stmt_resident_complaiannt->execute();
      $result_resident_complainant = $stmt_resident_complaiannt->get_result();
      $row_resident_complainant = $result_resident_complainant->fetch_assoc();
      $first_name_resident_complainant = $row_resident_complainant['first_name'];
      $last_name_resident_complainant = $row_resident_complainant['last_name'];
  
      $date_activity = $now = date("j-n-Y g:i A");  
      $admin = strtoupper('ADMIN').':' .' '. 'ADDED BLOTTER RECORD  - '.' ' .$blotter_id_main.' | Complainant - ' . $first_name_resident_complainant .' '. $last_name_resident_complainant .' | Incident - ' . $incident .' | Date Incident '. $date_of_incident .' | Location Incident '. $location_incident .' | Complainant Statement - '. $complainant_statement .' | Respondent - ' . $respodent;
      $status_activity_log = 'delete';
      $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
      $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
      $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
      $stmt_activity_log->execute();
      $stmt_activity_log->close();
  

    }

   


  
  }

  foreach($person_involed as $person){
 

    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $blotter_id = rand($date->format("mdYHIsv"),3);
   

    $sql_blotter_status = "INSERT INTO `blotter_status`(`blotter_id`,`blotter_main`,`person_id`) VALUES (?,?,?)";
    $query_blotter_status = $con->prepare($sql_blotter_status) or die ($con->error);
    $query_blotter_status->bind_param('sss',$blotter_id,$blotter_id_main,$person);
    $query_blotter_status->execute();
    $query_blotter_status->close();

    if($person != ''){

      $sql_resident_complainant = "SELECT first_name, last_name FROM residence_information WHERE residence_id = ?";
      $stmt_resident_complaiannt = $con->prepare($sql_resident_complainant) or die ($con->error);
      $stmt_resident_complaiannt->bind_param('s',$resident);
      $stmt_resident_complaiannt->execute();
      $result_resident_complainant = $stmt_resident_complaiannt->get_result();
      $row_resident_complainant = $result_resident_complainant->fetch_assoc();
      $first_name_resident_complainant = $row_resident_complainant['first_name'];
      $last_name_resident_complainant = $row_resident_complainant['last_name'];
  
  
      $date_activity = $now = date("j-n-Y g:i A");  
      $admin = strtoupper('ADMIN').':' .' '. 'ADDED BLOTTER RECORD  - '.' ' .$blotter_id_main.' | Person Involved - ' . $first_name_resident_complainant .' '. $last_name_resident_complainant .' | Incident - ' . $incident .' | Date Incident '. $date_of_incident .' | Location Incident '. $location_incident .' | Complainant Statement - '. $complainant_statement .' | Respondent - ' . $respodent;
      $status_activity_log = 'delete';
      $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
      $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
      $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
      $stmt_activity_log->execute();
      $stmt_activity_log->close();

    }


   


  }


 

  $sql_blotter=  "INSERT INTO `blotter_record`(
    `blotter_id`,
    `barangay_id`,
    `complainant_not_residence`, 
    `statement`, 
    `respodent`, 
    `involved_not_resident`,
    `date_incident`, 
    `date_reported`, 
    `type_of_incident`, 
    `location_incident`, 
    `status`, 
    `remarks`,
    `statement_person`,
    `date_added`)
   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
   $query_blotter = $con->prepare($sql_blotter) or die ($con->error);
   $query_blotter->bind_param('ssssssssssssss',
    $blotter_id_main,
    $barangay_id,
    $complainant_not_residence,
    $complainant_statement,
    $respodent,
    $person_involevd_not_resident,
    $date_of_incident,
    $date_reported,
    $incident,
    $location_incident,
    $status,
    $remarks,
    $person_statement,
    $date_report
  );
  $query_blotter->execute();
  $query_blotter->close();



  $date_activity = $now = date("j-n-Y g:i A");  
  $admin = strtoupper('ADMIN').':' .' '. 'ADDED BLOTTER RECORD  - '.' ' .$blotter_id_main.' | Person Not Resident - ' . $person_involevd_not_resident  .' | Incident - ' . $incident .' | Date Incident '. $date_of_incident .' | Location Incident '. $location_incident .' | Complainant Statement - '. $person_statement .' | Respondent - ' . $respodent;
  $status_activity_log = 'delete';
  $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
  $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
  $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
  $stmt_activity_log->execute();
  $stmt_activity_log->close();

  
  $date_activity = $now = date("j-n-Y g:i A");  
  $admin = strtoupper('ADMIN').':' .' '. 'ADDED BLOTTER RECORD  - '.' ' .$blotter_id_main.' | Complainant Not Resident - ' . $complainant_not_residence  .' | Incident - ' . $incident .' | Date Incident '. $date_of_incident .' | Location Incident '. $location_incident .' | Complainant Statement - '. $complainant_statement .' | Respondent - ' . $respodent;
  $status_activity_log = 'delete';
  $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
  $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
  $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
  $stmt_activity_log->execute();
  $stmt_activity_log->close();

  if($resident == '' && $person == ''){

    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('ADMIN').':' .' '. 'ADDED BLOTTER RECORD  - '.' ' .$blotter_id_main.' | Complainant Not Resident - ' . $complainant_not_residence  .' Complainant Statement - '.$complainant_statement.' Person Not Resident - '.$person_involevd_not_resident .' Person Statement - '. $person_statement .' | Incident - ' . $incident .' | Date Incident '. $date_of_incident .' | Location Incident '. $location_incident .' | Complainant Statement - '. $complainant_statement .' | Respondent - ' . $respodent;
    $status_activity_log = 'delete';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();

  }





}catch(Exception $e){
  echo $e->getMessage();
}





?>