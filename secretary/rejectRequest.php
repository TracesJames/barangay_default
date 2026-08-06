<?php 


  include_once '../connection.php';
include_once '../includes/auth_secretary.php';

barangay_require_post();
if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'secretary'){
  
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




}else{
 echo '<script>
        window.location.href = "../login.php";
      </script>';
}

try{

  if(isset($_REQUEST['residence_id']) && isset($_REQUEST['certificate_id'])){
    $residence_id = $con->real_escape_string($_REQUEST['residence_id']);
    $certificate_id = $con->real_escape_string($_REQUEST['certificate_id']);
    $message = $con->real_escape_string($_REQUEST['message']);

    $purpose = $con->real_escape_string($_REQUEST['purpose']);
    

    $status_reject_request = "REJECTED";
    $sql_reject_request = "UPDATE certificate_request SET status = ?, message = ? WHERE id = ? AND residence_id = ?";
    $stmt_reject_request = $con->prepare($sql_reject_request) or die ($con->error);
    $stmt_reject_request->bind_param('ssss',$status_reject_request,$message,$certificate_id,$residence_id);
    $stmt_reject_request->execute();
    $stmt_reject_request->close();

    $sql_user = "SELECT first_name, last_name FROM users WHERE id = ?";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$residence_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $first_name = $row_user['first_name'];
    $last_name = $row_user['last_name'];
    $status_activity_log = 'updated';
  


    $date_activity = $now = date("j-n-Y g:i A"); 
    $message_activity =  'OFFICIAL: '.$first_name_user. ' '. $last_name_user. ' ' . $user_id. ' | '. 'RESIDENT '.$first_name.' '.$last_name. ' REQUEST CERTIFICATE REJECTED - ' .$residence_id. ' | PURPOSE ' .strtoupper($purpose). ' | MESSAGE ' .$message;
    $sql_system_logs= "INSERT INTO activity_log (`message`, `date`,`status`) VALUES (?,?,?)";
    $query_system_logs = $con->prepare($sql_system_logs) or die ($con->error);
    $query_system_logs->bind_param('sss',$message_activity,$date_activity,$status_activity_log);
    $query_system_logs->execute();
    $query_system_logs->close();

  }


}catch(Exception $e){
  echo $e->getMessage();
}



?>