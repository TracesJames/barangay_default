<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

$auth = nutrition_api_authenticate($con);
$body = nutrition_api_json_body();
$override = trim((string) ($_GET['barangay_id'] ?? $body['barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $override !== '' ? $override : null);

$surveyId = trim((string) ($_GET['id'] ?? $body['id'] ?? ''));
$filters = [];
if (barangay_user_is_barangay_nutrition_scholar($con, $auth['user_id'])) {
    $assigned = staff_assigned_purok_labels($con, $auth['user_id'], $ctx['barangay_id']);
    if ($assigned !== []) {
        $filters['purok_in'] = $assigned;
    }
}

if ($surveyId !== '') {
    $survey = nutrition_load_household_survey_by_id($con, $surveyId, $ctx['barangay_id']);
    if ($survey === null) {
        nutrition_api_error('Household survey not found.', 404);
    }
    if (isset($filters['purok_in']) && is_array($filters['purok_in'])) {
        $label = trim((string) ($survey['purok_label'] ?? ''));
        if ($label !== '' && !in_array($label, $filters['purok_in'], true)) {
            nutrition_api_error('Household survey not found.', 404);
        }
    }
    $members = nutrition_list_household_family_members($con, $surveyId);
    $full = strtolower(trim((string) ($_GET['full'] ?? $body['full'] ?? '1'))) !== '0';
    $payload = [
        'ok' => true,
        'barangay_id' => $ctx['barangay_id'],
        'barangay_name' => $ctx['barangay_name'],
        'household' => nutrition_api_household_summary($survey),
        'members' => array_map('nutrition_api_member_summary', $members),
        'can_edit' => nutrition_user_can_edit_household_surveys($con, $auth['user_id']),
    ];
    if ($full) {
        $payload['survey'] = nutrition_build_household_survey_edit_payload($survey, $members, false);
    }
    nutrition_api_send($payload);
}

$limit = (int) ($_GET['limit'] ?? $body['limit'] ?? 100);
$limit = max(1, min(500, $limit));
$rows = nutrition_list_household_surveys($con, $ctx['barangay_id'], $limit, $filters);
$list = [];
foreach ($rows as $row) {
    $list[] = nutrition_api_household_summary($row);
}

nutrition_api_send([
    'ok' => true,
    'barangay_id' => $ctx['barangay_id'],
    'barangay_name' => $ctx['barangay_name'],
    'count' => count($list),
    'households' => $list,
]);

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function nutrition_api_household_summary(array $row): array
{
    return [
        'survey_id' => (string) ($row['survey_id'] ?? ''),
        'house_hold_id' => (string) ($row['house_hold_id'] ?? ''),
        'purok_label' => (string) ($row['purok_label'] ?? ''),
        'household_head' => (string) ($row['household_head'] ?? ''),
        'head_last_name' => (string) ($row['head_last_name'] ?? ''),
        'head_first_name' => (string) ($row['head_first_name'] ?? ''),
        'head_middle_name' => (string) ($row['head_middle_name'] ?? ''),
        'gender' => (string) ($row['gender'] ?? ''),
        'survey_date' => (string) ($row['survey_date'] ?? ''),
        'members_count' => (int) ($row['members_count'] ?? 0),
        'children_count' => (int) ($row['children_count'] ?? 0),
        'has_pregnant' => (string) ($row['has_pregnant'] ?? 'NO'),
        'has_lactating' => (string) ($row['has_lactating'] ?? 'NO'),
        'moved_out' => (string) ($row['moved_out'] ?? 'NO'),
        'toilet_type' => (string) ($row['toilet_type'] ?? ''),
        'water_source' => (string) ($row['water_source'] ?? ''),
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function nutrition_api_member_summary(array $row): array
{
    return [
        'member_id' => (string) ($row['member_id'] ?? ''),
        'member_name' => (string) ($row['member_name'] ?? ''),
        'relationship' => (string) ($row['relationship'] ?? ''),
        'gender' => (string) ($row['gender'] ?? ''),
        'birth_date' => (string) ($row['birth_date'] ?? ''),
        'age_months' => isset($row['age_months']) ? (int) $row['age_months'] : null,
        'weight_kg' => $row['weight_kg'] ?? null,
        'height_cm' => $row['height_cm'] ?? null,
        'weight_for_age' => (string) ($row['weight_for_age'] ?? ''),
        'height_for_age' => (string) ($row['height_for_age'] ?? ''),
        'weight_for_height' => (string) ($row['weight_for_height'] ?? ''),
        'is_pregnant' => (string) ($row['is_pregnant'] ?? 'NO'),
        'is_lactating' => (string) ($row['is_lactating'] ?? 'NO'),
    ];
}
