<?php

/**
 * Barangay residence registry ↔ Nutrition household survey integration.
 */
require_once __DIR__ . '/nutrition_context.php';
require_once __DIR__ . '/residence_family.php';
require_once __DIR__ . '/audit_log.php';

if (!function_exists('nutrition_parse_member_name')) {
    /**
     * Best-effort split of a single member_name field into name parts.
     *
     * @return array{first:string,middle:string,last:string,suffix:string}
     */
    function nutrition_parse_member_name(string $memberName): array
    {
        $memberName = trim(preg_replace('/\s+/', ' ', $memberName) ?? '');
        if ($memberName === '') {
            return ['first' => '', 'middle' => '', 'last' => '', 'suffix' => ''];
        }

        $parts = explode(' ', $memberName);
        $suffix = '';
        $lastPart = $parts[count($parts) - 1];
        if (preg_match('/^(Jr\.?|Sr\.?|III|IV|II)$/i', $lastPart)) {
            $suffix = $lastPart;
            array_pop($parts);
        }

        if ($parts === []) {
            return ['first' => $memberName, 'middle' => '', 'last' => '', 'suffix' => $suffix];
        }

        $first = array_shift($parts);
        if ($parts === []) {
            return ['first' => $first, 'middle' => '', 'last' => '', 'suffix' => $suffix];
        }

        $last = array_pop($parts);

        return [
            'first' => $first,
            'middle' => implode(' ', $parts),
            'last' => $last,
            'suffix' => $suffix,
        ];
    }
}

if (!function_exists('nutrition_survey_member_match_key')) {
    function nutrition_survey_member_match_key(string $memberName, ?string $birthDate = null): string
    {
        $norm = strtolower(trim(preg_replace('/\s+/', ' ', $memberName) ?? ''));
        $birth = $birthDate !== null ? nutrition_normalize_date_to_ymd($birthDate) : null;
        if ($birth === null && $birthDate !== null && trim($birthDate) !== '') {
            $birth = strtolower(trim($birthDate));
        }

        return $norm . '|' . ($birth ?? '');
    }
}

if (!function_exists('nutrition_resident_match_key')) {
    function nutrition_resident_match_key(
        string $firstName,
        string $middleName,
        string $lastName,
        string $suffix = '',
        ?string $birthDate = null
    ): string {
        $display = nutrition_format_member_display_name($firstName, $middleName, $lastName, $suffix);

        return nutrition_survey_member_match_key($display, $birthDate);
    }
}

if (!function_exists('nutrition_person_match_key')) {
    /**
     * Normalized key for deduplicating people across survey members and residents.
     */
    function nutrition_person_match_key(
        string $firstName,
        string $middleName,
        string $lastName,
        string $suffix = '',
        ?string $birthDate = null
    ): string {
        $norm = static function (string $value): string {
            return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
        };

        $birth = $birthDate !== null ? nutrition_normalize_date_to_ymd($birthDate) : null;
        if ($birth === null && $birthDate !== null && trim($birthDate) !== '') {
            $birth = $norm(trim($birthDate));
        }

        return implode('|', [
            $norm($firstName),
            $norm($middleName),
            $norm($lastName),
            $norm($suffix),
            $birth ?? '',
        ]);
    }
}

