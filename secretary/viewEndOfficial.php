<?php 

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
try{


  
if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'secretary'){

  $user_id = $_SESSION['user_id'];
  $sql_user = "SELECT * FROM `users` WHERE `id` = ? ";
  $stmt_user = $con->prepare($sql_user) or die ($con->error);
  $stmt_user->bind_param('s',$user_id);
  $stmt_user->execute();
  $result_user = $stmt_user->get_result();
  $row_user = $result_user->fetch_assoc();
  $first_name_user = $row_user['first_name'];
  $last_name_user = $row_user['last_name'];
  $user_type = $row_user['user_type'];
  $user_image = $row_user['image'];


  $sql = "SELECT * FROM `barangay_information`";
$query = $con->prepare($sql) or die ($con->error);
$query->execute();
$result = $query->get_result();
while($row = $result->fetch_assoc()){
    $barangay = $row['barangay'];
    $zone = $row['zone'];
    $district = $row['district'];
    $image = $row['image'];
    $image_path = $row['image_path'];
    $id = $row['id'];
}

if(isset($_REQUEST['request'])){
  $official_id = $con->real_escape_string(trim($_REQUEST['request']));
  $sql_official = "SELECT official_end_information.*, official_end_status.* FROM official_end_information 
  INNER JOIN official_end_status ON official_end_information.official_id = official_end_status.official_id WHERE official_end_information.official_id = ?";
  $stmt_official = $con->prepare($sql_official) or die ($con->error);
  $stmt_official->bind_param('s',$official_id);
  $stmt_official->execute();
 $result =  $stmt_official->get_result();
  $row_official = $result->fetch_assoc();
}


}else{
 echo '<script>
        window.location.href = "../login.php";
      </script>';
}

}catch(Exception $e){
  echo $e->getMessage();
}




?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>

 
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
 
 
 
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini barangay-portal">
<div class="wrapper">

