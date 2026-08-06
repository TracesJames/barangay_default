<?php
/**
 * Region 10 eOPT Plus ver2 LMS growth scoring (Female + Male).
 */

if (!function_exists('nutrition_eopt_lms_dataset')) {
    /**
     * @return array<string, array<string, array<string, array{0:float,1:float,2:float,3:float}>>>
     */
    function nutrition_eopt_lms_dataset(): array
    {
        static $data = null;
        if ($data !== null) {
            return $data;
        }
        $path = __DIR__ . '/nutrition_eopt_lms_data.php';
        if (!is_file($path)) {
            $data = [];
            return $data;
        }
        $loaded = require $path;
        $data = is_array($loaded) ? $loaded : [];

        return $data;
    }
}

if (!function_exists('nutrition_age_in_days')) {
    function nutrition_age_in_days(?string $birthDate, ?string $referenceDate = null): ?int
    {
        $birthDate = trim((string) $birthDate);
        if ($birthDate === '') {
            return null;
        }
        $referenceDate = trim((string) ($referenceDate ?? date('Y-m-d')));
        try {
            $birth = new DateTimeImmutable($birthDate);
            $reference = new DateTimeImmutable($referenceDate);
        } catch (Exception $e) {
            return null;
        }
        if ($birth > $reference) {
            return null;
        }

        return (int) $birth->diff($reference)->days;
    }
}

if (!function_exists('nutrition_eopt_lms_z_score')) {
    /**
     * WHO LMS z-score: z = (((y/M)^L) - 1) / (L*S) ; if L≈0 then ln(y/M)/S
     *
     * @param array{0:float,1:float,2:float,3:float} $lms [L, M, S, SD]
     */
    function nutrition_eopt_lms_z_score(float $value, array $lms): ?float
    {
        if ($value <= 0) {
            return null;
        }
        $L = (float) $lms[0];
        $M = (float) $lms[1];
        $S = (float) $lms[2];
        if ($M <= 0 || $S <= 0) {
            return null;
        }
        if (abs($L) < 1.0e-7) {
            return log($value / $M) / $S;
        }
        $ratio = $value / $M;
        if ($ratio <= 0) {
            return null;
        }

        return (pow($ratio, $L) - 1.0) / ($L * $S);
    }
}

if (!function_exists('nutrition_eopt_lms_lookup')) {
    /**
     * @return array{0:float,1:float,2:float,3:float}|null
     */
    function nutrition_eopt_lms_lookup(string $sex, string $indicator, string $indexKey): ?array
    {
        $data = nutrition_eopt_lms_dataset();
        if ($sex !== 'Female' && $sex !== 'Male') {
            return null;
        }
        $row = $data[$sex][$indicator][$indexKey] ?? null;
        if (!is_array($row) || count($row) < 3) {
            return null;
        }

        return [
            (float) $row[0],
            (float) $row[1],
            (float) $row[2],
            (float) ($row[3] ?? 0),
        ];
    }
}

if (!function_exists('nutrition_eopt_lms_lookup_nearest')) {
    /**
     * Exact key, else nearest available index (cm*10 or day).
     *
     * @return array{0:float,1:float,2:float,3:float}|null
     */
    function nutrition_eopt_lms_lookup_nearest(string $sex, string $indicator, int $index): ?array
    {
        $exact = nutrition_eopt_lms_lookup($sex, $indicator, (string) $index);
        if ($exact !== null) {
            return $exact;
        }
        $data = nutrition_eopt_lms_dataset();
        $table = $data[$sex][$indicator] ?? [];
        if ($table === []) {
            return null;
        }
        $bestKey = null;
        $bestDist = PHP_INT_MAX;
        foreach (array_keys($table) as $key) {
            $k = (int) $key;
            $dist = abs($k - $index);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $bestKey = (string) $key;
            }
        }
        if ($bestKey === null) {
            return null;
        }

        return nutrition_eopt_lms_lookup($sex, $indicator, $bestKey);
    }
}

