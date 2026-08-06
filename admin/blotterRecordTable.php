<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/datatables_helper.php';

try{
  header('Content-Type: application/json; charset=utf-8');

  $whereClause = barangay_blotter_where_clause($con);

  if(isset($_REQUEST['search']['value']) && $_REQUEST['search']['value'] !== '') {
    $search = $con->real_escape_string($_REQUEST['search']['value']);
    $whereClause[] = "(blotter_record.status LIKE '%" . $search . "%' "
      . "OR blotter_record.blotter_id LIKE '%" . $search . "%' "
      . "OR blotter_record.remarks LIKE '%" . $search . "%' "
      . "OR blotter_record.type_of_incident LIKE '%" . $search . "%' "
      . "OR blotter_record.location_incident LIKE '%" . $search . "%' "
      . "OR blotter_record.date_incident LIKE '%" . $search . "%' "
      . "OR blotter_record.date_reported LIKE '%" . $search . "%')";
  }

  $where = barangay_sql_where($whereClause);
  $sql_blooter_check = "SELECT * FROM blotter_record" . $where;

  $query_blotter_check = $con->prepare($sql_blooter_check) or die ($con->error);
  $query_blotter_check->execute();
  $result_blotter_check = $query_blotter_check->get_result(); 
  $totalData = $result_blotter_check->num_rows;


  $__orderColumns = ['blotter_id', 'blotter_id', 'status', 'remarks', 'type_of_incident', 'location_incident', 'date_incident', 'date_reported'];
  $sql_blooter_check .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY date_reported DESC');
  $sql_blooter_check .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);


  

  $query_blotter_check = $con->prepare($sql_blooter_check) or die ($con->error);
  $query_blotter_check->execute();
  $result_blotter_check = $query_blotter_check->get_result(); 
  $data = [];
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

    $subdata = [];
    $subdata[] = '<input type="checkbox" id="'. $row_blotter_check['blotter_id'].'" class="sub_checkbox">';
    $subdata[] = $row_blotter_check['blotter_id'];
    $subdata[] = $status_blotter;
    $subdata[] = $remarks_blotter;
    $subdata[] = $row_blotter_check['type_of_incident'];
    $subdata[] = $row_blotter_check['location_incident'];
    $subdata[] = $date_incident;
    $subdata[] = $date_reported;
    $subdata[] =   '<i style="cursor: pointer;  color: yellow;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-book-open text-lg px-2 viewRecords" id="'.$row_blotter_check['blotter_id'].'"></i>';

    $data[] = $subdata;
  }


  $json_data = [
    'draw' => intval($_REQUEST['draw']),
    'recordsTotal' => intval($totalData),
    'recordsFiltered' => intval($totalData),
    'data' => $data,
  ];

    echo json_encode($json_data);

}catch(Exception $e){
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(500);
  echo json_encode([
    'draw' => intval($_REQUEST['draw'] ?? 0),
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => [],
    'error' => $e->getMessage(),
  ]);
}
