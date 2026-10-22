<?php

require_once __DIR__ . '/barangay_context.php';

if (!defined('NUTRITION_VALENCIA_CITY_PSGC_CODE')) {
    define('NUTRITION_VALENCIA_CITY_PSGC_CODE', '1001321000');
}

if (!defined('NUTRITION_VALENCIA_PSGC_CODE')) {
    define('NUTRITION_VALENCIA_PSGC_CODE', NUTRITION_VALENCIA_CITY_PSGC_CODE);
}

/** Nutrition module child age range (years). Barangay hub stays at 0–17. */
if (!defined('NUTRITION_CHILD_MAX_AGE_YEARS')) {
    define('NUTRITION_CHILD_MAX_AGE_YEARS', 19);
}

if (!function_exists('nutrition_child_max_age_years')) {
    function nutrition_child_max_age_years(): int
    {
        return (int) NUTRITION_CHILD_MAX_AGE_YEARS;
    }
}

if (!function_exists('nutrition_children_age_label')) {
    function nutrition_children_age_label(): string
    {
        return 'Children (0–' . nutrition_child_max_age_years() . ')';
    }
}

if (!function_exists('nutrition_children_age_condition')) {
    /**
     * Age filter for nutrition children counts/searches (0 to max years inclusive).
     * Independent from barangay hub's 0–17 filter.
     */
    function nutrition_children_age_condition(string $infoAlias = 'residence_information'): string
    {
        $maxAge = nutrition_child_max_age_years();
        $birth = $infoAlias . '.birth_date';
        $age = $infoAlias . '.age';

        return "(
            ($birth IS NOT NULL AND $birth != '' AND TIMESTAMPDIFF(YEAR, $birth, CURDATE()) <= {$maxAge})
            OR (($birth IS NULL OR $birth = '') AND $age != '' AND CAST($age AS UNSIGNED) <= {$maxAge})
        )";
    }
}

if (!function_exists('nutrition_user_is_portal_admin')) {
    function nutrition_user_is_portal_admin(mysqli $con, string $userId): bool
    {
        return barangay_user_is_nutrition_portal_admin($con, $userId);
    }
}

if (!function_exists('nutrition_user_can_view_registered_households')) {
    /**
     * Registered Households page (nutritionAssess.php):
     * SSA, Nutrition Hub SA, Nutrition Admin (A), CNPC.
     */
    function nutrition_user_can_view_registered_households(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }

        return barangay_user_is_ssa($con, $userId)
            || barangay_user_is_nutrition_portal_admin($con, $userId)
            || barangay_user_is_bns_admin($con, $userId)
            || barangay_user_is_cnpc($con, $userId);
    }
}

if (!function_exists('nutrition_user_can_manage_household_surveys')) {
    /**
     * Legacy alias for delete capability (SSA / Nutrition SA only).
     * Prefer nutrition_user_can_delete_household_surveys / edit / add helpers.
     */
    function nutrition_user_can_manage_household_surveys(mysqli $con, string $userId): bool
    {
        return nutrition_user_can_delete_household_surveys($con, $userId);
    }
}

if (!function_exists('nutrition_user_can_add_household_surveys')) {
    /**
     * Add / encode new household surveys.
     * SSA, Nutrition SA, Nutrition Admin (A), BNS — not CNPC (edit/delete only).
     */
    function nutrition_user_can_add_household_surveys(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }

        if (barangay_user_is_ssa($con, $userId)
            || barangay_user_is_nutrition_portal_admin($con, $userId)
            || barangay_user_is_bns_admin($con, $userId)
            || barangay_user_is_barangay_nutrition_scholar($con, $userId)) {
            return true;
        }

        // Legacy nutrition.* city accounts without explicit role.
        $stmt = $con->prepare('SELECT username, user_type, barangay_id, staff_role FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return false;
        }
        $username = strtolower(trim((string) ($row['username'] ?? '')));
        $userType = strtolower(trim((string) ($row['user_type'] ?? '')));
        $barangayId = trim((string) ($row['barangay_id'] ?? ''));
        $role = trim((string) ($row['staff_role'] ?? ''));
        if ($role === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) {
            return false;
        }

        return ($username === 'nutrition.superadmin' || str_starts_with($username, 'nutrition.'))
            && $userType === 'admin'
            && $barangayId === '';
    }
}

if (!function_exists('nutrition_user_can_edit_household_survey_names')) {
    /**
     * Edit registered household / member names on Consolidated Report.
     * Same mutate set as full edit/delete (SSA / Nutrition SA / Nutrition Admin / CNPC).
     */
    function nutrition_user_can_edit_household_survey_names(mysqli $con, string $userId): bool
    {
        return nutrition_user_can_edit_household_surveys($con, $userId);
    }
}

if (!function_exists('nutrition_user_can_edit_household_surveys')) {
    /**
     * Full edit of a registered household survey (reopen form / UPDATE).
     * SSA, Nutrition SA, Nutrition Admin (A), CNPC — not BNS.
     */
    function nutrition_user_can_edit_household_surveys(mysqli $con, string $userId): bool
    {
        return nutrition_user_can_delete_household_surveys($con, $userId);
    }
}

if (!function_exists('nutrition_user_can_save_settings')) {
    /** SSA and Nutrition Super Admin may change nutrition settings. */
    function nutrition_user_can_save_settings(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }

        return barangay_user_is_ssa($con, $userId)
            || barangay_user_is_nutrition_portal_admin($con, $userId);
    }
}

if (!function_exists('nutrition_user_can_delete_household_surveys')) {
    /**
     * Delete household surveys / registered names.
     * SSA, Nutrition SA, Nutrition Admin (A), CNPC — not BNS.
     */
    function nutrition_user_can_delete_household_surveys(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }

        if (barangay_user_is_ssa($con, $userId)
            || barangay_user_is_nutrition_portal_admin($con, $userId)
            || barangay_user_is_bns_admin($con, $userId)
            || barangay_user_is_cnpc($con, $userId)) {
            return true;
        }

        $stmt = $con->prepare('SELECT username, user_type, barangay_id, staff_role FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return false;
        }
        $role = trim((string) ($row['staff_role'] ?? ''));
        if ($role === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR) {
            return false;
        }
        $username = strtolower(trim((string) ($row['username'] ?? '')));
        $userType = strtolower(trim((string) ($row['user_type'] ?? '')));
        $barangayId = trim((string) ($row['barangay_id'] ?? ''));

        return ($username === 'nutrition.superadmin' || str_starts_with($username, 'nutrition.'))
            && $userType === 'admin'
            && $barangayId === '';
    }
}

if (!function_exists('nutrition_admin_login_token')) {
    function nutrition_admin_login_token(mysqli $con, string $userId): ?string
    {
        if (!nutrition_user_is_portal_admin($con, $userId)) {
            return null;
        }

        barangay_clear_active();
        $barangays = barangay_list_all($con);
        if (count($barangays) === 1) {
            barangay_set_active((string) $barangays[0]['id']);

            return 'nutrition_dashboard';
        }

        return 'nutrition_admin';
    }
}

if (!function_exists('nutrition_admin_login_url')) {
    function nutrition_admin_login_url(string $token): string
    {
        return match ($token) {
            'nutrition_dashboard' => 'admin/nutritionDashboard.php',
            'nutrition_admin' => 'admin/nutritionSuperDashboard.php',
            default => 'admin/nutritionSuperDashboard.php',
        };
    }
}

if (!function_exists('nutrition_admin_redirect_if_needed')) {
    function nutrition_admin_redirect_if_needed(mysqli $con, string $userId): void
    {
        $token = nutrition_admin_login_token($con, $userId);
        if ($token === null) {
            return;
        }

        header('Location: ' . nutrition_admin_login_url($token));
        exit;
    }
}

