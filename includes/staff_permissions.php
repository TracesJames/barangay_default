<?php

require_once __DIR__ . '/helpers.php';

if (!defined('STAFF_ROLE_SSA')) {
    /** Super Super Admin — both Barangay Hub and Nutrition Hub */
    define('STAFF_ROLE_SSA', 'ssa');
}
if (!defined('STAFF_ROLE_SUPER_ADMIN')) {
    /** Super Admin (SA) — Barangay Hub only */
    define('STAFF_ROLE_SUPER_ADMIN', 'super_admin');
}
if (!defined('STAFF_ROLE_NUTRITION_SUPER_ADMIN')) {
    /** Super Admin (SA) — Nutrition Hub only */
    define('STAFF_ROLE_NUTRITION_SUPER_ADMIN', 'nutrition_super_admin');
}
if (!defined('STAFF_ROLE_ADMIN')) {
    /** Admin (A) — Barangay Hub */
    define('STAFF_ROLE_ADMIN', 'admin');
}
if (!defined('STAFF_ROLE_BARANGAY_ADMIN')) {
    define('STAFF_ROLE_BARANGAY_ADMIN', 'barangay_admin');
}
if (!defined('STAFF_ROLE_BARANGAY_STAFF')) {
    define('STAFF_ROLE_BARANGAY_STAFF', 'barangay_staff');
}
if (!defined('STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR')) {
    /** BNS — one account per barangay */
    define('STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR', 'barangay_nutrition_scholar');
}
if (!defined('STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN')) {
    /** Admin (A) — Nutrition Hub (city-wide nutrition admin) */
    define('STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN', 'barangay_nutrition_scholar_admin');
}
/** Alias: Nutrition Hub Admin (A) */
if (!defined('STAFF_ROLE_NUTRITION_ADMIN')) {
    define('STAFF_ROLE_NUTRITION_ADMIN', STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN);
}

if (!function_exists('staff_role_column_exists')) {
    function staff_role_column_exists(mysqli $con): bool
    {
        return barangay_column_exists($con, 'users', 'staff_role');
    }
}

if (!function_exists('barangay_user_staff_role')) {
    function barangay_user_staff_role(mysqli $con, string $userId): string
    {
        static $cache = [];
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $role = '';
        if (staff_role_column_exists($con)) {
            $stmt = $con->prepare('SELECT user_type, barangay_id, staff_role FROM users WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $userId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if ($row) {
                    $role = trim((string) ($row['staff_role'] ?? ''));
                    if ($role === '') {
                        $type = strtolower((string) ($row['user_type'] ?? ''));
                        $barangayId = $row['barangay_id'] ?? null;
                        if ($type === 'admin' && ($barangayId === null || $barangayId === '')) {
                            $role = STAFF_ROLE_SSA;
                        } elseif ($type === 'admin') {
                            $role = STAFF_ROLE_BARANGAY_ADMIN;
                        } elseif ($type === 'secretary') {
                            $role = STAFF_ROLE_BARANGAY_STAFF;
                        }
                    }
                }
            }
        } else {
            $type = '';
            $barangayId = barangay_user_barangay_id($con, $userId);
            $stmt = $con->prepare('SELECT user_type FROM users WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $userId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $type = strtolower((string) ($row['user_type'] ?? ''));
            }
            if ($type === 'admin' && $barangayId === null) {
                $role = STAFF_ROLE_SSA;
            } elseif ($type === 'admin') {
                $role = STAFF_ROLE_BARANGAY_ADMIN;
            } elseif ($type === 'secretary') {
                $role = STAFF_ROLE_BARANGAY_STAFF;
            }
        }

        $cache[$userId] = $role;
        return $role;
    }
}

