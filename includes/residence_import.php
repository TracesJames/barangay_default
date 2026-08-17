<?php

require_once __DIR__ . '/spreadsheet_import.php';
require_once __DIR__ . '/spreadsheet_export.php';
require_once __DIR__ . '/residence_family.php';

if (!function_exists('residence_registration_template_fields')) {
    /**
     * Registration form fields in the same order as newResidence.php.
     *
     * @return array<int, array{key:string,label:string,group:string}>
     */
    function residence_registration_template_fields(): array
    {
        return [
            ['key' => 'voters', 'label' => 'Voters', 'group' => 'Status'],
            ['key' => 'gender', 'label' => 'Gender', 'group' => 'Status'],
            ['key' => 'birth_date', 'label' => 'Date of Birth', 'group' => 'Status'],
            ['key' => 'birth_place', 'label' => 'Place of Birth', 'group' => 'Status'],
            ['key' => 'pwd', 'label' => 'PWD', 'group' => 'Status'],
            ['key' => 'pwd_info', 'label' => 'Type of PWD', 'group' => 'Status'],
            ['key' => 'single_parent', 'label' => 'Single Parent', 'group' => 'Status'],
            ['key' => 'indigenous', 'label' => 'Indigenous People (IP)', 'group' => 'Status'],
            ['key' => 'first_name', 'label' => 'First Name', 'group' => 'Personal'],
            ['key' => 'middle_name', 'label' => 'Middle Name', 'group' => 'Personal'],
            ['key' => 'last_name', 'label' => 'Last Name', 'group' => 'Personal'],
            ['key' => 'suffix', 'label' => 'Suffix', 'group' => 'Personal'],
            ['key' => 'civil_status', 'label' => 'Civil Status', 'group' => 'Personal'],
            ['key' => 'religion', 'label' => 'Religion', 'group' => 'Personal'],
            ['key' => 'nationality', 'label' => 'Nationality', 'group' => 'Personal'],
            ['key' => 'municipality', 'label' => 'Municipality', 'group' => 'Address'],
            ['key' => 'zip', 'label' => 'Zip', 'group' => 'Address'],
            ['key' => 'barangay', 'label' => 'Barangay', 'group' => 'Address'],
            ['key' => 'purok', 'label' => 'Purok', 'group' => 'Address'],
            ['key' => 'house_number', 'label' => 'House Number', 'group' => 'Address'],
            ['key' => 'street', 'label' => 'Street', 'group' => 'Address'],
            ['key' => 'address', 'label' => 'Address', 'group' => 'Address'],
            ['key' => 'contact_number', 'label' => 'Contact Number', 'group' => 'Address'],
            ['key' => 'email_address', 'label' => 'Email Address', 'group' => 'Address'],
            ['key' => 'fathers_name', 'label' => "Father's Name", 'group' => 'Guardian'],
            ['key' => 'mothers_name', 'label' => "Mother's Name", 'group' => 'Guardian'],
            ['key' => 'guardian', 'label' => 'Guardian', 'group' => 'Guardian'],
            ['key' => 'guardian_contact', 'label' => 'Guardian Contact', 'group' => 'Guardian'],
            ['key' => 'spouse_first_name', 'label' => 'Spouse First Name', 'group' => 'Spouse'],
            ['key' => 'spouse_middle_name', 'label' => 'Spouse Middle Name', 'group' => 'Spouse'],
            ['key' => 'spouse_last_name', 'label' => 'Spouse Last Name', 'group' => 'Spouse'],
            ['key' => 'spouse_suffix', 'label' => 'Spouse Suffix', 'group' => 'Spouse'],
            ['key' => 'spouse_birth_date', 'label' => 'Spouse Birth Date', 'group' => 'Spouse'],
            ['key' => 'spouse_occupation', 'label' => 'Spouse Occupation', 'group' => 'Spouse'],
            ['key' => 'spouse_employer_name', 'label' => 'Spouse Employer', 'group' => 'Spouse'],
            ['key' => 'spouse_contact', 'label' => 'Spouse Contact Number', 'group' => 'Spouse'],
        ];
    }
}

