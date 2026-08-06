<?php 

include_once '../connection.php';
include_once '../includes/auth_resident.php';
require_once '../includes/residence_family.php';
try{
  if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident'){

    $user_id = $_SESSION['user_id'];
    $can_request_certificate = residence_can_request_certificate($con, (string) $user_id);
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


    $sql_resident = "SELECT * FROM residence_information WHERE residence_id = '$user_id'";
    $query_resident = $con->query($sql_resident) or die ($con->error);
    $row_resident = $query_resident->fetch_assoc();


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
  <link rel="stylesheet" href="../assets/css/resident-portal.css">
  <style>
    


    
    #barangay_logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }

    .logo{
      height: 150px;
      width:auto;
      max-width:500px;
    }
    .wrapper{
      background-image: url('../assets/logo/cover.jpg');
      background-repeat:no-repeat;

background-size: cover;
background-position:center;
width: 100%;
  height: auto;
        animation-name: example;
        animation-duration: 5s;
       
       
    }


@keyframes example {
  from {opacity: 0;}
  to {opacity: 1.5;}
}

.dataTables_wrapper .dataTables_paginate .page-link {
      
      border: none;
  }
  .dataTables_wrapper .dataTables_paginate .page-item .page-link{
      color: #fff ;
      border-color: transparent;
      
      
    }
 
  .dataTables_wrapper .dataTables_paginate .page-item.active .page-link{
      color: #fff ;
      border: transparent;
      background: none;
      font-weight: bold;
      background-color: #000;
    }
  .page-link:focus{
    border-color:#CCC;
    outline:0;
    -webkit-box-shadow:none;
    box-shadow:none;
  }

  .dataTables_length select{
    border: 1px solid #fff;
    border-top: none;
    border-left: none;
    border-right: none;
    cursor: pointer;
    color: #fff;

  }
  .dataTables_length span{
    color: #fff;
    font-weight: 500; 
  }

  .last:after{
    display:none;
    width: 70px;
    background-color: black;
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 5px 0;
    position: absolute;
    font-size: 10px;
    z-index: 1;
    margin-left: -20px;
  }
    .last:hover:after{
        display: block;
    }
    .last:after{
        content: "Last Page";
    } 

    .first:after{
      display:none;
      width: 70px;
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px 0;
      position: absolute;
      font-size: 10px;
      z-index: 1;
      margin-left: -20px;
  }
    .first:hover:after{
        display: block;
    }
    .first:after{
        content: "First Page";
    } 

    .last:after{
        content: "Last Page";
    } 

    .next:after{
      display:none;
      width: 70px;
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px 0;
      position: absolute;
      font-size: 10px;
      z-index: 1;
      margin-left: -20px;
  }
    .next:hover:after{
        display: block;
    }
    .next:after{
        content: "Next Page";
    } 

    .previous:after{
      display:none;
      width: 80px;
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px 5px;
      position: absolute;
      font-size: 10px;
      z-index: 1;
      margin-left: -20px;
  }
    .previous:hover:after{
        display: block;
    }
    .previous:after{
        content: "Previous Page";
    } 
    .dataTables_info{
      font-size: 13px;
      margin-top: 8px;
      font-weight: 500;
      color: #fff;
    }
    .dataTables_scrollHeadInner, .table{ 
      table-layout: auto;
     width: 100% !important; 
    }

  .select2-container--default .select2-selection--single{
    background-color: transparent;
    height: 38px;
    
    
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{
    color: #fff;
  }
  #tableRequest_filter{
      display: none;
    }



  </style>
<?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="layout-top-nav dark-mode barangay-portal">

<div class="wrapper  p-0 maring-0 bg-transparent" >

  <?php require_once '../includes/partials/resident_navbar.php'; ?>
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper"  style="background-color: transparent">
    <!-- Content Header (Page header) -->
 
    
  
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content  " >
  



    <div class="container-fluid " >


                  <div class="card mt-5 resident-panel-card">
                      <div class="card-header">
                        <div class="card-title">
                          <h4 style="font-variant: small-caps">List of Request <span class="badge bg-lime" id="total"></span></h4>
                        </div>
                        <div class="card-tools">
                          <?php if ($can_request_certificate): ?>
                          <button type="button" class="btn bg-black elevation-5 px-3 btn-flat newRequest" data-toggle="modal" data-target="#newRequestModal"><i class="fas fa-plus"></i> New Request</button>
                          <?php endif; ?>
                        </div>
                      </div>
                    <div class="card-body">
                            <?php if ($can_request_certificate): ?>
                            <div class="alert alert-info text-sm mb-3">
                              All registered residents may submit a certificate request here, regardless of active/inactive status or voter registration.
                            </div>
                            <?php endif; ?>
                            <div class="row">
                              <div class="col-sm-6">
                                <div class="input-group input-group-md mb-3">
                                  <div class="input-group-prepend">
                                    <span class="input-group-text bg-indigo">SEARCH</span>
                                  </div>
                                  <input type="text" class="form-control" id="searching" autocomplete="off">
                                  <div class="input-group-append">
                                    <span class="input-group-text bg-red" id="reset" type="button"><i class="fas fa-undo"></i> RESET</span>
                                  </div>
                                </div>
                              </div>
                            </div>
                              <div class="table-responsive">
                              <table class="table table-striped table-hover text-sm" id="tableRequest">
                                <thead>
                                  <tr>
                                    <th>Purpose</th>
                                    <th>
                                      <select name="date_request" id="date_request" class="custom-select custom-select-sm">
                                          <option value="">Date Request</option>
                                              <?php 
                                              $blank_request = '';
                                              $sql_date_request = "SELECT date_request FROM certificate_request WHERE residence_id = ? AND date_request != ? GROUP BY date_request";
                                              $stmt_date_request = $con->prepare($sql_date_request) or die ($con->error);
                                              $stmt_date_request->bind_param('ss',$user_id,$blank_request);
                                              $stmt_date_request->execute();
                                              $result_date_request = $stmt_date_request->get_result();
                                              while($row_date_request = $result_date_request->fetch_assoc()){
                                                  echo '<option value="'.$row_date_request['date_request'].'">'.date("m/d/Y", strtotime($row_date_request['date_request'])).'</option>';
                                              }
                                              
                                              ?>
                                      </select>
                                    </th>
                                    <th>
                                        <select name="date_issued" id="date_issued" class="custom-select custom-select-sm">
                                                <option value="">Date Issued</option>
                                              <?php 
                                              $blank_issued = '';
                                              $sql_date_issued = "SELECT date_issued FROM certificate_request WHERE residence_id = ? AND date_issued != ? GROUP BY date_issued";
                                              $stmt_date_issued = $con->prepare($sql_date_issued) or die ($con->error);
                                              $stmt_date_issued->bind_param('ss',$user_id,$blank_issued);
                                              $stmt_date_issued->execute();
                                              $result_date_issued = $stmt_date_issued->get_result();
                                              while($row_date_issued = $result_date_issued->fetch_assoc()){
                                                  echo '<option value="'.$row_date_issued['date_issued'].'">'.date("m/d/Y", strtotime($row_date_issued['date_issued'])).'</option>';
                                              }
                                              
                                              ?>
                                      </select>
                                    </th>
                                    <th>
                                        <select name="date_expired" id="date_expired" class="custom-select custom-select-sm">
                                                <option value="">Date Expired</option>
                                              <?php 
                                              $blank_expired = '';
                                              $sql_date_expired = "SELECT date_expired FROM certificate_request WHERE residence_id = ? AND date_expired != ? GROUP BY date_expired";
                                              $stmt_date_expired = $con->prepare($sql_date_expired) or die ($con->error);
                                              $stmt_date_expired->bind_param('ss',$user_id,$blank_expired);
                                              $stmt_date_expired->execute();
                                              $result_date_expired = $stmt_date_expired->get_result();
                                              while($row_date_expired = $result_date_expired->fetch_assoc()){
                                                  echo '<option value="'.$row_date_expired['date_expired'].'">'.$row_date_expired['date_expired'].'</option>';
                                              }
                                              
                                              ?>
                                      </select>
                                    </th>
                                    <th>
                                        <select name="status" id="status" class="custom-select custom-select-sm">
                                                <option value="">Status</option>
                                              <?php 
                                            
                                              $sql_status = "SELECT status FROM certificate_request WHERE residence_id = ? GROUP BY status";
                                              $stmt_status = $con->prepare($sql_status) or die ($con->error);
                                              $stmt_status->bind_param('s',$user_id);
                                              $stmt_status->execute();
                                              $result_status = $stmt_status->get_result();
                                              while($row_status = $result_status->fetch_assoc()){
                                                  echo '<option value="'.$row_status['status'].'">'.$row_status['status'].'</option>';
                                              }
                                              
                                              ?>
                                      </select>
                                    </th>
                                    <th class="text-center">Tools</th>
                                  </tr>
                                </thead>
                                <tbody></tbody>
                              </table>
                              </div>
                    </div>
                  </div>

    
</div><!--/. container-fluid -->

    


     
          
               
      
     
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  
  <!-- Modal -->
  <div class="modal fade" id="newRequestModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form id="requestForm" method="post">

          <div class="modal-header">
              <h5 class="modal-title">Fill-up Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <div class="modal-body">
          <div class="container-fluid">

              <div class="row">
                  <input type="hidden" name="user_id" id="user_id" value="<?= $user_id;?>">
                <div class="col-sm-12">
                  <div class="form-group">
                    <label>Purpose</label>
                    <input type="text" name="purpose" id="purpose" class="form-control text-uppercase" required>
                  </div>
                </div>
              </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn bg-black btn-flat elevation-5 px-3" data-dismiss="modal"><i class="fas fa-times"></i> CLOSE</button>
          <button type="submit" class="btn btn-success btn-flat elevation-5 px-3"><i class="fas fa-sign-in-alt"></i> SUBMIT</button>
        </div>

        </form>
      </div>
    </div>
  </div>

 
  <footer class="main-footer barangay-footer text-white">
    <div class="float-right d-none d-sm-block">
    
    </div>
  <i class="fas fa-map-marker-alt"></i> <?= $postal_address ?> 
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

<div id="show_status"></div>

<script>
  $(document).ready(function(){

    tableRequest()

    function tableRequest(){

      var date_request =  $("#date_request").val();
      var date_issued  =  $("#date_issued").val();
      var date_expired =  $("#date_expired").val();
      var status       =  $("#status").val();
      var user_id      = $("#user_id").val();
      var tableRequest = $("#tableRequest").DataTable({
        processing: true,
        serverSide: true,
        order:[],
        autoWidth: false,
        ordering: false,
        columnDefs:[{
              targets: 5,
              className: 'text-center'
        }],
        dom: "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'d-flex flex-sm-row-reverse flex-column border-top '<'px-2 'p><'px-2'i> <'px-2'l> >",
            pagingType: "full_numbers",
            language: {
              paginate: {
                next: '<i class="fas fa-angle-right text-white"></i>',
                previous: '<i class="fas fa-angle-left text-white"></i>', 
                first: '<i class="fa fa-angle-double-left text-white"></i>',
                last: '<i class="fa fa-angle-double-right text-white"  ></i>'        
              }, 
              lengthMenu: '<div class="mt-3 pr-2"> <span class="text-sm mb-3 pr-2">Rows per page:</span> <select>'+
                          '<option value="10">10</option>'+
                          '<option value="20">20</option>'+
                          '<option value="30">30</option>'+
                          '<option value="40">40</option>'+
                          '<option value="50">50</option>'+
                          '<option value="-1">All</option>'+
                          '</select></div>',
              info:  " _START_ - _END_ of _TOTAL_ ",
            },
        ajax:{
            url: 'userRequestTable.php',
            type: 'POST',
            data:{
              user_id:user_id,
              date_request:date_request,
              date_issued:date_issued,
              date_expired:date_expired,
              status:status,
            }
        },
            drawCallback:function(data)  {
              $('#total').text(data.json.total);
              $('.dataTables_paginate').addClass("mt-2 mt-md-2 pt-1");
              $('.dataTables_paginate ul.pagination').addClass("pagination-md");   
              $('[data-toggle="tooltip"]').tooltip();
                               
            },
       
      })
      $('#searching').keyup(function(){
        tableRequest.search($(this).val()).draw() ;
        })

    }
    

    $(document).on('change',"#date_request, #date_issued, #date_expired, #status",function(){
      $("#tableRequest").DataTable().destroy();
      tableRequest()
      $('#searching').keyup();
    })

    



  $("#requestForm").submit(function(e){
    e.preventDefault();

    Swal.fire({
        title: '<strong class="text-info">ARE YOU SURE?</strong>',
        html: "<b>You want Submit this Request?</b>",
        type: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        allowOutsideClick: false,
        confirmButtonText: 'Yes, Submit it!',
        width: '400px',
      }).then((result) => {
        if (result.value) {
            $.ajax({
              url: 'requestCertificate.php',
              type: 'POST',
              data: $(this).serialize(),
              success:function(response){
                  var resultText = (response || '').toString().trim();
                  if (resultText !== '' && resultText !== 'success') {
                    var errorMessage = 'Unable to submit your certificate request.';
                    if (resultText === 'errorPurpose') {
                      errorMessage = 'Purpose is required.';
                    } else if (resultText === 'errorNotRegistered') {
                      errorMessage = 'Only registered residents can request a certificate.';
                    } else if (resultText === 'errorForbidden') {
                      errorMessage = 'You are not allowed to submit a request for this account.';
                    }

                    Swal.fire({
                      title: '<strong class="text-danger">ERROR</strong>',
                      type: 'error',
                      html: '<b>' + errorMessage + '<b>',
                      width: '400px',
                      confirmButtonColor: '#6610f2',
                    });
                    return;
                  }

                  Swal.fire({
                    title: '<strong class="text-success">Success</strong>',
                    type: 'success',
                    html: '<b>Request Submitted  Successfully<b>',
                    width: '400px',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    timer: 2000
                  }).then(()=>{
                    $("#requestForm")[0].reset();
                    $("#tableRequest").DataTable().ajax.reload();
                    $("#newRequestModal").modal('hide')
                  })

                  
              }
            }).fail(barangayAjaxError)
        }
      })

  })



    $(document).on('click','#reset',function(){

        if($("#date_request").val() != '' ||  $("#date_issued").val() !=  '' || $("#date_expired").val() != '' ||  $("#status").val() != '' ||  $("#searching").val() != ''){
            $("#date_request").val('');
            $("#date_issued").val('');
            $("#date_expired").val('');
            $("#status").val('');
            $("#searching").val('');
            $("#tableRequest").DataTable().destroy();
            tableRequest();
              $("#searching").keyup();
        }
    })


    $(document).on('click','.acceptStatus',function(){

        $("#show_status").html('');

        var residence_id = $(this).attr('id');
        var certificate_id = $(this).data('id');

        $.ajax({
          url: 'certificateRequestStatus.php',
          type: 'POST',
          data:{
            residence_id:residence_id,
            certificate_id:certificate_id,
          },
          success:function(data){
            $("#show_status").html(data);
            $("#showStatusRequestModal").modal('show');
          }
        }).fail(barangayAjaxError)

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



  $("#purpose").inputFilter(function(value) {
  return /^[a-z, ]*$/i.test(value); 
  });
  


</script>


</body>
</html>
