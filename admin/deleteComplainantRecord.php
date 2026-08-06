<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();


try{


  if(isset($_REQUEST['blotter_id']) && isset($_REQUEST['complainant_id'])){
    $blotter_id = $con->real_escape_string($_REQUEST['blotter_id']);
    $complainant_id = $con->real_escape_string($_REQUEST['complainant_id']);
    $blank = '';

    $sql_delete_complainant_record = "UPDATE blotter_complainant SET complainant_id = ? WHERE blotter_main = ? AND  complainant_id = ?";
    $stmt_delete_complainant_record = $con->prepare($sql_delete_complainant_record) or die ($con->error);
    $stmt_delete_complainant_record->bind_param('sss',$blank,$blotter_id,$complainant_id);
    $stmt_delete_complainant_record->execute();
    $stmt_delete_complainant_record->close();


  }



}catch(Exception $e){
  echo $e->getMessage();
}






?>