<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();


try{


  if(isset($_REQUEST['blotter_id']) && isset($_REQUEST['person_id'])){
    $blotter_id = $con->real_escape_string($_REQUEST['blotter_id']);
    $person_id = $con->real_escape_string($_REQUEST['person_id']);
    $blank = '';

    $sql_delete_person_record = "UPDATE blotter_status SET person_id = ? WHERE blotter_main = ? AND  person_id = ?";
    $stmt_delete_person_record = $con->prepare($sql_delete_person_record) or die ($con->error);
    $stmt_delete_person_record->bind_param('sss',$blank,$blotter_id,$person_id);
    $stmt_delete_person_record->execute();
    $stmt_delete_person_record->close();


  }



}catch(Exception $e){
  echo $e->getMessage();
}






?>