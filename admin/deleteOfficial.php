<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();

try{

  if(isset($_REQUEST['official_id'])){
    $official_id = $con->real_escape_string(trim($_REQUEST['official_id']));


    $sql = "SELECT official_information.*, official_status.*, position.position AS check_position FROM official_information 
    INNER JOIN official_status ON official_information.official_id = official_status.official_id 
    INNER JOIN position ON official_status.position = position.position_id
    WHERE official_information.official_id = ?";
    $stmt = $con->prepare($sql) or die ($con->error);
    $stmt->bind_param('s',$official_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $row = $result->fetch_assoc();


    $official_id_end = $row['official_id'];
    $first_name = $row['first_name'];
    $middle_name = $row['middle_name'];
    $last_name = $row['last_name'];
    $suffix = $row['suffix'];
    $birth_date = $row['birth_date'];
    $birth_place = $row['birth_place'];
    $gender = $row['gender'];
    $age = $row['age'];
    $civil_status = $row['civil_status'];
    $religion = $row['religion'];
    $nationality = $row['nationality'];
    $municipality = $row['municipality'];
    $zip = $row['zip'];
    $barangay = $row['barangay'];
    $house_number = $row['house_number'];
    $street = $row['street'];
    $address = $row['address'];
    $email_address = $row['email_address'];
    $contact_number = $row['contact_number'];
    $fathers_name = $row['fathers_name'];
    $mothers_name = $row['mothers_name'];
    $guardian = $row['guardian'];
    $guardian_contact = $row['guardian_contact'];
    $image = $row['image'];
    $image_path = $row['image_path'];
    $position = $row['position'];
    $check_position = strtoupper($row['check_position']);
    $senior = $row['senior'];
    $term_from = $row['term_from'];
    $term_to = $row['term_to'];
    $pwd = $row['pwd'];
    $status = 'INACTIVE';
    $voters = $row['voters'];
    $pwd_info = $row['pwd_info'];
    $single_parent = $row['single_parent'];
 
    $date_deleted = date("m/d/Y h:i A");


    $sql_insert = "INSERT INTO `official_end_information`
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
    $stmt_insert = $con->prepare($sql_insert) or die ($con->error);
    $stmt_insert->bind_param('ssssssssssssssssssssssssss',
      $official_id_end,
      $first_name,
      $middle_name,
      $last_name,
      $gender,
      $suffix,
      $birth_date,
      $birth_place,
      $age,
      $civil_status,
      $religion,
      $nationality,
      $municipality,
      $zip,
      $barangay,
      $house_number,
      $street,
      $address,
      $email_address,
      $contact_number,
      $fathers_name,
      $mothers_name,
      $guardian,
      $guardian_contact,
      $image,
      $image_path
    );
    $stmt_insert->execute();
    $stmt_insert->close();


    $sql_official_status = "INSERT INTO `official_end_status` (`official_id`, `status`, `senior`,`voters`,`pwd_info`, `single_parent`, `position`,`date_deleted`, `term_from`, `term_to`, `pwd`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    $stmt_official_status = $con->prepare($sql_official_status) or die ($con->error);
    $stmt_official_status->bind_param('sssssssssss',$official_id_end,$status,$senior,$voters,$pwd_info,$single_parent,$position,$date_deleted,$term_from,$term_to,$pwd);
    $stmt_official_status->execute();
    $stmt_official_status->close();


    $sql_delete = "DELETE FROM official_information WHERE official_id = ?";
    $stmt_delete = $con->prepare($sql_delete) or die ($con->error);
    $stmt_delete->bind_param('s',$official_id_end);
    $stmt_delete->execute();
    $stmt_delete->close();


    $sql_delete_status = "DELETE FROM official_status WHERE official_id = ?";
    $stmt_delete_status = $con->prepare($sql_delete_status) or die ($con->error);
    $stmt_delete_status->bind_param('s',$official_id_end);
    $stmt_delete_status->execute();
    $stmt_delete_status->close();


    
    $date_activity = $now = date("j-n-Y g:i A");  
    $admin = strtoupper('ADMIN').':' .' '. 'DELETED OFFICIAL - '.' ' .$official_id.' | ' . $check_position .' - '.$first_name .' '. $last_name;
    $status_activity_log = 'delete';
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