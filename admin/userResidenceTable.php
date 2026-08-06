<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/datatables_helper.php';


try{

  $first_name = $con->real_escape_string($_REQUEST['first_name']);
  $middle_name = $con->real_escape_string($_REQUEST['middle_name']);
  $last_name = $con->real_escape_string($_REQUEST['last_name']);
  $resident_id = $con->real_escape_string($_REQUEST['resident_id']);

  $whereClause = barangay_residents_where_clause($con, [
    "users.user_type != 'admin'",
    "users.user_type != 'secretary'",
  ]);

  if(!empty($resident_id))

  $whereClause[] = "residence_information.residence_id='$resident_id'";


  if(!empty($first_name))

    $whereClause[] = "residence_information.first_name LIKE '%" .$first_name. "%' ";

  if(!empty($middle_name))

    $whereClause[] = "residence_information.middle_name LIKE '%" .$middle_name. "%' ";

  if(!empty($last_name))

    $whereClause[] = "residence_information.last_name LIKE '%" .$last_name. "%' ";

   

  $where = barangay_sql_where($whereClause);
 

  $sql = "SELECT residence_information.residence_id, 
  residence_information.first_name, 
  residence_information.middle_name, 
  residence_information.last_name, 
  residence_information.image,
  residence_information.image_path, 
  users.username, users.password
  FROM residence_information
  INNER JOIN users ON residence_information.residence_id = users.id
  INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id" . $where;
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->execute();
  $stmt->store_result();
  $totalData = $stmt->num_rows;
  $totalFiltered = $totalData;



  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->execute();
  $stmt->store_result();
  $totalData = $stmt->num_rows;

  $__orderColumns = ['users.first_name','users.username','users.contact_number'];
$sql .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY users.username DESC');
$sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

  


$stmt = $con->prepare($sql) or die ($con->error);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while($row = $result->fetch_assoc()){
  if($row['image'] != '' || $row['image'] != null || !empty($row['image'])){
    $image = '<span style="cursor: pointer;" class="pop"><img src="'.$row['image_path'].'" alt="residence_image" class="img-circle" width="40"></span>';
  }else{
    $image = '<span style="cursor: pointer;" class="pop"><img src="../assets/dist/img/blank_image.png" alt="residence_image" class="img-circle"  width="40"></span>';
  }

  if($row['middle_name'] != ''){
    $middle_name = ucfirst($row['middle_name'])[0].'.';
  }else{
    $middle_name = '';
  }

  $subdata = [];
  $subdata[] = $image;
  $subdata[] = $row['residence_id'];
  $subdata[] =  ucfirst($row['first_name']).' '. $middle_name .' '. ucfirst($row['last_name']); 
  $subdata[] = $row['username'];
  $subdata[] = $row['password'];
  $subdata[] = '<i style="cursor: pointer;  color: yellow;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-user-edit text-lg px-3 viewUserResidence" id="'.$row['residence_id'].'"></i>
';
  $data[] = $subdata;
}

$json_data = [
  'draw' => intval($_REQUEST['draw']),
  'recordsTotal' => intval($totalData),
  'recordsFiltered' => intval($totalFiltered),
  'data' => $data,
];

echo json_encode($json_data);

}catch(Exception $e){
  echo $e->getMessage();
}



?>