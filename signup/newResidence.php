<?php
include_once '../connection.php';
require_once '../includes/helpers.php';
require_once '../includes/csrf.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_family.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
csrf_verify();
date_default_timezone_set('Asia/Manila');
$date = new DateTime();
$date_added = date("m/d/Y h:i A");
$archive = 'NO';

$add_barangay_id = trim((string) ($_POST['add_barangay_id'] ?? ''));
$barangayRow = barangay_load_by_id($con, $add_barangay_id);
if ($barangayRow === null) {
    exit('errorBarangay');
}
$number = residence_generate_number($con, $add_barangay_id, (string) ($barangayRow['barangay'] ?? ''));

if (isset($_POST['add_pwd_info'])) {
  $add_pwd_check = $con->real_escape_string($_POST['add_pwd_info']);
} else {
  $add_pwd_check = '';
}
$add_single_parent = $con->real_escape_string($_POST['add_single_parent']);
$add_indigenous = $con->real_escape_string($_POST['add_indigenous'] ?? 'NO');
$add_pwd = $con->real_escape_string($_POST['add_pwd']);
$add_voters = $con->real_escape_string($_POST['add_voters']);
$add_first_name = $con->real_escape_string($_POST['add_first_name']);
$add_middle_name = $con->real_escape_string($_POST['add_middle_name']);
$add_last_name = $con->real_escape_string($_POST['add_last_name']);
$add_suffix = $con->real_escape_string($_POST['add_suffix']);
$add_gender = $con->real_escape_string($_POST['add_gender']);
$add_civil_status = $con->real_escape_string($_POST['add_civil_status']);
$add_religion = $con->real_escape_string($_POST['add_religion']);
$add_nationality = $con->real_escape_string($_POST['add_nationality']);
$add_contact_number = $con->real_escape_string($_POST['add_contact_number']);
$add_email_address = $con->real_escape_string($_POST['add_email_address']);
$add_address = $con->real_escape_string($_POST['add_address']);
$add_birth_date = $con->real_escape_string($_POST['add_birth_date']);
$add_birth_place = $con->real_escape_string($_POST['add_birth_place']);
residence_require_minor_guardian_or_exit($_POST);
$add_municipality = $con->real_escape_string($_POST['add_municipality']);
$add_zip = $con->real_escape_string($_POST['add_zip']);
$add_barangay = $con->real_escape_string($barangayRow['barangay']);
$add_house_number = $con->real_escape_string($_POST['add_house_number']);
$add_street = $con->real_escape_string($_POST['add_street']);
$add_fathers_name = $con->real_escape_string($_POST['add_fathers_name']);
$add_mothers_name = $con->real_escape_string($_POST['add_mothers_name']);
$add_guardian = $con->real_escape_string($_POST['add_guardian']);
$add_guardian_contact = $con->real_escape_string($_POST['add_guardian_contact']);
$add_status = 'ACTIVE';
$user_type = 'resident';

$add_username = $con->real_escape_string($_POST['add_username']);
$plainPassword = $con->real_escape_string($_POST['add_password']);
$add_confirm_password = $con->real_escape_string($_POST['add_confirm_password']);

if ($plainPassword !== $add_confirm_password) {
  exit('errorPassword');
}
$hashedPassword = barangay_hash_password($plainPassword);

$sql_check_username = "SELECT username FROM users WHERE username = ?";
$query_check_username = $con->prepare($sql_check_username) or die ($con->error);
$query_check_username->bind_param('s', $add_username);
$query_check_username->execute();
if ($query_check_username->get_result()->num_rows > 0) {
  exit('errorUsername');
}

require_once '../includes/upload_helper.php';
$new_image_name = '';
$new_image_path = '';
if (!empty($_FILES['add_image_residence']['name'])) {
    $upload = barangay_store_image_upload($_FILES['add_image_residence']);
    if (!$upload['ok']) {
        exit('errorImage');
    }
    $new_image_name = $upload['filename'];
    $new_image_path = $upload['path'];
}

