<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/nutrition_household_survey_commit.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    nutrition_api_error('Method not allowed. Use POST.', 405);
}

$auth = nutrition_api_authenticate($con);
$body = nutrition_api_json_body();
$override = trim((string) ($body['barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $override !== '' ? $override : null);

// Accept either flat POST-like JSON or nested "survey" object.
$survey = $body['survey'] ?? $body;
if (!is_array($survey)) {
    nutrition_api_error('Invalid survey payload.', 422);
}

// Normalize date fields: allow YYYY-MM-DD or MM/DD/YYYY.
foreach (['survey_date', 'birth_date'] as $dateKey) {
    if (!empty($survey[$dateKey])) {
        $ymd = nutrition_normalize_date_to_ymd(trim((string) $survey[$dateKey]));
        if ($ymd !== null) {
            $survey[$dateKey] = $ymd;
        }
    }
}

// Purok: accept purok_number or purok_label.
if (empty($survey['purok_number']) && !empty($survey['purok_label'])) {
    $survey['purok_number'] = nutrition_purok_input_from_label((string) $survey['purok_label'])
        ?: (string) $survey['purok_label'];
}

// Edit key aliases.
if (empty($survey['existing_survey_id']) && !empty($survey['survey_id'])) {
    $survey['existing_survey_id'] = (string) $survey['survey_id'];
}

// YES/NO flags from bools.
foreach ([
    'is_4ps', 'is_pwd', 'is_ip', 'is_solo_parent', 'is_na_member',
    'uses_iodized_salt', 'uses_sangkap_pinoy', 'has_carenderia', 'has_sari_sari_store',
    'practices_family_planning', 'head_is_pregnant', 'head_is_lactating',
    'head_planned_exclusive_breastfeeding', 'head_planned_mixed_feeding',
    'head_planned_bottle_feeding', 'head_planned_other_feeding',
    'head_lactating_exclusive_breastfeeding', 'head_lactating_mixed_feeding',
    'head_lactating_bottle_feeding', 'head_lactating_other_feeding',
] as $flag) {
    if (array_key_exists($flag, $survey) && is_bool($survey[$flag])) {
        $survey[$flag] = $survey[$flag] ? 'YES' : 'NO';
    }
}

// Food production activities[] → POST shape for commit helper.
if (isset($survey['food_production_activities']) && is_array($survey['food_production_activities'])) {
    $_POST['food_production_activities'] = array_values(array_map('strval', $survey['food_production_activities']));
}

// Family planning methods list.
if (isset($survey['family_planning_methods_list']) && is_array($survey['family_planning_methods_list'])) {
    $_POST['family_planning_methods'] = array_values(array_map('strval', $survey['family_planning_methods_list']));
    if (!empty($survey['family_planning_methods_other'])) {
        $_POST['family_planning_methods_other'] = (string) $survey['family_planning_methods_other'];
    }
} elseif (isset($survey['family_planning_methods']) && is_array($survey['family_planning_methods'])) {
    $_POST['family_planning_methods'] = array_values(array_map('strval', $survey['family_planning_methods']));
}

$memberBoolFlags = [
    'is_pregnant', 'is_lactating',
    'planned_exclusive_breastfeeding', 'planned_mixed_feeding',
    'planned_bottle_feeding', 'planned_other_feeding',
    'lactating_exclusive_breastfeeding', 'lactating_mixed_feeding',
    'lactating_bottle_feeding', 'lactating_other_feeding',
];

if (isset($survey['family_members']) && is_array($survey['family_members'])) {
    foreach ($survey['family_members'] as $i => $member) {
        if (!is_array($member)) {
            continue;
        }
        $memberName = trim((string) ($member['member_name'] ?? ''));
        if ($memberName === '') {
            $last = trim((string) ($member['last_name'] ?? ''));
            $first = trim((string) ($member['first_name'] ?? ''));
            $middle = trim((string) ($member['middle_name'] ?? ''));
            if ($last !== '' && $first !== '') {
                $memberName = trim($last . ', ' . $first . ($middle !== '' ? ' ' . $middle : ''));
            } elseif ($first !== '' || $last !== '') {
                $memberName = trim($last . ' ' . $first . ' ' . $middle);
            }
            if ($memberName !== '') {
                $survey['family_members'][$i]['member_name'] = $memberName;
            }
        }
        foreach (['birth_date', 'date_measured', 'prev_date_measured'] as $dateKey) {
            if (!empty($member[$dateKey])) {
                $ymd = nutrition_normalize_date_to_ymd(trim((string) $member[$dateKey]));
                if ($ymd !== null) {
                    $survey['family_members'][$i][$dateKey] = $ymd;
                }
            }
        }
        foreach ($memberBoolFlags as $flag) {
            if (array_key_exists($flag, $member) && is_bool($member[$flag])) {
                $survey['family_members'][$i][$flag] = $member[$flag] ? 'YES' : 'NO';
            }
        }
        $rel = trim((string) ($member['relationship'] ?? ''));
        $relOther = trim((string) ($member['relationship_other'] ?? ''));
        if ($rel !== '' || $relOther !== '') {
            $survey['family_members'][$i]['relationship'] = nutrition_compose_member_relationship($rel, $relOther);
        }
    }
}

if (isset($survey['food_production_activities']) && is_array($survey['food_production_activities'])) {
    // commit uses nutrition_prf_food_production_from_post() which reads $_POST.
    // nutrition_commit_household_survey assigns $_POST = $post.
}

if (isset($survey['family_planning_methods_list']) && is_array($survey['family_planning_methods_list'])) {
    $survey['family_planning_methods'] = array_values(array_map('strval', $survey['family_planning_methods_list']));
}

$result = nutrition_commit_household_survey(
    $con,
    $auth['user_id'],
    $ctx['barangay_id'],
    $ctx['barangay_name'],
    $survey
);

if (empty($result['ok'])) {
    nutrition_api_error(
        (string) ($result['error'] ?? 'Could not save household survey.'),
        (int) ($result['status'] ?? 400)
    );
}

nutrition_api_send([
    'ok' => true,
    'survey_id' => (string) ($result['survey_id'] ?? ''),
    'house_hold_id' => (string) ($result['house_hold_id'] ?? ''),
    'residence_id' => $result['residence_id'] ?? null,
    'auto_linked' => !empty($result['auto_linked']),
    'message' => (string) ($result['message'] ?? 'Household survey saved.'),
]);