if (!function_exists('staff_role_label')) {
    function staff_role_label(string $role): string
    {
        return match ($role) {
            STAFF_ROLE_SSA => 'Super Super Admin (SSA)',
            STAFF_ROLE_SUPER_ADMIN => 'Super Admin — Barangay Hub (SA)',
            STAFF_ROLE_NUTRITION_SUPER_ADMIN => 'Super Admin — Nutrition Hub (SA)',
            STAFF_ROLE_ADMIN => 'Admin — Barangay Hub (A)',
            STAFF_ROLE_BARANGAY_ADMIN => 'Barangay Admin',
            STAFF_ROLE_BARANGAY_STAFF => 'Barangay Staff',
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR => 'BNS (per Barangay)',
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN => 'Admin — Nutrition Hub (A)',
            default => 'Staff',
        };
    }
}

if (!function_exists('staff_role_user_type')) {
    function staff_role_user_type(string $role): string
    {
        return match ($role) {
            STAFF_ROLE_SSA,
            STAFF_ROLE_SUPER_ADMIN,
            STAFF_ROLE_NUTRITION_SUPER_ADMIN,
            STAFF_ROLE_ADMIN,
            STAFF_ROLE_BARANGAY_ADMIN,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN => 'admin',
            STAFF_ROLE_BARANGAY_STAFF => 'secretary',
            default => '',
        };
    }
}

if (!function_exists('staff_role_hub')) {
    /**
     * Which hub a role belongs to: both | barangay | nutrition | barangay_local
     */
    function staff_role_hub(string $role): string
    {
        return match ($role) {
            STAFF_ROLE_SSA => 'both',
            STAFF_ROLE_SUPER_ADMIN, STAFF_ROLE_ADMIN => 'barangay',
            STAFF_ROLE_NUTRITION_SUPER_ADMIN,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR => 'nutrition',
            STAFF_ROLE_BARANGAY_ADMIN, STAFF_ROLE_BARANGAY_STAFF => 'barangay_local',
            default => '',
        };
    }
}

if (!function_exists('staff_role_requires_barangay')) {
    function staff_role_requires_barangay(string $role): bool
    {
        return in_array($role, [
            STAFF_ROLE_BARANGAY_ADMIN,
            STAFF_ROLE_BARANGAY_STAFF,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
        ], true);
    }
}

if (!function_exists('barangay_user_is_ssa')) {
    /** Super Super Admin — both hubs */
    function barangay_user_is_ssa(mysqli $con, string $userId): bool
    {
        return barangay_user_staff_role($con, $userId) === STAFF_ROLE_SSA;
    }
}

if (!function_exists('barangay_user_is_barangay_hub_super_admin')) {
    /** Super Admin (SA) for Barangay Hub only */
    function barangay_user_is_barangay_hub_super_admin(mysqli $con, string $userId): bool
    {
        return barangay_user_staff_role($con, $userId) === STAFF_ROLE_SUPER_ADMIN;
    }
}

if (!function_exists('barangay_user_is_super_admin')) {
    /**
     * SSA or Barangay Hub Super Admin (legacy “city super admin” checks).
     * Does not include Nutrition Hub Super Admin.
     */
    function barangay_user_is_super_admin(mysqli $con, string $userId): bool
    {
        $role = barangay_user_staff_role($con, $userId);

        return $role === STAFF_ROLE_SSA || $role === STAFF_ROLE_SUPER_ADMIN;
    }
}

if (!function_exists('barangay_user_can_edit_or_delete_person')) {
    /**
     * Only Super Super Admin and Barangay Hub Super Admin may edit or delete resident (person) details.
     */
    function barangay_user_can_edit_or_delete_person(mysqli $con, ?string $userId = null): bool
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');
        if ($userId === '') {
            return false;
        }

        return barangay_user_is_super_admin($con, $userId);
    }
}

if (!function_exists('barangay_user_is_city_admin')) {
    function barangay_user_is_city_admin(mysqli $con, string $userId): bool
    {
        return barangay_user_staff_role($con, $userId) === STAFF_ROLE_ADMIN;
    }
}

if (!function_exists('barangay_user_is_barangay_staff')) {
    function barangay_user_is_barangay_staff(mysqli $con, string $userId): bool
    {
        return barangay_user_staff_role($con, $userId) === STAFF_ROLE_BARANGAY_STAFF;
    }
}

