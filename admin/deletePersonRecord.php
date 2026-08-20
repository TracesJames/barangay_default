<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
barangay_require_mutate_barangay_records($con);

try {
    $blotter_id = trim((string) ($_POST['blotter_id'] ?? $_REQUEST['blotter_id'] ?? ''));
    $person_id = trim((string) ($_POST['person_id'] ?? $_REQUEST['person_id'] ?? ''));
    if ($blotter_id === '' || $person_id === '') {
        http_response_code(422);
        exit('Missing blotter or person id');
    }

    barangay_assert_blotter_in_scope($con, $blotter_id);
    $blank = '';

    $stmt_delete_person_record = $con->prepare(
        'UPDATE blotter_status SET person_id = ? WHERE blotter_main = ? AND person_id = ?'
    );
    if ($stmt_delete_person_record) {
        $stmt_delete_person_record->bind_param('sss', $blank, $blotter_id, $person_id);
        $stmt_delete_person_record->execute();
        $stmt_delete_person_record->close();
    }
} catch (Throwable $e) {
    http_response_code(400);
    exit('Unable to delete person from blotter.');
}
