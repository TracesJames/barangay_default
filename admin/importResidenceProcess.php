<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_import.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: importResidence.php');
    exit;
}

$activeBarangay = barangay_require_active($con, 'barangayHub.php?picker=1');
$barangay_id = $activeBarangay['id'];

if (empty($_FILES['import_file'])) {
    $_SESSION['residence_import_result'] = [
        'inserted' => 0,
        'failed' => 0,
        'errors' => ['No file was uploaded.'],
    ];
    header('Location: importResidence.php');
    exit;
}

try {
    $_SESSION['residence_import_result'] = residence_import_process_upload(
        $con,
        $_FILES['import_file'],
        $barangay_id,
        $activeBarangay,
        'admin'
    );
} catch (Throwable $e) {
    $_SESSION['residence_import_result'] = [
        'inserted' => 0,
        'failed' => 0,
        'errors' => [$e->getMessage()],
    ];
}

header('Location: importResidence.php');
exit;
