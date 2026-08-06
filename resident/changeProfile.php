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
  $sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
  $stmt_user = $con->prepare($sql_user) or die ($con->error);
  $stmt_user->bind_param('s',$user_id);
  $stmt_user->execute();
  $row_user = $stmt_user->get_result()->fetch_assoc();
  $old_password_user = $row_user['password'];

  $username = $con->real_escape_string($_POST['username']);
  $old_password = (string) ($_POST['old_password'] ?? '');
  $new_password = (string) ($_POST['new_password'] ?? '');
  $edit_confirm_password = (string) ($_POST['edit_confirm_password'] ?? '');

  $sql_username = "SELECT id FROM users WHERE username = ? AND id != ?";
  $query_username = $con->prepare($sql_username) or die ($con->error);
  $query_username->bind_param('ss',$username,$user_id);
  $query_username->execute();
  if ($query_username->get_result()->num_rows > 0) {
    exit('error1');
  }

  if (!barangay_verify_password($old_password, $old_password_user)) {
    exit('error2');
  }

  if ($new_password === '' && $edit_confirm_password === '') {
    $pass = $old_password_user;
  } else {
    if ($new_password !== $edit_confirm_password) {
      exit('error3');
    }
    $pass = barangay_hash_password($edit_confirm_password);
  }

  $sql_update = "UPDATE `users` SET username = ?, password = ? WHERE id = ?";
  $stmt = $con->prepare($sql_update) or die ($con->error);
  $stmt->bind_param('sss',$username,$pass,$user_id);
  $stmt->execute();
  $stmt->close();

}catch(Exception $e){
  echo $e->getMessage();
}

?>
