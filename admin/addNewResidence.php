<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/helpers.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_family.php';
require_once '../includes/upload_helper.php';
require_once '../includes/audit_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: newResidence.php');
    exit;
}

$activeBarangay = barangay_require_active($con, 'barangayHub.php');
$barangay_id = $activeBarangay['id'];

$postString = static function (string $key, string $default = '') use ($con): string {
    return $con->real_escape_string(trim((string) ($_POST[$key] ?? $default)));
};

try {
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime();
    $number = residence_generate_number($con, (string) $barangay_id, (string) ($activeBarangay['barangay'] ?? ''));
    $date_added = date('m/d/Y h:i A');
    $archive = 'NO';

    $add_first_name = $postString('add_first_name');
    $add_last_name = $postString('add_last_name');
    if ($add_first_name === '' || $add_last_name === '') {
        http_response_code(422);
        residence_exit_add_result('errorValidation');
    }

    $add_pwd_check = $postString('add_pwd_info');
    $add_pwd = $postString('add_pwd');
    $add_single_parent = $postString('add_single_parent');
    $add_indigenous = $postString('add_indigenous', 'NO');
    $add_voters = $postString('add_voters');
    $add_middle_name = $postString('add_middle_name');
    $add_suffix = $postString('add_suffix');
    $add_birth_date = $postString('add_birth_date');
    residence_require_unique_name_or_exit(
        $con,
        (string) $barangay_id,
        $add_first_name,
        $add_middle_name,
        $add_last_name,
        $add_suffix,
        null,
        $add_birth_date
    );
    $add_gender = $postString('add_gender');
    $add_civil_status = $postString('add_civil_status');
    $add_religion = $postString('add_religion');
    $add_nationality = $postString('add_nationality');
    $add_contact_number = $postString('add_contact_number');
    $add_email_address = $postString('add_email_address');
    $add_address = $postString('add_address');
    residence_require_minor_guardian_or_exit($_POST);
    $add_birth_place = $postString('add_birth_place');
    $add_municipality = $postString('add_municipality');
    $add_zip = $postString('add_zip');
    $add_barangay = $postString('add_barangay', (string) ($activeBarangay['barangay'] ?? ''));
    $add_house_number = $postString('add_house_number');
    $add_street = $postString('add_street');
    $add_fathers_name = $postString('add_fathers_name');
    $add_mothers_name = $postString('add_mothers_name');
    $add_guardian = $postString('add_guardian');
    $add_guardian_contact = $postString('add_guardian_contact');
    $add_status = 'ACTIVE';
    $user_type = 'resident';
    $password = barangay_hash_password($date->format('mdYHisv'));

    $new_image_name = '';
    $new_image_path = '';
    if (!empty($_FILES['add_image']['name'])) {
        $upload = barangay_store_image_upload($_FILES['add_image']);
        if (!$upload['ok']) {
            residence_exit_add_result('errorImage');
        }
        $new_image_name = $upload['filename'];
        $new_image_path = $upload['path'];
    }

    $today = date('Y/m/d');
    $age = date_diff(date_create($add_birth_date), date_create($today));
    $add_age_date = $age->format('%y');
    $senior = ((int) $add_age_date >= 60) ? 'YES' : 'NO';
    $age_add = ((int) $add_age_date === 0) ? '' : (string) $add_age_date;

    $sql = "INSERT INTO `residence_information`(`residence_id`,`first_name`, `middle_name`, `last_name`, `age`, `suffix`, `gender`, `civil_status`, `religion`, `nationality`, `contact_number`, `email_address`, `address`, `birth_date`, `birth_place`, `municipality`, `zip`, `barangay`, `house_number`, `street`, `fathers_name`, `mothers_name`, `guardian`, `guardian_contact`,`image`,`image_path`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException($con->error);
    }
    $stmt->bind_param(
        'ssssssssssssssssssssssssss',
        $number,
        $add_first_name,
        $add_middle_name,
        $add_last_name,
        $age_add,
        $add_suffix,
        $add_gender,
        $add_civil_status,
        $add_religion,
        $add_nationality,
        $add_contact_number,
        $add_email_address,
        $add_address,
        $add_birth_date,
        $add_birth_place,
        $add_municipality,
        $add_zip,
        $add_barangay,
        $add_house_number,
        $add_street,
        $add_fathers_name,
        $add_mothers_name,
        $add_guardian,
        $add_guardian_contact,
        $new_image_name,
        $new_image_path
    );
    $stmt->execute();
    $stmt->close();

    residence_save_spouse($con, $number, $_POST, 'add_');
    residence_save_dependents($con, $number, $_POST, 'add_');

    residence_insert_status_row(
        $con,
        $number,
        $add_status,
        $add_voters,
        $archive,
        $add_pwd,
        $add_pwd_check,
        $senior,
        $add_single_parent,
        $date_added,
        $barangay_id,
        $add_indigenous
    );

    if (barangay_column_exists($con, 'users', 'barangay_id')) {
        $sql_add_user = "INSERT INTO `users`(`id`, `first_name`, `middle_name`, `last_name`, `username`, `password`, `user_type`, `contact_number`,`image`,`image_path`, `barangay_id`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $stmt_user = $con->prepare($sql_add_user);
        if (!$stmt_user) {
            throw new RuntimeException($con->error);
        }
        $stmt_user->bind_param('sssssssssss', $number, $add_first_name, $add_middle_name, $add_last_name, $number, $password, $user_type, $add_contact_number, $new_image_name, $new_image_path, $barangay_id);
    } else {
        $sql_add_user = "INSERT INTO `users`(`id`, `first_name`, `middle_name`, `last_name`, `username`, `password`, `user_type`, `contact_number`,`image`,`image_path`) VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt_user = $con->prepare($sql_add_user);
        if (!$stmt_user) {
            throw new RuntimeException($con->error);
        }
        $stmt_user->bind_param('ssssssssss', $number, $add_first_name, $add_middle_name, $add_last_name, $number, $password, $user_type, $add_contact_number, $new_image_name, $new_image_path);
    }
    $stmt_user->execute();
    $stmt_user->close();

    barangay_audit_log(
        $con,
        strtoupper('ADMIN') . ': ADDED RESIDENT - ' . $number . ' |  ' . $add_first_name . ' ' . $add_last_name . ' ' . $add_suffix,
        'create',
        [
            'barangay_id' => (string) $barangay_id,
            'entity_type' => 'residence',
            'entity_id' => $number,
        ]
    );

    residence_exit_add_result('success|' . $number);
} catch (Exception $e) {
    http_response_code(500);
    residence_exit_add_result('errorServer');
}
