<?php

require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    barangay_start_session();
}

const BARANGAY_SESSION_KEY = 'active_barangay_id';

if (!function_exists('barangay_session_id')) {
    function barangay_session_id(): ?string
    {
        $id = $_SESSION[BARANGAY_SESSION_KEY] ?? null;
        return ($id !== null && $id !== '') ? (string) $id : null;
    }
}

if (!function_exists('barangay_set_active')) {
    function barangay_set_active(string $id): void
    {
        $_SESSION[BARANGAY_SESSION_KEY] = $id;
    }
}

if (!function_exists('barangay_clear_active')) {
    function barangay_clear_active(): void
    {
        unset($_SESSION[BARANGAY_SESSION_KEY]);
    }
}

if (!function_exists('barangay_generate_id')) {
    function barangay_generate_id(): string
    {
        return (string) hexdec(uniqid());
    }
}

if (!function_exists('barangay_column_cache')) {
    /**
     * @return array<string, bool>
     */
    function &barangay_column_cache(): array
    {
        static $cache = [];

        return $cache;
    }
}

if (!function_exists('barangay_column_exists')) {
    function barangay_column_exists(mysqli $con, string $table, string $column): bool
    {
        $cache = &barangay_column_cache();
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $sql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            $cache[$key] = false;
            return false;
        }
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $cache[$key] = ((int) ($row['total'] ?? 0)) > 0;
        return $cache[$key];
    }
}

if (!function_exists('barangay_mark_column_exists')) {
    function barangay_mark_column_exists(string $table, string $column): void
    {
        $cache = &barangay_column_cache();
        $cache[$table . '.' . $column] = true;
    }
}

