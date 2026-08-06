<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';

header('Content-Type: application/json; charset=utf-8');
nutrition_ensure_module_tables($con);

$barangayId = (string) ($barangay_id ?? '');
if ($barangayId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No active barangay selected.']);
    exit;
}

$headLastName = trim((string) ($_POST['head_last_name'] ?? ''));
$headFirstName = trim((string) ($_POST['head_first_name'] ?? ''));
$headMiddleName = trim((string) ($_POST['head_middle_name'] ?? ''));
$headSuffix = trim((string) ($_POST['head_suffix'] ?? ''));
$surveyDate = trim((string) ($_POST['survey_date'] ?? ''));
$purokNumber = trim((string) ($_POST['purok_number'] ?? ''));
$purokLabel = nutrition_purok_label_from_number($purokNumber);
$gender = trim((string) ($_POST['gender'] ?? ''));
$occupation = trim((string) ($_POST['occupation'] ?? ''));
$bnsName = trim((string) ($_POST['bns_name'] ?? ''));

if ($headLastName === '' || $headFirstName === '' || $surveyDate === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Household head last name, first name, and survey date are required.']);
    exit;
}

if ($purokLabel === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid purok number.']);
    exit;
}

if (!in_array($gender, ['Male', 'Female'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please select a valid gender.']);
    exit;
}

$householdHead = nutrition_format_household_head_name($headLastName, $headFirstName, $headMiddleName, $headSuffix);
$birthDate = trim((string) ($_POST['birth_date'] ?? ''));
$birthDateValue = $birthDate !== '' ? $birthDate : null;

$familyMembers = nutrition_parse_family_members_from_post($_POST, $surveyDate);
$nameValidationError = nutrition_validate_household_survey_names(
    $con,
    $headLastName,
    $headFirstName,
    $headMiddleName,
    $headSuffix,
    $familyMembers
);
if ($nameValidationError !== null) {
    http_response_code(409);
    echo json_encode(['error' => $nameValidationError]);
    exit;
}

$surveyId = (string) hexdec(uniqid());
$houseHoldId = nutrition_generate_household_reference($con, $barangayId, $purokLabel, (string) ($barangay ?? ''));

$headIsPregnant = ($gender === 'Female') ? nutrition_yes_no_from_post('head_is_pregnant') : 'NO';
$headIsLactating = ($gender === 'Female') ? nutrition_yes_no_from_post('head_is_lactating') : 'NO';
$headPregnancyMonths = null;
$headPregnantNutritionStatus = '';
$headPlannedExclusive = 'NO';
$headPlannedMixed = 'NO';
$headPlannedBottle = 'NO';
$headPlannedOther = 'NO';
$headPlannedOtherSpecify = '';
$headLactatingExclusive = 'NO';
$headLactatingMixed = 'NO';
$headLactatingBottle = 'NO';
$headLactatingOther = 'NO';
$headLactatingOtherSpecify = '';

if ($headIsPregnant === 'YES') {
    $monthsRaw = trim((string) ($_POST['head_pregnancy_months'] ?? ''));
    if ($monthsRaw !== '' && ctype_digit($monthsRaw)) {
        $monthsVal = (int) $monthsRaw;
        if ($monthsVal >= 1 && $monthsVal <= 9) {
            $headPregnancyMonths = $monthsVal;
        }
    }
    $headPregnantNutritionStatus = nutrition_prf_pick_option(
        (string) ($_POST['head_pregnant_nutrition_status'] ?? ''),
        nutrition_prf_pregnant_status_options()
    );
    $headPlannedExclusive = nutrition_yes_no_from_post('head_planned_exclusive_breastfeeding');
    $headPlannedMixed = nutrition_yes_no_from_post('head_planned_mixed_feeding');
    $headPlannedBottle = nutrition_yes_no_from_post('head_planned_bottle_feeding');
    $headPlannedOther = nutrition_yes_no_from_post('head_planned_other_feeding');
    $headPlannedOtherSpecify = $headPlannedOther === 'YES'
        ? trim((string) ($_POST['head_planned_other_specify'] ?? ''))
        : '';
}

if ($headIsLactating === 'YES') {
    $headLactatingExclusive = nutrition_yes_no_from_post('head_lactating_exclusive_breastfeeding');
    $headLactatingMixed = nutrition_yes_no_from_post('head_lactating_mixed_feeding');
    $headLactatingBottle = nutrition_yes_no_from_post('head_lactating_bottle_feeding');
    $headLactatingOther = nutrition_yes_no_from_post('head_lactating_other_feeding');
    $headLactatingOtherSpecify = $headLactatingOther === 'YES'
        ? trim((string) ($_POST['head_lactating_other_specify'] ?? ''))
        : '';
}

$hasPregnant = $headIsPregnant;
$hasLactating = $headIsLactating;
$childrenCount = 0;
foreach ($familyMembers as $familyMember) {
    if (($familyMember['is_pregnant'] ?? 'NO') === 'YES') {
        $hasPregnant = 'YES';
    }
    if (($familyMember['is_lactating'] ?? 'NO') === 'YES') {
        $hasLactating = 'YES';
    }
    $ageMonths = $familyMember['age_months'] ?? null;
    if ($ageMonths !== null && $ageMonths <= 216) {
        $childrenCount++;
    }
}
$membersCount = $familyMembers !== [] ? count($familyMembers) + 1 : 1;
$foodSecurity = 'secure';
$supplementaryFeeding = 'NO';
$remarks = trim((string) ($_POST['remarks'] ?? ''));

$is4ps = nutrition_yes_no_from_post('is_4ps');
$isPwd = nutrition_yes_no_from_post('is_pwd');
$isIp = nutrition_yes_no_from_post('is_ip');
$isSoloParent = nutrition_yes_no_from_post('is_solo_parent');
$isNaMember = nutrition_yes_no_from_post('is_na_member');
if ($isNaMember === 'YES') {
    $is4ps = 'NO';
    $isIp = 'NO';
}

$houseOwnership = nutrition_prf_pick_option((string) ($_POST['house_ownership'] ?? ''), nutrition_prf_house_ownership_options());
$houseOwnershipOther = $houseOwnership === 'Others' ? trim((string) ($_POST['house_ownership_other'] ?? '')) : '';
$toiletType = nutrition_prf_pick_option((string) ($_POST['toilet_type'] ?? ''), nutrition_prf_toilet_options());
$garbageDisposal = nutrition_prf_pick_option((string) ($_POST['garbage_disposal'] ?? ''), nutrition_prf_garbage_options());
$garbageUncollected = $garbageDisposal === 'Uncollected'
    ? nutrition_prf_pick_option((string) ($_POST['garbage_uncollected_type'] ?? ''), nutrition_prf_garbage_uncollected_options())
    : '';
$waterSource = nutrition_prf_pick_option((string) ($_POST['water_source'] ?? ''), nutrition_prf_water_source_options());
$dwellingType = nutrition_prf_pick_option((string) ($_POST['dwelling_type'] ?? ''), nutrition_prf_dwelling_options());
$foodProduction = nutrition_prf_food_production_from_post();
$usesIodizedSalt = nutrition_yes_no_from_post('uses_iodized_salt');
$usesSangkapPinoy = nutrition_yes_no_from_post('uses_sangkap_pinoy');
$hasCarenderia = nutrition_yes_no_from_post('has_carenderia');
$hasSariSariStore = nutrition_yes_no_from_post('has_sari_sari_store');
$practicesFamilyPlanning = nutrition_yes_no_from_post('practices_family_planning');
$familyPlanningMethods = $practicesFamilyPlanning === 'YES'
    ? nutrition_prf_methods_from_post('family_planning_methods', nutrition_prf_family_planning_method_options())
    : '';
$complementaryMeals = nutrition_prf_pick_option((string) ($_POST['complementary_meals'] ?? ''), nutrition_prf_complementary_meal_options());
$complementaryMealsOther = $complementaryMeals === 'Others' ? trim((string) ($_POST['complementary_meals_other'] ?? '')) : '';
$complementarySnacks = nutrition_prf_pick_option((string) ($_POST['complementary_snacks'] ?? ''), nutrition_prf_complementary_snack_options());
$complementarySnacksOther = $complementarySnacks === 'Others' ? trim((string) ($_POST['complementary_snacks_other'] ?? '')) : '';
$childPhysicalActivity = nutrition_prf_pick_option((string) ($_POST['child_physical_activity'] ?? ''), nutrition_prf_physical_activity_options());
$childPhysicalActivityOther = $childPhysicalActivity === 'Others' ? trim((string) ($_POST['child_physical_activity_other'] ?? '')) : '';

$sanitation = $toiletType;
$surveyedBy = (string) ($_SESSION['user_id'] ?? '');

$linkedResidenceId = trim((string) ($_POST['residence_id'] ?? ''));
if ($linkedResidenceId !== '') {
    $linkedResident = nutrition_load_resident($con, $linkedResidenceId, $barangayId);
    if ($linkedResident === null) {
        $linkedResidenceId = '';
    }
}

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
      dwelling_type, food_production, uses_iodized_salt, uses_sangkap_pinoy, has_carenderia, has_sari_sari_store,
      practices_family_planning, family_planning_methods,
      complementary_meals, complementary_meals_other, complementary_snacks, complementary_snacks_other,
      child_physical_activity, child_physical_activity_other, remarks, surveyed_by)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $con->error]);
    exit;
}