if (!function_exists('nutrition_eopt_growth_assessment')) {
    /**
     * eOPT LMS assessment for a child (0–~60 months / day 0–1856).
     *
     * @return array{
     *   age_months:?int,
     *   age_days:?int,
     *   is_child_0_to_5:bool,
     *   expected_weight_kg:?float,
     *   expected_height_cm:?float,
     *   weight_for_age:string,
     *   height_for_age:string,
     *   weight_for_height:string,
     *   z_wfa:?float,
     *   z_hfa:?float,
     *   z_wfh:?float,
     *   source:string
     * }
     */
    function nutrition_eopt_growth_assessment(
        string $gender,
        ?string $birthDate,
        float $weightKg,
        float $heightCm,
        ?string $referenceDate = null
    ): array {
        $empty = [
            'age_months' => null,
            'age_days' => null,
            'is_child_0_to_5' => false,
            'expected_weight_kg' => null,
            'expected_height_cm' => null,
            'weight_for_age' => '',
            'height_for_age' => '',
            'weight_for_height' => '',
            'z_wfa' => null,
            'z_hfa' => null,
            'z_wfh' => null,
            'source' => 'eopt_lms',
        ];

        $ageDays = nutrition_age_in_days($birthDate, $referenceDate);
        $ageMonths = nutrition_age_in_months($birthDate, $referenceDate);
        if ($ageDays === null || $ageMonths === null) {
            return $empty;
        }

        // eOPT tables cover through day 1856 (~60.9 months).
        $inEoptRange = $ageDays <= 1856;
        $hasGender = in_array($gender, ['Male', 'Female'], true);

        $wfaLms = $hasGender ? nutrition_eopt_lms_lookup_nearest($gender, 'wfa', $ageDays) : null;
        $hfaLms = $hasGender ? nutrition_eopt_lms_lookup_nearest($gender, 'hfa', $ageDays) : null;

        $expectedWeight = $wfaLms !== null ? round((float) $wfaLms[1], 2) : null;
        $expectedHeight = $hfaLms !== null ? round((float) $hfaLms[1], 1) : null;

        $result = [
            'age_months' => $ageMonths,
            'age_days' => $ageDays,
            'is_child_0_to_5' => $inEoptRange && nutrition_is_child_0_to_5($ageMonths),
            'expected_weight_kg' => $inEoptRange ? $expectedWeight : null,
            'expected_height_cm' => $inEoptRange ? $expectedHeight : null,
            'weight_for_age' => '',
            'height_for_age' => '',
            'weight_for_height' => '',
            'z_wfa' => null,
            'z_hfa' => null,
            'z_wfh' => null,
            'source' => 'eopt_lms',
        ];

        if (!$hasGender || !$inEoptRange || $weightKg <= 0 || $heightCm <= 0) {
            return $result;
        }

        $result['is_child_0_to_5'] = true;

        $zWfa = $wfaLms !== null ? nutrition_eopt_lms_z_score($weightKg, $wfaLms) : null;
        $zHfa = $hfaLms !== null ? nutrition_eopt_lms_z_score($heightCm, $hfaLms) : null;

        // <24 months: weight-for-length; >=24 months: weight-for-height (WHO / eOPT practice).
        $cmKey = (int) round($heightCm * 10);
        if ($ageMonths < 24) {
            $wfhLms = nutrition_eopt_lms_lookup_nearest($gender, 'wfl', $cmKey);
        } else {
            $wfhLms = nutrition_eopt_lms_lookup_nearest($gender, 'wfh', $cmKey);
            // Fall back to WFL if standing-height row missing but length table covers value.
            if ($wfhLms === null) {
                $wfhLms = nutrition_eopt_lms_lookup_nearest($gender, 'wfl', $cmKey);
            }
        }
        $zWfh = $wfhLms !== null ? nutrition_eopt_lms_z_score($weightKg, $wfhLms) : null;

        $result['z_wfa'] = $zWfa !== null ? round($zWfa, 2) : null;
        $result['z_hfa'] = $zHfa !== null ? round($zHfa, 2) : null;
        $result['z_wfh'] = $zWfh !== null ? round($zWfh, 2) : null;
        $result['weight_for_age'] = nutrition_classify_weight_for_age($zWfa);
        $result['height_for_age'] = nutrition_classify_height_for_age($zHfa);
        $result['weight_for_height'] = nutrition_classify_weight_for_height($zWfh);

        return $result;
    }
}

if (!function_exists('nutrition_eopt_classify_muac')) {
    /**
     * MUAC status via eOPT LMS (age in days) with WHO field cutoffs as fallback (6–59 mo).
     */
    function nutrition_eopt_classify_muac(
        string $gender,
        ?string $birthDate,
        ?float $muacCm,
        ?string $referenceDate = null
    ): string {
        if ($muacCm === null || $muacCm <= 0) {
            return '';
        }

        $ageDays = nutrition_age_in_days($birthDate, $referenceDate);
        $ageMonths = nutrition_age_in_months($birthDate, $referenceDate);
        $hasGender = in_array($gender, ['Male', 'Female'], true);

        if ($hasGender && $ageDays !== null && $ageDays >= 91) {
            $lms = nutrition_eopt_lms_lookup_nearest($gender, 'muac', $ageDays);
            if ($lms !== null) {
                $z = nutrition_eopt_lms_z_score($muacCm, $lms);
                if ($z === null) {
                    return '';
                }
                if ($z < -3) {
                    return 'SW';
                }
                if ($z < -2) {
                    return 'MW';
                }

                return 'Normal';
            }
        }

        // WHO MUAC cut-offs commonly used for 6–59 months when LMS row is unavailable.
        if ($ageMonths !== null && $ageMonths >= 6 && $ageMonths <= 59) {
            if ($muacCm < 11.5) {
                return 'SW';
            }
            if ($muacCm < 12.5) {
                return 'MW';
            }

            return 'Normal';
        }

        return '';
    }
}