<!-- Preloader -->
<div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__wobble " src="../assets/dist/img/loader.gif" alt="AdminLTELogo" height="70" width="70">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <h5><a class="nav-link text-white" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block" style="font-variant: small-caps;">
        <h5 class="nav-link text-white" ><?= $barangay ?></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" >-</h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" ><?= $zone ?></h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" >-</h5>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white" ><?= $district ?></h5>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-user"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <?php 
                if($user_image != '' || $user_image != null || !empty($user_image)){
                  echo '<img src="../assets/dist/img/'.$user_image.'" class="img-size-50 mr-3 img-circle" alt="User Image" style="width:50px;height:50px;object-fit:cover;">';
                }else{
                  echo '<img src="../assets/dist/img/image.png" class="img-size-50 mr-3 img-circle" alt="User Image" style="width:50px;height:50px;object-fit:cover;">';
                }
              ?>
            
              <div class="media-body">
                <h3 class="dropdown-item-title py-3">
                  <?= ucfirst($first_name_user) .' '. ucfirst($last_name_user) ?>
                </h3>
              </div>
            </div>
            <!-- Message End -->
          </a>         
          <div class="dropdown-divider"></div>
          <a href="../logout.php" class="dropdown-item dropdown-footer">LOGOUT</a>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
    <!-- Brand Logo -->
    <a href="#" class="brand-link text-center">
    <?php 
        if($image != '' || $image != null || !empty($image)){
          echo '<img src="'.$image_path.'" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }else{
          echo ' <img src="../assets//logo//logo.png" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }

      ?>
      <span class="brand-text font-weight-light"></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
    

    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../assets/dist/img/logo.png" class="img-circle elevation-5 img-bordered-sm" alt="User Image">
        </div>
        <div class="info text-center">
          <a href="#" class="d-block text-bold">OFFICIAL</a>
        </div>
      </div>
      <!-- Sidebar Menu -->
      <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>
          </li>
          <li class="nav-item menu-open">
            <a href="#" class="nav-link  bg-indigo">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>
              Barangay Official
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
             
              <li class="nav-item">
                <a href="allOfficial.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>List of Official</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="officialEndTerm.php" class="nav-link active">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Official End Term</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link ">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Residence
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="newResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Residence</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="allResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>All Residence</p>
                </a>
              </li>              <li class="nav-item">
                <a href="familyHouseholdHead.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Family House Hold</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="archiveResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Archive Residence</p>
                </a>
              </li>
            </ul>
          </li>
          
          <li class="nav-item ">
            <a href="requestCertificate.php" class="nav-link">
              <i class="nav-icon fas fa-certificate"></i>
              <p>
                Certificate
              </p>
            </a>
          </li>
          <li class="nav-item ">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user-shield"></i>
              <p>
                Users
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="usersResident.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Resident</p>
                </a>
              </li>

            </ul>
          </li>
       
          <li class="nav-item">
            <a href="blotterRecord.php" class="nav-link">
              <i class="nav-icon fas fa-clipboard"></i>
              <p>
                Blotter Record
              </p>
            </a>
          </li>
          <?php /* super-admin-only-nav */ if (!empty($isSuperAdmin)) : ?>

          <li class="nav-item">
            <a href="report.php" class="nav-link">
              <i class="nav-icon fas fa-bookmark"></i>
              <p>
                Reports
              </p>
            </a>
          </li>
         
          <li class="nav-item">
            <a href="systemLog.php" class="nav-link">
              <i class="nav-icon fas fa-history"></i>
              <p>
                System Logs
              </p>
            </a>
          </li>
          <?php endif; ?>
</ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    
  <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
            
            </div><!-- /.col -->
            <div class="col-sm-6">
            <a href="#" class="btn bg-black float-lg-right elevation-5 px-3 btn-flat" onclick="history.go(-1)"><i class="fas fa-backward"></i>  BACK</a>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
   

    <!-- Main content -->
    <section class="content mt-3">
      <div class="container-fluid">


     
      <form id="editOfficialForm" method="POST" enctype="multipart/form-data" autocomplete="off">
        <div class="row mb-3">
          <div class="col-sm-4">
            <div class="card card-indigo card-outline h-100">
              <div class="card-header">
                <div class="card-title">
                    View Details Official
                </div>
              </div>
              <div class="card-body box-profile">
                <div class="text-center">
                <?php 
                
                if($row_official['image'] != ''){
                  echo  '<img class="profile-user-img img-fluid img-thumbnail" src="'.$row_official['image_path'].'" alt="User profile picture" style="cursor: pointer;" id="image_official">';
                }else{
                  echo '<img class="profile-user-img img-fluid img-thumbnail" src="../assets/dist/img/blank_image.png" alt="User profile picture" style="cursor: pointer;" id="image_official">';
                }
                
                ?>
                  
                  <input type="file" name="edit_image" id="edit_image" style="display: none;">
                </div>

                <h3 class="profile-username text-center "><span id="keyup_first_name"></span> <span id="keyup_last_name"></span></h3>

                <div class="row">
                  <div class="col-sm-12">
                    <input type="hidden" name="official_id" value="<?= $official_id ?>">
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label>Position</label>
                        <select name="edit_position" id="edit_position" class="form-control form-control-sm text-uppercase">
                      
                          <?php 
                          
                          $sql_position = "SELECT position_id, position FROM position";
                          $stmt = $con->prepare($sql_position) or die ($con->error);
                          $stmt->execute();
                          $result_position = $stmt->get_result();
                          while($row_position = $result_position->fetch_assoc()){
                         
                            echo ' <option value="'.$row_position['position_id'].'" '.($row_position['position_id'] == $row_official['position'] ? 'selected': '').'>'.$row_position['position'].'</option>';
                          }
                          
                          ?>
                        
                        
                        </select>
                        <input type="hidden" id="edit_position_check" value="false">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group ">
                      <label >Start Year</label>
                      <input type="number" class="form-control" id="edit_term_from" name="edit_term_from" min="1900" max="2100" maxlength="4" placeholder="YYYY" value="<?= barangay_h(official_term_year_value($row_official['term_from'] ?? '')) ?>">
                      <input type="hidden" id="edit_term_from_check" value="false">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group ">
                      <label >End Year</label>
                      <input type="number" class="form-control" id="edit_term_to" name="edit_term_to" min="1900" max="2100" maxlength="4" placeholder="YYYY" value="<?= barangay_h(official_term_year_value($row_official['term_to'] ?? '')) ?>">
                      <input type="hidden" id="edit_term_to_check" value="false">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label>Voters</label>
                      <select name="edit_voters" id="edit_voters" class="form-control">
                        <option value="NO" <?= $row_official['voters'] == 'NO'? 'selected': ''; ?>>NO</option>
                        <option value="YES" <?= $row_official['voters'] == 'YES'? 'selected': ''; ?>>YES</option>
                      </select>
                      <input type="hidden" value="false" id="edit_voters_check">
                    </div>
                  </div>
                 

                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label >Date of Birth</label>
                      <input type="date" class="form-control" id="edit_birth_date" name="edit_birth_date" value="<?= barangay_html_date($row_official['birth_date'] ?? '') ?>">
                      <input type="hidden" id="edit_birth_date_check" value='false'>
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label >Place of Birth</label>
                      <input type="text" class="form-control" id="edit_birth_place" name="edit_birth_place" value="<?= $row_official['birth_place'] ?>">
                      <input type="hidden" id="edit_birth_place_check" value="false">
                    </div>
                  </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                      <label>Pwd</label>
                      <select name="edit_pwd" id="edit_pwd" class="form-control">
                        <option value="NO" <?= $row_official['pwd'] == 'NO'? 'selected': ''; ?>>NO</option>
                        <option value="YES" <?= $row_official['pwd'] == 'YES'? 'selected': ''; ?>>YES</option>
                      </select>
                      <input type="hidden" id="edit_pwd_check" value="false">
                    </div>
                  </div>

                  <div class="col-sm-12" style="display: <?= $row_official['pwd'] == 'NO'?  'none': '' ; ?>">
                    <div class="form-group">
                      <label>Pwd Type</label>
                      <input type="text" class="form-control" id="edit_pwd_info" name="edit_pwd_info" value="<?= $row_official['pwd_info'] ?>" >
                
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label>Single Parent</label>
                      <select name="edit_single_parent" id="edit_single_parent" class="form-control">
                        <option value="NO" <?= $row_official['single_parent'] == 'NO'? 'selected': ''; ?>>NO</option>
                        <option value="YES" <?= $row_official['single_parent'] == 'YES'? 'selected': ''; ?>>YES</option>
                      </select>
                      <input type="hidden" id="edit_single_parent_check" value="false">
                    </div>
                  </div>



               
              </div>
              <!-- /.card-body -->
            </div>
          </div>
          <div class="col-sm-8">
            <div class="card card-indigo card-tabs h-100">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="basic-info-tab" data-toggle="pill" href="#basic-info" role="tab" aria-controls="basic-info" aria-selected="true">Basic Info</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="other-info-tab" data-toggle="pill" href="#other-info" role="tab" aria-controls="other-info" aria-selected="false">Other Info</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="guardian-tab" data-toggle="pill" href="#guardian" role="tab" aria-controls="guardian" aria-selected="false">Guardian</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                  <div class="tab-pane fade active show" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                      <p class="lead text-center">Personal Details</p>
                      <div class="row">
                        <div class="col-sm-12">
                          <div class="form-group ">
                            <label>First Name </label>
                            <input type="text" class="form-control" id="edit_first_name" name="edit_first_name" value="<?= $row_official['first_name'] ?>">
                            <input type="hidden" value="false" id="edit_first_name_check">
                          </div>
                        </div>
                        <div class="col-sm-12">
                          <div class="form-group ">
                            <label>Middle Name </label>
                            <input type="text" class="form-control" id="edit_middle_name" name="edit_middle_name" value="<?= $row_official['middle_name'] ?>">
                            <input type="hidden" id="edit_middle_name_check" value="false">
                          </div>
                        </div>
                        <div class="col-sm-12">
                          <div class="form-group ">
                            <label>Last Name </label>
                            <input type="text" class="form-control" id="edit_last_name" name="edit_last_name" value="<?= $row_official['last_name'] ?>">
                            <input type="hidden" value="false" id="edit_last_name_check">
                          </div>  
                        </div>
                      </div>
                        <div class="row">
                          <div class="col-sm-6">
                            <div class="form-group ">
                              <label >Suffix </label>
                              <input type="text" class="form-control" id="edit_suffix" name="edit_suffix" value="<?= $row_official['suffix'] ?>">
                              <input type="hidden" id="edit_suffix_check" value="false">
                            </div>
                          </div>
                          
                          <div class="col-sm-6">
                            <div class="form-group ">
                              <label >Gender</label>
                              <select name="edit_gender" id="edit_gender" class="form-control">
                                <option value="Male" <?= $row_official['gender'] == 'Male'? 'selected': ''; ?>>Male</option>
                                <option value="Female" <?= $row_official['gender'] == 'Female'? 'selected': '' ?>>Female</option>
                              </select>
                              <input type="hidden" id="edit_gender_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group ">
                              <label >Civil Status</label>
                              <select name="edit_civil_status" id="edit_civil_status" class="form-control">
                                <option value="Single"<?= $row_official['civil_status'] == 'Single'? 'selected': '' ?> >Single</option>
                                <option value="Married"<?= $row_official['civil_status'] == 'Married'? 'selected': '' ?>>Married</option>
                              </select>
                              <input type="hidden" id="edit_civil_status_check" value="false">
                            </div>
                          </div>
                        
                          
                          <div class="col-sm-6">
                            <div class="form-group ">
                              <label >Religion</label>
                              <input type="text" class="form-control" id="edit_religion" name="edit_religion" value="<?= $row_official['religion'] ?>">
                              <input type="hidden" id="edit_religion_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group ">
                              <label >Nationality</label>
                              <input type="text" class="form-control" id="edit_nationality" name="edit_nationality" value="<?= $row_official['nationality'] ?>">
                              <input type="hidden" id="edit_nationality_check" value="false">
                            </div>
                          </div>                              
                        </div>
                  </div>
                  <div class="tab-pane fade" id="other-info" role="tabpanel" aria-labelledby="other-info-tab">
                        <p class="lead text-center">Address</p>
                        <div class="row">
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Municipality</label>
                              <input type="text" class="form-control" id="edit_municipality" name="edit_municipality" value="<?= $row_official['municipality'] ?>">
                              <input type="hidden" id="edit_municipality_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Zip</label>
                              <input type="text" class="form-control" id="edit_zip" name="edit_zip" value="<?= $row_official['zip'] ?>">
                              <input type="hidden" id="edit_zip_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Barangay</label>
                              <input type="text" class="form-control" id="edit_barangay" name="edit_barangay" value="<?= $row_official['barangay'] ?>">
                              <input type="hidden" id="edit_barangay_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>House Number</label>
                              <input type="text" class="form-control" id="edit_house_number" name="edit_house_number" value="<?= $row_official['house_number'] ?>">
                              <input type="hidden" id="edit_house_number_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Street</label>
                              <input type="text" class="form-control" id="edit_street" name="edit_street" value="<?= $row_official['street'] ?>">
                              <input type="hidden" id="edit_street_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Address</label>
                              <input type="text" class="form-control" id="edit_address" name="edit_address" value="<?= $row_official['address'] ?>">
                              <input type="hidden" id="edit_address_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Email Address</label>
                              <input type="text" class="form-control" id="edit_email_address" name="edit_email_address" value="<?= $row_official['email_address'] ?>">
                              <input type="hidden" id="edit_email_address_check" value="false">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label >Contact Number</label>
                              <input type="text" class="form-control" maxlength="11" id="edit_contact_number" name="edit_contact_number" value="<?= $row_official['contact_number'] ?>">
                              <input type="hidden" id="edit_contact_number_check" value="false">
                            </div>
                          </div>
                        </div>
     

                  </div>
                  <div class="tab-pane fade" id="guardian" role="tabpanel" aria-labelledby="guardian-tab">
                   
                      <p class="lead text-center">Guardian</p>
                      <div class="row">

                        <div class="col-sm-12">
                          <div class="form-group">
                            <label>Guardian</label>
                            <input type="text" class="form-control" id="edit_guardian" name="edit_guardian" value="<?= $row_official['guardian'] ?>">
                            <input type="hidden" id="edit_guardian_check" value="false">
                          </div>
                        </div>
                        <div class="col-sm-12">
                          <div class="form-group">
                            <label>Guardian Contact</label>
                            <input type="text" class="form-control" maxlength="11" id="edit_guardian_contact" name="edit_guardian_contact" value="<?= $row_official['guardian_contact'] ?>">
                            <input type="hidden" id="edit_guardian_contact_check" value="false">
                          </div>
                        </div>

                      </div>
                    
                  </div>
                </div>
              </div>
            
              <!-- /.card -->
            </div>

          </div>
        </div>
        </form>


      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

 

  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; <?php echo date("Y"); ?> - <?php echo date('Y', strtotime('+1 year'));  ?> </strong>
    
    <div class="float-right d-none d-sm-inline-block">
    </div>
  </footer>
</div>
<!-- ./wrapper -->



<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>

<!-- Bootstrap -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/popper/umd/popper.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../assets/plugins/jszip/jszip.min.js"></script>
<script src="../assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="../assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<script src="../assets/plugins/moment/moment.min.js"></script>
<script src="../assets/plugins/chart.js/Chart.min.js"></script>
<script src="../assets/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../assets/plugins/jquery-validation/additional-methods.min.js"></script>
<script src="../assets/plugins/inputmask/min/jquery.inputmask.bundle.min.js"></script>
<script src="../assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>


<script>
// Restricts input for each element in the set of matched elements to the given inputFilter.
(function($) {
  $.fn.inputFilter = function(inputFilter) {
    return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
      if (inputFilter(this.value)) {
        this.oldValue = this.value;
        this.oldSelectionStart = this.selectionStart;
        this.oldSelectionEnd = this.selectionEnd;
      } else if (this.hasOwnProperty("oldValue")) {
        this.value = this.oldValue;
        this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
      } else {
        this.value = "";
      }
    });
  };
}(jQuery));

 
  $("#edit_contact_number, #edit_zip, #edit_guardian_contact, #edit_age").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });


  $("#edit_first_name, #edit_middle_name, #edit_last_name, #edit_suffix, #edit_religion, #edit_nationality, #edit_municipality, #edit_guardian").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
  $("#edit_street, #edit_birth_place, #edit_house_number").inputFilter(function(value) {
  return /^[0-9a-z, ,-]*$/i.test(value); 
  });

</script>
</body>
</html>