if (!function_exists('residence_import_template_headers')) {
    /**
     * @return array<int, string>
     */
    function residence_import_template_headers(): array
    {
        return array_column(residence_registration_template_fields(), 'key');
    }
}

if (!function_exists('residence_import_header_aliases')) {
    /**
     * @return array<string, string>
     */
    function residence_import_header_aliases(): array
    {
        $aliases = [];
        foreach (residence_registration_template_fields() as $field) {
            $key = $field['key'];
            $label = $field['label'];
            $aliases[$key] = $key;
            $aliases[str_replace('_', '', $key)] = $key;
            $aliases[barangay_normalize_spreadsheet_header($label)] = $key;
            $aliases[barangay_normalize_spreadsheet_header($key)] = $key;
        }

        $aliases['firstname'] = 'first_name';
        $aliases['lastname'] = 'last_name';
        $aliases['middlename'] = 'middle_name';
        $aliases['birthdate'] = 'birth_date';
        $aliases['birthplace'] = 'birth_place';
        $aliases['contact'] = 'contact_number';
        $aliases['email'] = 'email_address';
        $aliases['father_name'] = 'fathers_name';
        $aliases['mother_name'] = 'mothers_name';
        $aliases['fathers_name'] = 'fathers_name';
        $aliases['mothers_name'] = 'mothers_name';
        $aliases['type_of_pwd'] = 'pwd_info';
        $aliases['indigenous_people_ip'] = 'indigenous';
        $aliases['spouse_employer'] = 'spouse_employer_name';

        return $aliases;
    }
}

if (!function_exists('residence_import_normalize_row')) {
    /**
     * @param array<string, string> $row
     * @return array<string, string>
     */
    function residence_import_normalize_row(array $row): array
    {
        $aliases = residence_import_header_aliases();
        $normalized = [];

        foreach ($row as $key => $value) {
            $key = barangay_normalize_spreadsheet_header((string) $key);
            $field = $aliases[$key] ?? $key;
            if (in_array($field, residence_import_template_headers(), true)) {
                $normalized[$field] = trim((string) $value);
            }
        }

        return $normalized;
    }
}

if (!function_exists('residence_import_normalize_yes_no')) {
    function residence_import_normalize_yes_no(string $value, string $default = 'NO'): string
    {
        $value = strtoupper(trim($value));
        if ($value === 'YES' || $value === 'Y' || $value === '1' || $value === 'TRUE') {
            return 'YES';
        }
        if ($value === 'NO' || $value === 'N' || $value === '0' || $value === 'FALSE') {
            return 'NO';
        }
        return $default;
    }
}

if (!function_exists('residence_import_normalize_birth_date')) {
    function residence_import_normalize_birth_date(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[1], (int) $matches[2]);
        }

        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }
}

if (!function_exists('residence_import_validate_row')) {
    /**
     * @param array<string, string> $row
     */
    function residence_import_validate_row(array $row, int $lineNumber): ?string
    {
        if (($row['first_name'] ?? '') === '') {
            return "Row $lineNumber: first_name is required.";
        }
        if (($row['last_name'] ?? '') === '') {
            return "Row $lineNumber: last_name is required.";
        }

        $birthDate = residence_import_normalize_birth_date($row['birth_date'] ?? '');
        if ($birthDate === null) {
            return "Row $lineNumber: birth_date is required (YYYY-MM-DD).";
        }

        $post = [
            'add_birth_date' => $birthDate,
            'add_guardian' => $row['guardian'] ?? '',
            'add_fathers_name' => $row['fathers_name'] ?? '',
            'add_mothers_name' => $row['mothers_name'] ?? '',
        ];
        if (residence_is_minor($birthDate) && !residence_has_guardian_info($post)) {
            return "Row $lineNumber: guardian, fathers_name, or mothers_name is required for minors.";
        }

        return null;
    }
}

