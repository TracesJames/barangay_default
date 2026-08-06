<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';

try{

  date_default_timezone_set('Asia/Manila');
  $date = new DateTime();
  $uniqid_date = $date->format("mdYHisv");
  $uniqid =   str_shuffle(hexdec(uniqid())).$uniqid_date;



  $add_position = $con->real_escape_string($_POST['add_position']);
  $add_description = $con->real_escape_string($_POST['add_description']);
  $limit = $con->real_escape_string($_POST['limit']);


  $sql_check_position = "SELECT position FROM position WHERE position =  ? ";
  $query_position = $con->prepare($sql_check_position) or die ($con->error);
  $query_position->bind_param('s',$add_position);
  $query_position->execute();
  $reult = $query_position->get_result();
  $count = $reult->num_rows;
  if($count > 0){
    exit('error');
  }
  function random_color_part() {
    return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
}

function random_color() {
    return random_color_part() . random_color_part() . random_color_part();
}

$color = '#'.random_color();

  $sql = "INSERT INTO `position` (`position_id`, `position`,`position_limit`, `position_description`,`color`) VALUES (?,?,?,?,?)";
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->bind_param('sssss',$uniqid,$add_position,$limit,$add_description,$color);
  $stmt->execute();
  $stmt->close();

  $date_activity = $now = date("j-n-Y g:i A");  
  $admin = strtoupper('ADMIN').':' .' '. 'ADDED POSITION - '.' ' .$uniqid.' | ' .' POSITION NAME '. $add_position.' | POSITION LIMIT '. $limit ;
  $status_activity_log = 'added';
  $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
  $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
  $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
  $stmt_activity_log->execute();
  $stmt_activity_log->close();



}catch(Exception $e){
  echo $e->getMessage();
}


?>