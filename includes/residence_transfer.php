<?php

/**
 * Resident transfer between barangays.
 *
 * The old record stays in the source barangay (archived with reason TRANSFERRED) so
 * certificates, blotter cases and submitted nutrition reports keep their history.
 * A new record with a destination-barangay resident number is created and linked
 * through `residence_transfer`.
 */

require_once __DIR__ . '/barangay_context.php';
require_once __DIR__ . '/staff_permissions.php';
require_once __DIR__ . '/residence_family.php';
require_once __DIR__ . '/audit_log.php';

if (!defined('RESIDENCE_TRANSFER_PENDING')) {
    define('RESIDENCE_TRANSFER_PENDING', 'pending');
}
if (!defined('RESIDENCE_TRANSFER_APPROVED')) {
    define('RESIDENCE_TRANSFER_APPROVED', 'approved');
}
if (!defined('RESIDENCE_TRANSFER_REJECTED')) {
    define('RESIDENCE_TRANSFER_REJECTED', 'rejected');
}
if (!defined('RESIDENCE_TRANSFER_CANCELLED')) {
    define('RESIDENCE_TRANSFER_CANCELLED', 'cancelled');
}

if (!function_exists('residence_archive_reasons')) {
    /**
     * @return array<string, string>
     */
    function residence_archive_reasons(): array
    {
        return [
            'TRANSFERRED' => 'Transferred to another barangay',
            'MOVED_OUT_OF_CITY' => 'Moved out of the city',
            'DECEASED' => 'Deceased',
            'DUPLICATE' => 'Duplicate record',
            'OTHER' => 'Other',
        ];
    }
}

if (!function_exists('residence_archive_reason_label')) {
    function residence_archive_reason_label(string $code): string
    {
        $reasons = residence_archive_reasons();

        return $reasons[strtoupper(trim($code))] ?? '';
    }
}

