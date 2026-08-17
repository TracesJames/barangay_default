<?php 



include_once '../connection.php';
include_once '../includes/auth_certificate_staff.php';
require_once '../includes/barangay_context.php';
require_once '../includes/datatables_helper.php';


try{

    $var_date_request = $con->real_escape_string((string) ($_REQUEST['date_request'] ?? ''));
    $var_date_issued = $con->real_escape_string((string) ($_REQUEST['date_issued'] ?? ''));
    $var_date_expired = $con->real_escape_string((string) ($_REQUEST['date_expired'] ?? ''));
    $var_status = $con->real_escape_string((string) ($_REQUEST['status'] ?? ''));
  

    $whereClause = barangay_certificates_where_clause($con, ['1=1']);

    if(!empty($var_date_request)){
      $whereClause[] = "certificate_request.date_request='$var_date_request'";
    }

    if(!empty($var_date_issued)){
      $whereClause[] = "certificate_request.date_issued='$var_date_issued'";
    }

    if(!empty($var_date_expired)){
      $whereClause[] = "certificate_request.date_expired='$var_date_expired'";
    }
    if(!empty($var_status)){
      $whereClause[] = "certificate_request.status='$var_status'";
    }

    $where = barangay_sql_where($whereClause);




    
    $sql_residencey = "SELECT certificate_request.*, residence_information.first_name, residence_information.middle_name,residence_information.last_name,residence_information.residence_id
    FROM certificate_request
    LEFT JOIN residence_information ON certificate_request.residence_id = residence_information.residence_id" . $where; 
    if (!empty($_REQUEST['search']['value'])) {
      $search = datatables_sql_like($con, (string) $_REQUEST['search']['value']);
      $sql_residencey .= " AND (certificate_request.residence_id LIKE '" . $search . "' ";
      $sql_residencey .= " OR last_name LIKE '" . $search . "' ";
      $sql_residencey .= " OR purpose LIKE '" . $search . "' ";
      $sql_residencey .= " OR first_name LIKE '" . $search . "' )";
    }

    $query_residency = $con->query($sql_residencey) or die ($con->error);
    $totalData = $query_residency->num_rows;
   



    $__orderColumns = ['certificate_request.request_id', 'certificate_request.purpose', 'certificate_request.date_request', 'certificate_request.status'];
    $sql_residencey .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY certificate_request.date_request DESC');
    $sql_residencey .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);
    

    $query_residency = $con->query($sql_residencey) or die ($con->error);

$data = [];
while($row_residency = $query_residency->fetch_assoc()){

  $date_today = date("Y-m-d");  

    if($row_residency['status'] == 'PENDING'){
      $status = '<span class="badge badge-warning">'.$row_residency['status'].'</span>';
      $tools = '<i  style="cursor: pointer;  color: yellow;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fas fa-eye text-lg px-2 acceptStatus" id="'.$row_residency['residence_id'].'" data-id="'.$row_residency['id'].'" data-toggle="tooltip" data-placement="left" title="View Request"></i> 
               ';
    }elseif($row_residency['status'] == 'ACCEPTED'){
      $status = '<span class="badge badge-success">'.$row_residency['status'].'</span>';
      
      if($row_residency['date_expired'] < $date_today ){
        $tools = '  <i  style="cursor: pointer;  color: red;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fas fa-times-circle text-lg px-2 acceptStatus" id="'.$row_residency['residence_id'].'" data-id="'.$row_residency['id'].'" data-toggle="tooltip" data-placement="left" title="Expired"></i>';
      }else{
        $tools = '<a href="printRequest.php?request='.$row_residency['residence_id'].'&purpose='.$row_residency['id'].'" target="_blank"  style="cursor: pointer;  color: pink;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fas fa-print text-lg px-2 printRequest"  data-toggle="tooltip" data-placement="left" title="Print"> </a>
        <i  style="cursor: pointer;  color: lime;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fas fa-check text-lg px-2 acceptStatus" id="'.$row_residency['residence_id'].'" data-id="'.$row_residency['id'].'" data-toggle="tooltip" data-placement="left" title="View Record"></i>';
      }




    }else{
      $status = '<span class="badge badge-danger">'.$row_residency['status'].'</span>';
      $tools = ' <i  style="cursor: pointer;  color: red;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fas fa-times text-lg px-2 acceptStatus" id="'.$row_residency['residence_id'].'" data-id="'.$row_residency['id'].'" data-toggle="tooltip" data-placement="left" title="View Record"></i>';
    }

      if($row_residency['date_issued'] != ''){
        $date_issued = date("m/d/Y", strtotime($row_residency['date_issued']));
      }else{
        $date_issued = '';
      }

      if($row_residency['date_expired'] != ''){
        $date_expired = date("m/d/Y", strtotime($row_residency['date_expired']));
      }else{
        $date_expired = '';
      }


  

    $subdata = [];
    $subdata[] = $row_residency['residence_id'];
    $subdata[] = $row_residency['first_name'] .' '. $row_residency['last_name'];
    $subdata[] = $row_residency['purpose'];
    $subdata[] = $row_residency['date_request'];
    $subdata[] =  $date_issued;
    $subdata[] =  $date_expired;
    $subdata[] = $status;
    $subdata[] = $tools;
    $data[] = $subdata;
}



$json_residency = [
  'draw' => intval($_REQUEST['draw'] ?? 0),
  'recordsTotal' => intval($totalData),
  'recordsFiltered' => intval($totalData),
  'data' => $data,
  'total' => number_format($totalData),
];

echo json_encode($json_residency);

}catch(Exception $e){
  echo $e->getMessage();
}







?>