if (!function_exists('barangay_user_is_barangay_nutrition_scholar')) {
    function barangay_user_is_barangay_nutrition_scholar(mysqli $con, string $userId): bool
    {
        return barangay_user_staff_role($con, $userId) === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR;
    }
}

if (!function_exists('barangay_user_is_bns_admin')) {
    function barangay_user_is_bns_admin(mysqli $con, string $userId): bool
    {
        return barangay_user_staff_role($con, $userId) === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN;
    }
}

if (!function_exists('barangay_user_can_pick_barangay')) {
    function barangay_user_can_pick_barangay(mysqli $con, ?string $userId = null): bool
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');
        if ($userId === '') {
            return false;
        }
        $role = barangay_user_staff_role($con, $userId);

        return in_array($role, [
            STAFF_ROLE_SSA,
            STAFF_ROLE_SUPER_ADMIN,
            STAFF_ROLE_NUTRITION_SUPER_ADMIN,
            STAFF_ROLE_ADMIN,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
        ], true);
    }
}

if (!function_exists('barangay_user_can_delete_staff_accounts')) {
    function barangay_user_can_delete_staff_accounts(mysqli $con, ?string $userId = null): bool
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');

        return $userId !== '' && barangay_user_is_ssa($con, $userId);
    }
}

if (!function_exists('barangay_user_can_manage_staff_accounts')) {
    function barangay_user_can_manage_staff_accounts(mysqli $con, ?string $userId = null): bool
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');
        if ($userId === '') {
            return false;
        }

        return barangay_user_is_ssa($con, $userId)
            || barangay_user_is_barangay_hub_super_admin($con, $userId)
            || barangay_user_is_nutrition_portal_admin($con, $userId);
    }
}

if (!function_exists('barangay_user_can_backup')) {
    function barangay_user_can_backup(mysqli $con, ?string $userId = null): bool
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');

        return $userId !== '' && barangay_user_is_ssa($con, $userId);
    }
}

