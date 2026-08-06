<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();

try{

  if(isset($_REQUEST['status_position'])){

    $status_position_id = $con->real_escape_string(trim($_REQUEST['status_position']));

    
    $sql_check_status = "SELECT status FROM position WHERE position_id = ?";
    $stmt_check_status = $con->prepare($sql_check_status) or die ($con->error);
    $stmt_check_status->bind_param('s',$status_position_id);
    $stmt_check_status->execute();
    $result_check_status = $stmt_check_status->get_result();
    $row_check_status = $result_check_status->fetch_assoc();

    if($row_check_status['status'] == 'ACTIVE'){
      $data_status = 'INACTIVE';
      
    }else{
      $data_status = 'ACTIVE';
     
    }
    
    $sql_update_status = "UPDATE position SET `status` = ? WHERE position_id = ?";
    $stmt_update_status = $con->prepare($sql_update_status) or die ($con->error);
    $stmt_update_status->bind_param('ss',$data_status,$status_position_id);
    $stmt_update_status->execute();
    $stmt_update_status->close();
  

  }

}catch(Exception $e){
  echo $e->getMessage();
}



?>