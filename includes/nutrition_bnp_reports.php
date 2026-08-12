<?php

/**
 * Barangay Nutrition Profile (BNP) Template 2026 report builders.
 * Forms C1–C9 from 4. BNP-Template-2026.xlsx
 */

require_once __DIR__ . '/nutrition_context.php';

if (!function_exists('nutrition_bnp_normalize_mode')) {
    /**
     * BNP form header mode: Individual (per household) or Consolidated (barangay totals).
     */
    function nutrition_bnp_normalize_mode(?string $mode): string
    {
        return strtolower(trim((string) $mode)) === 'individual' ? 'individual' : 'consolidated';
    }
}

if (!function_exists('nutrition_bnp_report_types')) {
    /**
     * @return array<string, array<string, mixed>>
     */
    function nutrition_bnp_report_types(): array
    {
        return [
            'all_hh' => [
                'key' => 'all_hh',
                'form' => 'C1',
                'code' => 'CNHPDD012',
                'sheet' => 'ALL HH',
                'title' => 'ALL HOUSEHOLDS',
                'layout' => 'all_hh',
            ],
            'uw_suw_ps' => [
                'key' => 'uw_suw_ps',
                'form' => 'C2',
                'code' => 'CNHPDD013',
                'sheet' => 'FAM WITH UW & SUW PS',
                'title' => 'FAMILIES WITH MOD UNDERWEIGHT/SEVERELY UNDERWEIGHT PRESCHOOL CHILDREN',
                'layout' => 'family_abc',
                'filter' => 'growth',
                'growth_field' => 'weight_for_age',
                'moderate' => ['UW', 'uw'],
                'severe' => ['SUW', 'suw'],
                'labels' => [
                    'A' => 'A) Moderately Underweight (MUW)',
                    'B' => 'B) Severely Underweight (Sev)',
                    'C' => 'C) Mod Underweight (MUW) & Severely Underweight (SUW)',
                ],
                'age' => 'preschool',
            ],
            'st_sst_ps' => [
                'key' => 'st_sst_ps',
                'form' => 'C3',
                'code' => 'CNHPDD014',
                'sheet' => 'FAM WITH St & SSt PS',
                'title' => 'FAMILIES WITH STUNTED & SEVERELY STUNTED PRESCHOOL CHILDREN',
                'layout' => 'family_abc',
                'filter' => 'growth',
                'growth_field' => 'height_for_age',
                'moderate' => ['Stunted', 'stunted'],
                'severe' => ['Severely Stunted', 'severely stunted'],
                'labels' => [
                    'A' => 'A) Stunted (S)',
                    'B' => 'B) Severely Stunted (SS)',
                    'C' => 'C) Stunted (S) & Severely Stunted (SS)',
                ],
                'age' => 'preschool',
            ],
            'w_sw_ps' => [
                'key' => 'w_sw_ps',
                'form' => 'C4',
                'code' => 'CNHPDD015',
                'sheet' => 'FAM WITH W & SW PS',
                'title' => 'FAMILIES WITH MODERATELY WASTED (MW) & SEVERELY WASTED (SW) PRESCHOOL CHILDREN',
                'layout' => 'family_abc',
                'filter' => 'growth',
                'growth_field' => 'weight_for_height',
                'moderate' => ['Wasted', 'wasted'],
                'severe' => ['Sev Wasted', 'sev wasted'],
                'labels' => [
                    'A' => 'A) Moderately Wasted (MW)',
                    'B' => 'B) Severely Wasted (SW)',
                    'C' => 'C) Wasted (W) & Severely Wasted (SW)',
                ],
                'age' => 'preschool',
            ],
            'ow_ob_ps' => [
                'key' => 'ow_ob_ps',
                'form' => 'C5',
                'code' => 'CNHPDD016',
                'sheet' => 'FAM WITH OW & OB PS',
                'title' => 'FAMILIES WITH OVERWEIGHT (OW) & OBESE (OB) 0-59 MONTHS CHILDREN — Weight for Length',
                'layout' => 'family_abc',
                'filter' => 'growth',
                'growth_field' => 'weight_for_height',
                'moderate' => ['OW', 'ow'],
                'severe' => ['OB', 'ob'],
                'labels' => [
                    'A' => 'A) Overweight (OW)',
                    'B' => 'B) Obese (Ob)',
                    'C' => 'C) Overweight (OW) & Obese (Ob)',
                ],
                'age' => 'preschool',
            ],
            'lactating' => [
                'key' => 'lactating',
                'form' => 'C6',
                'code' => 'CNHPDD017',
                'sheet' => 'FAM WITH LACTATING',
                'title' => 'FAMILIES WITH LACTATING',
                'layout' => 'family_single',
                'filter' => 'lactating',
                'labels' => [
                    'A' => 'A) Lactating (L) Women',
                ],
            ],
            'pregnant' => [
                'key' => 'pregnant',
                'form' => 'C7',
                'code' => 'CNHPDD018',
                'sheet' => 'FAM WITH PREGNANT',
                'title' => 'FAMILIES WITH PREGNANT',
                'layout' => 'pregnant',
                'filter' => 'pregnant',
            ],
            'w_sw_sc' => [
                'key' => 'w_sw_sc',
                'form' => 'C8',
                'code' => 'CNHPDD019',
                'sheet' => 'FAM WITH W & SW SC',
                'title' => 'FAMILIES WITH WASTED (W) & SEVERELY WASTED (SW) SCHOOL CHILDREN',
                'layout' => 'family_abc',
                'filter' => 'growth',
                'growth_field' => 'weight_for_height',
                'moderate' => ['Wasted', 'wasted'],
                'severe' => ['Sev Wasted', 'sev wasted'],
                'labels' => [
                    'A' => 'A) Wasted (W)',
                    'B' => 'B) Severely Wasted (SW)',
                    'C' => 'C) Wasted (W) & Severely Wasted (SW)',
                ],
                'age' => 'school',
            ],
            'ow_ob_sc' => [
                'key' => 'ow_ob_sc',
                'form' => 'C9',
                'code' => 'CNHPDD020',
                'sheet' => 'FAM WITH OW & OB SC',
                'title' => 'FAMILIES WITH OVERWEIGHT (OW) & OBESE (OB) SCHOOL CHILDREN',
                'layout' => 'family_abc',
                'filter' => 'growth',
                'growth_field' => 'weight_for_height',
                'moderate' => ['OW', 'ow'],
                'severe' => ['OB', 'ob'],
                'labels' => [
                    'A' => 'A) Overweight (OW)',
                    'B' => 'B) Obese (Ob)',
                    'C' => 'C) Overweight (OW) & Obese (Ob)',
                ],
                'age' => 'school',
            ],
        ];
    }
}

