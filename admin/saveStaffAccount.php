<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_accounts.php';
require_once '../includes/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('errorMethod');
}

$userId = trim((string) ($_POST['user_id'] ?? ''));
$isEdit = $userId !== '';
$actorId = (string) ($_SESSION['user_id'] ?? '');
$isSsa = barangay_user_is_ssa($con, $actorId);
$isNutritionSa = barangay_user_is_nutrition_portal_admin($con, $actorId);

$nutritionLowerRoles = [
    STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
    STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
    STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
];

if (!$isEdit) {
    $requestedRole = trim((string) ($_POST['staff_role'] ?? ''));

    // Nutrition Portal: only SSA may create Nutrition Super Admin (SA).
    if ($requestedRole === STAFF_ROLE_NUTRITION_SUPER_ADMIN && !$isSsa) {
        http_response_code(403);
        exit('errorRole');
    }

    // Nutrition SA may create only Nutrition Admin (A), BNS, and CNPC.
    if ($isNutritionSa && !$isSsa) {
        if (!in_array($requestedRole, $nutritionLowerRoles, true)) {
            http_response_code(403);
            exit('errorRole');
        }
    }
} else {
    $check = $con->prepare('SELECT staff_role FROM users WHERE id = ? LIMIT 1');
    if ($check) {
        $check->bind_param('s', $userId);
        $check->execute();
        $existingRole = (string) (($check->get_result()->fetch_assoc()['staff_role'] ?? ''));
        $check->close();

        if ($isNutritionSa && !$isSsa && !in_array($existingRole, $nutritionLowerRoles, true)) {
            http_response_code(403);
            exit('errorRole');
        }
    }
}

if ($isEdit) {
    $assignmentIds = [];
    if (isset($_POST['barangay_ids']) && is_array($_POST['barangay_ids'])) {
        $assignmentIds = $_POST['barangay_ids'];
    } elseif (trim((string) ($_POST['barangay_id'] ?? '')) !== '') {
        $assignmentIds = [$_POST['barangay_id']];
    }

    $requestedRole = trim((string) ($_POST['staff_role'] ?? ''));
    if ($requestedRole !== '' && $isSsa) {
        // SSA may reassign any role; non-SSA path keeps existing role via update().
    } elseif ($requestedRole !== '' && !$isSsa) {
        // Ignore role changes from non-SSA actors.
        $requestedRole = '';
    }

    $result = staff_account_update($con, $userId, [
        'staff_role' => $requestedRole,
        'first_name' => $_POST['first_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'username' => $_POST['username'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
        'password' => $_POST['password'] ?? '',
        'password_changed' => ($_POST['password_changed'] ?? '') === '1' ? '1' : '',
        'barangay_id' => $_POST['barangay_id'] ?? '',
        'barangay_ids' => $assignmentIds,
    ]);
} else {
    $assignmentIds = [];
    if (isset($_POST['barangay_ids']) && is_array($_POST['barangay_ids'])) {
        $assignmentIds = $_POST['barangay_ids'];
    }
    $result = staff_account_create($con, [
        'staff_role' => $_POST['staff_role'] ?? '',
        'barangay_id' => $_POST['barangay_id'] ?? '',
        'barangay_ids' => $assignmentIds,
        'first_name' => $_POST['first_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
    ]);
}

if (!$result['ok']) {
    http_response_code(422);
    exit($result['error'] ?? 'errorSave');
}

exit('success');
