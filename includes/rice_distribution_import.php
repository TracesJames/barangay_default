<?php

require_once __DIR__ . '/spreadsheet_import.php';
require_once __DIR__ . '/barangay_context.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/residence_family.php';

if (!function_exists('rice_distribution_normalize_barangay_name')) {
    function rice_distribution_normalize_barangay_name(string $name): string
    {
        $name = trim($name);
        $aliases = [
            'conception' => 'Concepcion',
            'dagat_kidavao' => 'Dagat-Kidavao',
            'dagat-kidavao' => 'Dagat-Kidavao',
            'mt_nebo' => 'Mt. Nebo',
            'mt. nebo' => 'Mt. Nebo',
            'san carlos' => 'San Carlos',
            'san isidro' => 'San Isidro',
        ];
        $key = strtolower(str_replace(['_', '-'], ' ', $name));
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        foreach ($aliases as $aliasKey => $canonical) {
            if ($key === str_replace(['_', '-'], ' ', $aliasKey)) {
                return $canonical;
            }
        }

        return ucwords(strtolower($name));
    }
}

if (!function_exists('rice_distribution_normalize_psgc')) {
    /**
     * Normalize rice-list PSGC values to official 10-digit PSA codes.
     * Excel often stores truncated 9-digit codes (e.g. 101321001 -> 1001321001).
     */
    function rice_distribution_normalize_psgc(string $code, string $barangayName = ''): string
    {
        $official = barangay_psgc_lookup_by_name($barangayName);
        if ($official !== '') {
            return $official;
        }

        $digits = preg_replace('/\D+/', '', trim($code)) ?? '';
        if ($digits === '') {
            return '';
        }

        // Truncated Valencia City barangay codes: 101321xxx -> 1001321xxx
        if (strlen($digits) === 9 && str_starts_with($digits, '101321')) {
            return '100' . substr($digits, 2);
        }

        return $digits;
    }
}

if (!function_exists('rice_distribution_sync_psgc_from_main')) {
    /**
     * @param array<int, array<string, string>> $mainRows
     * @return array{updated:int,skipped:int,errors:array<int,string>}
     */
    function rice_distribution_sync_psgc_from_main(mysqli $con, array $mainRows): array
    {
        barangay_ensure_psgc_column($con);
        $barangayMap = rice_distribution_load_barangay_map($con);
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $stmt = $con->prepare('UPDATE barangay_information SET psgc_code = ? WHERE id = ?');
        if (!$stmt) {
            return ['updated' => 0, 'skipped' => 0, 'errors' => ['Could not prepare PSGC update: ' . $con->error]];
        }

        foreach ($mainRows as $row) {
            $rawName = trim((string) ($row['barangay'] ?? $row['barangay_name'] ?? ''));
            $rawPsgc = trim((string) ($row['psgc'] ?? $row['psgc_barangay'] ?? ''));
            if ($rawName === '' || ctype_digit($rawName)) {
                $skipped++;
                continue;
            }

            $barangayName = rice_distribution_normalize_barangay_name($rawName);
            $barangayKey = strtolower($barangayName);
            $active = $barangayMap[$barangayKey] ?? null;
            if ($active === null) {
                $errors[] = "PSGC sync skipped unknown barangay: $rawName";
                continue;
            }

            $psgc = rice_distribution_normalize_psgc($rawPsgc, $barangayName);
            if ($psgc === '') {
                $skipped++;
                continue;
            }

            $barangayId = (string) $active['id'];
            $stmt->bind_param('ss', $psgc, $barangayId);
            $stmt->execute();
            $updated++;
            echo "PSGC: $barangayName => $psgc\n";
        }

        return ['updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }
}

if (!function_exists('rice_distribution_excel_serial_to_date')) {
    function rice_distribution_excel_serial_to_date(string $serial): ?string
    {
        $serial = trim($serial);
        if ($serial === '' || !is_numeric($serial)) {
            return null;
        }

        $days = (int) floor((float) $serial);
        if ($days <= 0) {
            return null;
        }

        $date = new DateTime('1899-12-30');
        $date->modify('+' . $days . ' days');
        return $date->format('Y-m-d');
    }
}

if (!function_exists('rice_distribution_parse_person_name')) {
    /**
     * @return array{first_name:string,middle_name:string,last_name:string,suffix:string}
     */
    function rice_distribution_parse_person_name(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '' || $fullName === ',') {
            return ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => ''];
        }

        $parts = explode(',', $fullName, 2);
        $lastName = trim($parts[0]);
        $rest = trim($parts[1] ?? '');
        $tokens = $rest === '' ? [] : preg_split('/\s+/', $rest);
        $suffix = '';
        $suffixes = ['JR', 'JR.', 'SR', 'SR.', 'II', 'III', 'IV'];

        if ($tokens !== []) {
            $lastToken = strtoupper(rtrim((string) end($tokens), '.'));
            if (in_array($lastToken, ['JR', 'SR', 'II', 'III', 'IV'], true)) {
                $suffix = (string) array_pop($tokens);
            }
        }

        $firstName = $tokens !== [] ? (string) array_shift($tokens) : '';
        $middleName = implode(' ', $tokens);

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'suffix' => $suffix,
        ];
    }
}

