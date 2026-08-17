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

        $headSql = "SELECT COUNT(*) AS total
            FROM nutrition_household_survey
            WHERE UPPER(head_is_pregnant) = 'YES'
              {$statusFilterHead}
              {$barangayFilter}";
        $headResult = $con->query($headSql);
        $total += (int) ($headResult ? $headResult->fetch_assoc()['total'] ?? 0 : 0);

        if (barangay_table_exists($con, 'nutrition_household_family_member')) {
            $memberSql = "SELECT COUNT(*) AS total
                FROM nutrition_household_family_member m
                INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id
                WHERE UPPER(m.is_pregnant) = 'YES'
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
            'pregnant' => 0,
            'teenage_pregnant' => 0,
        ];

        if (!nutrition_table_exists($con) || !barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            return $defaults;
        }

        $childWhere = nutrition_children_where($con);
        $childWhere[] = "rs.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
        $childSql = 'SELECT COUNT(*) AS total
            FROM residence_information ri
            INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id'
            . barangay_sql_where($childWhere);
        $childResult = $con->query($childSql);
        $defaults['children'] = (int) ($childResult ? $childResult->fetch_assoc()['total'] ?? 0 : 0);

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
            $defaults['this_month'] = (int) ($monthStmt->get_result()->fetch_assoc()['total'] ?? 0);
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

        if (!nutrition_table_exists($con) || !barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            return $totals;
        }

        $childWhere = nutrition_children_where($con);
        $childSql = 'SELECT COUNT(*) AS total
            FROM residence_information ri
            INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id'
            . barangay_sql_where($childWhere);
        $childResult = $con->query($childSql);
        $totals['children'] = (int) ($childResult ? $childResult->fetch_assoc()['total'] ?? 0 : 0);

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
            $totals['assessed'] = (int) ($row['total'] ?? 0);
            $totals['at_risk'] = (int) ($row['at_risk'] ?? 0);
        }

        $totals['pending'] = max(0, $totals['children'] - $totals['assessed']);

        $monthResult = $con->query(
            "SELECT COUNT(*) AS total FROM nutrition_assessment
             WHERE assessment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
        $totals['this_month'] = (int) ($monthResult ? $monthResult->fetch_assoc()['total'] ?? 0 : 0);
        $totals['pregnant'] = nutrition_pregnant_count($con);
        $totals['teenage_pregnant'] = nutrition_teenage_pregnant_count($con);

        return $totals;
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
                'SELECT barangay_id, COUNT(*) AS total FROM nutrition_household_survey GROUP BY barangay_id'
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
            $atRisk = $scoped['underweight'] + $scoped['wasted'] + $scoped['severely_wasted']
                + $scoped['stunted'] + $scoped['overweight'] + $scoped['obese'];

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

        $atRisk = ($statusTotals['underweight'] ?? 0) + ($statusTotals['wasted'] ?? 0)
            + ($statusTotals['severely_wasted'] ?? 0) + ($statusTotals['stunted'] ?? 0)
            + ($statusTotals['overweight'] ?? 0) + ($statusTotals['obese'] ?? 0);

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
        nutrition_ensure_column($con, 'nutrition_household_survey', 'toilet_type', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `house_ownership_other`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'garbage_disposal', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `toilet_type`");
        nutrition_ensure_column($con, 'nutrition_household_survey', 'garbage_uncollected_type', "VARCHAR(120) NOT NULL DEFAULT '' AFTER `garbage_disposal`");
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
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'age_months', 'INT DEFAULT NULL AFTER `date_measured`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'weight_for_age', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `age_months`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'height_for_age', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `weight_for_age`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'weight_for_height', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `height_for_age`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'muac_cm', 'DECIMAL(4,1) DEFAULT NULL AFTER `weight_for_height`');
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'muac_status', "VARCHAR(32) NOT NULL DEFAULT '' AFTER `muac_cm`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'edema', "VARCHAR(16) NOT NULL DEFAULT '' AFTER `muac_status`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'disability', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `edema`");
        nutrition_ensure_column($con, 'nutrition_household_family_member', 'pregnant_nutrition_status', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `pregnancy_months`");
        nutrition_ensure_column($con, 'nutrition_settings', 'kobo_enabled', "VARCHAR(8) NOT NULL DEFAULT 'NO' AFTER `enable_barangay_survey`");
        nutrition_ensure_column($con, 'nutrition_settings', 'kobo_server_url', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `kobo_enabled`");
        nutrition_ensure_column($con, 'nutrition_settings', 'kobo_api_token', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `kobo_server_url`");
        nutrition_ensure_column($con, 'nutrition_settings', 'kobo_asset_uid', "VARCHAR(64) NOT NULL DEFAULT '' AFTER `kobo_api_token`");
        nutrition_ensure_column($con, 'nutrition_settings', 'kobo_form_url', "VARCHAR(500) NOT NULL DEFAULT '' AFTER `kobo_asset_uid`");
        nutrition_ensure_column($con, 'nutrition_settings', 'kobo_last_synced_at', 'DATETIME DEFAULT NULL AFTER `kobo_form_url`');
        nutrition_ensure_column($con, 'nutrition_settings', 'bnp_form_c1', "LONGTEXT NULL AFTER `kobo_last_synced_at`");

        if (!barangay_table_exists($con, 'nutrition_kobo_submission')) {
            $con->query("CREATE TABLE IF NOT EXISTS `nutrition_kobo_submission` (
                `submission_id` VARCHAR(64) NOT NULL,
                `barangay_id` VARCHAR(32) NOT NULL,
                `asset_uid` VARCHAR(64) NOT NULL DEFAULT '',
                `submitted_at` DATETIME DEFAULT NULL,
                `household_label` VARCHAR(255) NOT NULL DEFAULT '',
                `purok_label` VARCHAR(64) NOT NULL DEFAULT '',
                `respondent_name` VARCHAR(255) NOT NULL DEFAULT '',
                `raw_payload` LONGTEXT DEFAULT NULL,
                `date_synced` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`submission_id`),
                KEY `idx_nks_barangay` (`barangay_id`),
                KEY `idx_nks_submitted` (`submitted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

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
            'kobo_enabled' => 'NO',
            'kobo_server_url' => 'https://kf.kobotoolbox.org',
            'kobo_api_token' => '',
            'kobo_asset_uid' => '',
            'kobo_form_url' => '',
            'kobo_last_synced_at' => '',
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
              enable_household_survey, enable_barangay_survey, kobo_enabled, kobo_server_url, kobo_api_token,
              kobo_asset_uid, kobo_form_url, bnp_form_c1, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
             nutrition_officer = VALUES(nutrition_officer),
             contact_number = VALUES(contact_number),
             assessment_frequency = VALUES(assessment_frequency),
             report_header = VALUES(report_header),
             psfc_code = VALUES(psfc_code),
             enable_household_survey = VALUES(enable_household_survey),
             enable_barangay_survey = VALUES(enable_barangay_survey),
             kobo_enabled = VALUES(kobo_enabled),
             kobo_server_url = VALUES(kobo_server_url),
             kobo_api_token = VALUES(kobo_api_token),
             kobo_asset_uid = VALUES(kobo_asset_uid),
             kobo_form_url = VALUES(kobo_form_url),
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
            'ssssssssssssss',
            $barangayId,
            $data['nutrition_officer'],
            $data['contact_number'],
            $data['assessment_frequency'],
            $data['report_header'],
            $data['psfc_code'],
            $data['enable_household_survey'],
            $data['enable_barangay_survey'],
            $data['kobo_enabled'],
            $data['kobo_server_url'],
            $data['kobo_api_token'],
            $data['kobo_asset_uid'],
            $data['kobo_form_url'],
            $bnpFormC1
        );
        $stmt->execute();
        $ok = $stmt->affected_rows >= 0;
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('nutrition_kobo_is_configured')) {
    function nutrition_kobo_is_configured(array $settings): bool
    {
        if (($settings['kobo_enabled'] ?? 'NO') !== 'YES') {
            return false;
        }

        return trim((string) ($settings['kobo_server_url'] ?? '')) !== ''
            && trim((string) ($settings['kobo_api_token'] ?? '')) !== ''
            && trim((string) ($settings['kobo_asset_uid'] ?? '')) !== '';
    }
}

if (!function_exists('nutrition_kobo_normalize_server_url')) {
    function nutrition_kobo_normalize_server_url(string $serverUrl): string
    {
        $serverUrl = trim($serverUrl);
        if ($serverUrl === '') {
            return '';
        }

        return rtrim($serverUrl, '/');
    }
}

if (!function_exists('nutrition_kobo_api_request')) {
    /**
     * @return array{ok:bool,status:int,body:string,error?:string}
     */
    function nutrition_kobo_api_request(string $serverUrl, string $apiToken, string $path): array
    {
        $serverUrl = nutrition_kobo_normalize_server_url($serverUrl);
        if ($serverUrl === '' || $apiToken === '') {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'KoBoToolbox server URL and API token are required.'];
        }

        $url = $serverUrl . $path;
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'PHP cURL extension is required for KoBoToolbox sync.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $apiToken,
                'Accept: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'status' => $status, 'body' => '', 'error' => $curlError !== '' ? $curlError : 'KoBoToolbox request failed.'];
        }

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => (string) $body,
            'error' => $status >= 200 && $status < 300 ? '' : 'KoBoToolbox API returned HTTP ' . $status,
        ];
    }
}

if (!function_exists('nutrition_kobo_extract_field')) {
    function nutrition_kobo_extract_field(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = trim((string) $payload[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('nutrition_kobo_parse_submission')) {
    /**
     * @return array{household_label:string,purok_label:string,respondent_name:string,submitted_at:?string}
     */
    function nutrition_kobo_parse_submission(array $payload): array
    {
        $submittedAt = trim((string) ($payload['_submission_time'] ?? $payload['end'] ?? ''));
        $submittedAtValue = $submittedAt !== '' ? date('Y-m-d H:i:s', strtotime($submittedAt)) : null;

        return [
            'household_label' => nutrition_kobo_extract_field($payload, [
                'household_id', 'house_hold_id', 'household_head', 'head_of_household', 'respondent_household',
            ]),
            'purok_label' => nutrition_kobo_extract_field($payload, ['purok', 'purok_number', 'purok_label']),
            'respondent_name' => nutrition_kobo_extract_field($payload, [
                'respondent_name', 'head_last_name', 'head_first_name', 'enumerator', 'name',
            ]),
            'submitted_at' => $submittedAtValue,
        ];
    }
}

if (!function_exists('nutrition_kobo_fetch_submissions')) {
    /**
     * @return array{ok:bool,submissions:array<int,array<string,mixed>>,error?:string}
     */
    function nutrition_kobo_fetch_submissions(array $settings): array
    {
        if (!nutrition_kobo_is_configured($settings)) {
            return ['ok' => false, 'submissions' => [], 'error' => 'KoBoToolbox is not configured for this barangay.'];
        }

        $serverUrl = nutrition_kobo_normalize_server_url((string) $settings['kobo_server_url']);
        $apiToken = trim((string) $settings['kobo_api_token']);
        $assetUid = trim((string) $settings['kobo_asset_uid']);
        $submissions = [];
        $nextUrl = '/api/v2/assets/' . rawurlencode($assetUid) . '/data/?format=json';

        while ($nextUrl !== '') {
            $response = nutrition_kobo_api_request($serverUrl, $apiToken, $nextUrl);
            if (!$response['ok']) {
                return ['ok' => false, 'submissions' => [], 'error' => $response['error'] ?? 'Could not fetch KoBoToolbox submissions.'];
            }

            $decoded = json_decode($response['body'], true);
            if (!is_array($decoded)) {
                return ['ok' => false, 'submissions' => [], 'error' => 'Invalid KoBoToolbox response.'];
            }

            $results = $decoded['results'] ?? [];
            if (!is_array($results)) {
                $results = [];
            }

            foreach ($results as $row) {
                if (is_array($row)) {
                    $submissions[] = $row;
                }
            }

            $next = trim((string) ($decoded['next'] ?? ''));
            if ($next === '') {
                $nextUrl = '';
                continue;
            }

            $parsed = parse_url($next);
            $nextUrl = ($parsed['path'] ?? '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        }

        return ['ok' => true, 'submissions' => $submissions];
    }
}

if (!function_exists('nutrition_kobo_sync_submissions')) {
    /**
     * @return array{ok:bool,synced:int,total:int,error?:string}
     */
    function nutrition_kobo_sync_submissions(mysqli $con, string $barangayId, array $settings): array
    {
        nutrition_ensure_module_tables($con);
        $fetch = nutrition_kobo_fetch_submissions($settings);
        if (!$fetch['ok']) {
            return ['ok' => false, 'synced' => 0, 'total' => 0, 'error' => $fetch['error'] ?? 'Sync failed.'];
        }

        $assetUid = trim((string) ($settings['kobo_asset_uid'] ?? ''));
        $stmt = $con->prepare(
            'INSERT INTO nutrition_kobo_submission
             (submission_id, barangay_id, asset_uid, submitted_at, household_label, purok_label, respondent_name, raw_payload, date_synced)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
             asset_uid = VALUES(asset_uid),
             submitted_at = VALUES(submitted_at),
             household_label = VALUES(household_label),
             purok_label = VALUES(purok_label),
             respondent_name = VALUES(respondent_name),
             raw_payload = VALUES(raw_payload),
             date_synced = NOW()'
        );
        if (!$stmt) {
            return ['ok' => false, 'synced' => 0, 'total' => 0, 'error' => 'Database error: ' . $con->error];
        }

        $synced = 0;
        foreach ($fetch['submissions'] as $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $submissionId = trim((string) ($payload['_id'] ?? $payload['id'] ?? ''));
            if ($submissionId === '') {
                continue;
            }

            $parsed = nutrition_kobo_parse_submission($payload);
            $rawJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $submittedAt = $parsed['submitted_at'] ?? '';
            $stmt->bind_param(
                'ssssssss',
                $submissionId,
                $barangayId,
                $assetUid,
                $submittedAt,
                $parsed['household_label'],
                $parsed['purok_label'],
                $parsed['respondent_name'],
                $rawJson
            );
            if ($stmt->execute()) {
                $synced++;
            }
        }
        $stmt->close();

        $update = $con->prepare('UPDATE nutrition_settings SET kobo_last_synced_at = NOW() WHERE barangay_id = ?');
        if ($update) {
            $update->bind_param('s', $barangayId);
            $update->execute();
            $update->close();
        }

        return [
            'ok' => true,
            'synced' => $synced,
            'total' => count($fetch['submissions']),
        ];
    }
}

if (!function_exists('nutrition_kobo_list_submissions')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function nutrition_kobo_list_submissions(mysqli $con, string $barangayId, int $limit = 100): array
    {
        if (!barangay_table_exists($con, 'nutrition_kobo_submission')) {
            return [];
        }

        $stmt = $con->prepare(
            'SELECT submission_id, submitted_at, household_label, purok_label, respondent_name, date_synced
             FROM nutrition_kobo_submission
             WHERE barangay_id = ?
             ORDER BY submitted_at DESC, date_synced DESC
             LIMIT ?'
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('si', $barangayId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows ?: [];
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
            echo json_encode(['error' => 'Only Nutrition Super Admin or Super Super Admin can edit registered household surveys.']);
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
            'Son',
            'Daughter',
            'Father',
            'Mother',
            'Brother',
            'Sister',
            'Grandchild',
            'Grandparent',
            'Other Relative',
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

if (!function_exists('nutrition_prf_toilet_options')) {
    /** @return array<int, string> */
    function nutrition_prf_toilet_options(): array
    {
        return ['Water Sealed', 'Covered Pit', 'Open Pit', 'No Toilet'];
    }
}

if (!function_exists('nutrition_prf_garbage_options')) {
    /** @return array<int, string> */
    function nutrition_prf_garbage_options(): array
    {
        return ['Collected', 'Uncollected'];
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
            'Pipe Water System',
            'Communal Water Source',
            'Well',
            'Spring',
            'Mineral',
        ];
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
        return ['Natural', 'IUD', 'Pills', 'Ligation', 'Vasectomy', 'Depo', 'Implanon Implant', 'Condom', 'BTL', 'DMPA'];
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
            if ($dateMeasured === null) {
                $dateMeasured = $referenceDate;
            }
            $memberReferenceDate = $dateMeasured !== null && $dateMeasured !== '' ? $dateMeasured : $referenceDate;
            $ageMonthsOnly = nutrition_age_in_months($birthDateValue, $memberReferenceDate);
            if (!nutrition_is_child_0_to_5($ageMonthsOnly)) {
                $weightKg = 0;
                $heightCm = 0;
                $dateMeasured = null;
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
                'relationship' => trim((string) ($row['relationship'] ?? '')),
                'gender' => $gender,
                'birth_date' => $birthDateValue,
                'weight_kg' => $weightKg > 0 ? $weightKg : null,
                'height_cm' => $heightCm > 0 ? $heightCm : null,
                'date_measured' => ($dateMeasured !== null && $dateMeasured !== '' && ($weightKg > 0 || $heightCm > 0)) ? $dateMeasured : null,
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
              date_measured, age_months, weight_for_age, height_for_age, weight_for_height, is_pregnant, is_lactating, pregnancy_months,
              pregnant_nutrition_status,
              planned_exclusive_breastfeeding, planned_mixed_feeding, planned_bottle_feeding, planned_other_feeding,
              planned_other_specify, lactating_exclusive_breastfeeding, lactating_mixed_feeding,
              lactating_bottle_feeding, lactating_other_feeding, lactating_other_specify, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            $sortOrder = (int) $index;
            $pregnantNutritionStatus = (string) ($member['pregnant_nutrition_status'] ?? '');

            // Nullable numeric fields are bound as strings to avoid PHP 8 bind_param NULL issues.
            $ageMonthsBind = $ageMonthsValue === null ? null : (string) $ageMonthsValue;
            $pregnancyMonthsBind = $pregnancyMonthsValue === null ? null : (string) $pregnancyMonthsValue;
            $weightKgBind = $weightKgValue === null ? null : (string) $weightKgValue;
            $heightCmBind = $heightCmValue === null ? null : (string) $heightCmValue;

            $stmt->bind_param(
                'ssssssssssssssssssssssssssssi',
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
            'toilet' => [
                'Water Sealed' => nutrition_pregnant_zero_columns(),
                'Covered Pit' => nutrition_pregnant_zero_columns(),
                'Open Pit' => nutrition_pregnant_zero_columns(),
                'No Toilet' => nutrition_pregnant_zero_columns(),
            ],
            'water' => [
                'Pipe Water System' => nutrition_pregnant_zero_columns(),
                'Communal Water Source' => nutrition_pregnant_zero_columns(),
                'Mineral' => nutrition_pregnant_zero_columns(),
                'Well' => nutrition_pregnant_zero_columns(),
                'Spring' => nutrition_pregnant_zero_columns(),
            ],
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

        $garbageDisposal = trim((string) ($survey['garbage_disposal'] ?? ''));
        if (strcasecmp($garbageDisposal, 'Collected') === 0) {
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

        $toiletType = trim((string) ($survey['toilet_type'] ?? ''));
        if ($toiletType !== '' && isset($buckets['toilet'][$toiletType])) {
            $bump($buckets['toilet'], $toiletType, $col);
        }

        $waterSource = trim((string) ($survey['water_source'] ?? ''));
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
            'toilet' => [
                'Water Sealed' => nutrition_pregnant_zero_columns(),
                'Covered Pit' => nutrition_pregnant_zero_columns(),
                'Open Pit' => nutrition_pregnant_zero_columns(),
                'No Toilet' => nutrition_pregnant_zero_columns(),
            ],
            'water' => [
                'Pipe Water System' => nutrition_pregnant_zero_columns(),
                'Communal Water Source' => nutrition_pregnant_zero_columns(),
                'Mineral' => nutrition_pregnant_zero_columns(),
                'Well' => nutrition_pregnant_zero_columns(),
                'Spring' => nutrition_pregnant_zero_columns(),
            ],
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
        $memberRows = max(3, min(12, $memberRows));
        $feedingOptions = 'Exclusive Breastfeeding, Mixed Feeding, Bottle Feeding, Others';
        $blank = '';

        $rows = [
            ['HOUSEHOLD NUTRITION SURVEY FORM'],
            [$reportHeader],
            [
                ($certHeader['country'] ?? '') . ' | '
                . ($certHeader['province'] ?? '') . ' | '
                . ($certHeader['city'] ?? ''),
            ],
            [
                ($certHeader['barangay_line'] ?? ('Barangay ' . $barangayName))
                . ($district !== '' ? ' | ' . $district : ''),
            ],
        ];

        if ($psgcCode !== '') {
            $rows[] = ['Barangay PSGC', $psgcCode];
        }
        if ($nutritionOfficer !== '') {
            $rows[] = ['Nutrition Officer', $nutritionOfficer];
        }

        $rows[] = [];
        $rows[] = ['I. SURVEY INFORMATION'];
        $rows[] = ['Survey Date (YYYY-MM-DD)', $blank];
        $rows[] = ['Purok No.', $prefillPurok];
        $rows[] = [];
        $rows[] = ['II. HOUSEHOLD HEAD'];
        $rows[] = ['Last Name', $blank];
        $rows[] = ['First Name', $blank];
        $rows[] = ['Middle Name', $blank];
        $rows[] = ['Suffix', $blank];
        $rows[] = ['Birthday (YYYY-MM-DD)', $blank];
        $rows[] = ['Gender (Male/Female)', $blank];
        $rows[] = ['Occupation', $blank];
        $rows[] = [];
        $rows[] = ['III. HOUSEHOLD CLASSIFICATION'];
        $rows[] = ['4Ps Member (YES/NO)', $blank];
        $rows[] = ['PWD (YES/NO)', $blank];
        $rows[] = ['IP Member (YES/NO)', $blank];
        $rows[] = ['Solo Parent (YES/NO)', $blank];
        $rows[] = [];
        $rows[] = ['IV. FAMILY MEMBERS'];
        $rows[] = [
            'Name',
            'Relationship',
            'Gender',
            'Birthday',
            'Pregnant (YES/NO)',
            'Lactating (YES/NO)',
            'Weight (kg)',
            'Height (cm)',
            'Nutrition Result (WFA / HFA / WFH)',
        ];

        for ($i = 0; $i < $memberRows; $i++) {
            $rows[] = array_fill(0, 9, $blank);
        }

        $rows[] = [];
        $rows[] = ['Notes'];
        $rows[] = ['Relationship options', implode(', ', $relationshipOptions)];
        $rows[] = ['Pregnancy months (if pregnant)', $blank];
        $rows[] = ['Feeding methods', $feedingOptions];
        $rows[] = ['Other feeding specify', $blank];
        $rows[] = [];
        $rows[] = ['V. ACKNOWLEDGEMENT'];
        $rows[] = ['I certify that the information provided above is true and correct to the best of my knowledge.'];
        $rows[] = [];
        $rows[] = ['Signature of Household Head', 'Date', 'Enumerator / Nutrition Officer'];
        $rows[] = array_fill(0, 3, $blank);

        return $rows;
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
            $formRows = nutrition_household_survey_build_form_layout_rows(
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
                ['name' => 'Survey Form', 'rows' => $formRows],
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
            fputcsv($out, $row);
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