if (!function_exists('barangay_user_can_issue_certificate')) {
    function barangay_user_can_issue_certificate(mysqli $con, ?string $userId = null): bool
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');
        if ($userId === '') {
            return false;
        }

        if (barangay_user_is_city_admin($con, $userId)) {
            return false;
        }

        if (barangay_user_is_barangay_nutrition_scholar($con, $userId)
            || barangay_user_is_bns_admin($con, $userId)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('barangay_city_admin_certificate_scripts')) {
    /**
     * Certificate pages city Admin cannot access.
     *
     * @return array<int, string>
     */
    function barangay_city_admin_certificate_scripts(): array
    {
        return [
            'requestCertificate.php',
            'createCertificateRequest.php',
            'certificateResidentSearch.php',
            'certificateTable.php',
            'certificateRequestStatus.php',
            'requestStatus.php',
            'rejectRequest.php',
            'printRequest.php',
        ];
    }
}

if (!function_exists('barangay_city_admin_denied_scripts')) {
    /**
     * Admin portal pages city Admin and Barangay Staff roles cannot access.
     *
     * @return array<int, string>
     */
    function barangay_city_admin_denied_scripts(): array
    {
        return array_merge(barangay_super_admin_only_scripts(), barangay_city_admin_certificate_scripts(), [
            'staffAccounts.php',
            'staffAccountsTable.php',
            'saveStaffAccount.php',
            'deleteStaffAccount.php',
            'viewStaffAccount.php',
            'resetStaffAccountPassword.php',
        ]);
    }
}

if (!function_exists('barangay_city_admin_allowed_scripts')) {
    /**
     * Pages city Admin may access (after picking a barangay).
     *
     * @return array<int, string>
     */
    function barangay_city_admin_allowed_scripts(): array
    {
        return [
            'dashboard.php',
            'barangayHub.php',
            'selectBarangay.php',
            'myProfile.php',
            'newOfficial.php',
            'addNewOfficial.php',
            'allOfficial.php',
            'viewOfficial.php',
            'viewEndOfficial.php',
            'editOfficial.php',
            'editEndOfficial.php',
            'officialEndTerm.php',
            'newResidence.php',
            'addNewResidence.php',
            'allResidence.php',
            'importResidence.php',
            'importResidenceProcess.php',
            'downloadResidenceImportTemplate.php',
            'archiveResidence.php',
            'familyHouseholdHead.php',
            'familyHouseholdHeadTable.php',
            'setHouseholdHead.php',
            'usersResident.php',
            'userResidenceTable.php',
            'viewResidenceUser.php',
            'editUserResidence.php',
            'report.php',
            'reportTable.php',
            'printReport.php',
            'position.php',
            'blotterRecord.php',
            'settings.php',
        ];
    }
}

if (!function_exists('barangay_city_admin_support_scripts')) {
    /**
     * Ajax/DataTables/action endpoints used by city Admin allowed pages.
     *
     * @return array<int, string>
     */
    function barangay_city_admin_support_scripts(): array
    {
        return [
            'positionTable.php',
            'viewPositionModal.php',
            'addNewPosition.php',
            'editPosition.php',
            'editStatusposition.php',
            'deletePosition.php',
            'allOfficialTable.php',
            'viewOfficialModal.php',
            'editStatusOfficial.php',
            'deleteOfficial.php',
            'unDeleteOfficial.php',
            'allResidenceTable.php',
            'showResidence.php',
            'showResidenceInfo.php',
            'viewResidenceModal.php',
            'editStatusResidence.php',
            'archiveResidenceTable.php',
            'unArchiveResidence.php',
            'endOfficialTable.php',
            'blotterRecordTable.php',
            'blotterPersonTable.php',
            'addNewBlotterRecord.php',
            'editBlotterRecord.php',
            'deleteBlotterRecord.php',
            'deletePersonRecord.php',
            'deleteComplainantRecord.php',
            'viewRecordsModal.php',
            'viewRecordResident.php',
            'showPerson.php',
            'updateSettings.php',
            'saveProfile.php',
            'blotterResidentSearch.php',
            'updateSettings.php',
            'saveProfile.php',
        ];
    }
}

if (!function_exists('barangay_city_admin_can_access_script')) {
    function barangay_city_admin_can_access_script(string $script): bool
    {
        if (in_array($script, barangay_city_admin_denied_scripts(), true)) {
            return false;
        }

        return in_array($script, barangay_city_admin_allowed_scripts(), true)
            || in_array($script, barangay_city_admin_support_scripts(), true);
    }
}

if (!function_exists('barangay_bns_allowed_scripts')) {
    /**
     * Pages Barangay Nutrition Scholar may access (assigned barangay only).
     *
     * @return array<int, string>
     */
    function barangay_bns_allowed_scripts(): array
    {
        return [
            'nutritionDashboard.php',
            'nutritionSuperDashboard.php',
            'nutritionAccountProfile.php',
            'nutritionHouseholdSurvey.php',
            'nutritionHouseholdSurveyForm.php',
            'nutritionHouseholdSurveyFormExcel.php',
            'nutritionBarangaySurvey.php',
            'nutritionBarangaySurveyPrint.php',
            'nutritionPregnantFamiliesReport.php',
            'nutritionPregnantFamiliesPrint.php',
            'nutritionBnpReport.php',
            'nutritionBnpPrint.php',
            'nutritionEoptPrint.php',
            'nutritionReport.php',
            'nutritionPrintReport.php',
            'nutritionProfiles.php',
            'nutritionAssess.php',
            'myProfile.php',
        ];
    }
}

if (!function_exists('barangay_bns_support_scripts')) {
    /**
     * Ajax/DataTables/action endpoints used by BNS nutrition pages.
     *
     * @return array<int, string>
     */
    function barangay_bns_support_scripts(): array
    {
        return [
            'saveProfile.php',
            'nutritionProfilesTable.php',
            'nutritionResidentSearch.php',
            'nutritionHouseholdResidentSearch.php',
            'nutritionHouseholdResidentPrefill.php',
            'nutritionFamilyMemberGrowth.php',
            'saveNutritionAssessment.php',
            'saveNutritionHouseholdSurvey.php',
            'saveNutritionBarangaySurvey.php',
            'nutritionNextHouseholdId.php',
            'updateNutritionHouseholdSurveyNames.php',
        ];
    }
}

if (!function_exists('barangay_bns_can_access_script')) {
    function barangay_bns_can_access_script(string $script): bool
    {
        return in_array($script, barangay_bns_allowed_scripts(), true)
            || in_array($script, barangay_bns_support_scripts(), true);
    }
}

if (!function_exists('barangay_bns_admin_allowed_scripts')) {
    /**
     * Pages BNS Admin may access (any barangay via hub picker).
     *
     * @return array<int, string>
     */
    function barangay_bns_admin_allowed_scripts(): array
    {
        return array_values(array_unique(array_merge(barangay_bns_allowed_scripts(), [
            'barangayHub.php',
            'selectBarangay.php',
            'nutritionSuperDashboard.php',
            'nutritionSettings.php',
            'nutritionSuperPrintReport.php',
            'nutritionHubGuidePrint.php',
            'nutritionSuperPregnantFamiliesPrint.php',
            'nutritionMellpiCityProfile.php',
            'saveNutritionMellpiCityProfile.php',
        ])));
    }
}

if (!function_exists('barangay_bns_admin_support_scripts')) {
    /**
     * Ajax/action endpoints used by BNS Admin nutrition pages.
     *
     * @return array<int, string>
     */
    function barangay_bns_admin_support_scripts(): array
    {
        return array_values(array_unique(array_merge(barangay_bns_support_scripts(), [
            'saveNutritionSettings.php',
            'deleteNutritionHouseholdSurvey.php',
        ])));
    }
}

if (!function_exists('barangay_bns_admin_can_access_script')) {
    function barangay_bns_admin_can_access_script(string $script): bool
    {
        return in_array($script, barangay_bns_admin_allowed_scripts(), true)
            || in_array($script, barangay_bns_admin_support_scripts(), true);
    }
}

if (!function_exists('barangay_user_is_nutrition_portal_admin')) {
    /**
     * Nutrition Hub Super Admin (SA) — city-wide nutrition only.
     */
    function barangay_user_is_nutrition_portal_admin(mysqli $con, string $userId): bool
    {
        static $cache = [];

        if ($userId === '') {
            return false;
        }
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $role = barangay_user_staff_role($con, $userId);
        if ($role === STAFF_ROLE_NUTRITION_SUPER_ADMIN) {
            $cache[$userId] = true;

            return true;
        }

        // Legacy: nutrition.* username still treated as Nutrition Hub SA until migrated.
        if ($role !== STAFF_ROLE_SUPER_ADMIN && $role !== STAFF_ROLE_SSA) {
            $cache[$userId] = false;

            return false;
        }

        $stmt = $con->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            $cache[$userId] = false;

            return false;
        }

        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $username = strtolower(trim((string) ($row['username'] ?? '')));
        $cache[$userId] = $username === 'nutrition.superadmin' || str_starts_with($username, 'nutrition.');

        return $cache[$userId];
    }
}

