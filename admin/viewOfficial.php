<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';
try{


  
if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){

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
if(isset($_REQUEST['official_id']) || isset($_REQUEST['request'])){
  $official_id = $con->real_escape_string(trim((string) ($_REQUEST['official_id'] ?? $_REQUEST['request'] ?? '')));
  if ($official_id === '') {
    echo '<script>window.location.href = "allOfficial.php";</script>';
    exit;
  }
  barangay_assert_official_in_scope($con, $official_id);
  $sql_official = "SELECT official_information.*, official_status.* FROM official_information 
  INNER JOIN official_status ON official_information.official_id = official_status.official_id WHERE official_information.official_id = ?";
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
          <a href="myProfile.php" class="dropdown-item">
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
      <img src="<?= barangay_h($sidebarLogo) ?>" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="<?= barangay_h($barangay) ?>" style="width: 70%;">
      <span class="brand-text font-weight-light"></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
    

    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../assets/dist/img/logo.png" class="img-circle elevation-5 img-bordered-sm" alt="User Image">
        </div>
        <div class="info text-center">
          <a href="#" class="d-block text-bold"><?= strtoupper($user_type) ?></a>
        </div>
      </div>
      <!-- Sidebar Menu -->
      <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link ">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>
          </li>
          <li class="nav-item menu-open" >
            <a href="#" class="nav-link bg-indigo">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>
              Barangay Official
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="newOfficial.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Official</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="allOfficial.php" class="nav-link active">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>List of Official</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="officialEndTerm.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Official End Term</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
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
                <a href="allResidence.php" class="nav-link">
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
          <?php if (!empty($canIssueCertificate)) { ?>
          <li class="nav-item ">
            <a href="requestCertificate.php" class="nav-link">
              <i class="nav-icon fas fa-certificate"></i>
              <p>
                Certificate
              </p>
            </a>
          </li>
          <?php } ?>
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
              <li class="nav-item">
                <a href="staffAccounts.php" class="nav-link">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Staff Accounts</p>
                </a>
              </li>

            </ul>
          </li>
          <li class="nav-item">
            <a href="position.php" class="nav-link">
              <i class="nav-icon fas fa-user-tie"></i>
              <p>
                Position
              </p>
            </a>
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
            <a href="settings.php" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p>
                Settings
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
          <li class="nav-item">
            <a href="backupRestore.php" class="nav-link">
              <i class="nav-icon fas fa-database"></i>
              <p>
                Backup/Restore
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
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label>Term</label>
                      <input type="text" class="form-control" id="edit_term_years" name="edit_term_years" placeholder="2021-2026" value="<?= barangay_h(official_term_range_display($row_official['term_from'] ?? '', $row_official['term_to'] ?? '')) ?>">
                      <input type="hidden" id="edit_term_years_check" value="false">
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
                <div class="col-sm-12" id="pwd_check" style="display: <?= $row_official['pwd'] == 'NO' ?  'none': ''; ?>;">
                  <div class="form-group ">
                    <label >TYPE OF PWD</label>
                      <input type="text" class="form-control" id="edit_pwd_info" name="edit_pwd_info" value="<?= $row_official['pwd_info'] ?>" <?= $row_official['pwd'] == 'NO' ?  'disabled': ''; ?>>
                      <input type="hidden" id="edit_pwd_info_check" value="false">
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
                              <label >Age </label>
                              <input type="text" class="form-control"  value="<?= $row_official['age'] ?>" disabled>
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
              <div class="card-footer">
                <button type="submit"  class="btn btn-success px-4 elevation-3"> <i class="fas fa-edit"></i> UPDATE</button>
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
  $(document).ready(function(){
    $.validator.addMethod('officialTermRange', function (value) {
      return /^\d{4}\s*-\s*\d{4}$/.test((value || '').trim());
    }, 'Enter term as YYYY-YYYY (example: 2021-2026)');

    $.validator.addMethod('officialTermOrder', function (value) {
      var parts = (value || '').trim().match(/^(\d{4})\s*-\s*(\d{4})$/);
      if (!parts) {
        return true;
      }
      return parseInt(parts[2], 10) >= parseInt(parts[1], 10);
    }, 'End year must be the same as or after start year');

    $(function () {

      $("#edit_pwd").change(function(){
      var pwd_check = $(this).val();

      if(pwd_check == 'YES'){
        $("#pwd_check").css('display', 'block');
        $("#edit_pwd_info").prop('disabled', false);
      }else{
        $("#pwd_check").css('display', 'none');
        $("#edit_pwd_info").prop('disabled', true);
      }

    })

            var edit_first_name = $("#edit_first_name").val();
            var edit_last_name = $("#edit_last_name").val();
            var edit_position = $("#edit_position").val();
            var edit_term_years = $("#edit_term_years").val();
            var edit_voters = $("#edit_voters").val();
            var edit_pwd = $("#edit_pwd").val();
            var edit_birth_date = $("#edit_birth_date").val();
            var edit_birth_place = $("#edit_birth_place").val();
            var edit_middle_name = $("#edit_middle_name").val();
            var edit_suffix = $("#edit_suffix").val();
            var edit_gender = $("#edit_gender").val();
            var edit_vivil_status = $("#edit_vivil_status").val();
            var edit_nationality = $("#edit_nationality").val();
            var edit_municipality = $("#edit_municipality").val();
            var edit_zip = $("#edit_zip").val();
            var edit_barangay = $("#edit_barangay").val();
            var edit_house_number = $("#edit_house_number").val();
            var edit_street = $("#edit_street").val();
            var edit_address = $("#edit_address").val();
            var edit_email_address = $("#edit_email_address").val();
            var edit_contact_number = $("#edit_contact_number").val();
            var edit_guardian = $("#edit_guardian").val();
            var edit_guardian_contact = $("#edit_guardian_contact").val();
            var edit_pwd_info = $("#edit_pwd_info").val();
            
            
              $("#edit_first_name").change(function(){

                var newFirstName = $(this).val();

              if(!(newFirstName == edit_first_name )){

                $("#edit_first_name_check").val('true');
              
              }else{

                  $("#edit_first_name_check").val('false');
              }

            })

          
            
            $("#edit_last_name").change(function(){

              var newLastName = $(this).val();

            if(!(newLastName == edit_last_name )){

              $("#edit_last_name_check").val('true');
            
            }else{

                $("#edit_last_name_check").val('false');
            }

          })

          $("#edit_position").change(function(){

            var newPosition = $(this).val();

            if(!(newPosition == edit_position )){

            $("#edit_position_check").val('true');

            }else{

              $("#edit_position_check").val('false');
            }

          })

          $("#edit_term_years").change(function(){

            var newTermYears = $(this).val();

          if(!(newTermYears == edit_term_years )){

          $("#edit_term_years_check").val('true');

          }else{

            $("#edit_term_years_check").val('false');
          }

        })

          $("#edit_voters").change(function(){

            var newVoters = $(this).val();

            if(!(newVoters == edit_voters )){

            $("#edit_voters_check").val('true');

            }else{

              $("#edit_voters_check").val('false');
            }

          })

          $("#edit_pwd").change(function(){

            var newPwd = $(this).val();

            if(!(newPwd == edit_pwd )){

            $("#edit_pwd_check").val('true');

            }else{

              $("#edit_pwd_check").val('false');
            }

          })

          $("#edit_pwd_info").change(function(){

          var newPwdInfo = $(this).val();

          if(!(newPwdInfo == edit_pwd_info )){

          $("#edit_pwd_info_check").val('true');

          }else{

            $("#edit_pwd_info_check").val('false');
          }

          })

          $("#edit_birth_date").change(function(){

            var newBday = $(this).val();

            if(!(newBday == edit_birth_date )){

            $("#edit_birth_date_check").val('true');

            }else{

              $("#edit_birth_date_check").val('false');
            }

            })

            $("#edit_birth_place").change(function(){

              var newBplace = $(this).val();

              if(!(newBplace == edit_birth_place )){

              $("#edit_birth_place_check").val('true');

              }else{

                $("#edit_birth_place_check").val('false');
              }

            })

            $("#edit_middle_name").change(function(){

              var newMiddleName = $(this).val();

              if(!(newMiddleName == edit_middle_name )){

              $("#edit_middle_name_check").val('true');

              }else{

                $("#edit_middle_name_check").val('false');
              }

            })

            $("#edit_suffix").change(function(){

              var new_suffix = $(this).val();

              if(!(new_suffix == edit_suffix )){

              $("#edit_suffix_check").val('true');

              }else{

                $("#edit_suffix_check").val('false');
              }

              })

              $("#edit_gender").change(function(){

                var newGender = $(this).val();

                if(!(newGender == edit_gender )){

                $("#edit_gender_check").val('true');

                }else{

                  $("#edit_gender_check").val('false');
                }

                })

                $("#edit_civil_status").change(function(){

                  var newCivil = $(this).val();

                  if(!(newCivil == edit_civil_status )){

                  $("#edit_civil_status_check").val('true');

                  }else{

                    $("#edit_civil_status_check").val('false');
                  }

                 })


                 $("#edit_religion").change(function(){

                  var newReligion = $(this).val();

                  if(!(newReligion == edit_religion )){

                  $("#edit_religion_check").val('true');

                  }else{

                    $("#edit_religion_check").val('false');
                  }

                  })


            $("#edit_nationality").change(function(){

              var newNationality = $(this).val();

              if(!(newNationality == edit_nationality )){

              $("#edit_nationality_check").val('true');

              }else{

                $("#edit_nationality_check").val('false');
              }

            })

            $("#edit_municipality").change(function(){

              var newMunicipality = $(this).val();

              if(!(newMunicipality == edit_municipality )){

              $("#edit_municipality_check").val('true');

              }else{

                $("#edit_municipality_check").val('false');
              }

            })


            
            $("#edit_zip").change(function(){

              var newZip = $(this).val();

              if(!(newZip == edit_zip )){

              $("#edit_zip_check").val('true');

              }else{

                $("#edit_zip_check").val('false');
              }

            })


            $("#edit_barangay").change(function(){

              var newBarangay = $(this).val();

              if(!(newBarangay == edit_barangay )){

              $("#edit_barangay_check").val('true');

              }else{

                $("#edit_barangay_check").val('false');
              }

            })

            $("#edit_house_number").change(function(){

              var newHnumber = $(this).val();

              if(!(newHnumber == edit_house_number )){

              $("#edit_house_number_check").val('true');

              }else{

                $("#edit_house_number_check").val('false');
              }

            })

            $("#edit_street").change(function(){

              var newStreet = $(this).val();

              if(!(newStreet == edit_street )){

              $("#edit_street_check").val('true');

              }else{

                $("#edit_street_check").val('false');
              }

            })

            $("#edit_address").change(function(){

              var newAddress = $(this).val();

              if(!(newAddress == edit_address )){

              $("#edit_address_check").val('true');

              }else{

                $("#edit_address_check").val('false');
              }

            })

            $("#edit_email_address").change(function(){

              var newEmail = $(this).val();

              if(!(newEmail == edit_email_address )){

              $("#edit_email_address_check").val('true');

              }else{

                $("#edit_email_address_check").val('false');
              }

            })

            $("#edit_contact_number").change(function(){

              var newNumber = $(this).val();

              if(!(newNumber == edit_contact_number )){

              $("#edit_contact_number_check").val('true');

              }else{

                $("#edit_contact_number_check").val('false');
              }

            })

            $("#edit_guardian").change(function(){

              var newGuardian = $(this).val();

              if(!(newGuardian == edit_guardian )){

              $("#edit_guardian_check").val('true');

              }else{

                $("#edit_guardian_check").val('false');
              }

              })

              $("#edit_guardian_contact").change(function(){

                var newGcontact = $(this).val();

                if(!(newGcontact == edit_guardian_contact )){

                $("#edit_guardian_contact_check").val('true');

                }else{

                  $("#edit_guardian_contact_check").val('false');
                }

              })

        $.validator.setDefaults({
          submitHandler: function (form) {
            Swal.fire({
              title: '<strong class="text-warning">Are you sure?</strong>',
              html: "<b>You want edit this details?</b>",
              type: 'info',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, edit it!',
              allowOutsideClick: false,
              width: '400px',
            }).then((result) => {
              if (result.value) {


                var formData = new FormData(form)
            
                formData.append("edit_first_name_check",$("#edit_first_name_check").val())
                formData.append("edit_last_name_check",$("#edit_last_name_check").val())
                formData.append("edit_position_check",$("#edit_position_check").val())
                formData.append("edit_term_years_check",$("#edit_term_years_check").val())
                formData.append("edit_voters_check",$("#edit_voters_check").val())
                formData.append("edit_pwd_check",$("#edit_pwd_check").val())
                formData.append("edit_birth_date_check",$("#edit_birth_date_check").val())
                formData.append("edit_birth_place_check",$("#edit_birth_place_check").val())
                formData.append("edit_middle_name_check",$("#edit_middle_name_check").val())
                formData.append("edit_suffix_check",$("#edit_suffix_check").val())
                formData.append("edit_gender_check",$("#edit_gender_check").val())
                formData.append("edit_civil_status_check",$("#edit_civil_status_check").val())
                formData.append("edit_religion_check",$("#edit_religion_check").val())
                formData.append("edit_nationality_check",$("#edit_nationality_check").val())
                formData.append("edit_municipality_check",$("#edit_municipality_check").val())
                formData.append("edit_zip_check",$("#edit_zip_check").val())
                formData.append("edit_barangay_check",$("#edit_barangay_check").val())
                formData.append("edit_house_number_check",$("#edit_house_number_check").val())
                formData.append("edit_street_check",$("#edit_street_check").val())
                formData.append("edit_address_check",$("#edit_address_check").val())
                formData.append("edit_email_address_check",$("#edit_email_address_check").val())
                formData.append("edit_contact_number_check",$("#edit_contact_number_check").val())
                formData.append("edit_guardian_check",$("#edit_guardian_check").val())
                formData.append("edit_guardian_contact_check",$("#edit_guardian_contact_check").val())
                formData.append("edit_pwd_info_check",$("#edit_pwd_info_check").val())
                  $.ajax({
                    url: 'editOfficial.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success:function(data){
                      if(data == 'error'){

                          Swal.fire({
                            title: '<strong class="text-danger">ERROR</strong>',
                            type: 'error',
                            html: '<b>Position Limited<b>',
                            width: '400px',
                            confirmButtonColor: '#6610f2',
                            allowOutsideClick: false,
                          });


                      }else{
                        Swal.fire({
                          title: '<strong class="text-success">SUCCESS</strong>',
                          type: 'success',
                          html: '<b>Updated Official has Successfully<b>',
                          width: '400px',
                          confirmButtonColor: '#6610f2',
                          allowOutsideClick: false,
                          showConfirmButton: false,
                          timer: 2000,
                        }).then(()=>{
                          window.location.reload();
                        })

                      }
                    }
                  }).fail(barangayAjaxError)
              }
            })

           
          }
        });
      $('#editOfficialForm').validate({
        ignore: "",
        rules: {
          edit_first_name: {
            required: true,
            minlength: 2
          },
          edit_last_name: {
            required: true,
            minlength: 2
          },
          edit_age: {
            required: true,
          },
          edit_birth_date: {
            required: true,
          },
          edit_address:{
            required: true,
          },
          edit_email_address:{
            email: true,
          },
          edit_term_years:{
            required: true,
            officialTermRange: true,
            officialTermOrder: true,
          },
          edit_contact_number:{
            required: true,
            minlength: 11
          },
          edit_pwd_info:{
            required: true,
          
          },
        },
        messages: {
          edit_first_name: {
            required: "Please provide a First Name",
            minlength: "First Name must be at least 2 characters long"
          },
          edit_last_name: {
            required: "Please provide a Last Name",
            minlength: "Last Name must be at least 2 characters long"
          },
          edit_age: {
            required: "Please provide a age",
          },
          edit_birth_date: {
            required: "Please provide a Birth Date",
          },
          edit_address: {
            required: "Please provide a Address",
          },
          edit_term_years: {
            required: "Please provide a term",
          },
          edit_email_address:{
            email:"Enter Valid Email!",
            },
            edit_contact_number:{
              required: "Please provide a Contact Number",
              minlength: "Input Exact Contact Number",
            },
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
          element.closest('.form-group-sm').append(error);
        },
        highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        }
      });
    })
   

    $("#edit_first_name, #edit_last_name").keyup(function(){
      var edit_first_name = $("#edit_first_name").val();
      var edit_last_name = $("#edit_last_name").val();
      $("#keyup_first_name").text(edit_first_name);
      $("#keyup_last_name").text(edit_last_name);
    })

    $("#image_official").click(function(){
          $("#edit_image").click();
      });

    function displayImge(input){
      if(input.files && input.files[0]){
        var reader = new FileReader();
        var edit_image = $("#edit_image").val().split('.').pop().toLowerCase();

        if(edit_image != ''){
          if(jQuery.inArray(edit_image,['gif','png','jpg','jpeg']) == -1){
            Swal.fire({
              title: '<strong class="text-danger">ERROR</strong>',
              type: 'error',
              html: '<b>Invalid Image File<b>',
              width: '400px',
              confirmButtonColor: '#6610f2',
            })
            $("#edit_image").val('');
            $("#image_official").attr('src', '../assets/dist/img/blank_image.png');
            return false;
          }
        }

        reader.onload = function(e){
          $("#image_official").attr('src',e.target.result);
          $("#image_official").hide();
          $("#image_official").fadeIn(650);
        }

        reader.readAsDataURL(input.files[0]);

      }
    }  

    $("#edit_image").change(function(){
      displayImge(this);
    })


    
  })
</script>

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

  $("#edit_term_years").inputFilter(function(value) {
  return /^[\d-]*$/.test(value);
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