if (!function_exists('barangay_load_by_id')) {
    function barangay_load_by_id(mysqli $con, string $id): ?array
    {
        $stmt = $con->prepare('SELECT * FROM barangay_information WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}

if (!function_exists('barangay_ensure_psgc_column')) {
    function barangay_ensure_psgc_column(mysqli $con): void
    {
        if (barangay_column_exists($con, 'barangay_information', 'psgc_code')) {
            return;
        }

        if ($con->query(
            "ALTER TABLE `barangay_information`
             ADD COLUMN `psgc_code` VARCHAR(16) NOT NULL DEFAULT '' AFTER `barangay`"
        )) {
            barangay_mark_column_exists('barangay_information', 'psgc_code');
        }
    }
}

if (!function_exists('barangay_psgc_valencia_map')) {
    /**
     * @return array<string, string>
     */
    function barangay_psgc_valencia_map(): array
    {
        static $map = null;
        if ($map === null) {
            $file = __DIR__ . '/psgc_valencia_barangays.php';
            $map = is_file($file) ? (require $file) : [];
        }

        return $map;
    }
}

if (!function_exists('barangay_normalize_psgc_name')) {
    function barangay_normalize_psgc_name(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        $aliases = [
            'nabag-o' => 'Nabago',
            'nabago' => 'Nabago',
            'poblacion' => 'Poblacion',
            'mt nebo' => 'Mt. Nebo',
            'mt. nebo' => 'Mt. Nebo',
            'mount nebo' => 'Mt. Nebo',
            'lurugan' => 'Lurogan',
            'dagat kidavao' => 'Dagat-Kidavao',
            'dagat-kidavao' => 'Dagat-Kidavao',
        ];

        $key = mb_strtolower($name);
        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        return $name;
    }
}

if (!function_exists('barangay_psgc_lookup_by_name')) {
    function barangay_psgc_lookup_by_name(string $barangayName): string
    {
        $barangayName = barangay_normalize_psgc_name($barangayName);
        if ($barangayName === '') {
            return '';
        }

        $map = barangay_psgc_valencia_map();
        if (isset($map[$barangayName])) {
            return (string) $map[$barangayName];
        }

        foreach ($map as $label => $code) {
            if (strcasecmp($label, $barangayName) === 0) {
                return (string) $code;
            }
        }

        return '';
    }
}

if (!function_exists('barangay_resolve_psgc_code')) {
    function barangay_resolve_psgc_code(mysqli $con, string $barangayId = '', string $barangayName = ''): string
    {
        barangay_ensure_psgc_column($con);

        if ($barangayId !== '') {
            $row = barangay_load_by_id($con, $barangayId);
            if ($row) {
                $stored = trim((string) ($row['psgc_code'] ?? ''));
                if ($stored !== '') {
                    return $stored;
                }
                $barangayName = trim((string) ($row['barangay'] ?? $barangayName));
            }
        }

        $code = barangay_psgc_lookup_by_name($barangayName);
        if ($code !== '' && $barangayId !== '') {
            $stmt = $con->prepare('UPDATE barangay_information SET psgc_code = ? WHERE id = ? AND (psgc_code IS NULL OR psgc_code = ?)');
            if ($stmt) {
                $empty = '';
                $stmt->bind_param('sss', $code, $barangayId, $empty);
                $stmt->execute();
                $stmt->close();
            }
        }

        return $code;
    }
}

if (!function_exists('barangay_seed_psgc_codes')) {
    /**
     * @return array{updated:int,skipped:int,missing:array<int,string>}
     */
    function barangay_seed_psgc_codes(mysqli $con): array
    {
        barangay_ensure_psgc_column($con);

        $updated = 0;
        $skipped = 0;
        $missing = [];

        $stmt = $con->prepare(
            'UPDATE barangay_information SET psgc_code = ? WHERE id = ? AND (psgc_code IS NULL OR psgc_code = ?)'
        );
        if (!$stmt) {
            return ['updated' => 0, 'skipped' => 0, 'missing' => []];
        }

        foreach (barangay_list_all($con) as $row) {
            $name = trim((string) ($row['barangay'] ?? ''));
            $id = (string) ($row['id'] ?? '');
            if ($name === '' || $id === '') {
                continue;
            }

            $existing = trim((string) ($row['psgc_code'] ?? ''));
            if ($existing !== '') {
                $skipped++;
                continue;
            }

            $code = barangay_psgc_lookup_by_name($name);
            if ($code === '') {
                $missing[] = $name;
                continue;
            }

            $empty = '';
            $stmt->bind_param('sss', $code, $id, $empty);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        $stmt->close();

        return ['updated' => $updated, 'skipped' => $skipped, 'missing' => $missing];
    }
}

if (!function_exists('barangay_load_active')) {
    function barangay_load_active(mysqli $con): ?array
    {
        $id = barangay_session_id();
        if ($id === null) {
            return null;
        }
        $row = barangay_load_by_id($con, $id);
        if ($row === null) {
            // Stale session after PSGC ID migration — clear and force re-select.
            barangay_clear_active();
            return null;
        }
        return $row;
    }
}

if (!function_exists('barangay_require_active')) {
    function barangay_require_active(mysqli $con, string $hubPath = 'barangayHub.php'): array
    {
        $row = barangay_load_active($con);
        if ($row === null) {
            header('Location: ' . $hubPath);
            exit;
        }
        return $row;
    }
}

if (!function_exists('barangay_is_placeholder_name')) {
    function barangay_is_placeholder_name(?string $name): bool
    {
        $name = trim((string) $name);

        return $name === '' || strcasecmp($name, 'Barangay') === 0;
    }
}

if (!function_exists('barangay_list_all')) {
    function barangay_list_all(mysqli $con): array
    {
        $rows = [];
        $result = $con->query('SELECT * FROM barangay_information ORDER BY barangay ASC');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (barangay_is_placeholder_name($row['barangay'] ?? null)) {
                    continue;
                }
                $rows[] = $row;
            }
        }
        return $rows;
    }
}

if (!function_exists('barangay_list_puroks')) {
    /**
     * @return array<int, array{purok_id:string,purok:string,leader:string,barangay_id:string}>
     */
    function barangay_list_puroks(mysqli $con, ?string $barangayId = null): array
    {
        if (!barangay_column_exists($con, 'purok', 'barangay_id')) {
            return [];
        }

        $barangayId = $barangayId ?? barangay_resolve_scope_id($con);
        if ($barangayId === null || $barangayId === '') {
            return [];
        }

        $rows = [];
        $stmt = $con->prepare('SELECT purok_id, purok, leader, barangay_id FROM purok WHERE barangay_id = ? ORDER BY purok ASC');
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $barangayId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('barangay_purok_filter_options')) {
    /**
     * Purok names for filters: configured puroks plus resident address values for the barangay.
     *
     * @return array<int, array{value:string,label:string}>
     */
    function barangay_purok_filter_options(mysqli $con, ?string $barangayId = null): array
    {
        $barangayId = $barangayId ?? barangay_resolve_scope_id($con);
        $options = [];
        $seen = [];

        foreach (barangay_list_puroks($con, $barangayId) as $row) {
            $name = trim((string) ($row['purok'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $options[] = ['value' => $name, 'label' => $name];
        }

        if ($barangayId !== null && $barangayId !== ''
            && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $stmt = $con->prepare(
                "SELECT DISTINCT TRIM(ri.address) AS address
                 FROM residence_information ri
                 INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
                 WHERE rs.barangay_id = ?
                   AND rs.archive = 'NO'
                   AND ri.address IS NOT NULL
                   AND TRIM(ri.address) != ''
                 ORDER BY address ASC
                 LIMIT 100"
            );
            if ($stmt) {
                $stmt->bind_param('s', $barangayId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $name = trim((string) ($row['address'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $key = mb_strtolower($name);
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $options[] = ['value' => $name, 'label' => $name];
                }
            }
        }

        return $options;
    }
}

if (!function_exists('barangay_append_purok_filter')) {
    /**
     * @param array<int, string> $whereClause
     */
    function barangay_append_purok_filter(mysqli $con, array &$whereClause, string $purok): void
    {
        $purok = trim($purok);
        if ($purok === '') {
            return;
        }
        $esc = $con->real_escape_string($purok);
        $whereClause[] = "TRIM(residence_information.address) = '$esc'";
    }
}

if (!function_exists('barangay_count_residents')) {
    function barangay_count_residents(mysqli $con, string $barangayId): int
    {
        if (barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM residence_status WHERE archive = 'NO' AND barangay_id = ?");
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return (int) ($row['total'] ?? 0);
        }
        $result = $con->query("SELECT COUNT(*) AS total FROM residence_status WHERE archive = 'NO'");
        $row = $result ? $result->fetch_assoc() : null;
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('barangay_children_age_condition')) {
    function barangay_children_age_condition(string $infoAlias = 'residence_information'): string
    {
        $birth = $infoAlias . '.birth_date';
        $age = $infoAlias . '.age';

        return "(
            ($birth IS NOT NULL AND $birth != '' AND TIMESTAMPDIFF(YEAR, $birth, CURDATE()) <= 17)
            OR (($birth IS NULL OR $birth = '') AND $age != '' AND CAST($age AS UNSIGNED) <= 17)
        )";
    }
}

if (!function_exists('barangay_hub_resident_stat_keys')) {
    /**
     * @return array<int, string>
     */
    function barangay_hub_resident_stat_keys(): array
    {
        return [
            'population',
            'voters',
            'non_voters',
            'children',
            'senior',
            'pwd',
            'single_parent',
            'indigenous',
        ];
    }
}

if (!function_exists('barangay_hub_stat_where_clause')) {
    /**
     * WHERE fragments for hub summary resident lists (aliases: ri, rs).
     *
     * @return array<int, string>
     */
    function barangay_hub_stat_where_clause(mysqli $con, string $statKey): array
    {
        $where = ["rs.archive = 'NO'"];

        switch ($statKey) {
            case 'population':
                break;
            case 'voters':
                $where[] = "rs.voters = 'YES'";
                break;
            case 'non_voters':
                $where[] = "rs.voters = 'NO'";
                break;
            case 'children':
                $where[] = barangay_children_age_condition('ri');
                break;
            case 'senior':
                $where[] = "rs.senior = 'YES'";
                break;
            case 'pwd':
                $where[] = "rs.pwd = 'YES'";
                break;
            case 'single_parent':
                $where[] = "rs.single_parent = 'YES'";
                break;
            case 'indigenous':
                if (barangay_column_exists($con, 'residence_status', 'indigenous')) {
                    $where[] = "rs.indigenous = 'YES'";
                } else {
                    $where[] = '1 = 0';
                }
                break;
            default:
                $where[] = '1 = 0';
        }

        return $where;
    }
}

if (!function_exists('barangay_count_children')) {
    function barangay_count_children(mysqli $con, ?string $barangayId = null): int
    {
        $no = 'NO';
        $childCondition = barangay_children_age_condition('residence_information');
        $sql = "SELECT COUNT(*) AS total FROM residence_information
            INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id
            WHERE residence_status.archive = ?
              AND ($childCondition)";

        if ($barangayId !== null && $barangayId !== '' && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $sql .= ' AND residence_status.barangay_id = ?';
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param('ss', $no, $barangayId);
        } else {
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param('s', $no);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            return 0;
        }
        $row = $result->fetch_assoc();

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('barangay_scoped_resident_totals')) {
    /**
     * Fast aggregate counts for a single barangay dashboard.
     *
     * @return array{total:int,voters_yes:int,voters_no:int,single_parent:int,pwd:int,senior:int,indigenous:int}
     */
    function barangay_scoped_resident_totals(mysqli $con, string $barangayId): array
    {
        $yes = 'YES';
        $no = 'NO';
        $hasIndigenous = barangay_column_exists($con, 'residence_status', 'indigenous');

        $defaults = [
            'total' => 0,
            'voters_yes' => 0,
            'voters_no' => 0,
            'single_parent' => 0,
            'pwd' => 0,
            'senior' => 0,
            'indigenous' => 0,
        ];

        $sql = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN voters = ? THEN 1 ELSE 0 END) AS voters_yes,
            SUM(CASE WHEN voters = ? THEN 1 ELSE 0 END) AS voters_no,
            SUM(CASE WHEN single_parent = ? THEN 1 ELSE 0 END) AS single_parent,
            SUM(CASE WHEN pwd = ? THEN 1 ELSE 0 END) AS pwd,
            SUM(CASE WHEN senior = ? THEN 1 ELSE 0 END) AS senior";
        if ($hasIndigenous) {
            $sql .= ", SUM(CASE WHEN indigenous = ? THEN 1 ELSE 0 END) AS indigenous";
        }
        $sql .= ' FROM residence_status WHERE archive = ? AND barangay_id = ?';

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return $defaults;
        }

        if ($hasIndigenous) {
            $stmt->bind_param('ssssssss', $yes, $no, $yes, $yes, $yes, $yes, $no, $barangayId);
        } else {
            $stmt->bind_param('sssssss', $yes, $no, $yes, $yes, $yes, $no, $barangayId);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'voters_yes' => (int) ($row['voters_yes'] ?? 0),
            'voters_no' => (int) ($row['voters_no'] ?? 0),
            'single_parent' => (int) ($row['single_parent'] ?? 0),
            'pwd' => (int) ($row['pwd'] ?? 0),
            'senior' => (int) ($row['senior'] ?? 0),
            'indigenous' => (int) ($row['indigenous'] ?? 0),
        ];
    }
}

if (!function_exists('barangay_hub_totals')) {
    /**
     * System-wide totals across all barangays for the hub summary.
     */
    function barangay_hub_totals(mysqli $con): array
    {
        $yes = 'YES';
        $no = 'NO';
        $hasIndigenous = barangay_column_exists($con, 'residence_status', 'indigenous');

        $totals = [
            'population' => 0,
            'voters' => 0,
            'non_voters' => 0,
            'children' => 0,
            'senior' => 0,
            'pwd' => 0,
            'single_parent' => 0,
            'indigenous' => 0,
            'blotter' => 0,
        ];

        $sql = "SELECT
            COUNT(*) AS population,
            SUM(CASE WHEN voters = ? THEN 1 ELSE 0 END) AS voters,
            SUM(CASE WHEN voters = ? THEN 1 ELSE 0 END) AS non_voters,
            SUM(CASE WHEN pwd = ? THEN 1 ELSE 0 END) AS pwd,
            SUM(CASE WHEN single_parent = ? THEN 1 ELSE 0 END) AS single_parent,
            SUM(CASE WHEN senior = ? THEN 1 ELSE 0 END) AS senior";
        if ($hasIndigenous) {
            $sql .= ", SUM(CASE WHEN indigenous = ? THEN 1 ELSE 0 END) AS indigenous";
        }
        $sql .= ' FROM residence_status WHERE archive = ?';

        $stmt = $con->prepare($sql);
        if ($stmt) {
            if ($hasIndigenous) {
                $stmt->bind_param('sssssss', $yes, $no, $yes, $yes, $yes, $yes, $no);
            } else {
                $stmt->bind_param('ssssss', $yes, $no, $yes, $yes, $yes, $no);
            }
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc() ?: [];
            $totals['population'] = (int) ($row['population'] ?? 0);
            $totals['voters'] = (int) ($row['voters'] ?? 0);
            $totals['non_voters'] = (int) ($row['non_voters'] ?? 0);
            $totals['pwd'] = (int) ($row['pwd'] ?? 0);
            $totals['single_parent'] = (int) ($row['single_parent'] ?? 0);
            $totals['senior'] = (int) ($row['senior'] ?? 0);
            if ($hasIndigenous) {
                $totals['indigenous'] = (int) ($row['indigenous'] ?? 0);
            }
            $stmt->close();
        }

        $totals['children'] = barangay_count_children($con);

        $result = $con->query('SELECT COUNT(*) AS total FROM blotter_record');
        $totals['blotter'] = (int) ($result ? $result->fetch_assoc()['total'] : 0);

        return $totals;
    }
}

if (!function_exists('barangay_super_dashboard_rows')) {
    /**
     * Per-barangay summary rows for the super admin dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    function barangay_super_dashboard_rows(mysqli $con): array
    {
        $populationByBarangay = [];
        $officialsByBarangay = [];
        $blotterByBarangay = [];
        $certificatesByBarangay = [];
        $certificatesIssuedByBarangay = [];
        $adminByBarangay = [];

        if (barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $result = $con->query(
                "SELECT barangay_id, COUNT(*) AS total
                 FROM residence_status
                 WHERE archive = 'NO' AND barangay_id IS NOT NULL AND barangay_id != ''
                 GROUP BY barangay_id"
            );
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $populationByBarangay[(string) $row['barangay_id']] = (int) ($row['total'] ?? 0);
                }
            }
        }

        if (barangay_column_exists($con, 'official_status', 'barangay_id')) {
            $result = $con->query(
                'SELECT barangay_id, COUNT(*) AS total FROM official_status GROUP BY barangay_id'
            );
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $officialsByBarangay[(string) $row['barangay_id']] = (int) ($row['total'] ?? 0);
                }
            }
        }

        if (barangay_column_exists($con, 'blotter_record', 'barangay_id')) {
            $result = $con->query(
                'SELECT barangay_id, COUNT(*) AS total FROM blotter_record GROUP BY barangay_id'
            );
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $blotterByBarangay[(string) $row['barangay_id']] = (int) ($row['total'] ?? 0);
                }
            }
        }

        if (barangay_table_exists($con, 'certificate_request')
            && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $result = $con->query(
                "SELECT rs.barangay_id,
                        COUNT(*) AS total,
                        SUM(CASE WHEN cr.status = 'ACCEPTED' THEN 1 ELSE 0 END) AS issued
                 FROM certificate_request cr
                 INNER JOIN residence_status rs ON cr.residence_id = rs.residence_id
                 WHERE rs.barangay_id IS NOT NULL AND rs.barangay_id != ''
                 GROUP BY rs.barangay_id"
            );
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $id = (string) $row['barangay_id'];
                    $certificatesByBarangay[$id] = (int) ($row['total'] ?? 0);
                    $certificatesIssuedByBarangay[$id] = (int) ($row['issued'] ?? 0);
                }
            }
        }

        if (barangay_column_exists($con, 'users', 'barangay_id')) {
            $result = $con->query(
                "SELECT barangay_id, username
                 FROM users
                 WHERE user_type = 'admin' AND barangay_id IS NOT NULL AND barangay_id != ''"
            );
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $adminByBarangay[(string) $row['barangay_id']] = $row['username'] ?? null;
                }
            }
        }

        $rows = [];
        foreach (barangay_list_all($con) as $barangay) {
            $id = (string) $barangay['id'];
            $rows[] = [
                'id' => $id,
                'barangay' => $barangay['barangay'],
                'zone' => $barangay['zone'],
                'district' => $barangay['district'],
                'logo' => barangay_logo_url($barangay, '../'),
                'population' => $populationByBarangay[$id] ?? 0,
                'officials' => $officialsByBarangay[$id] ?? 0,
                'blotter' => $blotterByBarangay[$id] ?? 0,
                'certificates' => $certificatesByBarangay[$id] ?? 0,
                'certificates_issued' => $certificatesIssuedByBarangay[$id] ?? 0,
                'admin_username' => $adminByBarangay[$id] ?? null,
            ];
        }

        return $rows;
    }
}

if (!function_exists('barangay_register_url')) {
    function barangay_register_url(string $barangayId): string
    {
        return 'register.php?barangay_id=' . urlencode($barangayId);
    }
}

if (!function_exists('barangay_resolve_registration')) {
    function barangay_resolve_registration(mysqli $con): ?array
    {
        $id = trim((string) ($_GET['barangay_id'] ?? ''));
        if ($id === '') {
            return null;
        }
        return barangay_load_by_id($con, $id);
    }
}

if (!function_exists('barangay_project_root')) {
    function barangay_project_root(): string
    {
        static $root = null;
        if ($root === null) {
            $root = dirname(__DIR__);
        }
        return $root;
    }
}

if (!function_exists('barangay_public_base')) {
    function barangay_public_base(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $projectRoot = rtrim(str_replace('\\', '/', barangay_project_root()), '/');
        if ($docRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
            $base = substr($projectRoot, strlen($docRoot));
            $base = rtrim($base, '/');
            return $base === '/' ? '' : $base;
        }
        return '';
    }
}

if (!function_exists('barangay_public_asset')) {
    function barangay_public_asset(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $base = barangay_public_base();
        return ($base !== '' ? $base . '/' : '') . $relativePath;
    }
}

if (!function_exists('barangay_default_logo_url')) {
    function barangay_default_logo_url(string $assetPrefix = ''): string
    {
        return $assetPrefix . 'assets/logo/valencia-city.png';
    }
}

if (!function_exists('barangay_user_avatar_url')) {
    /**
     * Resolve a usable admin/user avatar URL from image / image_path columns.
     */
    function barangay_user_avatar_url(?string $image, ?string $imagePath = null, string $assetPrefix = '../'): string
    {
        $image = trim((string) $image);
        $imagePath = trim((string) $imagePath);
        $candidates = [];

        if ($imagePath !== '') {
            if (str_starts_with($imagePath, '../')) {
                $candidates[] = $assetPrefix . substr($imagePath, 3);
            } elseif (str_starts_with($imagePath, 'assets/')) {
                $candidates[] = $assetPrefix . $imagePath;
            } elseif (preg_match('#^https?://#i', $imagePath)) {
                return $imagePath;
            } else {
                $candidates[] = $imagePath;
            }
        }

        if ($image !== '') {
            if (str_starts_with($image, 'assets/') || str_contains($image, '/')) {
                $candidates[] = $assetPrefix . ltrim($image, '/');
            } else {
                $candidates[] = $assetPrefix . 'assets/dist/img/' . $image;
                $candidates[] = $assetPrefix . 'assets/uploads/' . $image;
            }
        }

        foreach ($candidates as $url) {
            if (barangay_logo_file_exists($url, $assetPrefix)) {
                return $url;
            }
        }

        return '';
    }
}

if (!function_exists('barangay_logo_file_exists')) {
    function barangay_logo_file_exists(string $webPath, string $assetPrefix = ''): bool
    {
        $path = $webPath;
        if ($assetPrefix !== '' && str_starts_with($path, $assetPrefix)) {
            $path = substr($path, strlen($assetPrefix));
        }
        $path = preg_replace('#^\.\./+#', '', $path);
        $path = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        return is_file(barangay_project_root() . DIRECTORY_SEPARATOR . $path);
    }
}

if (!function_exists('barangay_logo_url')) {
    function barangay_logo_url(array $row, string $assetPrefix = '../'): string
    {
        $candidates = [];
        if (!empty($row['image_path'])) {
            $path = $row['image_path'];
            if (str_starts_with($path, '../')) {
                $candidates[] = $assetPrefix . substr($path, 3);
            } elseif (str_starts_with($path, 'assets/')) {
                $candidates[] = $assetPrefix . $path;
            } else {
                $candidates[] = $path;
            }
        }
        if (!empty($row['image'])) {
            $candidates[] = $assetPrefix . 'assets/dist/img/' . $row['image'];
        }

        $fallbacks = [
            barangay_default_logo_url($assetPrefix),
            $assetPrefix . 'assets/logo/black.png',
            $assetPrefix . 'assets/logo/blank.png',
        ];

        foreach (array_merge($candidates, $fallbacks) as $url) {
            if (barangay_logo_file_exists($url, $assetPrefix)) {
                return $url;
            }
        }

        return barangay_default_logo_url($assetPrefix);
    }
}

if (!function_exists('barangay_public_logo_url')) {
    function barangay_public_logo_url(?array $row = null): string
    {
        $relative = $row ? barangay_logo_url($row, '') : barangay_default_logo_url('');
        return barangay_public_asset(ltrim($relative, '/'));
    }
}

if (!function_exists('barangay_admin_logo_url')) {
    function barangay_admin_logo_url(array $row): string
    {
        return barangay_logo_url($row, '../');
    }
}

if (!function_exists('barangay_load_for_scope')) {
    function barangay_load_for_scope(mysqli $con, ?string $barangayId = null): ?array
    {
        if ($barangayId !== null && $barangayId !== '') {
            $row = barangay_load_by_id($con, $barangayId);
            if ($row) {
                return $row;
            }
        }
        return barangay_load_active($con);
    }
}

if (!function_exists('barangay_bind_id')) {
    function barangay_bind_id(mysqli_stmt $stmt, string $types, array $params, ?string $barangayId): void
    {
        if ($barangayId !== null) {
            $types .= 's';
            $params[] = $barangayId;
        }
        $stmt->bind_param($types, ...$params);
    }
}

if (!function_exists('barangay_admin_username_slug')) {
    function barangay_admin_username_slug(string $barangayName): string
    {
        $slug = strtolower($barangayName);
        $slug = preg_replace('/[^a-z0-9]+/', '', $slug);
        if ($slug === '') {
            $slug = 'barangay';
        }
        return $slug . '.admin';
    }
}

if (!function_exists('barangay_user_barangay_id')) {
    function barangay_user_barangay_id(mysqli $con, string $userId): ?string
    {
        static $cache = [];
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }
        if (!barangay_column_exists($con, 'users', 'barangay_id')) {
            $cache[$userId] = null;
            return null;
        }
        $stmt = $con->prepare('SELECT barangay_id FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            $cache[$userId] = null;
            return null;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $id = $row['barangay_id'] ?? null;
        if ($id === null || $id === '') {
            $cache[$userId] = null;
            return null;
        }
        $cache[$userId] = (string) $id;
        return $cache[$userId];
    }
}

if (!function_exists('barangay_resolve_scope_id')) {
    function barangay_resolve_scope_id(mysqli $con): ?string
    {
        $sessionId = barangay_session_id();
        if ($sessionId !== null) {
            return $sessionId;
        }
        if (isset($_SESSION['user_id'])) {
            return barangay_user_barangay_id($con, (string) $_SESSION['user_id']);
        }
        return null;
    }
}

if (!function_exists('barangay_enforce_staff_scope')) {
    function barangay_enforce_staff_scope(mysqli $con): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['user_type'])) {
            return;
        }
        if (!in_array($_SESSION['user_type'], ['admin', 'secretary'], true)) {
            return;
        }
        $barangayId = barangay_user_barangay_id($con, (string) $_SESSION['user_id']);
        if ($barangayId !== null) {
            barangay_set_active($barangayId);
        }
    }
}

if (!function_exists('barangay_enforce_admin_scope')) {
    function barangay_enforce_admin_scope(mysqli $con): void
    {
        barangay_enforce_staff_scope($con);
        barangay_enforce_admin_page_access($con);
        barangay_assert_request_scope($con);
    }
}

if (!function_exists('barangay_officials_where_clause')) {
    /**
     * @param array<int, string> $extra
     * @return array<int, string>
     */
    function barangay_officials_where_clause(mysqli $con, array $extra = [], string $statusTable = 'official_status'): array
    {
        $where = $extra;
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId !== null && barangay_column_exists($con, $statusTable, 'barangay_id')) {
            $where[] = $statusTable . ".barangay_id='" . $con->real_escape_string($scopeId) . "'";
        }
        return $where;
    }
}

if (!function_exists('barangay_end_officials_where_clause')) {
    /**
     * @param array<int, string> $extra
     * @return array<int, string>
     */
    function barangay_end_officials_where_clause(mysqli $con, array $extra = []): array
    {
        $where = $extra;
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId === null) {
            return $where;
        }

        if (barangay_column_exists($con, 'official_end_status', 'barangay_id')) {
            $where[] = "official_end_status.barangay_id='" . $con->real_escape_string($scopeId) . "'";
            return $where;
        }

        $barangayRow = barangay_load_by_id($con, $scopeId);
        if ($barangayRow) {
            $where[] = "official_end_information.barangay='" . $con->real_escape_string($barangayRow['barangay']) . "'";
        }

        return $where;
    }
}

if (!function_exists('barangay_sql_where')) {
    function barangay_sql_where(array $whereClause): string
    {
        if ($whereClause === []) {
            return '';
        }
        return ' WHERE ' . implode(' AND ', $whereClause);
    }
}

if (!function_exists('barangay_super_admin_only_scripts')) {
    /**
     * Admin scripts restricted to the system super administrator.
     *
     * @return array<int, string>
     */
    function barangay_super_admin_only_scripts(): array
    {
        return [
            'superDashboard.php',
            'barangayHub.php',
            'createBarangay.php',
            'createBarangayAdmin.php',
            'deleteBarangay.php',
            'selectBarangay.php',
            'updateBarangayLogo.php',
            'userAdministrator.php',
            'addAdministrator.php',
            'editUserAdministrator.php',
            'deleteUserAdministrator.php',
            'userTableAdministrator.php',
            'viewUserAdministrator.php',
            'backupRestore.php',
            'backup.php',
            'backupTable.php',
            'restore.php',
            'deleteFile.php',
            'systemLog.php',
            'systemLogsTable.php',
            'barangayCertificates.php',
            'barangayCertificateTable.php',
        ];
    }
}

if (!function_exists('barangay_require_super_admin')) {
    function barangay_require_super_admin(mysqli $con, string $redirect = 'dashboard.php'): void
    {
        if (!isset($_SESSION['user_id']) || !barangay_user_is_super_admin($con, (string) $_SESSION['user_id'])) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}

if (!function_exists('barangay_require_staff_account_manager')) {
    function barangay_require_staff_account_manager(mysqli $con, string $redirect = 'dashboard.php'): void
    {
        if (!isset($_SESSION['user_id']) || !barangay_user_can_manage_staff_accounts($con, (string) $_SESSION['user_id'])) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}

require_once __DIR__ . '/staff_permissions.php';

if (!function_exists('barangay_enforce_admin_page_access')) {
    function barangay_enforce_admin_page_access(mysqli $con): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            return;
        }

        $userId = (string) $_SESSION['user_id'];
        $staffRole = barangay_user_staff_role($con, $userId);
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $isHubPicker = $script === 'barangayHub.php'
            && isset($_GET['picker']) && $_GET['picker'] !== '' && $_GET['picker'] !== '0';
        $hubSystem = (isset($_GET['system']) && $_GET['system'] === 'nutrition') ? 'nutrition' : 'admin';
        $isNutritionHubPicker = $isHubPicker && $hubSystem === 'nutrition';

        // Nutrition Hub Super Admin (SA): Nutrition Hub only.
        if (barangay_user_is_nutrition_portal_admin($con, $userId)) {
            if ($script === 'myProfile.php') {
                header('Location: nutritionAccountProfile.php');
                exit;
            }
            if ($script === 'barangayHub.php') {
                if ($isNutritionHubPicker || (isset($_GET['system']) && $_GET['system'] === 'nutrition')) {
                    return;
                }
                header('Location: nutritionSuperDashboard.php');
                exit;
            }
            if ($script === 'selectBarangay.php') {
                return;
            }
            if (!barangay_nutrition_portal_admin_can_access_script($script)) {
                header('Location: nutritionSuperDashboard.php');
                exit;
            }
            if (!in_array($script, [
                'barangayHub.php', 'selectBarangay.php', 'nutritionAccountProfile.php',
                'nutritionSuperDashboard.php',
                'nutritionMellpiCityProfile.php', 'saveNutritionMellpiCityProfile.php',
                'nutritionSuperPrintReport.php', 'nutritionHubGuidePrint.php',
                'nutritionSuperPregnantFamiliesPrint.php',
                'nutritionProcessFormPrint.php', 'cityReportPack.php',
                'staffAccounts.php', 'saveStaffAccount.php', 'deleteStaffAccount.php',
                'staffAccountsTable.php', 'viewStaffAccount.php', 'resetStaffAccountPassword.php',
            ], true) && barangay_session_id() === null) {
                header('Location: nutritionSuperDashboard.php');
                exit;
            }
            return;
        }

        // Super Super Admin (SSA): both hubs, full access.
        if ($staffRole === STAFF_ROLE_SSA) {
            return;
        }

        // Barangay Hub Super Admin (SA): Barangay Hub only.
        if ($staffRole === STAFF_ROLE_SUPER_ADMIN) {
            if (in_array($script, barangay_barangay_hub_sa_denied_scripts(), true)) {
                header('Location: superDashboard.php');
                exit;
            }
            if ($isNutritionHubPicker) {
                header('Location: barangayHub.php?picker=1');
                exit;
            }
            return;
        }

        if ($staffRole === STAFF_ROLE_ADMIN) {
            if ($isNutritionHubPicker || ($script === 'barangayHub.php' && isset($_GET['system']) && $_GET['system'] === 'nutrition')) {
                header('Location: barangayHub.php?picker=1');
                exit;
            }
            if (in_array($script, barangay_city_admin_denied_scripts(), true)) {
                if ($isHubPicker || $script === 'selectBarangay.php') {
                    return;
                }
                header('Location: barangayHub.php?picker=1');
                exit;
            }
            if (!barangay_city_admin_can_access_script($script)) {
                header('Location: dashboard.php');
                exit;
            }
            if (!in_array($script, [
                'barangayHub.php', 'selectBarangay.php', 'myProfile.php',
            ], true)
                && barangay_session_id() === null) {
                header('Location: barangayHub.php?picker=1');
                exit;
            }
            return;
        }

        if ($staffRole === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN) {
            if (!barangay_bns_admin_can_access_script($script)) {
                if ($isHubPicker || $script === 'selectBarangay.php') {
                    return;
                }
                header('Location: nutritionSuperDashboard.php');
                exit;
            }
            $cityWide = function_exists('barangay_bns_admin_city_wide_scripts')
                ? barangay_bns_admin_city_wide_scripts()
                : ['barangayHub.php', 'selectBarangay.php', 'myProfile.php', 'nutritionSuperDashboard.php', 'nutritionAccountProfile.php'];
            if (!in_array($script, $cityWide, true) && barangay_session_id() === null) {
                $wantsJson = function_exists('barangay_request_expects_json')
                    ? barangay_request_expects_json()
                    : false;
                if ($wantsJson || (function_exists('barangay_bns_admin_support_scripts')
                    && in_array($script, barangay_bns_admin_support_scripts(), true))) {
                    http_response_code(400);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'No active barangay selected. Open a barangay first, then encode the household survey.']);
                    exit;
                }
                $picker = 'barangayHub.php?picker=1&system=nutrition&view=picker';
                if ($script !== '' && $script !== 'nutritionDashboard.php' && $script !== 'barangayHub.php') {
                    $picker .= '&next=' . rawurlencode($script);
                }
                header('Location: ' . $picker);
                exit;
            }
            return;
        }

        if ($staffRole === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR) {
            if (!barangay_bns_can_access_script($script)) {
                header('Location: nutritionDashboard.php');
                exit;
            }
            $assignedBarangayId = barangay_user_barangay_id($con, $userId);
            if ($assignedBarangayId !== null) {
                barangay_set_active($assignedBarangayId);
            }
            return;
        }

        if ($staffRole === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) {
            if ($script === 'myProfile.php') {
                header('Location: nutritionAccountProfile.php');
                exit;
            }
            if (!barangay_cnpc_can_access_script($script)) {
                header('Location: nutritionSuperDashboard.php');
                exit;
            }
            $assignedIds = function_exists('staff_assigned_barangay_ids')
                ? staff_assigned_barangay_ids($con, $userId)
                : [];
            if ($script === 'selectBarangay.php' || ($script === 'barangayHub.php' && $isNutritionHubPicker)) {
                return;
            }
            if (in_array($script, ['nutritionSuperDashboard.php', 'nutritionAccountProfile.php', 'saveProfile.php'], true)) {
                return;
            }
            $activeId = barangay_session_id();
            if ($activeId === null) {
                if (count($assignedIds) === 1) {
                    barangay_set_active($assignedIds[0]);
                } else {
                    header('Location: nutritionSuperDashboard.php');
                    exit;
                }
            } elseif ($assignedIds !== [] && !in_array($activeId, $assignedIds, true)) {
                barangay_clear_active();
                header('Location: nutritionSuperDashboard.php');
                exit;
            }
            return;
        }

        if (in_array($script, barangay_super_admin_only_scripts(), true)) {
            if ($isHubPicker || $script === 'selectBarangay.php') {
                return;
            }
            header('Location: dashboard.php');
            exit;
        }

        if (in_array($script, ['staffAccounts.php', 'saveStaffAccount.php', 'deleteStaffAccount.php', 'staffAccountsTable.php', 'viewStaffAccount.php', 'resetStaffAccountPassword.php'], true)) {
            header('Location: dashboard.php');
            exit;
        }

        $assignedBarangayId = barangay_user_barangay_id($con, $userId);
        if ($assignedBarangayId !== null) {
            barangay_set_active($assignedBarangayId);
        }
    }
}

if (!function_exists('barangay_residents_where_clause')) {
    /**
     * @param array<int, string> $extra
     * @return array<int, string>
     */
    function barangay_residents_where_clause(mysqli $con, array $extra = []): array
    {
        $where = $extra;
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId !== null && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $where[] = "residence_status.barangay_id='" . $con->real_escape_string($scopeId) . "'";
        }
        return $where;
    }
}

