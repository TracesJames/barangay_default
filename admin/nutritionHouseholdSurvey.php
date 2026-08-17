<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$sessionUserId = (string) $user_id;
$editSurveyId = trim((string) ($_GET['edit'] ?? ''));
$editingSurvey = null;
$editingMembers = [];

if ($editSurveyId !== '') {
    if (!nutrition_user_can_edit_household_surveys($con, $sessionUserId)) {
        header('Location: nutritionBarangaySurvey.php');
        exit;
    }
    $editingSurvey = nutrition_load_household_survey_by_id($con, $editSurveyId, (string) $barangay_id);
    if ($editingSurvey === null) {
        header('Location: nutritionBarangaySurvey.php');
        exit;
    }
    $editingMembers = nutrition_list_household_family_members($con, $editSurveyId);
} elseif (!nutrition_user_can_add_household_surveys($con, $sessionUserId)) {
    header('Location: nutritionBarangaySurvey.php');
    exit;
}

$activePage = 'household_survey';
$nutritionPageTitle = $editingSurvey ? 'Edit Household Nutrition Survey' : 'Household Nutrition Survey';
$surveys = nutrition_list_household_surveys($con, (string) $barangay_id);
$report = nutrition_household_consolidated_report($con, (string) $barangay_id);
$summary = $report['summary'] ?? [];
$relationshipOptions = nutrition_relationship_options();
$defaultPurokInput = '1';
$defaultHouseholdId = nutrition_generate_household_reference(
    $con,
    (string) $barangay_id,
    $defaultPurokInput,
    (string) $barangay
);
$psgcCode = nutrition_barangay_psgc_code($con, (string) $barangay_id, (string) $barangay);

if ($editingSurvey !== null) {
    $defaultPurokInput = nutrition_purok_input_from_label((string) ($editingSurvey['purok_label'] ?? 'Purok 1'));
    if ($defaultPurokInput === '') {
        $defaultPurokInput = '1';
    }
    $defaultHouseholdId = (string) ($editingSurvey['house_hold_id'] ?? $defaultHouseholdId);
    $nutritionSurveyCardTitle = 'Edit Household Survey';
    $nutritionSurveySaveLabel = 'Update Household Survey';
}

$nutritionIncludeScriptsCsrf = true;
$nutritionExtraCss = [
    '../assets/plugins/select2/css/select2.min.css',
    '../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css',
    '../assets/plugins/jquery-ui/jquery-ui.min.css',
    '../assets/plugins/sweetalert2/css/sweetalert2.min.css',
];
$nutritionExtraJs = [
    '../assets/plugins/select2/js/select2.full.min.js',
    '../assets/plugins/jquery-ui/jquery-ui.min.js',
    '../assets/plugins/sweetalert2/js/sweetalert2.all.min.js',
    '../assets/js/barangay-ui.js',
];

