<?php 



include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();


try{


  if(isset($_REQUEST['position_id'])){
    $position_id = $con->real_escape_string($_REQUEST['position_id']);


    $sql_check_position = "SELECT position FROM official_status WHERE position = ?";
    $stmt_check_position = $con->prepare($sql_check_position) or die ($con->error);
    $stmt_check_position->bind_param('s',$position_id);
    $stmt_check_position->execute();
    $result_check_position = $stmt_check_position->get_result();
    $count_check_position = $result_check_position->num_rows;

    if($count_check_position > 0){
      exit('error');
    }


    
    $sql_check_position_end = "SELECT position FROM official_end_status WHERE position = ?";
    $stmt_check_position_end = $con->prepare($sql_check_position_end) or die ($con->error);
    $stmt_check_position_end->bind_param('s',$position_id);
    $stmt_check_position_end->execute();
    $result_check_position_end = $stmt_check_position_end->get_result();
    $count_check_position_end = $result_check_position_end->num_rows;

    if($count_check_position_end > 0){
      exit('error');
    }

    $sql_position = "SELECT position FROM position WHERE position_id = ?";
    $stmt_position = $con->prepare($sql_position) or die ($con->error);
    $stmt_position->bind_param('s',$position_id);
    $stmt_position->execute();
    $result_position = $stmt_position->get_result();
    $row_position = $result_position->fetch_assoc();
    $old_position = $row_position['position'];

    
    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('ADMIN').':' .' '. 'DELETED POSITION - '.' ' .$position_id.' | ' . $old_position;
    $status_activity_log = 'delete';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();



    $sql_delete_position = "DELETE FROM position WHERE position_id = ?";
    $stmt_delete_position = $con->prepare($sql_delete_position) or die ($con->error);
    $stmt_delete_position->bind_param('s',$position_id);
    $stmt_delete_position->execute();
    $stmt_delete_position->close();




  }


}catch(Exception $e){
  echo $e->getMessage();
}







?>