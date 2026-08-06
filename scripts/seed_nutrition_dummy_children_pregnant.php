<?php

/**
 * Seed dummy household surveys with children (0–59 mo + school-age)
 * and pregnant / lactating members for all Valencia barangays.
 *
 * Usage: php scripts/seed_nutrition_dummy_children_pregnant.php
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';
require_once dirname(__DIR__) . '/includes/nutrition_context.php';

nutrition_ensure_module_tables($con);

// Remove incomplete dummy seed rows from a previous failed run.
$con->query(
    "DELETE fm FROM nutrition_household_family_member fm
     INNER JOIN nutrition_household_survey s ON s.survey_id = fm.survey_id
     WHERE s.remarks LIKE 'Dummy seed data%'"
);
$con->query("DELETE FROM nutrition_household_survey WHERE remarks LIKE 'Dummy seed data%'");

$childFirst = [
    'Juan', 'Pedro', 'Miguel', 'Carlos', 'Jose', 'Andres', 'Rafael', 'Diego',
    'Maria', 'Sofia', 'Angela', 'Isabella', 'Camila', 'Lucia', 'Elena', 'Andrea',
];
$adultFirstF = ['Maria', 'Ana', 'Rosa', 'Liza', 'Jenny', 'Grace', 'Helen', 'Carmen', 'Nora', 'Fe'];
$adultFirstM = ['Juan', 'Pedro', 'Roberto', 'Antonio', 'Eduardo', 'Manuel', 'Ricardo', 'Fernando'];
$lastNames = [
    'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores',
    'Gonzales', 'Ramos', 'Lopez', 'Diaz', 'Castillo', 'Villanueva', 'Aquino', 'Navarro',
];

$wfaPool = ['Normal', 'Normal', 'Normal', 'UW', 'SUW', 'OW'];
$hfaPool = ['Normal', 'Normal', 'Stunted', 'Severely Stunted', 'Tall', 'Normal'];
$wfhPool = ['Normal', 'Normal', 'Wasted', 'Sev Wasted', 'OW', 'OB', 'Normal'];
$pregnantStatusPool = ['Normal', 'Teenage', 'Teenage', 'Underweight', 'Overweight'];

$surveyDate = date('Y-m-d');
$createdSurveys = 0;
$createdChildren = 0;
$createdPregnant = 0;
$nameIndex = 0;

$pick = static function (array $pool, int $n) {
    return $pool[$n % count($pool)];
};

$birthFromAgeMonths = static function (int $ageMonths, string $refDate): string {
    $dt = DateTime::createFromFormat('Y-m-d', $refDate) ?: new DateTime();
    $dt->modify('-' . $ageMonths . ' months');

    return $dt->format('Y-m-d');
};

$emptyMemberFlags = static function (): array {
    return [
        'is_pregnant' => 'NO',
        'is_lactating' => 'NO',
        'pregnancy_months' => null,
        'pregnant_nutrition_status' => '',
        'planned_exclusive_breastfeeding' => 'NO',
        'planned_mixed_feeding' => 'NO',
        'planned_bottle_feeding' => 'NO',
        'planned_other_feeding' => 'NO',
        'planned_other_specify' => '',
        'lactating_exclusive_breastfeeding' => 'NO',
        'lactating_mixed_feeding' => 'NO',
        'lactating_bottle_feeding' => 'NO',
        'lactating_other_feeding' => 'NO',
        'lactating_other_specify' => '',
    ];
};

$makeChild = static function (
    string $name,
    string $gender,
    int $ageMonths,
    string $surveyDate,
    array $wfaPool,
    array $hfaPool,
    array $wfhPool,
    int $seed,
    callable $birthFromAgeMonths,
    callable $emptyMemberFlags,
    callable $pick
) use (&$createdChildren): array {
    $flags = $emptyMemberFlags();
    $weight = max(2.5, round(3.2 + ($ageMonths * 0.22) + (($seed % 5) * 0.15), 2));
    $height = max(45.0, round(50 + ($ageMonths * 0.65) + (($seed % 4) * 0.4), 1));

    if ($ageMonths <= 59) {
        $growth = nutrition_family_member_growth_assessment($gender, $birthFromAgeMonths($ageMonths, $surveyDate), $weight, $height, $surveyDate);
        $wfa = $growth['weight_for_age'] !== '' ? $growth['weight_for_age'] : $pick($wfaPool, $seed);
        $hfa = $growth['height_for_age'] !== '' ? $growth['height_for_age'] : $pick($hfaPool, $seed + 1);
        $wfh = $growth['weight_for_height'] !== '' ? $growth['weight_for_height'] : $pick($wfhPool, $seed + 2);
        // Force variety for demo rows.
        if ($seed % 7 === 0) {
            $wfa = 'UW';
        } elseif ($seed % 11 === 0) {
            $wfa = 'SUW';
        } elseif ($seed % 9 === 0) {
            $hfa = 'Stunted';
        } elseif ($seed % 13 === 0) {
            $wfh = 'Wasted';
        }
    } else {
        // School-age: set WFH classes used by BNP C8/C9.
        $wfa = '';
        $hfa = '';
        $wfh = $pick(['Normal', 'Normal', 'Wasted', 'Sev Wasted', 'OW', 'OB'], $seed);
        $weight = max(15.0, round(18 + (($ageMonths - 60) * 0.18), 2));
        $height = max(100.0, round(105 + (($ageMonths - 60) * 0.35), 1));
    }

    $createdChildren++;

    return array_merge($flags, [
        'member_name' => $name,
        'relationship' => 'Son/Daughter',
        'gender' => $gender,
        'birth_date' => $birthFromAgeMonths($ageMonths, $surveyDate),
        'weight_kg' => $weight,
        'height_cm' => $height,
        'age_months' => $ageMonths,
        'weight_for_age' => $wfa,
        'height_for_age' => $hfa,
        'weight_for_height' => $wfh,
    ]);
};

$insertHousehold = static function (
    mysqli $con,
    string $barangayId,
    string $barangayName,
    string $purokLabel,
    string $bnsName,
    array $head,
    array $members,
    string $surveyDate
) use (&$createdSurveys): bool {
    $surveyId = (string) hexdec(uniqid());
    $houseHoldId = nutrition_generate_household_reference($con, $barangayId, $purokLabel, $barangayName);
    $householdHead = nutrition_format_household_head_name(
        $head['last'],
        $head['first'],
        $head['middle'] ?? '',
        ''
    );
    $childrenCount = 0;
    $hasPregnant = ($head['is_pregnant'] ?? 'NO') === 'YES' ? 'YES' : 'NO';
    $hasLactating = ($head['is_lactating'] ?? 'NO') === 'YES' ? 'YES' : 'NO';
    foreach ($members as $m) {
        if (($m['is_pregnant'] ?? 'NO') === 'YES') {
            $hasPregnant = 'YES';
        }
        if (($m['is_lactating'] ?? 'NO') === 'YES') {
            $hasLactating = 'YES';
        }
        $age = $m['age_months'] ?? null;
        if ($age !== null && (int) $age <= 216) {
            $childrenCount++;
        }
    }
    $membersCount = (string) (count($members) + 1);
    $childrenCountStr = (string) $childrenCount;
    $empty = '';
    $no = 'NO';
    $yes = 'YES';
    $secure = 'secure';
    $water = 'Pipe Water System';
    $toilet = 'Pour/Flush type with septic tank';
    $owned = 'Owned';
    $dwelling = 'Semi-concrete';
    $garbage = 'Collected';
    $zeroInt = 0;
    $nullInt = null;
    $headPregMonths = isset($head['pregnancy_months']) && $head['pregnancy_months'] !== null
        ? (string) (int) $head['pregnancy_months']
        : null;
    $headPregStatus = (string) ($head['pregnant_nutrition_status'] ?? '');
    $residenceId = '';
    $suffix = '';
    $birthDate = $head['birth_date'] ?? null;
    $occupation = $head['occupation'] ?? 'Farmer';
    $foodProd = 'Vegetable Garden';
    $remarks = 'Dummy seed data for nutrition demo reports.';

    $stmt = $con->prepare(
        'INSERT INTO nutrition_household_survey
         (survey_id, barangay_id, residence_id, house_hold_id, purok_label, head_last_name, head_first_name, head_middle_name,
          head_suffix, household_head, birth_date, gender, occupation, bns_name, is_4ps, is_pwd, is_ip, is_solo_parent, is_na_member,
          survey_date, members_count, children_count, food_security, has_pregnant, has_lactating,
          head_is_pregnant, head_is_lactating, head_pregnancy_months, head_pregnant_nutrition_status,
          head_planned_exclusive_breastfeeding, head_planned_mixed_feeding, head_planned_bottle_feeding,
          head_planned_other_feeding, head_planned_other_specify,
          head_lactating_exclusive_breastfeeding, head_lactating_mixed_feeding, head_lactating_bottle_feeding,
          head_lactating_other_feeding, head_lactating_other_specify,
          supplementary_feeding,
          water_source, sanitation, house_ownership, house_ownership_other, toilet_type, garbage_disposal, garbage_uncollected_type,
          dwelling_type, food_production, uses_iodized_salt, uses_sangkap_pinoy, practices_family_planning, family_planning_methods,
          complementary_meals, complementary_meals_other, complementary_snacks, complementary_snacks_other,
          child_physical_activity, child_physical_activity_other, remarks, surveyed_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    if (!$stmt) {
        echo "Insert prepare failed: {$con->error}\n";

        return false;
    }

    $surveyedBy = 'seed';
    $headIsPregnant = ($head['is_pregnant'] ?? 'NO') === 'YES' ? 'YES' : 'NO';
    $headIsLactating = ($head['is_lactating'] ?? 'NO') === 'YES' ? 'YES' : 'NO';
    $plannedExclusive = $headIsPregnant === 'YES' ? 'YES' : 'NO';
    $lactExclusive = $headIsLactating === 'YES' ? 'YES' : 'NO';
    $is4ps = ($head['is_4ps'] ?? 'NO') === 'YES' ? 'YES' : 'NO';

    $stmt->bind_param(
        str_repeat('s', 61),
        $surveyId,
        $barangayId,
        $residenceId,
        $houseHoldId,
        $purokLabel,
        $head['last'],
        $head['first'],
        $head['middle'],
        $suffix,
        $householdHead,
        $birthDate,
        $head['gender'],
        $occupation,
        $bnsName,
        $is4ps,
        $no,
        $no,
        $no,
        $no,
        $surveyDate,
        $membersCount,
        $childrenCountStr,
        $secure,
        $hasPregnant,
        $hasLactating,
        $headIsPregnant,
        $headIsLactating,
        $headPregMonths,
        $headPregStatus,
        $plannedExclusive,
        $no,
        $no,
        $no,
        $empty,
        $lactExclusive,
        $no,
        $no,
        $no,
        $empty,
        $no,
        $water,
        $toilet,
        $owned,
        $empty,
        $toilet,
        $garbage,
        $empty,
        $dwelling,
        $foodProd,
        $yes,
        $yes,
        $yes,
        $empty,
        $empty,
        $empty,
        $empty,
        $empty,
        $empty,
        $empty,
        $remarks,
        $surveyedBy
    );

    if (!$stmt->execute()) {
        echo "Survey insert failed for {$barangayName}: {$stmt->error}\n";
        $stmt->close();

        return false;
    }
    $stmt->close();

    nutrition_save_household_family_members($con, $surveyId, $barangayId, $members);
    $createdSurveys++;

    return true;
};

foreach (barangay_list_all($con) as $brgy) {
    $barangayId = (string) ($brgy['id'] ?? '');
    $barangayName = (string) ($brgy['barangay'] ?? '');
    if ($barangayId === '' || barangay_is_placeholder_name($barangayName)) {
        continue;
    }

    // Skip if this barangay already has seed surveys.
    $check = $con->prepare(
        "SELECT COUNT(*) AS total FROM nutrition_household_survey
         WHERE barangay_id = ? AND remarks LIKE 'Dummy seed data%'"
    );
    $existing = 0;
    if ($check) {
        $check->bind_param('s', $barangayId);
        $check->execute();
        $existing = (int) (($check->get_result()->fetch_assoc()['total'] ?? 0));
        $check->close();
    }
    if ($existing > 0) {
        echo "Skip {$barangayName}: already has {$existing} dummy survey(s).\n";
        continue;
    }

    $settings = nutrition_load_settings($con, $barangayId, $barangayName);
    $bnsName = trim((string) ($settings['nutrition_officer'] ?? ''));
    if ($bnsName === '') {
        $bnsName = 'Maria Santos';
    }

    // Household 1: Female head + 2 preschool children
    $last = $pick($lastNames, $nameIndex++);
    $first = $pick($adultFirstF, $nameIndex);
    $members = [
        $makeChild(
            $pick($childFirst, $nameIndex) . ' ' . $last,
            ($nameIndex % 2 === 0) ? 'Male' : 'Female',
            18 + ($nameIndex % 30),
            $surveyDate,
            $wfaPool,
            $hfaPool,
            $wfhPool,
            $nameIndex,
            $birthFromAgeMonths,
            $emptyMemberFlags,
            $pick
        ),
        $makeChild(
            $pick($childFirst, $nameIndex + 3) . ' ' . $last,
            ($nameIndex % 2 === 1) ? 'Male' : 'Female',
            36 + ($nameIndex % 20),
            $surveyDate,
            $wfaPool,
            $hfaPool,
            $wfhPool,
            $nameIndex + 3,
            $birthFromAgeMonths,
            $emptyMemberFlags,
            $pick
        ),
    ];
    $insertHousehold($con, $barangayId, $barangayName, 'Purok 1', $bnsName, [
        'last' => $last,
        'first' => $first,
        'middle' => 'D',
        'gender' => 'Female',
        'birth_date' => $birthFromAgeMonths(28 * 12, $surveyDate),
        'occupation' => 'Housewife',
        'is_pregnant' => 'NO',
        'is_lactating' => 'NO',
        'is_4ps' => ($nameIndex % 3 === 0) ? 'YES' : 'NO',
    ], $members, $surveyDate);

    // Household 2: Pregnant female head + 1 infant
    $last = $pick($lastNames, $nameIndex++);
    $first = $pick($adultFirstF, $nameIndex + 2);
    $pregMonths = 3 + ($nameIndex % 6);
    $pregStatus = $pick($pregnantStatusPool, $nameIndex);
    $infant = $makeChild(
        $pick($childFirst, $nameIndex + 1) . ' ' . $last,
        'Female',
        4 + ($nameIndex % 8),
        $surveyDate,
        $wfaPool,
        $hfaPool,
        $wfhPool,
        $nameIndex + 5,
        $birthFromAgeMonths,
        $emptyMemberFlags,
        $pick
    );
    if (($infant['age_months'] ?? 0) <= 5) {
        $infant['lactating_exclusive_breastfeeding'] = 'YES';
    }
    $insertHousehold($con, $barangayId, $barangayName, 'Purok 2', $bnsName, [
        'last' => $last,
        'first' => $first,
        'middle' => 'R',
        'gender' => 'Female',
        'birth_date' => $birthFromAgeMonths(24 * 12, $surveyDate),
        'occupation' => 'Vendor',
        'is_pregnant' => 'YES',
        'is_lactating' => 'NO',
        'pregnancy_months' => $pregMonths,
        'pregnant_nutrition_status' => $pregStatus,
        'is_4ps' => 'YES',
    ], [$infant], $surveyDate);
    $createdPregnant++;

    // Household 3: Male head + pregnant spouse member + preschool + school-age child
    $last = $pick($lastNames, $nameIndex++);
    $first = $pick($adultFirstM, $nameIndex);
    $spouseFirst = $pick($adultFirstF, $nameIndex + 4);
    $spouseFlags = $emptyMemberFlags();
    $spouseFlags['is_pregnant'] = 'YES';
    $spouseFlags['pregnancy_months'] = 5 + ($nameIndex % 4);
    $spouseFlags['pregnant_nutrition_status'] = $pick($pregnantStatusPool, $nameIndex + 1);
    $spouse = array_merge($spouseFlags, [
        'member_name' => $spouseFirst . ' ' . $last,
        'relationship' => 'Spouse',
        'gender' => 'Female',
        'birth_date' => $birthFromAgeMonths(26 * 12, $surveyDate),
        'weight_kg' => null,
        'height_cm' => null,
        'age_months' => 26 * 12,
        'weight_for_age' => '',
        'height_for_age' => '',
        'weight_for_height' => '',
    ]);
    $preschool = $makeChild(
        $pick($childFirst, $nameIndex + 2) . ' ' . $last,
        'Male',
        12 + ($nameIndex % 40),
        $surveyDate,
        $wfaPool,
        $hfaPool,
        $wfhPool,
        $nameIndex + 7,
        $birthFromAgeMonths,
        $emptyMemberFlags,
        $pick
    );
    $school = $makeChild(
        $pick($childFirst, $nameIndex + 6) . ' ' . $last,
        'Female',
        72 + ($nameIndex % 48),
        $surveyDate,
        $wfaPool,
        $hfaPool,
        $wfhPool,
        $nameIndex + 9,
        $birthFromAgeMonths,
        $emptyMemberFlags,
        $pick
    );
    $insertHousehold($con, $barangayId, $barangayName, 'Purok 3', $bnsName, [
        'last' => $last,
        'first' => $first,
        'middle' => 'A',
        'gender' => 'Male',
        'birth_date' => $birthFromAgeMonths(32 * 12, $surveyDate),
        'occupation' => 'Farmer',
        'is_pregnant' => 'NO',
        'is_lactating' => 'NO',
        'is_4ps' => 'NO',
    ], [$spouse, $preschool, $school], $surveyDate);
    $createdPregnant++;

    // Household 4: Lactating head + wasted/stunted preschooler
    $last = $pick($lastNames, $nameIndex++);
    $first = $pick($adultFirstF, $nameIndex + 1);
    $toddler = $makeChild(
        $pick($childFirst, $nameIndex + 8) . ' ' . $last,
        'Male',
        24 + ($nameIndex % 24),
        $surveyDate,
        $wfaPool,
        $hfaPool,
        $wfhPool,
        $nameIndex + 11,
        $birthFromAgeMonths,
        $emptyMemberFlags,
        $pick
    );
    $toddler['weight_for_age'] = 'UW';
    $toddler['height_for_age'] = 'Stunted';
    $toddler['weight_for_height'] = 'Wasted';
    $insertHousehold($con, $barangayId, $barangayName, 'Purok 4', $bnsName, [
        'last' => $last,
        'first' => $first,
        'middle' => 'S',
        'gender' => 'Female',
        'birth_date' => $birthFromAgeMonths(29 * 12, $surveyDate),
        'occupation' => 'Housewife',
        'is_pregnant' => 'NO',
        'is_lactating' => 'YES',
        'is_4ps' => 'YES',
    ], [$toddler], $surveyDate);

    echo "Seeded {$barangayName}: 4 households (children + pregnant/lactating).\n";
}

echo "\nDone.\n";
echo "Household surveys created: {$createdSurveys}\n";
echo "Child members created: {$createdChildren}\n";
echo "Pregnant households/members created: {$createdPregnant}\n";
