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
    $yes= 'YES';
    $no = 'NO';

  

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
  <link rel="stylesheet" href="../assets/plugins/jquery-ui/jquery-ui.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

<style>
  .dataTables_processing {
    position: absolute;
    top: 0px;
    left: 50%;
    width: 250px;
    margin-left: -125px;
    border: 1px solid #ddd;
    text-align: center;
    color: #999;
    font-size: 11px;
    padding: 2px 0;
    display: none;
}
</style>


<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal">
<div class="wrapper">

   <!-- Preloader -->
   <div class="preloader flex-column justify-content-center align-items-center">
          <img class="animation__wobble " src="../assets/dist/img/loader.gif" alt="AdminLTELogo" height="70" width="70">
          <br>
          <div class="container text-lime " id="showResult">

                          <?php 

                          if(isset($_POST['restore'])){


                           

                       
                          $test = $_POST['fileRestore'];

                      
                            define("DB_USER", 'root');
                            define("DB_PASSWORD", '');
                            define("DB_NAME", 'barangay');
                            define("DB_HOST", 'localhost');
                            define("BACKUP_DIR", '../backup'); // Comment this line to use same script's directory ('.')
                            define("BACKUP_FILE", $test); // Script will autodetect if backup file is gzipped based on .gz extension
                            define("CHARSET", 'utf8');
                            define("DISABLE_FOREIGN_KEY_CHECKS", true); // Set to true if you are having foreign key constraint fails
  
                            /**
                            * The Restore_Database class
                            */
                            class Restore_Database {
                            /**
                            * Host where the database is located
                            */
                            var $host;
  
                            /**
                            * Username used to connect to database
                            */
                            var $username;
  
                            /**
                            * Password used to connect to database
                            */
                            var $passwd;
  
                            /**
                            * Database to backup
                            */
                            var $dbName;
  
                            /**
                            * Database charset
                            */
                            var $charset;
  
                            /**
                            * Database connection
                            */
                            var $conn;
  
                            /**
                            * Disable foreign key checks
                            */
                            var $disableForeignKeyChecks;
  
                            /**
                            * Constructor initializes database
                            */
                            function __construct($host, $username, $passwd, $dbName, $charset = 'utf8') {
                            $this->host                    = $host;
                            $this->username                = $username;
                            $this->passwd                  = $passwd;
                            $this->dbName                  = $dbName;
                            $this->charset                 = $charset;
                            $this->disableForeignKeyChecks = defined('DISABLE_FOREIGN_KEY_CHECKS') ? DISABLE_FOREIGN_KEY_CHECKS : true;
                            $this->conn                    = $this->initializeDatabase();
                            $this->backupDir               = defined('BACKUP_DIR') ? BACKUP_DIR : '.';
                            $this->backupFile              = defined('BACKUP_FILE') ? BACKUP_FILE : null;
                            }
  
                            /**
                            * Destructor re-enables foreign key checks
                            */
                            function __destructor() {
                            /**
                            * Re-enable foreign key checks 
                            */
                            if ($this->disableForeignKeyChecks === true) {
                              mysqli_query($this->conn, 'SET foreign_key_checks = 1');
                            }
                            }
  
                            protected function initializeDatabase() {
                            try {
                              $conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
                              if (mysqli_connect_errno()) {
                                  throw new Exception('ERROR connecting database: ' . mysqli_connect_error());
                                  die();
                              }
                              if (!mysqli_set_charset($conn, $this->charset)) {
                                  mysqli_query($conn, 'SET NAMES '.$this->charset);
                              }
  
                              /**
                              * Disable foreign key checks 
                              */
                              if ($this->disableForeignKeyChecks === true) {
                                  mysqli_query($conn, 'SET foreign_key_checks = 0');
                              }
  
                            } catch (Exception $e) {
                              print_r($e->getMessage());
                              die();
                            }
  
                            return $conn;
                            }
  
                            /**
                            * Backup the whole database or just some tables
                            * Use '*' for whole database or 'table1 table2 table3...'
                            * @param string $tables
                            */
                            public function restoreDb() {
                            try {
                              $sql = '';
                              $multiLineComment = false;
  
                              $backupDir = $this->backupDir;
                              $backupFile = $this->backupFile;
  
                              /**
                              * Gunzip file if gzipped
                              */
                              $backupFileIsGzipped = substr($backupFile, -3, 3) == '.gz' ? true : false;
                              if ($backupFileIsGzipped) {
                                  if (!$backupFile = $this->gunzipBackupFile()) {
                                      throw new Exception("ERROR: couldn't gunzip backup file " . $backupDir . '/' . $backupFile);
                                  }
                              }
  
                              /**
                              * Read backup file line by line
                              */
                              $handle = fopen($backupDir . '/' . $backupFile, "r");
                              if ($handle) {
                                  while (($line = fgets($handle)) !== false) {
                                      $line = ltrim(rtrim($line));
                                      if (strlen($line) > 1) { // avoid blank lines
                                          $lineIsComment = false;
                                          if (preg_match('/^\/\*/', $line)) {
                                              $multiLineComment = true;
                                              $lineIsComment = true;
                                          }
                                          if ($multiLineComment or preg_match('/^\/\//', $line)) {
                                              $lineIsComment = true;
                                          }
                                          if (!$lineIsComment) {
                                              $sql .= $line;
                                              if (preg_match('/;$/', $line)) {
                                                  // execute query
                                                  if(mysqli_query($this->conn, $sql)) {
                                                      if (preg_match('/^CREATE TABLE `([^`]+)`/i', $sql, $tableName)) {
                                                          $this->obfPrint("Table succesfully created: `" . $tableName[1] . "`");
                                                      }
                                                      $sql = '';
                                                  } else {
                                                      throw new Exception("ERROR: SQL execution error: " . mysqli_error($this->conn));
                                                  }
                                              }
                                          } else if (preg_match('/\*\/$/', $line)) {
                                              $multiLineComment = false;
                                          }
                                      }
                                  }
                                  fclose($handle);
                              } else {
                                  throw new Exception("ERROR: couldn't open backup file " . $backupDir . '/' . $backupFile);
                              } 
                            } catch (Exception $e) {
                              print_r($e->getMessage());
                              return false;
                            }
  
                            if ($backupFileIsGzipped) {
                              unlink($backupDir . '/' . $backupFile);
                            }
  
                            return true;
                            }
  
                            /*
                            * Gunzip backup file
                            *
                            * @return string New filename (without .gz appended and without backup directory) if success, or false if operation fails
                            */
                            protected function gunzipBackupFile() {
                            // Raising this value may increase performance
                            $bufferSize = 4096; // read 4kb at a time
                            $error = false;
  
                            $source = $this->backupDir . '/' . $this->backupFile;
                            $dest = $this->backupDir . '/' . date("Ymd_His", time()) . '_' . substr($this->backupFile, 0, -3);
  
                            $this->obfPrint('Gunzipping backup file ' . $source . '... ', 1, 1);
  
                            // Remove $dest file if exists
                            if (file_exists($dest)) {
                              if (!unlink($dest)) {
                                  return false;
                              }
                            }
  
                            // Open gzipped and destination files in binary mode
                            if (!$srcFile = gzopen($this->backupDir . '/' . $this->backupFile, 'rb')) {
                              return false;
                            }
                            if (!$dstFile = fopen($dest, 'wb')) {
                              return false;
                            }
  
                            while (!gzeof($srcFile)) {
                              // Read buffer-size bytes
                              // Both fwrite and gzread are binary-safe
                              if(!fwrite($dstFile, gzread($srcFile, $bufferSize))) {
                                  return false;
                              }
                            }
  
                            fclose($dstFile);
                            gzclose($srcFile);
  
                            // Return backup filename excluding backup directory
                            return str_replace($this->backupDir . '/', '', $dest);
                            }
  
                            /**
                            * Prints message forcing output buffer flush
                            *
                            */
                            public function obfPrint ($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1) {
                            if (!$msg) {
                              return false;
                            }
  
                            $msg = date("Y-m-d H:i:s") . ' - ' . $msg;
                            $output = '';
  
                            if (php_sapi_name() != "cli") {
                              $lineBreak = "<br />";
                            } else {
                              $lineBreak = "\n";
                            }
  
                            if ($lineBreaksBefore > 0) {
                              for ($i = 1; $i <= $lineBreaksBefore; $i++) {
                                  $output .= $lineBreak;
                              }                
                            }
  
                            $output .= $msg;
  
                            if ($lineBreaksAfter > 0) {
                              for ($i = 1; $i <= $lineBreaksAfter; $i++) {
                                  $output .= $lineBreak;
                              }                
                            }
  
                            if (php_sapi_name() == "cli") {
                              $output .= "\n";
                            }
  
                            echo $output;
  
                            if (php_sapi_name() != "cli") {
                              ob_flush();
                            }
  
                            flush();
                            }
                            }
  
                            /**
                            * Instantiate Restore_Database and perform backup
                            */
                            // Report all errors
                            error_reporting(E_ALL);
                            // Set script max execution time
                      
  
                            if (php_sapi_name() != "cli") {
                            echo '<div style="font-family: monospace;">';
                            }
  
                            $restoreDatabase = new Restore_Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                            $result = $restoreDatabase->restoreDb(BACKUP_DIR, BACKUP_FILE) ? 'OK' : 'KO';
                            $restoreDatabase->obfPrint("Restoration result: ".$result, 1);
  
                            if (php_sapi_name() != "cli") {
                            echo '</div>';
                            }
  
  
                              
                            }
  


                         

                        

                          ?>

