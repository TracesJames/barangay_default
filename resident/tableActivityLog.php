<?php 


include_once '../connection.php';
include_once '../includes/auth_resident.php';


try{

  $col = ['message', 'date'];

  $user_id = $con->real_escape_string(trim($_REQUEST['user_id']));


  $sql = "SELECT * FROM activity_log WHERE user_id = '$user_id'";
  $query = $con->query($sql) or die ($con->error);
  $totalData = $query->num_rows;
  $totalFiltered = $totalData;



  if(isset($_REQUEST['order'])){
    $sql .= ' ORDER BY '.
    $col[$_REQUEST['order']['0']['column']].
    ' '.
    $_REQUEST['order']['0']['dir'].
    ' ';
  }else{
    $sql .= ' ORDER BY date DESC ';
  }

  if($_REQUEST['length'] != -1){
    $sql .= ' LIMIT '.
    $_REQUEST['start'].
    ' ,'.
    $_REQUEST['length'].
    ' ';
  }

  $query = $con->query($sql) or die ($con->error);
  $data = [];

  while($row  = $query->fetch_assoc()){
    $subdata = [];
    $subdata[] = $row['message'];
    $subdata[] = $row['date'];
    $data[] = $subdata;
  }

  $json_data = [
    'draw' => intval($_REQUEST['draw']),
    'recordsTotal' =>  intval($totalData),
    'recordsFiltered' =>  intval($totalFiltered),
    'data' => $data,
  ];

  echo json_encode($json_data);

}catch(Exception $e){
  echo $e->getMessage();
}






?>