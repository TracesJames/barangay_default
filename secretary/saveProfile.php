<?php 


include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
$stmt_user = $con->prepare($sql_user) or die ($con->error);
$stmt_user->bind_param('s',$user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$row_user = $result_user->fetch_assoc();
$password_user = $row_user['password'];

try{

  $username = $con->real_escape_string($_POST['username']);
  $first_name = $con->real_escape_string($_POST['first_name']);
  $middle_name = $con->real_escape_string($_POST['middle_name']);
  $last_name = $con->real_escape_string($_POST['last_name']);
  $contact_number = $con->real_escape_string($_POST['contact_number']);
  $old_password = (string) ($_POST['old_password'] ?? '');
  $new_password = (string) ($_POST['new_password'] ?? '');
  $new_confirm_password = (string) ($_POST['new_confirm_password'] ?? '');
  $image = $con->real_escape_string($_FILES['image']['name'] ?? '');

  $new_image_name = $row_user['image'];
  $new_image_path = $row_user['image_path'];

  if(isset($image) && $image != ''){
    $sql_check_image_user = "SELECT `image`, `image_path` FROM `users`  WHERE `id` = ?";
    $stmt_check_image_user = $con->prepare($sql_check_image_user) or die ($con->error);
    $stmt_check_image_user->bind_param('s',$user_id);
    $stmt_check_image_user->execute();
    $result_check_image_user = $stmt_check_image_user->get_result();
    $row_check_image_user = $result_check_image_user->fetch_assoc();

    if(!empty($row_check_image_user['image']) && file_exists($row_check_image_user['image_path'])){
      @unlink($row_check_image_user['image_path']);
    }

    require_once '../includes/upload_helper.php';
    $upload = barangay_store_image_upload($_FILES['image']);
    if (!$upload['ok']) {
      exit('errorImage');
    }
    $new_image_name = $upload['filename'];
    $new_image_path = $upload['path'];
  }

  $sql_check_username = "SELECT username FROM users WHERE username = ? AND id != ?";
  $stmt_check_username = $con->prepare($sql_check_username) or die ($con->error);
  $stmt_check_username->bind_param('ss',$username,$user_id);
  $stmt_check_username->execute();
  $count_check_username = $stmt_check_username->get_result()->num_rows;

  if($count_check_username > 0){
    exit('error');
  }

  if(!barangay_verify_password($old_password, $password_user)){
    exit('error1');
  }

  if($new_password !== '' && $new_password !== $new_confirm_password){
    exit('error2');
  }

  if($new_password === '' && $new_confirm_password === ''){
    $pass = $password_user;
  }else{
    $pass = barangay_hash_password($new_confirm_password);
  }

  $sql_update = "UPDATE users SET username = ?, password = ?, first_name = ?, middle_name =? , last_name = ?, contact_number = ?, image = ?, image_path = ? WHERE id = ?";
  $stmt_update = $con->prepare($sql_update) or die ($con->error);
  $stmt_update->bind_param('sssssssss',$username,$pass,$first_name,$middle_name,$last_name,$contact_number,$new_image_name,$new_image_path,$user_id);
  $stmt_update->execute();
  $stmt_update->close();

}catch(Exception $e){
  echo $e->getMessage();
}

?>
