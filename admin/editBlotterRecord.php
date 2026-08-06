<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';


try{


  $blotter_id = $_POST['blotter_id'];
  if(isset($_POST['edit_complainant_residence']) && $_POST['edit_complainant_residence'] != ''){
    $complainant_blotter_id =  $_POST['edit_complainant_residence'];
  }else{
    $complainant_blotter_id = [
      ''
    ];
  }
  if(isset($_POST['edit_person_involed']) && $_POST['edit_person_involed'] != ''){
    $person_blotter_id =  $_POST['edit_person_involed'];
  }else{
    $person_blotter_id = [
      ''
    ];
  }

 
 $edit_complainant_not_residence = $con->real_escape_string($_POST['edit_complainant_not_residence']);
 $edit_complainant_statement = $con->real_escape_string($_POST['edit_complainant_statement']);
 $edit_respodent = $con->real_escape_string($_POST['edit_respodent']);
 $edit_person_involevd_not_resident = $con->real_escape_string($_POST['edit_person_involevd_not_resident']);
 $edit_location_incident = $con->real_escape_string($_POST['edit_location_incident']);
 $edit_date_of_incident = $con->real_escape_string($_POST['edit_date_of_incident']);
 $edit_incident = $con->real_escape_string($_POST['edit_incident']);
 $edit_status = $con->real_escape_string($_POST['edit_status']);
 $edit_date_reported = $con->real_escape_string($_POST['edit_date_reported']);
 $edit_remarks = $con->real_escape_string($_POST['edit_remarks']);
 $edit_person_statement = $con->real_escape_string($_POST['edit_person_statement']);

 
 

  $sql_blotter_select = "SELECT * FROM blotter_complainant WHERE blotter_main = ?";
  $stmt_blotter_select = $con->prepare($sql_blotter_select) or die ($con->error);
  $stmt_blotter_select->bind_param('s',$blotter_id);
  $stmt_blotter_select->execute();
  $result_blotter_select = $stmt_blotter_select->get_result();
  $stmt_blotter_select->close();



  $complainant_array = [];
  foreach($result_blotter_select as $fetch_blotter_select){
    $complainant_array[] = $fetch_blotter_select['complainant_id'];
  }
 

  foreach($complainant_blotter_id as $insertBlotterValue){
    
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $uniqid = uniqid(mt_rand().$date->format("mDYHisv").rand());
    $generate = md5(('see=').$uniqid);
    $id = uniqid(rand())   . $generate;

    if(!in_array($insertBlotterValue,$complainant_array)){
      

      $sql_blotter_insert = "INSERT INTO blotter_complainant (`id`,`blotter_main`,`complainant_id`) VALUES (?,?,?)";
      $stmt_blotter_insert = $con->prepare($sql_blotter_insert) or die ($con->error);
      $stmt_blotter_insert->bind_param('sss',$id,$blotter_id,$insertBlotterValue);
      $stmt_blotter_insert->execute();
      $stmt_blotter_insert->close();
    }

  }

  foreach($complainant_array as $fetch_blotter_select){
    if(!in_array($fetch_blotter_select, $complainant_blotter_id)){
     
      $sql_blotter_delete = "DELETE FROM blotter_complainant WHERE blotter_main = ? AND complainant_id = ?";
      $stmt_blotter_delete = $con->prepare($sql_blotter_delete) or die ($con->error);
      $stmt_blotter_delete->bind_param('ss',$blotter_id,$fetch_blotter_select);
      $stmt_blotter_delete->execute();
      $stmt_blotter_delete->close();

    }
  }


  
  $sql_blotter_select_person = "SELECT * FROM blotter_status WHERE blotter_main = ?";
  $stmt_blotter_select_person = $con->prepare($sql_blotter_select_person) or die ($con->error);
  $stmt_blotter_select_person->bind_param('s',$blotter_id);
  $stmt_blotter_select_person->execute();
  $result_blotter_select_person = $stmt_blotter_select_person->get_result();
  $stmt_blotter_select_person->close();

  $person_array = [];
  foreach($result_blotter_select_person as $fetch_blotter_select_person){
    $person_array[] = $fetch_blotter_select_person['person_id'];
  }

  foreach($person_blotter_id as $insertBlotterValuePerson){
    
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $uniqid = uniqid(mt_rand().$date->format("mDYHisv").rand());
    $generate = md5(('seae=').$uniqid);
    $ids =    $generate . uniqid(rand());

    if(!in_array($insertBlotterValuePerson,$person_array)){
      

      $sql_blotter_insert_person = "INSERT INTO blotter_status (`blotter_id`,`blotter_main`,`person_id`) VALUES (?,?,?)";
      $stmt_blotter_insert_person = $con->prepare($sql_blotter_insert_person) or die ($con->error);
      $stmt_blotter_insert_person->bind_param('sss',$ids,$blotter_id,$insertBlotterValuePerson);
      $stmt_blotter_insert_person->execute();
      $stmt_blotter_insert_person->close();
    }

  }

  foreach($person_array as $fetch_blotter_select_person){
    if(!in_array($fetch_blotter_select_person, $person_blotter_id)){
     
      $sql_blotter_delete_person = "DELETE FROM blotter_status WHERE blotter_main = ? AND person_id = ?";
      $stmt_blotter_delete_person = $con->prepare($sql_blotter_delete_person) or die ($con->error);
      $stmt_blotter_delete_person->bind_param('ss',$blotter_id,$fetch_blotter_select_person);
      $stmt_blotter_delete_person->execute();
      $stmt_blotter_delete_person->close();

    }
  }
 
 

  $sql_update_record = "UPDATE `blotter_record` SET 
  `complainant_not_residence`= ?,
  `statement`= ? ,
  `respodent`= ?,
  `involved_not_resident`= ?,
  `date_incident`= ?,
  `date_reported`= ?,
  `type_of_incident`= ?,
  `location_incident`= ?,
  `status`= ? ,
  `remarks`= ?,
  `statement_person` = ?
   WHERE `blotter_id` = ?";
  $stmt_update_record = $con->prepare($sql_update_record) or die ($con->error);
  $stmt_update_record->bind_param('ssssssssssss',
    $edit_complainant_not_residence,
    $edit_complainant_statement,
    $edit_respodent,
    $edit_person_involevd_not_resident,
    $edit_date_of_incident,
    $edit_date_reported,
    $edit_incident,
    $edit_location_incident,
    $edit_status,
    $edit_remarks,
    $edit_person_statement,
    $blotter_id);
  $stmt_update_record->execute();
  $stmt_update_record->close();


}catch(Exception $e){
  echo $e->getMessage();
}






?>