if (!function_exists('barangay_blotter_where_clause')) {
    /**
     * @param array<int, string> $extra
     * @return array<int, string>
     */
    function barangay_blotter_where_clause(mysqli $con, array $extra = []): array
    {
        $where = $extra;
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId !== null && barangay_column_exists($con, 'blotter_record', 'barangay_id')) {
            $where[] = "blotter_record.barangay_id='" . $con->real_escape_string($scopeId) . "'";
        }
        return $where;
    }
}

if (!function_exists('barangay_certificates_where_clause')) {
    /**
     * @param array<int, string> $extra
     * @return array<int, string>
     */
    function barangay_certificates_where_clause(mysqli $con, array $extra = []): array
    {
        $where = $extra;
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId !== null && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $escaped = $con->real_escape_string($scopeId);
            $where[] = "certificate_request.residence_id IN (SELECT residence_id FROM residence_status WHERE barangay_id = '$escaped')";
        }
        return $where;
    }
}

if (!function_exists('barangay_count_certificates')) {
    function barangay_count_certificates(mysqli $con, ?string $barangayId = null, ?string $status = null): int
    {
        if (!barangay_table_exists($con, 'certificate_request')) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS total FROM certificate_request';
        $where = [];

        if ($barangayId !== null && $barangayId !== '' && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $escaped = $con->real_escape_string($barangayId);
            $where[] = "certificate_request.residence_id IN (SELECT residence_id FROM residence_status WHERE barangay_id = '$escaped')";
        }

        if ($status !== null && $status !== '') {
            $where[] = "certificate_request.status = '" . $con->real_escape_string($status) . "'";
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $result = $con->query($sql);

        return (int) ($result ? $result->fetch_assoc()['total'] ?? 0 : 0);
    }
}

if (!function_exists('barangay_certificate_summary_rows')) {
    /**
     * Certificate totals per barangay for city-wide reports.
     *
     * @return array<int, array<string, mixed>>
     */
    function barangay_certificate_summary_rows(mysqli $con): array
    {
        $rows = [];

        foreach (barangay_list_all($con) as $barangay) {
            $id = (string) $barangay['id'];
            $rows[] = [
                'id' => $id,
                'barangay' => $barangay['barangay'],
                'zone' => $barangay['zone'],
                'logo' => barangay_logo_url($barangay, '../'),
                'total' => barangay_count_certificates($con, $id),
                'issued' => barangay_count_certificates($con, $id, 'ACCEPTED'),
                'pending' => barangay_count_certificates($con, $id, 'PENDING'),
                'rejected' => barangay_count_certificates($con, $id, 'REJECTED'),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => ($b['total'] <=> $a['total']) ?: strcasecmp((string) $a['barangay'], (string) $b['barangay']));

        return $rows;
    }
}

if (!function_exists('barangay_residence_barangay_id')) {
    function barangay_residence_barangay_id(mysqli $con, string $residenceId): ?string
    {
        if (!barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            return null;
        }
        $stmt = $con->prepare('SELECT barangay_id FROM residence_status WHERE residence_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $residenceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $id = $row['barangay_id'] ?? null;
        return ($id !== null && $id !== '') ? (string) $id : null;
    }
}

if (!function_exists('barangay_resolve_resident_barangay_id')) {
    function barangay_resolve_resident_barangay_id(mysqli $con, string $residenceId): ?string
    {
        $fromStatus = barangay_residence_barangay_id($con, $residenceId);
        if ($fromStatus !== null) {
            return $fromStatus;
        }

        if (barangay_column_exists($con, 'users', 'barangay_id')) {
            $stmt = $con->prepare("SELECT barangay_id FROM users WHERE id = ? AND user_type = 'resident' LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $residenceId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $id = $row['barangay_id'] ?? null;
                if ($id !== null && $id !== '') {
                    return (string) $id;
                }
            }
        }

        $stmt = $con->prepare('SELECT barangay FROM residence_information WHERE residence_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $residenceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $name = trim((string) ($row['barangay'] ?? ''));
        if ($name === '') {
            return null;
        }

        $lookup = $con->prepare('SELECT id FROM barangay_information WHERE barangay = ? LIMIT 1');
        if (!$lookup) {
            return null;
        }
        $lookup->bind_param('s', $name);
        $lookup->execute();
        $match = $lookup->get_result()->fetch_assoc();
        $id = $match['id'] ?? null;

        return ($id !== null && $id !== '') ? (string) $id : null;
    }
}

if (!function_exists('barangay_load_for_resident')) {
    function barangay_load_for_resident(mysqli $con, string $residenceId): ?array
    {
        $barangayId = barangay_resolve_resident_barangay_id($con, $residenceId);
        if ($barangayId === null) {
            return null;
        }

        return barangay_load_by_id($con, $barangayId);
    }
}

if (!function_exists('barangay_official_barangay_id')) {
    function barangay_official_barangay_id(mysqli $con, string $officialId): ?string
    {
        if (!barangay_column_exists($con, 'official_status', 'barangay_id')) {
            return null;
        }
        $stmt = $con->prepare('SELECT barangay_id FROM official_status WHERE official_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $officialId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $id = $row['barangay_id'] ?? null;
        return ($id !== null && $id !== '') ? (string) $id : null;
    }
}

if (!function_exists('barangay_blotter_barangay_id')) {
    function barangay_blotter_barangay_id(mysqli $con, string $blotterId): ?string
    {
        if (!barangay_column_exists($con, 'blotter_record', 'barangay_id')) {
            return null;
        }
        $stmt = $con->prepare('SELECT barangay_id FROM blotter_record WHERE blotter_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $blotterId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $id = $row['barangay_id'] ?? null;
        return ($id !== null && $id !== '') ? (string) $id : null;
    }
}

if (!function_exists('barangay_deny_scope_access')) {
    function barangay_deny_scope_access(string $message = 'Access denied'): void
    {
        http_response_code(403);
        exit($message);
    }
}

if (!function_exists('barangay_assert_barangay_in_scope')) {
    function barangay_assert_barangay_in_scope(mysqli $con, string $barangayId): void
    {
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId === null) {
            return;
        }
        if ($barangayId !== $scopeId) {
            barangay_deny_scope_access();
        }
    }
}

if (!function_exists('barangay_assert_residence_in_scope')) {
    function barangay_assert_residence_in_scope(mysqli $con, string $residenceId): void
    {
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId === null) {
            return;
        }
        $ownerId = barangay_residence_barangay_id($con, $residenceId);
        if ($ownerId === null || $ownerId !== $scopeId) {
            barangay_deny_scope_access();
        }
    }
}

if (!function_exists('barangay_assert_official_in_scope')) {
    function barangay_assert_official_in_scope(mysqli $con, string $officialId): void
    {
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId === null) {
            return;
        }
        $ownerId = barangay_official_barangay_id($con, $officialId);
        if ($ownerId === null || $ownerId !== $scopeId) {
            barangay_deny_scope_access();
        }
    }
}

if (!function_exists('barangay_assert_blotter_in_scope')) {
    function barangay_assert_blotter_in_scope(mysqli $con, string $blotterId): void
    {
        $scopeId = barangay_resolve_scope_id($con);
        if ($scopeId === null) {
            return;
        }
        $ownerId = barangay_blotter_barangay_id($con, $blotterId);
        if ($ownerId === null || $ownerId !== $scopeId) {
            barangay_deny_scope_access();
        }
    }
}

if (!function_exists('barangay_assert_request_scope')) {
    function barangay_assert_request_scope(mysqli $con): void
    {
        foreach (['residence_id', 'resident_id', 'status_residence'] as $key) {
            $id = trim((string) ($_REQUEST[$key] ?? ''));
            if ($id !== '') {
                barangay_assert_residence_in_scope($con, $id);
            }
        }

        foreach (['official_id', 'official_id_end', 'request'] as $key) {
            // "request" is a legacy alias used by viewOfficial.php
            $id = trim((string) ($_REQUEST[$key] ?? ''));
            if ($id !== '' && $key === 'request' && isset($_REQUEST['residence_id'])) {
                continue;
            }
            if ($id !== '') {
                // Only treat "request" as official_id on official view endpoints
                if ($key === 'request') {
                    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
                    if (!preg_match('/official/i', $script)) {
                        continue;
                    }
                }
                barangay_assert_official_in_scope($con, $id);
            }
        }

        foreach (['blotter_id', 'record_id', 'id'] as $key) {
            $id = trim((string) ($_REQUEST[$key] ?? ''));
            if ($id === '') {
                continue;
            }
            if ($key === 'id') {
                $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
                if (!preg_match('/blotter|Blotter|Record/i', $script)) {
                    continue;
                }
            }
            barangay_assert_blotter_in_scope($con, $id);
        }

        $barangayId = trim((string) ($_REQUEST['barangay_id'] ?? ''));
        if ($barangayId !== '') {
            barangay_assert_barangay_in_scope($con, $barangayId);
        }
    }
}

if (!function_exists('barangay_load_admin_account')) {
    /**
     * @return array{id:string,username:string,barangay_id:string}|null
     */
    function barangay_load_admin_account(mysqli $con, string $barangayId): ?array
    {
        if (!barangay_column_exists($con, 'users', 'barangay_id')) {
            return null;
        }

        $stmt = $con->prepare("SELECT id, username, barangay_id FROM users WHERE user_type = 'admin' AND barangay_id = ? LIMIT 1");
        $stmt->bind_param('s', $barangayId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return null;
        }

        return [
            'id' => (string) $row['id'],
            'username' => (string) $row['username'],
            'barangay_id' => (string) $row['barangay_id'],
        ];
    }
}

if (!function_exists('barangay_update_admin_password')) {
    function barangay_update_admin_password(mysqli $con, string $userId, string $plainPassword): bool
    {
        require_once __DIR__ . '/helpers.php';

        $hash = barangay_hash_password($plainPassword);
        $stmt = $con->prepare('UPDATE users SET password = ? WHERE id = ? AND user_type = ?');
        $userType = 'admin';
        $stmt->bind_param('sss', $hash, $userId, $userType);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}

if (!function_exists('barangay_create_admin_for_barangay')) {
    /**
     * Create a barangay-scoped admin account. Returns account info or null if one already exists.
     */
    function barangay_create_admin_for_barangay(
        mysqli $con,
        string $barangayId,
        string $barangayName,
        string $defaultPassword = ''
    ): ?array {
        if (!barangay_column_exists($con, 'users', 'barangay_id')) {
            return null;
        }

        $check = $con->prepare("SELECT id, username FROM users WHERE user_type = 'admin' AND barangay_id = ? LIMIT 1");
        $check->bind_param('s', $barangayId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        if ($existing) {
            return null;
        }

        require_once __DIR__ . '/helpers.php';
        if ($defaultPassword === '') {
            $defaultPassword = 'Vc!' . bin2hex(random_bytes(5)) . 'A9';
        }

        $baseUsername = barangay_admin_username_slug($barangayName);
        $username = $baseUsername;
        $suffix = 1;
        while (true) {
            $uCheck = $con->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $uCheck->bind_param('s', $username);
            $uCheck->execute();
            if ($uCheck->get_result()->num_rows === 0) {
                break;
            }
            $suffix++;
            $username = $baseUsername . $suffix;
        }

        $userId = (string) hexdec(uniqid());
        $firstName = 'Admin';
        $middleName = '';
        $lastName = $barangayName;
        $password = barangay_hash_password($defaultPassword);
        $userType = 'admin';
        $contact = '09000000000';
        $image = '';
        $imagePath = '';

        $stmt = $con->prepare(
            'INSERT INTO users (id, first_name, middle_name, last_name, username, password, user_type, contact_number, image, image_path, barangay_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssssssss',
            $userId,
            $firstName,
            $middleName,
            $lastName,
            $username,
            $password,
            $userType,
            $contact,
            $image,
            $imagePath,
            $barangayId
        );
        $stmt->execute();

        return [
            'user_id' => $userId,
            'username' => $username,
            'password' => $defaultPassword,
            'barangay' => $barangayName,
        ];
    }
}

if (!function_exists('barangay_secretary_username_slug')) {
    function barangay_secretary_username_slug(string $barangayName): string
    {
        $slug = strtolower($barangayName);
        $slug = preg_replace('/[^a-z0-9]+/', '', $slug);
        if ($slug === '') {
            $slug = 'barangay';
        }

        return $slug . '.secretary';
    }
}

if (!function_exists('barangay_certificate_header')) {
    /**
     * Standard certificate/document header lines for Valencia City barangays.
     *
     * @return array{country:string,province:string,city:string,barangay_line:string,barangay_name:string,done_in:string,office:string}
     */
    function barangay_certificate_header(array $row): array
    {
        $name = trim((string) ($row['barangay'] ?? ''));

        return [
            'country' => 'Republic of the Philippines',
            'province' => 'Province of Bukidnon',
            'city' => 'City of Valencia',
            'barangay_line' => $name !== '' ? 'Barangay ' . $name : 'Barangay',
            'barangay_name' => $name,
            'done_in' => 'City of Valencia',
            'office' => 'OFFICE OF THE BARANGAY CHAIRMAN',
        ];
    }
}

if (!function_exists('barangay_certificate_location_label')) {
    function barangay_certificate_location_label(array $row): string
    {
        $header = barangay_certificate_header($row);

        return $header['barangay_line'] . ', ' . $header['city'];
    }
}

if (!function_exists('barangay_load_secretary_account')) {
    /**
     * @return array{id:string,username:string,barangay_id:string}|null
     */
    function barangay_load_secretary_account(mysqli $con, string $barangayId): ?array
    {
        if (!barangay_column_exists($con, 'users', 'barangay_id')) {
            return null;
        }

        $stmt = $con->prepare("SELECT id, username, barangay_id FROM users WHERE user_type = 'secretary' AND barangay_id = ? LIMIT 1");
        $stmt->bind_param('s', $barangayId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return null;
        }

        return [
            'id' => (string) $row['id'],
            'username' => (string) $row['username'],
            'barangay_id' => (string) $row['barangay_id'],
        ];
    }
}

if (!function_exists('barangay_update_secretary_password')) {
    function barangay_update_secretary_password(mysqli $con, string $userId, string $plainPassword): bool
    {
        require_once __DIR__ . '/helpers.php';

        $hash = barangay_hash_password($plainPassword);
        $stmt = $con->prepare('UPDATE users SET password = ? WHERE id = ? AND user_type = ?');
        $userType = 'secretary';
        $stmt->bind_param('sss', $hash, $userId, $userType);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}

if (!function_exists('barangay_create_secretary_for_barangay')) {
    /**
     * Create a barangay-scoped secretary account. Returns account info or null if one already exists.
     */
    function barangay_create_secretary_for_barangay(
        mysqli $con,
        string $barangayId,
        string $barangayName,
        string $defaultPassword = ''
    ): ?array {
        if (!barangay_column_exists($con, 'users', 'barangay_id')) {
            return null;
        }

        $check = $con->prepare("SELECT id, username FROM users WHERE user_type = 'secretary' AND barangay_id = ? LIMIT 1");
        $check->bind_param('s', $barangayId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        if ($existing) {
            return null;
        }

        require_once __DIR__ . '/helpers.php';
        if ($defaultPassword === '') {
            $defaultPassword = 'Vc!' . bin2hex(random_bytes(5)) . 'A9';
        }

        $baseUsername = barangay_secretary_username_slug($barangayName);
        $username = $baseUsername;
        $suffix = 1;
        while (true) {
            $uCheck = $con->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $uCheck->bind_param('s', $username);
            $uCheck->execute();
            if ($uCheck->get_result()->num_rows === 0) {
                break;
            }
            $suffix++;
            $username = $baseUsername . $suffix;
        }

        $userId = (string) hexdec(uniqid());
        $firstName = 'Secretary';
        $middleName = '';
        $lastName = $barangayName;
        $password = barangay_hash_password($defaultPassword);
        $userType = 'secretary';
        $contact = '09000000000';
        $image = '';
        $imagePath = '';

        $stmt = $con->prepare(
            'INSERT INTO users (id, first_name, middle_name, last_name, username, password, user_type, contact_number, image, image_path, barangay_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssssssss',
            $userId,
            $firstName,
            $middleName,
            $lastName,
            $username,
            $password,
            $userType,
            $contact,
            $image,
            $imagePath,
            $barangayId
        );
        $stmt->execute();

        return [
            'user_id' => $userId,
            'username' => $username,
            'password' => $defaultPassword,
            'barangay' => $barangayName,
        ];
    }
}

if (!function_exists('barangay_standard_positions')) {
    /**
     * Default barangay council structure (per barangay).
     *
     * @return array<int, array{position: string, limit: int, description: string, color: string}>
     */
    function barangay_standard_positions(): array
    {
        return [
            ['position' => 'chairman', 'limit' => 1, 'description' => 'Barangay Chairman', 'color' => '#4fb42e'],
            ['position' => 'kagawad', 'limit' => 7, 'description' => 'Barangay Kagawad', 'color' => '#50d425'],
            ['position' => 'ip representative', 'limit' => 1, 'description' => 'Indigenous Peoples Representative', 'color' => '#8b5cf6'],
            ['position' => 'sk chairman', 'limit' => 1, 'description' => 'SK Chairman', 'color' => '#2563eb'],
            ['position' => 'sk kagawad', 'limit' => 7, 'description' => 'SK Kagawad', 'color' => '#3bc173'],
        ];
    }
}

if (!function_exists('barangay_punong_barangay_name')) {
    /**
     * Punong Barangay / Barangay Chairman name from Barangay Hub Officials
     * for a specific barangay (same source as allOfficial / certificates).
     */
    function barangay_punong_barangay_name(mysqli $con, string $barangayId = '', string $barangayName = ''): string
    {
        $barangayId = trim($barangayId);
        $barangayName = trim($barangayName);
        if ($barangayId === '') {
            $barangayId = (string) (barangay_session_id() ?? '');
        }
        if ($barangayName === '' && $barangayId !== '') {
            $row = barangay_load_by_id($con, $barangayId);
            $barangayName = trim((string) ($row['barangay'] ?? ''));
        }
        if ($barangayId === '' && $barangayName === '') {
            return '';
        }

        $hasOfficialScope = barangay_column_exists($con, 'official_status', 'barangay_id');
        $hasInfoBarangay = barangay_column_exists($con, 'official_information', 'barangay');

        // Prefer ACTIVE chairman for this barangay, then any chairman record.
        $statusFilters = ['ACTIVE', ''];
        foreach ($statusFilters as $statusFilter) {
            $sql = "SELECT oi.first_name, oi.middle_name, oi.last_name
                    FROM official_status os
                    INNER JOIN official_information oi ON oi.official_id = os.official_id
                    INNER JOIN position p ON p.position_id = os.position
                    WHERE LOWER(p.position) = 'chairman'";
            $types = '';
            $params = [];

            if ($statusFilter !== '') {
                $sql .= ' AND UPPER(os.status) = ?';
                $types .= 's';
                $params[] = $statusFilter;
            }

            if ($hasOfficialScope && $barangayId !== '') {
                $sql .= ' AND os.barangay_id = ?';
                $types .= 's';
                $params[] = $barangayId;
            } elseif ($hasInfoBarangay && $barangayName !== '') {
                $sql .= ' AND oi.barangay = ?';
                $types .= 's';
                $params[] = $barangayName;
            } elseif ($barangayId === '' && $barangayName === '') {
                // no scope — skip
                continue;
            }

            $sql .= ' ORDER BY os.a_i ASC LIMIT 1';

            $stmt = $con->prepare($sql);
            if (!$stmt) {
                continue;
            }
            if ($types !== '') {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) {
                continue;
            }

            $first = trim((string) ($row['first_name'] ?? ''));
            $middle = trim((string) ($row['middle_name'] ?? ''));
            $last = trim((string) ($row['last_name'] ?? ''));
            $middleInitial = $middle !== '' ? strtoupper(mb_substr($middle, 0, 1)) . '. ' : '';
            $name = trim($first . ' ' . $middleInitial . $last);
            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }
}

if (!function_exists('barangay_count_officials_for_position')) {
    function barangay_count_officials_for_position(
        mysqli $con,
        string $positionId,
        string $barangayId,
        ?string $excludeOfficialId = null
    ): int {
        $hasBarangayScope = barangay_column_exists($con, 'official_status', 'barangay_id');
        $sql = 'SELECT COUNT(*) AS total FROM official_status WHERE position = ?';
        $types = 's';
        $params = [$positionId];

        if ($hasBarangayScope && $barangayId !== '') {
            $sql .= ' AND barangay_id = ?';
            $types .= 's';
            $params[] = $barangayId;
        }

        if ($excludeOfficialId !== null && $excludeOfficialId !== '') {
            $sql .= ' AND official_id != ?';
            $types .= 's';
            $params[] = $excludeOfficialId;
        }

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('official_term_year_value')) {
    function official_term_year_value(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{4}$/', $value)) {
            return $value;
        }
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y', $timestamp);
        }

        return $value;
    }
}

if (!function_exists('official_validate_term_years')) {
    function official_validate_term_years(string $startYear, string $endYear): bool
    {
        $start = official_term_year_value($startYear);
        $end = official_term_year_value($endYear);
        if (!preg_match('/^\d{4}$/', $start) || !preg_match('/^\d{4}$/', $end)) {
            return false;
        }

        $startInt = (int) $start;
        $endInt = (int) $end;
        if ($startInt < 1900 || $startInt > 2100 || $endInt < 1900 || $endInt > 2100) {
            return false;
        }

        return $endInt >= $startInt;
    }
}

if (!function_exists('official_term_range_display')) {
    function official_term_range_display(?string $from, ?string $to): string
    {
        $fromYear = official_term_year_value($from);
        $toYear = official_term_year_value($to);
        if ($fromYear === '' && $toYear === '') {
            return '';
        }
        if ($fromYear === '') {
            return $toYear;
        }
        if ($toYear === '') {
            return $fromYear;
        }

        return $fromYear . '-' . $toYear;
    }
}

if (!function_exists('official_parse_term_range')) {
    /**
     * @return array{from:string,to:string}
     */
    function official_parse_term_range(string $value): array
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $value, $matches)) {
            return [
                'from' => $matches[1],
                'to' => $matches[2],
            ];
        }

        return ['from' => '', 'to' => ''];
    }
}

if (!function_exists('official_validate_term_range')) {
    function official_validate_term_range(string $value): bool
    {
        $range = official_parse_term_range($value);

        return official_validate_term_years($range['from'], $range['to']);
    }
}

if (!function_exists('barangay_get_position_row')) {
    function barangay_get_position_row(mysqli $con, string $positionId): ?array
    {
        $stmt = $con->prepare('SELECT position_id, position, position_limit, position_description FROM position WHERE position_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $positionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ?: null;
    }
}

if (!function_exists('barangay_position_limit_reached')) {
    function barangay_position_limit_reached(
        mysqli $con,
        string $positionId,
        string $barangayId,
        ?string $excludeOfficialId = null
    ): bool {
        $positionRow = barangay_get_position_row($con, $positionId);
        if (!$positionRow) {
            return true;
        }

        $limit = (int) $positionRow['position_limit'];
        $count = barangay_count_officials_for_position($con, $positionId, $barangayId, $excludeOfficialId);

        return $count >= $limit;
    }
}

if (!function_exists('barangay_positions_with_usage')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function barangay_positions_with_usage(mysqli $con, ?string $barangayId): array
    {
        $positions = [];
        $sql = "SELECT position_id, position, position_limit, position_description
                FROM position
                ORDER BY FIELD(LOWER(position), 'chairman', 'kagawad', 'ip representative', 'sk chairman', 'sk kagawad'), position";
        $result = $con->query($sql);
        if (!$result) {
            return $positions;
        }

        while ($row = $result->fetch_assoc()) {
            $count = $barangayId
                ? barangay_count_officials_for_position($con, $row['position_id'], $barangayId)
                : 0;
            $limit = (int) $row['position_limit'];
            $row['filled'] = $count;
            $row['available'] = max(0, $limit - $count);
            $row['is_full'] = $count >= $limit;
            $positions[] = $row;
        }

        return $positions;
    }
}

if (!function_exists('barangay_count_linked_records')) {
    /**
     * Count operational records tied to a barangay (excludes barangay-scoped admin accounts).
     *
     * @return array{residents:int,officials:int,blotter:int,purok:int,residents_users:int,total:int}
     */
    function barangay_count_linked_records(mysqli $con, string $barangayId): array
    {
        $counts = [
            'residents' => 0,
            'officials' => 0,
            'blotter' => 0,
            'purok' => 0,
            'residents_users' => 0,
            'total' => 0,
        ];

        if (barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM residence_status WHERE barangay_id = ?");
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $counts['residents'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }

        if (barangay_column_exists($con, 'official_status', 'barangay_id')) {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM official_status WHERE barangay_id = ?");
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $counts['officials'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }

        if (barangay_column_exists($con, 'blotter_record', 'barangay_id')) {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM blotter_record WHERE barangay_id = ?");
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $counts['blotter'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }

        if (barangay_column_exists($con, 'purok', 'barangay_id')) {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM purok WHERE barangay_id = ?");
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $counts['purok'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }

        if (barangay_column_exists($con, 'users', 'barangay_id')) {
            $stmt = $con->prepare("SELECT COUNT(*) AS total FROM users WHERE barangay_id = ? AND user_type = 'resident'");
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $counts['residents_users'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }

        $counts['total'] = $counts['residents']
            + $counts['officials']
            + $counts['blotter']
            + $counts['purok']
            + $counts['residents_users'];

        return $counts;
    }
}

if (!function_exists('barangay_table_exists')) {
    function barangay_table_exists(mysqli $con, string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }
        $stmt = $con->prepare('SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        if (!$stmt) {
            $cache[$table] = false;
            return false;
        }
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $cache[$table] = ((int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0)) > 0;
        return $cache[$table];
    }
}

if (!function_exists('barangay_delete_where')) {
    function barangay_delete_where(mysqli $con, string $sql, string $bindValue): void
    {
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $bindValue);
        $stmt->execute();
    }
}

if (!function_exists('barangay_purge_linked_records')) {
    /**
     * Permanently remove all operational records tied to a barangay.
     */
    function barangay_purge_linked_records(mysqli $con, string $barangayId, string $barangayName): void
    {
        if (barangay_column_exists($con, 'blotter_record', 'barangay_id')) {
            barangay_delete_where(
                $con,
                'DELETE bc FROM blotter_complainant bc
                 INNER JOIN blotter_record br ON bc.blotter_main = br.blotter_id
                 WHERE br.barangay_id = ?',
                $barangayId
            );
            barangay_delete_where(
                $con,
                'DELETE bs FROM blotter_status bs
                 INNER JOIN blotter_record br ON bs.blotter_main = br.blotter_id
                 WHERE br.barangay_id = ?',
                $barangayId
            );
            if (barangay_table_exists($con, 'blotter_info')) {
                barangay_delete_where(
                    $con,
                    'DELETE bi FROM blotter_info bi
                     INNER JOIN blotter_record br ON bi.blotter_main_id = br.blotter_id
                     WHERE br.barangay_id = ?',
                    $barangayId
                );
            }
            barangay_delete_where($con, 'DELETE FROM blotter_record WHERE barangay_id = ?', $barangayId);
        }

        if (barangay_column_exists($con, 'official_status', 'barangay_id')) {
            barangay_delete_where(
                $con,
                'DELETE oei FROM official_end_information oei
                 INNER JOIN official_status os ON oei.official_id = os.official_id
                 WHERE os.barangay_id = ?',
                $barangayId
            );
            barangay_delete_where(
                $con,
                'DELETE oes FROM official_end_status oes
                 INNER JOIN official_status os ON oes.official_id = os.official_id
                 WHERE os.barangay_id = ?',
                $barangayId
            );
            barangay_delete_where(
                $con,
                'DELETE oi FROM official_information oi
                 INNER JOIN official_status os ON oi.official_id = os.official_id
                 WHERE os.barangay_id = ?',
                $barangayId
            );
            barangay_delete_where($con, 'DELETE FROM official_status WHERE barangay_id = ?', $barangayId);
        }

        if (barangay_column_exists($con, 'official_end_status', 'barangay_id')) {
            barangay_delete_where(
                $con,
                'DELETE oei FROM official_end_information oei
                 INNER JOIN official_end_status oes ON oei.official_id = oes.official_id
                 WHERE oes.barangay_id = ?',
                $barangayId
            );
            barangay_delete_where($con, 'DELETE FROM official_end_status WHERE barangay_id = ?', $barangayId);
        } elseif ($barangayName !== '') {
            barangay_delete_where(
                $con,
                'DELETE oes FROM official_end_status oes
                 INNER JOIN official_end_information oei ON oes.official_id = oei.official_id
                 WHERE oei.barangay = ?',
                $barangayName
            );
            barangay_delete_where(
                $con,
                'DELETE FROM official_end_information WHERE barangay = ?',
                $barangayName
            );
        }

        if (barangay_column_exists($con, 'residence_status', 'barangay_id')) {
            if (barangay_table_exists($con, 'certificate')) {
                barangay_delete_where(
                    $con,
                    'DELETE c FROM certificate c
                     INNER JOIN residence_status rs ON c.residence_id = rs.residence_id
                     WHERE rs.barangay_id = ?',
                    $barangayId
                );
            }
            if (barangay_table_exists($con, 'certificate_request')) {
                barangay_delete_where(
                    $con,
                    'DELETE cr FROM certificate_request cr
                     INNER JOIN residence_status rs ON cr.residence_id = rs.residence_id
                     WHERE rs.barangay_id = ?',
                    $barangayId
                );
            }
            if (barangay_table_exists($con, 'residence_dependents')) {
                barangay_delete_where(
                    $con,
                    'DELETE rd FROM residence_dependents rd
                     INNER JOIN residence_status rs ON rd.residence_id = rs.residence_id
                     WHERE rs.barangay_id = ?',
                    $barangayId
                );
            }
            if (barangay_table_exists($con, 'vaccine')) {
                barangay_delete_where(
                    $con,
                    'DELETE v FROM vaccine v
                     INNER JOIN residence_status rs ON v.residence_id = rs.residence_id
                     WHERE rs.barangay_id = ?',
                    $barangayId
                );
            }
            if (barangay_table_exists($con, 'wra')) {
                barangay_delete_where(
                    $con,
                    'DELETE w FROM wra w
                     INNER JOIN residence_status rs ON w.residence_id = rs.residence_id
                     WHERE rs.barangay_id = ?',
                    $barangayId
                );
            }
            if (barangay_table_exists($con, 'house_holds')) {
                barangay_delete_where(
                    $con,
                    'DELETE hh FROM house_holds hh
                     INNER JOIN residence_status rs ON hh.residence_id = rs.residence_id
                     WHERE rs.barangay_id = ?',
                    $barangayId
                );
            }
            barangay_delete_where(
                $con,
                'DELETE u FROM users u
                 INNER JOIN residence_status rs ON u.id = rs.residence_id
                 WHERE rs.barangay_id = ?',
                $barangayId
            );
            barangay_delete_where(
                $con,
                'DELETE ri FROM residence_information ri
                 INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
                 WHERE rs.barangay_id = ?',
                $barangayId
            );
            barangay_delete_where($con, 'DELETE FROM residence_status WHERE barangay_id = ?', $barangayId);
        }

        if (barangay_column_exists($con, 'purok', 'barangay_id')) {
            if (barangay_table_exists($con, 'house_holds')) {
                barangay_delete_where(
                    $con,
                    'DELETE hh FROM house_holds hh
                     INNER JOIN purok p ON hh.purok_id = p.purok_id
                     WHERE p.barangay_id = ?',
                    $barangayId
                );
            }
            barangay_delete_where($con, 'DELETE FROM purok WHERE barangay_id = ?', $barangayId);
        }

        if (barangay_column_exists($con, 'users', 'barangay_id')) {
            barangay_delete_where($con, 'DELETE FROM users WHERE barangay_id = ?', $barangayId);
        }
    }
}

if (!function_exists('barangay_delete')) {
    /**
     * Delete a barangay and all linked operational records.
     *
     * @return array{ok:bool,error?:string,barangay?:string,linked?:array}
     */
    function barangay_delete(mysqli $con, string $barangayId): array
    {
        $row = barangay_load_by_id($con, $barangayId);
        if ($row === null) {
            return ['ok' => false, 'error' => 'Barangay not found.'];
        }

        $linked = barangay_count_linked_records($con, $barangayId);
        $barangayName = (string) ($row['barangay'] ?? '');

        $con->begin_transaction();
        try {
            barangay_purge_linked_records($con, $barangayId, $barangayName);

            $stmt = $con->prepare('DELETE FROM barangay_information WHERE id = ?');
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();

            $con->commit();
        } catch (Throwable $e) {
            $con->rollback();
            return [
                'ok' => false,
                'error' => 'Could not delete barangay. Please try again.',
                'linked' => $linked,
            ];
        }

        if (barangay_session_id() === $barangayId) {
            barangay_clear_active();
        }

        return ['ok' => true, 'barangay' => $barangayName, 'linked' => $linked];
    }
}