if (!function_exists('rice_distribution_map_gender')) {
    function rice_distribution_map_gender(string $gender): string
    {
        $gender = strtoupper(trim($gender));
        if ($gender === 'F' || $gender === 'FEMALE') {
            return 'Female';
        }
        if ($gender === 'M' || $gender === 'MALE') {
            return 'Male';
        }
        return '';
    }
}

if (!function_exists('rice_distribution_load_barangay_map')) {
    /**
     * @return array<string, array<string, mixed>>
     */
    function rice_distribution_load_barangay_map(mysqli $con): array
    {
        $map = [];
        $result = $con->query('SELECT * FROM barangay_information');
        while ($row = $result->fetch_assoc()) {
            $name = (string) ($row['barangay'] ?? '');
            $map[strtolower($name)] = $row;
            $map[strtolower(rice_distribution_normalize_barangay_name($name))] = $row;
        }
        return $map;
    }
}

if (!function_exists('rice_distribution_existing_source_ids')) {
    /**
     * @return array<string, bool>
     */
    function rice_distribution_existing_source_ids(mysqli $con): array
    {
        $existing = [];
        $result = $con->query(
            "SELECT national_number FROM residence_information
             WHERE national_number IS NOT NULL AND TRIM(national_number) != ''"
        );
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $existing[(string) $row['national_number']] = true;
            }
        }
        return $existing;
    }
}