$nutritionEditPayload = null;
if ($editingSurvey !== null) {
    $fmt = static function ($ymd) {
        $ymd = trim((string) $ymd);
        if ($ymd === '' || $ymd === '0000-00-00') {
            return '';
        }
        if (function_exists('nutrition_format_date_mdy')) {
            return (string) nutrition_format_date_mdy($ymd);
        }
        $ts = strtotime($ymd);

        return $ts ? date('m/d/Y', $ts) : '';
    };

    $memberPayload = [];
    foreach ($editingMembers as $member) {
        $memberPayload[] = [
            'member_name' => (string) ($member['member_name'] ?? ''),
            'relationship' => (string) ($member['relationship'] ?? ''),
            'gender' => (string) ($member['gender'] ?? ''),
            'birth_date' => $fmt($member['birth_date'] ?? ''),
            'weight_kg' => $member['weight_kg'] ?? '',
            'height_cm' => $member['height_cm'] ?? '',
            'date_measured' => $fmt($member['date_measured'] ?? ''),
            'age_months' => $member['age_months'] ?? '',
            'weight_for_age' => (string) ($member['weight_for_age'] ?? ''),
            'height_for_age' => (string) ($member['height_for_age'] ?? ''),
            'weight_for_height' => (string) ($member['weight_for_height'] ?? ''),
            'is_pregnant' => strtoupper((string) ($member['is_pregnant'] ?? 'NO')) === 'YES',
            'is_lactating' => strtoupper((string) ($member['is_lactating'] ?? 'NO')) === 'YES',
            'pregnancy_months' => $member['pregnancy_months'] ?? '',
            'pregnant_nutrition_status' => (string) ($member['pregnant_nutrition_status'] ?? ''),
            'planned_exclusive_breastfeeding' => strtoupper((string) ($member['planned_exclusive_breastfeeding'] ?? 'NO')) === 'YES',
            'planned_mixed_feeding' => strtoupper((string) ($member['planned_mixed_feeding'] ?? 'NO')) === 'YES',
            'planned_bottle_feeding' => strtoupper((string) ($member['planned_bottle_feeding'] ?? 'NO')) === 'YES',
            'planned_other_feeding' => strtoupper((string) ($member['planned_other_feeding'] ?? 'NO')) === 'YES',
            'planned_other_specify' => (string) ($member['planned_other_specify'] ?? ''),
            'lactating_exclusive_breastfeeding' => strtoupper((string) ($member['lactating_exclusive_breastfeeding'] ?? 'NO')) === 'YES',
            'lactating_mixed_feeding' => strtoupper((string) ($member['lactating_mixed_feeding'] ?? 'NO')) === 'YES',
            'lactating_bottle_feeding' => strtoupper((string) ($member['lactating_bottle_feeding'] ?? 'NO')) === 'YES',
            'lactating_other_feeding' => strtoupper((string) ($member['lactating_other_feeding'] ?? 'NO')) === 'YES',
            'lactating_other_specify' => (string) ($member['lactating_other_specify'] ?? ''),
        ];
    }

    $nutritionEditPayload = [
        'survey_id' => (string) ($editingSurvey['survey_id'] ?? ''),
        'residence_id' => (string) ($editingSurvey['residence_id'] ?? ''),
        'house_hold_id' => (string) ($editingSurvey['house_hold_id'] ?? ''),
        'purok_number' => $defaultPurokInput,
        'purok_input' => $defaultPurokInput,
        'survey_date' => $fmt($editingSurvey['survey_date'] ?? ''),
        'bns_name' => (string) ($editingSurvey['bns_name'] ?? ''),
        'head_last_name' => (string) ($editingSurvey['head_last_name'] ?? ''),
        'head_first_name' => (string) ($editingSurvey['head_first_name'] ?? ''),
        'head_middle_name' => (string) ($editingSurvey['head_middle_name'] ?? ''),
        'head_suffix' => (string) ($editingSurvey['head_suffix'] ?? ''),
        'birth_date' => $fmt($editingSurvey['birth_date'] ?? ''),
        'gender' => (string) ($editingSurvey['gender'] ?? ''),
        'occupation' => (string) ($editingSurvey['occupation'] ?? ''),
        'is_4ps' => strtoupper((string) ($editingSurvey['is_4ps'] ?? 'NO')) === 'YES',
        'is_pwd' => strtoupper((string) ($editingSurvey['is_pwd'] ?? 'NO')) === 'YES',
        'is_ip' => strtoupper((string) ($editingSurvey['is_ip'] ?? 'NO')) === 'YES',
        'is_solo_parent' => strtoupper((string) ($editingSurvey['is_solo_parent'] ?? 'NO')) === 'YES',
        'is_na_member' => strtoupper((string) ($editingSurvey['is_na_member'] ?? 'NO')) === 'YES',
        'head_is_pregnant' => strtoupper((string) ($editingSurvey['head_is_pregnant'] ?? 'NO')) === 'YES',
        'head_is_lactating' => strtoupper((string) ($editingSurvey['head_is_lactating'] ?? 'NO')) === 'YES',
        'head_pregnancy_months' => $editingSurvey['head_pregnancy_months'] ?? '',
        'head_pregnant_nutrition_status' => (string) ($editingSurvey['head_pregnant_nutrition_status'] ?? ''),
        'head_planned_exclusive_breastfeeding' => strtoupper((string) ($editingSurvey['head_planned_exclusive_breastfeeding'] ?? 'NO')) === 'YES',
        'head_planned_mixed_feeding' => strtoupper((string) ($editingSurvey['head_planned_mixed_feeding'] ?? 'NO')) === 'YES',
        'head_planned_bottle_feeding' => strtoupper((string) ($editingSurvey['head_planned_bottle_feeding'] ?? 'NO')) === 'YES',
        'head_planned_other_feeding' => strtoupper((string) ($editingSurvey['head_planned_other_feeding'] ?? 'NO')) === 'YES',
        'head_planned_other_specify' => (string) ($editingSurvey['head_planned_other_specify'] ?? ''),
        'head_lactating_exclusive_breastfeeding' => strtoupper((string) ($editingSurvey['head_lactating_exclusive_breastfeeding'] ?? 'NO')) === 'YES',
        'head_lactating_mixed_feeding' => strtoupper((string) ($editingSurvey['head_lactating_mixed_feeding'] ?? 'NO')) === 'YES',
        'head_lactating_bottle_feeding' => strtoupper((string) ($editingSurvey['head_lactating_bottle_feeding'] ?? 'NO')) === 'YES',
        'head_lactating_other_feeding' => strtoupper((string) ($editingSurvey['head_lactating_other_feeding'] ?? 'NO')) === 'YES',
        'head_lactating_other_specify' => (string) ($editingSurvey['head_lactating_other_specify'] ?? ''),
        'house_ownership' => (string) ($editingSurvey['house_ownership'] ?? ''),
        'house_ownership_other' => (string) ($editingSurvey['house_ownership_other'] ?? ''),
        'toilet_type' => (string) ($editingSurvey['toilet_type'] ?? ''),
        'garbage_disposal' => (string) ($editingSurvey['garbage_disposal'] ?? ''),
        'garbage_uncollected_type' => (string) ($editingSurvey['garbage_uncollected_type'] ?? ''),
        'water_source' => (string) ($editingSurvey['water_source'] ?? ''),
        'dwelling_type' => (string) ($editingSurvey['dwelling_type'] ?? ''),
        'food_production' => (string) ($editingSurvey['food_production'] ?? ''),
        'uses_iodized_salt' => strtoupper((string) ($editingSurvey['uses_iodized_salt'] ?? 'NO')) === 'YES',
        'uses_sangkap_pinoy' => strtoupper((string) ($editingSurvey['uses_sangkap_pinoy'] ?? 'NO')) === 'YES',
        'has_carenderia' => strtoupper((string) ($editingSurvey['has_carenderia'] ?? 'NO')) === 'YES',
        'has_sari_sari_store' => strtoupper((string) ($editingSurvey['has_sari_sari_store'] ?? 'NO')) === 'YES',
        'practices_family_planning' => strtoupper((string) ($editingSurvey['practices_family_planning'] ?? 'NO')) === 'YES',
        'family_planning_methods' => (string) ($editingSurvey['family_planning_methods'] ?? ''),
        'complementary_meals' => (string) ($editingSurvey['complementary_meals'] ?? ''),
        'complementary_meals_other' => (string) ($editingSurvey['complementary_meals_other'] ?? ''),
        'complementary_snacks' => (string) ($editingSurvey['complementary_snacks'] ?? ''),
        'complementary_snacks_other' => (string) ($editingSurvey['complementary_snacks_other'] ?? ''),
        'child_physical_activity' => (string) ($editingSurvey['child_physical_activity'] ?? ''),
        'child_physical_activity_other' => (string) ($editingSurvey['child_physical_activity_other'] ?? ''),
        'remarks' => (string) ($editingSurvey['remarks'] ?? ''),
        'family_members' => $memberPayload,
    ];
}

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
require __DIR__ . '/../includes/partials/nutrition_household_survey_content.php';
?>
<script>
window.nutritionRelationshipOptions = <?= json_encode($relationshipOptions, JSON_UNESCAPED_UNICODE) ?>;
window.nutritionPregnantStatusOptions = <?= json_encode(nutrition_prf_pregnant_status_options(), JSON_UNESCAPED_UNICODE) ?>;
window.nutritionSurveyEditPayload = <?= json_encode($nutritionEditPayload, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php
require __DIR__ . '/../includes/partials/nutrition_household_survey_scripts.php';
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
