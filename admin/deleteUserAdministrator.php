<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();


try{


  if(isset($_REQUEST['user_id'])){
    $user_id = $con->real_escape_string($_REQUEST['user_id']);

    $sql_check_admin = "SELECT * FROM users where id = ?";
    $stmt_check_admin = $con->prepare($sql_check_admin) or die ($con->error);
    $stmt_check_admin->bind_param('s',$user_id);
    $stmt_check_admin->execute();
    $result_check_admin = $stmt_check_admin->get_result();
    $row_check_admin = $result_check_admin->fetch_assoc();
  
    $old_first_name = $row_check_admin['first_name'];
    $old_middle_name = $row_check_admin['middle_name'];
    $old_last_name = $row_check_admin['last_name'];

    
    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('ADMIN').':' .' '. 'DELETED ADMINISTRATOR  - '.' ' .$user_id.' | ' . $old_first_name .' '. $old_last_name;
    $status_activity_log = 'delete';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();


    $sql_delete_user = "DELETE FROM `users` WHERE id = '$user_id'";
    $query = $con->query($sql_delete_user) or die ($con->error);


 


  }




}catch(Exception $e){
  echo $e->getMessage();
}


?>