$stmt->bind_param(
    str_repeat('s', 20) . 'ii' . str_repeat('s', 5) . 'i' . str_repeat('s', 35),
    $surveyId,
    $barangayId,
    $linkedResidenceId,
    $houseHoldId,
    $purokLabel,
    $headLastName,
    $headFirstName,
    $headMiddleName,
    $headSuffix,
    $householdHead,
    $birthDateValue,
    $gender,
    $occupation,
    $bnsName,
    $is4ps,
    $isPwd,
    $isIp,
    $isSoloParent,
    $isNaMember,
    $surveyDate,
    $membersCount,
    $childrenCount,
    $foodSecurity,
    $hasPregnant,
    $hasLactating,
    $headIsPregnant,
    $headIsLactating,
    $headPregnancyMonths,
    $headPregnantNutritionStatus,
    $headPlannedExclusive,
    $headPlannedMixed,
    $headPlannedBottle,
    $headPlannedOther,
    $headPlannedOtherSpecify,
    $headLactatingExclusive,
    $headLactatingMixed,
    $headLactatingBottle,
    $headLactatingOther,
    $headLactatingOtherSpecify,
    $supplementaryFeeding,
    $waterSource,
    $sanitation,
    $houseOwnership,
    $houseOwnershipOther,
    $toiletType,
    $garbageDisposal,
    $garbageUncollected,
    $dwellingType,
    $foodProduction,
    $usesIodizedSalt,
    $usesSangkapPinoy,
    $hasCarenderia,
    $hasSariSariStore,
    $practicesFamilyPlanning,
    $familyPlanningMethods,
    $complementaryMeals,
    $complementaryMealsOther,
    $complementarySnacks,
    $complementarySnacksOther,
    $childPhysicalActivity,
    $childPhysicalActivityOther,
    $remarks,
    $surveyedBy
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save survey: ' . $stmt->error]);
    exit;
}
$stmt->close();

nutrition_save_household_family_members($con, $surveyId, $barangayId, $familyMembers);

echo json_encode([
    'ok' => true,
    'survey_id' => $surveyId,
    'house_hold_id' => $houseHoldId,
    'message' => 'Household survey saved.',
]);
