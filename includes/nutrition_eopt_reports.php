<?php

/**
 * e-OPT Plus Community Level Tool report builders
 * Region 10 ver2 (July 2024): Nut_StatusTool, Form 1A / 1B / 1C,
 * NutStatusBrgy, DQC, Graphs, monitoring lists.
 */

require_once __DIR__ . '/nutrition_context.php';
require_once __DIR__ . '/nutrition_bnp_reports.php';
require_once __DIR__ . '/nutrition_eopt_lms.php';

if (!function_exists('nutrition_eopt_age_bands')) {
    /**
     * @return array<int, string>
     */
    function nutrition_eopt_age_bands(): array
    {
        return ['0-5', '6-11', '12-23', '24-35', '36-47', '48-59'];
    }
}

if (!function_exists('nutrition_eopt_age_band')) {
    function nutrition_eopt_age_band(?int $ageMonths): ?string
    {
        if ($ageMonths === null || $ageMonths < 0 || $ageMonths > 59) {
            return null;
        }
        if ($ageMonths <= 5) {
            return '0-5';
        }
        if ($ageMonths <= 11) {
            return '6-11';
        }
        if ($ageMonths <= 23) {
            return '12-23';
        }
        if ($ageMonths <= 35) {
            return '24-35';
        }
        if ($ageMonths <= 47) {
            return '36-47';
        }

        return '48-59';
    }
}

if (!function_exists('nutrition_eopt_sex_key')) {
    function nutrition_eopt_sex_key(string $gender): string
    {
        $g = strtoupper(trim($gender));
        if ($g === 'M' || $g === 'MALE' || str_starts_with($g, 'M')) {
            return 'M';
        }
        if ($g === 'F' || $g === 'FEMALE' || str_starts_with($g, 'F')) {
            return 'F';
        }

        return '';
    }
}

if (!function_exists('nutrition_eopt_gender_label')) {
    function nutrition_eopt_gender_label(string $sexOrGender): string
    {
        $sex = nutrition_eopt_sex_key($sexOrGender);
        if ($sex === 'M') {
            return 'Male';
        }
        if ($sex === 'F') {
            return 'Female';
        }

        return '';
    }
}

if (!function_exists('nutrition_eopt_normalize_wfa')) {
    function nutrition_eopt_normalize_wfa(string $value): string
    {
        $v = nutrition_bnp_normalize_growth_label($value);
        if ($v === 'suw' || $v === 'severely underweight') {
            return 'SUW';
        }
        if ($v === 'uw' || $v === 'muw' || $v === 'underweight' || $v === 'moderately underweight') {
            return 'UW';
        }
        if ($v === 'ow' || $v === 'overweight') {
            return 'OW';
        }
        if ($v === 'ob' || $v === 'obese' || $v === 'obesity') {
            return 'OB';
        }
        if ($v === 'normal') {
            return 'Normal';
        }

        return '';
    }
}

if (!function_exists('nutrition_eopt_normalize_hfa')) {
    function nutrition_eopt_normalize_hfa(string $value): string
    {
        $v = nutrition_bnp_normalize_growth_label($value);
        if ($v === 'severely stunted' || $v === 'sst') {
            return 'SSt';
        }
        if ($v === 'stunted' || $v === 'st' || $v === 'mst' || $v === 'moderately stunted') {
            return 'St';
        }
        if ($v === 'tall') {
            return 'Tall';
        }
        if ($v === 'normal') {
            return 'Normal';
        }

        return '';
    }
}

if (!function_exists('nutrition_eopt_normalize_wfl')) {
    function nutrition_eopt_normalize_wfl(string $value): string
    {
        $v = nutrition_bnp_normalize_growth_label($value);
        if ($v === 'sev wasted' || $v === 'sw' || $v === 'severely wasted' || $v === 'sam') {
            return 'SW';
        }
        if ($v === 'wasted' || $v === 'mw' || $v === 'mam' || $v === 'moderately wasted') {
            return 'MW';
        }
        if ($v === 'ob' || $v === 'obese') {
            return 'Ob';
        }
        if ($v === 'ow' || $v === 'overweight') {
            return 'OW';
        }
        if ($v === 'normal') {
            return 'Normal';
        }

        return '';
    }
}

