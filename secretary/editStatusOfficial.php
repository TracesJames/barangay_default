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

  if(isset($_REQUEST['official_id'])){

    $official_id = $con->real_escape_string(trim($_REQUEST['official_id']));

    
    $sql_check_status = "SELECT status FROM official_status WHERE official_id = ?";
    $stmt_check_status = $con->prepare($sql_check_status) or die ($con->error);
    $stmt_check_status->bind_param('s',$official_id);
    $stmt_check_status->execute();
    $result_check_status = $stmt_check_status->get_result();
    $row_check_status = $result_check_status->fetch_assoc();
    $old_status = $row_check_status['status'];

    if($row_check_status['status'] == 'ACTIVE'){
      $data_status = 'INACTIVE';
      
    }else{
      $data_status = 'ACTIVE';
     
    }
    
    $sql_update_status = "UPDATE official_status SET `status` = ? WHERE official_id = ?";
    $stmt_update_status = $con->prepare($sql_update_status) or die ($con->error);
    $stmt_update_status->bind_param('ss',$data_status,$official_id);
    $stmt_update_status->execute();
    $stmt_update_status->close();


    
     
  $date_activity = $now = date("j-n-Y g:i A");  
  $admin = strtoupper('OFFICAL').': ' .$first_name_user.' '.$last_name_user. ' - ' .$user_id.' | '. 'UPDATED OFFICIAL  STATUS -'.' ' .$official_id.' |' .' '. ' FROM '.$old_status.' TO '. $data_status;
  $status_activity_log = 'update';
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