if (!function_exists('nutrition_bnp_resolve_type')) {
    /**
     * @return array<string, mixed>|null
     */
    function nutrition_bnp_resolve_type(string $key): ?array
    {
        $types = nutrition_bnp_report_types();

        return $types[$key] ?? null;
    }
}

if (!function_exists('nutrition_bnp_normalize_growth_label')) {
    function nutrition_bnp_normalize_growth_label(string $value): string
    {
        return strtolower(trim($value));
    }
}

if (!function_exists('nutrition_bnp_member_in_age_band')) {
    function nutrition_bnp_member_in_age_band(?int $ageMonths, string $band): bool
    {
        if ($ageMonths === null || $ageMonths < 0) {
            return false;
        }
        if ($band === 'preschool') {
            return $ageMonths <= 59;
        }
        if ($band === 'school') {
            return $ageMonths >= 60 && $ageMonths <= 216;
        }

        return true;
    }
}

if (!function_exists('nutrition_bnp_empty_prf_counts')) {
    /**
     * @return array<string, mixed>
     */
    function nutrition_bnp_empty_prf_counts(): array
    {
        return [
            'house' => ['Owned' => 0, 'Rented' => 0, 'Others' => 0],
            'dwelling' => [
                'Concrete' => 0,
                'Semi-concrete' => 0,
                'Wood' => 0,
                'Makeshift/Barong-barong' => 0,
            ],
            'garbage' => [
                'Collected' => 0,
                'Burning_seg' => 0,
                'Burning_unseg' => 0,
                'Dumping_seg' => 0,
                'Dumping_unseg' => 0,
                'Composting_seg' => 0,
                'Composting_unseg' => 0,
                // Legacy keys kept for older callers
                'Burning' => 0,
                'Dumping' => 0,
                'Composting' => 0,
            ],
            'toilet_sanitary' => [
                'Pour/Flush type with septic tank' => 0,
                'Pour Flush Toilet connected to septic tank and sewerage system' => 0,
                'Ventilated Pit (VIP) Latrine' => 0,
            ],
            'toilet_unsanitary' => [
                'Water-sealed toilet w/o septic tank' => 0,
                'Overhung Latrine' => 0,
                'Open Pit Latrine' => 0,
                'Without toilet' => 0,
            ],
            'water' => [
                'Pipe Water System' => 0,
                'Communal Water Source' => 0,
                'Mineral' => 0,
                'Well' => 0,
                'Spring' => 0,
            ],
            'food' => [
                'Vegetable Garden' => 0,
                'Livestock and/or Poultry' => 0,
                'Fish Pond' => 0,
                'Others' => 0,
            ],
            'family_size' => array_fill(1, 15, 0),
            'most_common_occupation' => '',
        ];
    }
}

if (!function_exists('nutrition_bnp_map_toilet')) {
    /**
     * Map stored PRF toilet values into BNP sanitary/unsanitary buckets.
     *
     * @return array{group:string,key:string}|null
     */
    function nutrition_bnp_map_toilet(string $toiletType): ?array
    {
        $toiletType = trim($toiletType);
        $map = [
            'Water Sealed' => ['group' => 'toilet_sanitary', 'key' => 'Pour/Flush type with septic tank'],
            'Covered Pit' => ['group' => 'toilet_sanitary', 'key' => 'Ventilated Pit (VIP) Latrine'],
            'Open Pit' => ['group' => 'toilet_unsanitary', 'key' => 'Open Pit Latrine'],
            'No Toilet' => ['group' => 'toilet_unsanitary', 'key' => 'Without toilet'],
        ];

        return $map[$toiletType] ?? null;
    }
}

