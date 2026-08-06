<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();

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
    $admin = strtoupper('ADMIN').':' .' '. 'UPDATED OFFICIAL STATUS  - '.' ' .$official_id.' |' .' '. ' FROM '.$old_status.' TO '. $data_status;
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