if (!function_exists('residence_archive_set_reason')) {
    /** Store why a resident was archived. TRANSFERRED is reserved for the transfer flow. */
    function residence_archive_set_reason(mysqli $con, string $residenceId, string $reason): void
    {
        residence_transfer_ensure_schema($con);
        $reason = strtoupper(trim($reason));
        if ($reason === 'TRANSFERRED' || !array_key_exists($reason, residence_archive_reasons())) {
            $reason = 'OTHER';
        }
        if (!barangay_column_exists($con, 'residence_status', 'archive_reason')) {
            return;
        }
        $stmt = $con->prepare('UPDATE residence_status SET archive_reason = ? WHERE residence_id = ?');
        if ($stmt) {
            $stmt->bind_param('ss', $reason, $residenceId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('residence_archive_clear_reason')) {
    function residence_archive_clear_reason(mysqli $con, string $residenceId): void
    {
        if (!barangay_column_exists($con, 'residence_status', 'archive_reason')) {
            return;
        }
        $stmt = $con->prepare("UPDATE residence_status SET archive_reason = '' WHERE residence_id = ?");
        if ($stmt) {
            $stmt->bind_param('s', $residenceId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('residence_transfer_block_unarchive_or_exit')) {
    /**
     * A transferred record must stay archived, or the person would be active in two barangays.
     */
    function residence_transfer_block_unarchive_or_exit(mysqli $con, string $residenceId): void
    {
        $link = residence_transfer_links_for($con, [$residenceId])[$residenceId] ?? null;
        if ($link === null || $link['direction'] !== 'to') {
            return;
        }
        http_response_code(409);
        exit('This resident was transferred to ' . $link['barangay'] . ' (' . $link['other_id']
            . '). The old record stays archived as history and cannot be restored.');
    }
}

if (!function_exists('residence_transfer_ensure_schema')) {
    function residence_transfer_ensure_schema(mysqli $con): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!barangay_table_exists($con, 'residence_transfer')) {
            $con->query(
                "CREATE TABLE IF NOT EXISTS `residence_transfer` (
                    `id` VARCHAR(32) NOT NULL,
                    `batch_id` VARCHAR(32) NOT NULL DEFAULT '',
                    `residence_id_old` VARCHAR(255) NOT NULL,
                    `residence_id_new` VARCHAR(255) NOT NULL DEFAULT '',
                    `person_name` VARCHAR(255) NOT NULL DEFAULT '',
                    `from_barangay_id` VARCHAR(255) NOT NULL,
                    `to_barangay_id` VARCHAR(255) NOT NULL,
                    `transfer_date` DATE NOT NULL,
                    `reason` VARCHAR(255) NOT NULL DEFAULT '',
                    `new_house_number` VARCHAR(69) NOT NULL DEFAULT '',
                    `new_street` VARCHAR(69) NOT NULL DEFAULT '',
                    `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
                    `requested_by` VARCHAR(255) NOT NULL DEFAULT '',
                    `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `transferred_by` VARCHAR(255) NOT NULL DEFAULT '',
                    `decided_at` DATETIME NULL DEFAULT NULL,
                    `decision_note` VARCHAR(255) NOT NULL DEFAULT '',
                    PRIMARY KEY (`id`),
                    KEY `idx_rt_batch` (`batch_id`),
                    KEY `idx_rt_old` (`residence_id_old`),
                    KEY `idx_rt_new` (`residence_id_new`),
                    KEY `idx_rt_from` (`from_barangay_id`, `status`),
                    KEY `idx_rt_to` (`to_barangay_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        if (barangay_table_exists($con, 'residence_status')
            && !barangay_column_exists($con, 'residence_status', 'archive_reason')) {
            if ($con->query("ALTER TABLE `residence_status` ADD COLUMN `archive_reason` VARCHAR(32) NOT NULL DEFAULT '' AFTER `archive`")) {
                barangay_mark_column_exists('residence_status', 'archive_reason');
            }
        }

        if (barangay_table_exists($con, 'nutrition_household_survey')) {
            $columns = [
                'moved_out' => "ALTER TABLE `nutrition_household_survey` ADD COLUMN `moved_out` VARCHAR(8) NOT NULL DEFAULT 'NO'",
                'moved_out_date' => 'ALTER TABLE `nutrition_household_survey` ADD COLUMN `moved_out_date` DATE NULL DEFAULT NULL',
                'moved_out_transfer_id' => "ALTER TABLE `nutrition_household_survey` ADD COLUMN `moved_out_transfer_id` VARCHAR(32) NOT NULL DEFAULT ''",
            ];
            foreach ($columns as $column => $sql) {
                if (!barangay_column_exists($con, 'nutrition_household_survey', $column) && $con->query($sql)) {
                    barangay_mark_column_exists('nutrition_household_survey', $column);
                }
            }
        }
    }
}

if (!function_exists('residence_transfer_local_roles')) {
    /**
     * Barangay-bound roles that may request a transfer for their own barangay.
     *
     * @return array<int, string>
     */
    function residence_transfer_local_roles(): array
    {
        return [STAFF_ROLE_BARANGAY_ADMIN, STAFF_ROLE_BARANGAY_STAFF];
    }
}

if (!function_exists('residence_transfer_can_execute_directly')) {
    /** SSA and Barangay Hub SA transfer immediately, without an approval step. */
    function residence_transfer_can_execute_directly(mysqli $con, string $userId): bool
    {
        return $userId !== '' && barangay_user_can_mutate_barangay_records($con, $userId);
    }
}

if (!function_exists('residence_transfer_can_request')) {
    function residence_transfer_can_request(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }
        if (residence_transfer_can_execute_directly($con, $userId)) {
            return true;
        }

        return in_array(barangay_user_staff_role($con, $userId), residence_transfer_local_roles(), true)
            && barangay_user_barangay_id($con, $userId) !== null;
    }
}

if (!function_exists('residence_transfer_can_decide')) {
    /** Approve / reject: SSA, Barangay SA, or the Barangay Admin of the receiving barangay. */
    function residence_transfer_can_decide(mysqli $con, string $userId, string $toBarangayId): bool
    {
        if ($userId === '' || $toBarangayId === '') {
            return false;
        }
        if (residence_transfer_can_execute_directly($con, $userId)) {
            return true;
        }

        return barangay_user_staff_role($con, $userId) === STAFF_ROLE_BARANGAY_ADMIN
            && barangay_user_barangay_id($con, $userId) === $toBarangayId;
    }
}

if (!function_exists('residence_transfer_can_cancel')) {
    /** Cancel a pending request: SSA, Barangay SA, or staff of the sending barangay. */
    function residence_transfer_can_cancel(mysqli $con, string $userId, string $fromBarangayId): bool
    {
        if ($userId === '' || $fromBarangayId === '') {
            return false;
        }
        if (residence_transfer_can_execute_directly($con, $userId)) {
            return true;
        }

        return in_array(barangay_user_staff_role($con, $userId), residence_transfer_local_roles(), true)
            && barangay_user_barangay_id($con, $userId) === $fromBarangayId;
    }
}

if (!function_exists('residence_transfer_can_view')) {
    function residence_transfer_can_view(mysqli $con, string $userId): bool
    {
        return residence_transfer_can_request($con, $userId);
    }
}

if (!function_exists('residence_transfer_person_name')) {
    /**
     * @param array<string, mixed> $row
     */
    function residence_transfer_person_name(array $row): string
    {
        $parts = [];
        foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '' && strcasecmp($value, 'none') !== 0) {
                $parts[] = $value;
            }
        }

        return trim(implode(' ', $parts));
    }
}

if (!function_exists('residence_transfer_load_resident')) {
    /**
     * @return array{info:array<string,mixed>,status:array<string,mixed>}|null
     */
    function residence_transfer_load_resident(mysqli $con, string $residenceId): ?array
    {
        $stmt = $con->prepare('SELECT * FROM residence_information WHERE residence_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $residenceId);
        $stmt->execute();
        $info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$info) {
            return null;
        }

        $stmt = $con->prepare('SELECT * FROM residence_status WHERE residence_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $residenceId);
        $stmt->execute();
        $status = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$status) {
            return null;
        }

        return ['info' => $info, 'status' => $status];
    }
}

if (!function_exists('residence_transfer_pending_for')) {
    function residence_transfer_pending_for(mysqli $con, string $residenceId): ?array
    {
        residence_transfer_ensure_schema($con);
        $pending = RESIDENCE_TRANSFER_PENDING;
        $stmt = $con->prepare('SELECT * FROM residence_transfer WHERE residence_id_old = ? AND status = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $residenceId, $pending);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }
}

if (!function_exists('residence_transfer_active_residents')) {
    /**
     * Active (non-archived) residents of a barangay, for the "move together" picker.
     *
     * @return array<int, array{residence_id:string,name:string,household_head:string}>
     */
    function residence_transfer_active_residents(mysqli $con, string $barangayId, string $excludeResidenceId = ''): array
    {
        $stmt = $con->prepare(
            "SELECT ri.residence_id, ri.first_name, ri.middle_name, ri.last_name, ri.suffix, rs.household_head
             FROM residence_information ri
             INNER JOIN residence_status rs ON rs.residence_id = ri.residence_id
             WHERE rs.barangay_id = ? AND rs.archive = 'NO' AND ri.residence_id <> ?
             ORDER BY ri.last_name ASC, ri.first_name ASC"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ss', $barangayId, $excludeResidenceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'residence_id' => (string) $row['residence_id'],
                'name' => residence_transfer_person_name($row),
                'household_head' => strtoupper((string) ($row['household_head'] ?? 'NO')),
            ];
        }
        $stmt->close();

        return $rows;
    }
}

if (!function_exists('residence_transfer_barangay_name')) {
    function residence_transfer_barangay_name(mysqli $con, string $barangayId): string
    {
        static $cache = [];
        if (!array_key_exists($barangayId, $cache)) {
            $row = $barangayId !== '' ? barangay_load_by_id($con, $barangayId) : null;
            $cache[$barangayId] = $row ? (string) ($row['barangay'] ?? '') : '';
        }

        return $cache[$barangayId];
    }
}

if (!function_exists('residence_transfer_actor_label')) {
    function residence_transfer_actor_label(mysqli $con, string $userId): string
    {
        $role = barangay_user_staff_role($con, $userId);

        return strtoupper($role !== '' ? staff_role_label($role) : 'ADMIN');
    }
}

if (!function_exists('residence_transfer_create')) {
    /**
     * Create a transfer request for one resident plus optional household members.
     * SSA / Barangay SA requests are approved immediately.
     *
     * @param array<int, string> $memberIds
     * @param array{transfer_date:string,reason:string,new_house_number?:string,new_street?:string} $details
     * @return array{batch_id:string,status:string,count:int,new_ids:array<string,string>}
     */
    function residence_transfer_create(
        mysqli $con,
        string $actorId,
        string $fromBarangayId,
        string $residenceId,
        array $memberIds,
        string $toBarangayId,
        array $details
    ): array {
        residence_transfer_ensure_schema($con);

        if (!residence_transfer_can_request($con, $actorId)) {
            throw new DomainException('You do not have permission to transfer residents.');
        }
        if ($fromBarangayId === '' || $toBarangayId === '') {
            throw new DomainException('Select the destination barangay.');
        }
        if ($fromBarangayId === $toBarangayId) {
            throw new DomainException('The destination barangay must be different from the current barangay.');
        }
        if (barangay_load_by_id($con, $toBarangayId) === null) {
            throw new DomainException('Destination barangay not found.');
        }

        $transferDate = trim((string) ($details['transfer_date'] ?? ''));
        $parsedDate = DateTime::createFromFormat('Y-m-d', $transferDate);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $transferDate) {
            throw new DomainException('Enter a valid transfer date.');
        }
        $reason = trim((string) ($details['reason'] ?? ''));
        if ($reason === '') {
            throw new DomainException('Enter the reason for the transfer.');
        }
        $reason = mb_substr($reason, 0, 255);
        $houseNumber = mb_substr(trim((string) ($details['new_house_number'] ?? '')), 0, 69);
        $street = mb_substr(trim((string) ($details['new_street'] ?? '')), 0, 69);

        $ids = [$residenceId];
        foreach ($memberIds as $memberId) {
            $memberId = trim((string) $memberId);
            if ($memberId !== '' && !in_array($memberId, $ids, true)) {
                $ids[] = $memberId;
            }
        }

        $people = [];
        foreach ($ids as $id) {
            $resident = residence_transfer_load_resident($con, $id);
            if ($resident === null) {
                throw new DomainException('Resident ' . $id . ' was not found.');
            }
            if ((string) ($resident['status']['barangay_id'] ?? '') !== $fromBarangayId) {
                throw new DomainException('Resident ' . $id . ' is not registered in this barangay.');
            }
            if (strtoupper((string) ($resident['status']['archive'] ?? '')) === 'YES') {
                throw new DomainException('Resident ' . $id . ' is archived and cannot be transferred.');
            }
            if (residence_transfer_pending_for($con, $id) !== null) {
                throw new DomainException(residence_transfer_person_name($resident['info']) . ' already has a pending transfer.');
            }
            residence_transfer_assert_no_duplicate($con, $resident['info'], $toBarangayId);
            $people[$id] = $resident;
        }

        $batchId = barangay_generate_id();
        $pending = RESIDENCE_TRANSFER_PENDING;
        $insert = $con->prepare(
            'INSERT INTO residence_transfer
                (id, batch_id, residence_id_old, person_name, from_barangay_id, to_barangay_id,
                 transfer_date, reason, new_house_number, new_street, status, requested_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        if (!$insert) {
            throw new RuntimeException('Could not save the transfer request.');
        }

        $con->begin_transaction();
        try {
            foreach ($people as $id => $resident) {
                $transferId = barangay_generate_id() . substr((string) $id, -4);
                $name = residence_transfer_person_name($resident['info']);
                $insert->bind_param(
                    'ssssssssssss',
                    $transferId,
                    $batchId,
                    $id,
                    $name,
                    $fromBarangayId,
                    $toBarangayId,
                    $transferDate,
                    $reason,
                    $houseNumber,
                    $street,
                    $pending,
                    $actorId
                );
                if (!$insert->execute()) {
                    throw new RuntimeException('Could not save the transfer request.');
                }
            }
            $insert->close();
            $con->commit();
        } catch (Throwable $e) {
            $con->rollback();
            throw $e;
        }

        $fromName = residence_transfer_barangay_name($con, $fromBarangayId);
        $toName = residence_transfer_barangay_name($con, $toBarangayId);
        $names = implode(', ', array_map(static fn ($r) => residence_transfer_person_name($r['info']), $people));

        if (residence_transfer_can_execute_directly($con, $actorId)) {
            $newIds = residence_transfer_approve_batch($con, $actorId, $batchId);

            return ['batch_id' => $batchId, 'status' => RESIDENCE_TRANSFER_APPROVED, 'count' => count($people), 'new_ids' => $newIds];
        }

        barangay_audit_log(
            $con,
            residence_transfer_actor_label($con, $actorId) . ': REQUESTED TRANSFER - ' . $names . ' | FROM ' . $fromName . ' TO ' . $toName,
            'transfer',
            ['user_id' => $actorId, 'barangay_id' => $fromBarangayId, 'entity_type' => 'residence_transfer', 'entity_id' => $batchId]
        );

        return ['batch_id' => $batchId, 'status' => RESIDENCE_TRANSFER_PENDING, 'count' => count($people), 'new_ids' => []];
    }
}

if (!function_exists('residence_transfer_assert_no_duplicate')) {
    /**
     * @param array<string, mixed> $info
     */
    function residence_transfer_assert_no_duplicate(mysqli $con, array $info, string $toBarangayId): void
    {
        $birthDate = trim((string) ($info['birth_date'] ?? ''));
        if ($birthDate === '' || strcasecmp($birthDate, 'none') === 0) {
            return;
        }
        $match = residence_find_duplicate_name(
            $con,
            $toBarangayId,
            (string) ($info['first_name'] ?? ''),
            (string) ($info['middle_name'] ?? ''),
            (string) ($info['last_name'] ?? ''),
            (string) ($info['suffix'] ?? ''),
            null,
            $birthDate
        );
        if ($match === null) {
            return;
        }
        $existing = residence_transfer_load_resident($con, $match['residence_id']);
        if ($existing !== null && strtoupper((string) ($existing['status']['archive'] ?? '')) !== 'YES') {
            throw new DomainException(
                residence_transfer_person_name($info) . ' is already registered in '
                . residence_transfer_barangay_name($con, $toBarangayId) . ' (' . $match['residence_id'] . ').'
            );
        }
    }
}

if (!function_exists('residence_transfer_load_batch')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function residence_transfer_load_batch(mysqli $con, string $batchId): array
    {
        residence_transfer_ensure_schema($con);
        $stmt = $con->prepare('SELECT * FROM residence_transfer WHERE batch_id = ? ORDER BY requested_at ASC, id ASC');
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $batchId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }
}

if (!function_exists('residence_transfer_load')) {
    function residence_transfer_load(mysqli $con, string $transferId): ?array
    {
        residence_transfer_ensure_schema($con);
        $stmt = $con->prepare('SELECT * FROM residence_transfer WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $transferId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }
}

if (!function_exists('residence_transfer_insert_copy')) {
    /**
     * Insert a copy of $row into $table, dropping $skip columns.
     *
     * @param array<string, mixed> $row
     * @param array<int, string> $skip
     */
    function residence_transfer_insert_copy(mysqli $con, string $table, array $row, array $skip = ['a_i']): void
    {
        foreach ($skip as $column) {
            unset($row[$column]);
        }
        $columns = array_keys($row);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES ('
            . implode(',', array_fill(0, count($columns), '?')) . ')';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Could not copy ' . $table . ': ' . $con->error);
        }
        $values = array_map(static fn ($v) => $v === null ? null : (string) $v, array_values($row));
        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Could not copy ' . $table . ': ' . $error);
        }
        $stmt->close();
    }
}

if (!function_exists('residence_transfer_exec')) {
    /**
     * @param array<int, string|null> $params
     */
    function residence_transfer_exec(mysqli $con, string $sql, array $params): int
    {
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Database error: ' . $con->error);
        }
        if ($params !== []) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Database error: ' . $error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected;
    }
}

if (!function_exists('residence_transfer_execute_person')) {
    /**
     * Archive the old record, create the linked record in the destination barangay,
     * move the resident's login, and flag their household survey as moved out.
     * Caller owns the transaction.
     *
     * @param array<string, mixed> $transfer
     * @return string New resident number
     */
    function residence_transfer_execute_person(mysqli $con, array $transfer, string $actorId): string
    {
        $oldId = (string) $transfer['residence_id_old'];
        $fromId = (string) $transfer['from_barangay_id'];
        $toId = (string) $transfer['to_barangay_id'];

        $resident = residence_transfer_load_resident($con, $oldId);
        if ($resident === null) {
            throw new DomainException('Resident ' . $oldId . ' no longer exists.');
        }
        if ((string) ($resident['status']['barangay_id'] ?? '') !== $fromId
            || strtoupper((string) ($resident['status']['archive'] ?? '')) === 'YES') {
            throw new DomainException(residence_transfer_person_name($resident['info']) . ' is no longer an active resident of the sending barangay.');
        }
        residence_transfer_assert_no_duplicate($con, $resident['info'], $toId);

        $toName = residence_transfer_barangay_name($con, $toId);
        $newId = residence_generate_number($con, $toId, $toName);
        $now = date('m/d/Y h:i A');

        $info = $resident['info'];
        $info['residence_id'] = $newId;
        $info['barangay'] = $toName;
        if (trim((string) $transfer['new_house_number']) !== '') {
            $info['house_number'] = (string) $transfer['new_house_number'];
        }
        if (trim((string) $transfer['new_street']) !== '') {
            $info['street'] = (string) $transfer['new_street'];
        }
        residence_transfer_insert_copy($con, 'residence_information', $info);

        $status = $resident['status'];
        $status['residence_id'] = $newId;
        $status['barangay_id'] = $toId;
        $status['status'] = 'ACTIVE';
        $status['archive'] = 'NO';
        $status['date_added'] = $now;
        $status['date_archive'] = 'none';
        $status['date_unarchive'] = 'none';
        // Purok and precinct belong to the old barangay; the receiving barangay assigns new ones.
        foreach (['purok_id', 'precint_id'] as $column) {
            if (array_key_exists($column, $status)) {
                $status[$column] = '';
            }
        }
        if (array_key_exists('archive_reason', $status)) {
            $status['archive_reason'] = '';
        }
        residence_transfer_insert_copy($con, 'residence_status', $status);

        if (residence_dependents_table_exists($con)) {
            $stmt = $con->prepare('SELECT * FROM residence_dependents WHERE residence_id = ?');
            if ($stmt) {
                $stmt->bind_param('s', $oldId);
                $stmt->execute();
                $dependents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                foreach ($dependents as $dependent) {
                    $dependent['dependent_id'] = barangay_generate_id();
                    $dependent['residence_id'] = $newId;
                    residence_transfer_insert_copy($con, 'residence_dependents', $dependent, []);
                }
            }
        }

        $archiveSql = barangay_column_exists($con, 'residence_status', 'archive_reason')
            ? "UPDATE residence_status SET archive = 'YES', status = 'INACTIVE', date_archive = ?, archive_reason = 'TRANSFERRED' WHERE residence_id = ?"
            : "UPDATE residence_status SET archive = 'YES', status = 'INACTIVE', date_archive = ? WHERE residence_id = ?";
        residence_transfer_exec($con, $archiveSql, [$now, $oldId]);

        // Same username and password; the account now points at the new record.
        $userSet = 'id = ?';
        $userParams = [$newId];
        if (barangay_column_exists($con, 'users', 'barangay_id')) {
            $userSet .= ', barangay_id = ?';
            $userParams[] = $toId;
        }
        if (barangay_column_exists($con, 'users', 'active_session_token')) {
            $userSet .= ', active_session_token = NULL';
        }
        $userParams[] = $oldId;
        $movedLogin = residence_transfer_exec($con, "UPDATE users SET {$userSet} WHERE id = ? AND user_type = 'resident'", $userParams) > 0;
        if ($movedLogin && barangay_table_exists($con, 'password_reset_tokens')) {
            residence_transfer_exec($con, 'DELETE FROM password_reset_tokens WHERE user_id = ?', [$oldId]);
        }

        if (barangay_table_exists($con, 'nutrition_household_survey')
            && barangay_column_exists($con, 'nutrition_household_survey', 'moved_out')) {
            residence_transfer_exec(
                $con,
                "UPDATE nutrition_household_survey
                 SET moved_out = 'YES', moved_out_date = ?, moved_out_transfer_id = ?
                 WHERE residence_id = ? AND barangay_id = ?",
                [(string) $transfer['transfer_date'], (string) $transfer['id'], $oldId, $fromId]
            );
        }

        $approved = RESIDENCE_TRANSFER_APPROVED;
        residence_transfer_exec(
            $con,
            'UPDATE residence_transfer SET residence_id_new = ?, status = ?, transferred_by = ?, decided_at = NOW() WHERE id = ?',
            [$newId, $approved, $actorId, (string) $transfer['id']]
        );

        $fromName = residence_transfer_barangay_name($con, $fromId);
        $name = residence_transfer_person_name($resident['info']);
        $actor = residence_transfer_actor_label($con, $actorId);
        barangay_audit_log(
            $con,
            $actor . ': TRANSFERRED RESIDENT - ' . $oldId . ' | ' . $name . ' | TO ' . $toName . ' AS ' . $newId,
            'transfer',
            ['user_id' => $actorId, 'barangay_id' => $fromId, 'entity_type' => 'residence', 'entity_id' => $oldId]
        );
        barangay_audit_log(
            $con,
            $actor . ': RECEIVED TRANSFERRED RESIDENT - ' . $newId . ' | ' . $name . ' | FROM ' . $fromName . ' (' . $oldId . ')',
            'transfer',
            ['user_id' => $actorId, 'barangay_id' => $toId, 'entity_type' => 'residence', 'entity_id' => $newId]
        );

        return $newId;
    }
}

if (!function_exists('residence_transfer_approve_batch')) {
    /**
     * @return array<string, string> old resident number => new resident number
     */
    function residence_transfer_approve_batch(mysqli $con, string $actorId, string $batchId): array
    {
        $rows = array_values(array_filter(
            residence_transfer_load_batch($con, $batchId),
            static fn ($row) => ($row['status'] ?? '') === RESIDENCE_TRANSFER_PENDING
        ));
        if ($rows === []) {
            throw new DomainException('This transfer is no longer pending.');
        }
        if (!residence_transfer_can_decide($con, $actorId, (string) $rows[0]['to_barangay_id'])) {
            throw new DomainException('Only the receiving barangay admin or a Super Admin can approve this transfer.');
        }

        $newIds = [];
        $con->begin_transaction();
        try {
            foreach ($rows as $row) {
                $newIds[(string) $row['residence_id_old']] = residence_transfer_execute_person($con, $row, $actorId);
            }
            $con->commit();
        } catch (Throwable $e) {
            $con->rollback();
            throw $e;
        }

        return $newIds;
    }
}

if (!function_exists('residence_transfer_close_batch')) {
    /**
     * Reject (receiving side) or cancel (sending side) a pending batch.
     */
    function residence_transfer_close_batch(mysqli $con, string $actorId, string $batchId, string $newStatus, string $note = ''): int
    {
        $rows = array_values(array_filter(
            residence_transfer_load_batch($con, $batchId),
            static fn ($row) => ($row['status'] ?? '') === RESIDENCE_TRANSFER_PENDING
        ));
        if ($rows === []) {
            throw new DomainException('This transfer is no longer pending.');
        }

        $first = $rows[0];
        if ($newStatus === RESIDENCE_TRANSFER_REJECTED) {
            if (!residence_transfer_can_decide($con, $actorId, (string) $first['to_barangay_id'])) {
                throw new DomainException('Only the receiving barangay admin or a Super Admin can reject this transfer.');
            }
            $verb = 'REJECTED';
        } elseif ($newStatus === RESIDENCE_TRANSFER_CANCELLED) {
            if (!residence_transfer_can_cancel($con, $actorId, (string) $first['from_barangay_id'])) {
                throw new DomainException('Only the sending barangay or a Super Admin can cancel this transfer.');
            }
            $verb = 'CANCELLED';
        } else {
            throw new DomainException('Unknown transfer action.');
        }

        $note = mb_substr(trim($note), 0, 255);
        $pending = RESIDENCE_TRANSFER_PENDING;
        $count = residence_transfer_exec(
            $con,
            'UPDATE residence_transfer SET status = ?, transferred_by = ?, decided_at = NOW(), decision_note = ? WHERE batch_id = ? AND status = ?',
            [$newStatus, $actorId, $note, $batchId, $pending]
        );

        $names = implode(', ', array_map(static fn ($row) => (string) $row['person_name'], $rows));
        barangay_audit_log(
            $con,
            residence_transfer_actor_label($con, $actorId) . ': ' . $verb . ' TRANSFER - ' . $names
                . ' | FROM ' . residence_transfer_barangay_name($con, (string) $first['from_barangay_id'])
                . ' TO ' . residence_transfer_barangay_name($con, (string) $first['to_barangay_id'])
                . ($note !== '' ? ' | ' . $note : ''),
            'transfer',
            ['user_id' => $actorId, 'barangay_id' => (string) $first['from_barangay_id'], 'entity_type' => 'residence_transfer', 'entity_id' => $batchId]
        );

        return $count;
    }
}

if (!function_exists('residence_transfer_list_batches')) {
    /**
     * Transfers touching a barangay, grouped by batch.
     *
     * @param string $direction incoming|outgoing|all
     * @return array<int, array<string, mixed>>
     */
    function residence_transfer_list_batches(mysqli $con, string $barangayId, string $direction, ?string $status = null, int $limit = 50): array
    {
        residence_transfer_ensure_schema($con);
        if ($barangayId === '') {
            return [];
        }

        $where = match ($direction) {
            'incoming' => 'to_barangay_id = ?',
            'outgoing' => 'from_barangay_id = ?',
            default => '(to_barangay_id = ? OR from_barangay_id = ?)',
        };
        $params = $direction === 'all' ? [$barangayId, $barangayId] : [$barangayId];
        if ($status !== null) {
            $where .= ' AND status = ?';
            $params[] = $status;
        } else {
            $where .= ' AND status <> ?';
            $params[] = RESIDENCE_TRANSFER_PENDING;
        }

        $stmt = $con->prepare("SELECT * FROM residence_transfer WHERE {$where} ORDER BY requested_at DESC, id ASC");
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $batches = [];
        while ($row = $result->fetch_assoc()) {
            $key = (string) ($row['batch_id'] !== '' ? $row['batch_id'] : $row['id']);
            if (!isset($batches[$key])) {
                if (count($batches) >= $limit) {
                    break;
                }
                $batches[$key] = [
                    'batch_id' => $key,
                    'transfer_id' => (string) $row['id'],
                    'from_barangay_id' => (string) $row['from_barangay_id'],
                    'to_barangay_id' => (string) $row['to_barangay_id'],
                    'from_barangay' => residence_transfer_barangay_name($con, (string) $row['from_barangay_id']),
                    'to_barangay' => residence_transfer_barangay_name($con, (string) $row['to_barangay_id']),
                    'transfer_date' => (string) $row['transfer_date'],
                    'reason' => (string) $row['reason'],
                    'status' => (string) $row['status'],
                    'requested_by' => (string) $row['requested_by'],
                    'requested_at' => (string) $row['requested_at'],
                    'decided_at' => (string) ($row['decided_at'] ?? ''),
                    'decision_note' => (string) $row['decision_note'],
                    'people' => [],
                ];
            }
            $batches[$key]['people'][] = [
                'name' => (string) $row['person_name'],
                'old_id' => (string) $row['residence_id_old'],
                'new_id' => (string) $row['residence_id_new'],
            ];
        }
        $stmt->close();

        return array_values($batches);
    }
}

if (!function_exists('residence_transfer_pending_incoming_count')) {
    function residence_transfer_pending_incoming_count(mysqli $con, string $barangayId): int
    {
        residence_transfer_ensure_schema($con);
        if ($barangayId === '') {
            return 0;
        }
        $pending = RESIDENCE_TRANSFER_PENDING;
        $stmt = $con->prepare('SELECT COUNT(DISTINCT batch_id) AS total FROM residence_transfer WHERE to_barangay_id = ? AND status = ?');
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ss', $barangayId, $pending);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }
}

if (!function_exists('residence_transfer_links_for')) {
    /**
     * Approved transfer links for a set of resident numbers, for "Transferred from/to" badges.
     *
     * @param array<int, string> $residenceIds
     * @return array<string, array{direction:string,barangay:string,other_id:string,transfer_date:string}>
     */
    function residence_transfer_links_for(mysqli $con, array $residenceIds): array
    {
        residence_transfer_ensure_schema($con);
        $residenceIds = array_values(array_unique(array_filter(array_map('strval', $residenceIds))));
        if ($residenceIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $approved = RESIDENCE_TRANSFER_APPROVED;
        $stmt = $con->prepare(
            "SELECT residence_id_old, residence_id_new, from_barangay_id, to_barangay_id, transfer_date
             FROM residence_transfer
             WHERE status = ? AND (residence_id_old IN ($placeholders) OR residence_id_new IN ($placeholders))"
        );
        if (!$stmt) {
            return [];
        }
        $params = array_merge([$approved], $residenceIds, $residenceIds);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $lookup = array_flip($residenceIds);
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $old = (string) $row['residence_id_old'];
            $new = (string) $row['residence_id_new'];
            if (isset($lookup[$old])) {
                $links[$old] = [
                    'direction' => 'to',
                    'barangay' => residence_transfer_barangay_name($con, (string) $row['to_barangay_id']),
                    'other_id' => $new,
                    'transfer_date' => (string) $row['transfer_date'],
                ];
            }
            if (isset($lookup[$new])) {
                $links[$new] = [
                    'direction' => 'from',
                    'barangay' => residence_transfer_barangay_name($con, (string) $row['from_barangay_id']),
                    'other_id' => $old,
                    'transfer_date' => (string) $row['transfer_date'],
                ];
            }
        }
        $stmt->close();

        return $links;
    }
}

if (!function_exists('residence_transfer_link_badge')) {
    /**
     * @param array{direction:string,barangay:string,other_id:string,transfer_date:string}|null $link
     */
    function residence_transfer_link_badge(?array $link): string
    {
        if ($link === null) {
            return '';
        }
        $label = $link['direction'] === 'from' ? 'Transferred from ' : 'Transferred to ';
        $title = ($link['direction'] === 'from' ? 'Previous' : 'New') . ' resident no. ' . $link['other_id']
            . ($link['transfer_date'] !== '' ? ' · ' . $link['transfer_date'] : '');

        return '<br><span class="badge badge-info" title="' . barangay_h($title) . '"><i class="fas fa-exchange-alt mr-1"></i>'
            . barangay_h($label . $link['barangay']) . '</span>';
    }
}

if (!function_exists('residence_transfer_lineage_ids')) {
    /**
     * Every resident number this person has held across approved transfers, oldest first.
     * Use this to follow a person's growth assessments across barangays.
     *
     * @return array<int, string>
     */
    function residence_transfer_lineage_ids(mysqli $con, string $residenceId): array
    {
        residence_transfer_ensure_schema($con);
        $approved = RESIDENCE_TRANSFER_APPROVED;
        $chain = [$residenceId];

        $back = $con->prepare('SELECT residence_id_old FROM residence_transfer WHERE residence_id_new = ? AND status = ? LIMIT 1');
        $current = $residenceId;
        for ($i = 0; $back && $i < 20; $i++) {
            $back->bind_param('ss', $current, $approved);
            $back->execute();
            $row = $back->get_result()->fetch_assoc();
            if (!$row || in_array((string) $row['residence_id_old'], $chain, true)) {
                break;
            }
            $current = (string) $row['residence_id_old'];
            array_unshift($chain, $current);
        }
        if ($back) {
            $back->close();
        }

        $forward = $con->prepare('SELECT residence_id_new FROM residence_transfer WHERE residence_id_old = ? AND status = ? LIMIT 1');
        $current = $residenceId;
        for ($i = 0; $forward && $i < 20; $i++) {
            $forward->bind_param('ss', $current, $approved);
            $forward->execute();
            $row = $forward->get_result()->fetch_assoc();
            if (!$row || (string) $row['residence_id_new'] === '' || in_array((string) $row['residence_id_new'], $chain, true)) {
                break;
            }
            $current = (string) $row['residence_id_new'];
            $chain[] = $current;
        }
        if ($forward) {
            $forward->close();
        }

        return $chain;
    }
}
