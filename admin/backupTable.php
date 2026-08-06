<?php 


include_once '../connection.php';
include_once '../includes/auth_admin.php';


try{


  $sql = "SELECT * FROM backup ORDER BY id DESC";
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->execute();
  $result = $stmt->get_result();
  $count = $result->num_rows;
  $totalData = $count;
  $totalFiltered = $totalData;



  $data = [];
  while($row = $result->fetch_assoc()){

    $subdata = [];
    $subdata[] = $row['path'];
    $subdata[] = '<a href="../backup/'.$row['path'].'" style="cursor: pointer;  color: yellow;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fas fa-download text-lg" download data-toggle="tooltip" data-placement="left" title="Download File"> </a> 
    <i style="cursor: pointer;  color: red;  text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-times text-lg px-2 deleteFile"  id="'.$row['id'].'" data-toggle="tooltip" data-placement="bottom" title="Delete File"></i> ';
    $data[] = $subdata;

  }

  $json_data = [
    'draw' => intval($_REQUEST['draw']),
    'totalRecord' => intval($totalData),
    'totalFiltered' => intval($totalFiltered),
    'data' => $data,
  ];

  echo json_encode($json_data);



}catch(Exception $e){
  echo $e->getMessage();
}








?>