if (!function_exists('nutrition_ensure_table')) {
    function nutrition_ensure_table(mysqli $con): bool
    {
        if (nutrition_table_exists($con)) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `nutrition_assessment` (
            `assessment_id` VARCHAR(32) NOT NULL,
            `residence_id` VARCHAR(32) NOT NULL,
            `barangay_id` VARCHAR(32) NOT NULL,
            `assessment_date` DATE NOT NULL,
            `weight_kg` DECIMAL(5,2) NOT NULL,
            `height_cm` DECIMAL(5,2) NOT NULL,
            `bmi` DECIMAL(5,2) DEFAULT NULL,
            `muac_cm` DECIMAL(4,1) DEFAULT NULL,
            `nutritional_status` VARCHAR(32) NOT NULL DEFAULT 'normal',
            `remarks` TEXT DEFAULT NULL,
            `assessed_by` VARCHAR(32) DEFAULT NULL,
            `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`assessment_id`),
            KEY `idx_nutrition_barangay` (`barangay_id`),
            KEY `idx_nutrition_residence` (`residence_id`),
            KEY `idx_nutrition_date` (`assessment_date`),
            KEY `idx_nutrition_status` (`nutritional_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        return (bool) $con->query($sql);
    }
}

if (!function_exists('nutrition_table_exists')) {
    function nutrition_table_exists(mysqli $con): bool
    {
        return barangay_table_exists($con, 'nutrition_assessment');
    }
}

if (!function_exists('nutrition_status_options')) {
    /**
     * @return array<string, string>
     */
    function nutrition_status_options(): array
    {
        return [
            'normal' => 'Normal',
            'underweight' => 'Underweight',
            'wasted' => 'Wasted',
            'severely_wasted' => 'Severely Wasted',
            'stunted' => 'Stunted',
            'overweight' => 'Overweight',
            'obese' => 'Obese',
        ];
    }
}

if (!function_exists('nutrition_status_label')) {
    function nutrition_status_label(string $status): string
    {
        return nutrition_status_options()[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

if (!function_exists('nutrition_status_badge_class')) {
    function nutrition_status_badge_class(string $status): string
    {
        return match ($status) {
            'normal' => 'badge-success',
            'underweight', 'wasted' => 'badge-warning',
            'severely_wasted', 'stunted' => 'badge-danger',
            'overweight' => 'badge-info',
            'obese' => 'badge-dark',
            default => 'badge-secondary',
        };
    }
}

if (!function_exists('nutrition_calculate_bmi')) {
    function nutrition_calculate_bmi(float $weightKg, float $heightCm): ?float
    {
        if ($weightKg <= 0 || $heightCm <= 0) {
            return null;
        }
        $heightM = $heightCm / 100;

        return round($weightKg / ($heightM * $heightM), 2);
    }
}

if (!function_exists('nutrition_suggest_status')) {
    function nutrition_suggest_status(?float $bmi, int $ageYears): string
    {
        if ($bmi === null || $bmi <= 0) {
            return 'normal';
        }

        if ($ageYears < 5) {
            if ($bmi < 14) {
                return 'severely_wasted';
            }
            if ($bmi < 16) {
                return 'wasted';
            }
            if ($bmi < 18.5) {
                return 'underweight';
            }
            if ($bmi >= 25) {
                return 'overweight';
            }

            return 'normal';
        }

        if ($bmi < 18.5) {
            return 'underweight';
        }
        if ($bmi < 25) {
            return 'normal';
        }
        if ($bmi < 30) {
            return 'overweight';
        }

        return 'obese';
    }
}

if (!function_exists('nutrition_normalize_date_to_ymd')) {
    /**
     * Accept Y-m-d or Month/Day/YYYY (M/D/YYYY) and return Y-m-d for DB/API use.
     */
    function nutrition_normalize_date_to_ymd(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            $d = (int) $m[3];
            if (!checkdate($mo, $d, $y)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
            $mo = (int) $m[1];
            $d = (int) $m[2];
            $y = (int) $m[3];
            if (!checkdate($mo, $d, $y)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }

        try {
            $dt = new DateTime($value);

            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('nutrition_format_date_mdy')) {
    /** Format a date value as Month/Day/YYYY for form display. */
    function nutrition_format_date_mdy(?string $value): string
    {
        $ymd = nutrition_normalize_date_to_ymd($value);
        if ($ymd === null) {
            return '';
        }

        [$y, $mo, $d] = array_map('intval', explode('-', $ymd));

        return sprintf('%02d/%02d/%04d', $mo, $d, $y);
    }
}

if (!function_exists('nutrition_age_in_months')) {
    function nutrition_age_in_months(?string $birthDate, ?string $referenceDate = null): ?int
    {
        $birthYmd = nutrition_normalize_date_to_ymd($birthDate);
        if ($birthYmd === null) {
            return null;
        }

        $referenceYmd = nutrition_normalize_date_to_ymd($referenceDate ?? date('Y-m-d'));
        if ($referenceYmd === null) {
            $referenceYmd = date('Y-m-d');
        }

        try {
            $birth = new DateTime($birthYmd);
            $reference = new DateTime($referenceYmd);
        } catch (Exception $e) {
            return null;
        }

        if ($birth > $reference) {
            return null;
        }

        $diff = $birth->diff($reference);

        return max(0, ($diff->y * 12) + $diff->m + (int) floor($diff->d / 30));
    }
}

if (!function_exists('nutrition_format_age_label')) {
    /**
     * Human age label with years, months, and days.
     * Example: 1y 1m 5d (13 months)
     */
    function nutrition_format_age_label(?string $birthDate, ?string $referenceDate = null): ?string
    {
        $birthYmd = nutrition_normalize_date_to_ymd($birthDate);
        if ($birthYmd === null) {
            return null;
        }

        $referenceYmd = nutrition_normalize_date_to_ymd($referenceDate ?? date('Y-m-d'));
        if ($referenceYmd === null) {
            $referenceYmd = date('Y-m-d');
        }

        try {
            $birth = new DateTime($birthYmd);
            $reference = new DateTime($referenceYmd);
        } catch (Exception $e) {
            return null;
        }

        if ($birth > $reference) {
            return null;
        }

        $diff = $birth->diff($reference);
        $years = (int) $diff->y;
        $months = (int) $diff->m;
        $days = (int) $diff->d;
        $ageMonths = nutrition_age_in_months($birthYmd, $referenceYmd);
        if ($ageMonths === null) {
            $ageMonths = ($years * 12) + $months;
        }

        return $years . 'y ' . $months . 'm ' . $days . 'd (' . $ageMonths . ' months)';
    }
}

if (!function_exists('nutrition_growth_interpolate')) {
    /**
     * @param array<int, array{0:float|int,1:float|int}> $points
     */
    function nutrition_growth_interpolate(float $x, array $points): ?float
    {
        if ($points === []) {
            return null;
        }

        if ($x <= (float) $points[0][0]) {
            return (float) $points[0][1];
        }

        $lastIndex = count($points) - 1;
        if ($x >= (float) $points[$lastIndex][0]) {
            return (float) $points[$lastIndex][1];
        }

        for ($i = 0; $i < $lastIndex; $i++) {
            $x0 = (float) $points[$i][0];
            $x1 = (float) $points[$i + 1][0];
            if ($x < $x0 || $x > $x1) {
                continue;
            }

            $y0 = (float) $points[$i][1];
            $y1 = (float) $points[$i + 1][1];
            if ($x1 === $x0) {
                return $y0;
            }

            return $y0 + (($x - $x0) / ($x1 - $x0)) * ($y1 - $y0);
        }

        return null;
    }
}

if (!function_exists('nutrition_growth_z_score')) {
    function nutrition_growth_z_score(float $value, ?float $median, float $sd): ?float
    {
        if ($median === null || $sd <= 0) {
            return null;
        }

        return ($value - $median) / $sd;
    }
}

if (!function_exists('nutrition_growth_wfa_reference')) {
    /**
     * @return array<int, array{0:int,1:float}>
     */
    function nutrition_growth_wfa_reference(string $gender): array
    {
        if ($gender === 'Female') {
            return [
                [0, 3.2], [1, 4.2], [2, 5.1], [3, 5.8], [4, 6.4], [5, 6.9], [6, 7.3], [9, 8.2], [12, 8.9],
                [15, 9.6], [18, 10.2], [24, 11.5], [36, 13.9], [48, 15.9], [60, 18.0], [72, 20.0], [84, 22.4],
                [96, 25.0], [108, 28.1], [120, 31.9], [132, 36.3], [144, 41.1], [156, 45.8], [168, 49.4],
                [180, 51.8], [192, 53.2], [204, 54.2], [216, 54.8], [228, 55.2],
            ];
        }

        return [
            [0, 3.3], [1, 4.5], [2, 5.6], [3, 6.4], [4, 7.0], [5, 7.5], [6, 7.9], [9, 8.9], [12, 9.6],
            [15, 10.3], [18, 10.9], [24, 12.2], [36, 14.3], [48, 16.3], [60, 18.3], [72, 20.5], [84, 22.9],
            [96, 25.6], [108, 28.6], [120, 32.0], [132, 36.1], [144, 40.5], [156, 45.0], [168, 49.1],
            [180, 52.4], [192, 54.4], [204, 55.8], [216, 56.8], [228, 57.5],
        ];
    }
}

if (!function_exists('nutrition_growth_hfa_reference')) {
    /**
     * @return array<int, array{0:int,1:float}>
     */
    function nutrition_growth_hfa_reference(string $gender): array
    {
        if ($gender === 'Female') {
            return [
                [0, 49.1], [1, 53.7], [2, 57.1], [3, 59.8], [4, 62.1], [5, 64.0], [6, 65.7], [9, 69.0],
                [12, 74.0], [15, 77.5], [18, 80.7], [24, 85.7], [36, 95.1], [48, 102.7], [60, 109.0],
                [72, 115.5], [84, 121.5], [96, 127.5], [108, 133.0], [120, 138.0], [132, 143.5], [144, 149.5],
                [156, 156.0], [168, 161.5], [180, 165.5], [192, 168.0], [204, 169.5], [216, 170.5], [228, 171.0],
            ];
        }

        return [
            [0, 49.9], [1, 54.7], [2, 58.4], [3, 61.4], [4, 64.0], [5, 66.2], [6, 67.6], [9, 71.0], [12, 75.7],
            [15, 79.1], [18, 82.3], [24, 87.1], [36, 96.1], [48, 103.3], [60, 109.4], [72, 116.0], [84, 122.0],
            [96, 128.0], [108, 133.5], [120, 138.5], [132, 144.0], [144, 150.0], [156, 157.0], [168, 163.5],
            [180, 168.5], [192, 172.0], [204, 174.0], [216, 175.5], [228, 176.5],
        ];
    }
}

if (!function_exists('nutrition_growth_wfh_reference')) {
    /**
     * @return array<int, array{0:int,1:float}>
     */
    function nutrition_growth_wfh_reference(string $gender): array
    {
        if ($gender === 'Female') {
            return [
                [65, 7.1], [70, 8.0], [75, 8.8], [80, 9.6], [85, 10.4], [90, 11.2], [95, 12.0], [100, 12.9],
                [105, 13.8], [110, 14.9], [115, 16.1], [120, 17.4], [125, 18.8], [130, 20.4], [135, 22.2],
                [140, 24.5], [145, 27.2], [150, 30.5], [155, 34.5], [160, 39.0], [165, 44.0], [170, 49.0],
                [175, 54.0],
            ];
        }

        return [
            [65, 7.4], [70, 8.4], [75, 9.2], [80, 10.1], [85, 10.9], [90, 11.8], [95, 12.7], [100, 13.7],
            [105, 14.8], [110, 16.0], [115, 17.3], [120, 18.8], [125, 20.3], [130, 22.0], [135, 24.0],
            [140, 26.5], [145, 29.5], [150, 33.0], [155, 37.0], [160, 41.5], [165, 46.5], [170, 51.5],
            [175, 56.5],
        ];
    }
}

if (!function_exists('nutrition_classify_weight_for_age')) {
    function nutrition_classify_weight_for_age(?float $z): string
    {
        if ($z === null) {
            return '';
        }
        if ($z < -3) {
            return 'SUW';
        }
        if ($z < -2) {
            return 'UW';
        }
        if ($z > 3) {
            return 'OB';
        }
        if ($z > 2) {
            return 'OW';
        }

        return 'Normal';
    }
}

if (!function_exists('nutrition_classify_height_for_age')) {
    function nutrition_classify_height_for_age(?float $z): string
    {
        if ($z === null) {
            return '';
        }
        if ($z < -3) {
            return 'Severely Stunted';
        }
        if ($z < -2) {
            return 'Stunted';
        }
        if ($z > 2) {
            return 'Tall';
        }

        return 'Normal';
    }
}

if (!function_exists('nutrition_classify_weight_for_height')) {
    function nutrition_classify_weight_for_height(?float $z): string
    {
        if ($z === null) {
            return '';
        }
        if ($z < -3) {
            return 'Sev Wasted';
        }
        if ($z < -2) {
            return 'Wasted';
        }
        if ($z > 3) {
            return 'OB';
        }
        if ($z > 2) {
            return 'OW';
        }

        return 'Normal';
    }
}

if (!function_exists('nutrition_is_child_0_to_5')) {
    /** Preschool OPT age: 0–60 months (0 to 5 years). */
    function nutrition_is_child_0_to_5(?int $ageMonths): bool
    {
        return $ageMonths !== null && $ageMonths >= 0 && $ageMonths <= 60;
    }
}

if (!function_exists('nutrition_family_member_growth_assessment')) {
    /**
     * Growth status via Region 10 eOPT Plus LMS (Female/Male PDFs).
     *
     * @return array{
     *   age_months:?int,
     *   is_child_0_to_5:bool,
     *   expected_weight_kg:?float,
     *   expected_height_cm:?float,
     *   weight_for_age:string,
     *   height_for_age:string,
     *   weight_for_height:string
     * }
     */
    function nutrition_family_member_growth_assessment(
        string $gender,
        ?string $birthDate,
        float $weightKg,
        float $heightCm,
        ?string $referenceDate = null
    ): array {
        require_once __DIR__ . '/nutrition_eopt_lms.php';

        $eopt = nutrition_eopt_growth_assessment($gender, $birthDate, $weightKg, $heightCm, $referenceDate);

        return [
            'age_months' => $eopt['age_months'],
            'is_child_0_to_5' => (bool) $eopt['is_child_0_to_5'],
            'expected_weight_kg' => $eopt['expected_weight_kg'],
            'expected_height_cm' => $eopt['expected_height_cm'],
            'weight_for_age' => (string) $eopt['weight_for_age'],
            'height_for_age' => (string) $eopt['height_for_age'],
            'weight_for_height' => (string) $eopt['weight_for_height'],
        ];
    }
}

if (!function_exists('nutrition_growth_result_badge_class')) {
    function nutrition_growth_result_badge_class(string $result): string
    {
        $result = strtolower($result);
        if (in_array($result, ['suw', 'severely stunted', 'sev wasted'], true)) {
            return 'badge-danger';
        }
        if (in_array($result, ['uw', 'stunted', 'wasted'], true)) {
            return 'badge-warning';
        }
        if (in_array($result, ['ow', 'ob'], true)) {
            return 'badge-info';
        }
        if ($result === 'tall') {
            return 'badge-primary';
        }
        if ($result === 'normal') {
            return 'badge-success';
        }

        return 'badge-secondary';
    }
}

if (!function_exists('nutrition_member_age_years')) {
    function nutrition_member_age_years(?int $ageMonths, ?string $birthDate, ?string $referenceDate = null): ?int
    {
        $birthYmd = nutrition_normalize_date_to_ymd($birthDate);
        if ($birthYmd !== null) {
            $referenceYmd = nutrition_normalize_date_to_ymd($referenceDate ?? date('Y-m-d')) ?? date('Y-m-d');
            try {
                $birth = new DateTime($birthYmd);
                $reference = new DateTime($referenceYmd);
            } catch (Exception $e) {
                return null;
            }
            if ($birth > $reference) {
                return null;
            }

            return $birth->diff($reference)->y;
        }

        if ($ageMonths !== null && $ageMonths >= 0) {
            return intdiv($ageMonths, 12);
        }

        return null;
    }
}

if (!function_exists('nutrition_member_is_child_0_to_19')) {
    function nutrition_member_is_child_0_to_19(?int $ageMonths, ?string $birthDate, ?string $referenceDate = null): bool
    {
        $ageYears = nutrition_member_age_years($ageMonths, $birthDate, $referenceDate);

        return $ageYears !== null && $ageYears <= nutrition_child_max_age_years();
    }
}

if (!function_exists('nutrition_survey_growth_label_to_status')) {
    function nutrition_survey_growth_label_to_status(string $label): ?string
    {
        $label = strtolower(trim($label));
        if ($label === '' || $label === '—' || $label === '-') {
            return null;
        }

        return match (true) {
            in_array($label, ['normal', 'n'], true) => 'normal',
            in_array($label, ['underweight', 'uw', 'muw'], true) => 'underweight',
            in_array($label, ['severely underweight', 'suw'], true) => 'underweight',
            in_array($label, ['wasted', 'mw'], true) => 'wasted',
            in_array($label, ['severely wasted', 'sev wasted', 'sw'], true) => 'severely_wasted',
            in_array($label, ['stunted', 'st', 'mst'], true) => 'stunted',
            in_array($label, ['severely stunted', 'sst'], true) => 'stunted',
            in_array($label, ['overweight', 'ow'], true) => 'overweight',
            in_array($label, ['obese', 'ob'], true) => 'obese',
            default => null,
        };
    }
}

if (!function_exists('nutrition_survey_member_is_assessed')) {
    function nutrition_survey_member_is_assessed(array $member): bool
    {
        $weight = $member['weight_kg'] ?? null;
        $height = $member['height_cm'] ?? null;
        $muac = $member['muac_cm'] ?? null;

        if ($weight !== null && $weight !== '' && (float) $weight > 0) {
            return true;
        }
        if ($height !== null && $height !== '' && (float) $height > 0) {
            return true;
        }
        if ($muac !== null && $muac !== '' && (float) $muac > 0) {
            return true;
        }

        foreach (['weight_for_age', 'height_for_age', 'weight_for_height', 'muac_status'] as $field) {
            if (trim((string) ($member[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('nutrition_survey_member_dashboard_status')) {
    /**
     * Single primary dashboard status for a survey member (for badges / recent activity).
     * Prefers the most severe non-normal indicator when several apply.
     */
    function nutrition_survey_member_dashboard_status(array $member): ?string
    {
        $statuses = nutrition_survey_member_dashboard_statuses($member);
        if ($statuses === []) {
            return null;
        }

        $priority = [
            'severely_wasted',
            'wasted',
            'underweight',
            'stunted',
            'obese',
            'overweight',
            'normal',
        ];
        foreach ($priority as $key) {
            if (in_array($key, $statuses, true)) {
                return $key;
            }
        }

        return $statuses[0];
    }
}

if (!function_exists('nutrition_survey_member_dashboard_statuses')) {
    /**
     * All distinct nutritional statuses from WFA / HFA / WFH for breakdown chips.
     * A child who is both underweight and stunted is counted in both buckets.
     * Normal is returned only when every present indicator is normal.
     *
     * @return list<string>
     */
    function nutrition_survey_member_dashboard_statuses(array $member): array
    {
        $found = [];
        foreach (['weight_for_age', 'height_for_age', 'weight_for_height'] as $field) {
            $status = nutrition_survey_growth_label_to_status((string) ($member[$field] ?? ''));
            if ($status !== null) {
                $found[$status] = true;
            }
        }

        if ($found === []) {
            return [];
        }

        if (count($found) === 1 && isset($found['normal'])) {
            return ['normal'];
        }

        unset($found['normal']);

        return array_keys($found);
    }
}

if (!function_exists('nutrition_survey_children_totals')) {
    /**
     * Dashboard totals for children recorded in household surveys.
     *
     * Status chips count each growth indicator separately (a child may appear in
     * more than one non-normal bucket). `at_risk` counts unique children with any
     * non-normal indicator.
     *
     * @return array{
     *   children:int,
     *   assessed:int,
     *   pending:int,
     *   normal:int,
     *   underweight:int,
     *   wasted:int,
     *   severely_wasted:int,
     *   stunted:int,
     *   overweight:int,
     *   obese:int,
     *   this_month:int,
     *   at_risk:int
     * }
     */
    function nutrition_survey_children_totals(mysqli $con, ?string $barangayId = null): array
    {
        $totals = [
            'children' => 0,
            'assessed' => 0,
            'pending' => 0,
            'normal' => 0,
            'underweight' => 0,
            'wasted' => 0,
            'severely_wasted' => 0,
            'stunted' => 0,
            'overweight' => 0,
            'obese' => 0,
            'this_month' => 0,
            'at_risk' => 0,
        ];

        if (!barangay_table_exists($con, 'nutrition_household_family_member')
            || !barangay_table_exists($con, 'nutrition_household_survey')) {
            return $totals;
        }

        $sql = 'SELECT m.*, s.survey_date
            FROM nutrition_household_family_member m
            INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id
            WHERE ' . nutrition_survey_current_condition($con, 's');
        if ($barangayId !== null && $barangayId !== '') {
            $sql .= " AND m.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
        }

        $result = $con->query($sql);
        if (!$result) {
            return $totals;
        }

        $monthStart = date('Y-m-01');
        while ($member = $result->fetch_assoc()) {
            $ageMonths = isset($member['age_months']) && $member['age_months'] !== null && $member['age_months'] !== ''
                ? (int) $member['age_months']
                : null;
            if ($ageMonths === null) {
                $ageMonths = nutrition_age_in_months(
                    trim((string) ($member['birth_date'] ?? '')) !== '' ? (string) $member['birth_date'] : null
                );
            }

            if (!nutrition_member_is_child_0_to_19($ageMonths, (string) ($member['birth_date'] ?? ''))) {
                continue;
            }

            $totals['children']++;

            if (!nutrition_survey_member_is_assessed($member)) {
                continue;
            }

            $totals['assessed']++;
            $statuses = nutrition_survey_member_dashboard_statuses($member);
            foreach ($statuses as $status) {
                if (isset($totals[$status])) {
                    $totals[$status]++;
                }
            }
            if ($statuses !== [] && !in_array('normal', $statuses, true)) {
                $totals['at_risk']++;
            }

            $measuredOn = trim((string) ($member['date_measured'] ?? ''));
            if ($measuredOn === '') {
                $measuredOn = trim((string) ($member['survey_date'] ?? ''));
            }
            $measuredYmd = nutrition_normalize_date_to_ymd($measuredOn);
            if ($measuredYmd !== null && $measuredYmd >= $monthStart) {
                $totals['this_month']++;
            }
        }

        $totals['pending'] = max(0, $totals['children'] - $totals['assessed']);

        return $totals;
    }
}

if (!function_exists('nutrition_list_children_by_dashboard_status')) {
    /**
     * Children matching a dashboard status chip (survey members + residence assessments).
     * Same membership rules as nutrition_survey_children_totals / nutrition_scoped_totals.
     *
     * @return array<int, array{name:string,age_label:string,barangay:string,purok:string,source:string,household:string,date:string}>
     */
    function nutrition_list_children_by_dashboard_status(mysqli $con, string $status, ?string $barangayId = null): array
    {
        $status = trim($status);
        $allowed = array_keys(nutrition_status_options());
        if ($status === '' || !in_array($status, $allowed, true)) {
            return [];
        }

        $rows = [];
        $seen = [];

        if (barangay_table_exists($con, 'nutrition_household_family_member')
            && barangay_table_exists($con, 'nutrition_household_survey')) {
            $sql = 'SELECT m.member_name, m.birth_date, m.age_months, m.date_measured,
                           m.weight_for_age, m.height_for_age, m.weight_for_height, m.muac_status,
                           m.weight_kg, m.height_cm, m.muac_cm,
                           s.survey_date, s.purok_label, s.head_last_name, s.head_first_name, s.household_head,
                           s.barangay_id, b.barangay AS barangay_name
                FROM nutrition_household_family_member m
                INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id
                LEFT JOIN barangay_information b ON b.id = s.barangay_id
                WHERE ' . nutrition_survey_current_condition($con, 's');
            if ($barangayId !== null && $barangayId !== '') {
                $sql .= " AND m.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
            }
            $sql .= ' ORDER BY b.barangay ASC, m.member_name ASC';

            $result = $con->query($sql);
            if ($result) {
                while ($member = $result->fetch_assoc()) {
                    $ageMonths = isset($member['age_months']) && $member['age_months'] !== null && $member['age_months'] !== ''
                        ? (int) $member['age_months']
                        : null;
                    if ($ageMonths === null) {
                        $ageMonths = nutrition_age_in_months(
                            trim((string) ($member['birth_date'] ?? '')) !== '' ? (string) $member['birth_date'] : null
                        );
                    }
                    if (!nutrition_member_is_child_0_to_19($ageMonths, (string) ($member['birth_date'] ?? ''))) {
                        continue;
                    }
                    if (!nutrition_survey_member_is_assessed($member)) {
                        continue;
                    }
                    $statuses = nutrition_survey_member_dashboard_statuses($member);
                    if (!in_array($status, $statuses, true)) {
                        continue;
                    }

                    $name = trim((string) ($member['member_name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $ageYears = nutrition_member_age_years($ageMonths, (string) ($member['birth_date'] ?? ''));
                    $ageLabel = $ageYears !== null ? ($ageYears . ' y/o') : '';
                    if ($ageMonths !== null && $ageMonths < 24) {
                        $ageLabel = $ageMonths . ' mo';
                    }
                    $head = trim((string) ($member['household_head'] ?? ''));
                    if ($head === '') {
                        $head = trim(
                            trim((string) ($member['head_last_name'] ?? '')) . ', '
                            . trim((string) ($member['head_first_name'] ?? '')),
                            ' ,'
                        );
                    }
                    $date = trim((string) ($member['date_measured'] ?? ''));
                    if ($date === '') {
                        $date = trim((string) ($member['survey_date'] ?? ''));
                    }
                    $key = strtolower($name . '|' . (string) ($member['barangay_id'] ?? '') . '|' . $date . '|survey');
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $rows[] = [
                        'name' => $name,
                        'age_label' => $ageLabel,
                        'barangay' => trim((string) ($member['barangay_name'] ?? '')),
                        'purok' => trim((string) ($member['purok_label'] ?? '')),
                        'source' => 'Household Survey',
                        'household' => $head,
                        'date' => $date,
                    ];
                }
            }
        }

        // Also include latest residence assessments (barangay dashboard totals add these).
        if (nutrition_table_exists($con) && barangay_table_exists($con, 'residence_information')) {
            $sql = "SELECT ri.first_name, ri.middle_name, ri.last_name, ri.age,
                           na.assessment_date, na.nutritional_status, na.barangay_id,
                           b.barangay AS barangay_name
                FROM nutrition_assessment na
                INNER JOIN residence_information ri ON na.residence_id = ri.residence_id
                INNER JOIN (
                    SELECT residence_id, MAX(assessment_date) AS latest_date
                    FROM nutrition_assessment
                    WHERE (? = '' OR barangay_id = ?)
                    GROUP BY residence_id
                ) latest ON latest.residence_id = na.residence_id AND latest.latest_date = na.assessment_date
                LEFT JOIN barangay_information b ON b.id = na.barangay_id
                WHERE na.nutritional_status = ?
                  AND (? = '' OR na.barangay_id = ?)
                ORDER BY b.barangay ASC, ri.last_name ASC, ri.first_name ASC";
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $scope = ($barangayId !== null && $barangayId !== '') ? $barangayId : '';
                $stmt->bind_param('sssss', $scope, $scope, $status, $scope, $scope);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $name = trim(
                        trim((string) ($row['last_name'] ?? '')) . ', '
                        . trim((string) ($row['first_name'] ?? '')) . ' '
                        . trim((string) ($row['middle_name'] ?? ''))
                    );
                    $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
                    if ($name === '' || $name === ',') {
                        continue;
                    }
                    $date = trim((string) ($row['assessment_date'] ?? ''));
                    $key = strtolower($name . '|' . (string) ($row['barangay_id'] ?? '') . '|' . $date . '|assessment');
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $age = trim((string) ($row['age'] ?? ''));
                    $rows[] = [
                        'name' => $name,
                        'age_label' => $age !== '' ? ('Age ' . $age) : '',
                        'barangay' => trim((string) ($row['barangay_name'] ?? '')),
                        'purok' => '',
                        'source' => 'Individual Assessment',
                        'household' => '',
                        'date' => $date,
                    ];
                }
                $stmt->close();
            }
        }

        usort($rows, static function (array $a, array $b): int {
            $cmp = strcasecmp((string) ($a['barangay'] ?? ''), (string) ($b['barangay'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $rows;
    }
}

if (!function_exists('nutrition_survey_current_condition')) {
    /**
     * SQL condition that skips households that moved out (transferred) of the barangay.
     * Historical reports (EOPT/BNP lists) intentionally do not use this.
     */
    function nutrition_survey_current_condition(mysqli $con, string $alias = ''): string
    {
        if (!barangay_column_exists($con, 'nutrition_household_survey', 'moved_out')) {
            return '1=1';
        }
        $prefix = $alias !== '' ? $alias . '.' : '';

        return "COALESCE({$prefix}moved_out, 'NO') <> 'YES'";
    }
}

if (!function_exists('nutrition_children_where')) {
    /**
     * @return array<int, string>
     */
    function nutrition_children_where(mysqli $con, string $infoAlias = 'ri', string $statusAlias = 'rs'): array
    {
        return [
            "$statusAlias.archive = 'NO'",
            nutrition_children_age_condition($infoAlias),
        ];
    }
}

if (!function_exists('nutrition_pregnant_count')) {
    /**
     * Count pregnant individuals from household surveys (heads + family members).
     * Pass $status (e.g. Teenage) to limit by pregnant_nutrition_status.
     */
    function nutrition_pregnant_count(mysqli $con, ?string $barangayId = null, ?string $status = null): int
    {
        if (!barangay_table_exists($con, 'nutrition_household_survey')) {
            return 0;
        }

        $barangayFilter = '';
        $memberBarangayFilter = '';
        if ($barangayId !== null && $barangayId !== '') {
            $escaped = $con->real_escape_string($barangayId);
            $barangayFilter = " AND barangay_id = '{$escaped}'";
            $memberBarangayFilter = " AND s.barangay_id = '{$escaped}'";
        }

        $statusFilterHead = '';
        $statusFilterMember = '';
        if ($status !== null && $status !== '') {
            $statusEscaped = $con->real_escape_string($status);
            $statusFilterHead = " AND head_pregnant_nutrition_status = '{$statusEscaped}'";
            $statusFilterMember = " AND m.pregnant_nutrition_status = '{$statusEscaped}'";
        }

        $total = 0;
        $currentHead = nutrition_survey_current_condition($con);
        $currentMember = nutrition_survey_current_condition($con, 's');

        $headSql = "SELECT COUNT(*) AS total
            FROM nutrition_household_survey
            WHERE UPPER(head_is_pregnant) = 'YES'
              AND {$currentHead}
              {$statusFilterHead}
              {$barangayFilter}";
        $headResult = $con->query($headSql);
        $total += (int) ($headResult ? $headResult->fetch_assoc()['total'] ?? 0 : 0);

        if (barangay_table_exists($con, 'nutrition_household_family_member')) {
            $memberSql = "SELECT COUNT(*) AS total
                FROM nutrition_household_family_member m
                INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id
                WHERE UPPER(m.is_pregnant) = 'YES'
                  AND {$currentMember}
                  {$statusFilterMember}
                  {$memberBarangayFilter}";
            $memberResult = $con->query($memberSql);
            $total += (int) ($memberResult ? $memberResult->fetch_assoc()['total'] ?? 0 : 0);
        }

        return $total;
    }
}

if (!function_exists('nutrition_teenage_pregnant_count')) {
    /**
     * Count pregnant individuals marked Teenage (BNP column B) from household surveys.
     */
    function nutrition_teenage_pregnant_count(mysqli $con, ?string $barangayId = null): int
    {
        return nutrition_pregnant_count($con, $barangayId, 'Teenage');
    }
}

if (!function_exists('nutrition_scoped_totals')) {
    /**
     * @return array{
     *   children:int,
     *   assessed:int,
     *   pending:int,
     *   normal:int,
     *   underweight:int,
     *   wasted:int,
     *   severely_wasted:int,
     *   stunted:int,
     *   overweight:int,
     *   obese:int,
     *   this_month:int,
     *   pregnant:int,
     *   teenage_pregnant:int
     * }
     */
    function nutrition_scoped_totals(mysqli $con, string $barangayId): array
    {
        $defaults = [
            'children' => 0,
            'assessed' => 0,
            'pending' => 0,
            'normal' => 0,
            'underweight' => 0,
            'wasted' => 0,
            'severely_wasted' => 0,
            'stunted' => 0,
            'overweight' => 0,
            'obese' => 0,
            'this_month' => 0,
            'at_risk' => 0,
            'pregnant' => 0,
            'teenage_pregnant' => 0,
        ];

        $surveyTotals = nutrition_survey_children_totals($con, $barangayId);
        foreach (['children', 'assessed', 'this_month', 'normal', 'underweight', 'wasted', 'severely_wasted', 'stunted', 'overweight', 'obese', 'at_risk'] as $key) {
            $defaults[$key] += (int) ($surveyTotals[$key] ?? 0);
        }

        if (!nutrition_table_exists($con) || !barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $defaults['pending'] = max(0, $defaults['children'] - $defaults['assessed']);
            $defaults['teenage_pregnant'] = nutrition_teenage_pregnant_count($con, $barangayId);
            $defaults['pregnant'] = nutrition_pregnant_count($con, $barangayId);

            return $defaults;
        }

        $childWhere = nutrition_children_where($con);
        $childWhere[] = "rs.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
        if (!function_exists('nutrition_count_residence_children_excluding_survey_duplicates')) {
            require_once __DIR__ . '/nutrition_residence_sync.php';
        }
        $defaults['children'] += nutrition_count_residence_children_excluding_survey_duplicates($con, $barangayId);

        $latestSql = "SELECT na.residence_id, na.nutritional_status, na.assessment_date
            FROM nutrition_assessment na
            INNER JOIN (
                SELECT residence_id, MAX(assessment_date) AS latest_date
                FROM nutrition_assessment
                WHERE barangay_id = ?
                GROUP BY residence_id
            ) latest ON latest.residence_id = na.residence_id AND latest.latest_date = na.assessment_date
            WHERE na.barangay_id = ?";
        $stmt = $con->prepare($latestSql);
        if (!$stmt) {
            return $defaults;
        }
        $stmt->bind_param('ss', $barangayId, $barangayId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $defaults['assessed']++;
            $status = (string) ($row['nutritional_status'] ?? 'normal');
            if (isset($defaults[$status])) {
                $defaults[$status]++;
            }
            if ($status !== '' && $status !== 'normal') {
                $defaults['at_risk']++;
            }
        }
        $stmt->close();

        $defaults['pending'] = max(0, $defaults['children'] - $defaults['assessed']);

        $monthStmt = $con->prepare(
            "SELECT COUNT(*) AS total FROM nutrition_assessment
             WHERE barangay_id = ? AND assessment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
        if ($monthStmt) {
            $monthStmt->bind_param('s', $barangayId);
            $monthStmt->execute();
            $defaults['this_month'] += (int) ($monthStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $monthStmt->close();
        }

        $defaults['teenage_pregnant'] = nutrition_teenage_pregnant_count($con, $barangayId);
        $defaults['pregnant'] = nutrition_pregnant_count($con, $barangayId);

        return $defaults;
    }
}

if (!function_exists('nutrition_hub_totals')) {
    /**
     * City-wide nutrition summary for the hub.
     *
     * @return array<string, int>
     */
    function nutrition_hub_totals(mysqli $con): array
    {
        $totals = [
            'children' => 0,
            'assessed' => 0,
            'pending' => 0,
            'at_risk' => 0,
            'this_month' => 0,
            'pregnant' => 0,
            'teenage_pregnant' => 0,
        ];

        $surveyTotals = nutrition_survey_children_totals($con);
        $totals['children'] = (int) ($surveyTotals['children'] ?? 0);
        $totals['assessed'] = (int) ($surveyTotals['assessed'] ?? 0);
        $totals['this_month'] = (int) ($surveyTotals['this_month'] ?? 0);
        $totals['at_risk'] = (int) ($surveyTotals['at_risk'] ?? 0);

        if (!nutrition_table_exists($con) || !barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $totals['pending'] = max(0, $totals['children'] - $totals['assessed']);
            $totals['pregnant'] = nutrition_pregnant_count($con);
            $totals['teenage_pregnant'] = nutrition_teenage_pregnant_count($con);

            return $totals;
        }

        $childWhere = nutrition_children_where($con);
        if (!function_exists('nutrition_count_residence_children_excluding_survey_duplicates')) {
            require_once __DIR__ . '/nutrition_residence_sync.php';
        }
        $totals['children'] += nutrition_count_residence_children_excluding_survey_duplicates($con);

        $latestSql = "SELECT COUNT(*) AS total,
            SUM(CASE WHEN nutritional_status != 'normal' THEN 1 ELSE 0 END) AS at_risk
            FROM nutrition_assessment na
            INNER JOIN (
                SELECT residence_id, MAX(assessment_date) AS latest_date
                FROM nutrition_assessment
                GROUP BY residence_id
            ) latest ON latest.residence_id = na.residence_id AND latest.latest_date = na.assessment_date";
        $latestResult = $con->query($latestSql);
        if ($latestResult) {
            $row = $latestResult->fetch_assoc() ?: [];
            $totals['assessed'] += (int) ($row['total'] ?? 0);
            $totals['at_risk'] += (int) ($row['at_risk'] ?? 0);
        }

        $totals['pending'] = max(0, $totals['children'] - $totals['assessed']);

        $monthResult = $con->query(
            "SELECT COUNT(*) AS total FROM nutrition_assessment
             WHERE assessment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
        $totals['this_month'] += (int) ($monthResult ? $monthResult->fetch_assoc()['total'] ?? 0 : 0);
        $totals['pregnant'] = nutrition_pregnant_count($con);
        $totals['teenage_pregnant'] = nutrition_teenage_pregnant_count($con);

        return $totals;
    }
}

if (!function_exists('nutrition_recent_activity_rows')) {
    /**
     * Recent nutrition activity for a barangay: residence assessments + assessed survey children.
     *
     * @return array<int, array{name:string,date:string,status:string,source:string,age_label:string}>
     */
    function nutrition_recent_activity_rows(mysqli $con, string $barangayId, int $limit = 8): array
    {
        $rows = [];
        $limit = max(1, min(50, $limit));

        $recentSql = "SELECT na.assessment_date, na.nutritional_status, na.date_created,
                ri.first_name, ri.last_name, ri.age
            FROM nutrition_assessment na
            INNER JOIN residence_information ri ON na.residence_id = ri.residence_id
            WHERE na.barangay_id = ?
            ORDER BY na.assessment_date DESC, na.date_created DESC
            LIMIT ?";
        $recentStmt = $con->prepare($recentSql);
        if ($recentStmt) {
            $recentStmt->bind_param('si', $barangayId, $limit);
            $recentStmt->execute();
            $result = $recentStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $date = trim((string) ($row['assessment_date'] ?? ''));
                $rows[] = [
                    'name' => trim((string) ($row['last_name'] ?? '') . ', ' . (string) ($row['first_name'] ?? ''), ' ,'),
                    'date' => $date,
                    'status' => (string) ($row['nutritional_status'] ?? 'normal'),
                    'source' => 'assessment',
                    'age_label' => trim((string) ($row['age'] ?? '')) !== ''
                        ? 'Age ' . trim((string) $row['age'])
                        : '',
                    '_sort' => $date . ' ' . (string) ($row['date_created'] ?? ''),
                ];
            }
            $recentStmt->close();
        }

        if (barangay_table_exists($con, 'nutrition_household_family_member')
            && barangay_table_exists($con, 'nutrition_household_survey')) {
            $sql = "SELECT m.member_name, m.birth_date, m.age_months, m.date_measured, m.weight_for_age,
                           m.height_for_age, m.weight_for_height, m.muac_status, s.survey_date
                    FROM nutrition_household_family_member m
                    INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id
                    WHERE m.barangay_id = ?
                    ORDER BY COALESCE(m.date_measured, s.survey_date) DESC, m.member_id DESC
                    LIMIT 40";
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $barangayId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($member = $result->fetch_assoc()) {
                    $ageMonths = isset($member['age_months']) && $member['age_months'] !== null && $member['age_months'] !== ''
                        ? (int) $member['age_months']
                        : nutrition_age_in_months(
                            trim((string) ($member['birth_date'] ?? '')) !== '' ? (string) $member['birth_date'] : null
                        );
                    if (!nutrition_member_is_child_0_to_19($ageMonths, (string) ($member['birth_date'] ?? ''))) {
                        continue;
                    }
                    if (!nutrition_survey_member_is_assessed($member)) {
                        continue;
                    }
                    $status = nutrition_survey_member_dashboard_status($member);
                    if ($status === null) {
                        continue;
                    }
                    $date = trim((string) ($member['date_measured'] ?? ''));
                    if ($date === '') {
                        $date = trim((string) ($member['survey_date'] ?? ''));
                    }
                    $ageLabel = '';
                    if ($ageMonths !== null) {
                        $years = (int) floor($ageMonths / 12);
                        $ageLabel = $years > 0 ? ('Age ' . $years) : ($ageMonths . ' mo');
                    }
                    $rows[] = [
                        'name' => trim((string) ($member['member_name'] ?? '')),
                        'date' => $date,
                        'status' => $status,
                        'source' => 'survey',
                        'age_label' => $ageLabel,
                        '_sort' => $date,
                    ];
                }
                $stmt->close();
            }
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($b['_sort'] ?? ''), (string) ($a['_sort'] ?? ''));
        });

        $out = [];
        foreach (array_slice($rows, 0, $limit) as $row) {
            unset($row['_sort']);
            $out[] = $row;
        }

        return $out;
    }
}

if (!function_exists('nutrition_hub_status_totals')) {
    /**
     * City-wide nutritional status breakdown (latest assessment per resident).
     *
     * @return array<string, int>
     */
    function nutrition_hub_status_totals(mysqli $con): array
    {
        $totals = [
            'normal' => 0,
            'underweight' => 0,
            'wasted' => 0,
            'severely_wasted' => 0,
            'stunted' => 0,
            'overweight' => 0,
            'obese' => 0,
        ];

        if (!nutrition_table_exists($con)) {
            return $totals;
        }

        $sql = "SELECT na.nutritional_status
            FROM nutrition_assessment na
            INNER JOIN (
                SELECT residence_id, MAX(assessment_date) AS latest_date
                FROM nutrition_assessment
                GROUP BY residence_id
            ) latest ON latest.residence_id = na.residence_id AND latest.latest_date = na.assessment_date";
        $result = $con->query($sql);
        if (!$result) {
            return $totals;
        }

        while ($row = $result->fetch_assoc()) {
            $status = (string) ($row['nutritional_status'] ?? 'normal');
            if (isset($totals[$status])) {
                $totals[$status]++;
            }
        }

        $surveyTotals = nutrition_survey_children_totals($con);
        foreach (['normal', 'underweight', 'wasted', 'severely_wasted', 'stunted', 'overweight', 'obese'] as $key) {
            $totals[$key] += (int) ($surveyTotals[$key] ?? 0);
        }

        return $totals;
    }
}

if (!function_exists('nutrition_super_dashboard_rows')) {
    /**
     * Per-barangay nutrition summary for the super admin nutrition dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    function nutrition_super_dashboard_rows(mysqli $con, ?array $limitBarangayIds = null): array
    {
        $rows = [];
        $surveysByBarangay = [];
        $bnsByBarangay = [];
        $limitLookup = null;
        if ($limitBarangayIds !== null) {
            $limitLookup = [];
            foreach ($limitBarangayIds as $id) {
                $id = trim((string) $id);
                if ($id !== '') {
                    $limitLookup[$id] = true;
                }
            }
        }

        if (barangay_table_exists($con, 'nutrition_household_survey')) {
            $surveyResult = $con->query(
                'SELECT barangay_id, COUNT(*) AS total FROM nutrition_household_survey WHERE '
                . nutrition_survey_current_condition($con) . ' GROUP BY barangay_id'
            );
            if ($surveyResult) {
                while ($surveyRow = $surveyResult->fetch_assoc()) {
                    $surveysByBarangay[(string) $surveyRow['barangay_id']] = (int) ($surveyRow['total'] ?? 0);
                }
            }
        }

        if (staff_role_column_exists($con) && barangay_column_exists($con, 'users', 'barangay_id')) {
            $bnsRole = STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR;
            $bnsStmt = $con->prepare(
                "SELECT u.barangay_id, u.username
                 FROM users u
                 WHERE u.staff_role = ? AND u.barangay_id IS NOT NULL AND u.barangay_id != ''"
            );
            if ($bnsStmt) {
                $bnsStmt->bind_param('s', $bnsRole);
                $bnsStmt->execute();
                $bnsResult = $bnsStmt->get_result();
                while ($bnsRow = $bnsResult->fetch_assoc()) {
                    $bnsByBarangay[(string) $bnsRow['barangay_id']] = (string) ($bnsRow['username'] ?? '');
                }
                $bnsStmt->close();
            }
        }

        foreach (barangay_list_all($con) as $barangayRow) {
            $barangayId = (string) ($barangayRow['id'] ?? '');
            if ($barangayId === '') {
                continue;
            }
            if ($limitLookup !== null && !isset($limitLookup[$barangayId])) {
                continue;
            }

            $scoped = nutrition_scoped_totals($con, $barangayId);
            $atRisk = (int) ($scoped['at_risk'] ?? 0);

            $rows[] = [
                'id' => $barangayId,
                'barangay' => (string) ($barangayRow['barangay'] ?? ''),
                'zone' => (string) ($barangayRow['zone'] ?? ''),
                'logo' => barangay_admin_logo_url($barangayRow),
                'children' => (int) $scoped['children'],
                'assessed' => (int) $scoped['assessed'],
                'pending' => (int) $scoped['pending'],
                'at_risk' => $atRisk,
                'this_month' => (int) $scoped['this_month'],
                'surveys' => $surveysByBarangay[$barangayId] ?? 0,
                'pregnant' => (int) ($scoped['pregnant'] ?? 0),
                'teenage_pregnant' => (int) ($scoped['teenage_pregnant'] ?? 0),
                'bns_username' => $bnsByBarangay[$barangayId] ?? '',
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcasecmp((string) $a['barangay'], (string) $b['barangay']));

        return $rows;
    }
}

if (!function_exists('nutrition_staff_display_name')) {
    function nutrition_staff_display_name(array $row): string
    {
        $first = trim((string) ($row['first_name'] ?? ''));
        $middle = trim((string) ($row['middle_name'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));
        $middleInitial = $middle !== '' ? strtoupper($middle[0]) . '. ' : '';

        return trim($first . ' ' . $middleInitial . $last);
    }
}

if (!function_exists('nutrition_prepared_by_signatory')) {
    /**
     * Name and role for BNP "Prepared by" — uses the logged-in user when available.
     *
     * @return array{name:string,title:string}
     */
    function nutrition_prepared_by_signatory(mysqli $con, ?string $userId = null, string $fallbackName = ''): array
    {
        $userId = $userId ?? (string) ($_SESSION['user_id'] ?? '');
        if ($userId !== '') {
            $stmt = $con->prepare(
                'SELECT first_name, middle_name, last_name, staff_role, user_type, barangay_id
                 FROM users WHERE id = ? LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('s', $userId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $name = nutrition_staff_display_name($row);
                    if (function_exists('staff_account_resolve_role')) {
                        require_once __DIR__ . '/staff_accounts.php';
                    }
                    $role = function_exists('staff_account_resolve_role')
                        ? staff_account_resolve_role($row)
                        : barangay_user_staff_role($con, $userId);
                    $title = $role !== '' ? staff_role_label($role) : 'Staff';

                    if ($name !== '') {
                        return ['name' => $name, 'title' => $title];
                    }
                }
            }
        }

        $fallbackName = trim($fallbackName);

        return [
            'name' => $fallbackName,
            'title' => 'Barangay Nutrition Scholar (BNS)',
        ];
    }
}

if (!function_exists('nutrition_staff_signature_html')) {
    function nutrition_staff_signature_html(array $row, string $imgId = 'nutrition-signatory'): string
    {
        $image = trim((string) ($row['image'] ?? ''));
        $path = trim((string) ($row['image_path'] ?? ''));
        if ($image !== '' && $path !== '') {
            return '<img src="' . barangay_h($path) . '" class="nutrition-signatory-img" id="' . barangay_h($imgId) . '" alt="Signature">';
        }

        return '<img src="../assets/dist/img/image.png" class="nutrition-signatory-img" id="' . barangay_h($imgId) . '" alt="Signature">';
    }
}

if (!function_exists('nutrition_bns_accounts_by_barangay')) {
    /**
     * @return array<string, array<string, mixed>>
     */
    function nutrition_bns_accounts_by_barangay(mysqli $con): array
    {
        $accounts = [];
        if (!staff_role_column_exists($con) || !barangay_column_exists($con, 'users', 'barangay_id')) {
            return $accounts;
        }

        $role = STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR;
        $stmt = $con->prepare(
            "SELECT u.id, u.barangay_id, u.username, u.first_name, u.middle_name, u.last_name,
                    u.image, u.image_path, b.barangay AS barangay_name, b.zone
             FROM users u
             LEFT JOIN barangay_information b ON u.barangay_id = b.id
             WHERE u.staff_role = ? AND u.barangay_id IS NOT NULL AND u.barangay_id != ''"
        );
        if (!$stmt) {
            return $accounts;
        }
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $barangayId = (string) ($row['barangay_id'] ?? '');
            if ($barangayId === '') {
                continue;
            }
            $row['display_name'] = nutrition_staff_display_name($row);
            $accounts[$barangayId] = $row;
        }
        $stmt->close();

        return $accounts;
    }
}

if (!function_exists('nutrition_bns_admin_account')) {
    /**
     * @return array<string, mixed>|null
     */
    function nutrition_bns_admin_account(mysqli $con): ?array
    {
        if (!staff_role_column_exists($con)) {
            return null;
        }

        $role = STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN;
        $stmt = $con->prepare(
            "SELECT u.id, u.username, u.first_name, u.middle_name, u.last_name, u.image, u.image_path
             FROM users u
             WHERE u.staff_role = ?
             ORDER BY u.first_name ASC, u.last_name ASC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }
        $row['display_name'] = nutrition_staff_display_name($row);

        return $row;
    }
}

if (!function_exists('nutrition_city_certificate_header')) {
    /**
     * @return array<string, string>
     */
    function nutrition_city_certificate_header(): array
    {
        return [
            'country' => 'Republic of the Philippines',
            'province' => 'Province of Bukidnon',
            'city' => 'City of Valencia',
            'system_line' => 'BARANGAY NUTRITION PROFILING SYSTEM',
            'office' => 'OFFICE OF THE BARANGAY NUTRITION SCHOLAR ADMIN',
            'done_in' => 'City of Valencia',
        ];
    }
}

if (!function_exists('nutrition_city_report_snapshot')) {
    /**
     * City-wide nutrition report data for super admin print.
     *
     * @return array<string, mixed>
     */
    function nutrition_city_report_snapshot(mysqli $con): array
    {
        $hubTotals = nutrition_hub_totals($con);
        $statusTotals = nutrition_hub_status_totals($con);
        $barangayRows = nutrition_super_dashboard_rows($con);
        $bnsAccounts = nutrition_bns_accounts_by_barangay($con);
        $bnsAdmin = nutrition_bns_admin_account($con);

        foreach ($barangayRows as &$row) {
            $barangayId = (string) ($row['id'] ?? '');
            $bns = $bnsAccounts[$barangayId] ?? null;
            $row['bns'] = $bns;
            $row['bns_name'] = $bns ? (string) ($bns['display_name'] ?? '') : '';
        }
        unset($row);

        $atRisk = (int) ($hubTotals['at_risk'] ?? 0);

        return [
            'hub_totals' => $hubTotals,
            'status_totals' => $statusTotals,
            'barangay_rows' => $barangayRows,
            'bns_accounts' => $bnsAccounts,
            'bns_admin' => $bnsAdmin,
            'barangay_count' => count($barangayRows),
            'total_surveys' => array_sum(array_column($barangayRows, 'surveys')),
            'at_risk' => $atRisk,
            'coverage' => $hubTotals['children'] > 0
                ? round(($hubTotals['assessed'] / $hubTotals['children']) * 100, 1)
                : 0.0,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}

if (!function_exists('nutrition_count_assessments')) {
    function nutrition_count_assessments(mysqli $con, string $barangayId): int
    {
        if (!nutrition_table_exists($con)) {
            return 0;
        }

        $stmt = $con->prepare('SELECT COUNT(*) AS total FROM nutrition_assessment WHERE barangay_id = ?');
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $barangayId);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }
}

if (!function_exists('nutrition_resident_age_years')) {
    function nutrition_resident_age_years(array $resident): int
    {
        $birth = trim((string) ($resident['birth_date'] ?? ''));
        if ($birth !== '') {
            $timestamp = strtotime($birth);
            if ($timestamp !== false) {
                return max(0, (int) date('Y') - (int) date('Y', $timestamp));
            }
        }

        return max(0, (int) ($resident['age'] ?? 0));
    }
}

if (!function_exists('nutrition_load_resident')) {
    function nutrition_load_resident(mysqli $con, string $residenceId, ?string $barangayId = null): ?array
    {
        $statusSelect = 'rs.barangay_id, rs.purok_id, rs.pwd, rs.`4ps`, rs.single_parent';
        if (barangay_column_exists($con, 'residence_status', 'household_head')) {
            $statusSelect .= ', rs.household_head';
        }
        if (barangay_column_exists($con, 'residence_status', 'indigenous')) {
            $statusSelect .= ', rs.indigenous';
        }

        $sql = "SELECT ri.*, {$statusSelect}
            FROM residence_information ri
            INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
            WHERE ri.residence_id = ? AND rs.archive = ?";
        $types = 'ss';
        $archive = 'NO';
        $params = [$residenceId, $archive];

        if ($barangayId !== null && $barangayId !== '' && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $sql .= ' AND rs.barangay_id = ?';
            $types .= 's';
            $params[] = $barangayId;
        }

        $stmt = $con->prepare($sql . ' LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ?: null;
    }
}

if (!function_exists('nutrition_purok_number_from_label')) {
    function nutrition_purok_number_from_label(string $purokLabel): int
    {
        if (preg_match('/(\d+)/', $purokLabel, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }
}

if (!function_exists('nutrition_purok_input_from_label')) {
    /** Display value for the purok text field (e.g. 1, 1A, A). */
    function nutrition_purok_input_from_label(string $purokLabel): string
    {
        $label = trim($purokLabel);
        if ($label === '') {
            return '1';
        }
        $core = preg_replace('/^(purok|prk)\s*/i', '', $label) ?? $label;
        $core = trim($core);

        return $core !== '' ? $core : '1';
    }
}

if (!function_exists('nutrition_format_member_display_name')) {
    function nutrition_format_member_display_name(
        string $firstName,
        string $middleName = '',
        string $lastName = '',
        string $suffix = ''
    ): string {
        $parts = array_filter([
            trim($firstName),
            trim($middleName),
            trim($lastName),
            trim($suffix),
        ], static fn (string $part): bool => $part !== '');

        return trim(implode(' ', $parts));
    }
}

if (!function_exists('nutrition_load_resident_survey_prefill')) {
    /**
     * Prefill household survey fields from a barangay resident record.
     *
     * @return array{
     *   residence_id:string,
     *   head_last_name:string,
     *   head_first_name:string,
     *   head_middle_name:string,
     *   head_suffix:string,
     *   birth_date:string,
     *   gender:string,
     *   occupation:string,
     *   is_4ps:string,
     *   is_pwd:string,
     *   is_ip:string,
     *   is_solo_parent:string,
     *   purok_number:int,
     *   purok_input:string,
     *   purok_label:string,
     *   family_members:array<int, array{member_name:string,relationship:string,gender:string,birth_date:string}>
     * }|null
     */
    function nutrition_load_resident_survey_prefill(mysqli $con, string $residenceId, string $barangayId): ?array
    {
        require_once __DIR__ . '/residence_family.php';

        $resident = nutrition_load_resident($con, $residenceId, $barangayId);
        if ($resident === null) {
            return null;
        }

        $purokLabel = '';
        $purokId = trim((string) ($resident['purok_id'] ?? ''));
        if ($purokId !== '' && barangay_table_exists($con, 'purok')) {
            $stmt = $con->prepare('SELECT purok FROM purok WHERE purok_id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $purokId);
                $stmt->execute();
                $purokLabel = trim((string) ($stmt->get_result()->fetch_assoc()['purok'] ?? ''));
                $stmt->close();
            }
        }

        $familyMembers = [];
        $spouseFirst = trim((string) ($resident['spouse_first_name'] ?? ''));
        $spouseLast = trim((string) ($resident['spouse_last_name'] ?? ''));
        if ($spouseFirst !== '' || $spouseLast !== '') {
            $familyMembers[] = [
                'member_name' => nutrition_format_member_display_name(
                    $spouseFirst,
                    (string) ($resident['spouse_middle_name'] ?? ''),
                    $spouseLast,
                    (string) ($resident['spouse_suffix'] ?? '')
                ),
                'relationship' => 'Spouse',
                'gender' => '',
                'birth_date' => trim((string) ($resident['spouse_birth_date'] ?? '')),
            ];
        }

        foreach (residence_load_dependents($con, $residenceId) as $dependent) {
            $familyMembers[] = [
                'member_name' => nutrition_format_member_display_name(
                    (string) ($dependent['first_name'] ?? ''),
                    (string) ($dependent['middle_name'] ?? ''),
                    (string) ($dependent['last_name'] ?? ''),
                    (string) ($dependent['suffix'] ?? '')
                ),
                'relationship' => trim((string) ($dependent['relationship'] ?? '')),
                'gender' => trim((string) ($dependent['gender'] ?? '')),
                'birth_date' => trim((string) ($dependent['birth_date'] ?? '')),
            ];
        }

        $gender = trim((string) ($resident['gender'] ?? ''));
        if (!in_array($gender, ['Male', 'Female'], true)) {
            $gender = '';
        }

        $birthDate = trim((string) ($resident['birth_date'] ?? ''));
        if ($birthDate === '' || strcasecmp($birthDate, 'none') === 0 || strtotime($birthDate) === false) {
            $birthDate = '';
        } else {
            $birthDate = date('Y-m-d', strtotime($birthDate));
        }

        $yesNo = static function (mixed $value): string {
            return strtoupper(trim((string) $value)) === 'YES' ? 'YES' : 'NO';
        };

        return [
            'residence_id' => (string) ($resident['residence_id'] ?? $residenceId),
            'head_last_name' => trim((string) ($resident['last_name'] ?? '')),
            'head_first_name' => trim((string) ($resident['first_name'] ?? '')),
            'head_middle_name' => trim((string) ($resident['middle_name'] ?? '')),
            'head_suffix' => trim((string) ($resident['suffix'] ?? '')),
            'birth_date' => $birthDate,
            'gender' => $gender,
            'occupation' => trim((string) ($resident['occupation'] ?? '')),
            'is_4ps' => $yesNo($resident['4ps'] ?? 'NO'),
            'is_pwd' => $yesNo($resident['pwd'] ?? 'NO'),
            'is_ip' => $yesNo($resident['indigenous'] ?? 'NO'),
            'is_solo_parent' => $yesNo($resident['single_parent'] ?? 'NO'),
            'purok_number' => nutrition_purok_number_from_label($purokLabel),
            'purok_input' => nutrition_purok_input_from_label($purokLabel),
            'purok_label' => $purokLabel,
            'family_members' => $familyMembers,
        ];
    }
}

if (!function_exists('nutrition_allowed_redirect')) {
    function nutrition_allowed_redirect(string $redirect): string
    {
        $redirect = basename(str_replace('\\', '/', $redirect));
        $allowed = [
            'dashboard.php',
            'nutritionDashboard.php',
            'nutritionHouseholdSurvey.php',
            'nutritionBarangaySurvey.php',
            'nutritionProfiles.php',
            'nutritionBnpReport.php',
            'nutritionMellpiBarangayProfile.php',
            'nutritionReport.php',
        ];

        return in_array($redirect, $allowed, true) ? $redirect : 'dashboard.php';
    }
}

if (!function_exists('nutrition_ensure_module_tables')) {
    function nutrition_ensure_module_tables(mysqli $con): void
    {
        nutrition_ensure_table($con);

        if (!barangay_table_exists($con, 'nutrition_household_survey')) {
            $con->query("CREATE TABLE IF NOT EXISTS `nutrition_household_survey` (
                `survey_id` VARCHAR(32) NOT NULL,
                `barangay_id` VARCHAR(32) NOT NULL,
                `residence_id` VARCHAR(32) NOT NULL DEFAULT '',
                `house_hold_id` VARCHAR(255) NOT NULL DEFAULT '',
                `purok_label` VARCHAR(64) NOT NULL DEFAULT '',
                `head_last_name` VARCHAR(120) NOT NULL DEFAULT '',
                `head_first_name` VARCHAR(120) NOT NULL DEFAULT '',
                `head_middle_name` VARCHAR(120) NOT NULL DEFAULT '',
                `head_suffix` VARCHAR(32) NOT NULL DEFAULT '',
                `household_head` VARCHAR(255) NOT NULL DEFAULT '',
                `birth_date` DATE DEFAULT NULL,
                `gender` VARCHAR(20) NOT NULL DEFAULT '',
                `occupation` VARCHAR(120) NOT NULL DEFAULT '',
                `is_4ps` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `is_pwd` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `is_ip` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `is_solo_parent` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `survey_date` DATE NOT NULL,
                `members_count` INT NOT NULL DEFAULT 0,
                `children_count` INT NOT NULL DEFAULT 0,
                `food_security` VARCHAR(32) NOT NULL DEFAULT 'secure',
                `has_pregnant` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `has_lactating` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `supplementary_feeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `water_source` VARCHAR(120) NOT NULL DEFAULT '',
                `sanitation` VARCHAR(120) NOT NULL DEFAULT '',
                `remarks` TEXT DEFAULT NULL,
                `surveyed_by` VARCHAR(32) DEFAULT NULL,
                `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`survey_id`),
                KEY `idx_nhs_barangay` (`barangay_id`),
                KEY `idx_nhs_date` (`survey_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!barangay_table_exists($con, 'nutrition_household_family_member')) {
            $con->query("CREATE TABLE IF NOT EXISTS `nutrition_household_family_member` (
                `member_id` VARCHAR(32) NOT NULL,
                `survey_id` VARCHAR(32) NOT NULL,
                `barangay_id` VARCHAR(32) NOT NULL,
                `member_name` VARCHAR(255) NOT NULL DEFAULT '',
                `relationship` VARCHAR(120) NOT NULL DEFAULT '',
                `gender` VARCHAR(20) NOT NULL DEFAULT '',
                `birth_date` DATE DEFAULT NULL,
                `weight_kg` DECIMAL(5,2) DEFAULT NULL,
                `height_cm` DECIMAL(5,2) DEFAULT NULL,
                `date_measured` DATE DEFAULT NULL,
                `prev_weight_kg` DECIMAL(5,2) DEFAULT NULL,
                `prev_height_cm` DECIMAL(5,2) DEFAULT NULL,
                `prev_date_measured` DATE DEFAULT NULL,
                `age_months` INT DEFAULT NULL,
                `weight_for_age` VARCHAR(32) NOT NULL DEFAULT '',
                `height_for_age` VARCHAR(32) NOT NULL DEFAULT '',
                `weight_for_height` VARCHAR(32) NOT NULL DEFAULT '',
                `is_pregnant` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `is_lactating` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `pregnancy_months` INT DEFAULT NULL,
                `planned_exclusive_breastfeeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `planned_mixed_feeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `planned_bottle_feeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `planned_other_feeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `planned_other_specify` VARCHAR(255) NOT NULL DEFAULT '',
                `lactating_exclusive_breastfeeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `lactating_mixed_feeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `lactating_bottle_feeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `lactating_other_feeding` VARCHAR(8) NOT NULL DEFAULT 'NO',
                `lactating_other_specify` VARCHAR(255) NOT NULL DEFAULT '',
                `sort_order` INT NOT NULL DEFAULT 0,
                `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`member_id`),
                KEY `idx_nhfm_survey` (`survey_id`),
                KEY `idx_nhfm_barangay` (`barangay_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!barangay_table_exists($con, 'nutrition_barangay_survey')) {
            $con->query("CREATE TABLE IF NOT EXISTS `nutrition_barangay_survey` (
                `survey_id` VARCHAR(32) NOT NULL,
                `barangay_id` VARCHAR(32) NOT NULL,
                `survey_period` VARCHAR(64) NOT NULL DEFAULT '',
                `survey_date` DATE NOT NULL,
                `total_households` INT NOT NULL DEFAULT 0,
                `households_surveyed` INT NOT NULL DEFAULT 0,
                `children_screened` INT NOT NULL DEFAULT 0,
                `malnourished_cases` INT NOT NULL DEFAULT 0,
                `at_risk_cases` INT NOT NULL DEFAULT 0,
                `programs_conducted` TEXT DEFAULT NULL,
                `challenges` TEXT DEFAULT NULL,
                `recommendations` TEXT DEFAULT NULL,
                `surveyed_by` VARCHAR(32) DEFAULT NULL,
                `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`survey_id`),
                KEY `idx_nbs_barangay` (`barangay_id`),
                KEY `idx_nbs_date` (`survey_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!barangay_table_exists($con, 'nutrition_settings')) {
            $con->query("CREATE TABLE IF NOT EXISTS `nutrition_settings` (
                `barangay_id` VARCHAR(32) NOT NULL,
                `nutrition_officer` VARCHAR(255) NOT NULL DEFAULT '',
                `contact_number` VARCHAR(64) NOT NULL DEFAULT '',
                `assessment_frequency` VARCHAR(64) NOT NULL DEFAULT 'Monthly',
                `report_header` VARCHAR(255) NOT NULL DEFAULT '',
                `psfc_code` VARCHAR(16) NOT NULL DEFAULT '',
                `enable_household_survey` VARCHAR(8) NOT NULL DEFAULT 'YES',
                `enable_barangay_survey` VARCHAR(8) NOT NULL DEFAULT 'YES',
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`barangay_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        nutrition_ensure_column($con, 'nutrition_settings', 'psfc_code', "VARCHAR(16) NOT NULL DEFAULT '' AFTER `report_header`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'purok_label', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `house_hold_id`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'residence_id', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `barangay_id`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_last_name', "VARCHAR(120) NOT NULL DEFAULT '' AFTER `purok_label`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_first_name', "VARCHAR(120) NOT NULL DEFAULT '' AFTER `head_last_name`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_middle_name', "VARCHAR(120) NOT NULL DEFAULT '' AFTER `head_first_name`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_suffix', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `head_middle_name`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'birth_date', 'DATE DEFAULT NULL AFTER `household_head`');
        nutrition_ensure_column($con, 'nutrition_household_survey', 'gender', "VARCHAR(20) NOT NULL DEFAULT '' AFTER `birth_date`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'occupation', "VARCHAR(120) NOT NULL DEFAULT '' AFTER `gender`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'is_4ps', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `gender`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'is_pwd', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `is_4ps`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'is_ip', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `is_pwd`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'is_solo_parent', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `is_ip`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'bns_name', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `occupation`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'is_na_member', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `is_solo_parent`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'house_ownership', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `sanitation`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'house_ownership_other', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `house_ownership`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'toilet_type', "VARCHAR(160) NOT NULL DEFAULT '' AFTER `house_ownership_other`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'garbage_disposal', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `toilet_type`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'garbage_uncollected_type', "VARCHAR(120) NOT NULL DEFAULT '' AFTER `garbage_disposal`");
        // Widen toilet_type for full BSI sanitary/unsanitary labels (existing installs).
        try {
            $con->query("ALTER TABLE `nutrition_household_survey` MODIFY COLUMN `toilet_type` VARCHAR(160) NOT NULL DEFAULT ''");
        } catch (Throwable $e) {
            // ignore
        }
        nutrition_ensure_column($con, 'nutrition_household_survey', 'dwelling_type', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `water_source`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'food_production', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `dwelling_type`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'uses_iodized_salt', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `food_production`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'uses_sangkap_pinoy', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `uses_iodized_salt`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'has_carenderia', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `uses_sangkap_pinoy`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'has_sari_sari_store', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `has_carenderia`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'practices_family_planning', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `has_sari_sari_store`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'family_planning_methods', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `practices_family_planning`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'complementary_meals', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `family_planning_methods`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'complementary_meals_other', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `complementary_meals`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'complementary_snacks', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `complementary_meals_other`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'complementary_snacks_other', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `complementary_snacks`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'child_physical_activity', "VARCHAR(120) NOT NULL DEFAULT '' AFTER `complementary_snacks_other`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'child_physical_activity_other', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `child_physical_activity`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_is_pregnant', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `has_lactating`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_is_lactating', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_is_pregnant`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_pregnancy_months', 'INT DEFAULT NULL AFTER `head_is_lactating`');
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_pregnant_nutrition_status', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `head_pregnancy_months`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_planned_exclusive_breastfeeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_pregnant_nutrition_status`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_planned_mixed_feeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_planned_exclusive_breastfeeding`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_planned_bottle_feeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_planned_mixed_feeding`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_planned_other_feeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_planned_bottle_feeding`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_planned_other_specify', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `head_planned_other_feeding`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_lactating_exclusive_breastfeeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_planned_other_specify`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_lactating_mixed_feeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_lactating_exclusive_breastfeeding`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_lactating_bottle_feeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_lactating_mixed_feeding`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_lactating_other_feeding', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `head_lactating_bottle_feeding`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'head_lactating_other_specify', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `head_lactating_other_feeding`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'weight_kg', 'DECIMAL(5,2) DEFAULT NULL AFTER `birth_date`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'height_cm', 'DECIMAL(5,2) DEFAULT NULL AFTER `weight_kg`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'date_measured', 'DATE DEFAULT NULL AFTER `height_cm`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'prev_weight_kg', 'DECIMAL(5,2) DEFAULT NULL AFTER `date_measured`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'prev_height_cm', 'DECIMAL(5,2) DEFAULT NULL AFTER `prev_weight_kg`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'prev_date_measured', 'DATE DEFAULT NULL AFTER `prev_height_cm`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'age_months', 'INT DEFAULT NULL AFTER `prev_date_measured`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'weight_for_age', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `age_months`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'height_for_age', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `weight_for_age`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'weight_for_height', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `height_for_age`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'muac_cm', 'DECIMAL(4,1) DEFAULT NULL AFTER `weight_for_height`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'muac_status', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `muac_cm`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'edema', "VARCHAR(16) NOT NULL DEFAULT '' AFTER `muac_status`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'disability', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `edema`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'pregnant_nutrition_status', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `pregnancy_months`");
        nutrition_ensure_column($con, 'nutrition_settings', 'bnp_form_c1', "LONGTEXT NULL AFTER `enable_barangay_survey`");

        if (is_file(__DIR__ . '/nutrition_mellpi.php')) {
            require_once __DIR__ . '/nutrition_mellpi.php';
            if (function_exists('nutrition_mellpi_ensure_table')) {
                nutrition_mellpi_ensure_table($con);
            }
        }

        if (barangay_table_exists($con, 'nutrition_settings')) {
            barangay_ensure_psgc_column($con);
            barangay_seed_psgc_codes($con);

            foreach (barangay_list_all($con) as $brgyRow) {
                $brgyId = (string) ($brgyRow['id'] ?? '');
                if ($brgyId === '') {
                    continue;
                }
                $psgc = nutrition_resolve_psfc_code($con, $brgyId, (string) ($brgyRow['barangay'] ?? ''));
                if ($psgc === '') {
                    continue;
                }
                $stmt = $con->prepare(
                    'UPDATE nutrition_settings SET psfc_code = ? WHERE barangay_id = ? AND (psfc_code IS NULL OR psfc_code = ? OR psfc_code = ?)'
                );
                if ($stmt) {
                    $empty = '';
                    $cityCode = nutrition_city_psgc_code();
                    $stmt->bind_param('ssss', $psgc, $brgyId, $empty, $cityCode);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

if (!function_exists('nutrition_ensure_column')) {
    function nutrition_ensure_column(mysqli $con, string $table, string $column, string $definition): void
    {
        if (barangay_column_exists($con, $table, $column)) {
            return;
        }

        try {
            $ok = $con->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            if ($ok) {
                if (function_exists('barangay_mark_column_exists')) {
                    barangay_mark_column_exists($table, $column);
                }
                return;
            }
        } catch (Throwable $e) {
            // Race / duplicate column: treat as already present.
        }

        // Refresh cache from information_schema in case another request added it.
        if (function_exists('barangay_column_cache')) {
            $cache = &barangay_column_cache();
            unset($cache[$table . '.' . $column]);
        }
        if (barangay_column_exists($con, $table, $column)) {
            return;
        }

        // Non-fatal: leave page usable; log for operators.
        error_log('nutrition_ensure_column failed: ' . $table . '.' . $column . ' — ' . ($con->error ?: 'unknown'));
    }
}

if (!function_exists('nutrition_default_settings')) {
    /**
     * @return array<string, string>
     */
    function nutrition_default_settings(mysqli $con, string $barangayId = '', string $barangayName = ''): array
    {
        $header = $barangayName !== '' ? 'Barangay ' . $barangayName . ' Nutrition Profiling' : 'Barangay Nutrition Profiling';
        $psgc = nutrition_resolve_psfc_code($con, $barangayId, $barangayName);

        return [
            'nutrition_officer' => '',
            'contact_number' => '',
            'assessment_frequency' => 'Monthly',
            'report_header' => $header,
            'psfc_code' => $psgc,
            'enable_household_survey' => 'YES',
            'enable_barangay_survey' => 'YES',
            'bnp_form_c1' => '',
        ];
    }
}

if (!function_exists('nutrition_load_settings')) {
    /**
     * @return array<string, string>
     */
    function nutrition_load_settings(mysqli $con, string $barangayId, string $barangayName = ''): array
    {
        $defaults = nutrition_default_settings($con, $barangayId, $barangayName);
        if (!barangay_table_exists($con, 'nutrition_settings')) {
            return $defaults;
        }

        $stmt = $con->prepare('SELECT * FROM nutrition_settings WHERE barangay_id = ? LIMIT 1');
        if (!$stmt) {
            return $defaults;
        }
        $stmt->bind_param('s', $barangayId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return $defaults;
        }

        $merged = array_merge($defaults, array_map(static fn ($v) => (string) $v, $row));
        $resolvedPsgc = nutrition_resolve_psfc_code($con, $barangayId, $barangayName);
        if ($resolvedPsgc !== '') {
            $merged['psfc_code'] = $resolvedPsgc;
        }

        return $merged;
    }
}

if (!function_exists('nutrition_save_settings')) {
    function nutrition_save_settings(mysqli $con, string $barangayId, array $data): bool
    {
        nutrition_ensure_module_tables($con);

        $stmt = $con->prepare(
            'INSERT INTO nutrition_settings
             (barangay_id, nutrition_officer, contact_number, assessment_frequency, report_header, psfc_code,
              enable_household_survey, enable_barangay_survey, bnp_form_c1, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
             nutrition_officer = VALUES(nutrition_officer),
             contact_number = VALUES(contact_number),
             assessment_frequency = VALUES(assessment_frequency),
             report_header = VALUES(report_header),
             psfc_code = VALUES(psfc_code),
             enable_household_survey = VALUES(enable_household_survey),
             enable_barangay_survey = VALUES(enable_barangay_survey),
             bnp_form_c1 = VALUES(bnp_form_c1),
             updated_at = NOW()'
        );
        if (!$stmt) {
            return false;
        }

        $bnpFormC1 = (string) ($data['bnp_form_c1'] ?? '');
        if ($bnpFormC1 === '') {
            $existing = nutrition_load_settings($con, $barangayId);
            $bnpFormC1 = (string) ($existing['bnp_form_c1'] ?? '');
        }
        $stmt->bind_param(
            'sssssssss',
            $barangayId,
            $data['nutrition_officer'],
            $data['contact_number'],
            $data['assessment_frequency'],
            $data['report_header'],
            $data['psfc_code'],
            $data['enable_household_survey'],
            $data['enable_barangay_survey'],
            $bnpFormC1
        );
        $stmt->execute();
        $ok = $stmt->affected_rows >= 0;
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('nutrition_list_household_surveys')) {
    /**
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    function nutrition_list_household_surveys(mysqli $con, string $barangayId, int $limit = 50, array $filters = []): array
    {
        if (!barangay_table_exists($con, 'nutrition_household_survey')) {
            return [];
        }

        $sql = 'SELECT * FROM nutrition_household_survey WHERE barangay_id = ?';
        $types = 's';
        $params = [$barangayId];

        $purok = trim((string) ($filters['purok'] ?? ''));
        if ($purok !== '') {
            $sql .= ' AND purok_label = ?';
            $types .= 's';
            $params[] = $purok;
        }

        $purokIn = $filters['purok_in'] ?? null;
        if (is_array($purokIn) && $purokIn !== []) {
            $normalized = [];
            foreach ($purokIn as $label) {
                $label = function_exists('staff_normalize_purok_label')
                    ? staff_normalize_purok_label((string) $label)
                    : trim((string) $label);
                if ($label !== '') {
                    $normalized[$label] = $label;
                }
            }
            $normalized = array_values($normalized);
            if ($normalized !== []) {
                $placeholders = implode(',', array_fill(0, count($normalized), '?'));
                $sql .= " AND purok_label IN ($placeholders)";
                $types .= str_repeat('s', count($normalized));
                foreach ($normalized as $label) {
                    $params[] = $label;
                }
            }
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $sql .= ' AND survey_date >= ?';
            $types .= 's';
            $params[] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $sql .= ' AND survey_date <= ?';
            $types .= 's';
            $params[] = $dateTo;
        }

        $sql .= ' ORDER BY survey_date DESC, date_created DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ?';
            $types .= 'i';
            $params[] = $limit;
        }

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }
}

if (!function_exists('nutrition_list_barangay_surveys')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function nutrition_list_barangay_surveys(mysqli $con, string $barangayId, int $limit = 20): array
    {
        if (!barangay_table_exists($con, 'nutrition_barangay_survey')) {
            return [];
        }

        $stmt = $con->prepare(
            'SELECT * FROM nutrition_barangay_survey
             WHERE barangay_id = ?
             ORDER BY survey_date DESC, date_created DESC
             LIMIT ?'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('si', $barangayId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }
}

if (!function_exists('nutrition_food_security_options')) {
    /**
     * @return array<string, string>
     */
    function nutrition_food_security_options(): array
    {
        return [
            'secure' => 'Food Secure',
            'moderate' => 'Moderately Food Insecure',
            'severe' => 'Severely Food Insecure',
        ];
    }
}

if (!function_exists('nutrition_report_snapshot')) {
    /**
     * @return array<string, mixed>
     */
    function nutrition_report_snapshot(mysqli $con, string $barangayId, string $barangayName): array
    {
        return [
            'totals' => nutrition_scoped_totals($con, $barangayId),
            'settings' => nutrition_load_settings($con, $barangayId, $barangayName),
            'household_surveys' => nutrition_list_household_surveys($con, $barangayId, 10),
            'barangay_surveys' => nutrition_list_barangay_surveys($con, $barangayId, 5),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}

if (!function_exists('nutrition_purok_code_from_label')) {
    function nutrition_purok_code_from_label(string $purok): string
    {
        $purok = trim($purok);
        if ($purok === '') {
            return 'P1';
        }
        $core = preg_replace('/^(purok|prk)\s*/i', '', $purok) ?? $purok;
        $core = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $core));
        if ($core === '' || $core === '0') {
            return 'P1';
        }
        if (str_starts_with($core, 'P') && strlen($core) > 1) {
            return $core;
        }

        return 'P' . $core;
    }
}

if (!function_exists('nutrition_purok_label_from_number')) {
    /** Accepts purok numbers and letters (1, 1A, A). */
    function nutrition_purok_label_from_number(string $purokNumber): string
    {
        $purokNumber = trim((string) preg_replace('/\s+/', ' ', $purokNumber));
        if ($purokNumber === '') {
            return '';
        }
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\s.\-\/]{0,31}$/u', $purokNumber)) {
            return '';
        }
        $core = preg_replace('/^(purok|prk)\s*/i', '', $purokNumber) ?? $purokNumber;
        $core = trim($core);
        if ($core === '') {
            return '';
        }
        if (preg_match('/^P([A-Za-z0-9]+)$/i', $core, $matches)) {
            $core = $matches[1];
        }
        if (ctype_digit($core)) {
            $n = (int) $core;
            if ($n < 1) {
                return '';
            }

            return 'PUROK ' . $n;
        }

        return 'PUROK ' . mb_strtoupper($core);
    }
}

if (!function_exists('nutrition_city_psgc_code')) {
    function nutrition_city_psgc_code(): string
    {
        return NUTRITION_VALENCIA_CITY_PSGC_CODE;
    }
}

if (!function_exists('nutrition_barangay_psgc_code')) {
    function nutrition_barangay_psgc_code(mysqli $con, string $barangayId = '', string $barangayName = ''): string
    {
        return nutrition_resolve_psfc_code($con, $barangayId, $barangayName);
    }
}

if (!function_exists('nutrition_resolve_psfc_code')) {
    function nutrition_resolve_psfc_code(mysqli $con, string $barangayId = '', string $barangayName = ''): string
    {
        return barangay_resolve_psgc_code($con, $barangayId, $barangayName);
    }
}

if (!function_exists('nutrition_next_household_series')) {
    /**
     * Next household series number for a barangay (unique across all puroks).
     * Format remains PSGC-Purok-##### so purok stays visible, but ##### never restarts per purok.
     */
    function nutrition_next_household_series(mysqli $con, string $barangayId, string $psfc, string $purokCode = ''): int
    {
        if (!barangay_table_exists($con, 'nutrition_household_survey')) {
            return 1;
        }

        $psfc = trim($psfc);
        if ($psfc === '') {
            return 1;
        }

        // Barangay-wide max of the trailing 5-digit series (ignore purok for sequencing).
        $stmt = $con->prepare(
            "SELECT house_hold_id FROM nutrition_household_survey
             WHERE barangay_id = ?
               AND house_hold_id LIKE ?
               AND house_hold_id REGEXP '-[0-9]{5}$'"
        );
        if (!$stmt) {
            return 1;
        }

        $like = $psfc . '-%';
        $stmt->bind_param('ss', $barangayId, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $maxSeries = 0;
        while ($row = $result->fetch_assoc()) {
            $id = (string) ($row['house_hold_id'] ?? '');
            if (preg_match('/-(\d{5})$/', $id, $matches)) {
                $maxSeries = max($maxSeries, (int) $matches[1]);
            }
        }
        $stmt->close();

        return max(1, $maxSeries + 1);
    }
}

if (!function_exists('nutrition_format_household_reference')) {
    function nutrition_format_household_reference(string $psfc, string $purokCode, int $series): string
    {
        return $psfc . '-' . $purokCode . '-' . str_pad((string) max(1, $series), 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('nutrition_generate_household_reference')) {
    function nutrition_generate_household_reference(
        mysqli $con,
        string $barangayId,
        string $purokLabel,
        string $barangayName = ''
    ): string {
        $psfc = nutrition_resolve_psfc_code($con, $barangayId, $barangayName);
        $purokCode = nutrition_purok_code_from_label($purokLabel);
        $series = nutrition_next_household_series($con, $barangayId, $psfc, $purokCode);

        return nutrition_format_household_reference($psfc, $purokCode, $series);
    }
}

if (!function_exists('nutrition_household_reference_preview')) {
    /**
     * @return array{household_id:string,psfc_code:string,purok_code:string,series:int}
     */
    function nutrition_household_reference_preview(
        mysqli $con,
        string $barangayId,
        string $purokLabel,
        string $barangayName = ''
    ): array {
        $psfc = nutrition_resolve_psfc_code($con, $barangayId, $barangayName);
        $purokCode = nutrition_purok_code_from_label($purokLabel);
        $series = nutrition_next_household_series($con, $barangayId, $psfc, $purokCode);

        return [
            'household_id' => nutrition_format_household_reference($psfc, $purokCode, $series),
            'psfc_code' => $psfc,
            'purok_code' => $purokCode,
            'series' => $series,
        ];
    }
}

if (!function_exists('nutrition_purok_select_options')) {
    /**
     * @return array<int, array{value:string,label:string}>
     */
    function nutrition_purok_select_options(mysqli $con, string $barangayId): array
    {
        $options = [];
        $seen = [];

        foreach (barangay_list_puroks($con, $barangayId) as $row) {
            $label = trim((string) ($row['purok'] ?? ''));
            if ($label === '') {
                continue;
            }
            $key = mb_strtolower($label);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $options[] = ['value' => $label, 'label' => $label];
        }

        if ($options === []) {
            for ($i = 1; $i <= 7; $i++) {
                $label = 'PUROK ' . $i;
                $options[] = ['value' => $label, 'label' => $label];
            }
        }

        return $options;
    }
}

if (!function_exists('nutrition_yes_no_from_post')) {
    function nutrition_yes_no_from_post(string $key): string
    {
        return (($_POST[$key] ?? '') === 'YES') ? 'YES' : 'NO';
    }
}

if (!function_exists('nutrition_format_household_head_name')) {
    function nutrition_format_household_head_name(
        string $lastName,
        string $firstName,
        string $middleName = '',
        string $suffix = ''
    ): string {
        $lastName = trim($lastName);
        $firstName = trim($firstName);
        $middleName = trim($middleName);
        $suffix = trim($suffix);

        if ($lastName === '' && $firstName === '') {
            return '';
        }

        $name = $lastName !== '' ? $lastName : $firstName;
        if ($lastName !== '' && $firstName !== '') {
            $name = $lastName . ', ' . $firstName;
        }
        if ($middleName !== '') {
            $name .= ' ' . $middleName;
        }
        if ($suffix !== '') {
            $name .= ' ' . $suffix;
        }

        return trim($name);
    }
}

if (!function_exists('nutrition_normalize_name_for_match')) {
    function nutrition_normalize_name_for_match(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return strtolower($name);
    }
}

if (!function_exists('nutrition_duplicate_name_error_suffix')) {
    /**
     * @param array<string, string> $duplicate
     */
    function nutrition_duplicate_name_error_suffix(array $duplicate): string
    {
        $parts = [];
        $householdId = trim((string) ($duplicate['house_hold_id'] ?? ''));
        if ($householdId !== '') {
            $parts[] = 'Household ID: ' . $householdId;
        }

        $barangayName = trim((string) ($duplicate['barangay_name'] ?? ''));
        if ($barangayName !== '') {
            $parts[] = 'Barangay: ' . $barangayName;
        }

        if ($parts === []) {
            return '';
        }

        return ' ' . implode('. ', $parts) . '.';
    }
}

if (!function_exists('nutrition_find_duplicate_household_head')) {
    /**
     * @return array<string, string>|null
     */
    function nutrition_find_duplicate_household_head(
        mysqli $con,
        string $headLastName,
        string $headFirstName,
        string $headMiddleName = '',
        string $headSuffix = '',
        string $excludeSurveyId = ''
    ): ?array {
        if (!barangay_table_exists($con, 'nutrition_household_survey')) {
            return null;
        }

        $normLast = nutrition_normalize_name_for_match($headLastName);
        $normFirst = nutrition_normalize_name_for_match($headFirstName);
        $normMiddle = nutrition_normalize_name_for_match($headMiddleName);
        $normSuffix = nutrition_normalize_name_for_match($headSuffix);
        $formatted = nutrition_format_household_head_name(
            $headLastName,
            $headFirstName,
            $headMiddleName,
            $headSuffix
        );
        $normFormatted = nutrition_normalize_name_for_match($formatted);

        if ($normLast === '' && $normFirst === '' && $normFormatted === '') {
            return null;
        }

        $sql = 'SELECT s.survey_id, s.house_hold_id, s.household_head,
                       COALESCE(b.barangay, \'\') AS barangay_name
                FROM nutrition_household_survey s
                LEFT JOIN barangay_information b ON b.id = s.barangay_id
                WHERE 1 = 1';
        $types = '';
        $params = [];

        if ($excludeSurveyId !== '') {
            $sql .= ' AND s.survey_id <> ?';
            $types .= 's';
            $params[] = $excludeSurveyId;
        }

        $sql .= ' AND (
                    (LOWER(TRIM(s.head_last_name)) = ? AND LOWER(TRIM(s.head_first_name)) = ?
                     AND LOWER(TRIM(s.head_middle_name)) = ? AND LOWER(TRIM(s.head_suffix)) = ?)
                    OR LOWER(TRIM(s.household_head)) = ?
                  )
                  LIMIT 1';

        $types .= 'sssss';
        $params[] = $normLast;
        $params[] = $normFirst;
        $params[] = $normMiddle;
        $params[] = $normSuffix;
        $params[] = $normFormatted;

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('nutrition_find_duplicate_person_name')) {
    /**
     * @return array<string, string>|null
     */
    function nutrition_find_duplicate_person_name(
        mysqli $con,
        string $personName,
        string $excludeSurveyId = '',
        string $excludeMemberId = ''
    ): ?array {
        $personName = trim($personName);
        if ($personName === '') {
            return null;
        }

        $normName = nutrition_normalize_name_for_match($personName);
        if ($normName === '') {
            return null;
        }

        if (barangay_table_exists($con, 'nutrition_household_survey')) {
            $sql = 'SELECT s.survey_id, s.house_hold_id, s.household_head AS matched_name,
                           COALESCE(b.barangay, \'\') AS barangay_name, \'household_head\' AS match_type
                    FROM nutrition_household_survey s
                    LEFT JOIN barangay_information b ON b.id = s.barangay_id
                    WHERE LOWER(TRIM(s.household_head)) = ?';
            $types = 's';
            $params = [$normName];

            if ($excludeSurveyId !== '') {
                $sql .= ' AND s.survey_id <> ?';
                $types .= 's';
                $params[] = $excludeSurveyId;
            }

            $sql .= ' LIMIT 1';
            $stmt = $con->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $stmt->close();
                if (is_array($row)) {
                    return $row;
                }
            }
        }

        if (!barangay_table_exists($con, 'nutrition_household_family_member')) {
            return null;
        }

        $sql = 'SELECT fm.member_id, fm.survey_id, fm.member_name AS matched_name,
                       COALESCE(h.house_hold_id, \'\') AS house_hold_id,
                       COALESCE(b.barangay, \'\') AS barangay_name, \'family_member\' AS match_type
                FROM nutrition_household_family_member fm
                LEFT JOIN nutrition_household_survey h
                  ON h.survey_id = fm.survey_id AND h.barangay_id = fm.barangay_id
                LEFT JOIN barangay_information b ON b.id = fm.barangay_id
                WHERE LOWER(TRIM(fm.member_name)) = ?';
        $types = 's';
        $params = [$normName];

        if ($excludeMemberId !== '') {
            $sql .= ' AND fm.member_id <> ?';
            $types .= 's';
            $params[] = $excludeMemberId;
        }

        if ($excludeSurveyId !== '') {
            $sql .= ' AND fm.survey_id <> ?';
            $types .= 's';
            $params[] = $excludeSurveyId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('nutrition_validate_household_survey_names')) {
    /**
     * @param array<int, array<string, mixed>> $familyMembers
     */
    function nutrition_validate_household_survey_names(
        mysqli $con,
        string $headLastName,
        string $headFirstName,
        string $headMiddleName,
        string $headSuffix,
        array $familyMembers,
        string $excludeSurveyId = ''
    ): ?string {
        $duplicateHead = nutrition_find_duplicate_household_head(
            $con,
            $headLastName,
            $headFirstName,
            $headMiddleName,
            $headSuffix,
            $excludeSurveyId
        );
        if ($duplicateHead !== null) {
            return 'This household head is already recorded in the system.'
                . nutrition_duplicate_name_error_suffix($duplicateHead);
        }

        $householdHead = nutrition_format_household_head_name(
            $headLastName,
            $headFirstName,
            $headMiddleName,
            $headSuffix
        );
        $duplicateHeadName = nutrition_find_duplicate_person_name(
            $con,
            $householdHead,
            $excludeSurveyId
        );
        if ($duplicateHeadName !== null) {
            return 'This household head name is already recorded in the system.'
                . nutrition_duplicate_name_error_suffix($duplicateHeadName);
        }

        $normHead = nutrition_normalize_name_for_match($householdHead);
        $seenMembers = [];
        foreach ($familyMembers as $familyMember) {
            $memberName = trim((string) ($familyMember['member_name'] ?? ''));
            if ($memberName === '') {
                continue;
            }

            $normMember = nutrition_normalize_name_for_match($memberName);
            if ($normHead !== '' && $normMember === $normHead) {
                return 'A family member name cannot be the same as the household head.';
            }

            if (isset($seenMembers[$normMember])) {
                return 'Duplicate family member name in this form: "' . $memberName . '".';
            }
            $seenMembers[$normMember] = true;

            $duplicateMember = nutrition_find_duplicate_person_name(
                $con,
                $memberName,
                $excludeSurveyId
            );
            if ($duplicateMember !== null) {
                return 'The name "' . $memberName . '" is already recorded in the system.'
                    . nutrition_duplicate_name_error_suffix($duplicateMember);
            }
        }

        return null;
    }
}

if (!function_exists('nutrition_household_head_display')) {
    function nutrition_household_head_display(array $survey): string
    {
        $formatted = nutrition_format_household_head_name(
            (string) ($survey['head_last_name'] ?? ''),
            (string) ($survey['head_first_name'] ?? ''),
            (string) ($survey['head_middle_name'] ?? ''),
            (string) ($survey['head_suffix'] ?? '')
        );

        if ($formatted !== '') {
            return $formatted;
        }

        return trim((string) ($survey['household_head'] ?? ''));
    }
}

if (!function_exists('nutrition_household_member_badges')) {
    /**
     * @return array<int, string>
     */
    function nutrition_household_member_badges(array $survey): array
    {
        $badges = [];
        $map = [
            'is_4ps' => '4Ps',
            'is_pwd' => 'PWD',
            'is_ip' => 'IP',
            'is_solo_parent' => 'Solo Parent',
        ];

        foreach ($map as $key => $label) {
            if (strtoupper((string) ($survey[$key] ?? 'NO')) === 'YES') {
                $badges[] = $label;
            }
        }

        return $badges;
    }
}

if (!function_exists('nutrition_ensure_super_admin_for_manage')) {
    function nutrition_ensure_super_admin_for_manage(mysqli $con, string $userId): void
    {
        if (!nutrition_user_can_delete_household_surveys($con, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'You are not allowed to delete household survey records.']);
            exit;
        }
    }
}

if (!function_exists('nutrition_ensure_can_edit_household_survey_names')) {
    function nutrition_ensure_can_edit_household_survey_names(mysqli $con, string $userId): void
    {
        if (!nutrition_user_can_edit_household_survey_names($con, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'You are not allowed to edit household survey names.']);
            exit;
        }
    }
}

if (!function_exists('nutrition_ensure_can_add_household_surveys')) {
    function nutrition_ensure_can_add_household_surveys(mysqli $con, string $userId): void
    {
        if (!nutrition_user_can_add_household_surveys($con, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'You are not allowed to add household surveys.']);
            exit;
        }
    }
}

if (!function_exists('nutrition_ensure_can_edit_household_surveys')) {
    function nutrition_ensure_can_edit_household_surveys(mysqli $con, string $userId): void
    {
        if (!nutrition_user_can_edit_household_surveys($con, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Only Nutrition Super Admin or Developer Accounts can edit registered household surveys.']);
            exit;
        }
    }
}

if (!function_exists('nutrition_load_household_survey_by_id')) {
    function nutrition_load_household_survey_by_id(mysqli $con, string $surveyId, string $barangayId): ?array
    {
        if ($surveyId === '' || $barangayId === '' || !barangay_table_exists($con, 'nutrition_household_survey')) {
            return null;
        }

        $stmt = $con->prepare('SELECT * FROM nutrition_household_survey WHERE survey_id = ? AND barangay_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('ss', $surveyId, $barangayId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }
}

if (!function_exists('nutrition_update_household_head_names')) {
    function nutrition_update_household_head_names(
        mysqli $con,
        string $surveyId,
        string $barangayId,
        string $headLastName,
        string $headFirstName,
        string $headMiddleName,
        string $headSuffix
    ): bool {
        $headLastName = trim($headLastName);
        $headFirstName = trim($headFirstName);
        $headMiddleName = trim($headMiddleName);
        $headSuffix = trim($headSuffix);

        if ($headLastName === '' || $headFirstName === '') {
            return false;
        }

        if (nutrition_load_household_survey_by_id($con, $surveyId, $barangayId) === null) {
            return false;
        }

        $householdHead = nutrition_format_household_head_name(
            $headLastName,
            $headFirstName,
            $headMiddleName,
            $headSuffix
        );

        $stmt = $con->prepare(
            'UPDATE nutrition_household_survey
             SET head_last_name = ?, head_first_name = ?, head_middle_name = ?, head_suffix = ?, household_head = ?
             WHERE survey_id = ? AND barangay_id = ?'
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            'sssssss',
            $headLastName,
            $headFirstName,
            $headMiddleName,
            $headSuffix,
            $householdHead,
            $surveyId,
            $barangayId
        );
        $stmt->execute();
        $ok = $stmt->affected_rows >= 0;
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('nutrition_update_family_member_name')) {
    function nutrition_update_family_member_name(
        mysqli $con,
        string $memberId,
        string $barangayId,
        string $memberName
    ): bool {
        $memberName = trim($memberName);
        if ($memberId === '' || $barangayId === '' || $memberName === '') {
            return false;
        }

        if (!barangay_table_exists($con, 'nutrition_household_family_member')) {
            return false;
        }

        $stmt = $con->prepare(
            'UPDATE nutrition_household_family_member
             SET member_name = ?
             WHERE member_id = ? AND barangay_id = ?'
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sss', $memberName, $memberId, $barangayId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('nutrition_delete_household_survey')) {
    function nutrition_delete_household_survey(mysqli $con, string $surveyId, string $barangayId): bool
    {
        if (nutrition_load_household_survey_by_id($con, $surveyId, $barangayId) === null) {
            return false;
        }

        if (barangay_table_exists($con, 'nutrition_household_family_member')) {
            $stmtMembers = $con->prepare(
                'DELETE FROM nutrition_household_family_member WHERE survey_id = ? AND barangay_id = ?'
            );
            if ($stmtMembers) {
                $stmtMembers->bind_param('ss', $surveyId, $barangayId);
                $stmtMembers->execute();
                $stmtMembers->close();
            }
        }

        $stmt = $con->prepare('DELETE FROM nutrition_household_survey WHERE survey_id = ? AND barangay_id = ?');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $surveyId, $barangayId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('nutrition_relationship_options')) {
    /**
     * @return array<int, string>
     */
    function nutrition_relationship_options(): array
    {
        return [
            'Spouse',
            'Common-law Husband',
            'Common-law Wife',
            'Son',
            'Daughter',
            'Father',
            'Mother',
            'Brother',
            'Sister',
            'Grandchild',
            'Grandparent',
            'Nephew',
            'Niece',
            'Other Relative',
        ];
    }
}

if (!function_exists('nutrition_build_household_survey_edit_payload')) {
    /**
     * Full survey payload for web edit + mobile GET detail (ISO dates for API clients).
     *
     * @param array<string, mixed> $survey
     * @param array<int, array<string, mixed>> $members
     * @return array<string, mixed>
     */
    function nutrition_build_household_survey_edit_payload(array $survey, array $members, bool $mdyDates = false): array
    {
        $fmt = static function ($ymd) use ($mdyDates): string {
            $ymd = trim((string) $ymd);
            if ($ymd === '' || $ymd === '0000-00-00') {
                return '';
            }
            if (!$mdyDates) {
                $normalized = nutrition_normalize_date_to_ymd($ymd);

                return $normalized ?? $ymd;
            }
            if (function_exists('nutrition_format_date_mdy')) {
                return (string) nutrition_format_date_mdy($ymd);
            }
            $ts = strtotime($ymd);

            return $ts ? date('m/d/Y', $ts) : '';
        };

        $yes = static fn($v): bool => strtoupper((string) ($v ?? 'NO')) === 'YES';

        $memberPayload = [];
        foreach ($members as $member) {
            $relParts = nutrition_split_member_relationship((string) ($member['relationship'] ?? ''));
            $memberPayload[] = [
                'member_id' => (string) ($member['member_id'] ?? ''),
                'member_name' => (string) ($member['member_name'] ?? ''),
                'relationship' => $relParts['relationship'],
                'relationship_other' => $relParts['relationship_other'],
                'gender' => (string) ($member['gender'] ?? ''),
                'birth_date' => $fmt($member['birth_date'] ?? ''),
                'weight_kg' => $member['weight_kg'] ?? '',
                'height_cm' => $member['height_cm'] ?? '',
                'date_measured' => $fmt($member['date_measured'] ?? ''),
                'prev_weight_kg' => $member['prev_weight_kg'] ?? '',
                'prev_height_cm' => $member['prev_height_cm'] ?? '',
                'prev_date_measured' => $fmt($member['prev_date_measured'] ?? ''),
                'age_months' => $member['age_months'] ?? '',
                'weight_for_age' => (string) ($member['weight_for_age'] ?? ''),
                'height_for_age' => (string) ($member['height_for_age'] ?? ''),
                'weight_for_height' => (string) ($member['weight_for_height'] ?? ''),
                'is_pregnant' => $yes($member['is_pregnant'] ?? 'NO'),
                'is_lactating' => $yes($member['is_lactating'] ?? 'NO'),
                'pregnancy_months' => $member['pregnancy_months'] ?? '',
                'pregnant_nutrition_status' => (string) ($member['pregnant_nutrition_status'] ?? ''),
                'planned_exclusive_breastfeeding' => $yes($member['planned_exclusive_breastfeeding'] ?? 'NO'),
                'planned_mixed_feeding' => $yes($member['planned_mixed_feeding'] ?? 'NO'),
                'planned_bottle_feeding' => $yes($member['planned_bottle_feeding'] ?? 'NO'),
                'planned_other_feeding' => $yes($member['planned_other_feeding'] ?? 'NO'),
                'planned_other_specify' => (string) ($member['planned_other_specify'] ?? ''),
                'lactating_exclusive_breastfeeding' => $yes($member['lactating_exclusive_breastfeeding'] ?? 'NO'),
                'lactating_mixed_feeding' => $yes($member['lactating_mixed_feeding'] ?? 'NO'),
                'lactating_bottle_feeding' => $yes($member['lactating_bottle_feeding'] ?? 'NO'),
                'lactating_other_feeding' => $yes($member['lactating_other_feeding'] ?? 'NO'),
                'lactating_other_specify' => (string) ($member['lactating_other_specify'] ?? ''),
            ];
        }

        $purokLabel = (string) ($survey['purok_label'] ?? '');
        $purokInput = function_exists('nutrition_purok_input_from_label')
            ? (string) (nutrition_purok_input_from_label($purokLabel) ?: $purokLabel)
            : $purokLabel;

        $fpMethods = (string) ($survey['family_planning_methods'] ?? '');
        $fpList = [];
        $fpOther = '';
        if ($fpMethods !== '') {
            foreach (explode(',', $fpMethods) as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                if (strcasecmp($part, 'Others') === 0 || str_starts_with($part, 'Others:')) {
                    $fpList[] = 'Others';
                    if (str_starts_with($part, 'Others:')) {
                        $fpOther = trim(substr($part, strlen('Others:')));
                    }
                    continue;
                }
                $fpList[] = $part;
            }
        }

        return [
            'existing_survey_id' => (string) ($survey['survey_id'] ?? ''),
            'survey_id' => (string) ($survey['survey_id'] ?? ''),
            'residence_id' => (string) ($survey['residence_id'] ?? ''),
            'house_hold_id' => (string) ($survey['house_hold_id'] ?? ''),
            'purok_label' => $purokLabel,
            'purok_number' => $purokInput,
            'purok_input' => $purokInput,
            'survey_date' => $fmt($survey['survey_date'] ?? ''),
            'bns_name' => (string) ($survey['bns_name'] ?? ''),
            'head_last_name' => (string) ($survey['head_last_name'] ?? ''),
            'head_first_name' => (string) ($survey['head_first_name'] ?? ''),
            'head_middle_name' => (string) ($survey['head_middle_name'] ?? ''),
            'head_suffix' => (string) ($survey['head_suffix'] ?? ''),
            'birth_date' => $fmt($survey['birth_date'] ?? ''),
            'gender' => (string) ($survey['gender'] ?? ''),
            'occupation' => (string) ($survey['occupation'] ?? ''),
            'is_4ps' => $yes($survey['is_4ps'] ?? 'NO'),
            'is_pwd' => $yes($survey['is_pwd'] ?? 'NO'),
            'is_ip' => $yes($survey['is_ip'] ?? 'NO'),
            'is_solo_parent' => $yes($survey['is_solo_parent'] ?? 'NO'),
            'is_na_member' => $yes($survey['is_na_member'] ?? 'NO'),
            'head_is_pregnant' => $yes($survey['head_is_pregnant'] ?? 'NO'),
            'head_is_lactating' => $yes($survey['head_is_lactating'] ?? 'NO'),
            'head_pregnancy_months' => $survey['head_pregnancy_months'] ?? '',
            'head_pregnant_nutrition_status' => (string) ($survey['head_pregnant_nutrition_status'] ?? ''),
            'head_planned_exclusive_breastfeeding' => $yes($survey['head_planned_exclusive_breastfeeding'] ?? 'NO'),
            'head_planned_mixed_feeding' => $yes($survey['head_planned_mixed_feeding'] ?? 'NO'),
            'head_planned_bottle_feeding' => $yes($survey['head_planned_bottle_feeding'] ?? 'NO'),
            'head_planned_other_feeding' => $yes($survey['head_planned_other_feeding'] ?? 'NO'),
            'head_planned_other_specify' => (string) ($survey['head_planned_other_specify'] ?? ''),
            'head_lactating_exclusive_breastfeeding' => $yes($survey['head_lactating_exclusive_breastfeeding'] ?? 'NO'),
            'head_lactating_mixed_feeding' => $yes($survey['head_lactating_mixed_feeding'] ?? 'NO'),
            'head_lactating_bottle_feeding' => $yes($survey['head_lactating_bottle_feeding'] ?? 'NO'),
            'head_lactating_other_feeding' => $yes($survey['head_lactating_other_feeding'] ?? 'NO'),
            'head_lactating_other_specify' => (string) ($survey['head_lactating_other_specify'] ?? ''),
            'house_ownership' => (string) ($survey['house_ownership'] ?? ''),
            'house_ownership_other' => (string) ($survey['house_ownership_other'] ?? ''),
            'toilet_type' => (string) ($survey['toilet_type'] ?? ''),
            'garbage_disposal' => (string) ($survey['garbage_disposal'] ?? ''),
            'garbage_uncollected_type' => (string) ($survey['garbage_uncollected_type'] ?? ''),
            'water_source' => (string) ($survey['water_source'] ?? ''),
            'dwelling_type' => (string) ($survey['dwelling_type'] ?? ''),
            'food_production' => (string) ($survey['food_production'] ?? ''),
            'food_production_activities' => nutrition_prf_parse_food_production((string) ($survey['food_production'] ?? '')),
            'uses_iodized_salt' => $yes($survey['uses_iodized_salt'] ?? 'NO'),
            'uses_sangkap_pinoy' => $yes($survey['uses_sangkap_pinoy'] ?? 'NO'),
            'has_carenderia' => $yes($survey['has_carenderia'] ?? 'NO'),
            'has_sari_sari_store' => $yes($survey['has_sari_sari_store'] ?? 'NO'),
            'practices_family_planning' => $yes($survey['practices_family_planning'] ?? 'NO'),
            'family_planning_methods' => $fpMethods,
            'family_planning_methods_list' => $fpList,
            'family_planning_methods_other' => $fpOther,
            'complementary_meals' => (string) ($survey['complementary_meals'] ?? ''),
            'complementary_meals_other' => (string) ($survey['complementary_meals_other'] ?? ''),
            'complementary_snacks' => (string) ($survey['complementary_snacks'] ?? ''),
            'complementary_snacks_other' => (string) ($survey['complementary_snacks_other'] ?? ''),
            'child_physical_activity' => (string) ($survey['child_physical_activity'] ?? ''),
            'child_physical_activity_other' => (string) ($survey['child_physical_activity_other'] ?? ''),
            'remarks' => (string) ($survey['remarks'] ?? ''),
            'family_members' => $memberPayload,
        ];
    }
}

if (!function_exists('nutrition_relationship_other_label')) {
    function nutrition_relationship_other_label(): string
    {
        return 'Other Relative';
    }
}

if (!function_exists('nutrition_compose_member_relationship')) {
    /**
     * Store Other Relative detail in the relationship column (no schema change).
     * Example: "Other Relative: Cousin".
     */
    function nutrition_compose_member_relationship(string $relationship, string $otherSpecify = ''): string
    {
        $relationship = trim($relationship);
        $otherSpecify = trim($otherSpecify);
        $otherLabel = nutrition_relationship_other_label();
        $options = nutrition_relationship_options();

        if ($relationship === $otherLabel || ($relationship !== '' && !in_array($relationship, $options, true))) {
            if ($otherSpecify !== '') {
                // Avoid double prefix if user typed the label again.
                if (stripos($otherSpecify, $otherLabel . ':') === 0) {
                    return trim($otherSpecify);
                }

                return $otherLabel . ': ' . $otherSpecify;
            }

            return $relationship === '' ? $otherLabel : $relationship;
        }

        return $relationship;
    }
}

if (!function_exists('nutrition_split_member_relationship')) {
    /**
     * @return array{relationship:string,relationship_other:string}
     */
    function nutrition_split_member_relationship(string $stored): array
    {
        $stored = trim($stored);
        $otherLabel = nutrition_relationship_other_label();
        $options = nutrition_relationship_options();

        if ($stored === '') {
            return ['relationship' => '', 'relationship_other' => ''];
        }

        if (preg_match('/^' . preg_quote($otherLabel, '/') . '\s*:\s*(.+)$/iu', $stored, $m)) {
            return [
                'relationship' => $otherLabel,
                'relationship_other' => trim((string) ($m[1] ?? '')),
            ];
        }

        if ($stored === $otherLabel) {
            return ['relationship' => $otherLabel, 'relationship_other' => ''];
        }

        if (in_array($stored, $options, true)) {
            return ['relationship' => $stored, 'relationship_other' => ''];
        }

        // Legacy free-text values → treat as Other Relative specify.
        return [
            'relationship' => $otherLabel,
            'relationship_other' => $stored,
        ];
    }
}

if (!function_exists('nutrition_prf_house_ownership_options')) {
    /** @return array<int, string> */
    function nutrition_prf_house_ownership_options(): array
    {
        return ['Owned', 'Rented', 'Others'];
    }
}

if (!function_exists('nutrition_prf_toilet_sanitary_options')) {
    /** @return array<int, string> BSI Type of Sanitary Facility */
    function nutrition_prf_toilet_sanitary_options(): array
    {
        return [
            'Pour/Flush type with septic tank',
            'Pour Flush Toilet connected to septic tank and sewerage system',
            'Ventilated Pit (VIP) Latrine',
        ];
    }
}

if (!function_exists('nutrition_prf_toilet_unsanitary_options')) {
    /** @return array<int, string> BSI Type of Unsanitary Facility */
    function nutrition_prf_toilet_unsanitary_options(): array
    {
        return [
            'Water-sealed toilet w/o septic tank',
            'Overhung Latrine',
            'Open Pit Latrine',
            'Without toilet',
        ];
    }
}

if (!function_exists('nutrition_prf_toilet_options')) {
    /** @return array<int, string> Full BSI toilet facility list for household survey */
    function nutrition_prf_toilet_options(): array
    {
        return array_merge(
            nutrition_prf_toilet_sanitary_options(),
            nutrition_prf_toilet_unsanitary_options()
        );
    }
}

if (!function_exists('nutrition_prf_normalize_toilet_type')) {
    /**
     * Map legacy PRF toilet labels to BSI sanitary/unsanitary labels.
     */
    function nutrition_prf_normalize_toilet_type(string $toiletType): string
    {
        $toiletType = trim($toiletType);
        if ($toiletType === '') {
            return '';
        }
        if (in_array($toiletType, nutrition_prf_toilet_options(), true)) {
            return $toiletType;
        }
        $legacy = [
            'Water Sealed' => 'Pour/Flush type with septic tank',
            'Covered Pit' => 'Ventilated Pit (VIP) Latrine',
            'Open Pit' => 'Open Pit Latrine',
            'No Toilet' => 'Without toilet',
        ];

        return $legacy[$toiletType] ?? $toiletType;
    }
}

if (!function_exists('nutrition_prf_garbage_options')) {
    /** @return array<int, string> */
    function nutrition_prf_garbage_options(): array
    {
        return ['Collected/Segregated', 'Uncollected'];
    }
}

if (!function_exists('nutrition_prf_normalize_garbage_disposal')) {
    function nutrition_prf_normalize_garbage_disposal(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strcasecmp($value, 'Collected') === 0) {
            return $value === '' ? '' : 'Collected/Segregated';
        }
        if (in_array($value, nutrition_prf_garbage_options(), true)) {
            return $value;
        }

        return $value;
    }
}

if (!function_exists('nutrition_prf_garbage_uncollected_options')) {
    /** @return array<int, string> */
    function nutrition_prf_garbage_uncollected_options(): array
    {
        return [
            'Burning segregated',
            'Burning unsegregated',
            'Dumping segregated',
            'Dumping unsegregated',
            'Composting segregated',
            'Composting unsegregated',
        ];
    }
}

if (!function_exists('nutrition_prf_water_source_options')) {
    /** @return array<int, string> */
    function nutrition_prf_water_source_options(): array
    {
        return [
            'Pipe Water System (level III)',
            'Communal source piped water system',
            'Mineral water/water dispensing stores',
            'Well',
            'Spring',
        ];
    }
}

if (!function_exists('nutrition_prf_normalize_water_source')) {
    /**
     * Map legacy short water labels to Form C1 drinking-water labels.
     */
    function nutrition_prf_normalize_water_source(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (in_array($value, nutrition_prf_water_source_options(), true)) {
            return $value;
        }
        $legacy = [
            'Pipe Water System' => 'Pipe Water System (level III)',
            'Communal Water Source' => 'Communal source piped water system',
            'Mineral' => 'Mineral water/water dispensing stores',
            'Mineral water' => 'Mineral water/water dispensing stores',
        ];

        return $legacy[$value] ?? $value;
    }
}

if (!function_exists('nutrition_prf_dwelling_options')) {
    /** @return array<int, string> */
    function nutrition_prf_dwelling_options(): array
    {
        return ['Concrete', 'Semi-concrete', 'Wood', 'Makeshift/Barong-barong'];
    }
}

if (!function_exists('nutrition_prf_food_production_options')) {
    /** @return array<int, string> */
    function nutrition_prf_food_production_options(): array
    {
        return [
            'Vege Garden, Fruit Tree, Poultry, Livestock & Fishpond',
            'Vege Garden, Fruit Tree, Poultry & Livestock',
            'Vege Garden, Fruit Tree & Poultry',
            'Vege Garden & Fruit Tree',
            'Vege Garden',
            'Fruit Tree, Poultry, Livestock & Fishpond',
            'Fruit Tree, Poultry & Livestock',
            'Fruit Tree & Poultry',
            'Fruit Trees',
            'Poultry, Livestock & Fishpond',
            'Poultry & Livestock',
            'Poultry',
            'Livestock & Fishpond',
            'Livestock',
            'Fishpond',
        ];
    }
}

if (!function_exists('nutrition_prf_food_production_activity_options')) {
    /** @return array<int, string> */
    function nutrition_prf_food_production_activity_options(): array
    {
        return [
            'Vege Garden',
            'Fruit Trees',
            'Poultry',
            'Livestock',
            'Fishpond',
        ];
    }
}

if (!function_exists('nutrition_prf_food_production_from_post')) {
    function nutrition_prf_food_production_from_post(): string
    {
        return nutrition_prf_methods_from_post(
            'food_production_activities',
            nutrition_prf_food_production_activity_options()
        );
    }
}

if (!function_exists('nutrition_prf_parse_food_production')) {
    /**
     * @return array<int, string>
     */
    function nutrition_prf_parse_food_production(string $stored): array
    {
        $stored = trim($stored);
        $activities = nutrition_prf_food_production_activity_options();
        if ($stored === '') {
            return [];
        }

        if (in_array($stored, nutrition_prf_food_production_options(), true)) {
            return nutrition_prf_legacy_food_production_to_activities($stored);
        }

        $picked = [];
        foreach (explode(',', $stored) as $part) {
            $part = trim($part);
            if (in_array($part, $activities, true)) {
                $picked[] = $part;
            }
        }

        return array_values(array_unique($picked));
    }
}

if (!function_exists('nutrition_prf_legacy_food_production_to_activities')) {
    /**
     * @return array<int, string>
     */
    function nutrition_prf_legacy_food_production_to_activities(string $legacy): array
    {
        $found = [];
        $needles = [
            'Vege Garden' => ['Vege Garden', 'Vege'],
            'Fruit Trees' => ['Fruit Tree', 'Fruit Trees'],
            'Poultry' => ['Poultry'],
            'Livestock' => ['Livestock'],
            'Fishpond' => ['Fishpond'],
        ];

        foreach ($needles as $activity => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($legacy, $pattern) !== false) {
                    $found[] = $activity;
                    break;
                }
            }
        }

        return array_values(array_unique($found));
    }
}

if (!function_exists('nutrition_prf_format_food_production')) {
    function nutrition_prf_format_food_production(string $stored): string
    {
        $activities = nutrition_prf_parse_food_production($stored);

        return $activities !== [] ? implode(', ', $activities) : trim($stored);
    }
}

if (!function_exists('nutrition_prf_family_planning_method_options')) {
    /** @return array<int, string> */
    function nutrition_prf_family_planning_method_options(): array
    {
        return ['Natural', 'IUD', 'Pills', 'Ligation', 'Vasectomy', 'Depo', 'Implanon Implant', 'Condom', 'BTL', 'DMPA', 'LAM', 'Others'];
    }
}

if (!function_exists('nutrition_prf_family_planning_methods_from_post')) {
    /**
     * Comma-separated FP methods. Others may include a specify suffix: "Others: …".
     */
    function nutrition_prf_family_planning_methods_from_post(): string
    {
        $methods = nutrition_prf_methods_from_post(
            'family_planning_methods',
            nutrition_prf_family_planning_method_options()
        );
        if ($methods === '') {
            return '';
        }

        $parts = [];
        $hasOthers = false;
        foreach (explode(',', $methods) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if ($part === 'Others' || str_starts_with($part, 'Others:')) {
                $hasOthers = true;
                continue;
            }
            $parts[] = $part;
        }

        if ($hasOthers) {
            $other = trim((string) ($_POST['family_planning_methods_other'] ?? ''));
            $parts[] = $other !== '' ? ('Others: ' . $other) : 'Others';
        }

        return implode(', ', $parts);
    }
}

if (!function_exists('nutrition_prf_complementary_meal_options')) {
    /** @return array<int, string> */
    function nutrition_prf_complementary_meal_options(): array
    {
        return [
            'Rice/Lugaw, Fish/Meat/Legumes, Vegetables, Fruits & Water',
            'Rice/Lugaw, Fish, Vegetable & Fruits',
            'Rice/Lugaw, Fish & Vegetable',
            'Rice/Lugaw & Fish',
            'Rice/Lugaw',
            'Rice/Processed Foods (Cold Cuts, Canned Goods, Instant Noodles)',
            'Others',
        ];
    }
}

if (!function_exists('nutrition_prf_complementary_snack_options')) {
    /** @return array<int, string> */
    function nutrition_prf_complementary_snack_options(): array
    {
        return [
            'Boiled Camote, Boiled Banana, Boiled Cassava, etc.',
            'Fried Camote, Banana Cue, Fried Cassava chips, etc.',
            'Biscuits, Bread, cookies',
            'Cerelac',
            'Nutripak',
            'Others',
        ];
    }
}

if (!function_exists('nutrition_prf_physical_activity_options')) {
    /** @return array<int, string> */
    function nutrition_prf_physical_activity_options(): array
    {
        return [
            'Outdoor (biking, ball games, etc)',
            'Computer or mobile games',
            'Others',
        ];
    }
}

if (!function_exists('nutrition_prf_pregnant_status_options')) {
    /** @return array<int, string> */
    function nutrition_prf_pregnant_status_options(): array
    {
        return ['Normal', 'Teenage', 'Underweight', 'Overweight', 'Old Age'];
    }
}

if (!function_exists('nutrition_prf_pick_option')) {
    function nutrition_prf_pick_option(string $value, array $options): string
    {
        $value = trim($value);

        return in_array($value, $options, true) ? $value : '';
    }
}

if (!function_exists('nutrition_prf_methods_from_post')) {
    function nutrition_prf_methods_from_post(string $key, array $options): string
    {
        $raw = $_POST[$key] ?? [];
        if (!is_array($raw)) {
            return '';
        }
        $picked = [];
        foreach ($raw as $item) {
            $item = trim((string) $item);
            if (in_array($item, $options, true)) {
                $picked[] = $item;
            }
        }

        return implode(', ', array_values(array_unique($picked)));
    }
}

if (!function_exists('nutrition_family_member_yes_no')) {
    function nutrition_family_member_yes_no(array $row, string $key): string
    {
        return (($row[$key] ?? '') === 'YES') ? 'YES' : 'NO';
    }
}

if (!function_exists('nutrition_parse_family_members_from_post')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function nutrition_parse_family_members_from_post(array $post, ?string $referenceDate = null): array
    {
        $raw = $post['family_members'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $referenceDate = nutrition_normalize_date_to_ymd(
            trim((string) ($referenceDate ?? ($post['survey_date'] ?? date('Y-m-d'))))
        ) ?? date('Y-m-d');
        $members = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['member_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $isPregnant = nutrition_family_member_yes_no($row, 'is_pregnant');
            $isLactating = nutrition_family_member_yes_no($row, 'is_lactating');
            $birthDateValue = nutrition_normalize_date_to_ymd(trim((string) ($row['birth_date'] ?? '')));
            $gender = trim((string) ($row['gender'] ?? ''));
            $gender = in_array($gender, ['Male', 'Female'], true) ? $gender : '';
            if ($gender !== 'Female') {
                $isPregnant = 'NO';
                $isLactating = 'NO';
            }
            $weightKg = max(0, (float) ($row['weight_kg'] ?? 0));
            $heightCm = max(0, (float) ($row['height_cm'] ?? 0));
            $dateMeasured = nutrition_normalize_date_to_ymd(trim((string) ($row['date_measured'] ?? '')));
            $prevWeightKg = max(0, (float) ($row['prev_weight_kg'] ?? 0));
            $prevHeightCm = max(0, (float) ($row['prev_height_cm'] ?? 0));
            $prevDateMeasured = nutrition_normalize_date_to_ymd(trim((string) ($row['prev_date_measured'] ?? '')));
            if ($dateMeasured === null) {
                $dateMeasured = $referenceDate;
            }
            $memberReferenceDate = $dateMeasured !== null && $dateMeasured !== '' ? $dateMeasured : $referenceDate;
            $ageMonthsOnly = nutrition_age_in_months($birthDateValue, $memberReferenceDate);
            if (!nutrition_is_child_0_to_5($ageMonthsOnly)) {
                $weightKg = 0;
                $heightCm = 0;
                $dateMeasured = null;
                $prevWeightKg = 0;
                $prevHeightCm = 0;
                $prevDateMeasured = null;
            }
            // Previous date must not be after present measurement date.
            if ($prevDateMeasured !== null && $dateMeasured !== null && $prevDateMeasured > $dateMeasured) {
                $prevDateMeasured = null;
                $prevWeightKg = 0;
                $prevHeightCm = 0;
            }
            if ($prevWeightKg <= 0 && $prevHeightCm <= 0) {
                $prevDateMeasured = null;
            }
            $growth = nutrition_family_member_growth_assessment(
                $gender,
                $birthDateValue,
                $weightKg,
                $heightCm,
                $memberReferenceDate !== '' ? $memberReferenceDate : null
            );

            $members[] = [
                'member_name' => $name,
                'relationship' => nutrition_compose_member_relationship(
                    trim((string) ($row['relationship'] ?? '')),
                    trim((string) ($row['relationship_other'] ?? ''))
                ),
                'gender' => $gender,
                'birth_date' => $birthDateValue,
                'weight_kg' => $weightKg > 0 ? $weightKg : null,
                'height_cm' => $heightCm > 0 ? $heightCm : null,
                'date_measured' => ($dateMeasured !== null && $dateMeasured !== '' && ($weightKg > 0 || $heightCm > 0)) ? $dateMeasured : null,
                'prev_weight_kg' => $prevWeightKg > 0 ? $prevWeightKg : null,
                'prev_height_cm' => $prevHeightCm > 0 ? $prevHeightCm : null,
                'prev_date_measured' => ($prevDateMeasured !== null && $prevDateMeasured !== '' && ($prevWeightKg > 0 || $prevHeightCm > 0))
                    ? $prevDateMeasured
                    : null,
                'age_months' => $growth['age_months'],
                'weight_for_age' => (string) ($growth['weight_for_age'] ?? ''),
                'height_for_age' => (string) ($growth['height_for_age'] ?? ''),
                'weight_for_height' => (string) ($growth['weight_for_height'] ?? ''),
                'is_pregnant' => $isPregnant,
                'is_lactating' => $isLactating,
                'pregnancy_months' => $isPregnant === 'YES' ? max(0, (int) ($row['pregnancy_months'] ?? 0)) : null,
                'pregnant_nutrition_status' => $isPregnant === 'YES'
                    ? nutrition_prf_pick_option((string) ($row['pregnant_nutrition_status'] ?? ''), nutrition_prf_pregnant_status_options())
                    : '',
                'planned_exclusive_breastfeeding' => nutrition_family_member_yes_no($row, 'planned_exclusive_breastfeeding'),
                'planned_mixed_feeding' => nutrition_family_member_yes_no($row, 'planned_mixed_feeding'),
                'planned_bottle_feeding' => nutrition_family_member_yes_no($row, 'planned_bottle_feeding'),
                'planned_other_feeding' => nutrition_family_member_yes_no($row, 'planned_other_feeding'),
                'planned_other_specify' => trim((string) ($row['planned_other_specify'] ?? '')),
                'lactating_exclusive_breastfeeding' => nutrition_family_member_yes_no($row, 'lactating_exclusive_breastfeeding'),
                'lactating_mixed_feeding' => nutrition_family_member_yes_no($row, 'lactating_mixed_feeding'),
                'lactating_bottle_feeding' => nutrition_family_member_yes_no($row, 'lactating_bottle_feeding'),
                'lactating_other_feeding' => nutrition_family_member_yes_no($row, 'lactating_other_feeding'),
                'lactating_other_specify' => trim((string) ($row['lactating_other_specify'] ?? '')),
            ];
        }

        return $members;
    }
}

if (!function_exists('nutrition_save_household_family_members')) {
    /**
     * @param array<int, array<string, mixed>> $members
     */
    function nutrition_save_household_family_members(
        mysqli $con,
        string $surveyId,
        string $barangayId,
        array $members
    ): int {
        if ($members === [] || !barangay_table_exists($con, 'nutrition_household_family_member')) {
            return 0;
        }

        $stmt = $con->prepare(
            'INSERT INTO nutrition_household_family_member
             (member_id, survey_id, barangay_id, member_name, relationship, gender, birth_date, weight_kg, height_cm,
              date_measured, prev_weight_kg, prev_height_cm, prev_date_measured, age_months, weight_for_age, height_for_age, weight_for_height, is_pregnant, is_lactating, pregnancy_months,
              pregnant_nutrition_status,
              planned_exclusive_breastfeeding, planned_mixed_feeding, planned_bottle_feeding, planned_other_feeding,
              planned_other_specify, lactating_exclusive_breastfeeding, lactating_mixed_feeding,
              lactating_bottle_feeding, lactating_other_feeding, lactating_other_specify, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return 0;
        }

        $saved = 0;
        foreach ($members as $index => $member) {
            $memberId = (string) hexdec(uniqid());
            $pregnancyMonths = $member['pregnancy_months'];
            $pregnancyMonthsValue = $pregnancyMonths !== null ? (int) $pregnancyMonths : null;
            $ageMonths = $member['age_months'];
            $ageMonthsValue = $ageMonths !== null ? (int) $ageMonths : null;
            $weightKg = $member['weight_kg'];
            $heightCm = $member['height_cm'];
            $weightKgValue = $weightKg !== null ? (float) $weightKg : null;
            $heightCmValue = $heightCm !== null ? (float) $heightCm : null;
            $dateMeasured = trim((string) ($member['date_measured'] ?? ''));
            $dateMeasuredValue = $dateMeasured !== '' ? $dateMeasured : null;
            $prevWeightKg = $member['prev_weight_kg'] ?? null;
            $prevHeightCm = $member['prev_height_cm'] ?? null;
            $prevWeightKgValue = $prevWeightKg !== null ? (float) $prevWeightKg : null;
            $prevHeightCmValue = $prevHeightCm !== null ? (float) $prevHeightCm : null;
            $prevDateMeasured = trim((string) ($member['prev_date_measured'] ?? ''));
            $prevDateMeasuredValue = $prevDateMeasured !== '' ? $prevDateMeasured : null;
            $sortOrder = (int) $index;
            $pregnantNutritionStatus = (string) ($member['pregnant_nutrition_status'] ?? '');

            // Nullable numeric fields are bound as strings to avoid PHP 8 bind_param NULL issues.
            $ageMonthsBind = $ageMonthsValue === null ? null : (string) $ageMonthsValue;
            $pregnancyMonthsBind = $pregnancyMonthsValue === null ? null : (string) $pregnancyMonthsValue;
            $weightKgBind = $weightKgValue === null ? null : (string) $weightKgValue;
            $heightCmBind = $heightCmValue === null ? null : (string) $heightCmValue;
            $prevWeightKgBind = $prevWeightKgValue === null ? null : (string) $prevWeightKgValue;
            $prevHeightCmBind = $prevHeightCmValue === null ? null : (string) $prevHeightCmValue;

            $stmt->bind_param(
                'sssssssssssssssssssssssssssssssi',
                $memberId,
                $surveyId,
                $barangayId,
                $member['member_name'],
                $member['relationship'],
                $member['gender'],
                $member['birth_date'],
                $weightKgBind,
                $heightCmBind,
                $dateMeasuredValue,
                $prevWeightKgBind,
                $prevHeightCmBind,
                $prevDateMeasuredValue,
                $ageMonthsBind,
                $member['weight_for_age'],
                $member['height_for_age'],
                $member['weight_for_height'],
                $member['is_pregnant'],
                $member['is_lactating'],
                $pregnancyMonthsBind,
                $pregnantNutritionStatus,
                $member['planned_exclusive_breastfeeding'],
                $member['planned_mixed_feeding'],
                $member['planned_bottle_feeding'],
                $member['planned_other_feeding'],
                $member['planned_other_specify'],
                $member['lactating_exclusive_breastfeeding'],
                $member['lactating_mixed_feeding'],
                $member['lactating_bottle_feeding'],
                $member['lactating_other_feeding'],
                $member['lactating_other_specify'],
                $sortOrder
            );
            if (!$stmt->execute()) {
                continue;
            }
            $saved++;
        }

        $stmt->close();

        return $saved;
    }
}

if (!function_exists('nutrition_delete_household_family_members')) {
    function nutrition_delete_household_family_members(mysqli $con, string $surveyId, string $barangayId): bool
    {
        if ($surveyId === '' || $barangayId === '' || !barangay_table_exists($con, 'nutrition_household_family_member')) {
            return false;
        }

        $stmt = $con->prepare(
            'DELETE FROM nutrition_household_family_member WHERE survey_id = ? AND barangay_id = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $surveyId, $barangayId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('nutrition_list_household_family_members')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function nutrition_list_household_family_members(mysqli $con, string $surveyId): array
    {
        if (!barangay_table_exists($con, 'nutrition_household_family_member')) {
            return [];
        }

        $stmt = $con->prepare(
            'SELECT * FROM nutrition_household_family_member
             WHERE survey_id = ?
             ORDER BY sort_order ASC, date_created ASC'
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('s', $surveyId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows ?: [];
    }
}

if (!function_exists('nutrition_growth_result_badge_class')) {
    function nutrition_growth_result_badge_class(string $result): string
    {
        $value = strtolower(trim($result));
        if (in_array($value, ['suw', 'severely stunted', 'sev wasted'], true)) {
            return 'badge-danger';
        }
        if (in_array($value, ['uw', 'stunted', 'wasted'], true)) {
            return 'badge-warning';
        }
        if (in_array($value, ['ow', 'ob'], true)) {
            return 'badge-info';
        }
        if ($value === 'tall') {
            return 'badge-primary';
        }
        if ($value === 'normal') {
            return 'badge-success';
        }

        return 'badge-secondary';
    }
}

if (!function_exists('nutrition_growth_result_is_at_risk')) {
    function nutrition_growth_result_is_at_risk(string $result): bool
    {
        $value = strtolower(trim($result));
        if ($value === '' || $value === 'normal' || $value === 'tall') {
            return false;
        }

        return true;
    }
}

if (!function_exists('nutrition_list_household_puroks')) {
    /**
     * @return array<int, string>
     */
    function nutrition_list_household_puroks(mysqli $con, string $barangayId): array
    {
        if (!barangay_table_exists($con, 'nutrition_household_survey')) {
            return [];
        }

        $stmt = $con->prepare(
            'SELECT DISTINCT purok_label
             FROM nutrition_household_survey
             WHERE barangay_id = ? AND purok_label <> \'\'
             ORDER BY purok_label ASC'
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('s', $barangayId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['purok_label'] ?? '')),
            $rows
        )));
    }
}

if (!function_exists('nutrition_list_barangay_family_members')) {
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    function nutrition_list_barangay_family_members(mysqli $con, string $barangayId): array
    {
        if (!barangay_table_exists($con, 'nutrition_household_family_member')) {
            return [];
        }

        $stmt = $con->prepare(
            'SELECT * FROM nutrition_household_family_member
             WHERE barangay_id = ?
             ORDER BY sort_order ASC, date_created ASC'
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('s', $barangayId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $grouped = [];
        foreach ($rows as $row) {
            $surveyId = (string) ($row['survey_id'] ?? '');
            if ($surveyId === '') {
                continue;
            }
            $grouped[$surveyId][] = $row;
        }

        return $grouped;
    }
}

if (!function_exists('nutrition_household_consolidated_report')) {
    /**
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_household_consolidated_report(mysqli $con, string $barangayId, array $filters = []): array
    {
        $households = nutrition_list_household_surveys($con, $barangayId, 0, $filters);
        $membersBySurvey = nutrition_list_barangay_family_members($con, $barangayId);

        $summary = [
            'households' => 0,
            'family_members' => 0,
            'pregnant' => 0,
            'lactating' => 0,
            'four_ps' => 0,
            'pwd' => 0,
            'ip' => 0,
            'solo_parent' => 0,
            'malnourished' => 0,
            'at_risk_members' => 0,
            'assessed_members' => 0,
        ];

        $growthSummary = [
            'weight_for_age' => [],
            'height_for_age' => [],
            'weight_for_height' => [],
        ];

        $reportHouseholds = [];
        foreach ($households as $household) {
            $surveyId = (string) ($household['survey_id'] ?? '');
            $members = $membersBySurvey[$surveyId] ?? [];
            $summary['households']++;

            foreach (['is_4ps' => 'four_ps', 'is_pwd' => 'pwd', 'is_ip' => 'ip', 'is_solo_parent' => 'solo_parent'] as $field => $key) {
                if (strtoupper((string) ($household[$field] ?? 'NO')) === 'YES') {
                    $summary[$key]++;
                }
            }

            $householdAtRisk = false;
            foreach ($members as $member) {
                $summary['family_members']++;
                if (strtoupper((string) ($member['is_pregnant'] ?? 'NO')) === 'YES') {
                    $summary['pregnant']++;
                }
                if (strtoupper((string) ($member['is_lactating'] ?? 'NO')) === 'YES') {
                    $summary['lactating']++;
                }

                $memberAssessed = false;
                $memberAtRisk = false;
                $memberMalnourished = false;
                foreach (['weight_for_age', 'height_for_age', 'weight_for_height'] as $growthField) {
                    $result = trim((string) ($member[$growthField] ?? ''));
                    if ($result === '') {
                        continue;
                    }

                    $memberAssessed = true;
                    $growthSummary[$growthField][$result] = (int) ($growthSummary[$growthField][$result] ?? 0) + 1;

                    if (nutrition_growth_result_is_at_risk($result)) {
                        $memberAtRisk = true;
                        if (!in_array(strtolower($result), ['ow', 'ob', 'tall'], true)) {
                            $memberMalnourished = true;
                        }
                    }
                }

                if ($memberAssessed) {
                    $summary['assessed_members']++;
                }
                if ($memberAtRisk) {
                    $summary['at_risk_members']++;
                    $householdAtRisk = true;
                }
                if ($memberMalnourished) {
                    $summary['malnourished']++;
                }
            }

            $reportHouseholds[] = [
                'survey' => $household,
                'members' => $members,
                'member_count' => count($members),
                'has_at_risk' => $householdAtRisk,
            ];
        }

        return [
            'summary' => $summary,
            'growth_summary' => $growthSummary,
            'households' => $reportHouseholds,
            'purok_options' => nutrition_list_household_puroks($con, $barangayId),
        ];
    }
}

if (!function_exists('nutrition_pregnant_profile_columns')) {
    /**
     * Official BNP “Families with Pregnant” column keys A–E.
     *
     * @return array<string, string>
     */
    function nutrition_pregnant_profile_columns(): array
    {
        return [
            'A' => 'Normal Pregnant Women',
            'B' => 'Teenage Pregnant Women',
            'C' => 'Underweight Pregnant Women',
            'D' => 'Overweight Pregnant Women',
            'E' => 'Others (Old Age, PWD)',
        ];
    }
}

if (!function_exists('nutrition_pregnant_status_to_column')) {
    function nutrition_pregnant_status_to_column(string $status, bool $isPwd = false): string
    {
        $status = trim($status);
        $map = [
            'Normal' => 'A',
            'Teenage' => 'B',
            'Underweight' => 'C',
            'Overweight' => 'D',
            'Old Age' => 'E',
        ];
        if (isset($map[$status])) {
            return $map[$status];
        }
        if ($isPwd || $status === '' || strcasecmp($status, 'Others') === 0) {
            return 'E';
        }

        return 'E';
    }
}

if (!function_exists('nutrition_household_has_pregnant')) {
    /**
     * @param array<string, mixed> $survey
     * @param array<int, array<string, mixed>> $members
     */
    function nutrition_household_has_pregnant(array $survey, array $members): bool
    {
        if (strtoupper((string) ($survey['has_pregnant'] ?? 'NO')) === 'YES') {
            return true;
        }
        if (strtoupper((string) ($survey['head_is_pregnant'] ?? 'NO')) === 'YES') {
            return true;
        }
        foreach ($members as $member) {
            if (strtoupper((string) ($member['is_pregnant'] ?? 'NO')) === 'YES') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('nutrition_household_pregnant_column')) {
    /**
     * @param array<string, mixed> $survey
     * @param array<int, array<string, mixed>> $members
     */
    function nutrition_household_pregnant_column(array $survey, array $members): string
    {
        $isPwd = strtoupper((string) ($survey['is_pwd'] ?? 'NO')) === 'YES';

        if (strtoupper((string) ($survey['head_is_pregnant'] ?? 'NO')) === 'YES') {
            return nutrition_pregnant_status_to_column(
                (string) ($survey['head_pregnant_nutrition_status'] ?? ''),
                $isPwd
            );
        }

        foreach ($members as $member) {
            if (strtoupper((string) ($member['is_pregnant'] ?? 'NO')) !== 'YES') {
                continue;
            }

            return nutrition_pregnant_status_to_column(
                (string) ($member['pregnant_nutrition_status'] ?? ''),
                $isPwd
            );
        }

        return nutrition_pregnant_status_to_column('', $isPwd);
    }
}

if (!function_exists('nutrition_pregnant_zero_columns')) {
    /**
     * @return array<string, int>
     */
    function nutrition_pregnant_zero_columns(): array
    {
        return ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
    }
}

if (!function_exists('nutrition_pregnant_option_buckets')) {
    /**
     * @param array<int, string> $options
     * @return array<string, array<string, int>>
     */
    function nutrition_pregnant_option_buckets(array $options): array
    {
        $out = [];
        foreach ($options as $opt) {
            $out[$opt] = nutrition_pregnant_zero_columns();
        }

        return $out;
    }
}

if (!function_exists('nutrition_pregnant_empty_buckets')) {
    /**
     * @return array<string, mixed>
     */
    function nutrition_pregnant_empty_buckets(): array
    {
        $familySize = [];
        for ($n = 1; $n <= 15; $n++) {
            $familySize[$n] = nutrition_pregnant_zero_columns();
        }

        return [
            'pregnant_totals' => nutrition_pregnant_zero_columns(),
            'house' => [
                'Owned' => nutrition_pregnant_zero_columns(),
                'Rented' => nutrition_pregnant_zero_columns(),
                'Others' => nutrition_pregnant_zero_columns(),
            ],
            'dwelling' => [
                'Concrete' => nutrition_pregnant_zero_columns(),
                'Semi-concrete' => nutrition_pregnant_zero_columns(),
                'Wood' => nutrition_pregnant_zero_columns(),
                'Makeshift/Barong-barong' => nutrition_pregnant_zero_columns(),
            ],
            'garbage' => [
                'Collected' => nutrition_pregnant_zero_columns(),
                'Burning' => nutrition_pregnant_zero_columns(),
                'Dumping' => nutrition_pregnant_zero_columns(),
                'Composting' => nutrition_pregnant_zero_columns(),
            ],
            'toilet' => nutrition_pregnant_option_buckets(nutrition_prf_toilet_options()),
            'water' => nutrition_pregnant_option_buckets(nutrition_prf_water_source_options()),
            'food' => [
                'Vegetable Garden' => nutrition_pregnant_zero_columns(),
                'Livestock and/or Poultry' => nutrition_pregnant_zero_columns(),
                'Fish Pond' => nutrition_pregnant_zero_columns(),
                'Others' => nutrition_pregnant_zero_columns(),
            ],
            'family_size' => $familySize,
        ];
    }
}

if (!function_exists('nutrition_pregnant_apply_household')) {
    /**
     * Tallies one pregnant household into A–E matrix buckets. Returns column key or '' if skipped.
     *
     * @param array<string, mixed> $buckets
     * @param array<string, mixed> $survey
     * @param array<int, array<string, mixed>> $members
     */
    function nutrition_pregnant_apply_household(array &$buckets, array $survey, array $members): string
    {
        if (!nutrition_household_has_pregnant($survey, $members)) {
            return '';
        }

        $col = nutrition_household_pregnant_column($survey, $members);
        $bump = static function (array &$bucket, string $key, string $col): void {
            if (!isset($bucket[$key])) {
                $bucket[$key] = nutrition_pregnant_zero_columns();
            }
            if (!isset($bucket[$key][$col])) {
                $bucket[$key][$col] = 0;
            }
            $bucket[$key][$col]++;
        };

        $buckets['pregnant_totals'][$col] = (int) ($buckets['pregnant_totals'][$col] ?? 0) + 1;

        $ownership = trim((string) ($survey['house_ownership'] ?? ''));
        if ($ownership === '' || !isset($buckets['house'][$ownership])) {
            $ownership = 'Others';
        }
        $bump($buckets['house'], $ownership, $col);

        $dwellingType = trim((string) ($survey['dwelling_type'] ?? ''));
        if ($dwellingType !== '' && isset($buckets['dwelling'][$dwellingType])) {
            $bump($buckets['dwelling'], $dwellingType, $col);
        }

        $garbageDisposal = nutrition_prf_normalize_garbage_disposal((string) ($survey['garbage_disposal'] ?? ''));
        if ($garbageDisposal === 'Collected/Segregated' || strcasecmp($garbageDisposal, 'Collected') === 0) {
            $bump($buckets['garbage'], 'Collected', $col);
        } elseif (strcasecmp($garbageDisposal, 'Uncollected') === 0) {
            $uncollected = strtolower((string) ($survey['garbage_uncollected_type'] ?? ''));
            if (str_contains($uncollected, 'burn')) {
                $bump($buckets['garbage'], 'Burning', $col);
            } elseif (str_contains($uncollected, 'dump')) {
                $bump($buckets['garbage'], 'Dumping', $col);
            } elseif (str_contains($uncollected, 'compost')) {
                $bump($buckets['garbage'], 'Composting', $col);
            } else {
                $bump($buckets['garbage'], 'Dumping', $col);
            }
        }

        $toiletType = nutrition_prf_normalize_toilet_type((string) ($survey['toilet_type'] ?? ''));
        if ($toiletType !== '' && isset($buckets['toilet'][$toiletType])) {
            $bump($buckets['toilet'], $toiletType, $col);
        }

        $waterSource = nutrition_prf_normalize_water_source((string) ($survey['water_source'] ?? ''));
        if ($waterSource !== '' && isset($buckets['water'][$waterSource])) {
            $bump($buckets['water'], $waterSource, $col);
        }

        $activities = nutrition_prf_parse_food_production((string) ($survey['food_production'] ?? ''));
        $mappedFood = [];
        foreach ($activities as $activity) {
            if ($activity === 'Vege Garden') {
                $mappedFood['Vegetable Garden'] = true;
            } elseif ($activity === 'Livestock' || $activity === 'Poultry') {
                $mappedFood['Livestock and/or Poultry'] = true;
            } elseif ($activity === 'Fishpond') {
                $mappedFood['Fish Pond'] = true;
            } elseif ($activity === 'Fruit Trees') {
                $mappedFood['Others'] = true;
            }
        }
        foreach (array_keys($mappedFood) as $foodKey) {
            $bump($buckets['food'], $foodKey, $col);
        }

        $size = (int) ($survey['members_count'] ?? 0);
        if ($size < 1) {
            $size = count($members) + 1;
        }
        $size = max(1, min(15, $size));
        $buckets['family_size'][$size][$col] = (int) ($buckets['family_size'][$size][$col] ?? 0) + 1;

        return $col;
    }
}

if (!function_exists('nutrition_pregnant_form_from_household')) {
    /**
     * Build a single-household pregnant BNP matrix (Individual mode).
     *
     * @param array<string, mixed> $survey
     * @param array<int, array<string, mixed>> $members
     * @return array<string, mixed>
     */
    function nutrition_pregnant_form_from_household(array $survey, array $members): array
    {
        $buckets = nutrition_pregnant_empty_buckets();
        $col = nutrition_pregnant_apply_household($buckets, $survey, $members);
        $occupation = trim((string) ($survey['occupation'] ?? ''));

        return array_merge($buckets, [
            'columns' => nutrition_pregnant_profile_columns(),
            'family_count' => $col !== '' ? 1 : 0,
            'most_common_occupation' => $occupation,
            'calendar_year' => (int) date('Y'),
            'head_name' => nutrition_household_head_display($survey),
            'purok' => trim((string) ($survey['purok'] ?? '')),
            'column' => $col,
        ]);
    }
}

if (!function_exists('nutrition_pregnant_families_report')) {
    /**
     * Barangay Nutrition Profile — Families with Pregnant (consolidated matrix).
     *
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_pregnant_families_report(mysqli $con, string $barangayId, array $filters = []): array
    {
        $households = nutrition_list_household_surveys($con, $barangayId, 0, $filters);
        $membersBySurvey = nutrition_list_barangay_family_members($con, $barangayId);
        $columns = nutrition_pregnant_profile_columns();

        $buckets = nutrition_pregnant_empty_buckets();
        $occupationCounts = [];
        $familyCount = 0;
        $individuals = [];

        foreach ($households as $survey) {
            $surveyId = (string) ($survey['survey_id'] ?? '');
            $members = $membersBySurvey[$surveyId] ?? [];
            $col = nutrition_pregnant_apply_household($buckets, $survey, $members);
            if ($col === '') {
                continue;
            }

            $familyCount++;
            $occupation = trim((string) ($survey['occupation'] ?? ''));
            if ($occupation !== '') {
                $occKey = mb_strtolower($occupation);
                if (!isset($occupationCounts[$occKey])) {
                    $occupationCounts[$occKey] = ['label' => $occupation, 'count' => 0];
                }
                $occupationCounts[$occKey]['count']++;
            }

            $individuals[] = [
                'head_name' => nutrition_household_head_display($survey),
                'purok' => trim((string) ($survey['purok'] ?? '')),
                'column' => $col,
                'form' => nutrition_pregnant_form_from_household($survey, $members),
            ];
        }

        usort($occupationCounts, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $topOccupations = array_slice(array_values($occupationCounts), 0, 5);
        $mostCommon = '';
        if ($topOccupations !== []) {
            $parts = [];
            foreach ($topOccupations as $row) {
                $parts[] = $row['label'] . ' (' . $row['count'] . ')';
            }
            $mostCommon = implode(', ', $parts);
        }

        $bnsName = '';
        foreach ($households as $survey) {
            $candidate = trim((string) ($survey['bns_name'] ?? ''));
            if ($candidate !== '') {
                $bnsName = $candidate;
                break;
            }
        }
        if ($bnsName === '') {
            $settings = nutrition_load_settings($con, $barangayId);
            $bnsName = trim((string) ($settings['nutrition_officer'] ?? ''));
        }

        return [
            'columns' => $columns,
            'family_count' => $familyCount,
            'pregnant_totals' => $buckets['pregnant_totals'],
            'house' => $buckets['house'],
            'dwelling' => $buckets['dwelling'],
            'garbage' => $buckets['garbage'],
            'toilet' => $buckets['toilet'],
            'water' => $buckets['water'],
            'food' => $buckets['food'],
            'family_size' => $buckets['family_size'],
            'most_common_occupation' => $mostCommon,
            'individuals' => $individuals,
            'bns_name' => $bnsName,
            'purok_options' => nutrition_list_household_puroks($con, $barangayId),
            'calendar_year' => (int) date('Y'),
        ];
    }
}

if (!function_exists('nutrition_city_pregnant_families_report')) {
    /**
     * City-wide Families with Pregnant report (all barangays).
     *
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_city_pregnant_families_report(mysqli $con, array $filters = []): array
    {
        $columns = nutrition_pregnant_profile_columns();
        $merged = [
            'columns' => $columns,
            'family_count' => 0,
            'pregnant_totals' => nutrition_pregnant_zero_columns(),
            'house' => [
                'Owned' => nutrition_pregnant_zero_columns(),
                'Rented' => nutrition_pregnant_zero_columns(),
                'Others' => nutrition_pregnant_zero_columns(),
            ],
            'dwelling' => [
                'Concrete' => nutrition_pregnant_zero_columns(),
                'Semi-concrete' => nutrition_pregnant_zero_columns(),
                'Wood' => nutrition_pregnant_zero_columns(),
                'Makeshift/Barong-barong' => nutrition_pregnant_zero_columns(),
            ],
            'garbage' => [
                'Collected' => nutrition_pregnant_zero_columns(),
                'Burning' => nutrition_pregnant_zero_columns(),
                'Dumping' => nutrition_pregnant_zero_columns(),
                'Composting' => nutrition_pregnant_zero_columns(),
            ],
            'toilet' => nutrition_pregnant_option_buckets(nutrition_prf_toilet_options()),
            'water' => nutrition_pregnant_option_buckets(nutrition_prf_water_source_options()),
            'food' => [
                'Vegetable Garden' => nutrition_pregnant_zero_columns(),
                'Livestock and/or Poultry' => nutrition_pregnant_zero_columns(),
                'Fish Pond' => nutrition_pregnant_zero_columns(),
                'Others' => nutrition_pregnant_zero_columns(),
            ],
            'family_size' => [],
            'most_common_occupation' => '',
            'individuals' => [],
            'bns_name' => '',
            'barangay_rows' => [],
            'calendar_year' => (int) date('Y'),
        ];
        for ($n = 1; $n <= 15; $n++) {
            $merged['family_size'][$n] = nutrition_pregnant_zero_columns();
        }

        $occupationCounts = [];
        $mergeSection = static function (array &$target, array $source): void {
            foreach ($source as $key => $cols) {
                if (!isset($target[$key])) {
                    $target[$key] = nutrition_pregnant_zero_columns();
                }
                foreach ($cols as $col => $count) {
                    $target[$key][$col] = (int) ($target[$key][$col] ?? 0) + (int) $count;
                }
            }
        };

        foreach (barangay_list_all($con) as $brgy) {
            $id = (string) ($brgy['id'] ?? '');
            $name = (string) ($brgy['barangay'] ?? '');
            if ($id === '' || strcasecmp($name, 'Barangay') === 0) {
                continue;
            }
            $report = nutrition_pregnant_families_report($con, $id, $filters);
            $merged['barangay_rows'][] = [
                'id' => $id,
                'barangay' => $name,
                'family_count' => (int) ($report['family_count'] ?? 0),
                'pregnant_totals' => $report['pregnant_totals'] ?? nutrition_pregnant_zero_columns(),
            ];
            $merged['family_count'] += (int) ($report['family_count'] ?? 0);
            foreach ($report['pregnant_totals'] ?? [] as $col => $count) {
                $merged['pregnant_totals'][$col] = (int) ($merged['pregnant_totals'][$col] ?? 0) + (int) $count;
            }
            $mergeSection($merged['house'], $report['house'] ?? []);
            $mergeSection($merged['dwelling'], $report['dwelling'] ?? []);
            $mergeSection($merged['garbage'], $report['garbage'] ?? []);
            $mergeSection($merged['toilet'], $report['toilet'] ?? []);
            $mergeSection($merged['water'], $report['water'] ?? []);
            $mergeSection($merged['food'], $report['food'] ?? []);
            $mergeSection($merged['family_size'], $report['family_size'] ?? []);

            foreach ($report['individuals'] ?? [] as $ind) {
                $ind['barangay'] = $name;
                $merged['individuals'][] = $ind;
            }

            $occ = trim((string) ($report['most_common_occupation'] ?? ''));
            if ($occ !== '') {
                // Re-parse "Label (n), Label (n)" is fragile; leave city occupation blank and
                // recompute from per-barangay most-common labels lightly.
                foreach (explode(',', $occ) as $part) {
                    $part = trim($part);
                    if ($part === '') {
                        continue;
                    }
                    if (preg_match('/^(.*)\((\d+)\)\s*$/', $part, $m)) {
                        $label = trim($m[1]);
                        $count = (int) $m[2];
                    } else {
                        $label = $part;
                        $count = 1;
                    }
                    $key = mb_strtolower($label);
                    if (!isset($occupationCounts[$key])) {
                        $occupationCounts[$key] = ['label' => $label, 'count' => 0];
                    }
                    $occupationCounts[$key]['count'] += $count;
                }
            }
        }

        usort($occupationCounts, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $top = array_slice(array_values($occupationCounts), 0, 5);
        if ($top !== []) {
            $parts = [];
            foreach ($top as $row) {
                $parts[] = $row['label'] . ' (' . $row['count'] . ')';
            }
            $merged['most_common_occupation'] = implode(', ', $parts);
        }

        return $merged;
    }
}

if (!function_exists('nutrition_household_survey_build_form_layout_rows')) {
    /**
     * Build a single-household form layout matching the printable survey form.
     *
     * @return array<int, array<int, string>>
     */
    function nutrition_household_survey_build_form_layout_rows(
        string $barangayName,
        string $district,
        string $address,
        string $psgcCode,
        string $reportHeader,
        array $certHeader,
        array $relationshipOptions,
        string $prefillPurok = '',
        string $nutritionOfficer = '',
        int $memberRows = 6
    ): array {
        return nutrition_household_survey_build_form_sheet(
            $barangayName,
            $district,
            $address,
            $psgcCode,
            $reportHeader,
            $certHeader,
            $relationshipOptions,
            $prefillPurok,
            $nutritionOfficer,
            $memberRows
        )['rows'];
    }
}

if (!function_exists('nutrition_household_survey_build_form_sheet')) {
    /**
     * Styled single-household Excel form matching the printable / PDF survey form
     * (all fields and ☐ checkboxes). 15 columns, landscape A4, fit to width.
     *
     * @return array{rows: array<int, array<int, string|array{v:string,s:int}>>, options: array<string, mixed>}
     */
    function nutrition_household_survey_build_form_sheet(
        string $barangayName,
        string $district,
        string $address,
        string $psgcCode,
        string $reportHeader,
        array $certHeader,
        array $relationshipOptions,
        string $prefillPurok = '',
        string $nutritionOfficer = '',
        int $memberRows = 6
    ): array {
        require_once __DIR__ . '/spreadsheet_export.php';

        $memberRows = max(3, min(12, $memberRows));
        $lastCol = 14;
        $box = "\u{2610} ";
        $rows = [];
        $merges = [];
        $heights = [];
        // Hidden column P: per-row field markers read back by nutrition_household_survey_parse_form_xlsx().
        $keys = [0 => [NUTRITION_SURVEY_FORM_MARKER]];

        $cell = static fn (string $v, string $style = 'default'): array => ['v' => $v, 's' => barangay_xlsx_style($style)];
        $ref = static fn (int $col, int $row): string => barangay_xlsx_column_letter($col) . ($row + 1);
        $merge = static function (int $row, int $c1, int $c2) use (&$merges, $ref): void {
            if ($c2 > $c1) {
                $merges[] = $ref($c1, $row) . ':' . $ref($c2, $row);
            }
        };
        $blankRow = static function (float $height = 6) use (&$rows, &$heights): void {
            $heights[count($rows)] = $height;
            $rows[] = [];
        };
        $wide = static function (string $text, string $style, float $height = 0) use (&$rows, &$heights, $cell, $merge, $lastCol): void {
            $r = count($rows);
            $line = array_fill(0, $lastCol + 1, $cell('', $style));
            $line[0] = $cell($text, $style);
            $rows[] = $line;
            $merge($r, 0, $lastCol);
            if ($height > 0) {
                $heights[$r] = $height;
            }
        };
        $section = static function (string $title) use ($wide, $blankRow): void {
            $blankRow(8);
            $wide($title, 'section', 20);
        };
        /** @param array<int, array{0:string,1:int,2:int,3?:string,4?:string}> $fields label, startCol, endCol, value, key */
        $fields = static function (array $fields) use (&$rows, &$heights, &$keys, $cell, $merge): void {
            $labelRow = [];
            $inputRow = [];
            foreach ($fields as $f) {
                [$label, $c1, $c2] = $f;
                $value = (string) ($f[3] ?? '');
                $labelRow[$c1] = $cell(strtoupper($label), 'label');
                for ($c = $c1; $c <= $c2; $c++) {
                    $inputRow[$c] = $cell('', $value !== '' ? 'input_prefill' : 'input');
                }
                $inputRow[$c1] = $cell($value, $value !== '' ? 'input_prefill' : 'input');
            }
            ksort($labelRow);
            ksort($inputRow);
            $lr = count($rows);
            $rows[] = $labelRow + array_fill(0, max(array_keys($labelRow)) + 1, '');
            $ir = count($rows);
            $rows[] = $inputRow + array_fill(0, max(array_keys($inputRow)) + 1, '');
            ksort($rows[$lr]);
            ksort($rows[$ir]);
            $heights[$lr] = 13;
            $heights[$ir] = 20;
            foreach ($fields as $f) {
                $merge($lr, $f[1], $f[2]);
                $merge($ir, $f[1], $f[2]);
                if (!empty($f[4])) {
                    $keys[$ir][] = 'in:' . $f[4] . ':' . $f[1];
                }
            }
        };
        /** Options laid out across the sheet, each option spanning $span columns. */
        $options = static function (string $label, array $opts, int $span = 3, string $key = '', int $startCol = 0, int $endCol = 14) use (&$rows, &$heights, &$keys, $cell, $merge, $box): void {
            if ($label !== '') {
                $r = count($rows);
                $rows[] = array_fill(0, $startCol, '') + [$startCol => $cell(strtoupper($label), 'label')];
                ksort($rows[$r]);
                $merge($r, $startCol, $endCol);
                $heights[$r] = 13;
            }
            $perRow = max(1, intdiv($endCol - $startCol + 1, $span));
            $index = 0;
            foreach (array_chunk($opts, $perRow) as $chunk) {
                $r = count($rows);
                $line = array_fill(0, $startCol, '');
                $col = $startCol;
                $map = [];
                foreach ($chunk as $opt) {
                    $line[$col] = $cell($box . $opt, 'option');
                    $merge($r, $col, min($endCol, $col + $span - 1));
                    $map[] = $col . '=' . $index++;
                    $col += $span;
                }
                $rows[] = $line;
                $heights[$r] = 17;
                if ($key !== '') {
                    $keys[$r][] = 'opt:' . $key . ':' . implode(',', $map);
                }
            }
        };
        $specify = static function (string $text = 'Others, pls. specify:', string $key = '', int $startCol = 0, int $endCol = 14) use (&$rows, &$heights, &$keys, $cell, $merge): void {
            $r = count($rows);
            $line = array_fill(0, $startCol, '');
            $line[$startCol] = $cell($text, 'option');
            $line[$startCol + 1] = $cell('', 'option');
            for ($c = $startCol + 2; $c <= $endCol; $c++) {
                $line[$c] = $cell('', 'input');
            }
            $rows[] = $line;
            $merge($r, $startCol, $startCol + 1);
            $merge($r, $startCol + 2, $endCol);
            $heights[$r] = 18;
            if ($key !== '') {
                $keys[$r][] = 'in:' . $key . ':' . ($startCol + 2);
            }
        };
        /** Two vertical option lists side by side (left: cols 0-6, right: cols 8-14). */
        $columns = static function (string $leftLabel, array $leftOpts, string $rightLabel, array $rightOpts, string $leftKey, string $rightKey) use (&$rows, &$heights, &$keys, $cell, $merge, $box): void {
            $r = count($rows);
            $rows[] = [0 => $cell(strtoupper($leftLabel), 'label')] + array_fill(1, 7, '') + [8 => $cell(strtoupper($rightLabel), 'label')];
            $merge($r, 0, 6);
            $merge($r, 8, 14);
            $heights[$r] = 13;
            $count = max(count($leftOpts), count($rightOpts));
            for ($i = 0; $i < $count; $i++) {
                $r = count($rows);
                $line = array_fill(0, 15, '');
                if (isset($leftOpts[$i])) {
                    $line[0] = $cell($box . $leftOpts[$i], 'option');
                    $merge($r, 0, 6);
                    $keys[$r][] = 'opt:' . $leftKey . ':0=' . $i;
                }
                if (isset($rightOpts[$i])) {
                    $line[8] = $cell($box . $rightOpts[$i], 'option');
                    $merge($r, 8, 14);
                    $keys[$r][] = 'opt:' . $rightKey . ':8=' . $i;
                }
                $rows[] = $line;
                $heights[$r] = 17;
            }
        };

        // Header
        $wide('SURVEY FORM (FAMILY PROFILE)', 'title', 24);
        $wide('City Nutrition Committee · C.Y. ' . date('Y') . ($reportHeader !== '' ? ' · ' . $reportHeader : ''), 'subtitle');
        $wide(
            trim(($certHeader['country'] ?? '') . ' · ' . ($certHeader['province'] ?? '') . ' · ' . ($certHeader['city'] ?? ''), ' ·'),
            'subtitle'
        );
        $barangayLine = (string) ($certHeader['barangay_line'] ?? ('Barangay ' . $barangayName));
        $wide($barangayLine . ($district !== '' ? ' · ' . $district : ''), 'subtitle');
        $metaParts = [];
        if ($psgcCode !== '') {
            $metaParts[] = 'Barangay PSGC: ' . $psgcCode;
        }
        if ($nutritionOfficer !== '') {
            $metaParts[] = 'Nutrition Officer: ' . $nutritionOfficer;
        }
        if ($metaParts !== []) {
            $wide(implode('   ·   ', $metaParts), 'subtitle');
        }
        $wide(
            'How to fill in Excel: type on the lines. To check a box, replace ☐ with ☑ or type X before the option. '
            . 'Save the file and use "Upload Excel" on the Household Nutrition Survey page. Household No. is generated automatically.',
            'note',
            26
        );

        // I. Survey Information
        $section('I. SURVEY INFORMATION');
        $fields([
            ['Name of Barangay', 0, 3, $barangayName],
            ['Purok', 5, 8, $prefillPurok, 'purok_number'],
            ['Household No. (auto-generated)', 10, 14],
        ]);
        $fields([
            ['Name of BNS', 0, 3, '', 'bns_name'],
            ['No. of Household members', 5, 8],
            ['Survey Date (MM/DD/YYYY)', 10, 14, '', 'survey_date'],
        ]);

        // II. Household Head
        $section('II. HOUSEHOLD HEAD');
        $fields([
            ['Last Name', 0, 2, '', 'head_last_name'],
            ['First Name', 4, 6, '', 'head_first_name'],
            ['Middle Name', 8, 11, '', 'head_middle_name'],
            ['Suffix', 13, 14, '', 'head_suffix'],
        ]);
        $fields([
            ['Birthday (MM/DD/YYYY)', 0, 2, '', 'birth_date'],
            ['Occupation', 8, 14, '', 'occupation'],
        ]);
        $r = count($rows) - 2;
        $rows[$r][4] = $cell('GENDER', 'label');
        $merge($r, 4, 6);
        $rows[$r + 1][4] = $cell($box . 'Male', 'option');
        $rows[$r + 1][5] = $cell($box . 'Female', 'option');
        $keys[$r + 1][] = 'opt:gender:4=0,5=1';
        ksort($rows[$r]);
        ksort($rows[$r + 1]);
        $options('Household classification', nutrition_household_survey_form_option_sets()['classification'], 3, 'classification');

        // III. Living Conditions
        $section('III. LIVING CONDITIONS');
        $wide('Housing', 'subsection', 16);
        $options('III-A. Type of House', nutrition_prf_house_ownership_options(), 3, 'house_ownership');
        $specify('Others, pls. specify:', 'house_ownership_other');
        $options('III-E. Types of Dwelling Unit', nutrition_prf_dwelling_options(), 3, 'dwelling_type');
        $wide('Sanitation', 'subsection', 16);
        $options('III-B. Type of Toilet', nutrition_prf_toilet_options(), 3, 'toilet_type');
        $options('III-C. Type of Garbage Disposal', nutrition_prf_garbage_options(), 3, 'garbage_disposal');
        $options('If Uncollected', nutrition_prf_garbage_uncollected_options(), 3, 'garbage_uncollected_type');
        $wide('Water & Food Security', 'subsection', 16);
        $options('III-D. Type of Water Source', nutrition_prf_water_source_options(), 3, 'water_source');
        $options('III-F. Food Production Activities (check all that apply)', nutrition_prf_food_production_activity_options(), 3, 'food_production');
        $options('III-G. Check below if:', nutrition_household_survey_form_option_sets()['hh_checks'], 4, 'hh_checks');

        // IV. Family Planning & Feeding Practices
        $section('IV. FAMILY PLANNING & FEEDING PRACTICES');
        $wide('Family Planning', 'subsection', 16);
        $options('IV-A. Practices Family Planning', ['Yes', 'No'], 3, 'practices_family_planning');
        $options('If yes, check method(s):', nutrition_prf_family_planning_method_options(), 3, 'family_planning_methods');
        $specify('Others, pls. specify:', 'family_planning_methods_other');
        $wide('Complementary Feeding (6–23 months)', 'subsection', 16);
        $columns(
            'IV-B. Common Meals Given',
            nutrition_prf_complementary_meal_options(),
            'IV-C. Common Snacks Given',
            nutrition_prf_complementary_snack_options(),
            'complementary_meals',
            'complementary_snacks'
        );
        $r = count($rows);
        $specify('Others (meals):', 'complementary_meals_other', 0, 6);
        $specLine = array_pop($rows);
        $right = [];
        $right[8] = $cell('Others (snacks):', 'option');
        $right[9] = $cell('', 'option');
        for ($c = 10; $c <= 14; $c++) {
            $right[$c] = $cell('', 'input');
        }
        $rows[] = $specLine + array_fill(7, 1, '') + $right;
        ksort($rows[$r]);
        $merge($r, 8, 9);
        $merge($r, 10, 14);
        $keys[$r][] = 'in:complementary_snacks_other:10';
        $wide('Child Physical Activity', 'subsection', 16);
        $options('IV-D. Physical Activity of children (1–5 years old)', nutrition_prf_physical_activity_options(), 5, 'child_physical_activity');
        $specify('Others, pls. specify:', 'child_physical_activity_other');

        // V. Family Members
        $section('V. FAMILY MEMBERS');
        $wide(
            'For children 0–5 years: record previous (optional) and present Date Measured, Weight (kg), Height/Length (cm). '
            . 'WFA / HFA / WFH use the present date and boy/girl growth standards.',
            'note',
            26
        );
        $r = count($rows);
        $group = array_fill(0, 15, $cell('', 'th'));
        $group[0] = $cell('Family member', 'group_th');
        for ($c = 1; $c <= 5; $c++) {
            $group[$c] = $cell('', 'group_th');
        }
        $group[6] = $cell('Previous measurement (optional)', 'group_th');
        $group[7] = $cell('', 'group_th');
        $group[8] = $cell('', 'group_th');
        $group[9] = $cell('Present measurement', 'group_th');
        $group[10] = $cell('', 'group_th');
        $group[11] = $cell('', 'group_th');
        $group[12] = $cell('Nutrition result', 'group_th');
        $group[13] = $cell('', 'group_th');
        $group[14] = $cell('', 'group_th');
        $rows[] = $group;
        $merge($r, 0, 5);
        $merge($r, 6, 8);
        $merge($r, 9, 11);
        $merge($r, 12, 14);
        $heights[$r] = 16;

        $r = count($rows);
        $headers = [
            'Name', 'Relationship to Head', "Gender\n(M / F)", "Birthday\n(MM/DD/YYYY)", "Pregnant\n\u{2610}", "Lactating\n\u{2610}",
            'Date', 'Weight (kg)', 'Height (cm)', 'Date', 'Weight (kg)', 'Height (cm)', 'WFA', 'HFA', 'WFH',
        ];
        $rows[] = array_map(static fn (string $h): array => $cell($h, 'th'), $headers);
        $heights[$r] = 30;
        for ($i = 0; $i < $memberRows; $i++) {
            $keys[count($rows)][] = 'member';
            $heights[count($rows)] = 24;
            $rows[] = array_fill(0, 15, $cell('', 'td'));
        }

        $blankRow(6);
        $options('Relationship options (write in column B)', $relationshipOptions, 2);
        $options('Pregnant: nutritional status', nutrition_prf_pregnant_status_options(), 3, 'pregnant_nutrition_status');
        $fields([
            ['Pregnant: how many months', 0, 3, '', 'pregnancy_months'],
        ]);
        $feeding = nutrition_household_survey_form_option_sets()['feeding'];
        $options('Planned infant feeding method (pregnant)', $feeding, 3, 'planned_feeding');
        $options('Infant feeding method (lactating)', $feeding, 3, 'lactating_feeding');
        $specify('Other feeding, specify:', 'feeding_other');

        // VI. Acknowledgement
        $section('VI. ACKNOWLEDGEMENT');
        $wide('I certify that the information provided above is true and correct to the best of my knowledge.', 'option', 18);
        $blankRow(30);
        $r = count($rows);
        $sig = array_fill(0, 15, '');
        foreach ([[0, 3, 'Signature of Household Head'], [5, 8, 'Date'], [10, 14, 'BNS / Nutrition Officer']] as [$c1, $c2, $label]) {
            for ($c = $c1; $c <= $c2; $c++) {
                $sig[$c] = $cell('', 'signature');
            }
            $sig[$c1] = $cell($label, 'signature');
            $merge($r, $c1, $c2);
        }
        $rows[] = $sig;

        foreach ($keys as $i => $specs) {
            $rows[$i][NUTRITION_SURVEY_FORM_KEY_COL] = implode('|', $specs);
        }
        foreach ($rows as $i => $line) {
            if ($line !== []) {
                $rows[$i] = $line + array_fill(0, max(array_keys($line)) + 1, '');
                ksort($rows[$i]);
            }
        }

        return [
            'rows' => $rows,
            'options' => [
                'cols' => [24, 15, 9, 13, 9, 9, 11, 9, 9, 11, 9, 9, 7, 7, 7],
                'hidden_cols' => [NUTRITION_SURVEY_FORM_KEY_COL],
                'merges' => $merges,
                'heights' => $heights,
                'landscape' => true,
                'fit_width' => true,
            ],
        ];
    }
}

if (!defined('NUTRITION_SURVEY_FORM_MARKER')) {
    define('NUTRITION_SURVEY_FORM_MARKER', 'form:nutrition_household_v2');
    define('NUTRITION_SURVEY_FORM_KEY_COL', 15);
}

if (!function_exists('nutrition_household_survey_form_option_sets')) {
    /**
     * Option lists used only by the Excel survey form (others come from nutrition_prf_*_options()).
     *
     * @return array<string, array<int, string>>
     */
    function nutrition_household_survey_form_option_sets(): array
    {
        return [
            'gender' => ['Male', 'Female'],
            'classification' => ["4P's Member", "IP's Member", 'N/A', 'PWD', 'Solo Parent'],
            'hh_checks' => [
                'HH using Iodized Salt',
                'HH using Products with Sangkap Pinoy Seal',
                'HH with Carenderia/Eatery',
                'HH with Sari-Sari Store',
            ],
            'practices_family_planning' => ['Yes', 'No'],
            'feeding' => ['Exclusive Breastfeeding', 'Mixed Feeding', 'Bottle Feeding', 'Others'],
        ];
    }
}

if (!function_exists('nutrition_household_survey_form_cell_checked')) {
    /**
     * A ☐ option cell counts as checked once the box is replaced or marked (☑, ☒, ✓, X, etc.).
     */
    function nutrition_household_survey_form_cell_checked(string $text, string $label): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }
        $pos = mb_stripos($text, $label);
        $mark = $pos === false ? $text : trim(mb_substr($text, 0, $pos) . mb_substr($text, $pos + mb_strlen($label)));
        $mark = trim(str_replace("\u{2610}", '', $mark));

        return $mark !== '';
    }
}

if (!function_exists('nutrition_household_survey_form_date_mdy')) {
    /**
     * Excel date serial, Y-m-d or M/D/Y text → MM/DD/YYYY (empty when unreadable).
     */
    function nutrition_household_survey_form_date_mdy(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (is_numeric($value) && (float) $value > 1000 && (float) $value < 80000) {
            $ts = ((int) floor((float) $value) - 25569) * 86400;

            return gmdate('m/d/Y', $ts);
        }
        $ymd = nutrition_normalize_date_to_ymd($value);
        if ($ymd !== null && $ymd !== '') {
            $ts = strtotime($ymd);

            return $ts ? date('m/d/Y', $ts) : '';
        }

        return '';
    }
}

if (!function_exists('nutrition_household_survey_parse_form_xlsx')) {
    /**
     * Read a filled "Survey Form (Family Profile)" Excel file back into the
     * survey edit payload shape used by nutritionApplySurveyEditPayload().
     *
     * @return array{ok:bool, error?:string, payload?:array<string,mixed>, warnings?:array<int,string>}
     */
    function nutrition_household_survey_parse_form_xlsx(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'error' => 'Excel upload requires the PHP zip extension.'];
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'error' => 'Could not open the Excel file. Save it as .xlsx and try again.'];
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false && ($sx = @simplexml_load_string($sharedXml)) !== false) {
            foreach ($sx->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string) $si->t;
                    continue;
                }
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
                $shared[] = $text;
            }
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $sheet = $sheetXml !== false ? @simplexml_load_string($sheetXml) : false;
        if ($sheet === false || !isset($sheet->sheetData->row)) {
            return ['ok' => false, 'error' => 'The Excel file has no readable survey sheet.'];
        }

        /** @var array<int, array<int, string>> $grid */
        $grid = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowNum = (int) $row['r'];
            foreach ($row->c as $c) {
                if (!preg_match('/^([A-Z]+)(\d+)$/', (string) $c['r'], $m)) {
                    continue;
                }
                $col = barangay_xlsx_column_index_from_letters($m[1]);
                $type = (string) ($c['t'] ?? '');
                if ($type === 's') {
                    $value = $shared[(int) $c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = '';
                    if (isset($c->is->t)) {
                        $value = (string) $c->is->t;
                    } else {
                        foreach ($c->is->r as $run) {
                            $value .= (string) $run->t;
                        }
                    }
                } else {
                    $value = (string) ($c->v ?? '');
                }
                $grid[$rowNum ?: (int) $m[2]][$col] = trim($value);
            }
        }

        $keyCol = NUTRITION_SURVEY_FORM_KEY_COL;
        $hasMarker = false;
        foreach ($grid as $cells) {
            if (($cells[$keyCol] ?? '') === NUTRITION_SURVEY_FORM_MARKER) {
                $hasMarker = true;
                break;
            }
        }
        if (!$hasMarker) {
            return [
                'ok' => false,
                'error' => 'This is not the current Household Survey Excel Form. Download it again with the "Excel Form" button, fill it in, then upload.',
            ];
        }

        $sets = nutrition_household_survey_form_option_sets();
        $optionLists = [
            'gender' => $sets['gender'],
            'classification' => $sets['classification'],
            'house_ownership' => nutrition_prf_house_ownership_options(),
            'dwelling_type' => nutrition_prf_dwelling_options(),
            'toilet_type' => nutrition_prf_toilet_options(),
            'garbage_disposal' => nutrition_prf_garbage_options(),
            'garbage_uncollected_type' => nutrition_prf_garbage_uncollected_options(),
            'water_source' => nutrition_prf_water_source_options(),
            'food_production' => nutrition_prf_food_production_activity_options(),
            'hh_checks' => $sets['hh_checks'],
            'practices_family_planning' => $sets['practices_family_planning'],
            'family_planning_methods' => nutrition_prf_family_planning_method_options(),
            'complementary_meals' => nutrition_prf_complementary_meal_options(),
            'complementary_snacks' => nutrition_prf_complementary_snack_options(),
            'child_physical_activity' => nutrition_prf_physical_activity_options(),
            'pregnant_nutrition_status' => nutrition_prf_pregnant_status_options(),
            'planned_feeding' => $sets['feeding'],
            'lactating_feeding' => $sets['feeding'],
        ];

        $inputs = [];
        $checked = [];
        $memberLines = [];
        foreach ($grid as $cells) {
            $spec = (string) ($cells[$keyCol] ?? '');
            if ($spec === '') {
                continue;
            }
            foreach (explode('|', $spec) as $part) {
                $bits = explode(':', $part, 3);
                if ($bits[0] === 'member') {
                    $memberLines[] = $cells;
                } elseif ($bits[0] === 'in' && count($bits) === 3) {
                    $inputs[$bits[1]] = trim((string) ($cells[(int) $bits[2]] ?? ''));
                } elseif ($bits[0] === 'opt' && count($bits) === 3 && isset($optionLists[$bits[1]])) {
                    foreach (explode(',', $bits[2]) as $pair) {
                        [$col, $idx] = array_map('intval', explode('=', $pair) + [1 => -1]);
                        $label = $optionLists[$bits[1]][$idx] ?? null;
                        if ($label !== null && nutrition_household_survey_form_cell_checked((string) ($cells[$col] ?? ''), $label)) {
                            $checked[$bits[1]][] = $label;
                        }
                    }
                }
            }
        }

        $warnings = [];
        $single = static function (string $key) use (&$checked, &$warnings): string {
            $values = $checked[$key] ?? [];
            if (count($values) > 1) {
                $warnings[] = 'More than one option checked for ' . str_replace('_', ' ', $key) . '; using "' . $values[0] . '".';
            }

            return (string) ($values[0] ?? '');
        };
        $has = static fn (string $key, string $label): bool => in_array($label, $checked[$key] ?? [], true);

        $classification = $checked['classification'] ?? [];
        $fpMethods = $checked['family_planning_methods'] ?? [];
        $fpOther = trim((string) ($inputs['family_planning_methods_other'] ?? ''));
        if ($fpOther !== '' && in_array('Others', $fpMethods, true)) {
            $fpMethods = array_map(static fn (string $m): string => $m === 'Others' ? 'Others: ' . $fpOther : $m, $fpMethods);
        }

        $relationshipOptions = nutrition_relationship_options();
        $members = [];
        foreach ($memberLines as $cells) {
            $name = trim((string) ($cells[0] ?? ''));
            if ($name === '') {
                continue;
            }
            $relationship = trim((string) ($cells[1] ?? ''));
            foreach ($relationshipOptions as $opt) {
                if (strcasecmp($opt, $relationship) === 0) {
                    $relationship = $opt;
                    break;
                }
            }
            $genderRaw = strtolower(trim((string) ($cells[2] ?? '')));
            $gender = in_array($genderRaw, ['m', 'male', 'boy', 'lalaki'], true)
                ? 'Male'
                : (in_array($genderRaw, ['f', 'female', 'girl', 'babae'], true) ? 'Female' : '');
            $truthy = static function (string $v): bool {
                $v = strtolower(trim(str_replace("\u{2610}", '', $v)));

                return $v !== '' && !in_array($v, ['no', 'n', '0', '-', 'none', 'false'], true);
            };
            $num = static function (string $v): string {
                $v = trim(str_replace(',', '.', $v));

                return is_numeric($v) && (float) $v > 0 ? (string) round((float) $v, 2) : '';
            };
            $members[] = [
                'member_name' => $name,
                'relationship' => $relationship,
                'relationship_other' => '',
                'gender' => $gender,
                'birth_date' => nutrition_household_survey_form_date_mdy((string) ($cells[3] ?? '')),
                'is_pregnant' => $gender === 'Female' && $truthy((string) ($cells[4] ?? '')),
                'is_lactating' => $gender === 'Female' && $truthy((string) ($cells[5] ?? '')),
                'prev_date_measured' => nutrition_household_survey_form_date_mdy((string) ($cells[6] ?? '')),
                'prev_weight_kg' => $num((string) ($cells[7] ?? '')),
                'prev_height_cm' => $num((string) ($cells[8] ?? '')),
                'date_measured' => nutrition_household_survey_form_date_mdy((string) ($cells[9] ?? '')),
                'weight_kg' => $num((string) ($cells[10] ?? '')),
                'height_cm' => $num((string) ($cells[11] ?? '')),
            ];
        }

        $feedingOther = trim((string) ($inputs['feeding_other'] ?? ''));
        $monthsRaw = preg_replace('/\D+/', '', (string) ($inputs['pregnancy_months'] ?? '')) ?? '';
        $pregnantStatus = $single('pregnant_nutrition_status');
        foreach ($members as $i => $member) {
            if ($member['is_pregnant']) {
                $members[$i] += [
                    'pregnancy_months' => $monthsRaw !== '' && (int) $monthsRaw >= 1 && (int) $monthsRaw <= 9 ? (int) $monthsRaw : '',
                    'pregnant_nutrition_status' => $pregnantStatus,
                    'planned_exclusive_breastfeeding' => $has('planned_feeding', 'Exclusive Breastfeeding'),
                    'planned_mixed_feeding' => $has('planned_feeding', 'Mixed Feeding'),
                    'planned_bottle_feeding' => $has('planned_feeding', 'Bottle Feeding'),
                    'planned_other_feeding' => $has('planned_feeding', 'Others'),
                    'planned_other_specify' => $has('planned_feeding', 'Others') ? $feedingOther : '',
                ];
                break;
            }
        }
        foreach ($members as $i => $member) {
            if ($member['is_lactating']) {
                $members[$i] += [
                    'lactating_exclusive_breastfeeding' => $has('lactating_feeding', 'Exclusive Breastfeeding'),
                    'lactating_mixed_feeding' => $has('lactating_feeding', 'Mixed Feeding'),
                    'lactating_bottle_feeding' => $has('lactating_feeding', 'Bottle Feeding'),
                    'lactating_other_feeding' => $has('lactating_feeding', 'Others'),
                    'lactating_other_specify' => $has('lactating_feeding', 'Others') ? $feedingOther : '',
                ];
                break;
            }
        }

        $purokInput = trim((string) ($inputs['purok_number'] ?? ''));
        $purokInput = (string) (nutrition_purok_input_from_label($purokInput) ?: $purokInput);

        $payload = [
            'purok_number' => $purokInput,
            'purok_input' => $purokInput,
            'survey_date' => nutrition_household_survey_form_date_mdy((string) ($inputs['survey_date'] ?? '')),
            'bns_name' => (string) ($inputs['bns_name'] ?? ''),
            'house_hold_id' => '',
            'head_last_name' => (string) ($inputs['head_last_name'] ?? ''),
            'head_first_name' => (string) ($inputs['head_first_name'] ?? ''),
            'head_middle_name' => (string) ($inputs['head_middle_name'] ?? ''),
            'head_suffix' => (string) ($inputs['head_suffix'] ?? ''),
            'birth_date' => nutrition_household_survey_form_date_mdy((string) ($inputs['birth_date'] ?? '')),
            'gender' => $single('gender'),
            'occupation' => (string) ($inputs['occupation'] ?? ''),
            'is_4ps' => in_array("4P's Member", $classification, true),
            'is_ip' => in_array("IP's Member", $classification, true),
            'is_na_member' => in_array('N/A', $classification, true),
            'is_pwd' => in_array('PWD', $classification, true),
            'is_solo_parent' => in_array('Solo Parent', $classification, true),
            'house_ownership' => $single('house_ownership'),
            'house_ownership_other' => (string) ($inputs['house_ownership_other'] ?? ''),
            'dwelling_type' => $single('dwelling_type'),
            'toilet_type' => $single('toilet_type'),
            'garbage_disposal' => $single('garbage_disposal'),
            'garbage_uncollected_type' => $single('garbage_uncollected_type'),
            'water_source' => $single('water_source'),
            'food_production' => implode(', ', $checked['food_production'] ?? []),
            'uses_iodized_salt' => $has('hh_checks', 'HH using Iodized Salt'),
            'uses_sangkap_pinoy' => $has('hh_checks', 'HH using Products with Sangkap Pinoy Seal'),
            'has_carenderia' => $has('hh_checks', 'HH with Carenderia/Eatery'),
            'has_sari_sari_store' => $has('hh_checks', 'HH with Sari-Sari Store'),
            'practices_family_planning' => $single('practices_family_planning') === 'Yes',
            'family_planning_methods' => implode(', ', $fpMethods),
            'complementary_meals' => $single('complementary_meals'),
            'complementary_meals_other' => (string) ($inputs['complementary_meals_other'] ?? ''),
            'complementary_snacks' => $single('complementary_snacks'),
            'complementary_snacks_other' => (string) ($inputs['complementary_snacks_other'] ?? ''),
            'child_physical_activity' => $single('child_physical_activity'),
            'child_physical_activity_other' => (string) ($inputs['child_physical_activity_other'] ?? ''),
            'remarks' => '',
            'family_members' => $members,
        ];

        if ($payload['head_last_name'] === '' && $payload['head_first_name'] === '') {
            return ['ok' => false, 'error' => 'The uploaded form has no household head name. Fill in section II and upload again.'];
        }

        return ['ok' => true, 'payload' => $payload, 'warnings' => $warnings];
    }
}

if (!function_exists('barangay_xlsx_column_index_from_letters')) {
    function barangay_xlsx_column_index_from_letters(string $letters): int
    {
        $index = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $index = $index * 26 + (ord($ch) - 64);
        }

        return $index - 1;
    }
}

if (!function_exists('nutrition_household_survey_xlsx_household_headers')) {
    /**
     * @return array<int, string>
     */
    function nutrition_household_survey_xlsx_household_headers(): array
    {
        return [
            'Survey Date (YYYY-MM-DD)',
            'Purok No.',
            'Head Last Name',
            'Head First Name',
            'Head Middle Name',
            'Head Suffix',
            'Birthday (YYYY-MM-DD)',
            'Gender',
            'Occupation',
            '4Ps Member (YES/NO)',
            'PWD (YES/NO)',
            'IP Member (YES/NO)',
            'Solo Parent (YES/NO)',
        ];
    }
}

if (!function_exists('nutrition_household_survey_xlsx_member_headers')) {
    /**
     * @return array<int, string>
     */
    function nutrition_household_survey_xlsx_member_headers(): array
    {
        return [
            'Household Head Last Name',
            'Purok No.',
            'Member Name',
            'Relationship to Head',
            'Gender',
            'Birthday (YYYY-MM-DD)',
            'Pregnant (YES/NO)',
            'Lactating (YES/NO)',
            'Weight (kg)',
            'Height (cm)',
            'Weight for Age',
            'Height for Age',
            'Weight for Height/Length',
            'Pregnancy Months',
            'Planned Exclusive Breastfeeding (YES/NO)',
            'Planned Mixed Feeding (YES/NO)',
            'Planned Bottle Feeding (YES/NO)',
            'Planned Other Feeding (YES/NO)',
            'Planned Other Specify',
            'Lactating Exclusive Breastfeeding (YES/NO)',
            'Lactating Mixed Feeding (YES/NO)',
            'Lactating Bottle Feeding (YES/NO)',
            'Lactating Other Feeding (YES/NO)',
            'Lactating Other Specify',
        ];
    }
}

if (!function_exists('nutrition_stream_household_survey_xlsx_form')) {
    /**
     * @param array<string, mixed> $activeBarangay
     */
    function nutrition_stream_household_survey_xlsx_form(
        mysqli $con,
        array $activeBarangay,
        string $prefillPurok = '',
        int $householdRows = 30,
        int $memberRows = 60,
        string $layout = 'form'
    ): void {
        require_once __DIR__ . '/spreadsheet_export.php';

        $barangayId = (string) ($activeBarangay['id'] ?? '');
        $barangayName = (string) ($activeBarangay['barangay'] ?? 'Barangay');
        $district = (string) ($activeBarangay['district'] ?? 'Valencia City');
        $address = (string) ($activeBarangay['address'] ?? 'Valencia City, Bukidnon');
        $psgcCode = nutrition_barangay_psgc_code($con, $barangayId, $barangayName);
        $certHeader = barangay_certificate_header(['barangay' => $barangayName]);
        $relationshipOptions = nutrition_relationship_options();
        $reportHeader = 'Barangay ' . $barangayName . ' Nutrition Profiling';
        $nutritionOfficer = '';
        if ($barangayId !== '') {
            $settings = nutrition_load_settings($con, $barangayId, $barangayName);
            $reportHeader = trim((string) ($settings['report_header'] ?? $reportHeader));
            $nutritionOfficer = trim((string) ($settings['nutrition_officer'] ?? ''));
        }

        if ($layout === 'form') {
            $formMemberRows = $memberRows > 0 && $memberRows <= 12 ? $memberRows : 6;
            $formSheet = nutrition_household_survey_build_form_sheet(
                $barangayName,
                $district,
                $address,
                $psgcCode,
                $reportHeader,
                $certHeader,
                $relationshipOptions,
                $prefillPurok,
                $nutritionOfficer,
                $formMemberRows
            );
            $formRows = $formSheet['rows'];

            if (!class_exists('ZipArchive')) {
                nutrition_stream_household_survey_csv_form($barangayName, $formRows, [], [], true);
                return;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'nutrition_xlsx_');
            if ($tempFile === false) {
                throw new RuntimeException('Could not create temporary Excel file.');
            }
            $xlsxPath = $tempFile . '.xlsx';
            @unlink($tempFile);

            barangay_xlsx_create_file([
                ['name' => 'Survey Form', 'rows' => $formRows, 'options' => $formSheet['options']],
            ], $xlsxPath);

            $filename = barangay_xlsx_safe_filename('Household_Nutrition_Survey_Form_' . $barangayName);
            barangay_xlsx_stream_file($xlsxPath, $filename);

            return;
        }

        $householdHeaders = nutrition_household_survey_xlsx_household_headers();
        $memberHeaders = nutrition_household_survey_xlsx_member_headers();
        $householdRows = max(10, min(200, $householdRows));
        $memberRows = max(20, min(500, $memberRows));

        $instructionRows = [
            ['Household Nutrition Survey Form'],
            ['Barangay', $barangayName],
            ['District', $district],
            ['City / Address', $address],
            ['Barangay PSGC', $psgcCode],
            [''],
            ['Instructions'],
            ['1. Fill one household per row in the Households sheet.'],
            ['2. Add family members in the Family Members sheet. Match Household Head Last Name and Purok No. to the household.'],
            ['3. Required household fields: Survey Date, Purok No., Head Last Name, Head First Name, Gender.'],
            ['4. Use YES or NO for 4Ps, PWD, IP Member, Solo Parent, Pregnant, and Lactating columns.'],
            ['5. Use YYYY-MM-DD for all date fields.'],
            ['6. Nutrition results (WFA, HFA, WFH) may be left blank and computed when entered online.'],
            [''],
            ['Relationship Options'],
        ];
        foreach ($relationshipOptions as $option) {
            $instructionRows[] = ['', $option];
        }

        $householdSheetRows = [$householdHeaders];
        $sampleRow = array_fill(0, count($householdHeaders), '');
        if ($prefillPurok !== '') {
            $sampleRow[1] = $prefillPurok;
        }
        $householdSheetRows[] = $sampleRow;

        for ($i = 1; $i < $householdRows; $i++) {
            $householdSheetRows[] = array_fill(0, count($householdHeaders), '');
        }

        $memberSheetRows = [$memberHeaders];
        for ($i = 0; $i < $memberRows; $i++) {
            $memberSheetRows[] = array_fill(0, count($memberHeaders), '');
        }

        if (!class_exists('ZipArchive')) {
            nutrition_stream_household_survey_csv_form(
                $barangayName,
                $instructionRows,
                $householdSheetRows,
                $memberSheetRows
            );

            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'nutrition_xlsx_');
        if ($tempFile === false) {
            throw new RuntimeException('Could not create temporary Excel file.');
        }
        $xlsxPath = $tempFile . '.xlsx';
        @unlink($tempFile);

        barangay_xlsx_create_file([
            ['name' => 'Instructions', 'rows' => $instructionRows],
            ['name' => 'Households', 'rows' => $householdSheetRows],
            ['name' => 'Family Members', 'rows' => $memberSheetRows],
        ], $xlsxPath);

        $filename = barangay_xlsx_safe_filename('Household_Nutrition_Survey_' . $barangayName);
        barangay_xlsx_stream_file($xlsxPath, $filename);
    }
}

if (!function_exists('nutrition_stream_household_survey_csv_form')) {
    /**
     * @param array<int, array<int, string>> $instructionRows
     * @param array<int, array<int, string>> $householdSheetRows
     * @param array<int, array<int, string>> $memberSheetRows
     */
    function nutrition_stream_household_survey_csv_form(
        string $barangayName,
        array $instructionRows,
        array $householdSheetRows,
        array $memberSheetRows,
        bool $formOnly = false
    ): void {
        require_once __DIR__ . '/spreadsheet_export.php';

        $filename = barangay_xlsx_safe_filename(
            $formOnly ? 'Household_Nutrition_Survey_Form_' . $barangayName : 'Household_Nutrition_Survey_' . $barangayName,
            '.csv'
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            throw new RuntimeException('Could not create CSV download.');
        }

        foreach ($instructionRows as $row) {
            fputcsv($out, array_map('barangay_xlsx_cell_value', $row));
        }

        if ($householdSheetRows !== []) {
            fputcsv($out, []);
            fputcsv($out, ['--- Households ---']);
            foreach ($householdSheetRows as $row) {
                fputcsv($out, $row);
            }
        }

        if ($memberSheetRows !== []) {
            fputcsv($out, []);
            fputcsv($out, ['--- Family Members ---']);
            foreach ($memberSheetRows as $row) {
                fputcsv($out, $row);
            }
        }

        fclose($out);
        exit;
    }
}
