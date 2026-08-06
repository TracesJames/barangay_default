<?php 



include_once '../connection.php';
include_once '../includes/auth_secretary.php';

try{


  if(isset($_REQUEST['official_id'])){

    $official_id = $con->real_escape_string($_REQUEST['official_id']);


    $sql_official_view = "SELECT official_information.first_name, official_information.middle_name, official_information.last_name, official_information.gender, official_information.age,
    official_information.address, official_information.contact_number, official_information.official_id, official_information.image, official_information.image_path, official_status.status, position.position, position.position_description 
    FROM official_status 
    INNER JOIN official_information ON official_status.official_id = official_information.official_id
    INNER JOIN position ON official_status.position = position.position_id
    
     WHERE official_information.official_id = ?";
    $stmt_official_view = $con->prepare($sql_official_view) or die ($con->error);
    $stmt_official_view->bind_param('s',$official_id);
    $stmt_official_view->execute();
    $result_official_view =  $stmt_official_view->get_result();

    $row_official_view = $result_official_view->fetch_assoc();

    if($row_official_view['middle_name'] != ''){
      $middle_name_view = $row_official_view['middle_name'][0].'.';
    }else{
      $middle_name_view = '';
    }
        
    if($row_official_view['image'] != '' || $row_official_view['image'] != null){
      $image = '<img class="img-circle elevation-2" src="'.$row_official_view['image_path'].'" alt="User Avatar">';
    }else{
      $image = '<img class="img-circle elevation-2" src="../assets/dist/img/image.png" alt="User Avatar">';
    }
    

  }




}catch(Exception $e){
  echo $e->getMessage();
}


?>


<!-- Modal -->
<div class="modal fade" id="viewOfficialModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <form id="requestForm" method="post">

        <div class="modal-header">
            <h5 class="modal-title"><i class="far fa-user"></i> Profile</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
          </div>
      <div class="modal-body">
        <div class="container-fluid">


            <div class="card card-widget widget-user-2">
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-black">
                <div class="widget-user-image">
                  <?= $image ?>
                </div>
                <!-- /.widget-user-image -->
                <h3 class="widget-user-username text-uppercase font-weight-bold"><?= $row_official_view['first_name'].' '. $middle_name_view.' '.  $row_official_view['last_name']  ?></h3>
                <h5 class="widget-user-desc "><?= strtoupper($row_official_view['position']) ?></h5>
              </div>
              <div class="card-footer p-0">
                <ul class="nav flex-column ">
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="OFFICIAL ID">
                    <span href="#" class="nav-link">
                      <i class="fas fa-id-card-alt text-fuchsia text-lg"></i> <span class="float-right "><?= $row_official_view['official_id']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="ADDRESS">
                    <span href="#" class="nav-link">
                      <i class="fas fa-map-marker-alt text-fuchsia text-lg"></i> <span class="float-right "><?= $row_official_view['address']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="GENDER">
                    <span href="#" class="nav-link" >
                      <i class="fas fa-venus-mars text-fuchsia text-lg"></i> <span class="float-right "><?= $row_official_view['gender']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="AGE">
                    <span href="#" class="nav-link" >
                      <i class="fa fa-child text-fuchsia text-lg" ></i> <span class="float-right "><?= $row_official_view['age']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="CONTACT NUMBER">
                    <span href="#" class="nav-link" >
                      <i class="fa fa-phone text-fuchsia text-lg" ></i> <span class="float-right "><?= $row_official_view['contact_number']?></span>
                    </span>
                  </li>
                  <li class="nav-item" data-toggle="tooltip" data-placement="bottom" title="STATUS">
                    <span href="#" class="nav-link" >
                      <i class="fa fa-exclamation text-fuchsia text-lg" ></i> 
                      <?php 
                      if($row_official_view['status'] == 'ACTIVE'){
                          echo ' <span class="float-right badge badge-success"> '.$row_official_view['status'].'</span> ';
               
                        }else{
                          echo ' <span class="float-right badge badge-danger"> '.$row_official_view['status'].'</span> ';
                        }
                      
                      ?>
                      </span>
                  </li>
                </ul>
                   
              

              </div>
            </div>

         

      


        </div>
      </div>
      <div class="modal-footer">
       
  
      <button type="button" class="btn bg-black btn-flat elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
      </div>

      </form>
    </div>
  </div>
</div>

<script>
  $(function () {
       $('[data-toggle="tooltip"]').tooltip()
    })
</script>