if (!function_exists('rice_distribution_row_to_import')) {
    /**
     * @param array<string, string> $row
     * @param array<string, mixed> $activeBarangay
     * @return array<string, string>|null
     */
    function rice_distribution_row_to_import(array $row, array $activeBarangay): ?array
    {
        $name = rice_distribution_parse_person_name($row['fullname'] ?? '');
        if ($name['first_name'] === '' && $name['last_name'] === '') {
            return null;
        }

        $birthDate = rice_distribution_excel_serial_to_date($row['bday'] ?? '');
        if ($birthDate === null) {
            $age = (int) ($row['age'] ?? 0);
            if ($age > 0) {
                $birthDate = ((int) date('Y') - $age) . '-01-01';
            } else {
                $birthDate = '1900-01-01';
            }
        }

        $spouse = rice_distribution_parse_person_name($row['spouse_name'] ?? '');
        $barangayName = (string) ($activeBarangay['barangay'] ?? ($row['barangay_name'] ?? ''));

        return [
            'national_number' => trim($row['unique_str_id'] ?? ''),
            'first_name' => $name['first_name'],
            'middle_name' => $name['middle_name'],
            'last_name' => $name['last_name'],
            'suffix' => $name['suffix'],
            'gender' => rice_distribution_map_gender($row['gender'] ?? ''),
            'civil_status' => trim($row['civil_status'] ?? ''),
            'religion' => '',
            'nationality' => 'Filipino',
            'contact_number' => trim($row['mobile_number'] ?? ''),
            'email_address' => '',
            'birth_date' => $birthDate,
            'birth_place' => 'Valencia City',
            'municipality' => (string) ($activeBarangay['address'] ?? 'Valencia City, Bukidnon'),
            'zip' => '8709',
            'barangay' => $barangayName,
            'purok' => trim($row['zone_sitio'] ?? ''),
            'house_number' => '',
            'street' => '',
            'address' => trim($row['zone_sitio'] ?? ''),
            'fathers_name' => '',
            'mothers_name' => '',
            'guardian' => '',
            'guardian_contact' => '',
            'voters' => '', // set from age during insert
            'pwd' => 'NO',
            'pwd_info' => '',
            'single_parent' => 'NO',
            'indigenous' => 'NO',
            'spouse_first_name' => $spouse['first_name'],
            'spouse_middle_name' => $spouse['middle_name'],
            'spouse_last_name' => $spouse['last_name'],
            'spouse_suffix' => $spouse['suffix'],
            'spouse_birth_date' => '',
            'spouse_occupation' => '',
            'spouse_employer_name' => '',
            'spouse_contact' => '',
        ];
    }
}