</div>

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
                <a href="newOfficial.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>New Official</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="allOfficial.php" class="nav-link">
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
            <a href="blotterRecord.php" class="nav-link ">
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
            <a href="backupRestore.php" class="nav-link bg-indigo">
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
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

      <div class="row mt-5">
        <div class="col-sm-6">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <h5>Upload Backup</h5>
                  </div>
                </div>
              <div class="card-body">

                  <button type="button" class="btn btn-flast btn-danger elevation-5 px-3 btn-flat mb-3" id="generateBackup"><i class="fas fa-file-export"></i> Generate Backup</button>
                    <form action="backupRestore.php" method="post">
                    <div class="p-5 text-center container elevation-5" style="border: 2px solid gray; border-style: dashed; cursor: pointer;" id="uploadFile">
                      Click Here to Upload <i class="fas fa-upload"></i> <br>
                      No <b>File Size</b> Limit
                      <br>

                      <span id="showValue"></span>


                    </div>
                  <input type="file" class="d-none" id="backup_file" name="fileRestore">
                  <button type="submit" name="restore" id="restore" class="btn btn-info btn-flat elevation-5 px-3 mt-3 "><i class="fas fa-recycle"></i> Restore</button>
                  </form>
              </div>
            </div>
        </div>
        <div class="col-sm-6">
          <div class="card">
              <div class="card-header">
                <div class="card-title">
                  <h5>Backup File</h5>
                </div>
              </div>
            <div class="card-body">

                <table class="table " id="backupTable">
                  <thead>
                    <tr>
                      <th>File Name</th>
                      <th>Action</th>
                    </tr>
                    </thead>
                </table>
              
            </div>
          </div>
        </div>
      </div>
           
      
        
   
       
       
          
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


