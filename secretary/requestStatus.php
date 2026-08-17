<?php 


include_once '../connection.php';
include_once '../includes/auth_secretary.php';
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

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('errorMethod');
  }

  $residence_id = $con->real_escape_string((string) ($_POST['residence_id'] ?? ''));
  $purpose = $con->real_escape_string(strtoupper((string) ($_POST['purpose'] ?? '')));
  $certificate_id = $con->real_escape_string((string) ($_POST['certificate_id'] ?? ''));
  $edit_date_issued = $con->real_escape_string((string) ($_POST['edit_date_issued'] ?? ''));
  $edit_date_expired = $con->real_escape_string((string) ($_POST['edit_date_expired'] ?? ''));
  $message = $con->real_escape_string((string) ($_POST['message'] ?? ''));
  $status = 'ACCEPTED';


  $sql_update_request = "UPDATE certificate_request SET date_issued = ?, date_expired = ?, status = ?, purpose = ?,  message = ? WHERE id = ? AND residence_id = ?";
  $stmt_update_request = $con->prepare($sql_update_request) or die ($con->error);
  $stmt_update_request->bind_param('sssssss',$edit_date_issued,$edit_date_expired,$status,$purpose,$message,$certificate_id,$residence_id);
  $stmt_update_request->execute();
  $stmt_update_request->close();

  $sql_user = "SELECT first_name, last_name FROM users WHERE id = ?";
    $stmt_user = $con->prepare($sql_user) or die ($con->error);
    $stmt_user->bind_param('s',$residence_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc() ?: [];
    $first_name = (string) ($row_user['first_name'] ?? '');
    $last_name = (string) ($row_user['last_name'] ?? '');
    $status_activity_log = 'updated';
  
     
    $date_activity = $now = date("j-n-Y g:i A"); 
    $message_activity =  'OFFICIAL: '.$first_name_user. ' '. $last_name_user. ' ' . $user_id. ' | '. 'RESIDENT '.$first_name.' '.$last_name. ' REQUEST CERTIFICATE ACCEPTED - ' .$residence_id. ' | PURPOSE ' .strtoupper($purpose). ' | MESSAGE ' .$message . ' | DATE ISSUED ' .$edit_date_issued .' | DATE EXPIRED '. $edit_date_expired;
    $sql_system_logs= "INSERT INTO activity_log (`message`, `date`,`status`) VALUES (?,?,?)";
    $query_system_logs = $con->prepare($sql_system_logs) or die ($con->error);
    $query_system_logs->bind_param('sss',$message_activity,$date_activity,$status_activity_log);
    $query_system_logs->execute();
    $query_system_logs->close();







}catch(Exception $e){
  echo $e->getMessage();
}







?>