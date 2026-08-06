<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();


try{


  if(isset($_REQUEST['file_id'])){

    $id = $con->real_escape_string($_REQUEST['file_id']);

    $sql_delete_file = "DELETE FROM backup WHERE id = ?";
    $stmt_delete_file = $con->prepare($sql_delete_file) or die ($con->error);
    $stmt_delete_file->bind_param('s', $id);
    $stmt_delete_file->execute();
    $stmt_delete_file->close();



  }




}catch(Exception $e){
  echo $e->getMessage();
}







?>