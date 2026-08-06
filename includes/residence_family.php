<?php

require_once __DIR__ . '/barangay_context.php';

if (!function_exists('residence_number_pad_length')) {
    function residence_number_pad_length(): int
    {
        return 6;
    }
}

if (!function_exists('residence_format_number')) {
    /**
     * Resident Number format: {PSGC}-{NNNNNN}
     * Example: 1001321024-000001
     */
    function residence_format_number(string $psgc, int $series): string
    {
        $psgc = preg_replace('/\D+/', '', $psgc) ?? '';
        $series = max(1, $series);

        return $psgc . '-' . str_pad((string) $series, residence_number_pad_length(), '0', STR_PAD_LEFT);
    }
}

if (!function_exists('residence_is_psgc_number')) {
    function residence_is_psgc_number(string $residenceId): bool
    {
        $pad = residence_number_pad_length();

        return (bool) preg_match('/^\d{10}-\d{' . $pad . '}$/', $residenceId);
    }
}

if (!function_exists('residence_next_series_for_psgc')) {
    function residence_next_series_for_psgc(mysqli $con, string $psgc): int
    {
        $psgc = preg_replace('/\D+/', '', $psgc) ?? '';
        if ($psgc === '') {
            return 1;
        }

        $like = $psgc . '-%';
        $stmt = $con->prepare(
            "SELECT residence_id FROM residence_information
             WHERE residence_id LIKE ?
             ORDER BY residence_id DESC
             LIMIT 1"
        );
        if (!$stmt) {
            return 1;
        }
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $pad = residence_number_pad_length();
        if (!$row || !preg_match('/^' . preg_quote($psgc, '/') . '-(\d{' . $pad . '})$/', (string) $row['residence_id'], $m)) {
            return 1;
        }

        return max(1, (int) $m[1] + 1);
    }
}

if (!function_exists('residence_generate_number')) {
    /**
     * Generate next Resident Number for a barangay using its PSA PSGC code.
     */
    function residence_generate_number(mysqli $con, string $barangayId, string $barangayName = ''): string
    {
        $psgc = barangay_resolve_psgc_code($con, $barangayId, $barangayName);
        if ($psgc === '' || !preg_match('/^\d{10}$/', $psgc)) {
            // Fallback when barangay has no PSGC mapping yet.
            return (string) hexdec(uniqid());
        }

        $series = residence_next_series_for_psgc($con, $psgc);
        $candidate = residence_format_number($psgc, $series);

        // Guard against rare race conditions.
        for ($i = 0; $i < 20; $i++) {
            $check = $con->prepare('SELECT residence_id FROM residence_information WHERE residence_id = ? LIMIT 1');
            if (!$check) {
                break;
            }
            $check->bind_param('s', $candidate);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();
            if (!$exists) {
                return $candidate;
            }
            $series++;
            $candidate = residence_format_number($psgc, $series);
        }

        return $candidate;
    }
}

if (!function_exists('residence_has_spouse_columns')) {
    function residence_has_spouse_columns(mysqli $con): bool
    {
        return barangay_column_exists($con, 'residence_information', 'spouse_first_name');
    }
}

if (!function_exists('residence_dependents_table_exists')) {
    function residence_dependents_table_exists(mysqli $con): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $result = $con->query("SHOW TABLES LIKE 'residence_dependents'");
        $cache = $result && $result->num_rows > 0;
        return $cache;
    }
}

if (!function_exists('residence_compute_age')) {
    function residence_compute_age(string $birthDate): string
    {
        if ($birthDate === '') {
            return '';
        }
        $today = date('Y/m/d');
        $age = date_diff(date_create($birthDate), date_create($today));
        $years = $age->format('%y');
        return ($years === '0') ? '' : $years;
    }
}