if (!function_exists('nutrition_survey_child_person_keys')) {
    /**
     * @return array<string, true>
     */
    function nutrition_survey_child_person_keys(mysqli $con, ?string $barangayId = null): array
    {
        $keys = [];
        if (!barangay_table_exists($con, 'nutrition_household_family_member')
            || !barangay_table_exists($con, 'nutrition_household_survey')) {
            return $keys;
        }

        $sql = 'SELECT m.member_name, m.birth_date, m.age_months
            FROM nutrition_household_family_member m
            INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id';
        if ($barangayId !== null && $barangayId !== '') {
            $sql .= " WHERE m.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
        }

        $result = $con->query($sql);
        if (!$result) {
            return $keys;
        }

        while ($member = $result->fetch_assoc()) {
            $ageMonths = isset($member['age_months']) && $member['age_months'] !== null && $member['age_months'] !== ''
                ? (int) $member['age_months']
                : null;
            if ($ageMonths === null) {
                $ageMonths = nutrition_age_in_months(
                    trim((string) ($member['birth_date'] ?? '')) !== '' ? (string) $member['birth_date'] : null
                );
            }
            if (!nutrition_member_is_child_0_to_19($ageMonths, (string) ($member['birth_date'] ?? ''))) {
                continue;
            }

            $key = nutrition_survey_member_match_key(
                (string) ($member['member_name'] ?? ''),
                (string) ($member['birth_date'] ?? '')
            );
            if ($key !== '|') {
                $keys[$key] = true;
                $parsed = nutrition_parse_member_name((string) ($member['member_name'] ?? ''));
                $keys[nutrition_person_match_key(
                    $parsed['first'],
                    $parsed['middle'],
                    $parsed['last'],
                    $parsed['suffix'],
                    (string) ($member['birth_date'] ?? '')
                )] = true;
            }
        }

        return $keys;
    }
}

if (!function_exists('nutrition_count_residence_children_excluding_survey_duplicates')) {
    function nutrition_count_residence_children_excluding_survey_duplicates(mysqli $con, ?string $barangayId = null): int
    {
        if (!nutrition_table_exists($con) || !barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            return 0;
        }

        $surveyKeys = nutrition_survey_child_person_keys($con, $barangayId);
        $where = nutrition_children_where($con);
        if ($barangayId !== null && $barangayId !== '') {
            $where[] = "rs.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
        }

        $sql = 'SELECT ri.first_name, ri.middle_name, ri.last_name, ri.suffix, ri.birth_date
            FROM residence_information ri
            INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id'
            . barangay_sql_where($where);
        $result = $con->query($sql);
        if (!$result) {
            return 0;
        }

        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $residentKey = nutrition_resident_match_key(
                (string) ($row['first_name'] ?? ''),
                (string) ($row['middle_name'] ?? ''),
                (string) ($row['last_name'] ?? ''),
                (string) ($row['suffix'] ?? ''),
                (string) ($row['birth_date'] ?? '')
            );
            $legacyKey = nutrition_person_match_key(
                (string) ($row['first_name'] ?? ''),
                (string) ($row['middle_name'] ?? ''),
                (string) ($row['last_name'] ?? ''),
                (string) ($row['suffix'] ?? ''),
                (string) ($row['birth_date'] ?? '')
            );
            if (!isset($surveyKeys[$residentKey]) && !isset($surveyKeys[$legacyKey])) {
                $count++;
            }
        }

        return $count;
    }
}