if (!function_exists('rice_distribution_import_workbook')) {
    /**
     * @return array{inserted:int,skipped:int,failed:int,errors:array<int,string>}
     */
    function rice_distribution_import_workbook(
        mysqli $con,
        string $filePath,
        ?string $onlyBarangay = null,
        int $limit = 0
    ): array {
        $barangayMap = rice_distribution_load_barangay_map($con);
        $existingIds = rice_distribution_existing_source_ids($con);
        $fullWorkbook = barangay_parse_xlsx_workbook($filePath);
        $mainRows = $fullWorkbook['Main'] ?? $fullWorkbook['main'] ?? [];
        if ($mainRows !== []) {
            echo "Syncing corrected PSGC codes from Main sheet...\n";
            $psgcResult = rice_distribution_sync_psgc_from_main($con, $mainRows);
            $barangayMap = rice_distribution_load_barangay_map($con);
        } else {
            $psgcResult = ['updated' => 0, 'skipped' => 0, 'errors' => []];
        }

        $workbook = [];
        foreach ($fullWorkbook as $sheetName => $rows) {
            if (strcasecmp((string) $sheetName, 'Main') === 0) {
                continue;
            }
            $workbook[$sheetName] = $rows;
        }

        $inserted = 0;
        $skipped = 0;
        $failed = 0;
        $errors = $psgcResult['errors'];
        $processed = 0;

        $hasUsersBarangay = barangay_column_exists($con, 'users', 'barangay_id');
        $hasIndigenous = barangay_column_exists($con, 'residence_status', 'indigenous');
        $hasSpouse = residence_has_spouse_columns($con);
        $hasNationalNumber = barangay_column_exists($con, 'residence_information', 'national_number');
        $passwordHash = barangay_hash_password('RiceImport' . date('Y'));
        $dateAdded = date('m/d/Y h:i A');
        $batchSize = 250;

        $infoSql = $hasNationalNumber
            ? 'INSERT INTO residence_information (
                residence_id, first_name, middle_name, last_name, age, suffix, gender, civil_status,
                religion, nationality, contact_number, email_address, address, birth_date, birth_place,
                municipality, zip, barangay, house_number, street, fathers_name, mothers_name, guardian,
                guardian_contact, national_number, image, image_path
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            : 'INSERT INTO residence_information (
                residence_id, first_name, middle_name, last_name, age, suffix, gender, civil_status,
                religion, nationality, contact_number, email_address, address, birth_date, birth_place,
                municipality, zip, barangay, house_number, street, fathers_name, mothers_name, guardian,
                guardian_contact, image, image_path
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

        $statusSql = $hasIndigenous
            ? 'INSERT INTO residence_status (
                residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent,
                indigenous, date_added
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            : 'INSERT INTO residence_status (
                residence_id, barangay_id, status, voters, archive, pwd, pwd_info, senior, single_parent, date_added
            ) VALUES (?,?,?,?,?,?,?,?,?,?)';

        $userSql = $hasUsersBarangay
            ? 'INSERT INTO users (
                id, first_name, middle_name, last_name, username, password, user_type, contact_number,
                image, image_path, barangay_id
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            : 'INSERT INTO users (
                id, first_name, middle_name, last_name, username, password, user_type, contact_number,
                image, image_path
            ) VALUES (?,?,?,?,?,?,?,?,?,?)';

        $infoStmt = $con->prepare($infoSql);
        $statusStmt = $con->prepare($statusSql);
        $userStmt = $con->prepare($userSql);
        $spouseStmt = null;
        if ($hasSpouse) {
            $spouseStmt = $con->prepare(
                'UPDATE residence_information SET
                    spouse_first_name = ?, spouse_middle_name = ?, spouse_last_name = ?, spouse_suffix = ?,
                    spouse_birth_date = ?, spouse_age = ?, spouse_occupation = ?, spouse_contact = ?,
                    spouse_employer_name = ?
                WHERE residence_id = ?'
            );
        }

        if (!$infoStmt || !$statusStmt || !$userStmt) {
            throw RuntimeException('Could not prepare import statements: ' . $con->error);
        }

        $con->autocommit(false);

        foreach ($workbook as $sheetName => $rows) {
            $firstRow = $rows[0] ?? [];
            $barangayName = rice_distribution_normalize_barangay_name(
                (string) ($firstRow['barangay_name'] ?? $sheetName)
            );
            $barangayKey = strtolower($barangayName);

            if ($onlyBarangay !== null && strcasecmp($onlyBarangay, $barangayName) !== 0) {
                continue;
            }

            $activeBarangay = $barangayMap[$barangayKey] ?? null;
            if ($activeBarangay === null) {
                $errors[] = "Unknown barangay: $barangayName (sheet: $sheetName)";
                continue;
            }

            $barangayId = (string) $activeBarangay['id'];
            echo "Importing $barangayName (" . count($rows) . " rows)...\n";

            $batchCount = 0;
            foreach ($rows as $index => $row) {
                if ($limit > 0 && $processed >= $limit) {
                    break 2;
                }

                $sourceId = trim($row['unique_str_id'] ?? '');
                if ($sourceId !== '' && isset($existingIds[$sourceId])) {
                    $skipped++;
                    continue;
                }

                $importRow = rice_distribution_row_to_import($row, $activeBarangay);
                if ($importRow === null) {
                    $skipped++;
                    continue;
                }

                if ($sourceId !== '') {
                    $importRow['national_number'] = $sourceId;
                }

                try {
                    $birthDate = $importRow['birth_date'];
                    $today = date('Y/m/d');
                    $ageYears = (int) date_diff(date_create($birthDate), date_create($today))->format('%y');
                    $senior = $ageYears >= 60 ? 'YES' : 'NO';
                    $ageValue = $ageYears === 0 ? '' : (string) $ageYears;
                    $residenceId = barangay_generate_id();
                    $emptyImage = '';
                    $archive = 'NO';
                    $status = 'ACTIVE';
                    $userType = 'resident';
                    $voters = $ageYears >= 18 ? 'YES' : 'NO';
                    $pwd = 'NO';
                    $pwdInfo = '';
                    $singleParent = 'NO';
                    $indigenous = 'NO';

                    if ($hasNationalNumber) {
                        $infoStmt->bind_param(
                            'sssssssssssssssssssssssssss',
                            $residenceId,
                            $importRow['first_name'],
                            $importRow['middle_name'],
                            $importRow['last_name'],
                            $ageValue,
                            $importRow['suffix'],
                            $importRow['gender'],
                            $importRow['civil_status'],
                            $importRow['religion'],
                            $importRow['nationality'],
                            $importRow['contact_number'],
                            $importRow['email_address'],
                            $importRow['address'],
                            $birthDate,
                            $importRow['birth_place'],
                            $importRow['municipality'],
                            $importRow['zip'],
                            $importRow['barangay'],
                            $importRow['house_number'],
                            $importRow['street'],
                            $importRow['fathers_name'],
                            $importRow['mothers_name'],
                            $importRow['guardian'],
                            $importRow['guardian_contact'],
                            $importRow['national_number'],
                            $emptyImage,
                            $emptyImage
                        );
                    } else {
                        $infoStmt->bind_param(
                            'ssssssssssssssssssssssssss',
                            $residenceId,
                            $importRow['first_name'],
                            $importRow['middle_name'],
                            $importRow['last_name'],
                            $ageValue,
                            $importRow['suffix'],
                            $importRow['gender'],
                            $importRow['civil_status'],
                            $importRow['religion'],
                            $importRow['nationality'],
                            $importRow['contact_number'],
                            $importRow['email_address'],
                            $importRow['address'],
                            $birthDate,
                            $importRow['birth_place'],
                            $importRow['municipality'],
                            $importRow['zip'],
                            $importRow['barangay'],
                            $importRow['house_number'],
                            $importRow['street'],
                            $importRow['fathers_name'],
                            $importRow['mothers_name'],
                            $importRow['guardian'],
                            $importRow['guardian_contact'],
                            $emptyImage,
                            $emptyImage
                        );
                    }
                    $infoStmt->execute();

                    if ($hasIndigenous) {
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
                    } else {
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
                    }
                    $statusStmt->execute();

                    if ($hasUsersBarangay) {
                        $userStmt->bind_param(
                            'sssssssssss',
                            $residenceId,
                            $importRow['first_name'],
                            $importRow['middle_name'],
                            $importRow['last_name'],
                            $residenceId,
                            $passwordHash,
                            $userType,
                            $importRow['contact_number'],
                            $emptyImage,
                            $emptyImage,
                            $barangayId
                        );
                    } else {
                        $userStmt->bind_param(
                            'ssssssssss',
                            $residenceId,
                            $importRow['first_name'],
                            $importRow['middle_name'],
                            $importRow['last_name'],
                            $residenceId,
                            $passwordHash,
                            $userType,
                            $importRow['contact_number'],
                            $emptyImage,
                            $emptyImage
                        );
                    }
                    $userStmt->execute();

                    if ($spouseStmt !== null
                        && ($importRow['spouse_first_name'] !== '' || $importRow['spouse_last_name'] !== '')) {
                        $spouseAge = '';
                        $emptySpouseDate = '';
                        $emptySpouseJob = '';
                        $spouseStmt->bind_param(
                            'ssssssssss',
                            $importRow['spouse_first_name'],
                            $importRow['spouse_middle_name'],
                            $importRow['spouse_last_name'],
                            $importRow['spouse_suffix'],
                            $emptySpouseDate,
                            $spouseAge,
                            $emptySpouseJob,
                            $importRow['spouse_contact'],
                            $importRow['spouse_employer_name'],
                            $residenceId
                        );
                        $spouseStmt->execute();
                    }

                    if ($sourceId !== '') {
                        $existingIds[$sourceId] = true;
                    }
                    $inserted++;
                    $batchCount++;
                } catch (Throwable $e) {
                    $failed++;
                    if (count($errors) < 50) {
                        $errors[] = $barangayName . ' row ' . ($index + 2) . ': ' . $e->getMessage();
                    }
                }

                $processed++;
                if ($batchCount >= $batchSize) {
                    $con->commit();
                    $batchCount = 0;
                    echo "  processed $processed...\n";
                }
            }
        }

        $con->commit();
        $con->autocommit(true);

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