if (!function_exists('nutrition_bnp_tally_prf')) {
    /**
     * @param array<string, mixed> $survey
     * @param array<string, mixed> $counts
     * @param array<int, array<string, mixed>> $members
     */
    function nutrition_bnp_tally_prf(array $survey, array &$counts, array $members = []): void
    {
        $ownership = trim((string) ($survey['house_ownership'] ?? ''));
        if ($ownership === '' || !isset($counts['house'][$ownership])) {
            $ownership = 'Others';
        }
        $counts['house'][$ownership]++;

        $dwelling = trim((string) ($survey['dwelling_type'] ?? ''));
        if ($dwelling !== '' && isset($counts['dwelling'][$dwelling])) {
            $counts['dwelling'][$dwelling]++;
        }

        $garbage = trim((string) ($survey['garbage_disposal'] ?? ''));
        if (strcasecmp($garbage, 'Collected') === 0) {
            $counts['garbage']['Collected']++;
        } elseif (strcasecmp($garbage, 'Uncollected') === 0) {
            $u = strtolower((string) ($survey['garbage_uncollected_type'] ?? ''));
            $isSeg = str_contains($u, 'segregat') && !str_contains($u, 'unsegregat');
            $isUnseg = str_contains($u, 'unsegregat');
            if (str_contains($u, 'burn')) {
                $counts['garbage']['Burning']++;
                if ($isUnseg) {
                    $counts['garbage']['Burning_unseg']++;
                } else {
                    $counts['garbage']['Burning_seg']++;
                }
            } elseif (str_contains($u, 'compost')) {
                $counts['garbage']['Composting']++;
                if ($isUnseg) {
                    $counts['garbage']['Composting_unseg']++;
                } else {
                    $counts['garbage']['Composting_seg']++;
                }
            } else {
                $counts['garbage']['Dumping']++;
                if ($isUnseg) {
                    $counts['garbage']['Dumping_unseg']++;
                } elseif ($isSeg || $u !== '') {
                    $counts['garbage']['Dumping_seg']++;
                }
            }
        }

        $toiletMap = nutrition_bnp_map_toilet((string) ($survey['toilet_type'] ?? ''));
        if ($toiletMap !== null) {
            $counts[$toiletMap['group']][$toiletMap['key']]++;
        }

        $water = trim((string) ($survey['water_source'] ?? ''));
        if ($water !== '' && isset($counts['water'][$water])) {
            $counts['water'][$water]++;
        }

        foreach (nutrition_prf_parse_food_production((string) ($survey['food_production'] ?? '')) as $activity) {
            if ($activity === 'Vege Garden') {
                $counts['food']['Vegetable Garden']++;
            } elseif ($activity === 'Livestock' || $activity === 'Poultry') {
                $counts['food']['Livestock and/or Poultry']++;
            } elseif ($activity === 'Fishpond') {
                $counts['food']['Fish Pond']++;
            } elseif ($activity === 'Fruit Trees') {
                $counts['food']['Others']++;
            }
        }

        $size = (int) ($survey['members_count'] ?? 0);
        if ($size < 1) {
            $size = count($members) + 1;
        }
        $size = max(1, min(15, $size));
        $counts['family_size'][$size]++;
    }
}

