<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';

try{

  $edit_residence_id = $con->real_escape_string($_REQUEST['edit_residence_id']);

  $sql_blooter_check = "SELECT blotter_record.*, blotter_status.*, blotter_complainant.*, blotter_record.blotter_id AS gago FROM `blotter_record` 
  INNER JOIN blotter_complainant ON blotter_record.blotter_id = blotter_complainant.blotter_main 
  INNER JOIN blotter_status ON blotter_record.blotter_id = blotter_status.blotter_main WHERE person_id = ? OR complainant_id =  ? GROUP BY blotter_record.blotter_id";
  $query_blotter_check = $con->prepare($sql_blooter_check) or die ($con->error);
  $query_blotter_check->bind_param('ss',$edit_residence_id,$edit_residence_id);
  $query_blotter_check->execute();
  $result_blotter_check = $query_blotter_check->get_result();
  $totalDataBlotter = $result_blotter_check->num_rows;
  $totalFilteredBlotter = $totalDataBlotter;

$data= [];

  while($row_blotter_check = $result_blotter_check->fetch_assoc()){

    date_default_timezone_set('Asia/Manila');
    $date_incident= date("m/d/Y - h:i A", strtotime($row_blotter_check['date_incident']));

   
    $date_reported= date("m/d/Y - h:i A", strtotime($row_blotter_check['date_reported']));


    if($row_blotter_check['status'] == 'NEW'){
      $status_blotter = '<span class="badge badge-primary">'.$row_blotter_check['status'] .'</span>';
    }else{
      $status_blotter = '<span class="badge badge-warning">'.$row_blotter_check['status'] .'</span>';
    }

    if($row_blotter_check['remarks'] == 'CLOSED'){
      $remarks_blotter = '<span class="badge badge-success">'.$row_blotter_check['remarks'] .'</span>';
    }else{
      $remarks_blotter = '<span class="badge badge-danger">'.$row_blotter_check['remarks'] .'</span>';
    }

    if($row_blotter_check['complainant_id'] == $edit_residence_id){
      $color = 1;
      $delete_record = '<i style="cursor: pointer;  color: red;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-times text-lg px-2 deleteRecordComplainant" data-id="'.$row_blotter_check['complainant_id'].'" id="'.$row_blotter_check['blotter_main'].'" ></i>';
    }else{
      $color = 2;
      $delete_record = '<i style="cursor: pointer;  color: red;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-times text-lg px-2 deleteRecordPerson" data-id="'.$row_blotter_check['person_id'].'" id="'.$row_blotter_check['blotter_main'].'"></i>';
    }

    

    $subdata = [];

    $subdata[] = $color;
    $subdata[] = $row_blotter_check['gago'];
    $subdata[] = $status_blotter;
    $subdata[] = $remarks_blotter;
    $subdata[] = $row_blotter_check['type_of_incident'];
    $subdata[] = $row_blotter_check['location_incident'];
    $subdata[] = $date_incident;
    $subdata[] = $date_reported;
    // $subdata[] =   '<i style="cursor: pointer;  color: yellow;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-book-open text-lg px-2 viewRecord" id="'.$row_blotter_check['gago'].'"></i>
    // '.$delete_record.'';

   $data[] = $subdata;
    
  
  }


  $json_data = [
    'draw' => intval($_REQUEST['draw']),
    'recordsTotal' => intval($totalDataBlotter),
    'recordsFiltered' => intval($totalFilteredBlotter),
    'data' => $data,
  ];

  echo json_encode($json_data);





}catch(Exception $e){
  echo $e->getMessage();
}





?>