<?php

/**
 * Smoke test for Nutrition Portal + Barangay Portal core paths.
 * Usage: php scripts/smoke_test_portals.php
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';
require_once dirname(__DIR__) . '/includes/staff_permissions.php';
require_once dirname(__DIR__) . '/includes/nutrition_context.php';

$failed = 0;
$passed = 0;

function assert_true(string $label, bool $ok, string $detail = ''): void
{
    global $failed, $passed;
    if ($ok) {
        $passed++;
        echo "[PASS] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    } else {
        $failed++;
        echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    }
}

echo "=== Portal smoke test ===" . PHP_EOL;

assert_true('Database connection', $con instanceof mysqli && $con->ping());

$files = [
    'admin/nutritionSuperDashboard.php',
    'admin/nutritionAccountProfile.php',
    'admin/nutritionDashboard.php',
    'admin/barangayHub.php',
    'admin/dashboard.php',
    'admin/superDashboard.php',
    'admin/selectBarangay.php',
    'login.php',
    'includes/staff_permissions.php',
    'includes/barangay_context.php',
    'includes/nutrition_context.php',
    'includes/scripts_csrf.php',
    'includes/partials/super_nutrition_sidebar.php',
    'includes/partials/nutrition_sidebar.php',
];

foreach ($files as $rel) {
    $path = dirname(__DIR__) . '/' . $rel;
    assert_true("File exists: {$rel}", is_file($path));
}

assert_true('CSRF ajax phantom file absent', !is_file(dirname(__DIR__) . '/includes/csrf_ajax.php'));
assert_true('scripts_csrf.php present', is_file(dirname(__DIR__) . '/includes/scripts_csrf.php'));

$accountProfile = file_get_contents(dirname(__DIR__) . '/admin/nutritionAccountProfile.php');
assert_true(
    'nutritionAccountProfile uses scripts_csrf.php',
    is_string($accountProfile) && str_contains($accountProfile, 'scripts_csrf.php')
);
assert_true(
    'nutritionAccountProfile does not require csrf_ajax.php',
    is_string($accountProfile) && !str_contains($accountProfile, 'csrf_ajax.php')
);

$login = file_get_contents(dirname(__DIR__) . '/login.php');
assert_true(
    'Login shows City of Valencia Portal',
    is_string($login) && str_contains($login, 'City of Valencia Portal')
);

assert_true('Child max age is 19 (nutrition)', nutrition_child_max_age_years() === 19);
assert_true('Children label uses 0–19', nutrition_children_age_label() === 'Children (0–19)');

$hub = nutrition_hub_totals($con);
assert_true('Hub totals return children', isset($hub['children']));
assert_true('Hub totals return assessed', isset($hub['assessed']));
assert_true('Hub totals return pregnant', isset($hub['pregnant']));
assert_true('Hub totals return teenage_pregnant', isset($hub['teenage_pregnant']));

$pregnant = nutrition_pregnant_count($con);
$teenage = nutrition_teenage_pregnant_count($con);
assert_true('Pregnant count is non-negative', $pregnant >= 0, (string) $pregnant);
assert_true('Teenage <= pregnant', $teenage <= $pregnant, "teenage={$teenage}, pregnant={$pregnant}");

$stmt = $con->prepare(
    "SELECT id, username, staff_role FROM users
     WHERE username = 'nutrition.superadmin'
        OR staff_role = ?
     ORDER BY CASE WHEN username = 'nutrition.superadmin' THEN 0 ELSE 1 END, id ASC
     LIMIT 1"
);
$nutritionSaRole = STAFF_ROLE_NUTRITION_SUPER_ADMIN;
$stmt->bind_param('s', $nutritionSaRole);
$stmt->execute();
$nutritionAdmin = $stmt->get_result()->fetch_assoc();
$stmt->close();

assert_true('Nutrition Super Admin account exists', is_array($nutritionAdmin), is_array($nutritionAdmin) ? (string) $nutritionAdmin['username'] : '');
if (is_array($nutritionAdmin)) {
    $nid = (string) $nutritionAdmin['id'];
    $nUser = (string) ($nutritionAdmin['username'] ?? '');
    assert_true(
        'Nutrition SA role is nutrition_super_admin',
        ($nutritionAdmin['staff_role'] ?? '') === STAFF_ROLE_NUTRITION_SUPER_ADMIN
            || barangay_user_is_nutrition_portal_admin($con, $nid),
        (string) ($nutritionAdmin['staff_role'] ?? '')
    );
    assert_true('Nutrition SA is portal admin', barangay_user_is_nutrition_portal_admin($con, $nid), $nUser);
    assert_true('Nutrition SA is nutrition-scoped', barangay_user_is_nutrition_scoped_account($con, $nid), $nUser);
    assert_true(
        'Nutrition SA brand is Nutrition Portal',
        barangay_portal_brand_name($con, $nid) === 'Nutrition Portal',
        barangay_portal_brand_name($con, $nid)
    );
    assert_true(
        'Nutrition SA cannot open dashboard.php',
        !barangay_nutrition_portal_admin_can_access_script('dashboard.php')
    );
    assert_true(
        'Nutrition SA cannot open superDashboard.php',
        !barangay_nutrition_portal_admin_can_access_script('superDashboard.php')
    );
    assert_true(
        'Nutrition SA can open nutritionSuperDashboard.php',
        barangay_nutrition_portal_admin_can_access_script('nutritionSuperDashboard.php')
    );
    assert_true(
        'Nutrition SA can open nutritionAccountProfile.php',
        barangay_nutrition_portal_admin_can_access_script('nutritionAccountProfile.php')
    );
}

$citySuper = $con->query(
    "SELECT id, username, staff_role FROM users
     WHERE staff_role IN ('ssa', 'super_admin')
       AND username <> 'nutrition.superadmin'
       AND username NOT LIKE 'nutrition.%'
     LIMIT 1"
);
$cityRow = $citySuper ? $citySuper->fetch_assoc() : null;
if (is_array($cityRow)) {
    $cid = (string) $cityRow['id'];
    $role = (string) ($cityRow['staff_role'] ?? '');
    assert_true(
        'City SSA/SA is not nutrition-scoped',
        !barangay_user_is_nutrition_scoped_account($con, $cid),
        (string) $cityRow['username'] . " ({$role})"
    );
    assert_true(
        'City SSA/SA brand is City of Valencia Portal',
        barangay_portal_brand_name($con, $cid) === 'City of Valencia Portal',
        barangay_portal_brand_name($con, $cid)
    );
    if ($role === 'ssa') {
        assert_true('SSA can pick barangay', barangay_user_can_pick_barangay($con, $cid));
        assert_true('SSA manages staff accounts', barangay_user_can_manage_staff_accounts($con, $cid));
    }
} else {
    echo "[WARN] No city SSA/SA found for brand comparison" . PHP_EOL;
}

echo PHP_EOL . "=== Nutrition role matrix ===" . PHP_EOL;

$roleCases = [
    STAFF_ROLE_NUTRITION_SUPER_ADMIN => [
        'label' => 'Nutrition SA',
        'add' => true,
        'edit_names' => true,
        'edit_full' => true,
        'delete' => true,
        'household_script' => true,
        'delete_script' => true,
    ],
    STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN => [
        'label' => 'Nutrition Admin (A)',
        'add' => false,
        'edit_names' => true,
        'edit_full' => false,
        'delete' => false,
        'household_script' => false,
        'delete_script' => false,
    ],
    STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR => [
        'label' => 'BNS',
        'add' => true,
        'edit_names' => false,
        'edit_full' => false,
        'delete' => false,
        'household_script' => true,
        'delete_script' => false,
    ],
    STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR => [
        'label' => 'CNPC',
        'add' => true,
        'edit_names' => false,
        'edit_full' => false,
        'delete' => false,
        'household_script' => true,
        'delete_script' => false,
    ],
];

foreach ($roleCases as $role => $expect) {
    $stmt = $con->prepare('SELECT id, username FROM users WHERE staff_role = ? ORDER BY id ASC LIMIT 1');
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        echo "[WARN] No {$expect['label']} account ({$role}) — skip matrix checks" . PHP_EOL;
        continue;
    }

    $uid = (string) $row['id'];
    $who = $expect['label'] . ' @' . $row['username'];

    assert_true(
        "{$who}: add surveys",
        nutrition_user_can_add_household_surveys($con, $uid) === $expect['add']
    );
    assert_true(
        "{$who}: edit names",
        nutrition_user_can_edit_household_survey_names($con, $uid) === $expect['edit_names']
    );
    assert_true(
        "{$who}: edit full surveys",
        nutrition_user_can_edit_household_surveys($con, $uid) === $expect['edit_full']
    );
    assert_true(
        "{$who}: delete surveys",
        nutrition_user_can_delete_household_surveys($con, $uid) === $expect['delete']
    );

    if ($role === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN) {
        assert_true(
            "{$who}: cannot open household survey page",
            !barangay_bns_admin_can_access_script('nutritionHouseholdSurvey.php')
        );
        assert_true(
            "{$who}: can open consolidated report",
            barangay_bns_admin_can_access_script('nutritionBarangaySurvey.php')
        );
        assert_true(
            "{$who}: can update names endpoint",
            barangay_bns_admin_can_access_script('updateNutritionHouseholdSurveyNames.php')
        );
        assert_true(
            "{$who}: cannot open settings",
            !barangay_bns_admin_can_access_script('nutritionSettings.php')
        );
        assert_true(
            "{$who}: cannot open delete endpoint",
            !barangay_bns_admin_can_access_script('deleteNutritionHouseholdSurvey.php')
        );
    }

    if ($role === STAFF_ROLE_NUTRITION_SUPER_ADMIN) {
        assert_true(
            "{$who}: can open household survey page",
            barangay_nutrition_portal_admin_can_access_script('nutritionHouseholdSurvey.php') === $expect['household_script']
        );
        assert_true(
            "{$who}: can open delete endpoint",
            barangay_nutrition_portal_admin_can_access_script('deleteNutritionHouseholdSurvey.php') === $expect['delete_script']
        );
    }
}

$barangays = barangay_list_all($con);
assert_true('Barangays seeded', count($barangays) > 0, (string) count($barangays));

$surveyCount = (int) (($con->query('SELECT COUNT(*) AS c FROM nutrition_household_survey')->fetch_assoc()['c'] ?? 0));
$assessmentCount = (int) (($con->query('SELECT COUNT(*) AS c FROM nutrition_assessment')->fetch_assoc()['c'] ?? 0));
if ($surveyCount > 0 && $assessmentCount > 0) {
    assert_true('Household surveys present', true, (string) $surveyCount);
    assert_true('Assessments present', true, (string) $assessmentCount);
} else {
    echo "[WARN] Nutrition data empty — surveys={$surveyCount}, assessments={$assessmentCount} (schema OK; re-import or seed if needed)" . PHP_EOL;
}

echo PHP_EOL . "=== HTTP smoke test ===" . PHP_EOL;

$base = getenv('PORTAL_BASE_URL') ?: 'http://localhost/barangay_default';
$urls = [
    '/login.php',
    '/index.php',
    '/admin/nutritionSuperDashboard.php',
    '/admin/barangayHub.php?picker=1&system=nutrition&view=picker',
    '/admin/nutritionAccountProfile.php',
    '/admin/dashboard.php',
];

foreach ($urls as $path) {
    $url = rtrim($base, '/') . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $ok = $body !== false && $code > 0 && $code < 500;
    $detail = "HTTP {$code}" . ($err !== '' ? " err={$err}" : '');
    if ($body !== false && (stripos($body, 'Failed opening required') !== false || stripos($body, 'Fatal error') !== false)) {
        $ok = false;
        $detail .= ' fatal-in-body';
    }
    assert_true("HTTP {$path}", $ok, $detail);
}

echo PHP_EOL . "Passed: {$passed}  Failed: {$failed}" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