$hasUserBarangayId = barangay_column_exists($con, 'users', 'barangay_id');
if ($hasUserBarangayId) {
    $sql_add_user = "INSERT INTO `users`(`id`, `first_name`, `middle_name`, `last_name`, `username`, `password`, `user_type`,`contact_number`, `image`,`image_path`, `barangay_id`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    $stmt_user = $con->prepare($sql_add_user) or die ($con->error);
    $stmt_user->bind_param('sssssssssss', $number, $add_first_name, $add_middle_name, $add_last_name, $add_username, $hashedPassword, $user_type, $add_contact_number, $new_image_name, $new_image_path, $add_barangay_id);
} else {
    $sql_add_user = "INSERT INTO `users`(`id`, `first_name`, `middle_name`, `last_name`, `username`, `password`, `user_type`,`contact_number`, `image`,`image_path`) VALUES (?,?,?,?,?,?,?,?,?,?)";
    $stmt_user = $con->prepare($sql_add_user) or die ($con->error);
    $stmt_user->bind_param('ssssssssss', $number, $add_first_name, $add_middle_name, $add_last_name, $add_username, $hashedPassword, $user_type, $add_contact_number, $new_image_name, $new_image_path);
}
$stmt_user->execute();
$stmt_user->close();

$today = date("Y/m/d");
$age = date_diff(date_create($add_birth_date), date_create($today));
$add_age_date = $age->format("%y");
$senior = ($add_age_date >= 60) ? 'YES' : 'NO';
$age_add = ($add_age_date == '0') ? '' : $add_age_date;

$sql = "INSERT INTO `residence_information`(
  `residence_id`, `first_name`, `middle_name`, `last_name`, `age`, `suffix`, `gender`, `civil_status`, `religion`, `nationality`,
  `contact_number`, `email_address`, `address`, `birth_date`, `birth_place`, `municipality`, `zip`, `barangay`,
  `house_number`, `street`, `fathers_name`, `mothers_name`, `guardian`, `guardian_contact`, `image`, `image_path`
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt = $con->prepare($sql) or die ($con->error);
$stmt->bind_param('ssssssssssssssssssssssssss',
  $number, $add_first_name, $add_middle_name, $add_last_name, $age_add, $add_suffix, $add_gender, $add_civil_status,
  $add_religion, $add_nationality, $add_contact_number, $add_email_address, $add_address, $add_birth_date, $add_birth_place,
  $add_municipality, $add_zip, $add_barangay, $add_house_number, $add_street, $add_fathers_name, $add_mothers_name,
  $add_guardian, $add_guardian_contact, $new_image_name, $new_image_path
);
$stmt->execute();
$stmt->close();

residence_save_spouse($con, $number, $_POST, 'add_');
residence_save_dependents($con, $number, $_POST, 'add_');

$hasResidenceBarangayId = barangay_column_exists($con, 'residence_status', 'barangay_id');
if ($hasResidenceBarangayId) {
    $sql_residence_status = "INSERT INTO `residence_status` (`residence_id`, `barangay_id`, `status`, `voters`,`archive`,`pwd`,`pwd_info`,`single_parent`,`indigenous`,`senior`, `date_added`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    $stmt_residence_status = $con->prepare($sql_residence_status) or die ($con->error);
    $stmt_residence_status->bind_param('sssssssssss', $number, $add_barangay_id, $add_status, $add_voters, $archive, $add_pwd, $add_pwd_check, $add_single_parent, $add_indigenous, $senior, $date_added);
} else {
    $sql_residence_status = "INSERT INTO `residence_status` (`residence_id`, `status`, `voters`,`archive`,`pwd`,`pwd_info`,`single_parent`,`indigenous`,`senior`, `date_added`) VALUES (?,?,?,?,?,?,?,?,?,?)";
    $stmt_residence_status = $con->prepare($sql_residence_status) or die ($con->error);
    $stmt_residence_status->bind_param('ssssssssss', $number, $add_status, $add_voters, $archive, $add_pwd, $add_pwd_check, $add_single_parent, $add_indigenous, $senior, $date_added);
}
$stmt_residence_status->execute();
$stmt_residence_status->close();

$date_activity = date("j-n-Y g:i A");
$admin = strtoupper('RESIDENT') . ': REGISTER RESIDENT - ' . $number . ' | ' . $add_first_name . ' ' . $add_last_name . ' ' . $add_suffix . ' (' . $add_barangay . ')';
$status_activity_log = 'create';
$sql_activity_log = "INSERT INTO activity_log (`message`,`date`,`status`)VALUES(?,?,?)";
$stmt_activity_log = $con->prepare($sql_activity_log) or die ($con->error);
$stmt_activity_log->bind_param('sss', $admin, $date_activity, $status_activity_log);
$stmt_activity_log->execute();
$stmt_activity_log->close();

echo 'success';

} catch (Exception $e) {
  echo $e->getMessage();
}
