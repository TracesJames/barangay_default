<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

try{


  $edit_position_id = $con->real_escape_string(trim($_REQUEST['edit_position_id']));
  $edit_position = $con->real_escape_string($_REQUEST['edit_position']);
  $edit_description = $con->real_escape_string($_REQUEST['edit_description']);
  $edit_limit = $con->real_escape_string($_REQUEST['edit_limit']);


  $sql_position = "SELECT position, position_limit FROM position WHERE position_id = ? ";
  $smt_position = $con->prepare($sql_position) or die ($con->error);
  $smt_position->bind_param('s',$edit_position_id);
  $smt_position->execute();
  $result_position = $smt_position->get_result();
  $row_position = $result_position->fetch_assoc();
  $old_position = $row_position['position'];
  $old_limit = $row_position['position_limit'];


  $sql_check_position = "SELECT position FROM position WHERE position =  ? AND position_id != ? ";
  $query_position = $con->prepare($sql_check_position) or die ($con->error);
  $query_position->bind_param('ss',$edit_position,$edit_position_id);
  $query_position->execute();
  $reult = $query_position->get_result();
  $count = $reult->num_rows;


  if($count > 0){
    exit('error');
  }


  if($old_position != $edit_position){
    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('ADMIN').':' .' '. 'UPDATED POSITION - '.' ' .$edit_position_id.' | ' .' FROM '. $old_position.' TO '. $edit_position ;
    $status_activity_log = 'update';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();
  }

  
  if($old_limit != $edit_limit){
    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('ADMIN').':' .' '. 'UPDATED POSITION LIMIT - '.' ' .$edit_position_id.' | ' .' FROM '. $old_limit.' TO '. $edit_limit ;
    $status_activity_log = 'update';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();
  }

  $sql_update_position = "UPDATE position SET position = ?, position_description = ?, position_limit = ? WHERE position_id = ?";
  $stmt_update_position = $con->prepare($sql_update_position) or die ($con->error);
  $stmt_update_position->bind_param('ssss',$edit_position,$edit_description,$edit_limit,$edit_position_id);
  $stmt_update_position->execute();
  $stmt_update_position->close();



}catch(Exception $e){
  echo $e->getMessage();
}









?>