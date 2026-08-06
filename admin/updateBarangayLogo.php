<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/csrf.php';
require_once '../includes/upload_helper.php';

header('Content-Type: application/json; charset=utf-8');
csrf_verify();
barangay_require_super_admin($con, 'dashboard.php');

$barangayId = trim($_POST['barangay_id'] ?? '');
if ($barangayId === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Barangay ID is required.']);
    exit;
}

$row = barangay_load_by_id($con, $barangayId);
if ($row === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Barangay not found.']);
    exit;
}

if (empty($_FILES['logo']['name'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Please choose an image file.']);
    exit;
}

$upload = barangay_store_image_upload($_FILES['logo']);
if (!$upload['ok']) {
    http_response_code(400);
    echo json_encode(['error' => $upload['error'] ?? 'Invalid image file.']);
    exit;
}

$stmt = $con->prepare('UPDATE barangay_information SET image = ?, image_path = ? WHERE id = ?');
$stmt->bind_param('sss', $upload['filename'], $upload['path'], $barangayId);
$stmt->execute();

$updated = barangay_load_by_id($con, $barangayId);
echo json_encode([
    'ok' => true,
    'logo_url' => barangay_logo_url($updated, '../'),
    'barangay_id' => $barangayId,
]);