if (!function_exists('barangay_barangay_hub_sa_denied_scripts')) {
    /**
     * Nutrition Hub pages Barangay Hub Super Admin cannot open.
     *
     * @return array<int, string>
     */
    function barangay_barangay_hub_sa_denied_scripts(): array
    {
        return [
            'nutritionDashboard.php',
            'nutritionSuperDashboard.php',
            'nutritionAccountProfile.php',
            'nutritionHouseholdSurvey.php',
            'nutritionHouseholdSurveyForm.php',
            'nutritionHouseholdSurveyFormExcel.php',
            'nutritionBarangaySurvey.php',
            'nutritionBarangaySurveyPrint.php',
            'nutritionPregnantFamiliesReport.php',
            'nutritionPregnantFamiliesPrint.php',
            'nutritionSuperPregnantFamiliesPrint.php',
            'nutritionBnpReport.php',
            'nutritionBnpPrint.php',
            'nutritionEoptPrint.php',
            'nutritionReport.php',
            'nutritionPrintReport.php',
            'nutritionSettings.php',
            'nutritionProfiles.php',
            'nutritionAssess.php',
            'nutritionSuperPrintReport.php',
            'nutritionHubGuidePrint.php',
            'nutritionMellpiCityProfile.php',
            'saveNutritionMellpiCityProfile.php',
            'nutritionProfilesTable.php',
            'nutritionResidentSearch.php',
            'nutritionHouseholdResidentSearch.php',
            'nutritionHouseholdResidentPrefill.php',
            'nutritionFamilyMemberGrowth.php',
            'saveNutritionAssessment.php',
            'saveNutritionHouseholdSurvey.php',
            'saveNutritionBarangaySurvey.php',
            'saveNutritionSettings.php',
            'nutritionNextHouseholdId.php',
            'updateNutritionHouseholdSurveyNames.php',
            'deleteNutritionHouseholdSurvey.php',
        ];
    }
}

