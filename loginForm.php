<?php
include_once 'connection.php';
require_once 'includes/helpers.php';
require_once 'includes/security.php';
barangay_start_session();
include_once 'userInfo.php';
require_once 'includes/csrf.php';
require_once 'includes/barangay_context.php';
require_once 'includes/nutrition_context.php';
require_once 'includes/session_guard.php';
csrf_verify();

try{
  $username = trim((string) ($_POST['username'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  $forceLogin = ((string) ($_POST['force_login'] ?? '')) === '1';

  $rateLimited = barangay_login_rate_limit_check($username);
  if ($rateLimited !== null) {
      exit($rateLimited);
  }

  $hasBarangayId = function_exists('barangay_column_exists')
    ? barangay_column_exists($con, 'users', 'barangay_id')
    : false;

  $sql = "SELECT `id`,`username`, `password`, `user_type`, `first_name`, `middle_name`, `last_name`"
    . ($hasBarangayId ? ", `barangay_id`" : "")
    . " FROM `users` WHERE (username = ? OR id = ?)";
  $stmt = $con->prepare($sql) or die ($con->error);
  $stmt->bind_param('ss', $username, $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$count = $result->num_rows;

if($count > 0){
  $user_id = $row['id'];
  $checkUsername = $row['username'];
  $checkPassword = $row['password'];
  $fname = $row['first_name'];
  $mname = $row['middle_name'];
  $lname = $row['last_name'];
  $user_type = $row['user_type'];

    if(barangay_verify_password($password, $checkPassword)){

      $existingLocalToken = (string) ($_SESSION['active_session_token'] ?? '');
      $sameUserAlready = isset($_SESSION['user_id'])
          && (string) $_SESSION['user_id'] === (string) $user_id
          && $existingLocalToken !== '';

      if (!$sameUserAlready
          && !$forceLogin
          && barangay_session_guard_is_active_elsewhere($con, (string) $user_id, $existingLocalToken !== '' ? $existingLocalToken : null)
      ) {
          exit('errorAlreadyLoggedIn');
      }

      session_regenerate_id(true);
      $_SESSION['user_id'] = $user_id;
      $_SESSION['username'] = $checkUsername;
      $_SESSION['user_type'] = $user_type;
      barangay_login_rate_limit_success($username);
      barangay_session_guard_issue($con, (string) $user_id);

      date_default_timezone_set('Asia/Manila');
      $dates = new DateTime();
      $uniqid = uniqid(mt_rand().$dates->format("YmdHisv").rand());
      $generate = md5($uniqid);
      $rand = uniqid(rand()) . $generate;

      $date = date("Y l F j, h:i A");
      $device = UserInfo::get_device();
      $os = UserInfo::get_os();

      if($user_type == 'admin'){

        $sql_user = "SELECT first_name, last_name FROM users WHERE id = ?";
        $stmt_user = $con->prepare($sql_user) or die ($con->error);
        $stmt_user->bind_param('s',$_SESSION['user_id']);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $row_user = $result_user->fetch_assoc();
        $first_name = $row_user['first_name'];
        $last_name = $row_user['last_name'];
        $status_activity_log = 'login';

        $date_activity = $now = date("j-n-Y g:i A"); 
        $message =  'ADMIN'. ': '.$first_name.' '. $last_name .' | '. 'LOGIN';
        $sql_system_logs= "INSERT INTO activity_log (`message`, `date`,`status`) VALUES (?,?,?)";
        $query_system_logs = $con->prepare($sql_system_logs) or die ($con->error);
        $query_system_logs->bind_param('sss',$message,$date_activity,$status_activity_log);
        $query_system_logs->execute();
        $query_system_logs->close();

        $nutritionToken = nutrition_admin_login_token($con, (string) $user_id);
        if ($nutritionToken !== null) {
            exit($nutritionToken);
        }

        if (barangay_user_is_cnpc($con, (string) $user_id)) {
            $cnpcIds = staff_assigned_barangay_ids($con, (string) $user_id);
            if (count($cnpcIds) === 1) {
                barangay_set_active($cnpcIds[0]);
                exit('nutrition_dashboard');
            }
            barangay_clear_active();
            exit('nutrition_admin');
        }

        $staffRole = barangay_user_staff_role($con, (string) $user_id);
        $userBarangayId = !empty($row['barangay_id']) ? (string) $row['barangay_id'] : null;

        if ($staffRole === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR) {
            if ($userBarangayId !== null) {
                barangay_set_active($userBarangayId);
            }
            exit('nutrition_dashboard');
        }

        if ($staffRole === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN) {
            barangay_clear_active();
            $barangays = barangay_list_all($con);
            if (count($barangays) === 1) {
                barangay_set_active((string) $barangays[0]['id']);
                exit('nutrition_dashboard');
            }
            exit('nutrition_admin');
        }

        if ($userBarangayId !== null) {
            barangay_set_active($userBarangayId);
            exit('admin_barangay');
        }

        if ($staffRole === STAFF_ROLE_SSA || $staffRole === STAFF_ROLE_SUPER_ADMIN) {
            barangay_clear_active();
            exit('super_admin');
        }

        if ($staffRole === STAFF_ROLE_ADMIN) {
            barangay_clear_active();
            exit('admin');
        }

        barangay_clear_active();
        exit('super_admin');
      }elseif($user_type == 'secretary'){

        $sql_user = "SELECT first_name, last_name FROM users WHERE id = ?";
        $stmt_user = $con->prepare($sql_user) or die ($con->error);
        $stmt_user->bind_param('s',$_SESSION['user_id']);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $row_user = $result_user->fetch_assoc();
        $first_name = $row_user['first_name'];
        $last_name = $row_user['last_name'];
        $status_activity_log = 'login';

        $date_activity = $now = date("j-n-Y g:i A"); 
        $message =  'OFFICIAL'. ': '.$first_name.' '. $last_name .' | '. 'LOGIN';
        $sql_system_logs= "INSERT INTO activity_log (`message`, `date`,`status`) VALUES (?,?,?)";
        $query_system_logs = $con->prepare($sql_system_logs) or die ($con->error);
        $query_system_logs->bind_param('sss',$message,$date_activity,$status_activity_log);
        $query_system_logs->execute();
        $query_system_logs->close();

        exit('secretary');
      }else{
        
        $sql_user = "SELECT first_name, last_name FROM users WHERE id = ?";
        $stmt_user = $con->prepare($sql_user) or die ($con->error);
        $stmt_user->bind_param('s',$_SESSION['user_id']);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $row_user = $result_user->fetch_assoc();
        $first_name = $row_user['first_name'];
        $last_name = $row_user['last_name'];
        $status_activity_log = 'login';

        $date_activity = $now = date("j-n-Y g:i A"); 
        $message =  'RESIDENT'. ': '.$first_name.' '. $last_name .' | '. 'LOGIN';
        $sql_system_logs= "INSERT INTO activity_log (`message`, `date`,`status`) VALUES (?,?,?)";
        $query_system_logs = $con->prepare($sql_system_logs) or die ($con->error);
        $query_system_logs->bind_param('sss',$message,$date_activity,$status_activity_log);
        $query_system_logs->execute();
        $query_system_logs->close();

        exit('resident');
      }

        
    }else{

      barangay_login_rate_limit_fail($username);
      exit('errorPassword');

    }

}else{

  barangay_login_rate_limit_fail($username);
  exit('errorUsername');

}

}catch(Exception $e){
  echo $e->getMessage();
}





?>