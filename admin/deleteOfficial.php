<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/audit_log.php';
require_once '../includes/csrf.php';

barangay_require_post();
csrf_verify();
barangay_require_mutate_barangay_records($con);
barangay_require_end_official_term($con);

try {
    if (!isset($_REQUEST['official_id'])) {
        http_response_code(400);
        exit('Missing official_id');
    }

    $official_id = trim((string) $_REQUEST['official_id']);
    if ($official_id === '') {
        http_response_code(400);
        exit('Missing official_id');
    }

    $sql = "SELECT official_information.*, official_status.*, position.position AS check_position
            FROM official_information
            INNER JOIN official_status ON official_information.official_id = official_status.official_id
            INNER JOIN position ON official_status.position = position.position_id
            WHERE official_information.official_id = ?";
    $stmt = $con->prepare($sql) or die($con->error);
    $stmt->bind_param('s', $official_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        exit('Official not found');
    }

    $official_id_end = (string) $row['official_id'];
    $first_name = (string) $row['first_name'];
    $middle_name = (string) $row['middle_name'];
    $last_name = (string) $row['last_name'];
    $suffix = (string) $row['suffix'];
    $birth_date = (string) $row['birth_date'];
    $birth_place = (string) $row['birth_place'];
    $gender = (string) $row['gender'];
    $age = (string) $row['age'];
    $civil_status = (string) $row['civil_status'];
    $religion = (string) $row['religion'];
    $nationality = (string) $row['nationality'];
    $municipality = (string) $row['municipality'];
    $zip = (string) $row['zip'];
    $barangay = (string) $row['barangay'];
    $house_number = (string) $row['house_number'];
    $street = (string) $row['street'];
    $address = (string) $row['address'];
    $email_address = (string) $row['email_address'];
    $contact_number = (string) $row['contact_number'];
    $fathers_name = (string) $row['fathers_name'];
    $mothers_name = (string) $row['mothers_name'];
    $guardian = (string) $row['guardian'];
    $guardian_contact = (string) $row['guardian_contact'];
    $image = (string) $row['image'];
    $image_path = (string) $row['image_path'];
    $position = (string) $row['position'];
    $check_position = strtoupper((string) $row['check_position']);
    $senior = (string) $row['senior'];
    $term_from = (string) $row['term_from'];
    $term_to = (string) $row['term_to'];
    $pwd = (string) $row['pwd'];
    $status = 'INACTIVE';
    $voters = (string) $row['voters'];
    $pwd_info = (string) $row['pwd_info'];
    $single_parent = (string) $row['single_parent'];
    $purok_id = (string) ($row['purok_id'] ?? '');
    $barangayId = (string) ($row['barangay_id'] ?? barangay_session_id() ?? '');
    $date_deleted = date('m/d/Y h:i A');

    barangay_official_end_status_ensure_barangay_id($con);
    $hasEndBarangay = barangay_column_exists($con, 'official_end_status', 'barangay_id');
    $hasEndPurok = barangay_column_exists($con, 'official_end_status', 'purok_id');

    $con->begin_transaction();
    try {
        $sql_insert = "INSERT INTO `official_end_information`
        (`official_id`, `first_name`, `middle_name`, `last_name`, `gender`, `suffix`,
         `birth_date`, `birth_place`, `age`, `civil_status`, `religion`, `nationality`,
         `municipality`, `zip`, `barangay`, `house_number`, `street`, `address`,
         `email_address`, `contact_number`, `fathers_name`, `mothers_name`, `guardian`,
         `guardian_contact`, `image`, `image_path`)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt_insert = $con->prepare($sql_insert) or throw new RuntimeException($con->error);
        $stmt_insert->bind_param(
            'ssssssssssssssssssssssssss',
            $official_id_end,
            $first_name,
            $middle_name,
            $last_name,
            $gender,
            $suffix,
            $birth_date,
            $birth_place,
            $age,
            $civil_status,
            $religion,
            $nationality,
            $municipality,
            $zip,
            $barangay,
            $house_number,
            $street,
            $address,
            $email_address,
            $contact_number,
            $fathers_name,
            $mothers_name,
            $guardian,
            $guardian_contact,
            $image,
            $image_path
        );
        if (!$stmt_insert->execute()) {
            throw new RuntimeException($stmt_insert->error);
        }
        $stmt_insert->close();

        if ($hasEndBarangay && $hasEndPurok) {
            $sql_official_status = "INSERT INTO `official_end_status`
                (`official_id`, `barangay_id`, `status`, `senior`, `voters`, `pwd_info`, `single_parent`,
                 `position`, `purok_id`, `date_deleted`, `term_from`, `term_to`, `pwd`)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt_official_status = $con->prepare($sql_official_status) or throw new RuntimeException($con->error);
            $stmt_official_status->bind_param(
                'sssssssssssss',
                $official_id_end,
                $barangayId,
                $status,
                $senior,
                $voters,
                $pwd_info,
                $single_parent,
                $position,
                $purok_id,
                $date_deleted,
                $term_from,
                $term_to,
                $pwd
            );
        } elseif ($hasEndBarangay) {
            $sql_official_status = "INSERT INTO `official_end_status`
                (`official_id`, `barangay_id`, `status`, `senior`, `voters`, `pwd_info`, `single_parent`,
                 `position`, `date_deleted`, `term_from`, `term_to`, `pwd`)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt_official_status = $con->prepare($sql_official_status) or throw new RuntimeException($con->error);
            $stmt_official_status->bind_param(
                'ssssssssssss',
                $official_id_end,
                $barangayId,
                $status,
                $senior,
                $voters,
                $pwd_info,
                $single_parent,
                $position,
                $date_deleted,
                $term_from,
                $term_to,
                $pwd
            );
        } else {
            $sql_official_status = "INSERT INTO `official_end_status`
                (`official_id`, `status`, `senior`, `voters`, `pwd_info`, `single_parent`,
                 `position`, `date_deleted`, `term_from`, `term_to`, `pwd`)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $stmt_official_status = $con->prepare($sql_official_status) or throw new RuntimeException($con->error);
            $stmt_official_status->bind_param(
                'sssssssssss',
                $official_id_end,
                $status,
                $senior,
                $voters,
                $pwd_info,
                $single_parent,
                $position,
                $date_deleted,
                $term_from,
                $term_to,
                $pwd
            );
        }
        if (!$stmt_official_status->execute()) {
            throw new RuntimeException($stmt_official_status->error);
        }
        $stmt_official_status->close();

        $sql_delete = 'DELETE FROM official_information WHERE official_id = ?';
        $stmt_delete = $con->prepare($sql_delete) or throw new RuntimeException($con->error);
        $stmt_delete->bind_param('s', $official_id_end);
        if (!$stmt_delete->execute()) {
            throw new RuntimeException($stmt_delete->error);
        }
        $stmt_delete->close();

        $sql_delete_status = 'DELETE FROM official_status WHERE official_id = ?';
        $stmt_delete_status = $con->prepare($sql_delete_status) or throw new RuntimeException($con->error);
        $stmt_delete_status->bind_param('s', $official_id_end);
        if (!$stmt_delete_status->execute()) {
            throw new RuntimeException($stmt_delete_status->error);
        }
        $stmt_delete_status->close();

        $con->commit();
    } catch (Throwable $inner) {
        $con->rollback();
        throw $inner;
    }

    $admin = strtoupper('ADMIN') . ': ENDED OFFICIAL TERM - ' . $official_id_end
        . ' | ' . $check_position . ' - ' . $first_name . ' ' . $last_name
        . ' | term ' . $term_from . ' to ' . $term_to;
    barangay_audit_log($con, $admin, 'delete', [
        'barangay_id' => $barangayId !== '' ? $barangayId : null,
        'entity_type' => 'official',
        'entity_id' => $official_id_end,
    ]);

    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'official_id' => $official_id_end]);
} catch (Throwable $e) {
    http_response_code(500);
    echo $e->getMessage();
}