if (!function_exists('nutrition_load_survey_row')) {
    /**
     * @return array<string, mixed>|null
     */
    function nutrition_load_survey_row(mysqli $con, string $surveyId): ?array
    {
        $stmt = $con->prepare(
            'SELECT survey_id, barangay_id, residence_id, head_first_name, head_middle_name, head_last_name,
                    head_suffix, birth_date, house_hold_id
             FROM nutrition_household_survey WHERE survey_id = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $surveyId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }
}

if (!function_exists('nutrition_find_resident_for_survey_head')) {
    /**
     * @param array<string, mixed> $survey
     * @return array{residence_id:string,first_name:string,middle_name:string,last_name:string,suffix:string,birth_date:string}|null
     */
    function nutrition_find_resident_for_survey_head(mysqli $con, array $survey, string $barangayId): ?array
    {
        $first = trim((string) ($survey['head_first_name'] ?? ''));
        $middle = trim((string) ($survey['head_middle_name'] ?? ''));
        $last = trim((string) ($survey['head_last_name'] ?? ''));
        $suffix = trim((string) ($survey['head_suffix'] ?? ''));
        $birthDate = trim((string) ($survey['birth_date'] ?? ''));

        if ($first === '' || $last === '' || $barangayId === '') {
            return null;
        }

        $match = residence_find_duplicate_name($con, $barangayId, $first, $middle, $last, $suffix, null, $birthDate !== '' ? $birthDate : null);
        if ($match !== null) {
            return $match;
        }

        if ($birthDate !== '') {
            return null;
        }

        return residence_find_duplicate_name($con, $barangayId, $first, $middle, $last, $suffix);
    }
}

if (!function_exists('nutrition_validate_survey_residence_link')) {
    /**
     * @return array{ok:bool,error?:string,survey?:array<string,mixed>,barangay_id?:string}
     */
    function nutrition_validate_survey_residence_link(
        mysqli $con,
        string $surveyId,
        string $residenceId,
        string $surveyBarangayId = ''
    ): array {
        $survey = nutrition_load_survey_row($con, $surveyId);
        if (!$survey) {
            return ['ok' => false, 'error' => 'Survey not found.'];
        }

        $surveyBarangayId = $surveyBarangayId !== '' ? $surveyBarangayId : (string) ($survey['barangay_id'] ?? '');
        if ($residenceId === '') {
            return ['ok' => false, 'error' => 'Residence is required.'];
        }

        $resCheck = $con->prepare(
            'SELECT ri.residence_id
             FROM residence_information ri
             INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
             WHERE ri.residence_id = ? AND rs.archive = \'NO\' LIMIT 1'
        );
        if (!$resCheck) {
            return ['ok' => false, 'error' => 'Could not validate resident.'];
        }
        $resCheck->bind_param('s', $residenceId);
        $resCheck->execute();
        $residentOk = (bool) $resCheck->get_result()->fetch_assoc();
        $resCheck->close();

        if (!$residentOk) {
            return ['ok' => false, 'error' => 'Resident not found or archived.'];
        }

        if (barangay_column_exists($con, 'residence_status', 'barangay_id') && $surveyBarangayId !== '') {
            $brgyCheck = $con->prepare(
                'SELECT residence_id FROM residence_status WHERE residence_id = ? AND barangay_id = ? LIMIT 1'
            );
            if ($brgyCheck) {
                $brgyCheck->bind_param('ss', $residenceId, $surveyBarangayId);
                $brgyCheck->execute();
                $sameBrgy = (bool) $brgyCheck->get_result()->fetch_assoc();
                $brgyCheck->close();
                if (!$sameBrgy) {
                    return ['ok' => false, 'error' => 'Resident must belong to the same barangay as the survey.'];
                }
            }
        }

        return ['ok' => true, 'survey' => $survey, 'barangay_id' => $surveyBarangayId];
    }
}

if (!function_exists('nutrition_link_survey_to_residence')) {
    /**
     * @return array{ok:bool,error?:string,residence_id?:string,survey_id?:string}
     */
    function nutrition_link_survey_to_residence(
        mysqli $con,
        string $surveyId,
        string $residenceId,
        string $surveyBarangayId = '',
        string $actorUserId = '',
        bool $syncChildren = true
    ): array {
        nutrition_ensure_module_tables($con);

        $check = nutrition_validate_survey_residence_link($con, $surveyId, $residenceId, $surveyBarangayId);
        if (!$check['ok']) {
            return ['ok' => false, 'error' => $check['error'] ?? 'Validation failed.'];
        }

        $surveyBarangayId = (string) ($check['barangay_id'] ?? $surveyBarangayId);
        $upd = $con->prepare(
            'UPDATE nutrition_household_survey SET residence_id = ? WHERE survey_id = ? LIMIT 1'
        );
        if (!$upd) {
            return ['ok' => false, 'error' => 'Could not link survey.'];
        }
        $upd->bind_param('ss', $residenceId, $surveyId);
        $upd->execute();
        $upd->close();

        if ($syncChildren) {
            nutrition_sync_survey_children_to_dependents($con, $surveyId, $residenceId, $surveyBarangayId);
        }

        barangay_audit_log($con, 'Linked nutrition household survey to residence ' . $residenceId, 'nutrition_link', [
            'user_id' => $actorUserId !== '' ? $actorUserId : null,
            'barangay_id' => $surveyBarangayId,
            'entity_type' => 'nutrition_household_survey',
            'entity_id' => $surveyId,
        ]);

        return [
            'ok' => true,
            'survey_id' => $surveyId,
            'residence_id' => $residenceId,
        ];
    }
}

if (!function_exists('nutrition_auto_link_survey_by_head')) {
    /**
     * @return array{ok:bool,error?:string,residence_id?:string,survey_id?:string,matched?:bool}
     */
    function nutrition_auto_link_survey_by_head(
        mysqli $con,
        string $surveyId,
        string $barangayId,
        string $actorUserId = ''
    ): array {
        $survey = nutrition_load_survey_row($con, $surveyId);
        if (!$survey) {
            return ['ok' => false, 'error' => 'Survey not found.'];
        }

        $existing = trim((string) ($survey['residence_id'] ?? ''));
        if ($existing !== '') {
            return ['ok' => true, 'matched' => false, 'residence_id' => $existing, 'survey_id' => $surveyId];
        }

        $match = nutrition_find_resident_for_survey_head($con, $survey, $barangayId);
        if ($match === null) {
            return ['ok' => true, 'matched' => false, 'survey_id' => $surveyId];
        }

        $link = nutrition_link_survey_to_residence(
            $con,
            $surveyId,
            (string) $match['residence_id'],
            $barangayId,
            $actorUserId,
            true
        );
        $link['matched'] = $link['ok'];

        return $link;
    }
}

if (!function_exists('nutrition_list_unlinked_surveys')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function nutrition_list_unlinked_surveys(mysqli $con, ?string $barangayId = null, int $limit = 500): array
    {
        if (!barangay_table_exists($con, 'nutrition_household_survey')) {
            return [];
        }

        $limit = max(1, min(2000, $limit));
        $sql = "SELECT survey_id, barangay_id, house_hold_id, head_first_name, head_middle_name,
                       head_last_name, head_suffix, birth_date, survey_date
                FROM nutrition_household_survey
                WHERE residence_id IS NULL OR TRIM(residence_id) = ''";
        if ($barangayId !== null && $barangayId !== '') {
            $sql .= " AND barangay_id = '" . $con->real_escape_string($barangayId) . "'";
        }
        $sql .= ' ORDER BY survey_date DESC, survey_id DESC LIMIT ' . $limit;

        $rows = [];
        $res = $con->query($sql);
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('nutrition_auto_link_unlinked_surveys')) {
    /**
     * @return array{linked:int,skipped:int,no_match:int,errors:array<int,string>}
     */
    function nutrition_auto_link_unlinked_surveys(
        mysqli $con,
        ?string $barangayId = null,
        int $limit = 100,
        string $actorUserId = ''
    ): array {
        $stats = ['linked' => 0, 'skipped' => 0, 'no_match' => 0, 'errors' => []];
        $surveys = nutrition_list_unlinked_surveys($con, $barangayId, $limit);

        foreach ($surveys as $survey) {
            $surveyId = (string) ($survey['survey_id'] ?? '');
            $brgyId = (string) ($survey['barangay_id'] ?? '');
            if ($surveyId === '' || $brgyId === '') {
                $stats['skipped']++;
                continue;
            }

            $result = nutrition_auto_link_survey_by_head($con, $surveyId, $brgyId, $actorUserId);
            if (!$result['ok']) {
                $stats['errors'][] = $surveyId . ': ' . (string) ($result['error'] ?? 'unknown');
                continue;
            }
            if (!empty($result['matched']) && !empty($result['residence_id'])) {
                $stats['linked']++;
            } else {
                $stats['no_match']++;
            }
        }

        return $stats;
    }
}

if (!function_exists('nutrition_dependent_exists')) {
    function nutrition_dependent_exists(
        mysqli $con,
        string $residenceId,
        string $firstName,
        string $middleName,
        string $lastName,
        string $suffix,
        ?string $birthDate
    ): bool {
        if (!residence_dependents_table_exists($con)) {
            return false;
        }

        $key = nutrition_person_match_key($firstName, $middleName, $lastName, $suffix, $birthDate);
        $stmt = $con->prepare(
            'SELECT first_name, middle_name, last_name, suffix, birth_date
             FROM residence_dependents WHERE residence_id = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $residenceId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rowKey = nutrition_person_match_key(
                (string) ($row['first_name'] ?? ''),
                (string) ($row['middle_name'] ?? ''),
                (string) ($row['last_name'] ?? ''),
                (string) ($row['suffix'] ?? ''),
                (string) ($row['birth_date'] ?? '')
            );
            if ($rowKey === $key) {
                $stmt->close();

                return true;
            }
        }
        $stmt->close();

        return false;
    }
}

if (!function_exists('nutrition_sync_survey_children_to_dependents')) {
    /**
     * Append survey children (0–19) as dependents on the linked household head residence.
     *
     * @return array{added:int,skipped:int}
     */
    function nutrition_sync_survey_children_to_dependents(
        mysqli $con,
        string $surveyId,
        string $residenceId,
        string $barangayId
    ): array {
        $stats = ['added' => 0, 'skipped' => 0];
        if (!residence_dependents_table_exists($con)
            || !barangay_table_exists($con, 'nutrition_household_family_member')) {
            return $stats;
        }

        $stmt = $con->prepare(
            'SELECT member_name, birth_date, age_months, gender, relationship
             FROM nutrition_household_family_member
             WHERE survey_id = ? AND barangay_id = ?'
        );
        if (!$stmt) {
            return $stats;
        }
        $stmt->bind_param('ss', $surveyId, $barangayId);
        $stmt->execute();
        $members = $stmt->get_result();
        $stmt->close();

        $insert = $con->prepare(
            'INSERT INTO residence_dependents
            (dependent_id, residence_id, first_name, middle_name, last_name, suffix, birth_date, age, gender, relationship, contact_number, date_added)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$insert) {
            return $stats;
        }

        $dateAdded = date('m/d/Y h:i A');
        while ($member = $members->fetch_assoc()) {
            $ageMonths = isset($member['age_months']) && $member['age_months'] !== null && $member['age_months'] !== ''
                ? (int) $member['age_months']
                : nutrition_age_in_months(
                    trim((string) ($member['birth_date'] ?? '')) !== '' ? (string) $member['birth_date'] : null
                );
            if (!nutrition_member_is_child_0_to_19($ageMonths, (string) ($member['birth_date'] ?? ''))) {
                continue;
            }

            $memberName = trim((string) ($member['member_name'] ?? ''));
            $parsed = nutrition_parse_member_name($memberName);
            $first = $parsed['first'];
            $middle = $parsed['middle'];
            $last = $parsed['last'];
            $suffix = $parsed['suffix'];
            $birthDate = trim((string) ($member['birth_date'] ?? ''));
            if ($memberName === '' || ($first === '' && $last === '')) {
                $stats['skipped']++;
                continue;
            }
            if ($last === '') {
                $last = $first;
                $first = $memberName;
            }

            if (nutrition_dependent_exists($con, $residenceId, $first, $middle, $last, $suffix, $birthDate)) {
                $stats['skipped']++;
                continue;
            }

            $duplicateResident = residence_find_duplicate_name(
                $con,
                $barangayId,
                $first,
                $middle,
                $last,
                $suffix,
                $residenceId,
                $birthDate !== '' ? $birthDate : null
            );
            if ($duplicateResident !== null) {
                $stats['skipped']++;
                continue;
            }

            $ageYears = $ageMonths !== null ? (int) floor($ageMonths / 12) : '';
            if ($ageYears === 0) {
                $ageYears = '';
            } else {
                $ageYears = (string) $ageYears;
            }

            $gender = trim((string) ($member['gender'] ?? ''));
            $relationship = trim((string) ($member['relationship'] ?? 'Child'));
            if ($relationship === '') {
                $relationship = 'Child';
            }
            $contact = '';
            $dependentId = (string) hexdec(uniqid());

            $insert->bind_param(
                'ssssssssssss',
                $dependentId,
                $residenceId,
                $first,
                $middle,
                $last,
                $suffix,
                $birthDate,
                $ageYears,
                $gender,
                $relationship,
                $contact,
                $dateAdded
            );
            if ($insert->execute()) {
                $stats['added']++;
            } else {
                $stats['skipped']++;
            }
        }
        $insert->close();

        return $stats;
    }
}
