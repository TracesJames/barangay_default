<?php 

include_once '../connection.php';
include_once '../includes/auth_resident.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try{
  $user_id = $_SESSION['user_id'];
  $username = $con->real_escape_string($_POST['username']);
  $current_password = (string) ($_POST['current_password'] ?? '');
  $retype_password = (string) ($_POST['retype_password'] ?? '');
  $password = (string) ($_POST['new_password'] ?? '');

  $sql_check_username = "SELECT username FROM users WHERE username = ? AND id != ?";
  $stmt_check_username = $con->prepare($sql_check_username) or die ($con->error);
  $stmt_check_username->bind_param('ss',$username,$user_id);
  $stmt_check_username->execute();
  if ($stmt_check_username->get_result()->num_rows > 0) {
    exit('errorUsername');
  }

  $sql_check_password = "SELECT password FROM users WHERE id = ?";
  $stmt_check_password = $con->prepare($sql_check_password) or die ($con->error);
  $stmt_check_password->bind_param('s',$user_id);
  $stmt_check_password->execute();
  $row_password = $stmt_check_password->get_result()->fetch_assoc();

  if (!barangay_verify_password($current_password, $row_password['password'])) {
    exit('errorPassword');
  }

  if ($password !== $retype_password) {
    exit('errorNot');
  }

  $hashed = barangay_hash_password($password);
  $sql_user = "UPDATE users SET username = ?, password = ? WHERE id = ?";
  $stmt_user = $con->prepare($sql_user) or die ($con->error);
  $stmt_user->bind_param('sss',$username,$hashed,$user_id);
  $stmt_user->execute();
  $stmt_user->close();

}catch(Exception $e){
  echo $e->getMessage();
}

?>
