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
  <title>New Resident — <?= barangay_h($barangay ?? 'Barangay') ?></title>

 
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
  <link rel="stylesheet" href="../assets/plugins/phone code/intlTelInput.min.css">
  <link rel="stylesheet" href="../assets/css/new-residence.css?v=20260729a">
  
 <style>
    #image_residence{
      width: 140px;
      height: 140px;
      max-width: 140px;
      object-fit: cover;
      object-position: center;
      border-radius: 50%;
    }
    .iti__country-list {
      background-color: #343a40;
    }
    .iti { width: 100%; }
 </style>
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal">
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
    <?php 
        if($image != '' || $image != null || !empty($image)){
          echo '<img src="'.$image_path.'" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }else{
          echo ' <img src="../assets/logo/logo.png" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
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
          <li class="nav-item">
            <a href="#" class="nav-link">
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
                <a href="officialEndTerm.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Official End Term</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item menu-open">
            <a href="#" class="nav-link bg-indigo ">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Residence
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="newResidence.php" class="nav-link active">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Residence</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="importResidence.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Import from Excel</p>
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
   

    <!-- Main content -->
    <section class="content mt-3">
      <div class="container-fluid">

        <div class="card card-outline card-indigo elevation-2 mb-4 nr-page-hero">
          <div class="card-body text-center py-4">
            <h4 class="mb-0"><?= barangay_h($barangay ?? 'Barangay') ?></h4>
            <p class="text-muted mb-2"><?= barangay_h(($zone ?? '') . ' · ' . ($district ?? '')) ?></p>
            <h5 class="mb-3"><i class="fas fa-user-plus text-indigo"></i> Register New Resident</h5>
            <p class="text-muted small mb-3">Required fields are marked with <span class="text-danger">*</span>. A residence number is assigned automatically on save.</p>
            <a href="allResidence.php" class="btn btn-outline-secondary btn-sm elevation-1">
              <i class="fas fa-list"></i> All Residents
            </a>
            <a href="downloadResidenceImportTemplate.php" class="btn btn-success btn-sm elevation-2 ml-1">
              <i class="fas fa-file-excel"></i> Excel Template
            </a>
            <a href="importResidence.php" class="btn btn-primary btn-sm elevation-2 ml-1">
              <i class="fas fa-upload"></i> Bulk Import
            </a>
          </div>
        </div>

        <ul class="nr-steps" aria-label="Registration steps">
          <li class="active" data-step="1" data-target="basic-info"><span class="nr-step-num">1</span><span class="nr-step-label">Basic Info</span></li>
          <li data-step="2" data-target="other-info"><span class="nr-step-num">2</span><span class="nr-step-label">Address</span></li>
          <li data-step="3" data-target="guardian"><span class="nr-step-num">3</span><span class="nr-step-label">Guardian</span></li>
          <li data-step="4" data-target="spouse-info"><span class="nr-step-num">4</span><span class="nr-step-label">Spouse</span></li>
          <li data-step="5" data-target="dependents-info"><span class="nr-step-num">5</span><span class="nr-step-label">Dependents</span></li>
        </ul>

      <form id="newResidenceForm" method="post" action="addNewResidence.php" enctype="multipart/form-data" autocomplete="off">
        <?= csrf_field(); ?>
        <div class="row mb-3">
          <div class="col-sm-4">
            <div class="card card-indigo card-outline h-100">
              <div class="card-body box-profile">
                <div class="text-center">
                  <div class="nr-photo-wrap">
                    <img class="profile-user-img img-fluid img-thumbnail" src="../assets/dist/img/blank_image.png" alt="Resident photo" style="cursor: pointer;" id="image_residence" title="Click to upload photo">
                  </div>
                  <input type="file" name="add_image" id="add_image" accept="image/jpeg,image/png,image/gif" style="display: none;">
                  <p class="nr-photo-hint">Click photo to upload (JPG, PNG, or GIF)</p>
                </div>

                <h3 class="profile-username text-center nr-preview-name"><span id="keyup_first_name">New</span> <span id="keyup_last_name">Resident</span></h3>
                <p class="text-center nr-preview-meta mb-3" id="nr_preview_meta"></p>
  
                <div class="row">
                  <div class="col-sm-12">
                    <div class="form-group">
                      <label class="nr-required">Voters</label>
                      <select name="add_voters" id="add_voters" class="form-control">
                      <option value="">Select…</option>
                        <option value="NO">NO</option>
                        <option value="YES">YES</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label class="nr-required">Gender</label>
                      <select name="add_gender" id="add_gender" class="form-control">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label class="nr-required">Date of Birth</label>
                      <input type="date" class="form-control" id="add_birth_date" name="add_birth_date">
                      <span class="nr-age-badge" id="nr_age_display" style="display:none;"></span>
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label >Place of Birth</label>
                      <input type="text" class="form-control" id="add_birth_place" name="add_birth_place">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label class="nr-required">PWD</label>
                      <select name="add_pwd" id="add_pwd" class="form-control">
                      <option value="">Select…</option>
                        <option value="NO">NO</option>
                        <option value="YES">YES</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12" id="pwd_check" style="display: none;">
                    <div class="form-group ">
                      <label class="nr-required">Type of PWD</label>
                        <input type="text" class="form-control" id="add_pwd_info" name="add_pwd_info" disabled placeholder="e.g. Visual, Mobility">
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label class="nr-required">Single Parent</label>
                      <select name="add_single_parent" id="add_single_parent" class="form-control">
                        <option value="">Select…</option>
                        <option value="NO">NO</option>
                        <option value="YES">YES</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-12">
                    <div class="form-group ">
                      <label class="nr-required">Indigenous People (IP)</label>
                      <select name="add_indigenous" id="add_indigenous" class="form-control">
                        <option value="">Select…</option>
                        <option value="NO">NO</option>
                        <option value="YES">YES</option>
                      </select>
                    </div>
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
                  <?php require __DIR__ . '/../includes/partials/residence_family_tab_nav.php'; ?>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                  <div class="tab-pane fade active show" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                      <p class="lead text-center mb-3">Personal Details</p>
                      <div id="nr_duplicate_alert" class="nr-duplicate-alert" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> This name is already registered in the Barangay.
                      </div>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group ">
                            <label class="nr-required">First Name</label>
                            <input type="text" class="form-control" id="add_first_name" name="add_first_name" placeholder="Given name" autocomplete="given-name">
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group ">
                            <label>Middle Name</label>
                            <input type="text" class="form-control" id="add_middle_name" name="add_middle_name" placeholder="Optional" autocomplete="additional-name">
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group ">
                            <label class="nr-required">Last Name</label>
                            <input type="text" class="form-control" id="add_last_name" name="add_last_name" placeholder="Surname" autocomplete="family-name">
                          </div>  
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group ">
                              <label >Suffix</label>
                              <input type="text" class="form-control" id="add_suffix" name="add_suffix" placeholder="Jr., Sr., III">
                            </div>
                          </div>
                          <div class="col-sm-3">
                            <div class="form-group ">
                              <label >Civil Status</label>
                              <select name="add_civil_status" id="add_civil_status" class="form-control">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Annulled">Annulled</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group ">
                              <label >Religion</label>
                              <input type="text" class="form-control" id="add_religion" name="add_religion" placeholder="Optional">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group ">
                              <label >Nationality</label>
                              <input type="text" class="form-control" id="add_nationality" name="add_nationality" value="Filipino">
                            </div>
                          </div>                              
                        </div>
                      <div class="nr-tab-nav">
                        <span></span>
                        <button type="button" class="btn btn-outline-primary btn-sm nr-btn-next">Next <i class="fas fa-arrow-right"></i></button>
                      </div>
                  </div>
                  <div class="tab-pane fade" id="other-info" role="tabpanel" aria-labelledby="other-info-tab">
                        <p class="lead text-center mb-3">Address &amp; Contact</p>
                        <div class="row">
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Municipality</label>
                              <input type="text" class="form-control" id="add_municipality" name="add_municipality">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Zip</label>
                              <input type="text" class="form-control" id="add_zip" name="add_zip" maxlength="10" inputmode="numeric">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Barangay</label>
                              <input type="text" class="form-control" id="add_barangay" name="add_barangay" value="<?= barangay_h($barangay ?? '') ?>" readonly>
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>House Number</label>
                              <input type="text" class="form-control" id="add_house_number" name="add_house_number" >
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                            <label>Street</label>
                            <input type="text" class="form-control" id="add_street" name="add_street" >
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label class="nr-required">Address</label>
                              <input type="text" class="form-control" id="add_address" name="add_address" placeholder="Sitio / full address line">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label class="nr-required">Contact Number</label>
                              <input type="text" class="form-control" maxlength="11" id="add_contact_number" name="add_contact_number" placeholder="09XXXXXXXXX" inputmode="numeric">
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="form-group">
                              <label>Email Address</label>
                              <input type="email" class="form-control" id="add_email_address" name="add_email_address" placeholder="optional@email.com">
                            </div>
                          </div>
                        </div>
                      <div class="nr-tab-nav">
                        <button type="button" class="btn btn-outline-secondary btn-sm nr-btn-prev"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="btn btn-outline-primary btn-sm nr-btn-next">Next <i class="fas fa-arrow-right"></i></button>
                      </div>
                  </div>
                  <div class="tab-pane fade" id="guardian" role="tabpanel" aria-labelledby="guardian-tab">
                   
                      <p class="lead text-center mb-3">Guardian / Parents</p>
                      <?php require __DIR__ . '/../includes/partials/residence_minor_guardian_notice.php'; ?>
                      <div class="row">

                        <div class="col-sm-12 minor-guardian-field">
                          <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" class="form-control" id="add_fathers_name" name="add_fathers_name" >
                          </div>
                        </div>
                        <div class="col-sm-12 minor-guardian-field">
                          <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" class="form-control" id="add_mothers_name" name="add_mothers_name" >
                          </div>
                        </div>
                        <div class="col-sm-12 minor-guardian-field">
                          <div class="form-group">
                            <label>Guardian</label>
                            <input type="text" class="form-control" id="add_guardian" name="add_guardian" >
                          </div>
                        </div>
                        <div class="col-sm-12">
                          <div class="form-group">
                            <label>Guardian Contact</label>
                            <input type="text" class="form-control" maxlength="11" id="add_guardian_contact" name="add_guardian_contact" placeholder="09XXXXXXXXX" inputmode="numeric">
                          </div>
                        </div>

                      </div>
                      <div class="nr-tab-nav">
                        <button type="button" class="btn btn-outline-secondary btn-sm nr-btn-prev"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="btn btn-outline-primary btn-sm nr-btn-next">Next <i class="fas fa-arrow-right"></i></button>
                      </div>
                    
                  </div>
                  <?php require __DIR__ . '/../includes/partials/residence_family_tab_content.php'; ?>
                </div>
              </div>
              <div class="card-footer">
                <p class="nr-footer-hint"><i class="fas fa-info-circle"></i> Residence number is auto-generated. Double-check name and birth date before saving.</p>
                <button type="submit" class="btn btn-success px-4 elevation-3" id="nr_submit_btn">
                  <i class="fas fa-user-plus"></i> Save New Resident
                </button>
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
<script src="../assets/js/residence-family.js"></script>
<script src="../assets/js/residence-minor-guardian.js"></script>
<script src="../assets/js/new-residence.js?v=20260729a"></script>
<script src="../assets/plugins/phone code/intlTelInput.js"></script>

<script>
  $(document).ready(function(){

    $('#newResidenceForm').on('submit', function (e) {
      e.preventDefault();
      if (!$(this).valid()) {
        return false;
      }
    });

    $("#add_pwd").change(function(){
      var pwd_check = $(this).val();

      if(pwd_check == 'YES'){
        $("#pwd_check").css('display', 'block');
        $("#add_pwd_info").prop('disabled', false);
      }else{
        $("#pwd_check").css('display', 'none');
        $("#add_pwd_info").prop('disabled', true).val('');
      }
      $("#add_pwd_info").valid();
    });

    function newResidenceErrorMessage(resultText) {
      if (resultText === 'errorValidation') {
        return 'Please fill in all required fields.';
      }
      if (resultText === 'errorImage') {
        return 'Invalid image file. Use JPG, PNG, or GIF.';
      }
      if (resultText === 'errorMinorGuardian') {
        return 'Residents 17 years old and below must have a Guardian name or Parent name.';
      }
      if (resultText === 'errorDuplicateName') {
        return 'This name and birth date are already registered in the Barangay.';
      }
      if (resultText === 'errorServer') {
        return 'Unable to save the resident. Please try again.';
      }
      if (resultText.indexOf('Invalid CSRF token') !== -1) {
        return 'Your session expired. Please refresh the page and try again.';
      }
      return resultText || 'Unable to complete the request.';
    }



    $(function () {
        $.validator.setDefaults({
          submitHandler: function (form) {
            if (typeof barangaySyncCsrfForms === 'function') {
              barangaySyncCsrfForms();
            }
            var $btn = $('#nr_submit_btn');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving…');
            $.ajax({
              url: 'addNewResidence.php',
              type: 'POST',
              data: new FormData(form),
              processData: false,
              contentType: false,
              cache: false,
              success:function(data){
                var parsed = (window.barangayNewResidence && barangayNewResidence.parseSaveResult)
                  ? barangayNewResidence.parseSaveResult(data)
                  : { ok: (data || '').toString().trim() === 'success', residenceId: '', error: (data || '').toString().trim() };
                if (!parsed.ok) {
                  var resultText = parsed.error || '';
                  if (resultText === 'errorMinorGuardian') {
                    Swal.fire({
                      title: '<strong class="text-danger">ERROR</strong>',
                      icon: 'error',
                      html: '<b>' + newResidenceErrorMessage(resultText) + '</b>',
                      width: '400px',
                      confirmButtonColor: '#6610f2',
                    }).then(function () {
                      $('#guardian-tab').tab('show');
                    });
                    return;
                  }
                  if (resultText === 'errorDuplicateName') {
                    Swal.fire({
                      title: '<strong class="text-danger">Already Registered</strong>',
                      icon: 'warning',
                      html: '<b>This name and birth date are already registered in the Barangay.</b>',
                      width: '440px',
                      confirmButtonColor: '#6610f2',
                    }).then(function () {
                      $('#basic-info-tab').tab('show');
                      $('#add_first_name').focus();
                    });
                    return;
                  }
                  Swal.fire({
                    title: '<strong class="text-danger">ERROR</strong>',
                    icon: 'error',
                    html: '<b>' + newResidenceErrorMessage(resultText) + '</b>',
                    width: '400px',
                    confirmButtonColor: '#6610f2',
                  });
                  return;
                }
                var idHtml = parsed.residenceId
                  ? '<p class="mb-2">Residence No. <strong>' + $('<div>').text(parsed.residenceId).html() + '</strong></p>'
                  : '';
                Swal.fire({
                  title: '<strong class="text-success">Resident Saved</strong>',
                  icon: 'success',
                  html: idHtml + '<b>New resident was registered successfully.</b>',
                  width: '440px',
                  confirmButtonColor: '#6610f2',
                  showDenyButton: true,
                  confirmButtonText: 'Add another',
                  denyButtonText: 'View all residents',
                  allowOutsideClick: false,
                }).then(function (result) {
                  if (result.isDenied) {
                    window.location.href = 'allResidence.php';
                    return;
                  }
                  window.location.reload();
                });
              },
              complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-user-plus"></i> Save New Resident');
              }
            }).fail(barangayAjaxError)
          }
        });
      $('#newResidenceForm').validate({
        ignore: "",
        rules: {
          add_first_name: {
            required: true,
            minlength: 2
          },
          add_last_name: {
            required: true,
            minlength: 2
          },
        
          add_birth_date: {
            required: true,
          },
          add_contact_number:{
            required: true,
            minlength: 11
          },
          add_address:{
            required: true,
          },
          add_single_parent:{
            required: true,
          },
          add_indigenous:{
            required: true,
          },
          add_pwd_info:{
            required: {
              depends: function () {
                return $('#add_pwd').val() === 'YES';
              }
            },
          },
          add_pwd:{
            required: true,
          },
          add_voters:{
            required: true,
          },
          add_email_address:{
            email: true,
          },
          add_guardian: {
            minorGuardianGroup: true,
          },
        },
        messages: {
          add_first_name: {
            required: "Please provide a First Name",
            minlength: "First Name must be at least 2 characters long"
          },
          add_last_name: {
            required: "Please provide a Last Name",
            minlength: "Last Name must be at least 2 characters long"
          },
        
          add_birth_date: {
            required: "Please provide a Birth Date",
          },
          add_address: {
            required: "Please provide an Address",
          },
          add_contact_number: {
            required: "Please provide a Contact Number",
            minlength: "Enter the full 11-digit mobile number"
          },
          add_email_address:{
            email:"Enter a valid email address",
            },
          add_voters: { required: "Please select voter status" },
          add_pwd: { required: "Please select PWD status" },
          add_single_parent: { required: "Please select single parent status" },
          add_indigenous: { required: "Please select IP status" },
        },
        invalidHandler: function () {
          if (window.barangayNewResidence && barangayNewResidence.jumpToFirstInvalid) {
            setTimeout(barangayNewResidence.jumpToFirstInvalid, 50);
          }
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
   

    $("#add_first_name, #add_last_name").keyup(function(){
      var add_first_name = $("#add_first_name").val();
      var add_last_name = $("#add_last_name").val();
      $("#keyup_first_name").text(add_first_name);
      $("#keyup_last_name").text(add_last_name);
    })

    $("#image_residence").click(function(){
          $("#add_image").click();
      });

    function displayImge(input){
      if(input.files && input.files[0]){
        var reader = new FileReader();
        var add_image = $("#add_image").val().split('.').pop().toLowerCase();

        if(add_image != ''){
          if(jQuery.inArray(add_image,['gif','png','jpg','jpeg']) == -1){
            Swal.fire({
              title: '<strong class="text-danger">ERROR</strong>',
              type: 'error',
              html: '<b>Invalid Image File<b>',
              width: '400px',
              confirmButtonColor: '#6610f2',
            })
            $("#add_image").val('');
            $("#image_residence").attr('src', '../assets/dist/img/blank_image.png');
            return false;
          }
        }

        reader.onload = function(e){
          $("#image_residence").attr('src',e.target.result);
          $("#image_residence").hide();
          $("#image_residence").fadeIn(650);
        }

        reader.readAsDataURL(input.files[0]);

      }
    }  

    $("#add_image").change(function(){
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

 
  $("#add_contact_number,#add_zip, #add_guardian_contact, #add_spouse_contact, .residence-family-number").inputFilter(function(value) {
  return /^-?\d*$/.test(value); 
  
  });


  $("#add_first_name, #add_middle_name, #add_last_name, #add_suffix, #add_religion, #add_nationality, #add_municipality, #add_fathers_name, #add_mothers_name, #add_guardian, #add_spouse_first_name, #add_spouse_middle_name, #add_spouse_last_name, #add_spouse_suffix, .residence-family-name").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  
  $("#add_street, #add_birth_place, #add_house_number").inputFilter(function(value) {
  return /^[0-9a-z, ,-]*$/i.test(value); 
  });

</script>

</body>
</html>
