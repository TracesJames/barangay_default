<?php  

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/csrf.php';
csrf_verify();

$id = $con->real_escape_string($_POST['id'] ?? '');
$barangay = $con->real_escape_string($_POST['barangay'] ?? '');
$zone = $con->real_escape_string($_POST['zone']);$district = $con->real_escape_string($_POST['district']);
$address = $con->real_escape_string($_POST['address']);
$postal_address = $con->real_escape_string($_POST['postal_address']);
$image = $con->real_escape_string($_FILES['add_image']['name']);


require_once '../includes/upload_helper.php';
$sql_current = "SELECT image, image_path FROM barangay_information WHERE id = ?";
$stmt_current = $con->prepare($sql_current) or die($con->error);
$stmt_current->bind_param('s', $id);
$stmt_current->execute();
$current = $stmt_current->get_result()->fetch_assoc();
$new_image_name = $current['image'] ?? '';
$new_image_path = $current['image_path'] ?? '';

if (!empty($_FILES['add_image']['name'])) {
    $upload = barangay_store_image_upload($_FILES['add_image']);
    if (!$upload['ok']) {
        exit('errorImage');
    }
    $new_image_name = $upload['filename'];
    $new_image_path = $upload['path'];
}

  
  
  $sql_insert = "UPDATE  `barangay_information` SET `barangay` = ?, `zone` = ?, `district` = ?, `image` = ?, `image_path` = ?, `address` = ?, `postal_address` = ? WHERE `id` = ?";
  $stmt_insert = $con->prepare($sql_insert) or die ($con->error);
  $stmt_insert->bind_param('ssssssss',$barangay,$zone,$district,$new_image_name,$new_image_path,$address,$postal_address,$id);
  $stmt_insert->execute();
  $stmt_insert->close();

?>