if (!function_exists('residence_parse_spouse_post')) {
    function residence_parse_spouse_post(array $post, string $prefix = 'add_'): array
    {
        return [
            'spouse_first_name' => trim((string) ($post[$prefix . 'spouse_first_name'] ?? '')),
            'spouse_middle_name' => trim((string) ($post[$prefix . 'spouse_middle_name'] ?? '')),
            'spouse_last_name' => trim((string) ($post[$prefix . 'spouse_last_name'] ?? '')),
            'spouse_suffix' => trim((string) ($post[$prefix . 'spouse_suffix'] ?? '')),
            'spouse_birth_date' => trim((string) ($post[$prefix . 'spouse_birth_date'] ?? '')),
            'spouse_age' => residence_compute_age(trim((string) ($post[$prefix . 'spouse_birth_date'] ?? ''))),
            'spouse_occupation' => trim((string) ($post[$prefix . 'spouse_occupation'] ?? '')),
            'spouse_contact' => trim((string) ($post[$prefix . 'spouse_contact'] ?? '')),
            'spouse_employer_name' => trim((string) ($post[$prefix . 'spouse_employer_name'] ?? '')),
        ];
    }
}

if (!function_exists('residence_save_spouse')) {
    function residence_save_spouse(mysqli $con, string $residenceId, array $post, string $prefix = 'add_'): void
    {
        if (!residence_has_spouse_columns($con)) {
            return;
        }

        $spouse = residence_parse_spouse_post($post, $prefix);
        $sql = 'UPDATE residence_information SET
            spouse_first_name = ?,
            spouse_middle_name = ?,
            spouse_last_name = ?,
            spouse_suffix = ?,
            spouse_birth_date = ?,
            spouse_age = ?,
            spouse_occupation = ?,
            spouse_contact = ?,
            spouse_employer_name = ?
            WHERE residence_id = ?';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param(
            'ssssssssss',
            $spouse['spouse_first_name'],
            $spouse['spouse_middle_name'],
            $spouse['spouse_last_name'],
            $spouse['spouse_suffix'],
            $spouse['spouse_birth_date'],
            $spouse['spouse_age'],
            $spouse['spouse_occupation'],
            $spouse['spouse_contact'],
            $spouse['spouse_employer_name'],
            $residenceId
        );
        $stmt->execute();
    }
}

if (!function_exists('residence_parse_dependents_post')) {
    function residence_parse_dependents_post(array $post, string $prefix = 'add_'): array
    {
        $firstNames = $post[$prefix . 'dependent_first_name'] ?? [];
        if (!is_array($firstNames)) {
            $firstNames = [$firstNames];
        }

        $dependents = [];
        foreach ($firstNames as $index => $firstName) {
            $firstName = trim((string) $firstName);
            $middleName = trim((string) (($post[$prefix . 'dependent_middle_name'][$index] ?? '')));
            $lastName = trim((string) (($post[$prefix . 'dependent_last_name'][$index] ?? '')));
            $suffix = trim((string) (($post[$prefix . 'dependent_suffix'][$index] ?? '')));
            $birthDate = trim((string) (($post[$prefix . 'dependent_birth_date'][$index] ?? '')));
            $gender = trim((string) (($post[$prefix . 'dependent_gender'][$index] ?? '')));
            $relationship = trim((string) (($post[$prefix . 'dependent_relationship'][$index] ?? '')));
            $contactNumber = trim((string) (($post[$prefix . 'dependent_contact_number'][$index] ?? '')));

            if ($firstName === '' && $middleName === '' && $lastName === '' && $relationship === '') {
                continue;
            }

            $dependents[] = [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'birth_date' => $birthDate,
                'age' => residence_compute_age($birthDate),
                'gender' => $gender,
                'relationship' => $relationship,
                'contact_number' => $contactNumber,
            ];
        }

        return $dependents;
    }
}