if (!function_exists('nutrition_bnp_occupation_summary')) {
    /**
     * @param array<string, array{label:string,count:int}> $occupationCounts
     */
    function nutrition_bnp_occupation_summary(array $occupationCounts): string
    {
        usort($occupationCounts, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $top = array_slice(array_values($occupationCounts), 0, 5);
        if ($top === []) {
            return '';
        }
        $parts = [];
        foreach ($top as $row) {
            $parts[] = $row['label'] . ' (' . $row['count'] . ')';
        }

        return implode(', ', $parts);
    }
}

if (!function_exists('nutrition_bnp_classify_growth_household')) {
    /**
     * @param array<int, array<string, mixed>> $members
     * @param array<int, string> $moderate
     * @param array<int, string> $severe
     * @return string ''|A|B|C
     */
    function nutrition_bnp_classify_growth_household(
        array $members,
        string $growthField,
        array $moderate,
        array $severe,
        string $ageBand
    ): string {
        $mod = false;
        $sev = false;
        $modSet = array_map('nutrition_bnp_normalize_growth_label', $moderate);
        $sevSet = array_map('nutrition_bnp_normalize_growth_label', $severe);

        foreach ($members as $member) {
            $ageMonths = isset($member['age_months']) ? (int) $member['age_months'] : null;
            if ($ageMonths === null) {
                $ageMonths = nutrition_age_in_months(
                    trim((string) ($member['birth_date'] ?? '')) !== '' ? (string) $member['birth_date'] : null
                );
            }
            if (!nutrition_bnp_member_in_age_band($ageMonths, $ageBand)) {
                continue;
            }
            $result = nutrition_bnp_normalize_growth_label((string) ($member[$growthField] ?? ''));
            if ($result === '') {
                continue;
            }
            if (in_array($result, $modSet, true)) {
                $mod = true;
            }
            if (in_array($result, $sevSet, true)) {
                $sev = true;
            }
        }

        if ($mod && $sev) {
            return 'C';
        }
        if ($sev) {
            return 'B';
        }
        if ($mod) {
            return 'A';
        }

        return '';
    }
}

if (!function_exists('nutrition_bnp_family_profile_report')) {
    /**
     * Generic BNP family-profile report (C2–C6, C8–C9). Pregnant uses dedicated builder.
     *
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_bnp_family_profile_report(mysqli $con, string $barangayId, string $typeKey, array $filters = []): array
    {
        $meta = nutrition_bnp_resolve_type($typeKey);
        if ($meta === null || ($meta['layout'] ?? '') === 'pregnant' || ($meta['layout'] ?? '') === 'all_hh') {
            return [];
        }

        if ($typeKey === 'pregnant') {
            return nutrition_pregnant_families_report($con, $barangayId, $filters);
        }

        $households = nutrition_list_household_surveys($con, $barangayId, 0, $filters);
        $membersBySurvey = nutrition_list_barangay_family_members($con, $barangayId);
        $counts = nutrition_bnp_empty_prf_counts();
        $categoryTotals = [];
        foreach (array_keys($meta['labels'] ?? ['A' => 'A']) as $col) {
            $categoryTotals[$col] = 0;
        }
        $familyCount = 0;
        $occupationCounts = [];
        $individuals = [];

        foreach ($households as $survey) {
            $surveyId = (string) ($survey['survey_id'] ?? '');
            $members = $membersBySurvey[$surveyId] ?? [];
            $category = '';

            if (($meta['filter'] ?? '') === 'lactating') {
                $has = strtoupper((string) ($survey['has_lactating'] ?? 'NO')) === 'YES'
                    || strtoupper((string) ($survey['head_is_lactating'] ?? 'NO')) === 'YES';
                if (!$has) {
                    foreach ($members as $member) {
                        if (strtoupper((string) ($member['is_lactating'] ?? 'NO')) === 'YES') {
                            $has = true;
                            break;
                        }
                    }
                }
                if (!$has) {
                    continue;
                }
                $category = 'A';
            } elseif (($meta['filter'] ?? '') === 'growth') {
                $category = nutrition_bnp_classify_growth_household(
                    $members,
                    (string) ($meta['growth_field'] ?? 'weight_for_age'),
                    $meta['moderate'] ?? [],
                    $meta['severe'] ?? [],
                    (string) ($meta['age'] ?? 'preschool')
                );
                if ($category === '') {
                    continue;
                }
            } else {
                continue;
            }

            $familyCount++;
            $categoryTotals[$category] = (int) ($categoryTotals[$category] ?? 0) + 1;
            nutrition_bnp_tally_prf($survey, $counts, $members);

            $occupation = trim((string) ($survey['occupation'] ?? ''));
            if ($occupation !== '') {
                $key = mb_strtolower($occupation);
                if (!isset($occupationCounts[$key])) {
                    $occupationCounts[$key] = ['label' => $occupation, 'count' => 0];
                }
                $occupationCounts[$key]['count']++;
            }

            $indPrf = nutrition_bnp_empty_prf_counts();
            nutrition_bnp_tally_prf($survey, $indPrf, $members);
            $indPrf['most_common_occupation'] = $occupation;
            $indCategories = [];
            foreach (array_keys($meta['labels'] ?? ['A' => 'A']) as $col) {
                $indCategories[$col] = $col === $category ? 1 : 0;
            }
            $individuals[] = [
                'head_name' => nutrition_household_head_display($survey),
                'purok' => trim((string) ($survey['purok'] ?? '')),
                'category' => $category,
                'category_totals' => $indCategories,
                'prf' => $indPrf,
            ];
        }

        $counts['most_common_occupation'] = nutrition_bnp_occupation_summary($occupationCounts);
        $settings = nutrition_load_settings($con, $barangayId);
        $bnsName = trim((string) ($settings['nutrition_officer'] ?? ''));

        return [
            'meta' => $meta,
            'family_count' => $familyCount,
            'category_totals' => $categoryTotals,
            'prf' => $counts,
            'individuals' => $individuals,
            'bns_name' => $bnsName,
            'calendar_year' => (int) date('Y'),
            'purok_options' => nutrition_list_household_puroks($con, $barangayId),
        ];
    }
}

if (!function_exists('nutrition_bnp_form_c1_defaults')) {
    /**
     * Manual / barangay-profile fields on official BNP Form C1 (not from household surveys).
     *
     * @return array<string, string>
     */
    function nutrition_bnp_form_c1_defaults(): array
    {
        return [
            'daycare_public' => '',
            'daycare_private' => '',
            'elementary_public' => '',
            'elementary_private' => '',
            'kindergarten' => '',
            'grade1' => '',
            'school_weighed' => '',
            'school_weighing_pct' => '',
            'school_sev_wasted' => '',
            'school_wasted' => '',
            'school_normal' => '',
            'school_ow' => '',
            'school_ob' => '',
            'fic' => '',
            'sari_sari' => '',
            'bns_count' => '',
            'bhw_count' => '',
            'midwife_count' => '',
            'ip_pregnant' => '',
            'ip_6_23' => '',
        ];
    }
}

if (!function_exists('nutrition_bnp_load_form_c1')) {
    /**
     * @return array<string, string>
     */
    function nutrition_bnp_load_form_c1(mysqli $con, string $barangayId): array
    {
        $defaults = nutrition_bnp_form_c1_defaults();
        $settings = nutrition_load_settings($con, $barangayId);
        $raw = trim((string) ($settings['bnp_form_c1'] ?? ''));
        if ($raw === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }
        $out = $defaults;
        foreach ($defaults as $key => $_) {
            if (array_key_exists($key, $decoded)) {
                $out[$key] = trim((string) $decoded[$key]);
            }
        }

        return $out;
    }
}

if (!function_exists('nutrition_bnp_all_hh_report')) {
    /**
     * BNP Form C1 — ALL HOUSEHOLDS.
     *
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_bnp_all_hh_report(mysqli $con, string $barangayId, array $filters = []): array
    {
        $households = nutrition_list_household_surveys($con, $barangayId, 0, $filters);
        $membersBySurvey = nutrition_list_barangay_family_members($con, $barangayId);
        $prf = nutrition_bnp_empty_prf_counts();

        $pregnant = 0;
        $lactating = 0;
        $hhWith0to59 = 0;
        $pop0to59 = 0;
        $weighed0to59 = 0;
        $wfa = ['SUW' => 0, 'UW' => 0, 'OW' => 0, 'Normal' => 0];
        $wfh = ['Sev Wasted' => 0, 'Wasted' => 0, 'OB' => 0, 'OW' => 0, 'Normal' => 0];
        $hfa = ['Severely Stunted' => 0, 'Stunted' => 0, 'Normal' => 0, 'Tall' => 0];
        $age0to5 = 0;
        $age6to11 = 0;
        $age0to23 = 0;
        $age12to59 = 0;
        $age24to59 = 0;
        $famUwSuw = 0;
        $famWasted = 0;
        $famStunted = 0;
        $exclusiveBf = 0;
        $complementary623 = 0;
        $iodized = 0;
        $carenderia = 0;
        $sariSariHh = 0;
        $fourPs = 0;
        $ip = 0;
        $ipPregnant = 0;
        $ip623 = 0;
        $actualHouseholds = count($households);
        $actualPopulation = barangay_count_residents($con, $barangayId);
        $surveyPopulation = 0;

        foreach ($households as $survey) {
            $surveyId = (string) ($survey['survey_id'] ?? '');
            $members = $membersBySurvey[$surveyId] ?? [];

            // "Actual population" for BNP Form C1:
            // - Prefer resident counts from `residence_status`
            // - If resident counts are incomplete, use survey-derived population
            //   based on original members_count logic.
            $size = (int) ($survey['members_count'] ?? 0);
            if ($size < 1) {
                $size = count($members) + 1;
            }
            $surveyPopulation += max(1, $size);

            if (strtoupper((string) ($survey['has_pregnant'] ?? 'NO')) === 'YES'
                || strtoupper((string) ($survey['head_is_pregnant'] ?? 'NO')) === 'YES') {
                $pregnant++;
            } else {
                foreach ($members as $m) {
                    if (strtoupper((string) ($m['is_pregnant'] ?? 'NO')) === 'YES') {
                        $pregnant++;
                        break;
                    }
                }
            }

            if (strtoupper((string) ($survey['has_lactating'] ?? 'NO')) === 'YES'
                || strtoupper((string) ($survey['head_is_lactating'] ?? 'NO')) === 'YES') {
                $lactating++;
            } else {
                foreach ($members as $m) {
                    if (strtoupper((string) ($m['is_lactating'] ?? 'NO')) === 'YES') {
                        $lactating++;
                        break;
                    }
                }
            }

            if (strtoupper((string) ($survey['uses_iodized_salt'] ?? 'NO')) === 'YES') {
                $iodized++;
            }
            if (strtoupper((string) ($survey['has_carenderia'] ?? 'NO')) === 'YES') {
                $carenderia++;
            }
            if (strtoupper((string) ($survey['has_sari_sari_store'] ?? 'NO')) === 'YES') {
                $sariSariHh++;
            }
            if (strtoupper((string) ($survey['is_4ps'] ?? 'NO')) === 'YES') {
                $fourPs++;
            }
            if (strtoupper((string) ($survey['is_ip'] ?? 'NO')) === 'YES') {
                $ip++;
            }

            $hhHasComplementary = trim((string) ($survey['complementary_meals'] ?? '')) !== '';
            $isIpHh = strtoupper((string) ($survey['is_ip'] ?? 'NO')) === 'YES';

            nutrition_bnp_tally_prf($survey, $prf, $members);

            $has059 = false;
            $hasUw = false;
            $hasSuw = false;
            $hasW = false;
            $hasSw = false;
            $hasSt = false;
            $hasSst = false;

            foreach ($members as $member) {
                $ageMonths = isset($member['age_months']) ? (int) $member['age_months'] : null;
                if ($ageMonths === null) {
                    $bd = trim((string) ($member['birth_date'] ?? ''));
                    $ageMonths = $bd !== '' ? nutrition_age_in_months($bd) : null;
                }
                if ($ageMonths === null || $ageMonths < 0) {
                    continue;
                }

                if ($ageMonths <= 59) {
                    $has059 = true;
                    $pop0to59++;
                    if ($ageMonths <= 5) {
                        $age0to5++;
                        if (
                            strtoupper((string) ($member['lactating_exclusive_breastfeeding'] ?? 'NO')) === 'YES'
                            || strtoupper((string) ($member['planned_exclusive_breastfeeding'] ?? 'NO')) === 'YES'
                        ) {
                            $exclusiveBf++;
                        }
                    } elseif ($ageMonths <= 11) {
                        $age6to11++;
                    }
                    if ($ageMonths <= 23) {
                        $age0to23++;
                    }
                    if ($ageMonths >= 6 && $ageMonths <= 23 && $hhHasComplementary) {
                        $complementary623++;
                    }
                    if ($ageMonths >= 12) {
                        $age12to59++;
                    }
                    if ($ageMonths >= 24) {
                        $age24to59++;
                    }
                    if ($isIpHh) {
                        if (strtoupper((string) ($member['is_pregnant'] ?? 'NO')) === 'YES') {
                            $ipPregnant++;
                        }
                        if ($ageMonths >= 6 && $ageMonths <= 23) {
                            $ip623++;
                        }
                    }

                    $wfaVal = (string) ($member['weight_for_age'] ?? '');
                    $wfhVal = (string) ($member['weight_for_height'] ?? '');
                    $hfaVal = (string) ($member['height_for_age'] ?? '');
                    if ($wfaVal !== '' || $wfhVal !== '' || $hfaVal !== '') {
                        $weighed0to59++;
                    }
                    $wfaKey = strtoupper($wfaVal);
                    if ($wfaKey === 'SUW') {
                        $wfa['SUW']++;
                        $hasSuw = true;
                    } elseif ($wfaKey === 'UW') {
                        $wfa['UW']++;
                        $hasUw = true;
                    } elseif ($wfaKey === 'OW') {
                        $wfa['OW']++;
                    } elseif ($wfaKey === 'NORMAL') {
                        $wfa['Normal']++;
                    }

                    $wfhNorm = nutrition_bnp_normalize_growth_label($wfhVal);
                    if ($wfhNorm === 'sev wasted') {
                        $wfh['Sev Wasted']++;
                        $hasSw = true;
                    } elseif ($wfhNorm === 'wasted') {
                        $wfh['Wasted']++;
                        $hasW = true;
                    } elseif ($wfhNorm === 'ob') {
                        $wfh['OB']++;
                    } elseif ($wfhNorm === 'ow') {
                        $wfh['OW']++;
                    } elseif ($wfhNorm === 'normal') {
                        $wfh['Normal']++;
                    }

                    $hfaNorm = nutrition_bnp_normalize_growth_label($hfaVal);
                    if ($hfaNorm === 'severely stunted') {
                        $hfa['Severely Stunted']++;
                        $hasSst = true;
                    } elseif ($hfaNorm === 'stunted') {
                        $hfa['Stunted']++;
                        $hasSt = true;
                    } elseif ($hfaNorm === 'tall') {
                        $hfa['Tall']++;
                    } elseif ($hfaNorm === 'normal') {
                        $hfa['Normal']++;
                    }
                }
            }

            if ($has059) {
                $hhWith0to59++;
            }
            if ($hasUw || $hasSuw) {
                $famUwSuw++;
            }
            if ($hasW || $hasSw) {
                $famWasted++;
            }
            if ($hasSt || $hasSst) {
                $famStunted++;
            }
        }

        // BNP Form C1 "Total Actual Population" for consolidated prints:
        // derive from household survey counts (members_count logic).
        // Resident counts are intentionally not used because they may be incomplete
        // in the current dataset, causing mismatched totals.
        $actualPopulation = (int) $surveyPopulation;

        $settings = nutrition_load_settings($con, $barangayId);
        $formC1 = nutrition_bnp_load_form_c1($con, $barangayId);

        return [
            'meta' => nutrition_bnp_resolve_type('all_hh'),
            'calendar_year' => (int) date('Y'),
            'bns_name' => trim((string) ($settings['nutrition_officer'] ?? '')),
            'indicators' => [
                'actual_population' => $actualPopulation,
                'actual_households' => $actualHouseholds,
                'households_surveyed' => $actualHouseholds,
                'pregnant_women' => $pregnant,
                'lactating_women' => $lactating,
                'hh_with_0_59' => $hhWith0to59,
                'pop_0_59' => $pop0to59,
                'weighed_0_59' => $weighed0to59,
                'weighing_percent' => $pop0to59 > 0 ? round(($weighed0to59 / $pop0to59) * 100, 1) : 0,
                'wfa' => $wfa,
                'wfh' => $wfh,
                'hfa' => $hfa,
                'age_0_5' => $age0to5,
                'age_6_11' => $age6to11,
                'age_0_23' => $age0to23,
                'age_12_59' => $age12to59,
                'age_24_59' => $age24to59,
                'fam_uw_suw' => $famUwSuw,
                'fam_wasted' => $famWasted,
                'fam_stunted' => $famStunted,
                'exclusive_bf' => $exclusiveBf,
                'complementary_6_23' => $complementary623,
                'iodized_salt_hh' => $iodized,
                'carenderia_hh' => $carenderia,
                'sari_sari_hh' => $sariSariHh,
                'four_ps' => $fourPs,
                'ip' => $ip,
                'ip_pregnant' => $ipPregnant,
                'ip_6_23' => $ip623,
            ],
            'form_c1' => $formC1,
            'prf' => $prf,
            'purok_options' => nutrition_list_household_puroks($con, $barangayId),
        ];
    }
}

if (!function_exists('nutrition_bnp_build_report')) {
    /**
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_bnp_build_report(mysqli $con, string $barangayId, string $typeKey, array $filters = []): array
    {
        $meta = nutrition_bnp_resolve_type($typeKey);
        if ($meta === null) {
            return [];
        }
        if ($typeKey === 'all_hh') {
            return nutrition_bnp_all_hh_report($con, $barangayId, $filters);
        }
        if ($typeKey === 'pregnant') {
            $report = nutrition_pregnant_families_report($con, $barangayId, $filters);
            $report['meta'] = $meta;

            return $report;
        }

        return nutrition_bnp_family_profile_report($con, $barangayId, $typeKey, $filters);
    }
}

if (!function_exists('nutrition_bnp_sum_numeric_tree')) {
    /**
     * Deep-add numeric leaves from $source into $target (skips known non-count keys).
     *
     * @param array<string, mixed> $target
     * @param array<string, mixed> $source
     */
    function nutrition_bnp_sum_numeric_tree(array &$target, array $source, array $skipKeys = []): void
    {
        foreach ($source as $key => $value) {
            if (in_array((string) $key, $skipKeys, true)) {
                continue;
            }
            if (is_array($value)) {
                if (!isset($target[$key]) || !is_array($target[$key])) {
                    $target[$key] = [];
                }
                nutrition_bnp_sum_numeric_tree($target[$key], $value, $skipKeys);
                continue;
            }
            if (is_numeric($value)) {
                $target[$key] = (int) ($target[$key] ?? 0) + (int) $value;
            }
        }
    }
}

if (!function_exists('nutrition_bnp_merge_prf_counts')) {
    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $source
     */
    function nutrition_bnp_merge_prf_counts(array &$target, array $source): void
    {
        nutrition_bnp_sum_numeric_tree($target, $source, ['most_common_occupation']);
    }
}

if (!function_exists('nutrition_bnp_merge_occupation_summary')) {
    /**
     * @param array<string, array{label:string,count:int}> $occupationCounts
     */
    function nutrition_bnp_merge_occupation_summary(array &$occupationCounts, string $summary): void
    {
        $summary = trim($summary);
        if ($summary === '') {
            return;
        }
        foreach (explode(',', $summary) as $part) {
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
            if ($label === '') {
                continue;
            }
            $key = mb_strtolower($label);
            if (!isset($occupationCounts[$key])) {
                $occupationCounts[$key] = ['label' => $label, 'count' => 0];
            }
            $occupationCounts[$key]['count'] += $count;
        }
    }
}

if (!function_exists('nutrition_bnp_city_build_report')) {
    /**
     * City-wide consolidated BNP report for one form type (C1–C9).
     *
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    function nutrition_bnp_city_build_report(mysqli $con, string $typeKey, array $filters = []): array
    {
        $meta = nutrition_bnp_resolve_type($typeKey);
        if ($meta === null) {
            return [];
        }

        if ($typeKey === 'pregnant') {
            $report = nutrition_city_pregnant_families_report($con, $filters);
            $report['meta'] = $meta;
            $report['bns_name'] = '';

            return $report;
        }

        $calendarYear = (int) date('Y');
        $occupationCounts = [];

        if ($typeKey === 'all_hh') {
            $merged = [
                'meta' => $meta,
                'calendar_year' => $calendarYear,
                'bns_name' => '',
                'indicators' => [
                    'actual_population' => 0,
                    'actual_households' => 0,
                    'households_surveyed' => 0,
                    'pregnant_women' => 0,
                    'lactating_women' => 0,
                    'hh_with_0_59' => 0,
                    'pop_0_59' => 0,
                    'weighed_0_59' => 0,
                    'weighing_percent' => 0,
                    'wfa' => ['SUW' => 0, 'UW' => 0, 'OW' => 0, 'Normal' => 0],
                    'wfh' => ['Sev Wasted' => 0, 'Wasted' => 0, 'OB' => 0, 'OW' => 0, 'Normal' => 0],
                    'hfa' => ['Severely Stunted' => 0, 'Stunted' => 0, 'Normal' => 0, 'Tall' => 0],
                    'age_0_5' => 0,
                    'age_6_11' => 0,
                    'age_0_23' => 0,
                    'age_12_59' => 0,
                    'age_24_59' => 0,
                    'fam_uw_suw' => 0,
                    'fam_wasted' => 0,
                    'fam_stunted' => 0,
                    'exclusive_bf' => 0,
                    'complementary_6_23' => 0,
                    'iodized_salt_hh' => 0,
                    'carenderia_hh' => 0,
                    'sari_sari_hh' => 0,
                    'four_ps' => 0,
                    'ip' => 0,
                    'ip_pregnant' => 0,
                    'ip_6_23' => 0,
                ],
                'form_c1' => nutrition_bnp_form_c1_defaults(),
                'prf' => nutrition_bnp_empty_prf_counts(),
                'purok_options' => [],
            ];

            foreach (barangay_list_all($con) as $brgy) {
                $id = (string) ($brgy['id'] ?? '');
                $name = (string) ($brgy['barangay'] ?? '');
                if ($id === '' || barangay_is_placeholder_name($name)) {
                    continue;
                }
                $report = nutrition_bnp_all_hh_report($con, $id, $filters);
                nutrition_bnp_sum_numeric_tree(
                    $merged['indicators'],
                    $report['indicators'] ?? [],
                    ['weighing_percent']
                );
                nutrition_bnp_merge_prf_counts($merged['prf'], $report['prf'] ?? []);
                foreach (nutrition_bnp_form_c1_defaults() as $key => $_) {
                    $a = trim((string) ($merged['form_c1'][$key] ?? ''));
                    $b = trim((string) (($report['form_c1'][$key] ?? '')));
                    $sum = (is_numeric($a) ? (float) $a : 0) + (is_numeric($b) ? (float) $b : 0);
                    $merged['form_c1'][$key] = ($a === '' && $b === '') ? '' : (string) (int) $sum;
                }
                nutrition_bnp_merge_occupation_summary(
                    $occupationCounts,
                    (string) (($report['prf']['most_common_occupation'] ?? ''))
                );
            }

            $pop = (int) ($merged['indicators']['pop_0_59'] ?? 0);
            $weighed = (int) ($merged['indicators']['weighed_0_59'] ?? 0);
            $merged['indicators']['weighing_percent'] = $pop > 0 ? round(($weighed / $pop) * 100, 1) : 0;
            $merged['prf']['most_common_occupation'] = nutrition_bnp_occupation_summary($occupationCounts);

            return $merged;
        }

        $labels = $meta['labels'] ?? ['A' => 'A'];
        $categoryTotals = [];
        foreach (array_keys($labels) as $col) {
            $categoryTotals[$col] = 0;
        }
        $merged = [
            'meta' => $meta,
            'family_count' => 0,
            'category_totals' => $categoryTotals,
            'prf' => nutrition_bnp_empty_prf_counts(),
            'individuals' => [],
            'bns_name' => '',
            'calendar_year' => $calendarYear,
            'purok_options' => [],
        ];

        foreach (barangay_list_all($con) as $brgy) {
            $id = (string) ($brgy['id'] ?? '');
            $name = (string) ($brgy['barangay'] ?? '');
            if ($id === '' || barangay_is_placeholder_name($name)) {
                continue;
            }
            $report = nutrition_bnp_family_profile_report($con, $id, $typeKey, $filters);
            $merged['family_count'] += (int) ($report['family_count'] ?? 0);
            foreach ($report['category_totals'] ?? [] as $col => $count) {
                $merged['category_totals'][$col] = (int) ($merged['category_totals'][$col] ?? 0) + (int) $count;
            }
            nutrition_bnp_merge_prf_counts($merged['prf'], $report['prf'] ?? []);
            nutrition_bnp_merge_occupation_summary(
                $occupationCounts,
                (string) (($report['prf']['most_common_occupation'] ?? ''))
            );
        }

        $merged['prf']['most_common_occupation'] = nutrition_bnp_occupation_summary($occupationCounts);

        return $merged;
    }
}

if (!function_exists('nutrition_bnp_city_all_reports')) {
    /**
     * Build consolidated city-wide reports for every BNP form C1–C9.
     *
     * @param array<string, string> $filters
     * @return array<int, array{key:string,meta:array<string,mixed>,report:array<string,mixed>}>
     */
    function nutrition_bnp_city_all_reports(mysqli $con, array $filters = []): array
    {
        $out = [];
        foreach (nutrition_bnp_report_types() as $key => $meta) {
            $out[] = [
                'key' => $key,
                'meta' => $meta,
                'report' => nutrition_bnp_city_build_report($con, $key, $filters),
            ];
        }

        return $out;
    }
}
