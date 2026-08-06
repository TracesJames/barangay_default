<?php 


include_once '../connection.php';
include_once '../includes/auth_resident.php';



try{



  $residence_id = $con->real_escape_string((string) $_SESSION['user_id']);
  $posted_residence_id = trim((string) ($_POST['residence_id'] ?? ''));

  if ($posted_residence_id !== '' && $posted_residence_id !== $residence_id) {
    exit('errorForbidden');
  }

  $purpose = trim((string) ($_POST['purpose'] ?? ''));
  if ($purpose === '') {
    exit('errorPurpose');
  }

  $purpose = $con->real_escape_string(strtoupper($purpose));
  $certificate_id = $con->real_escape_string($_POST['certificate_id']);

  $sql_update_request = "UPDATE certificate_request SET purpose = ? WHERE id = ? AND residence_id = ? AND status = 'PENDING'";
  $stmt_update_request = $con->prepare($sql_update_request) or die ($con->error);
  $stmt_update_request->bind_param('sss',$purpose,$certificate_id,$residence_id);
  $stmt_update_request->execute();
  $stmt_update_request->close();








}catch(Exception $e){
  echo $e->getMessage();
}







?>