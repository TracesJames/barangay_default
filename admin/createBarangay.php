<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/csrf.php';
require_once '../includes/upload_helper.php';

header('Content-Type: application/json; charset=utf-8');
csrf_verify();

if (!barangay_user_is_super_admin($con, (string) $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Only the system administrator can create barangays.']);
    exit;
}

$barangay = trim($_POST['barangay'] ?? '');
$zone = trim($_POST['zone'] ?? '');
$district = trim($_POST['district'] ?? '');
$address = trim($_POST['address'] ?? '');
$postal = trim($_POST['postal_address'] ?? '');
$adminPassword = trim($_POST['admin_password'] ?? '');

$fieldErrors = [];
if ($barangay === '') {
    $fieldErrors['barangay'] = 'Barangay name is required.';
} elseif (mb_strlen($barangay) < 2) {
    $fieldErrors['barangay'] = 'Barangay name must be at least 2 characters.';
} elseif (mb_strlen($barangay) > 120) {
    $fieldErrors['barangay'] = 'Barangay name is too long.';
}
if ($zone === '') {
    $fieldErrors['zone'] = 'Default purok label is required.';
}
if ($district === '') {
    $fieldErrors['district'] = 'District is required.';
}
if ($address === '') {
    $fieldErrors['address'] = 'City / address is required.';
}
if ($postal === '') {
    $fieldErrors['postal_address'] = 'Postal address is required.';
}
if ($adminPassword !== '' && strlen($adminPassword) < 6) {
    $fieldErrors['admin_password'] = 'Admin password must be at least 6 characters.';
}

if ($fieldErrors !== []) {
    http_response_code(422);
    echo json_encode(['error' => 'Please correct the highlighted fields.', 'fields' => $fieldErrors]);
    exit;
}

$checkName = $con->prepare('SELECT id FROM barangay_information WHERE barangay = ? LIMIT 1');
$checkName->bind_param('s', $barangay);
$checkName->execute();
if ($checkName->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode([
        'error' => 'A barangay with this name already exists.',
        'fields' => ['barangay' => 'This barangay name is already registered.'],
    ]);
    exit;
}

barangay_ensure_psgc_column($con);
$psgc = barangay_psgc_lookup_by_name($barangay);
$id = $psgc !== '' ? $psgc : barangay_generate_id();

if ($psgc !== '') {
    $checkPsgc = $con->prepare('SELECT id FROM barangay_information WHERE id = ? OR psgc_code = ? LIMIT 1');
    $checkPsgc->bind_param('ss', $psgc, $psgc);
    $checkPsgc->execute();
    if ($checkPsgc->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            'error' => 'A barangay with this PSGC code already exists.',
            'fields' => ['barangay' => 'PSGC ' . $psgc . ' is already registered.'],
        ]);
        exit;
    }
}

$image = 'logo.png';
$imagePath = '../assets/dist/img/logo.png';

if (!empty($_FILES['logo']['name'])) {
    $upload = barangay_store_image_upload($_FILES['logo']);
    if (!$upload['ok']) {
        http_response_code(400);
        echo json_encode([
            'error' => $upload['error'] ?? 'Invalid logo image.',
            'fields' => ['logo' => $upload['error'] ?? 'Upload a JPG, PNG, GIF, or WebP image up to 5 MB.'],
        ]);
        exit;
    }
    $image = $upload['filename'];
    $imagePath = $upload['path'];
}

$stmt = $con->prepare('INSERT INTO barangay_information (id, barangay, psgc_code, zone, district, address, postal_address, image, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$psgcValue = $psgc !== '' ? $psgc : '';
$stmt->bind_param('sssssssss', $id, $barangay, $psgcValue, $zone, $district, $address, $postal, $image, $imagePath);
$stmt->execute();

$passwordForAdmin = $adminPassword !== '' ? $adminPassword : '';
$adminAccount = barangay_create_admin_for_barangay($con, $id, $barangay, $passwordForAdmin);
$secretaryAccount = barangay_create_secretary_for_barangay($con, $id, $barangay, $passwordForAdmin);

$dateActivity = date('j-n-Y g:i A');
$logMessage = strtoupper('ADMIN') . ': CREATED BARANGAY - ' . $barangay . ' (' . $id . ')';
$logStatus = 'create';
$stmtLog = $con->prepare('INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?, ?, ?)');
if ($stmtLog) {
    $stmtLog->bind_param('sss', $logMessage, $dateActivity, $logStatus);
    $stmtLog->execute();
}

barangay_set_active($id);

echo json_encode([
    'ok' => true,
    'barangay_id' => $id,
    'barangay' => $barangay,
    'redirect' => 'dashboard.php',
    'admin' => $adminAccount ? [
        'username' => $adminAccount['username'],
        'password' => $adminAccount['password'],
    ] : null,
    'secretary' => $secretaryAccount ? [
        'username' => $secretaryAccount['username'],
        'password' => $secretaryAccount['password'],
    ] : null,
]);