if (!function_exists('barangay_nutrition_portal_admin_allowed_scripts')) {
    /**
     * Pages Nutrition Super Admin may open (Nutrition Hub only).
     *
     * @return array<int, string>
     */
    function barangay_nutrition_portal_admin_allowed_scripts(): array
    {
        return array_values(array_unique(array_merge(barangay_bns_admin_allowed_scripts(), [
            'staffAccounts.php',
            'saveStaffAccount.php',
            'deleteStaffAccount.php',
            'staffAccountsTable.php',
            'viewStaffAccount.php',
            'resetStaffAccountPassword.php',
            'nutritionAccountProfile.php',
            'saveProfile.php',
        ])));
    }
}

if (!function_exists('barangay_nutrition_portal_admin_can_access_script')) {
    function barangay_nutrition_portal_admin_can_access_script(string $script): bool
    {
        return in_array($script, barangay_nutrition_portal_admin_allowed_scripts(), true)
            || in_array($script, barangay_bns_admin_support_scripts(), true);
    }
}

if (!function_exists('barangay_user_is_nutrition_scoped_account')) {
    /**
     * Accounts that belong to the Nutrition Portal (not Barangay Portal).
     */
    function barangay_user_is_nutrition_scoped_account(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }

        return barangay_user_is_nutrition_portal_admin($con, $userId)
            || barangay_user_is_bns_admin($con, $userId)
            || barangay_user_is_barangay_nutrition_scholar($con, $userId);
    }
}

if (!function_exists('barangay_portal_brand_name')) {
    /**
     * Visible portal product name for the logged-in account / current context.
     */
    function barangay_portal_brand_name(mysqli $con, ?string $userId = null, bool $preferNutrition = false): string
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');
        if ($preferNutrition || ($userId !== '' && barangay_user_is_nutrition_scoped_account($con, $userId))) {
            return 'Nutrition Portal';
        }

        return 'City of Valencia Portal';
    }
}

if (!function_exists('barangay_portal_brand_tagline')) {
    function barangay_portal_brand_tagline(mysqli $con, ?string $userId = null, bool $preferNutrition = false): string
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');
        if ($preferNutrition || ($userId !== '' && barangay_user_is_nutrition_scoped_account($con, $userId))) {
            return 'Valencia City · Nutrition Profiling';
        }

        return 'City of Valencia · Barangay Management';
    }
}

if (!function_exists('barangay_enforce_staff_portal_access')) {
    function barangay_enforce_staff_portal_access(mysqli $con): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type'] !== 'secretary') {
            return;
        }

        $userId = (string) $_SESSION['user_id'];
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $denied = [
            'backupRestore.php', 'backup.php', 'backupTable.php', 'restore.php', 'deleteFile.php',
            'staffAccounts.php', 'deleteStaffAccount.php', 'systemLog.php', 'systemLogsTable.php',
        ];

        if (in_array($script, $denied, true)) {
            header('Location: dashboard.php');
            exit;
        }

        if (!barangay_user_can_delete_staff_accounts($con, $userId) && in_array($script, ['deleteStaffAccount.php', 'deleteUserAdministrator.php'], true)) {
            header('Location: dashboard.php');
            exit;
        }

        $barangayId = barangay_user_barangay_id($con, $userId);
        if ($barangayId !== null) {
            barangay_set_active($barangayId);
        }
    }
}
