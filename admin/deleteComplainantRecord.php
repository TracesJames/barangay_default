<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
barangay_require_mutate_barangay_records($con);

try {
    $blotter_id = trim((string) ($_POST['blotter_id'] ?? $_REQUEST['blotter_id'] ?? ''));
    $complainant_id = trim((string) ($_POST['complainant_id'] ?? $_REQUEST['complainant_id'] ?? ''));
    if ($blotter_id === '' || $complainant_id === '') {
        http_response_code(422);
        exit('Missing blotter or complainant id');
    }

    barangay_assert_blotter_in_scope($con, $blotter_id);
    $blank = '';

    $stmt_delete_complainant_record = $con->prepare(
        'UPDATE blotter_complainant SET complainant_id = ? WHERE blotter_main = ? AND complainant_id = ?'
    );
    if ($stmt_delete_complainant_record) {
        $stmt_delete_complainant_record->bind_param('sss', $blank, $blotter_id, $complainant_id);
        $stmt_delete_complainant_record->execute();
        $stmt_delete_complainant_record->close();
    }
} catch (Throwable $e) {
    http_response_code(400);
    exit('Unable to delete complainant from blotter.');
}
