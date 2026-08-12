<?php

require_once __DIR__ . '/barangay_context.php';
require_once __DIR__ . '/staff_permissions.php';
require_once __DIR__ . '/helpers.php';

if (!function_exists('staff_accounts_ensure_schema')) {
    /**
     * Ensure users.barangay_id and users.staff_role exist (idempotent).
     */
    function staff_accounts_ensure_schema(mysqli $con): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!barangay_column_exists($con, 'users', 'barangay_id')) {
            $con->query("ALTER TABLE `users` ADD COLUMN `barangay_id` VARCHAR(255) NULL AFTER `user_type`");
            barangay_mark_column_exists('users', 'barangay_id');
        }

        if (!barangay_column_exists($con, 'users', 'staff_role')) {
            $after = barangay_column_exists($con, 'users', 'barangay_id') ? 'barangay_id' : 'user_type';
            $con->query("ALTER TABLE `users` ADD COLUMN `staff_role` VARCHAR(69) NOT NULL DEFAULT '' AFTER `{$after}`");
            barangay_mark_column_exists('users', 'staff_role');
        }

        if (!barangay_column_exists($con, 'users', 'staff_role')) {
            return;
        }

        $con->query("UPDATE users SET staff_role = 'ssa' WHERE user_type = 'admin' AND (barangay_id IS NULL OR barangay_id = '') AND (staff_role = '' OR staff_role IS NULL)");
        $con->query("UPDATE users SET staff_role = 'barangay_admin' WHERE user_type = 'admin' AND barangay_id IS NOT NULL AND barangay_id != '' AND (staff_role = '' OR staff_role IS NULL)");
        $con->query("UPDATE users SET staff_role = 'barangay_staff' WHERE user_type = 'secretary' AND (staff_role = '' OR staff_role IS NULL)");

        // Dedicated Nutrition Hub SA role (was super_admin + nutrition.* username).
        $con->query(
            "UPDATE users SET staff_role = 'nutrition_super_admin'
             WHERE staff_role IN ('super_admin', 'ssa')
               AND (LOWER(username) = 'nutrition.superadmin' OR LOWER(username) LIKE 'nutrition.%')"
        );

        // Existing city-wide Super Admins become SSA (both hubs).
        $con->query(
            "UPDATE users SET staff_role = 'ssa'
             WHERE staff_role = 'super_admin'
               AND (barangay_id IS NULL OR barangay_id = '')"
        );

        $tableCheck = $con->query("SHOW TABLES LIKE 'staff_barangay_assignment'");
        if ($tableCheck && $tableCheck->num_rows === 0) {
            $con->query(
                "CREATE TABLE `staff_barangay_assignment` (
                    `user_id` VARCHAR(64) NOT NULL,
                    `barangay_id` VARCHAR(64) NOT NULL,
                    PRIMARY KEY (`user_id`, `barangay_id`),
                    KEY `idx_sba_barangay` (`barangay_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }
    }
}

if (!function_exists('staff_account_resolve_role')) {
    function staff_account_resolve_role(array $userRow): string
    {
        $stored = trim((string) ($userRow['staff_role'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $type = strtolower(trim((string) ($userRow['user_type'] ?? '')));
        $barangayId = $userRow['barangay_id'] ?? null;

        if ($type === 'admin' && ($barangayId === null || $barangayId === '')) {
            return STAFF_ROLE_SSA;
        }
        if ($type === 'admin') {
            return STAFF_ROLE_BARANGAY_ADMIN;
        }
        if ($type === 'secretary') {
            return STAFF_ROLE_BARANGAY_STAFF;
        }

        return '';
    }
}

if (!function_exists('staff_account_role_label')) {
    function staff_account_role_label(string $role): string
    {
        return staff_role_label($role);
    }
}

if (!function_exists('staff_account_role_badge')) {
    function staff_account_role_badge(string $role): string
    {
        $class = match ($role) {
            STAFF_ROLE_SSA => 'badge-danger',
            STAFF_ROLE_SUPER_ADMIN => 'badge-danger',
            STAFF_ROLE_NUTRITION_SUPER_ADMIN => 'badge-success',
            STAFF_ROLE_ADMIN => 'badge-warning',
            STAFF_ROLE_BARANGAY_ADMIN => 'badge-primary',
            STAFF_ROLE_BARANGAY_STAFF => 'badge-info',
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR => 'badge-success',
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN => 'badge-dark',
            STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR => 'badge-info',
            default => 'badge-secondary',
        };

        return '<span class="badge ' . $class . '">' . barangay_h(staff_account_role_label($role)) . '</span>';
    }
}

if (!function_exists('staff_account_user_type_for_role')) {
    function staff_account_user_type_for_role(string $role): string
    {
        return staff_role_user_type($role);
    }
}

if (!function_exists('staff_account_actor_is_super_admin')) {
    function staff_account_actor_is_super_admin(mysqli $con): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return barangay_user_is_super_admin($con, (string) $_SESSION['user_id']);
    }
}

if (!function_exists('staff_account_actor_is_ssa')) {
    function staff_account_actor_is_ssa(mysqli $con): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return barangay_user_is_ssa($con, (string) $_SESSION['user_id']);
    }
}

if (!function_exists('staff_account_actor_is_nutrition_sa')) {
    function staff_account_actor_is_nutrition_sa(mysqli $con): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return barangay_user_is_nutrition_portal_admin($con, (string) $_SESSION['user_id']);
    }
}

if (!function_exists('staff_account_actor_barangay_id')) {
    function staff_account_actor_barangay_id(mysqli $con): ?string
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return barangay_user_barangay_id($con, (string) $_SESSION['user_id']);
    }
}

if (!function_exists('staff_account_creatable_roles')) {
    /**
     * @return array<int, string>
     */
    function staff_account_creatable_roles(mysqli $con): array
    {
        if (staff_account_actor_is_ssa($con)) {
            return [
                STAFF_ROLE_SSA,
                STAFF_ROLE_SUPER_ADMIN,
                STAFF_ROLE_NUTRITION_SUPER_ADMIN,
                STAFF_ROLE_ADMIN,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
                STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
                STAFF_ROLE_BARANGAY_ADMIN,
                STAFF_ROLE_BARANGAY_STAFF,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
            ];
        }

        if (staff_account_actor_is_nutrition_sa($con)) {
            // Nutrition Portal SA may create Nutrition Admin (A), BNS, and CNPC only.
            // Nutrition SA itself is created only by SSA.
            return [
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
                STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
            ];
        }

        if (barangay_user_is_barangay_hub_super_admin($con, (string) ($_SESSION['user_id'] ?? ''))) {
            return [
                STAFF_ROLE_ADMIN,
                STAFF_ROLE_BARANGAY_ADMIN,
                STAFF_ROLE_BARANGAY_STAFF,
            ];
        }

        return [];
    }
}

if (!function_exists('staff_account_assignable_roles_on_edit')) {
    /**
     * Roles the current actor may assign when editing an existing account.
     * SSA may change any staff account to any system role.
     *
     * @return array<int, string>
     */
    function staff_account_assignable_roles_on_edit(mysqli $con): array
    {
        if (staff_account_actor_is_ssa($con)) {
            return staff_account_creatable_roles($con);
        }

        return [];
    }
}

if (!function_exists('staff_account_nutrition_creatable_roles')) {
    /**
     * Nutrition Portal create rules:
     * - SSA → Nutrition SA only
     * - Nutrition SA → Nutrition Admin (A), BNS, CNPC
     *
     * @return array<int, string>
     */
    function staff_account_nutrition_creatable_roles(mysqli $con): array
    {
        if (staff_account_actor_is_ssa($con)) {
            return [STAFF_ROLE_NUTRITION_SUPER_ADMIN];
        }

        if (staff_account_actor_is_nutrition_sa($con)) {
            return [
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
                STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
            ];
        }

        return [];
    }
}

if (!function_exists('staff_account_nutrition_manageable_roles')) {
    /**
     * Roles visible/manageable in Nutrition Portal staff UI for the actor.
     *
     * @return array<int, string>
     */
    function staff_account_nutrition_manageable_roles(mysqli $con): array
    {
        if (staff_account_actor_is_ssa($con)) {
            return [
                STAFF_ROLE_NUTRITION_SUPER_ADMIN,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
                STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
            ];
        }

        if (staff_account_actor_is_nutrition_sa($con)) {
            return [
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
                STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
            ];
        }

        return [];
    }
}

if (!function_exists('staff_account_load')) {
    function staff_account_load(mysqli $con, string $userId): ?array
    {
        $stmt = $con->prepare(
            "SELECT u.*, b.barangay AS barangay_name
             FROM users u
             LEFT JOIN barangay_information b ON u.barangay_id = b.id
             WHERE u.id = ? AND u.user_type IN ('admin', 'secretary')
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return null;
        }

        $row['staff_role'] = staff_account_resolve_role($row);

        return $row;
    }
}

if (!function_exists('staff_account_can_manage')) {
    function staff_account_can_manage(mysqli $con, array $targetRow, string $action = 'edit'): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $actorId = (string) $_SESSION['user_id'];
        $targetId = (string) ($targetRow['id'] ?? '');
        $targetRole = staff_account_resolve_role($targetRow);

        if ($targetRole === '') {
            return false;
        }

        if ($action === 'delete') {
            if (barangay_user_can_delete_staff_accounts($con, $actorId)) {
                return true;
            }
            // Nutrition Hub SA may remove Nutrition Hub Admin / BNS accounts.
            if (staff_account_actor_is_nutrition_sa($con)) {
                return in_array($targetRole, [
                    STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
                    STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
                    STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
                ], true) && $targetId !== $actorId;
            }

            return false;
        }

        if (staff_account_actor_is_ssa($con)) {
            return true;
        }

        if (staff_account_actor_is_nutrition_sa($con)) {
            return in_array($targetRole, [
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
                STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
                STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
            ], true);
        }

        if (barangay_user_is_barangay_hub_super_admin($con, $actorId)) {
            return in_array($targetRole, [
                STAFF_ROLE_ADMIN,
                STAFF_ROLE_BARANGAY_ADMIN,
                STAFF_ROLE_BARANGAY_STAFF,
            ], true);
        }

        return false;
    }
}

if (!function_exists('staff_account_username_exists')) {
    function staff_account_username_exists(mysqli $con, string $username, ?string $excludeId = null): bool
    {
        if ($excludeId !== null && $excludeId !== '') {
            $stmt = $con->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
            $stmt->bind_param('ss', $username, $excludeId);
        } else {
            $stmt = $con->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
        }
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }
}

if (!function_exists('staff_account_barangay_admin_exists')) {
    function staff_account_barangay_admin_exists(mysqli $con, string $barangayId, ?string $excludeId = null): bool
    {
        if (!barangay_column_exists($con, 'users', 'barangay_id')) {
            return false;
        }

        if ($excludeId !== null && $excludeId !== '') {
            $stmt = $con->prepare("SELECT id FROM users WHERE staff_role = ? AND barangay_id = ? AND id != ? LIMIT 1");
            $role = STAFF_ROLE_BARANGAY_ADMIN;
            $stmt->bind_param('sss', $role, $barangayId, $excludeId);
        } else {
            $stmt = $con->prepare("SELECT id FROM users WHERE staff_role = ? AND barangay_id = ? LIMIT 1");
            $role = STAFF_ROLE_BARANGAY_ADMIN;
            $stmt->bind_param('ss', $role, $barangayId);
        }
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }
}

if (!function_exists('staff_account_log_activity')) {
    function staff_account_log_activity(mysqli $con, string $message, string $status = 'update'): void
    {
        $date = date('j-n-Y g:i A');
        $stmt = $con->prepare('INSERT INTO activity_log (message, date, status) VALUES (?,?,?)');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $message, $date, $status);
        $stmt->execute();
    }
}

if (!function_exists('staff_account_create')) {
    /**
     * @param array<string, mixed> $data
     * @return array{ok:bool,error?:string,user_id?:string}
     */
    function staff_account_create(mysqli $con, array $data): array
    {
        $role = trim((string) ($data['staff_role'] ?? ''));
        $creatable = staff_account_creatable_roles($con);
        if (!in_array($role, $creatable, true)) {
            return ['ok' => false, 'error' => 'You are not allowed to create this role.'];
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $username = trim((string) ($data['username'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        $contact = trim((string) ($data['contact_number'] ?? ''));

        if ($firstName === '' || $lastName === '' || $username === '' || $password === '' || $contact === '') {
            return ['ok' => false, 'error' => 'Please complete all required fields.'];
        }

        if (strlen($username) < 4 || strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Username or password is too short.'];
        }

        if (staff_account_username_exists($con, $username)) {
            return ['ok' => false, 'error' => 'Username already exists.'];
        }

        $barangayId = trim((string) ($data['barangay_id'] ?? ''));
        $assignmentIds = [];
        if (isset($data['barangay_ids']) && is_array($data['barangay_ids'])) {
            foreach ($data['barangay_ids'] as $id) {
                $id = trim((string) $id);
                if ($id !== '') {
                    $assignmentIds[] = $id;
                }
            }
        }

        if ($role === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) {
            if ($assignmentIds === [] && $barangayId !== '') {
                $assignmentIds = [$barangayId];
            }
            if ($assignmentIds === []) {
                return ['ok' => false, 'error' => 'Assign at least one barangay for CNPC.'];
            }
            $barangayId = '';
        } elseif (staff_role_requires_barangay($role)) {
            if ($barangayId === '') {
                return ['ok' => false, 'error' => 'Barangay is required for this role.'];
            }
        } else {
            $barangayId = '';
        }

        if ($role === STAFF_ROLE_BARANGAY_ADMIN && staff_account_barangay_admin_exists($con, $barangayId)) {
            return ['ok' => false, 'error' => 'This barangay already has an admin account.'];
        }

        $userType = staff_account_user_type_for_role($role);
        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $userId = (string) hexdec(uniqid());
        $hash = barangay_hash_password($password);
        $image = '';
        $imagePath = '';
        $staffRole = $role;

        if (staff_role_column_exists($con) && barangay_column_exists($con, 'users', 'barangay_id')) {
            $stmt = $con->prepare(
                'INSERT INTO users (id, first_name, middle_name, last_name, username, password, user_type, staff_role, contact_number, image, image_path, barangay_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param(
                'ssssssssssss',
                $userId,
                $firstName,
                $middleName,
                $lastName,
                $username,
                $hash,
                $userType,
                $staffRole,
                $contact,
                $image,
                $imagePath,
                $barangayId
            );
        } elseif (barangay_column_exists($con, 'users', 'barangay_id')) {
            $stmt = $con->prepare(
                'INSERT INTO users (id, first_name, middle_name, last_name, username, password, user_type, contact_number, image, image_path, barangay_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param(
                'sssssssssss',
                $userId,
                $firstName,
                $middleName,
                $lastName,
                $username,
                $hash,
                $userType,
                $contact,
                $image,
                $imagePath,
                $barangayId
            );
        } else {
            $stmt = $con->prepare(
                'INSERT INTO users (id, first_name, middle_name, last_name, username, password, user_type, contact_number, image, image_path)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param(
                'ssssssssss',
                $userId,
                $firstName,
                $middleName,
                $lastName,
                $username,
                $hash,
                $userType,
                $contact,
                $image,
                $imagePath
            );
        }

        if (!$stmt || !$stmt->execute()) {
            return ['ok' => false, 'error' => 'Unable to create account.'];
        }

        if ($role === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) {
            staff_set_barangay_assignments($con, $userId, $assignmentIds);
        }

        $roleLabel = staff_account_role_label($role);
        staff_account_log_activity(
            $con,
            strtoupper('ADMIN') . ': CREATED ' . strtoupper($roleLabel) . ' - ' . $userId . ' | ' . $firstName . ' ' . $lastName,
            'create'
        );

        return ['ok' => true, 'user_id' => $userId];
    }
}

if (!function_exists('staff_account_update')) {
    /**
     * @param array<string, mixed> $data
     * @return array{ok:bool,error?:string}
     */
    function staff_account_update(mysqli $con, string $userId, array $data): array
    {
        $target = staff_account_load($con, $userId);
        if (!$target) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        if (!staff_account_can_manage($con, $target, 'edit')) {
            return ['ok' => false, 'error' => 'You are not allowed to edit this account.'];
        }

        $actorId = (string) ($_SESSION['user_id'] ?? '');
        $currentRole = staff_account_resolve_role($target);
        $assignableRoles = staff_account_assignable_roles_on_edit($con);
        $requestedRole = trim((string) ($data['staff_role'] ?? ''));
        $newRole = $currentRole;
        $roleChanged = false;

        if ($requestedRole !== '' && $requestedRole !== $currentRole) {
            if ($assignableRoles === [] || !in_array($requestedRole, $assignableRoles, true)) {
                return ['ok' => false, 'error' => 'You are not allowed to change this account role.'];
            }
            if ($userId === $actorId) {
                return ['ok' => false, 'error' => 'You cannot change your own role. Ask another Super Super Admin.'];
            }
            $newRole = $requestedRole;
            $roleChanged = true;
        }

        $firstName = trim((string) ($data['first_name'] ?? $target['first_name']));
        $middleName = trim((string) ($data['middle_name'] ?? $target['middle_name']));
        $lastName = trim((string) ($data['last_name'] ?? $target['last_name']));
        $username = trim((string) ($data['username'] ?? $target['username']));
        $contact = trim((string) ($data['contact_number'] ?? $target['contact_number']));

        if ($firstName === '' || $lastName === '' || $username === '' || $contact === '') {
            return ['ok' => false, 'error' => 'Please complete all required fields.'];
        }

        if (staff_account_username_exists($con, $username, $userId)) {
            return ['ok' => false, 'error' => 'Username already exists.'];
        }

        $password = $target['password'];
        $newPassword = trim((string) ($data['password'] ?? ''));
        if ($newPassword !== '' && ($data['password_changed'] ?? '') === '1') {
            if (strlen($newPassword) < 6) {
                return ['ok' => false, 'error' => 'Password must be at least 6 characters.'];
            }
            $password = barangay_hash_password($newPassword);
        }

        $image = (string) ($target['image'] ?? '');
        $imagePath = (string) ($target['image_path'] ?? '');

        $barangayId = trim((string) ($target['barangay_id'] ?? ''));
        $assignmentIds = [];
        if (isset($data['barangay_ids']) && is_array($data['barangay_ids'])) {
            foreach ($data['barangay_ids'] as $id) {
                $id = trim((string) $id);
                if ($id !== '') {
                    $assignmentIds[] = $id;
                }
            }
        }
        if (array_key_exists('barangay_id', $data)) {
            $postedBarangay = trim((string) $data['barangay_id']);
            if ($postedBarangay !== '') {
                $barangayId = $postedBarangay;
            }
        }

        if ($roleChanged || $newRole === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR || staff_role_requires_barangay($newRole)) {
            if ($newRole === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) {
                if ($assignmentIds === [] && $barangayId !== '') {
                    $assignmentIds = [$barangayId];
                }
                if ($assignmentIds === []) {
                    $assignmentIds = staff_assigned_barangay_ids($con, $userId);
                }
                if ($assignmentIds === []) {
                    return ['ok' => false, 'error' => 'Assign at least one barangay for CNPC.'];
                }
                $barangayId = '';
            } elseif (staff_role_requires_barangay($newRole)) {
                if ($barangayId === '') {
                    return ['ok' => false, 'error' => 'Barangay is required for this role.'];
                }
                $assignmentIds = [];
            } else {
                $barangayId = '';
                $assignmentIds = [];
            }
        }

        if ($newRole === STAFF_ROLE_BARANGAY_ADMIN
            && $barangayId !== ''
            && staff_account_barangay_admin_exists($con, $barangayId, $userId)) {
            return ['ok' => false, 'error' => 'This barangay already has an admin account.'];
        }

        $userType = staff_account_user_type_for_role($newRole);

        if (staff_role_column_exists($con) && barangay_column_exists($con, 'users', 'barangay_id')) {
            $stmt = $con->prepare(
                'UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, username = ?, password = ?,
                 contact_number = ?, image = ?, image_path = ?, staff_role = ?, user_type = ?, barangay_id = ?
                 WHERE id = ?'
            );
            if (!$stmt) {
                return ['ok' => false, 'error' => 'Unable to update account.'];
            }
            $stmt->bind_param(
                'ssssssssssss',
                $firstName,
                $middleName,
                $lastName,
                $username,
                $password,
                $contact,
                $image,
                $imagePath,
                $newRole,
                $userType,
                $barangayId,
                $userId
            );
        } else {
            $stmt = $con->prepare(
                'UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, username = ?, password = ?, contact_number = ?, image = ?, image_path = ? WHERE id = ?'
            );
            if (!$stmt) {
                return ['ok' => false, 'error' => 'Unable to update account.'];
            }
            $stmt->bind_param(
                'sssssssss',
                $firstName,
                $middleName,
                $lastName,
                $username,
                $password,
                $contact,
                $image,
                $imagePath,
                $userId
            );
        }
        $stmt->execute();

        if ($newRole === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) {
            staff_set_barangay_assignments($con, $userId, $assignmentIds);
        } elseif ($roleChanged || array_key_exists('barangay_ids', $data)) {
            staff_set_barangay_assignments($con, $userId, []);
        }

        $roleNote = $roleChanged ? (' | ROLE ' . $currentRole . ' → ' . $newRole) : '';
        staff_account_log_activity(
            $con,
            strtoupper('ADMIN') . ': UPDATED STAFF ACCOUNT - ' . $userId . ' | ' . $firstName . ' ' . $lastName . $roleNote,
            'update'
        );

        return ['ok' => true];
    }
}

if (!function_exists('staff_account_delete')) {
    /**
     * @return array{ok:bool,error?:string}
     */
    function staff_account_delete(mysqli $con, string $userId): array
    {
        $target = staff_account_load($con, $userId);
        if (!$target) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        if (!staff_account_can_manage($con, $target, 'delete')) {
            return ['ok' => false, 'error' => 'You are not allowed to delete this account.'];
        }

        $stmt = $con->prepare('DELETE FROM users WHERE id = ? AND user_type IN (\'admin\', \'secretary\')');
        if (!$stmt) {
            return ['ok' => false, 'error' => 'Unable to delete account.'];
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            return ['ok' => false, 'error' => 'Account could not be deleted.'];
        }

        staff_set_barangay_assignments($con, $userId, []);

        staff_account_log_activity(
            $con,
            strtoupper('ADMIN') . ': DELETED STAFF ACCOUNT - ' . $userId . ' | ' . ($target['first_name'] ?? '') . ' ' . ($target['last_name'] ?? ''),
            'delete'
        );

        return ['ok' => true];
    }
}

if (!function_exists('staff_account_reset_password')) {
    /**
     * @return array{ok:bool,error?:string}
     */
    function staff_account_reset_password(mysqli $con, string $userId, string $plainPassword): array
    {
        $target = staff_account_load($con, $userId);
        if (!$target) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        if (!staff_account_can_manage($con, $target, 'edit')) {
            return ['ok' => false, 'error' => 'You are not allowed to reset this password.'];
        }

        if (strlen($plainPassword) < 6) {
            return ['ok' => false, 'error' => 'Password must be at least 6 characters.'];
        }

        $role = staff_account_resolve_role($target);
        $ok = false;
        $hash = barangay_hash_password($plainPassword);
        $stmt = $con->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('ss', $hash, $userId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;

        if (!$ok) {
            return ['ok' => false, 'error' => 'Password reset failed.'];
        }

        staff_account_log_activity(
            $con,
            strtoupper('ADMIN') . ': RESET PASSWORD - ' . $userId . ' | ' . ($target['username'] ?? ''),
            'update'
        );

        return ['ok' => true];
    }
}

if (!function_exists('staff_account_scope_where')) {
    /**
     * @return array{sql:string,types:string,params:array<int,string>}
     */
    function staff_account_scope_where(mysqli $con): array
    {
        $sql = "u.user_type IN ('admin', 'secretary')";
        $types = '';
        $params = [];

        if (staff_account_actor_is_ssa($con)) {
            return ['sql' => $sql, 'types' => $types, 'params' => $params];
        }

        if (staff_account_actor_is_nutrition_sa($con)) {
            $sql .= " AND u.staff_role IN ('barangay_nutrition_scholar','barangay_nutrition_scholar_admin','nutrition_super_admin','city_nutrition_program_coordinator')";

            return ['sql' => $sql, 'types' => $types, 'params' => $params];
        }

        if (barangay_user_is_barangay_hub_super_admin($con, (string) ($_SESSION['user_id'] ?? ''))) {
            $sql .= " AND u.staff_role IN ('super_admin','admin','barangay_admin','barangay_staff')";

            return ['sql' => $sql, 'types' => $types, 'params' => $params];
        }

        $actorBarangayId = staff_account_actor_barangay_id($con);
        if ($actorBarangayId !== null && barangay_column_exists($con, 'users', 'barangay_id')) {
            $sql .= ' AND (u.barangay_id = ? OR u.id = ?)';
            $types = 'ss';
            $params = [$actorBarangayId, (string) $_SESSION['user_id']];
        }

        return ['sql' => $sql, 'types' => $types, 'params' => $params];
    }
}
