<?php

/**
 * MELLPI PRO FORM CM — City/Municipality Profile Sheet
 * Registration storage + report builder (live stats + saved registration fields).
 */

require_once __DIR__ . '/nutrition_context.php';
require_once __DIR__ . '/nutrition_eopt_reports.php';
require_once __DIR__ . '/nutrition_bnp_reports.php';

if (!function_exists('nutrition_mellpi_ensure_table')) {
    function nutrition_mellpi_ensure_table(mysqli $con): void
    {
        if (!barangay_table_exists($con, 'nutrition_mellpi_city_profile')) {
            $con->query(
                "CREATE TABLE IF NOT EXISTS `nutrition_mellpi_city_profile` (
                    `profile_id` VARCHAR(64) NOT NULL DEFAULT 'valencia',
                    `city_name` VARCHAR(120) NOT NULL DEFAULT 'City of Valencia',
                    `province` VARCHAR(120) NOT NULL DEFAULT 'Bukidnon',
                    `income_class` VARCHAR(64) NOT NULL DEFAULT '',
                    `date_of_monitoring` DATE DEFAULT NULL,
                    `period_covered` VARCHAR(120) NOT NULL DEFAULT '',
                    `profile_json` LONGTEXT DEFAULT NULL,
                    `updated_by` VARCHAR(32) DEFAULT NULL,
                    `updated_at` DATETIME DEFAULT NULL,
                    PRIMARY KEY (`profile_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
        } else {
            // Allow longer ids for per-barangay profiles (brgy_{id}).
            @$con->query(
                "ALTER TABLE `nutrition_mellpi_city_profile`
                 MODIFY `profile_id` VARCHAR(64) NOT NULL DEFAULT 'valencia'"
            );
        }
    }
}

if (!function_exists('nutrition_mellpi_profile_id')) {
    function nutrition_mellpi_profile_id(?string $barangayId = null): string
    {
        $barangayId = trim((string) $barangayId);
        if ($barangayId === '') {
            return 'valencia';
        }

        return 'brgy_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $barangayId);
    }
}

if (!function_exists('nutrition_mellpi_default_profile')) {
    /**
     * @return array<string, mixed>
     */
    function nutrition_mellpi_default_profile(): array
    {
        $year = (int) date('Y');
        $years = [($year - 2), ($year - 1), $year];

        return [
            'city_name' => 'City of Valencia',
            'province' => 'Bukidnon',
            'income_class' => '',
            'date_of_monitoring' => date('Y-m-d'),
            'period_covered' => 'CY ' . $year,
            'community' => [
                'income_classification' => '',
                'hh_safe_water' => '',
                'hh_sanitary_toilets' => '',
                'day_care_centers' => '',
                'public_elementary_schools' => '',
                'public_secondary_schools' => '',
                'barangay_health_stations' => '',
                'retail_outlets' => '',
                'bakeries' => '',
                'public_markets' => '',
                'transport_terminals' => '',
                'pct_at_risk_pregnant' => '',
                'pct_exclusive_bf_5th_month' => '',
                'idd_pregnant' => '',
                'idd_lactating' => '',
                'terrain' => '',
            ],
            'population_snapshot' => [
                '0_59_estimated' => '',
                '0_59_actual' => '',
                'pregnant_estimated' => '',
                'pregnant_actual' => '',
                'lactating_estimated' => '',
                'lactating_actual' => '',
            ],
            'years' => $years,
            'preschool' => [
                'wfa' => [
                    'Normal' => array_fill_keys($years, ''),
                    'Underweight' => array_fill_keys($years, ''),
                    'Severely Underweight' => array_fill_keys($years, ''),
                    'Overweight' => array_fill_keys($years, ''),
                ],
                'wfh' => [
                    'Normal' => array_fill_keys($years, ''),
                    'Wasted' => array_fill_keys($years, ''),
                    'Severely Wasted' => array_fill_keys($years, ''),
                    'Overweight' => array_fill_keys($years, ''),
                    'Obese' => array_fill_keys($years, ''),
                ],
                'hfa' => [
                    'Normal' => array_fill_keys($years, ''),
                    'Stunted' => array_fill_keys($years, ''),
                    'Severely Stunted' => array_fill_keys($years, ''),
                    'Tall' => array_fill_keys($years, ''),
                ],
            ],
            'school' => [
                'Normal' => array_fill_keys($years, ''),
                'Wasted' => array_fill_keys($years, ''),
                'Severely Wasted' => array_fill_keys($years, ''),
                'Overweight' => array_fill_keys($years, ''),
                'Obese' => array_fill_keys($years, ''),
            ],
            'pregnant_status' => [
                'Normal' => array_fill_keys($years, ''),
                'Nutritionally at-risk' => array_fill_keys($years, ''),
                'Overweight' => array_fill_keys($years, ''),
                'Obese' => array_fill_keys($years, ''),
            ],
            'bns' => [
                'total' => '',
                'new' => '',
                'existing' => '',
            ],
            'hazards' => [
                ['type_month' => '', 'affected' => ''],
                ['type_month' => '', 'affected' => ''],
                ['type_month' => '', 'affected' => ''],
            ],
            'land_use' => [
                'Residential' => ['land_area' => '', 'bgy_covered' => '', 'remarks' => ''],
                'Commercial' => ['land_area' => '', 'bgy_covered' => '', 'remarks' => ''],
                'Industrial' => ['land_area' => '', 'bgy_covered' => '', 'remarks' => ''],
                'Agricultural' => ['land_area' => '', 'bgy_covered' => '', 'remarks' => ''],
                'Forest land/Mineral land/National Park' => ['land_area' => '', 'bgy_covered' => '', 'remarks' => ''],
            ],
        ];
    }
}

if (!function_exists('nutrition_mellpi_merge_profile')) {
    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $overlay
     * @return array<string, mixed>
     */
    function nutrition_mellpi_merge_profile(array $base, array $overlay): array
    {
        foreach ($overlay as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = nutrition_mellpi_merge_profile($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}

if (!function_exists('nutrition_mellpi_load_profile')) {
    /**
     * @return array<string, mixed>
     */
    function nutrition_mellpi_load_profile(mysqli $con, ?string $barangayId = null): array
    {
        nutrition_mellpi_ensure_table($con);
        $defaults = nutrition_mellpi_default_profile();
        $barangayId = trim((string) $barangayId);
        if ($barangayId !== '') {
            $defaults['city_name'] = '';
            $defaults['scope'] = 'barangay';
            $defaults['barangay_id'] = $barangayId;
        } else {
            $defaults['scope'] = 'city';
        }

        $stmt = $con->prepare(
            'SELECT city_name, province, income_class, date_of_monitoring, period_covered, profile_json
             FROM nutrition_mellpi_city_profile WHERE profile_id = ? LIMIT 1'
        );
        if (!$stmt) {
            return $defaults;
        }
        $id = nutrition_mellpi_profile_id($barangayId !== '' ? $barangayId : null);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return $defaults;
        }

        $json = [];
        $raw = trim((string) ($row['profile_json'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $merged = nutrition_mellpi_merge_profile($defaults, $json);
        $merged['city_name'] = trim((string) ($row['city_name'] ?? $merged['city_name']));
        $merged['province'] = trim((string) ($row['province'] ?? $merged['province']));
        $merged['income_class'] = trim((string) ($row['income_class'] ?? $merged['income_class']));
        $merged['date_of_monitoring'] = (string) ($row['date_of_monitoring'] ?? $merged['date_of_monitoring']);
        $merged['period_covered'] = trim((string) ($row['period_covered'] ?? $merged['period_covered']));
        $merged['scope'] = $barangayId !== '' ? 'barangay' : 'city';
        if ($barangayId !== '') {
            $merged['barangay_id'] = $barangayId;
        }

        return $merged;
    }
}

if (!function_exists('nutrition_mellpi_save_profile')) {
    /**
     * @param array<string, mixed> $data
     */
    function nutrition_mellpi_save_profile(mysqli $con, array $data, ?string $updatedBy = null, ?string $barangayId = null): bool
    {
        nutrition_mellpi_ensure_table($con);
        $barangayId = trim((string) ($barangayId ?? ($data['barangay_id'] ?? '')));
        $defaults = nutrition_mellpi_default_profile();
        $merged = nutrition_mellpi_merge_profile($defaults, $data);

        $cityName = trim((string) ($merged['city_name'] ?? ($barangayId !== '' ? '' : 'City of Valencia')));
        $province = trim((string) ($merged['province'] ?? 'Bukidnon'));
        $incomeClass = trim((string) ($merged['income_class'] ?? ''));
        $dateMon = trim((string) ($merged['date_of_monitoring'] ?? ''));
        if ($dateMon === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateMon)) {
            $dateMon = date('Y-m-d');
        }
        $period = trim((string) ($merged['period_covered'] ?? ''));

        $payload = $merged;
        unset(
            $payload['city_name'],
            $payload['province'],
            $payload['income_class'],
            $payload['date_of_monitoring'],
            $payload['period_covered'],
            $payload['scope'],
            $payload['barangay_id']
        );
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{}';
        }

        $id = nutrition_mellpi_profile_id($barangayId !== '' ? $barangayId : null);
        $stmt = $con->prepare(
            'INSERT INTO nutrition_mellpi_city_profile
                (profile_id, city_name, province, income_class, date_of_monitoring, period_covered, profile_json, updated_by, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                city_name = VALUES(city_name),
                province = VALUES(province),
                income_class = VALUES(income_class),
                date_of_monitoring = VALUES(date_of_monitoring),
                period_covered = VALUES(period_covered),
                profile_json = VALUES(profile_json),
                updated_by = VALUES(updated_by),
                updated_at = NOW()'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param(
            'ssssssss',
            $id,
            $cityName,
            $province,
            $incomeClass,
            $dateMon,
            $period,
            $json,
            $updatedBy
        );
        $ok = $stmt->execute();
        $stmt->close();

        return (bool) $ok;
    }
}

if (!function_exists('nutrition_mellpi_map_pregnant_status')) {
    function nutrition_mellpi_map_pregnant_status(string $raw): string
    {
        return match (strtolower(trim($raw))) {
            'normal' => 'Normal',
            'overweight' => 'Overweight',
            'obese' => 'Obese',
            'teenage', 'underweight', 'old age', 'nutritionally at-risk', 'nutritionally at risk' => 'Nutritionally at-risk',
            default => '',
        };
    }
}

if (!function_exists('nutrition_mellpi_encoded_member_counts')) {
    /**
     * School-age weight-for-height and pregnant status from encoded household surveys.
     *
     * @return array{
     *   school: array<string, int>,
     *   pregnant: array<string, int>,
     *   lactating: int,
     *   exclusive_bf: int
     * }
     */
    function nutrition_mellpi_encoded_member_counts(mysqli $con, string $barangayId): array
    {
        $school = [
            'Normal' => 0,
            'Wasted' => 0,
            'Severely Wasted' => 0,
            'Overweight' => 0,
            'Obese' => 0,
        ];
        $pregnant = [
            'Normal' => 0,
            'Nutritionally at-risk' => 0,
            'Overweight' => 0,
            'Obese' => 0,
        ];
        $lactating = 0;
        $exclusiveBf = 0;
        $schoolMap = [
            'normal' => 'Normal',
            'wasted' => 'Wasted',
            'severely_wasted' => 'Severely Wasted',
            'overweight' => 'Overweight',
            'obese' => 'Obese',
        ];

        if (!barangay_table_exists($con, 'nutrition_household_family_member')
            || !barangay_table_exists($con, 'nutrition_household_survey')
        ) {
            return [
                'school' => $school,
                'pregnant' => $pregnant,
                'lactating' => 0,
                'exclusive_bf' => 0,
            ];
        }

        $where = nutrition_survey_current_condition($con, 's');
        $barangayId = trim($barangayId);
        if ($barangayId !== '') {
            $where .= " AND s.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
        }

        $sql = 'SELECT m.age_months, m.birth_date, m.weight_for_height, m.is_pregnant, m.pregnant_nutrition_status,
                       m.is_lactating, m.lactating_exclusive_breastfeeding
                FROM nutrition_household_family_member m
                INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id
                WHERE ' . $where;
        $result = $con->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ageMonths = isset($row['age_months']) && $row['age_months'] !== '' && $row['age_months'] !== null
                    ? (int) $row['age_months']
                    : nutrition_age_in_months(
                        trim((string) ($row['birth_date'] ?? '')) !== '' ? (string) $row['birth_date'] : null
                    );
                if (nutrition_bnp_member_in_age_band($ageMonths, 'school')) {
                    $growth = nutrition_survey_growth_label_to_status((string) ($row['weight_for_height'] ?? ''));
                    if ($growth !== null && isset($schoolMap[$growth])) {
                        $school[$schoolMap[$growth]]++;
                    }
                }
                if (strtoupper((string) ($row['is_pregnant'] ?? 'NO')) === 'YES') {
                    $status = nutrition_mellpi_map_pregnant_status((string) ($row['pregnant_nutrition_status'] ?? ''));
                    if ($status !== '' && isset($pregnant[$status])) {
                        $pregnant[$status]++;
                    }
                }
                if (strtoupper((string) ($row['is_lactating'] ?? 'NO')) === 'YES') {
                    $lactating++;
                    if (strtoupper((string) ($row['lactating_exclusive_breastfeeding'] ?? 'NO')) === 'YES') {
                        $exclusiveBf++;
                    }
                }
            }
        }

        $headSql = 'SELECT head_is_pregnant, head_pregnant_nutrition_status, head_is_lactating, head_lactating_exclusive_breastfeeding
                    FROM nutrition_household_survey s
                    WHERE ' . $where . "
                      AND (UPPER(COALESCE(head_is_pregnant, 'NO')) = 'YES'
                        OR UPPER(COALESCE(head_is_lactating, 'NO')) = 'YES')";
        $heads = $con->query($headSql);
        if ($heads) {
            while ($row = $heads->fetch_assoc()) {
                if (strtoupper((string) ($row['head_is_pregnant'] ?? 'NO')) === 'YES') {
                    $status = nutrition_mellpi_map_pregnant_status((string) ($row['head_pregnant_nutrition_status'] ?? ''));
                    if ($status !== '' && isset($pregnant[$status])) {
                        $pregnant[$status]++;
                    }
                }
                if (strtoupper((string) ($row['head_is_lactating'] ?? 'NO')) === 'YES') {
                    $lactating++;
                    if (strtoupper((string) ($row['head_lactating_exclusive_breastfeeding'] ?? 'NO')) === 'YES') {
                        $exclusiveBf++;
                    }
                }
            }
        }

        return [
            'school' => $school,
            'pregnant' => $pregnant,
            'lactating' => $lactating,
            'exclusive_bf' => $exclusiveBf,
        ];
    }
}

if (!function_exists('nutrition_mellpi_matrix_total')) {
    /**
     * @param array<string, array<string, int>> $matrix
     * @param array<int, string> $statuses
     */
    function nutrition_mellpi_matrix_total(array $matrix, array $statuses): int
    {
        $total = 0;
        foreach ($statuses as $status) {
            $total += (int) ($matrix[$status]['0-59']['T'] ?? 0);
        }

        return $total;
    }
}

if (!function_exists('nutrition_mellpi_build_report')) {
    /**
     * Merge saved registration with live nutrition stats for the report.
     * Pass $barangayId for per-barangay MELLPI PRO registration/report.
     *
     * @return array<string, mixed>
     */
    function nutrition_mellpi_build_report(mysqli $con, ?string $barangayId = null): array
    {
        $barangayId = trim((string) $barangayId);
        $isBarangay = $barangayId !== '';
        $saved = nutrition_mellpi_load_profile($con, $isBarangay ? $barangayId : null);
        $year = (int) date('Y');
        $years = $saved['years'] ?? [($year - 2), ($year - 1), $year];
        if (!is_array($years) || $years === []) {
            $years = [($year - 2), ($year - 1), $year];
        }
        $years = array_map('intval', array_values($years));

        $barangayName = '';
        if ($isBarangay) {
            foreach (barangay_list_all($con) as $brgy) {
                if ((string) ($brgy['id'] ?? '') === $barangayId) {
                    $barangayName = (string) ($brgy['barangay'] ?? '');
                    break;
                }
            }
        }

        $barangayCount = 0;
        if (!$isBarangay) {
            foreach (barangay_list_all($con) as $brgy) {
                $name = (string) ($brgy['barangay'] ?? '');
                if ($name === '' || barangay_is_placeholder_name($name)) {
                    continue;
                }
                $barangayCount++;
            }
        } else {
            $barangayCount = 1;
        }

        $totalPop = 0;
        if (!$isBarangay) {
            $hub = function_exists('barangay_hub_totals') ? barangay_hub_totals($con) : ['population' => 0];
            $totalPop = (int) ($hub['population'] ?? 0);
        }

        if ($isBarangay) {
            $hhReport = nutrition_bnp_build_report($con, $barangayId, 'all_hh', []);
        } else {
            $hhReport = nutrition_bnp_city_build_report($con, 'all_hh', []);
        }
        $hhInd = $hhReport['indicators'] ?? [];
        $hhPrf = $hhReport['prf'] ?? [];
        $totalHouseholds = (int) ($hhInd['actual_households'] ?? 0);
        if ($totalHouseholds < 1) {
            $totalHouseholds = (int) ($hhInd['households_surveyed'] ?? 0);
        }
        if ($totalPop < 1) {
            $totalPop = (int) ($hhInd['actual_population'] ?? 0);
        }

        $safeWater = (int) ($hhPrf['water']['Pipe Water System (level III)'] ?? $hhPrf['water']['Pipe Water System'] ?? 0)
            + (int) ($hhPrf['water']['Communal source piped water system'] ?? $hhPrf['water']['Communal Water Source'] ?? 0)
            + (int) ($hhPrf['water']['Mineral water/water dispensing stores'] ?? $hhPrf['water']['Mineral'] ?? 0);
        $sanitaryToilet = 0;
        foreach ($hhPrf['toilet_sanitary'] ?? [] as $n) {
            $sanitaryToilet += (int) $n;
        }

        $eopt = nutrition_eopt_build_report($con, $isBarangay ? $barangayId : null, []);
        $wfa = $eopt['wfa'] ?? [];
        $hfa = $eopt['hfa'] ?? [];
        $wfl = $eopt['wfl'] ?? [];
        $eoptTotals = $eopt['totals'] ?? [];

        if ($isBarangay) {
            $pregnantReport = nutrition_pregnant_families_report($con, $barangayId, []);
        } else {
            $pregnantReport = nutrition_city_pregnant_families_report($con, []);
        }
        $pregnantActual = (int) ($pregnantReport['family_count'] ?? 0);

        $bnsAccounts = nutrition_bns_accounts_by_barangay($con);
        if ($isBarangay) {
            $bnsTotal = isset($bnsAccounts[$barangayId]) ? 1 : 0;
        } else {
            $bnsTotal = count($bnsAccounts);
        }

        // Prefer live current-year values when registration blanks are empty.
        $fillYear = static function (array &$bucket, string $label, int $y, $liveValue): void {
            if (!isset($bucket[$label]) || !is_array($bucket[$label])) {
                $bucket[$label] = [];
            }
            $current = trim((string) ($bucket[$label][$y] ?? ''));
            if ($current === '') {
                $bucket[$label][$y] = (string) $liveValue;
            }
        };

        $preschool = $saved['preschool'] ?? [];
        if (!isset($preschool['wfa']) || !is_array($preschool['wfa'])) {
            $preschool['wfa'] = [];
        }
        if (!isset($preschool['wfh']) || !is_array($preschool['wfh'])) {
            $preschool['wfh'] = [];
        }
        if (!isset($preschool['hfa']) || !is_array($preschool['hfa'])) {
            $preschool['hfa'] = [];
        }

        $fillYear($preschool['wfa'], 'Normal', $year, (int) ($wfa['Normal']['0-59']['T'] ?? 0));
        $fillYear($preschool['wfa'], 'Underweight', $year, (int) ($wfa['UW']['0-59']['T'] ?? 0));
        $fillYear($preschool['wfa'], 'Severely Underweight', $year, (int) ($wfa['SUW']['0-59']['T'] ?? 0));
        $fillYear($preschool['wfa'], 'Overweight', $year, (int) ($wfa['OW']['0-59']['T'] ?? 0) + (int) ($wfa['OB']['0-59']['T'] ?? 0));

        $fillYear($preschool['wfh'], 'Normal', $year, (int) ($wfl['Normal']['0-59']['T'] ?? 0));
        $fillYear($preschool['wfh'], 'Wasted', $year, (int) ($wfl['MW']['0-59']['T'] ?? 0));
        $fillYear($preschool['wfh'], 'Severely Wasted', $year, (int) ($wfl['SW']['0-59']['T'] ?? 0));
        $fillYear($preschool['wfh'], 'Overweight', $year, (int) ($wfl['OW']['0-59']['T'] ?? 0));
        $fillYear($preschool['wfh'], 'Obese', $year, (int) ($wfl['Ob']['0-59']['T'] ?? 0));

        $fillYear($preschool['hfa'], 'Normal', $year, (int) ($hfa['Normal']['0-59']['T'] ?? 0));
        $fillYear($preschool['hfa'], 'Stunted', $year, (int) ($hfa['St']['0-59']['T'] ?? 0));
        $fillYear($preschool['hfa'], 'Severely Stunted', $year, (int) ($hfa['SSt']['0-59']['T'] ?? 0));
        $fillYear($preschool['hfa'], 'Tall', $year, (int) ($hfa['Tall']['0-59']['T'] ?? 0));

        $community = $saved['community'] ?? [];
        if (trim((string) ($community['hh_safe_water'] ?? '')) === '') {
            $community['hh_safe_water'] = (string) $safeWater;
        }
        if (trim((string) ($community['hh_sanitary_toilets'] ?? '')) === '') {
            $community['hh_sanitary_toilets'] = (string) $sanitaryToilet;
        }
        if (trim((string) ($community['income_classification'] ?? '')) === ''
            && trim((string) ($saved['income_class'] ?? '')) !== ''
        ) {
            $community['income_classification'] = (string) $saved['income_class'];
        }

        $popSnap = $saved['population_snapshot'] ?? [];
        if (trim((string) ($popSnap['0_59_actual'] ?? '')) === '') {
            $popSnap['0_59_actual'] = (string) ((int) ($eoptTotals['measured'] ?? ($hhInd['pop_0_59'] ?? 0)));
        }
        if (trim((string) ($popSnap['pregnant_actual'] ?? '')) === '') {
            $popSnap['pregnant_actual'] = (string) $pregnantActual;
        }
        if (trim((string) ($popSnap['lactating_actual'] ?? '')) === '') {
            $popSnap['lactating_actual'] = (string) ((int) ($hhInd['lactating_women'] ?? 0));
        }

        $encoded = nutrition_mellpi_encoded_member_counts($con, $isBarangay ? $barangayId : '');
        $school = $saved['school'] ?? [];
        if (!is_array($school)) {
            $school = [];
        }
        foreach ($encoded['school'] as $label => $liveValue) {
            $fillYear($school, $label, $year, $liveValue);
        }

        $pregnantStatus = $saved['pregnant_status'] ?? [];
        if (!is_array($pregnantStatus)) {
            $pregnantStatus = [];
        }
        foreach ($encoded['pregnant'] as $label => $liveValue) {
            $fillYear($pregnantStatus, $label, $year, $liveValue);
        }

        $pregnantTotal = array_sum($encoded['pregnant']);
        if (trim((string) ($community['pct_at_risk_pregnant'] ?? '')) === '' && $pregnantTotal > 0) {
            $community['pct_at_risk_pregnant'] = (string) (int) round(
                (($encoded['pregnant']['Nutritionally at-risk'] ?? 0) / $pregnantTotal) * 100
            );
        }
        if (trim((string) ($community['pct_exclusive_bf_5th_month'] ?? '')) === '' && $encoded['lactating'] > 0) {
            $community['pct_exclusive_bf_5th_month'] = (string) (int) round(
                ($encoded['exclusive_bf'] / $encoded['lactating']) * 100
            );
        }

        $bns = $saved['bns'] ?? [];
        if (trim((string) ($bns['total'] ?? '')) === '') {
            $bns['total'] = (string) $bnsTotal;
        }
        if (trim((string) ($bns['existing'] ?? '')) === '' && trim((string) ($bns['new'] ?? '')) === '') {
            $bns['existing'] = (string) $bnsTotal;
            $bns['new'] = '0';
        }

        $displayName = $isBarangay
            ? ($barangayName !== '' ? $barangayName : (string) ($saved['city_name'] ?? 'Barangay'))
            : (string) ($saved['city_name'] ?? 'City of Valencia');

        return [
            'meta' => [
                'form' => 'MELLPI PRO FORM CM',
                'title' => $isBarangay ? 'BARANGAY PROFILE SHEET' : 'CITY/MUNICIPALITY PROFILE SHEET',
                'scope' => $isBarangay ? 'barangay' : 'city',
                'barangay_id' => $isBarangay ? $barangayId : '',
                'barangay_name' => $barangayName,
                'city_name' => $displayName,
                'province' => (string) ($saved['province'] ?? 'Bukidnon'),
                'income_class' => (string) ($saved['income_class'] ?? ''),
                'date_of_monitoring' => (string) ($saved['date_of_monitoring'] ?? date('Y-m-d')),
                'period_covered' => (string) ($saved['period_covered'] ?? ('CY ' . $year)),
                'calendar_year' => $year,
            ],
            'summary' => [
                'total_population' => $totalPop,
                'no_of_households' => $totalHouseholds,
                'no_of_barangays' => $barangayCount,
            ],
            'community' => $community,
            'population_snapshot' => $popSnap,
            'years' => $years,
            'preschool' => $preschool,
            'school' => $school,
            'pregnant_status' => $pregnantStatus,
            'bns' => $bns,
            'hazards' => $saved['hazards'] ?? [],
            'land_use' => $saved['land_use'] ?? [],
            'live' => [
                'measured_0_59' => (int) ($eoptTotals['measured'] ?? 0),
                'undernutrition' => (int) ($eoptTotals['undernutrition'] ?? 0),
                'overweight_obesity' => (int) ($eoptTotals['overweight_obesity'] ?? 0),
            ],
        ];
    }
}
