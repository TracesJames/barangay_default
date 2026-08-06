<?php 

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
try{

  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'){

    $row_barangay_information = barangay_load_active($con) ?? [];

    $whereClause = barangay_residents_where_clause($con, ["residence_status.archive = 'NO'"]);

    $table = '';
   
    if(isset($_REQUEST['status']) && $_REQUEST['status'] != ''){
       $status = $con->real_escape_string($_REQUEST['status']);
       $whereClause[] = "residence_status.status='$status'";
    } 

    if(isset($_REQUEST['single_parent']) && $_REQUEST['single_parent'] != ''){
        $singleParent = $con->real_escape_string($_REQUEST['single_parent']);
        $whereClause[] = "residence_status.single_parent='$singleParent'";
    } 
       
    if(isset($_REQUEST['voters']) && $_REQUEST['voters'] != ''){
      $voters = $con->real_escape_string($_REQUEST['voters']);
      $whereClause[] = "residence_status.voters='$voters'";
    }

    if(isset($_REQUEST['age']) && $_REQUEST['age'] != ''){
       $age = $con->real_escape_string($_REQUEST['age']);
       $whereClause[] = "residence_information.age='$age'";
    }

    if(isset($_REQUEST['pwd']) && $_REQUEST['pwd'] != ''){
      $pwd = $con->real_escape_string($_REQUEST['pwd']);
      $whereClause[] = "residence_status.pwd='$pwd'"; 
    }
      
    if(isset($_REQUEST['senior']) && $_REQUEST['senior'] != ''){
      $senior = $con->real_escape_string($_REQUEST['senior']);
      $whereClause[] = "residence_status.senior='$senior'";
    }
      
    $where = barangay_sql_where($whereClause);
    $order = ' ORDER BY residence_information.last_name ASC, residence_information.first_name ASC';

    $sql_report = "SELECT residence_information.*, residence_status.* FROM residence_information 
        INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id" . $where . $order;
        $query_report = $con->query($sql_report) or die ($con->error);
        $count_report = $query_report->num_rows;
        $i = 1;
        $totalResident = number_format($count_report);



          while($row_report = $query_report->fetch_assoc()){

          


            $table .= '<tr>
                    <td style="border: 2px solid #000;">'.$i++.'</td>
                    <td style="border: 2px solid #000;">'.ucfirst($row_report['last_name']).' </td>
                    <td style="border: 2px solid #000;">'.ucfirst($row_report['first_name']).' </td>
                    <td style="border: 2px solid #000;">'.ucfirst($row_report['middle_name']).' </td>
                    <td style="border: 2px solid #000;">'.$row_report['age'].'</td>
                    <td style="border: 2px solid #000;">'.$row_report['pwd_info'].'</td>
                    <td style="border: 2px solid #000;">'.$row_report['single_parent'].'</td>
                    <td style="border: 2px solid #000;">'.$row_report['voters'].'</td>
                    <td style="border: 2px solid #000;">'.$row_report['status'].'</td>
                    <td style="border: 2px solid #000;">'.$row_report['senior'].'</td>
                </tr>';
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

  <style>
      #barangay_logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }
    #valencia_city{
      height: 150px;
      width:auto;
      max-width:500px;
    }
  </style>

<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition sidebar-mini barangay-portal">
 
<table width="80%" style="font-size:9pt;" class="table table-borderless">
          <tbody>
            <tr>
              <td class="text-center">
                <?php
                $reportBarangayLogo = !empty($row_barangay_information)
                    ? barangay_admin_logo_url($row_barangay_information)
                    : '../assets/logo/valencia-city.png';
                echo '<img alt="barangay_logo" src="' . barangay_h($reportBarangayLogo) . '" class="img-circle" id="barangay_logo">';
                ?>
              </td>
              <td class="text-center">
                <div style="font-size:10pt; font-weight: 800">
                  <?php $certHeader = barangay_certificate_header($row_barangay_information ?? []); ?>
                  <?= barangay_h($certHeader['country']) ?><br>
                  <?= barangay_h($certHeader['province']) ?><br>
                  <?= barangay_h($certHeader['city']) ?><br>
                  <?= barangay_h($certHeader['barangay_line']) ?><br>
                  Barangay Residence Report
                </div>
              </td>
              <td  class="text-center">
              <img alt="city_logo" src="../assets/logo/valencia-city.png" class="img-circle" id="valencia_city">
               
                <br>
               
              </td>
            </tr>
          </tbody>
        </table>

                <div class="row">
                  <div class="col-sm-4" >
                    <div class="input-group mb-3">
                      <div class="input-group-prepend">
                        <b><label class="input-group-text bg-transparent" style="border: none; font-weight: 700;">VOTERS</label></b>
                      </div>
                      <input type="text" class="form-control" value="<?php if(isset($_REQUEST['voters'])) echo $_REQUEST['voters'] ?>"  style="border-top: none; border-right: none; border-left: none; border-bottom: 2px solid #000; "> 
                    </div>
                  </div>
                  
                  <div class="col-sm-4" >
                    <div class="input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent" style="border: none; font-weight: 700">AGE</span>
                      </div>
                          <input type="text" class="form-control" value="<?php if(isset($_REQUEST['age'])) echo $_REQUEST['age'] ?>" style="border-top: none; border-right: none; border-left: none; border-bottom: 2px solid #000; "> 
                        </select>
                    </div>
                  </div>
                  <div class="col-sm-4" >
                    <div class="input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent" style="border: none; font-weight: 700">STATUS</span>
                      </div>
                      <input type="text" class="form-control" value="<?php if(isset($_REQUEST['status'])) echo $_REQUEST['status'] ?>" style="border-top: none; border-right: none; border-left: none; border-bottom: 2px solid #000; "> 
                    </div>
                  </div>
                  <div class="col-sm-4" >
                    <div class="input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent" style="border: none; font-weight: 700">PWD</span>
                      </div>
                      <input type="text" class="form-control" value="<?php if(isset($_REQUEST['pwd'])) echo $_REQUEST['pwd'] ?>" style="border-top: none; border-right: none; border-left: none; border-bottom: 2px solid #000; "> 
                    </div>
                  </div>
                  <div class="col-sm-4" >
                    <div class="input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent" style="border: none; font-weight: 700">SENIOR</span>
                      </div>
                      <input type="text" class="form-control" value="<?php if(isset($_REQUEST['senior'])) echo $_REQUEST['senior'] ?>" style="border-top: none; border-right: none; border-left: none; border-bottom: 2px solid #000; "> 
                    </div>
                  </div>
                  <div class="col-sm-4" >
                    <div class="input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent" style="border: none; font-weight: 700">SINGLE PARENT</span>
                      </div>
                      <input type="text" class="form-control" value="<?php if(isset($_REQUEST['single_parent'])) echo $_REQUEST['single_parent'] ?>" style="border-top: none; border-right: none; border-left: none; border-bottom: 2px solid #000; "> 
                    </div>
                  </div>
                  <div class="col-sm-4" >
                    <div class="input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-transparent" style="border: none; font-weight: 700">TOTAL</span>
                      </div>
                      <input type="text" class="form-control" value="<?= $totalResident ?>" style="border-top: none; border-right: none; border-left: none; border-bottom: 2px solid #000; "> 
                    </div>
                  </div>
                </div>

                  
                <table class="table table-sm " width="100%" style="border: 2px solid #000;">             
                  <thead >
                    <tr >
                    <th style="border: 2px solid #000;">No.</th>
                      <th style="border: 2px solid #000;">Last Name</th>
                      <th style="border: 2px solid #000;">First Name</th>
                      <th style="border: 2px solid #000;">Middle Name</th>
                      <th style="border: 2px solid #000;">Age</th>
                      <th style="border: 2px solid #000;">Pwd</th>
                      <th style="border: 2px solid #000;">Single Parent</th>
                      <th style="border: 2px solid #000;">Voters</th>
                      <th style="border: 2px solid #000;">Status</th>
                      <th style="border: 2px solid #000;">Senior</th>
                    </tr>
                  </thead>
                  <tbody style="border: 2px solid #000;">
                  <?= $table ?>
                  </tbody>
                </table>
 
 
<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>

<!-- Bootstrap -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.js"></script>


<script>
$(document).ready(function(){
  
   
    var printContents = $("body").html();
     var originalContents = document.body.innerHTML;
     document.body.innerHTML = printContents;


     window.print();
     document.body.innerHTML = originalContents;
       setTimeout(function(){ 
             window.close();
  }, 5000);
   
  
})
</script>


</body>
</html>


              