if (!function_exists('residence_load_dependents')) {
    function residence_load_dependents(mysqli $con, string $residenceId): array
    {
        if (!residence_dependents_table_exists($con)) {
            return [];
        }

        $stmt = $con->prepare(
            'SELECT dependent_id, first_name, middle_name, last_name, suffix, birth_date, age, gender, relationship, contact_number
             FROM residence_dependents
             WHERE residence_id = ?
             ORDER BY a_i ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $residenceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}

if (!function_exists('residence_save_dependents')) {
    function residence_save_dependents(mysqli $con, string $residenceId, array $post, string $prefix = 'add_'): void
    {
        if (!residence_dependents_table_exists($con)) {
            return;
        }

        $dependents = residence_parse_dependents_post($post, $prefix);
        $delete = $con->prepare('DELETE FROM residence_dependents WHERE residence_id = ?');
        if ($delete) {
            $delete->bind_param('s', $residenceId);
            $delete->execute();
        }

        if ($dependents === []) {
            return;
        }

        $dateAdded = date('m/d/Y h:i A');
        $sql = 'INSERT INTO residence_dependents
            (dependent_id, residence_id, first_name, middle_name, last_name, suffix, birth_date, age, gender, relationship, contact_number, date_added)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return;
        }

        foreach ($dependents as $dependent) {
            $dependentId = (string) hexdec(uniqid());
            $stmt->bind_param(
                'ssssssssssss',
                $dependentId,
                $residenceId,
                $dependent['first_name'],
                $dependent['middle_name'],
                $dependent['last_name'],
                $dependent['suffix'],
                $dependent['birth_date'],
                $dependent['age'],
                $dependent['gender'],
                $dependent['relationship'],
                $dependent['contact_number'],
                $dateAdded
            );
            $stmt->execute();
        }
    }
}

if (!function_exists('residence_spouse_full_name')) {
    function residence_spouse_full_name(array $row): string
    {
        $parts = array_filter([
            trim((string) ($row['spouse_first_name'] ?? '')),
            trim((string) ($row['spouse_middle_name'] ?? '')),
            trim((string) ($row['spouse_last_name'] ?? '')),
            trim((string) ($row['spouse_suffix'] ?? '')),
        ], static fn ($part) => $part !== '');

        return trim(implode(' ', $parts));
    }
}

if (!function_exists('residence_is_minor')) {
    function residence_is_minor(string $birthDate, int $maxAge = 17): bool
    {
        $age = residence_compute_age($birthDate);
        if ($age === '') {
            return false;
        }

        return (int) $age <= $maxAge;
    }
}

if (!function_exists('residence_has_guardian_info')) {
    function residence_has_guardian_info(array $post, string $prefix = 'add_'): bool
    {
        $guardian = trim((string) ($post[$prefix . 'guardian'] ?? ''));
        $father = trim((string) ($post[$prefix . 'fathers_name'] ?? ''));
        $mother = trim((string) ($post[$prefix . 'mothers_name'] ?? ''));

        return $guardian !== '' || $father !== '' || $mother !== '';
    }
}

if (!function_exists('residence_require_minor_guardian_or_exit')) {
    function residence_require_minor_guardian_or_exit(array $post, string $prefix = 'add_'): void
    {
        $birthDate = trim((string) ($post[$prefix . 'birth_date'] ?? ''));
        if (!residence_is_minor($birthDate) || residence_has_guardian_info($post, $prefix)) {
            return;
        }

        residence_exit_add_result('errorMinorGuardian');
    }
}

if (!function_exists('residence_insert_status_row')) {
    /**
     * Insert a residence_status row using columns available in the current schema.
     */
    function residence_insert_status_row(
        mysqli $con,
        string $residenceId,
        string $status,
        string $voters,
        string $archive,
        string $pwd,
        string $pwdInfo,
        string $senior,
        string $singleParent,
        string $dateAdded,
        ?string $barangayId = null,
        string $indigenous = 'NO',
        ?string $purokId = null
    ): void {
        $hasIndigenous = barangay_column_exists($con, 'residence_status', 'indigenous');
        $hasBarangayId = barangay_column_exists($con, 'residence_status', 'barangay_id')
            && $barangayId !== null && $barangayId !== '';
        $hasPurok = barangay_column_exists($con, 'residence_status', 'purok_id')
            && $purokId !== null && $purokId !== '';

        if ($hasIndigenous && $hasBarangayId && $hasPurok) {
            $sql = 'INSERT INTO residence_status (
                residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent,
                indigenous, purok_id, date_added
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException($con->error);
            }
            $stmt->bind_param(
                'ssssssssssss',
                $residenceId,
                $barangayId,
                $status,
                $voters,
                $archive,
                $pwd,
                $pwdInfo,
                $senior,
                $singleParent,
                $indigenous,
                $purokId,
                $dateAdded
            );
        } elseif ($hasIndigenous && $hasBarangayId) {
            $sql = 'INSERT INTO residence_status (
                residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent,
                indigenous, date_added
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException($con->error);
            }
            $stmt->bind_param(
                'sssssssssss',
                $residenceId,
                $barangayId,
                $status,
                $voters,
                $archive,
                $pwd,
                $pwdInfo,
                $senior,
                $singleParent,
                $indigenous,
                $dateAdded
            );
        } elseif ($hasBarangayId) {
            $sql = 'INSERT INTO residence_status (
                residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent, date_added
            ) VALUES (?,?,?,?,?,?,?,?,?,?)';
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException($con->error);
            }
            $stmt->bind_param(
                'ssssssssss',
                $residenceId,
                $barangayId,
                $status,
                $voters,
                $archive,
                $pwd,
                $pwdInfo,
                $senior,
                $singleParent,
                $dateAdded
            );
        } elseif ($hasIndigenous) {
            $sql = 'INSERT INTO residence_status (
                residence_id, status, voters, archive, pwd, pwd_info, senior, single_parent, indigenous, date_added
            ) VALUES (?,?,?,?,?,?,?,?,?,?)';
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException($con->error);
            }
            $stmt->bind_param(
                'ssssssssss',
                $residenceId,
                $status,
                $voters,
                $archive,
                $pwd,
                $pwdInfo,
                $senior,
                $singleParent,
                $indigenous,
                $dateAdded
            );
        } else {
            $sql = 'INSERT INTO residence_status (
                residence_id, status, voters, archive, pwd, pwd_info, senior, single_parent, date_added
            ) VALUES (?,?,?,?,?,?,?,?,?)';
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException($con->error);
            }
            $stmt->bind_param(
                'sssssssss',
                $residenceId,
                $status,
                $voters,
                $archive,
                $pwd,
                $pwdInfo,
                $senior,
                $singleParent,
                $dateAdded
            );
        }

        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('residence_has_household_head_column')) {
    function residence_has_household_head_column(mysqli $con): bool
    {
        return barangay_column_exists($con, 'residence_status', 'household_head');
    }
}

