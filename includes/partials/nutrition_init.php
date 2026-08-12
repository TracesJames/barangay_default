<?php

/**
 * Shared bootstrap for Barangay Nutrition Profiling admin pages.
 * Include after connection.php and auth_admin.php.
 */
require_once __DIR__ . '/../nutrition_context.php';
require_once __DIR__ . '/../nutrition_bnp_reports.php';

nutrition_ensure_module_tables($con);

$user_id = (string) $_SESSION['user_id'];
$stmt_user = $con->prepare('SELECT first_name, middle_name, last_name, username, contact_number, image, image_path, user_type FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc() ?: [];

$first_name_user = $row_user['first_name'] ?? 'Admin';
$middle_name_user = $row_user['middle_name'] ?? '';
$last_name_user = $row_user['last_name'] ?? '';
$username_user = $row_user['username'] ?? '';
$contact_number_user = $row_user['contact_number'] ?? '';
$user_type = $row_user['user_type'] ?? 'admin';
$user_image = $row_user['image'] ?? '';
$user_image_path = $row_user['image_path'] ?? '';
$userAvatarUrl = barangay_user_avatar_url($user_image, $user_image_path, '../');
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionScholar = barangay_user_is_barangay_nutrition_scholar($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);
$isCnpc = barangay_user_is_cnpc($con, $user_id);
$staffRoleLabel = $isNutritionPortalAdmin
    ? staff_role_label(STAFF_ROLE_NUTRITION_SUPER_ADMIN)
    : staff_role_label(barangay_user_staff_role($con, $user_id));

if ($isNutritionScholar) {
    // Assigned barangay is forced in barangay_enforce_admin_page_access.
} elseif (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'nutritionAccountProfile.php') {
    // City nutrition accounts can edit profile without an active barangay session.
} elseif (($isSuperAdmin || $isBnsAdmin || $isNutritionPortalAdmin || $isCnpc) && barangay_session_id() === null) {
    header('Location: nutritionSuperDashboard.php');
    exit;
} elseif ($isCityAdmin && barangay_session_id() === null) {
    header('Location: barangayHub.php?picker=1&system=nutrition');
    exit;
} elseif (!$isSuperAdmin && !$isCityAdmin && !$isBnsAdmin && !$isNutritionPortalAdmin && empty($barangay_id)) {
    header('Location: barangayHub.php?picker=1&system=nutrition');
    exit;
}

// Ensure CSRF is persisted before session_write_close (forms / AJAX on nutrition pages).
require_once __DIR__ . '/../csrf.php';
csrf_token();
barangay_release_session_lock();

$nutritionPageTitle = $nutritionPageTitle ?? 'Nutrition Profiling';
$nutritionSettings = nutrition_load_settings($con, (string) $barangay_id, (string) $barangay);