if (!function_exists('nutrition_eopt_normalize_muac')) {
    function nutrition_eopt_normalize_muac(string $value): string
    {
        return nutrition_eopt_normalize_wfl($value);
    }
}

if (!function_exists('nutrition_eopt_empty_sex_band_matrix')) {
    /**
     * @param array<int, string> $statuses
     * @return array<string, array<string, array<string, int>>>
     */
    function nutrition_eopt_empty_sex_band_matrix(array $statuses): array
    {
        $matrix = [];
        foreach ($statuses as $status) {
            $matrix[$status] = [];
            foreach (nutrition_eopt_age_bands() as $band) {
                $matrix[$status][$band] = ['M' => 0, 'F' => 0, 'T' => 0];
            }
            $matrix[$status]['0-59'] = ['M' => 0, 'F' => 0, 'T' => 0];
            $matrix[$status]['0-23'] = ['M' => 0, 'F' => 0, 'T' => 0];
            $matrix[$status]['6-59'] = ['M' => 0, 'F' => 0, 'T' => 0];
        }

        return $matrix;
    }
}

if (!function_exists('nutrition_eopt_tally_matrix')) {
    /**
     * @param array<string, array<string, array<string, int>>> $matrix
     */
    function nutrition_eopt_tally_matrix(array &$matrix, string $status, string $band, string $sex): void
    {
        if ($status === '' || $band === '' || ($sex !== 'M' && $sex !== 'F')) {
            return;
        }
        if (!isset($matrix[$status])) {
            return;
        }
        foreach ([$band, '0-59'] as $key) {
            if (!isset($matrix[$status][$key])) {
                continue;
            }
            $matrix[$status][$key][$sex]++;
            $matrix[$status][$key]['T']++;
        }
        if (in_array($band, ['0-5', '6-11', '12-23'], true) && isset($matrix[$status]['0-23'])) {
            $matrix[$status]['0-23'][$sex]++;
            $matrix[$status]['0-23']['T']++;
        }
        if (in_array($band, ['6-11', '12-23', '24-35', '36-47', '48-59'], true) && isset($matrix[$status]['6-59'])) {
            $matrix[$status]['6-59'][$sex]++;
            $matrix[$status]['6-59']['T']++;
        }
    }
}

if (!function_exists('nutrition_eopt_filter_list')) {
    /**
     * @param array<int, array<string, mixed>> $rows
     * @param callable(array<string, mixed>):bool $predicate
     * @return array<int, array<string, mixed>>
     */
    function nutrition_eopt_filter_list(array $rows, callable $predicate): array
    {
        $out = [];
        $n = 0;
        foreach ($rows as $row) {
            if (!$predicate($row)) {
                continue;
            }
            $n++;
            $row['list_seq'] = $n;
            $out[] = $row;
        }

        return $out;
    }
}

if (!function_exists('nutrition_eopt_median')) {
    /**
     * @param array<int, float> $values
     */
    function nutrition_eopt_median(array $values): ?float
    {
        $n = count($values);
        if ($n === 0) {
            return null;
        }
        sort($values, SORT_NUMERIC);
        $mid = intdiv($n, 2);
        if ($n % 2 === 1) {
            return $values[$mid];
        }

        return ($values[$mid - 1] + $values[$mid]) / 2;
    }
}

