<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
csrf_verify();

if (!barangay_user_is_super_admin($con, (string) $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Only the system administrator can manage barangay accounts.']);
    exit;
}

$barangayId = trim($_POST['barangay_id'] ?? '');
$password = trim($_POST['password'] ?? '');
$reset = ($_POST['reset'] ?? '') === '1';

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

if ($password !== '' && strlen($password) < 6) {
    http_response_code(422);
    echo json_encode([
        'error' => 'Password must be at least 6 characters.',
        'fields' => ['password' => 'Password must be at least 6 characters.'],
    ]);
    exit;
}

$plainPassword = $password !== '' ? $password : 'barangay123';
$barangayName = (string) ($row['barangay'] ?? '');
$existing = barangay_load_secretary_account($con, $barangayId);

if ($existing !== null && !$reset) {
    echo json_encode([
        'ok' => true,
        'created' => false,
        'reset' => false,
        'barangay' => $barangayName,
        'secretary' => [
            'username' => $existing['username'],
            'password' => null,
        ],
        'message' => 'This barangay already has a secretary account.',
    ]);
    exit;
}

if ($existing !== null && $reset) {
    if (!barangay_update_secretary_password($con, $existing['id'], $plainPassword)) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not reset the secretary password.']);
        exit;
    }

    $dateActivity = date('j-n-Y g:i A');
    $logMessage = strtoupper('ADMIN') . ': RESET BARANGAY SECRETARY PASSWORD - ' . $barangayName . ' (' . $existing['username'] . ')';
    $logStatus = 'update';
    $stmtLog = $con->prepare('INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?, ?, ?)');
    if ($stmtLog) {
        $stmtLog->bind_param('sss', $logMessage, $dateActivity, $logStatus);
        $stmtLog->execute();
    }

    echo json_encode([
        'ok' => true,
        'created' => false,
        'reset' => true,
        'barangay' => $barangayName,
        'secretary' => [
            'username' => $existing['username'],
            'password' => $plainPassword,
        ],
        'message' => 'Secretary password has been reset.',
    ]);
    exit;
}

$created = barangay_create_secretary_for_barangay($con, $barangayId, $barangayName, $plainPassword);
if ($created === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create barangay secretary account.']);
    exit;
}

$dateActivity = date('j-n-Y g:i A');
$logMessage = strtoupper('ADMIN') . ': CREATED BARANGAY SECRETARY - ' . $barangayName . ' (' . $created['username'] . ')';
$logStatus = 'create';
$stmtLog = $con->prepare('INSERT INTO activity_log (`message`, `date`, `status`) VALUES (?, ?, ?)');
if ($stmtLog) {
    $stmtLog->bind_param('sss', $logMessage, $dateActivity, $logStatus);
    $stmtLog->execute();
}

echo json_encode([
    'ok' => true,
    'created' => true,
    'reset' => false,
    'barangay' => $barangayName,
    'secretary' => [
        'username' => $created['username'],
        'password' => $created['password'],
    ],
    'message' => 'Barangay secretary account created.',
]);
