<?php 


include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/datatables_helper.php';

try{


  $col = ['id','message','date'];
 
  
  $sql = "SELECT * FROM activity_log";
  


  if (!empty($_REQUEST['search']['value'])) {
    $search = datatables_sql_like($con, (string) $_REQUEST['search']['value']);
    $sql .= " WHERE message LIKE '" . $search . "' ";
    $sql .= " OR date LIKE '" . $search . "' ";
  }
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->execute();
  $result = $stmt->get_result();
  $totalData = $result->num_rows;
  

  $__orderColumns = ['activity_log.date','activity_log.message','activity_log.status'];
$sql .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY activity_log.id DESC');
$sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);
  

  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->execute();
  $result = $stmt->get_result();



  $data = [];

 

  while($row = $result->fetch_assoc()){
    $subdata = [];
    $subdata[] = $row['id'];
    $subdata[] = $row['message'];
    $subdata[] = $row['date'];
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
  echo $e->getMessage();
}










?>