if (!function_exists('nutrition_eopt_collect_children')) {
    /**
     * Collect preschool (0–59 mo) children with measurements from household surveys.
     *
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    function nutrition_eopt_collect_children(mysqli $con, ?string $barangayId = null, array $filters = []): array
    {
        nutrition_ensure_module_tables($con);

        $children = [];
        $seq = 0;
        $barangays = [];
        if ($barangayId !== null && $barangayId !== '') {
            foreach (barangay_list_all($con) as $brgy) {
                if ((string) ($brgy['id'] ?? '') === $barangayId) {
                    $barangays[] = $brgy;
                    break;
                }
            }
        } else {
            $barangays = barangay_list_all($con);
        }

        foreach ($barangays as $brgy) {
            $id = (string) ($brgy['id'] ?? '');
            $name = (string) ($brgy['barangay'] ?? '');
            if ($id === '' || barangay_is_placeholder_name($name)) {
                continue;
            }
            $households = nutrition_list_household_surveys($con, $id, 0, $filters);
            $membersBySurvey = nutrition_list_barangay_family_members($con, $id);

            foreach ($households as $survey) {
                $surveyId = (string) ($survey['survey_id'] ?? '');
                $members = $membersBySurvey[$surveyId] ?? [];
                $caregiver = nutrition_household_head_display($survey);
                $purok = trim((string) ($survey['purok'] ?? ''));
                $address = $purok !== '' ? ('Purok ' . $purok) : trim((string) ($survey['address'] ?? ''));
                $isIpHh = strtoupper((string) ($survey['is_ip'] ?? 'NO')) === 'YES';
                $surveyDateMeasured = (string) ($survey['survey_date'] ?? ($survey['date_updated'] ?? ''));

                foreach ($members as $member) {
                    $ageMonths = isset($member['age_months']) && $member['age_months'] !== null && $member['age_months'] !== ''
                        ? (int) $member['age_months']
                        : null;
                    $band = nutrition_eopt_age_band($ageMonths);
                    if ($band === null) {
                        continue;
                    }

                    $memberDateMeasured = trim((string) ($member['date_measured'] ?? ''));
                    if ($memberDateMeasured === '') {
                        $memberDateMeasured = $surveyDateMeasured;
                    }

                    $wfa = nutrition_eopt_normalize_wfa((string) ($member['weight_for_age'] ?? ''));
                    $hfa = nutrition_eopt_normalize_hfa((string) ($member['height_for_age'] ?? ''));
                    $wfl = nutrition_eopt_normalize_wfl((string) ($member['weight_for_height'] ?? ''));
                    $weight = $member['weight_kg'] ?? null;
                    $height = $member['height_cm'] ?? null;
                    $muacRaw = $member['muac_cm'] ?? null;
                    $muacCm = ($muacRaw !== null && $muacRaw !== '' && (float) $muacRaw > 0) ? (float) $muacRaw : null;
                    $muac = nutrition_eopt_normalize_muac((string) ($member['muac_status'] ?? ''));
                    if ($muac === '' && $muacCm !== null) {
                        $muac = nutrition_eopt_classify_muac(
                            nutrition_eopt_gender_label((string) ($member['gender'] ?? '')),
                            (string) ($member['birth_date'] ?? '') ?: null,
                            $muacCm,
                            $memberDateMeasured !== '' ? $memberDateMeasured : null
                        );
                    }

                    $measured = ($weight !== null && $weight !== '' && (float) $weight > 0)
                        || ($height !== null && $height !== '' && (float) $height > 0)
                        || $muacCm !== null
                        || $wfa !== '' || $hfa !== '' || $wfl !== '' || $muac !== '';

                    if (!$measured) {
                        continue;
                    }

                    $seq++;
                    $sex = nutrition_eopt_sex_key((string) ($member['gender'] ?? ''));
                    $childName = trim((string) ($member['member_name'] ?? ''));
                    if ($childName === '') {
                        $childName = nutrition_format_member_display_name(
                            (string) ($member['first_name'] ?? ''),
                            (string) ($member['middle_name'] ?? ''),
                            (string) ($member['last_name'] ?? ''),
                            (string) ($member['ext_name'] ?? '')
                        );
                    }

                    $edema = strtoupper(trim((string) ($member['edema'] ?? '')));
                    if ($edema === '') {
                        $edema = 'NONE';
                    }
                    $disability = strtoupper((string) ($member['disability'] ?? 'NO')) === 'YES' ? 'YES' : 'NO';

                    $children[] = [
                        'seq' => $seq,
                        'barangay_id' => $id,
                        'barangay' => $name,
                        'purok' => $purok,
                        'address' => $address !== '' ? $address : $name,
                        'caregiver' => $caregiver,
                        'child_name' => $childName !== '' ? $childName : '—',
                        'ip' => $isIpHh || strtoupper((string) ($member['is_ip'] ?? 'NO')) === 'YES' ? 'YES' : 'NO',
                        'sex' => $sex,
                        'birth_date' => (string) ($member['birth_date'] ?? ''),
                        'date_measured' => $memberDateMeasured,
                        'weight_kg' => $weight !== null && $weight !== '' ? (float) $weight : null,
                        'height_cm' => $height !== null && $height !== '' ? (float) $height : null,
                        'muac_cm' => $muacCm,
                        'age_months' => $ageMonths,
                        'age_band' => $band,
                        'wfa' => $wfa,
                        'hfa' => $hfa,
                        'wfl' => $wfl,
                        'muac' => $muac,
                        'edema' => $edema,
                        'disability' => $disability,
                    ];
                }
            }
        }

        return $children;
    }
}

if (!function_exists('nutrition_eopt_build_dqc')) {
    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<string, mixed>
     */
    function nutrition_eopt_build_dqc(array $children): array
    {
        $weights = [];
        $heights = [];
        $ages = [];
        $missing = [
            'no_weight' => 0,
            'no_height' => 0,
            'weight_no_height' => 0,
            'height_no_weight' => 0,
            'no_sex' => 0,
            'no_birth_date' => 0,
            'no_muac' => 0,
            'no_edema' => 0,
            'no_caregiver_or_address' => 0,
            'over_59' => 0,
        ];
        $dupKeys = [];
        $duplicates = 0;

        foreach ($children as $child) {
            $w = $child['weight_kg'] ?? null;
            $h = $child['height_cm'] ?? null;
            $age = $child['age_months'] ?? null;
            if ($w !== null) {
                $weights[] = (float) $w;
            } else {
                $missing['no_weight']++;
            }
            if ($h !== null) {
                $heights[] = (float) $h;
            } else {
                $missing['no_height']++;
            }
            if ($w !== null && $h === null) {
                $missing['weight_no_height']++;
            }
            if ($h !== null && $w === null) {
                $missing['height_no_weight']++;
            }
            if ($age !== null) {
                $ages[] = (float) $age;
            }
            if (($child['sex'] ?? '') === '') {
                $missing['no_sex']++;
            }
            if (trim((string) ($child['birth_date'] ?? '')) === '') {
                $missing['no_birth_date']++;
            }
            if (($child['muac_cm'] ?? null) === null) {
                $missing['no_muac']++;
            }
            if (trim((string) ($child['edema'] ?? '')) === '' || strtoupper((string) $child['edema']) === 'NONE' && false) {
                // Edema defaults to NONE when blank; count only truly empty after collect.
            }
            if (trim((string) ($child['caregiver'] ?? '')) === '' || trim((string) ($child['address'] ?? '')) === '') {
                $missing['no_caregiver_or_address']++;
            }

            $key = strtolower(trim((string) ($child['child_name'] ?? ''))) . '|' . trim((string) ($child['birth_date'] ?? ''));
            if ($key !== '|' && isset($dupKeys[$key])) {
                $duplicates++;
            } else {
                $dupKeys[$key] = true;
            }
        }

        $stat = static function (array $values): array {
            $n = count($values);
            if ($n === 0) {
                return [
                    'n' => 0,
                    'mean' => null,
                    'median' => null,
                    'min' => null,
                    'max' => null,
                    'sd' => null,
                ];
            }
            $sum = array_sum($values);
            $mean = $sum / $n;
            $var = 0.0;
            foreach ($values as $v) {
                $var += ($v - $mean) ** 2;
            }
            $sd = $n > 1 ? sqrt($var / ($n - 1)) : 0.0;

            return [
                'n' => $n,
                'mean' => round($mean, 2),
                'median' => round((float) nutrition_eopt_median($values), 2),
                'min' => round(min($values), 2),
                'max' => round(max($values), 2),
                'sd' => round($sd, 2),
            ];
        };

        $n = max(count($children), 1);

        return [
            'weight' => $stat($weights),
            'height' => $stat($heights),
            'age' => $stat($ages),
            'missing' => $missing,
            'duplicates' => $duplicates,
            'pct_missing_muac' => round(($missing['no_muac'] / $n) * 100, 1),
            'pct_duplicates' => round(($duplicates / $n) * 100, 1),
        ];
    }
}