if (!function_exists('residence_import_resolve_purok_id')) {
    function residence_import_resolve_purok_id(mysqli $con, string $barangayId, string $purokName): ?string
    {
        $purokName = trim($purokName);
        if ($purokName === '' || !barangay_column_exists($con, 'purok', 'barangay_id')) {
            return null;
        }

        $stmt = $con->prepare('SELECT purok_id FROM purok WHERE barangay_id = ? AND purok = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $barangayId, $purokName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row['purok_id'] ?? null;
    }
}

if (!function_exists('residence_import_create_one')) {
    /**
     * @param array<string, string> $row
     * @param array<string, mixed> $activeBarangay
     * @return array{ok:bool,residence_id?:string,error?:string}
     */
    function residence_import_create_one(
        mysqli $con,
        array $row,
        string $barangayId,
        array $activeBarangay,
        string $actorLabel
    ): array {
        $birthDate = residence_import_normalize_birth_date($row['birth_date'] ?? '');
        if ($birthDate === null) {
            return ['ok' => false, 'error' => 'Invalid birth_date.'];
        }

        date_default_timezone_set('Asia/Manila');
        $date = new DateTime();
        $residenceId = residence_generate_number($con, $barangayId, (string) ($activeBarangay['barangay'] ?? ''));
        $dateAdded = date('m/d/Y h:i A');
        $archive = 'NO';
        $status = 'ACTIVE';
        $userType = 'resident';
        $password = barangay_hash_password($date->format('mdYHisv'));

        $firstName = $row['first_name'];
        $middleName = $row['middle_name'] ?? '';
        $lastName = $row['last_name'];
        $suffix = $row['suffix'] ?? '';
        $gender = $row['gender'] ?? '';
        $civilStatus = $row['civil_status'] ?? '';
        $religion = $row['religion'] ?? '';
        $nationality = $row['nationality'] ?? '';
        $contactNumber = $row['contact_number'] ?? '';
        $emailAddress = $row['email_address'] ?? '';
        $address = $row['address'] ?? '';
        $birthPlace = $row['birth_place'] ?? '';
        $municipality = $row['municipality'] ?? (string) ($activeBarangay['address'] ?? 'Valencia City, Bukidnon');
        $zip = $row['zip'] ?? '';
        $barangayName = (string) ($activeBarangay['barangay'] ?? '');
        $houseNumber = $row['house_number'] ?? '';
        $street = $row['street'] ?? '';
        $fathersName = $row['fathers_name'] ?? '';
        $mothersName = $row['mothers_name'] ?? '';
        $guardian = $row['guardian'] ?? '';
        $guardianContact = $row['guardian_contact'] ?? '';
        $voters = residence_import_normalize_yes_no($row['voters'] ?? '', 'NO');
        $pwd = residence_import_normalize_yes_no($row['pwd'] ?? '', 'NO');
        $pwdInfo = $row['pwd_info'] ?? '';
        $singleParent = residence_import_normalize_yes_no($row['single_parent'] ?? '', 'NO');
        $indigenous = residence_import_normalize_yes_no($row['indigenous'] ?? '', 'NO');
        $purokId = residence_import_resolve_purok_id($con, $barangayId, $row['purok'] ?? '');

        $today = date('Y/m/d');
        $ageDiff = date_diff(date_create($birthDate), date_create($today));
        $ageYears = (int) $ageDiff->format('%y');
        $senior = $ageYears >= 60 ? 'YES' : 'NO';
        $ageValue = $ageYears === 0 ? '' : (string) $ageYears;

        $con->begin_transaction();

        try {
            $stmt = $con->prepare(
                'INSERT INTO residence_information (
                    residence_id, first_name, middle_name, last_name, age, suffix, gender, civil_status,
                    religion, nationality, contact_number, email_address, address, birth_date, birth_place,
                    municipality, zip, barangay, house_number, street, fathers_name, mothers_name, guardian,
                    guardian_contact, image, image_path
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            if (!$stmt) {
                throw new RuntimeException($con->error);
            }

            $emptyImage = '';
            $stmt->bind_param(
                'ssssssssssssssssssssssssss',
                $residenceId,
                $firstName,
                $middleName,
                $lastName,
                $ageValue,
                $suffix,
                $gender,
                $civilStatus,
                $religion,
                $nationality,
                $contactNumber,
                $emailAddress,
                $address,
                $birthDate,
                $birthPlace,
                $municipality,
                $zip,
                $barangayName,
                $houseNumber,
                $street,
                $fathersName,
                $mothersName,
                $guardian,
                $guardianContact,
                $emptyImage,
                $emptyImage
            );
            $stmt->execute();
            $stmt->close();

            $spousePost = [
                'add_spouse_first_name' => $row['spouse_first_name'] ?? '',
                'add_spouse_middle_name' => $row['spouse_middle_name'] ?? '',
                'add_spouse_last_name' => $row['spouse_last_name'] ?? '',
                'add_spouse_suffix' => $row['spouse_suffix'] ?? '',
                'add_spouse_birth_date' => residence_import_normalize_birth_date($row['spouse_birth_date'] ?? '') ?? '',
                'add_spouse_occupation' => $row['spouse_occupation'] ?? '',
                'add_spouse_employer_name' => $row['spouse_employer_name'] ?? '',
                'add_spouse_contact' => $row['spouse_contact'] ?? '',
            ];
            residence_save_spouse($con, $residenceId, $spousePost);

            $hasIndigenous = barangay_column_exists($con, 'residence_status', 'indigenous');
            $hasPurok = barangay_column_exists($con, 'residence_status', 'purok_id') && $purokId !== null;

            if ($hasIndigenous && $hasPurok) {
                $statusSql = 'INSERT INTO residence_status (
                    residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent,
                    indigenous, purok_id, date_added
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
                $statusStmt = $con->prepare($statusSql);
                if (!$statusStmt) {
                    throw new RuntimeException($con->error);
                }
                $statusStmt->bind_param(
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
            } elseif ($hasIndigenous) {
                $statusSql = 'INSERT INTO residence_status (
                    residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent,
                    indigenous, date_added
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
                $statusStmt = $con->prepare($statusSql);
                if (!$statusStmt) {
                    throw new RuntimeException($con->error);
                }
                $statusStmt->bind_param(
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
            } elseif (barangay_column_exists($con, 'residence_status', 'barangay_id')) {
                $statusSql = 'INSERT INTO residence_status (
                    residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent, date_added
                ) VALUES (?,?,?,?,?,?,?,?,?,?)';
                $statusStmt = $con->prepare($statusSql);
                if (!$statusStmt) {
                    throw new RuntimeException($con->error);
                }
                $statusStmt->bind_param(
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
            } else {
                $statusSql = 'INSERT INTO residence_status (
                    residence_id, status, voters, archive, pwd, pwd_info, senior, single_parent, date_added
                ) VALUES (?,?,?,?,?,?,?,?,?)';
                $statusStmt = $con->prepare($statusSql);
                if (!$statusStmt) {
                    throw new RuntimeException($con->error);
                }
                $statusStmt->bind_param(
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
            $statusStmt->execute();
            $statusStmt->close();

            if (barangay_column_exists($con, 'users', 'barangay_id')) {
                $userSql = 'INSERT INTO users (
                    id, first_name, middle_name, last_name, username, password, user_type, contact_number,
                    image, image_path, barangay_id
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
                $userStmt = $con->prepare($userSql);
                if (!$userStmt) {
                    throw new RuntimeException($con->error);
                }
                $emptyImage = '';
                $userStmt->bind_param(
                    'sssssssssss',
                    $residenceId,
                    $firstName,
                    $middleName,
                    $lastName,
                    $residenceId,
                    $password,
                    $userType,
                    $contactNumber,
                    $emptyImage,
                    $emptyImage,
                    $barangayId
                );
            } else {
                $userSql = 'INSERT INTO users (
                    id, first_name, middle_name, last_name, username, password, user_type, contact_number,
                    image, image_path
                ) VALUES (?,?,?,?,?,?,?,?,?,?)';
                $userStmt = $con->prepare($userSql);
                if (!$userStmt) {
                    throw new RuntimeException($con->error);
                }
                $emptyImage = '';
                $userStmt->bind_param(
                    'ssssssssss',
                    $residenceId,
                    $firstName,
                    $middleName,
                    $lastName,
                    $residenceId,
                    $password,
                    $userType,
                    $contactNumber,
                    $emptyImage,
                    $emptyImage
                );
            }
            $userStmt->execute();
            $userStmt->close();

            $activityDate = date('j-n-Y g:i A');
            $message = strtoupper($actorLabel) . ': IMPORTED RESIDENT - ' . $residenceId . ' | ' . $firstName . ' ' . $lastName . ' ' . $suffix;
            $activityStatus = 'create';
            $logStmt = $con->prepare('INSERT INTO activity_log (message, date, status) VALUES (?,?,?)');
            if ($logStmt) {
                $logStmt->bind_param('sss', $message, $activityDate, $activityStatus);
                $logStmt->execute();
                $logStmt->close();
            }

            $con->commit();
            return ['ok' => true, 'residence_id' => $residenceId];
        } catch (Throwable $e) {
            $con->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('residence_import_process_upload')) {
    /**
     * @param array<string, mixed> $activeBarangay
     * @return array{inserted:int,failed:int,errors:array<int,string>}
     */
    function residence_import_process_upload(
        mysqli $con,
        array $file,
        string $barangayId,
        array $activeBarangay,
        string $actorLabel
    ): array {
        $validation = barangay_validate_spreadsheet_upload($file);
        if (!$validation['ok']) {
            return ['inserted' => 0, 'failed' => 0, 'errors' => [$validation['error'] ?? 'Invalid upload.']];
        }

        $tmpPath = (string) $file['tmp_name'];
        $rows = barangay_parse_spreadsheet_rows($tmpPath, $validation['ext']);

        if ($rows === []) {
            return ['inserted' => 0, 'failed' => 0, 'errors' => ['The spreadsheet has no data rows.']];
        }

        $inserted = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $rawRow) {
            $lineNumber = $index + 2;
            $row = residence_import_normalize_row($rawRow);
            $validationError = residence_import_validate_row($row, $lineNumber);
            if ($validationError !== null) {
                $failed++;
                $errors[] = $validationError;
                continue;
            }

            $result = residence_import_create_one($con, $row, $barangayId, $activeBarangay, $actorLabel);
            if ($result['ok']) {
                $inserted++;
                continue;
            }

            $failed++;
            $errors[] = 'Row ' . $lineNumber . ': ' . ($result['error'] ?? 'Could not save resident.');
        }

        return ['inserted' => $inserted, 'failed' => $failed, 'errors' => $errors];
    }
}

if (!function_exists('residence_import_sample_row')) {
    /**
     * @param array<string, mixed> $activeBarangay
     * @return array<string, string>
     */
    function residence_import_sample_row(mysqli $con, array $activeBarangay): array
    {
        $barangayName = (string) ($activeBarangay['barangay'] ?? '');
        $municipality = (string) ($activeBarangay['address'] ?? 'Valencia City, Bukidnon');
        $purokOptions = barangay_purok_filter_options($con, (string) ($activeBarangay['id'] ?? ''));
        $samplePurok = $purokOptions[0]['label'] ?? 'PUROK 1';

        return [
            'voters' => 'YES',
            'gender' => 'Male',
            'birth_date' => '1990-05-15',
            'birth_place' => 'Valencia City',
            'pwd' => 'NO',
            'pwd_info' => '',
            'single_parent' => 'NO',
            'indigenous' => 'NO',
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'suffix' => 'Jr',
            'civil_status' => 'Single',
            'religion' => 'Roman Catholic',
            'nationality' => 'Filipino',
            'municipality' => $municipality,
            'zip' => '8709',
            'barangay' => $barangayName,
            'purok' => $samplePurok,
            'house_number' => '12',
            'street' => 'Rizal Street',
            'address' => '12 Rizal Street',
            'contact_number' => '09171234567',
            'email_address' => 'juan@example.com',
            'fathers_name' => 'Pedro Cruz',
            'mothers_name' => 'Maria Cruz',
            'guardian' => '',
            'guardian_contact' => '',
            'spouse_first_name' => '',
            'spouse_middle_name' => '',
            'spouse_last_name' => '',
            'spouse_suffix' => '',
            'spouse_birth_date' => '',
            'spouse_occupation' => '',
            'spouse_employer_name' => '',
            'spouse_contact' => '',
        ];
    }
}

if (!function_exists('residence_import_stream_template')) {
    function residence_import_stream_template(mysqli $con, array $activeBarangay): void
    {
        residence_import_stream_registration_template($con, $activeBarangay);
    }
}

if (!function_exists('residence_import_stream_registration_template')) {
    /**
     * @param array<string, mixed> $activeBarangay
     */
    function residence_import_stream_registration_template(mysqli $con, array $activeBarangay): void
    {
        $fields = residence_registration_template_fields();
        $labels = array_column($fields, 'label');
        $keys = array_column($fields, 'key');
        $sample = residence_import_sample_row($con, $activeBarangay);
        $sampleRow = [];
        foreach ($keys as $key) {
            $sampleRow[] = $sample[$key] ?? '';
        }

        $barangayName = (string) ($activeBarangay['barangay'] ?? 'Barangay');
        $district = (string) ($activeBarangay['district'] ?? 'Valencia City');
        $city = (string) ($activeBarangay['address'] ?? 'Valencia City, Bukidnon');
        $zone = (string) ($activeBarangay['zone'] ?? 'PUROK');
        $purokOptions = barangay_purok_filter_options($con, (string) ($activeBarangay['id'] ?? ''));

        $instructionRows = [
            ['Resident Registration Import Form'],
            ['Barangay', $barangayName],
            ['Zone / Purok Label', $zone],
            ['District', $district],
            ['City / Address', $city],
            [''],
            ['Instructions'],
            ['1. Fill one resident per row in the Residents sheet.'],
            ['2. Required: First Name, Last Name, Date of Birth (YYYY-MM-DD).'],
            ['3. For residents under 18, provide Guardian, Father\'s Name, or Mother\'s Name.'],
            ['4. Voters, PWD, Single Parent, and IP columns accept YES or NO.'],
            ['5. Keep the Barangay column as ' . $barangayName . ' for this template.'],
            ['6. Upload the completed file from Import from Excel.'],
            [''],
            ['Valid Purok Values'],
        ];

        if ($purokOptions === []) {
            $instructionRows[] = ['(No purok records yet — enter purok name or leave blank)'];
        } else {
            foreach ($purokOptions as $option) {
                $instructionRows[] = ['', (string) ($option['label'] ?? '')];
            }
        }

        $residentRows = [$labels, $sampleRow];
        for ($i = 0; $i < 49; $i++) {
            $blank = [];
            foreach ($keys as $key) {
                if ($key === 'barangay') {
                    $blank[] = $barangayName;
                } elseif ($key === 'municipality') {
                    $blank[] = $city;
                } elseif ($key === 'zip') {
                    $blank[] = '8709';
                } else {
                    $blank[] = '';
                }
            }
            $residentRows[] = $blank;
        }

        if (!class_exists('ZipArchive')) {
            residence_import_stream_csv_template($con, $activeBarangay);
            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'brgy_xlsx_');
        if ($tempFile === false) {
            throw new RuntimeException('Could not create temporary Excel file.');
        }
        $xlsxPath = $tempFile . '.xlsx';
        @unlink($tempFile);

        barangay_xlsx_create_file([
            ['name' => 'Instructions', 'rows' => $instructionRows],
            ['name' => 'Residents', 'rows' => $residentRows],
        ], $xlsxPath);

        $filename = barangay_xlsx_safe_filename('Resident_Registration_' . $barangayName);
        barangay_xlsx_stream_file($xlsxPath, $filename);
    }
}

if (!function_exists('residence_import_stream_csv_template')) {
    /**
     * @param array<string, mixed> $activeBarangay
     */
    function residence_import_stream_csv_template(mysqli $con, array $activeBarangay): void
    {
        $fields = residence_registration_template_fields();
        $labels = array_column($fields, 'label');
        $keys = array_column($fields, 'key');
        $sample = residence_import_sample_row($con, $activeBarangay);
        $sampleRow = [];
        foreach ($keys as $key) {
            $sampleRow[] = $sample[$key] ?? '';
        }

        $barangayName = (string) ($activeBarangay['barangay'] ?? 'Barangay');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . barangay_xlsx_safe_filename('Resident_Registration_' . $barangayName, '.csv') . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }
        fputcsv($out, $labels);
        fputcsv($out, $sampleRow);
        fclose($out);
        exit;
    }
}
