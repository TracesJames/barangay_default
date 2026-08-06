<?php 

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/helpers.php';

$aa = $_SESSION['user_id'];
$sql_user = "SELECT first_name, last_name FROM `users` WHERE `id` = ? ";
$stmt_user = $con->prepare($sql_user) or die ($con->error);
$stmt_user->bind_param('s',$aa);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'];
$last_name_user = $row_user['last_name'];

try{

  $user_id = $con->real_escape_string(trim($_POST['user_id']));
  $first_name = $con->real_escape_string($_POST['edit_first_name']);
  $middle_name = $con->real_escape_string($_POST['edit_middle_name']);
  $last_name = $con->real_escape_string($_POST['edit_last_name']);
  $username = $con->real_escape_string($_POST['edit_username']);
  $password = $con->real_escape_string($_POST['edit_password']);
  $contact_number = $con->real_escape_string($_POST['edit_contact_number']);


    $sql_check_username = "SELECT username FROM users WHERE username = ? AND id != ?";
    $stmt_check_username = $con->prepare($sql_check_username) or die ($con->error);
    $stmt_check_username->bind_param('ss',$username,$user_id);
    $stmt_check_username->execute();
    $stmt_check_username->store_result();
    $count = $stmt_check_username->num_rows;

    if($count > 0){
      exit('error');
    }



    $sql_check_user = "SELECT * FROM users WHERE id = ?";
    $stmt_check_user = $con->prepare($sql_check_user) or die ($con->error);
    $stmt_check_user->bind_param('s',$user_id);
    $stmt_check_user->execute();
    $result_check_user = $stmt_check_user->get_result();
    $row_check_user = $result_check_user->fetch_assoc();


    $old_first_name = $row_check_user['first_name'];
    $old_middle_name = $row_check_user['middle_name'];
    $old_last_name = $row_check_user['last_name'];
    $old_username = $row_check_user['username'];
    $old_password = $row_check_user['password'];
    $old_contact_number = $row_check_user['contact_number'];
  



  $sql = "UPDATE residence_information SET first_name = ?, middle_name = ?, last_name = ? WHERE residence_id = ?";
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->bind_param('ssss',$first_name,$middle_name,$last_name,$user_id);
  $stmt->execute();
  $stmt->close();


  if(($_POST['edit_password_check'] ?? '') == 'true'){
    $password = barangay_hash_password($password);
  } else {
    $password = $old_password;
  }

  $sql_user = "UPDATE users SET username = ?, password = ?, first_name = ?, middle_name = ?, last_name = ?, contact_number = ?  WHERE id = ?";
  $stmt_user = $con->prepare($sql_user) or die ($con->error);
  $stmt_user->bind_param('sssssss',$username,$password,$first_name,$middle_name,$last_name,$contact_number,$user_id);
  $stmt_user->execute();
  $stmt_user->close();



  if($_POST['edit_first_name_check'] == 'true' || $_POST['edit_first_name_check'] === TRUE){

    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('OFFICAL').': ' .$first_name_user.' '.$last_name_user. ' - ' .$aa.' | '. 'UPDATED RESIDENT USER`S FIRST NAME -'.' ' .$user_id.' |' .' '. ' FROM '.$old_first_name.' TO '. $first_name;
    $status_activity_log = 'update';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();

  }


  if($_POST['edit_middle_name_check'] == 'true' || $_POST['edit_middle_name_check'] === TRUE){

    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('OFFICAL').': ' .$first_name_user.' '.$last_name_user. ' - ' .$aa.' | '. 'UPDATED RESIDENT USER`S MIDDLE NAME -'.' ' .$user_id.' |' .' '. ' FROM '.$old_middle_name.' TO '. $middle_name;
    $status_activity_log = 'update';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();

  }

  if($_POST['edit_last_name_check'] == 'true' || $_POST['edit_last_name_check'] === TRUE){

    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('OFFICAL').': ' .$first_name_user.' '.$last_name_user. ' - ' .$aa.' | '. 'UPDATED RESIDENT USER`S LAST NAME -'.' ' .$user_id.' |' .' '. ' FROM '.$old_last_name.' TO '. $last_name;
    $status_activity_log = 'update';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();

  }

  if($_POST['edit_username_check'] == 'true' || $_POST['edit_username_check'] === TRUE){

    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('OFFICAL').': ' .$first_name_user.' '.$last_name_user. ' - ' .$aa.' | '. 'UPDATED RESIDENT USER`S USERNAME -'.' ' .$user_id.' |' .' '. ' FROM '.$old_username.' TO '. $username;
    $status_activity_log = 'update';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();

  }

  if($_POST['edit_password_check'] == 'true' || $_POST['edit_password_check'] === TRUE){

    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('OFFICAL').': ' .$first_name_user.' '.$last_name_user. ' - ' .$aa.' | '. 'UPDATED RESIDENT USER`S PASSWORD -'.' ' .$user_id.' |' .' '. ' FROM '.$old_password.' TO '. $password;
    $status_activity_log = 'update';
    $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
    $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
    $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
    $stmt_activity_log->execute();
    $stmt_activity_log->close();

  }


  if($_POST['edit_contact_number_check'] == 'true' || $_POST['edit_contact_number_check'] === TRUE){

    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('OFFICAL').': ' .$first_name_user.' '.$last_name_user. ' - ' .$aa.' | '. 'UPDATED RESIDENT USER`S CONTACT NUMBER -'.' ' .$user_id.' |' .' '. ' FROM '.$old_contact_number.' TO '. $contact_number;
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