if (!function_exists('nutrition_eopt_build_prevalence')) {
    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<string, array<string, float|int>>
     */
    function nutrition_eopt_build_prevalence(array $children): array
    {
        $den = count($children);
        $count = static function (callable $fn) use ($children): int {
            $n = 0;
            foreach ($children as $row) {
                if ($fn($row)) {
                    $n++;
                }
            }

            return $n;
        };
        $row = static function (int $num) use ($den): array {
            return [
                'count' => $num,
                'denominator' => $den,
                'prevalence' => $den > 0 ? round(($num / $den) * 100, 2) : 0.0,
            ];
        };

        $mw = $count(static fn ($c) => ($c['wfl'] ?? '') === 'MW');
        $sw = $count(static fn ($c) => ($c['wfl'] ?? '') === 'SW');
        $st = $count(static fn ($c) => ($c['hfa'] ?? '') === 'St');
        $sst = $count(static fn ($c) => ($c['hfa'] ?? '') === 'SSt');
        $uw = $count(static fn ($c) => ($c['wfa'] ?? '') === 'UW');
        $suw = $count(static fn ($c) => ($c['wfa'] ?? '') === 'SUW');
        $ow = $count(static fn ($c) => in_array(($c['wfl'] ?? ''), ['OW', 'Ob'], true) || in_array(($c['wfa'] ?? ''), ['OW', 'OB'], true));

        return [
            'mw' => $row($mw),
            'sw' => $row($sw),
            'wasted' => $row($mw + $sw),
            'st' => $row($st),
            'sst' => $row($sst),
            'stunted' => $row($st + $sst),
            'uw' => $row($uw),
            'suw' => $row($suw),
            'underweight' => $row($uw + $suw),
            'ow_ob' => $row($ow),
        ];
    }
}

