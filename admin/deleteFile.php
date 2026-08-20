<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

barangay_require_post();
barangay_require_backup_access($con);

try {
    $id = trim((string) ($_POST['file_id'] ?? $_REQUEST['file_id'] ?? ''));
    if ($id === '') {
        http_response_code(422);
        exit('Missing backup id');
    }

    $sql_delete_file = 'DELETE FROM backup WHERE id = ?';
    $stmt_delete_file = $con->prepare($sql_delete_file);
    if (!$stmt_delete_file) {
        throw new RuntimeException('Database error');
    }
    $stmt_delete_file->bind_param('s', $id);
    $stmt_delete_file->execute();
    $stmt_delete_file->close();
} catch (Throwable $e) {
    http_response_code(400);
    exit('Unable to delete backup file.');
}