if (!function_exists('residence_set_household_head')) {
    function residence_set_household_head(mysqli $con, string $residenceId, string $value): bool
    {
        if (!residence_has_household_head_column($con) || $residenceId === '') {
            return false;
        }

        $flag = strtoupper($value) === 'YES' ? 'YES' : 'NO';
        $stmt = $con->prepare('UPDATE residence_status SET household_head = ? WHERE residence_id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $flag, $residenceId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('residence_is_registered')) {
    function residence_is_registered(mysqli $con, string $residenceId): bool
    {
        if ($residenceId === '') {
            return false;
        }

        $stmt = $con->prepare('SELECT residence_id FROM residence_information WHERE residence_id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $residenceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row !== null;
    }
}

if (!function_exists('residence_find_duplicate_name')) {
    /**
     * Find an existing resident in the barangay with the same full name
     * (first, middle, last, suffix — case-insensitive).
     *
     * @return array{residence_id:string,first_name:string,middle_name:string,last_name:string,suffix:string,birth_date:string}|null
     */
    function residence_find_duplicate_name(
        mysqli $con,
        string $barangayId,
        string $firstName,
        string $middleName,
        string $lastName,
        string $suffix = '',
        ?string $excludeResidenceId = null,
        ?string $birthDate = null
    ): ?array {
        $firstName = trim($firstName);
        $middleName = trim($middleName);
        $lastName = trim($lastName);
        $suffix = trim($suffix);
        $birthDate = $birthDate !== null ? trim($birthDate) : null;

        if ($firstName === '' || $lastName === '' || $barangayId === '') {
            return null;
        }

        $hasBarangayScope = barangay_column_exists($con, 'residence_status', 'barangay_id');
        $sql = 'SELECT ri.residence_id, ri.first_name, ri.middle_name, ri.last_name, ri.suffix, ri.birth_date
                FROM residence_information ri
                INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
                WHERE LOWER(TRIM(ri.first_name)) = LOWER(?)
                  AND LOWER(TRIM(IFNULL(ri.middle_name, \'\'))) = LOWER(?)
                  AND LOWER(TRIM(ri.last_name)) = LOWER(?)
                  AND LOWER(TRIM(IFNULL(ri.suffix, \'\'))) = LOWER(?)';
        $types = 'ssss';
        $params = [$firstName, $middleName, $lastName, $suffix];

        if ($birthDate !== null && $birthDate !== '') {
            $sql .= ' AND ri.birth_date = ?';
            $types .= 's';
            $params[] = $birthDate;
        }

        if ($hasBarangayScope) {
            $sql .= ' AND rs.barangay_id = ?';
            $types .= 's';
            $params[] = $barangayId;
        }

        if ($excludeResidenceId !== null && $excludeResidenceId !== '') {
            $sql .= ' AND ri.residence_id <> ?';
            $types .= 's';
            $params[] = $excludeResidenceId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return [
            'residence_id' => (string) ($row['residence_id'] ?? ''),
            'first_name' => (string) ($row['first_name'] ?? ''),
            'middle_name' => (string) ($row['middle_name'] ?? ''),
            'last_name' => (string) ($row['last_name'] ?? ''),
            'suffix' => (string) ($row['suffix'] ?? ''),
            'birth_date' => (string) ($row['birth_date'] ?? ''),
        ];
    }
}

if (!function_exists('residence_duplicate_check')) {
    /**
     * Soft-warn when the same name exists; hard-block when name + birth date match.
     *
     * @return array{level:string,message:string,residence_id:string,match:?array}|null
     */
    function residence_duplicate_check(
        mysqli $con,
        string $barangayId,
        string $firstName,
        string $middleName,
        string $lastName,
        string $suffix = '',
        string $birthDate = '',
        ?string $excludeResidenceId = null
    ): ?array {
        $birthDate = trim($birthDate);

        if ($birthDate !== '') {
            $hard = residence_find_duplicate_name(
                $con,
                $barangayId,
                $firstName,
                $middleName,
                $lastName,
                $suffix,
                $excludeResidenceId,
                $birthDate
            );
            if ($hard !== null) {
                return [
                    'level' => 'block',
                    'message' => 'This name and birth date are already registered in the Barangay.',
                    'residence_id' => $hard['residence_id'],
                    'match' => $hard,
                ];
            }
        }

        $soft = residence_find_duplicate_name(
            $con,
            $barangayId,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $excludeResidenceId,
            null
        );
        if ($soft !== null) {
            return [
                'level' => 'warn',
                'message' => 'A resident with this name is already registered. Confirm birth date before saving.',
                'residence_id' => $soft['residence_id'],
                'match' => $soft,
            ];
        }

        return null;
    }
}

if (!function_exists('residence_require_unique_name_or_exit')) {
    function residence_require_unique_name_or_exit(
        mysqli $con,
        string $barangayId,
        string $firstName,
        string $middleName,
        string $lastName,
        string $suffix = '',
        ?string $excludeResidenceId = null,
        string $birthDate = ''
    ): void {
        $check = residence_duplicate_check(
            $con,
            $barangayId,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $birthDate,
            $excludeResidenceId
        );
        if ($check !== null && $check['level'] === 'block') {
            residence_exit_add_result('errorDuplicateName');
        }
    }
}

if (!function_exists('residence_is_ajax_request')) {
    function residence_is_ajax_request(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

if (!function_exists('residence_exit_add_result')) {
    /**
     * AJAX callers get a plain status token; full-page POSTs redirect back to the form.
     */
    function residence_exit_add_result(string $result, string $formPage = 'newResidence.php'): void
    {
        if (residence_is_ajax_request()) {
            exit($result);
        }

        if (strpos($result, 'success') === 0) {
            $parts = explode('|', $result, 2);
            $id = isset($parts[1]) ? trim($parts[1]) : '';
            $qs = 'saved=1';
            if ($id !== '') {
                $qs .= '&residence_id=' . rawurlencode($id);
            }
            header('Location: ' . $formPage . '?' . $qs);
            exit;
        }

        $map = [
            'errorDuplicateName' => 'duplicate',
            'errorMinorGuardian' => 'minor_guardian',
            'errorValidation' => 'validation',
            'errorImage' => 'image',
            'errorServer' => 'server',
        ];
        $code = $map[$result] ?? '1';
        header('Location: ' . $formPage . '?error=' . rawurlencode($code));
        exit;
    }
}

if (!function_exists('residence_can_request_certificate')) {
    /**
     * All registered residents may request certificates regardless of
     * ACTIVE/INACTIVE status, archive flag, or voter registration.
     */
    function residence_can_request_certificate(mysqli $con, string $residenceId): bool
    {
        return residence_is_registered($con, $residenceId);
    }
}

if (!function_exists('residence_require_certificate_access_or_exit')) {
    function residence_require_certificate_access_or_exit(mysqli $con, string $residenceId): void
    {
        if (!residence_can_request_certificate($con, $residenceId)) {
            exit('errorNotRegistered');
        }
    }
}