if (!function_exists('nutrition_eopt_build_report')) {
    /**
     * Build e-OPT Plus printable report pack from household nutrition data.
     *
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_eopt_build_report(mysqli $con, ?string $barangayId = null, array $filters = []): array
    {
        $children = nutrition_eopt_collect_children($con, $barangayId, $filters);
        $isCityWide = $barangayId === null || $barangayId === '';
        $barangayName = 'All Barangays';
        if (!$isCityWide) {
            foreach (barangay_list_all($con) as $brgy) {
                if ((string) ($brgy['id'] ?? '') === $barangayId) {
                    $barangayName = (string) ($brgy['barangay'] ?? 'Barangay');
                    break;
                }
            }
        }

        $wfaMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'OW', 'UW', 'SUW', 'OB']);
        $hfaMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'Tall', 'St', 'SSt']);
        $wflMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'OW', 'Ob', 'MW', 'SW']);
        $muacMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'MW', 'SW']);
        $wfaIpMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'OW', 'UW', 'SUW', 'OB']);
        $hfaIpMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'Tall', 'St', 'SSt']);
        $wflIpMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'OW', 'Ob', 'MW', 'SW']);
        $muacIpMatrix = nutrition_eopt_empty_sex_band_matrix(['Normal', 'MW', 'SW']);

        $ipCount = 0;
        $undernutrition = 0;
        $overweightObesity = 0;
        $edemaCount = 0;
        $disabilityCount = 0;
        $boys = 0;
        $girls = 0;
        $muacMeasured = 0;
        $wfaClassified = 0;
        $hfaClassified = 0;
        $wflClassified = 0;
        $measuredByBand = [
            '0-5' => 0,
            '6-11' => 0,
            '12-23' => 0,
            '24-35' => 0,
            '36-47' => 0,
            '48-59' => 0,
            '0-23' => 0,
            '0-59' => 0,
        ];

        foreach ($children as $child) {
            $sex = (string) ($child['sex'] ?? '');
            $band = (string) ($child['age_band'] ?? '');
            $isIp = (($child['ip'] ?? '') === 'YES');
            $wfa = (string) ($child['wfa'] ?? '');
            $hfa = (string) ($child['hfa'] ?? '');
            $wfl = (string) ($child['wfl'] ?? '');
            $muacStatus = (string) ($child['muac'] ?? '');

            nutrition_eopt_tally_matrix($wfaMatrix, $wfa, $band, $sex);
            nutrition_eopt_tally_matrix($hfaMatrix, $hfa, $band, $sex);
            nutrition_eopt_tally_matrix($wflMatrix, $wfl, $band, $sex);
            // MUAC is not applied to 0–5 months (blanked on OPT Form 1B).
            if ($muacStatus !== '' && $band !== '0-5') {
                nutrition_eopt_tally_matrix($muacMatrix, $muacStatus, $band, $sex);
                $muacMeasured++;
            }

            if ($isIp) {
                nutrition_eopt_tally_matrix($wfaIpMatrix, $wfa, $band, $sex);
                nutrition_eopt_tally_matrix($hfaIpMatrix, $hfa, $band, $sex);
                nutrition_eopt_tally_matrix($wflIpMatrix, $wfl, $band, $sex);
                if ($muacStatus !== '' && $band !== '0-5') {
                    nutrition_eopt_tally_matrix($muacIpMatrix, $muacStatus, $band, $sex);
                }
            }

            if ($wfa !== '') {
                $wfaClassified++;
            }
            if ($hfa !== '') {
                $hfaClassified++;
            }
            if ($wfl !== '') {
                $wflClassified++;
            }

            if (isset($measuredByBand[$band])) {
                $measuredByBand[$band]++;
            }
            $measuredByBand['0-59']++;
            if (in_array($band, ['0-5', '6-11', '12-23'], true)) {
                $measuredByBand['0-23']++;
            }

            if ($sex === 'M') {
                $boys++;
            } elseif ($sex === 'F') {
                $girls++;
            }

            if ($isIp) {
                $ipCount++;
            }
            if (strtoupper((string) ($child['edema'] ?? 'NONE')) !== 'NONE' && trim((string) ($child['edema'] ?? '')) !== '') {
                $edemaCount++;
            }
            if (($child['disability'] ?? '') === 'YES') {
                $disabilityCount++;
            }

            if (in_array($wfa, ['UW', 'SUW'], true)
                || in_array($hfa, ['St', 'SSt'], true)
                || in_array($wfl, ['MW', 'SW'], true)
            ) {
                $undernutrition++;
            }
            if (in_array($wfa, ['OW', 'OB'], true) || in_array($wfl, ['OW', 'Ob'], true)) {
                $overweightObesity++;
            }
        }

        $fieldList = static function (array $rows, string $field, array $values): array {
            return nutrition_eopt_filter_list($rows, static function (array $row) use ($field, $values): bool {
                return in_array((string) ($row[$field] ?? ''), $values, true);
            });
        };

        $atRisk = nutrition_eopt_filter_list($children, static function (array $child): bool {
            $wfa = (string) ($child['wfa'] ?? '');
            $hfa = (string) ($child['hfa'] ?? '');
            $wfl = (string) ($child['wfl'] ?? '');
            $muac = (string) ($child['muac'] ?? '');

            return in_array($wfa, ['UW', 'SUW', 'OW', 'OB'], true)
                || in_array($hfa, ['St', 'SSt'], true)
                || in_array($wfl, ['MW', 'SW', 'OW', 'Ob'], true)
                || in_array($muac, ['MW', 'SW'], true);
        });

        $listAge023 = nutrition_eopt_filter_list($children, static fn (array $c): bool => (int) ($c['age_months'] ?? 99) <= 23);
        $listMw = $fieldList($children, 'wfl', ['MW']);
        $listSw = $fieldList($children, 'wfl', ['SW']);
        $listMstSst = $fieldList($children, 'hfa', ['St', 'SSt']);
        $listOwOb = nutrition_eopt_filter_list($children, static function (array $c): bool {
            return in_array((string) ($c['wfl'] ?? ''), ['OW', 'Ob'], true)
                || in_array((string) ($c['wfa'] ?? ''), ['OW', 'OB'], true);
        });
        $listMuwSuwMstSst = nutrition_eopt_filter_list($children, static function (array $c): bool {
            return in_array((string) ($c['wfa'] ?? ''), ['UW', 'SUW'], true)
                && in_array((string) ($c['hfa'] ?? ''), ['St', 'SSt'], true);
        });
        $listMstSstMwSw = nutrition_eopt_filter_list($children, static function (array $c): bool {
            return in_array((string) ($c['hfa'] ?? ''), ['St', 'SSt'], true)
                && in_array((string) ($c['wfl'] ?? ''), ['MW', 'SW'], true);
        });
        $listMstSstOwOb = nutrition_eopt_filter_list($children, static function (array $c): bool {
            return in_array((string) ($c['hfa'] ?? ''), ['St', 'SSt'], true)
                && (in_array((string) ($c['wfl'] ?? ''), ['OW', 'Ob'], true)
                    || in_array((string) ($c['wfa'] ?? ''), ['OW', 'OB'], true));
        });
        $listMuac = nutrition_eopt_filter_list($children, static fn (array $c): bool => ($c['muac_cm'] ?? null) !== null || ($c['muac'] ?? '') !== '');

        $header = nutrition_city_certificate_header();

        return [
            'meta' => [
                'tool' => 'e-OPT Plus Community Level Tool',
                'version' => 'Region 10 · ver2 · July 2024',
                'is_city_wide' => $isCityWide,
                'barangay' => $barangayName,
                'municipality' => (string) ($header['city'] ?? 'City of Valencia'),
                'province' => (string) ($header['province'] ?? 'Bukidnon'),
                'region' => 'Region X (Northern Mindanao)',
                'calendar_year' => (int) date('Y'),
                'generated_at' => date('Y-m-d H:i:s'),
                'printed_date' => date('M d, Y'),
            ],
            'children' => $children,
            'totals' => [
                'measured' => count($children),
                'boys' => $boys,
                'girls' => $girls,
                'ip' => $ipCount,
                'undernutrition' => $undernutrition,
                'overweight_obesity' => $overweightObesity,
                'at_risk' => count($atRisk),
                'uw' => count($fieldList($children, 'wfa', ['UW'])),
                'suw' => count($fieldList($children, 'wfa', ['SUW'])),
                'st' => count($fieldList($children, 'hfa', ['St'])),
                'sst' => count($fieldList($children, 'hfa', ['SSt'])),
                'mw' => count($listMw),
                'sw' => count($listSw),
                'ow' => count($fieldList($children, 'wfl', ['OW'])) + count($fieldList($children, 'wfa', ['OW'])),
                'ob' => count($fieldList($children, 'wfl', ['Ob'])) + count($fieldList($children, 'wfa', ['OB'])),
                'muac_measured' => $muacMeasured,
                'wfa_classified' => $wfaClassified,
                'hfa_classified' => $hfaClassified,
                'wfl_classified' => $wflClassified,
                'edema' => $edemaCount,
                'disability' => $disabilityCount,
                'age_0_23' => count($listAge023),
                'measured_by_band' => $measuredByBand,
            ],
            'wfa' => $wfaMatrix,
            'hfa' => $hfaMatrix,
            'wfl' => $wflMatrix,
            'muac' => $muacMatrix,
            'wfa_ip' => $wfaIpMatrix,
            'hfa_ip' => $hfaIpMatrix,
            'wfl_ip' => $wflIpMatrix,
            'muac_ip' => $muacIpMatrix,
            'dqc' => nutrition_eopt_build_dqc($children),
            'prevalence' => nutrition_eopt_build_prevalence($children),
            'lists' => [
                'uw' => $fieldList($children, 'wfa', ['UW']),
                'suw' => $fieldList($children, 'wfa', ['SUW']),
                'st' => $fieldList($children, 'hfa', ['St']),
                'sst' => $fieldList($children, 'hfa', ['SSt']),
                'mw' => $listMw,
                'sw' => $listSw,
                'at_risk' => $atRisk,
                'age_0_23' => $listAge023,
                'mst_sst' => $listMstSst,
                'ow_ob' => $listOwOb,
                'muw_suw_mst_sst' => $listMuwSuwMstSst,
                'mst_sst_mw_sw' => $listMstSstMwSw,
                'mst_sst_ow_ob' => $listMstSstOwOb,
                'muac' => $listMuac,
            ],
        ];
    }
}
