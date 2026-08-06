<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/helpers.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_family.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'duplicate' => false, 'message' => 'Method not allowed']);
    exit;
}

$activeBarangay = barangay_require_active($con, 'barangayHub.php?picker=1');
$barangay_id = (string) $activeBarangay['id'];

$first = trim((string) ($_POST['add_first_name'] ?? ''));
$middle = trim((string) ($_POST['add_middle_name'] ?? ''));
$last = trim((string) ($_POST['add_last_name'] ?? ''));
$suffix = trim((string) ($_POST['add_suffix'] ?? ''));
$birth = trim((string) ($_POST['add_birth_date'] ?? ''));

if ($first === '' || $last === '') {
    echo json_encode(['ok' => true, 'duplicate' => false, 'level' => '']);
    exit;
}

$check = residence_duplicate_check($con, $barangay_id, $first, $middle, $last, $suffix, $birth);
if ($check === null) {
    echo json_encode(['ok' => true, 'duplicate' => false, 'level' => '']);
    exit;
}

echo json_encode([
    'ok' => true,
    'duplicate' => true,
    'level' => $check['level'],
    'residence_id' => $check['residence_id'],
    'message' => $check['message'],
]);
