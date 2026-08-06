<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';

$activeBarangay = barangay_require_active($con, 'barangayHub.php');
$barangay_id = $activeBarangay['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try{
  $user_id = $_SESSION['user_id'];
  $sql_user = "SELECT first_name, last_name FROM `users` WHERE `id` = ? ";
  $stmt_user = $con->prepare($sql_user) or die ($con->error);
  $stmt_user->bind_param('s',$user_id);
  $stmt_user->execute();
  $result_user = $stmt_user->get_result();
  $row_user = $result_user->fetch_assoc();
  $first_name_user = $row_user['first_name'];
  $last_name_user = $row_user['last_name'];

   $add_pwd_check = '';
  
  $add_single_parent = 'NO';
  $add_pwd = 'NO';
  $termRange = official_parse_term_range((string) ($_POST['add_term_years'] ?? ''));
  if (!official_validate_term_range((string) ($_POST['add_term_years'] ?? ''))) {
    exit('errorTermYear');
  }
  $add_term_from = $con->real_escape_string($termRange['from']);
  $add_term_to = $con->real_escape_string($termRange['to']);
  $add_position = $con->real_escape_string($_POST['add_position']);
  $add_voters = 'NO';
  $add_first_name = $con->real_escape_string($_POST['add_first_name']);
  $add_middle_name = $con->real_escape_string($_POST['add_middle_name'] ?? '');
  $add_last_name = $con->real_escape_string($_POST['add_last_name']);
  $add_suffix = $con->real_escape_string($_POST['add_suffix'] ?? '');
  $add_gender = $con->real_escape_string($_POST['add_gender']);
  $add_civil_status = '';
  $add_religion = '';
  $add_nationality = '';
  $add_contact_number = $con->real_escape_string($_POST['add_contact_number'] ?? '');
  $add_email_address = '';
  $add_address = '';
  $add_birth_date = '';
  $add_birth_place = '';
  $add_municipality = '';
  $add_zip = '';
  $add_barangay = '';
  $add_house_number = '';
  $add_street = '';
  $add_fathers_name = '';
  $add_mothers_name = '';
  $add_guardian = $con->real_escape_string($_POST['add_guardian'] ?? '');
  $add_guardian_contact = $con->real_escape_string($_POST['add_guardian_contact'] ?? '');
$add_image = $con->real_escape_string($_FILES['add_image']['name']);
$add_status = 'ACTIVE';
$add_approval = 'ACCEPTED';



require_once '../includes/upload_helper.php';
$new_image_name = '';
$new_image_path = '';
if (!empty($_FILES['add_image']['name'])) {
    $upload = barangay_store_image_upload($_FILES['add_image']);
    if (!$upload['ok']) {
        exit('errorImage');
    }
    $new_image_name = $upload['filename'];
    $new_image_path = $upload['path'];
}

$row_position_limit = barangay_get_position_row($con, $add_position);
if (!$row_position_limit || barangay_position_limit_reached($con, $add_position, $barangay_id)) {
  exit('error');
}







date_default_timezone_set('Asia/Manila');
$date = new DateTime();

$today = date("Y/m/d");
$add_age_date = '';
$official_id = $date->format("mdYHisv") . rand(10, 99);
$date_added = date("m/d/Y h:i A");

$senior = 'NO';



  $sql = "INSERT INTO `official_information`
  (`official_id`,
   `first_name`, 
   `middle_name`, 
   `last_name`, 
   `gender`,
   `suffix`, 
   `birth_date`, 
   `birth_place`, 
   `age`, 
   `civil_status`, 
   `religion`, 
   `nationality`, 
   `municipality`, 
   `zip`, 
   `barangay`, 
   `house_number`, 
   `street`, 
   `address`, 
   `email_address`, 
   `contact_number`, 
   `fathers_name`, 
   `mothers_name`, 
   `guardian`, 
   `guardian_contact`, 
   `image`, 
   `image_path`
   ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->bind_param('ssssssssssssssssssssssssss',
    $official_id,
    $add_first_name,
    $add_middle_name,
    $add_last_name,
    $add_gender,
    $add_suffix,
    $add_birth_date,
    $add_birth_place,
    $add_age_date,
    $add_civil_status,
    $add_religion,
    $add_nationality,
    $add_municipality,
    $add_zip,
    $add_barangay,
    $add_house_number,
    $add_street,
    $add_address,
    $add_email_address,
    $add_contact_number,
    $add_fathers_name,
    $add_mothers_name,
    $add_guardian,
    $add_guardian_contact,
    $new_image_name,
    $new_image_path
  );
  $stmt->execute();
  $stmt->close();
  
  $sql_official_status = "INSERT INTO `official_status` (`official_id`, `barangay_id`, `status`, `senior`,`voters`, `position`,`date_added`, `term_from`, `term_to`, `pwd`,`pwd_info`,`single_parent`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
  $stmt_official_status = $con->prepare($sql_official_status) or die ($con->error);
  $stmt_official_status->bind_param('ssssssssssss',$official_id,$barangay_id,$add_status,$senior,$add_voters,$add_position,$date_added,$add_term_from,$add_term_to,$add_pwd,$add_pwd_check,$add_single_parent);
  $stmt_official_status->execute();
  $stmt_official_status->close();

  

  
  $date_activity = $now = date("j-n-Y g:i A");  
  $activity_log_position = strtoupper($row_position_limit['position']);
  $admin = strtoupper('ADMIN').':' .' '. 'ADDED OFFICIAL -'.' ' .$official_id.' |' .' '.$activity_log_position .' '.$add_first_name .' '. $add_last_name .' '. $add_suffix .' | START ' .$add_term_from .' END ' .$add_term_to;
  $status_activity_log = 'create';


  $sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
  $stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
  $stmt_activity_log->bind_param('sss',$admin,$date_activity,$status_activity_log);
  $stmt_activity_log->execute();
  $stmt_activity_log->close();
  

 

}catch(Exception $e){
  echo $e->getMessage();
}








?>