<script>
  $(document).ready(function(){


    $("#backup_file").change(function(){
      var backupID = $(this).val().split('.').pop().toLowerCase();
      var wew = $(this).val();
      $("#showValue").text(wew);

      if(backupID != ''){
        if(jQuery.inArray(backupID, ['sql'])  == -1){
          Swal.fire({
              title: '<strong class="text-danger">ERROR</strong>',
              type: 'error',
              html: '<b>Invalid File</b>',
              width: '400px',
              showConfirmButton: true,
              allowOutsideClick: false,
            })
            $("#backup_file").val('');
            $("#showValue").text('');
            return false;
        }
      
      }

    })


    $(document).on('click','#generateBackup',function(){

      $.ajax({
        url: 'backup.php',
        type: 'POST',
        success:function(data){
          Swal.fire({
              title: '<strong class="text-success">SUCCESS</strong>',
              type: 'success',
              html: '<b>Generate Backup Successfully</b>',
              width: '400px',
              showConfirmButton: false,
              allowOutsideClick: false,
              timer: 2000,
            }).then(()=>{
              $("#backupTable").DataTable().ajax.reload();
            })
        
        }
      })

    })

    $("#uploadFile").on('click',function(){
      $("#backup_file").click();
    })


    var backupTable = $("#backupTable").DataTable({
      processing: false,
      serverSide: true,
      searching: false,
      info: false,
      paginate: false,
      ordering: false,
      autoWidth: false,
      scrollY: '200',

      ajax:{
        url: 'backupTable.php',
        type:'POST',
      },
      drawCallback: function (settings) {
      $('[data-toggle="tooltip"]').tooltip();
    }


    })


    $(document).on('click','.deleteFile',function(){
    var file_id = $(this).attr('id');
    Swal.fire({
        title: '<strong class="text-danger">ARE YOU SURE?</strong>',
        html: "You want delete this File?",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        allowOutsideClick: false,
        confirmButtonText: 'Yes, Delete it!',
        width: '400px',
      }).then((result) => {
        if (result.value) {
          $.ajax({
            url: 'deleteFile.php',
            type: 'POST',
            data: {
              file_id:file_id,
            },
            cache: false,
            success:function(data){
              Swal.fire({
                title: '<strong class="text-success">Success</strong>',
                type: 'success',
                html: '<b>Delete File has Successfully<b>',
                width: '400px',
                showConfirmButton: false,
                allowOutsideClick: false,
                timer: 2000
              }).then(()=>{
                $("#backupTable").DataTable().ajax.reload();
              })
            }
          }).fail(barangayAjaxError)
        }
      })

  })

  })
</script>

</body>
</html>
