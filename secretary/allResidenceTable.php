<?php 

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/barangay_context.php';
require_once '../includes/datatables_helper.php';

try{
  

$first_name = $con->real_escape_string($_POST['first_name']);
$middle_name = $con->real_escape_string($_POST['middle_name']);
$last_name = $con->real_escape_string($_POST['last_name']);
$status = $con->real_escape_string($_POST['status']);
$voters =  $con->real_escape_string($_POST['voters']);
$age =  $con->real_escape_string($_POST['age']);
$pwd =  $con->real_escape_string($_POST['pwd']);
$senior =  $con->real_escape_string($_POST['senior']);
$resident_id =  $con->real_escape_string($_POST['resident_id']);
$single_parent =  $con->real_escape_string($_POST['single_parent']);
$purok = trim($_POST['purok'] ?? '');

$whereClause = barangay_residents_where_clause($con, ["residence_status.archive = 'NO'"]);

if(!empty($first_name))  
$whereClause[] = "first_name LIKE '%" .$first_name. "%'";

if(!empty($middle_name))  
$whereClause[] = "middle_name LIKE '%" .$middle_name. "%'";

if(!empty($last_name))  
$whereClause[] = "last_name LIKE '%" .$last_name. "%'";

if(!empty($pwd))  
$whereClause[] = "residence_status.pwd='".$pwd."'";

if(!empty($single_parent))  
$whereClause[] = "residence_status.single_parent='".$single_parent."'";


if(!empty($senior))  
$whereClause[] = "residence_status.senior='".$senior."'";

if(!empty($status))  
$whereClause[] = "residence_status.status='".$status."'";

if(!empty($voters))
$whereClause[] = "residence_status.voters='".$voters."'";

if(!empty($age))
$whereClause[] = "residence_information.age='".$age."'";


if(!empty($resident_id))
$whereClause[] = "residence_information.residence_id='$resident_id'";

barangay_append_purok_filter($con, $whereClause, $purok);

$where = barangay_sql_where($whereClause);




$sql = "SELECT 
residence_information.residence_id, 
residence_information.first_name,  
residence_information.middle_name, 
residence_information.last_name, 
residence_information.age, 
residence_information.image, 
residence_information.image_path, 
residence_status.residence_id,
residence_status.voters,
residence_status.pwd_info,
residence_status.single_parent,
residence_status.pwd,
residence_status.status
FROM residence_information
INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id" . $where;

$countSql = "SELECT COUNT(*) AS total
FROM residence_information
INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id" . $where;
$countStmt = $con->prepare($countSql) or die($con->error);
$countStmt->execute();
$totalData = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalFiltered = $totalData;







$__orderColumns = ['residence_information.residence_id','residence_information.residence_id','residence_information.first_name','residence_information.age','residence_status.pwd_info','residence_status.single_parent','residence_status.voters','residence_status.status'];
$sql .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY residence_status.date_added DESC');
$sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);



$query = $con->prepare($sql) or die ($con->error);
$query->execute();
$result = $query->get_result();
$data = [];
$canEditOrDeletePerson = barangay_user_can_edit_or_delete_person($con, (string) ($_SESSION['user_id'] ?? ''));
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

  if($row['status'] == 'ACTIVE'){
    $status = '<label class="switch">
                    <input type="checkbox" class="editStatus" data-status="ACTIVE"  id="'.$row['residence_id'].'"  checked>
                  <div class="slider round">
                    <span class="on ">ACTIVE</span>
                    <span class="off ">INACTIVE</span>
                  </div>
              </label>';
}else{
    $status = '<label class="switch">
                    <input type="checkbox" class="editStatus" id="'.$row['residence_id'].'" data-status="INACTIVE">
                  <div class="slider round">
                    <span class="off ">INACTIVE</span>
                    <span class="on ">ACTIVE</span>
                  </div>
              </label> ';
}


if($row['voters'] == 'YES'){
  $voters = '<span class="badge badge-success text-md">'.$row['voters'].'</span>';
}else{
  $voters = '<span class="badge badge-danger text-md">'.$row['voters'].'</span>';
}

if($row['single_parent'] == 'YES'){
  $single_parent = '<span class="badge badge-info text-md ">'.$row['single_parent'].'</span>';
}else{
  $single_parent = '<span class="badge badge-warning text-md ">'.$row['single_parent'].'</span>';
}
  $subdata = [];
  $subdata[] = $image;
  $subdata[] =  $row['residence_id'];
  $subdata[] =  ucfirst($row['first_name']).' '. $middle_name .' '. ucfirst($row['last_name']); 
  $subdata[] =  $row['age'];
  $subdata[] =  $row['pwd_info']; 
  $subdata[] =  $single_parent; 
  $subdata[] = $voters;
  $subdata[] = $status;
  $actions = '<i style="cursor: pointer;  color: yellow;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-user-edit text-lg px-3 viewResidence" id="'.$row['residence_id'].'"></i>';
  if ($canEditOrDeletePerson) {
    $actions .= '<i style="cursor: pointer;  color: red;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-times text-lg px-2 deleteResidence" id="'.$row['residence_id'].'"></i>';
  }
  $subdata[] = $actions;
  $data[] = $subdata;
}

$json_data = [
  'draw' => intval($_REQUEST['draw']),
  'recordsTotal' => intval($totalData),
  'recordsFiltered' => intval($totalFiltered),
  'data' => $data,
  'total'    => number_format($totalData),
];

echo json_encode($json_data);



}catch(Exception $e){
  echo $e->getMessage();
}





?>