<?php

/**
 * Mobile API bearer-token auth (Nutrition Portal).
 * Independent of PHP web sessions / CSRF.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/staff_permissions.php';

if (!defined('NUTRITION_API_TOKEN_TTL_DAYS')) {
    define('NUTRITION_API_TOKEN_TTL_DAYS', 30);
}

if (!function_exists('nutrition_api_ensure_tokens_table')) {
    function nutrition_api_ensure_tokens_table(mysqli $con): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $con->query(
            "CREATE TABLE IF NOT EXISTS `nutrition_api_tokens` (
                `token_id` VARCHAR(32) NOT NULL,
                `user_id` VARCHAR(64) NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `device_label` VARCHAR(120) NOT NULL DEFAULT '',
                `active_barangay_id` VARCHAR(32) NOT NULL DEFAULT '',
                `expires_at` DATETIME NOT NULL,
                `last_used_at` DATETIME DEFAULT NULL,
                `revoked_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`token_id`),
                UNIQUE KEY `uniq_nutrition_api_token_hash` (`token_hash`),
                KEY `idx_nutrition_api_token_user` (`user_id`),
                KEY `idx_nutrition_api_token_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $done = true;
    }
}

if (!function_exists('nutrition_api_ensure_must_change_password_column')) {
    function nutrition_api_ensure_must_change_password_column(mysqli $con): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $hasColumn = false;
        if (function_exists('barangay_column_exists')) {
            $hasColumn = barangay_column_exists($con, 'users', 'must_change_password');
        } else {
            $res = $con->query("SHOW COLUMNS FROM `users` LIKE 'must_change_password'");
            $hasColumn = $res instanceof mysqli_result && $res->num_rows > 0;
            if ($res instanceof mysqli_result) {
                $res->free();
            }
        }
        if (!$hasColumn) {
            $con->query(
                "ALTER TABLE `users`
                 ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0
                 AFTER `password`"
            );
        }
        $done = true;
    }
}

if (!function_exists('nutrition_api_user_must_change_password')) {
    function nutrition_api_user_must_change_password(mysqli $con, string $userId): bool
    {
        nutrition_api_ensure_must_change_password_column($con);
        $stmt = $con->prepare('SELECT must_change_password FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['must_change_password'] ?? 0) === 1;
    }
}

if (!function_exists('nutrition_api_set_must_change_password')) {
    function nutrition_api_set_must_change_password(mysqli $con, string $userId, bool $required): bool
    {
        nutrition_api_ensure_must_change_password_column($con);
        $flag = $required ? 1 : 0;
        $stmt = $con->prepare('UPDATE users SET must_change_password = ? WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('is', $flag, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool) $ok;
    }
}

if (!function_exists('nutrition_api_json_body')) {
    /**
     * @return array<string, mixed>
     */
    function nutrition_api_json_body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('nutrition_api_send')) {
    /**
     * @param array<string, mixed> $payload
     */
    function nutrition_api_send(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('nutrition_api_error')) {
    function nutrition_api_error(string $message, int $status = 400, array $extra = []): void
    {
        nutrition_api_send(array_merge(['ok' => false, 'error' => $message], $extra), $status);
    }
}

if (!function_exists('nutrition_api_bearer_token')) {
    function nutrition_api_bearer_token(): string
    {
        $header = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers ?: [] as $key => $value) {
                if (strcasecmp((string) $key, 'Authorization') === 0) {
                    $header = trim((string) $value);
                    break;
                }
            }
        }
        if ($header === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = trim((string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return trim($m[1]);
        }

        return trim((string) ($_GET['access_token'] ?? ''));
    }
}

if (!function_exists('nutrition_api_hash_token')) {
    function nutrition_api_hash_token(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}

if (!function_exists('nutrition_api_user_may_use_mobile')) {
    function nutrition_api_user_may_use_mobile(mysqli $con, string $userId): bool
    {
        return $userId !== '' && barangay_user_can_access_nutrition_portal($con, $userId);
    }
}

if (!function_exists('nutrition_api_revoke_user_tokens')) {
    /** Revoke all active mobile tokens for a user (optionally keep one token_id). */
    function nutrition_api_revoke_user_tokens(mysqli $con, string $userId, string $keepTokenId = ''): void
    {
        nutrition_api_ensure_tokens_table($con);
        $now = date('Y-m-d H:i:s');
        if ($keepTokenId !== '') {
            $stmt = $con->prepare(
                'UPDATE nutrition_api_tokens
                 SET revoked_at = ?
                 WHERE user_id = ?
                   AND token_id <> ?
                   AND revoked_at IS NULL'
            );
            if ($stmt) {
                $stmt->bind_param('sss', $now, $userId, $keepTokenId);
                $stmt->execute();
                $stmt->close();
            }

            return;
        }
        $stmt = $con->prepare(
            'UPDATE nutrition_api_tokens
             SET revoked_at = ?
             WHERE user_id = ?
               AND revoked_at IS NULL'
        );
        if ($stmt) {
            $stmt->bind_param('ss', $now, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('nutrition_api_user_has_active_mobile_session')) {
    function nutrition_api_user_has_active_mobile_session(mysqli $con, string $userId): bool
    {
        nutrition_api_ensure_tokens_table($con);
        $now = date('Y-m-d H:i:s');
        $stmt = $con->prepare(
            'SELECT token_id FROM nutrition_api_tokens
             WHERE user_id = ?
               AND revoked_at IS NULL
               AND expires_at > ?
             LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $userId, $now);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return is_array($row);
    }
}

if (!function_exists('nutrition_api_issue_token')) {
    /**
     * @return array{token:string,expires_at:string,token_id:string}
     */
    function nutrition_api_issue_token(mysqli $con, string $userId, string $deviceLabel = '', string $activeBarangayId = ''): array
    {
        nutrition_api_ensure_tokens_table($con);
        // One mobile session per account: drop any older tokens first.
        nutrition_api_revoke_user_tokens($con, $userId);

        $raw = 'ntr_' . bin2hex(random_bytes(32));
        $hash = nutrition_api_hash_token($raw);
        $tokenId = substr(md5(uniqid((string) mt_rand(), true)), 0, 32);
        $expiresAt = date('Y-m-d H:i:s', time() + (NUTRITION_API_TOKEN_TTL_DAYS * 86400));
        $createdAt = date('Y-m-d H:i:s');
        $deviceLabel = mb_substr(trim($deviceLabel), 0, 120);
        $activeBarangayId = trim($activeBarangayId);

        $stmt = $con->prepare(
            'INSERT INTO nutrition_api_tokens
             (token_id, user_id, token_hash, device_label, active_barangay_id, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            throw new RuntimeException('Could not prepare token insert.');
        }
        $stmt->bind_param(
            'sssssss',
            $tokenId,
            $userId,
            $hash,
            $deviceLabel,
            $activeBarangayId,
            $expiresAt,
            $createdAt
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Could not store API token.');
        }
        $stmt->close();

        return [
            'token' => $raw,
            'expires_at' => $expiresAt,
            'token_id' => $tokenId,
        ];
    }
}

if (!function_exists('nutrition_api_authenticate')) {
    /**
     * @return array{user_id:string,token_id:string,active_barangay_id:string,user:array<string,mixed>}
     */
    function nutrition_api_authenticate(mysqli $con, bool $allowMustChangePassword = false): array
    {
        nutrition_api_ensure_tokens_table($con);
        $raw = nutrition_api_bearer_token();
        if ($raw === '' || !str_starts_with($raw, 'ntr_')) {
            nutrition_api_error('Missing or invalid Authorization bearer token.', 401);
        }

        $hash = nutrition_api_hash_token($raw);
        $stmt = $con->prepare(
            'SELECT token_id, user_id, active_barangay_id, expires_at, revoked_at
             FROM nutrition_api_tokens
             WHERE token_hash = ?
             LIMIT 1'
        );
        if (!$stmt) {
            nutrition_api_error('Token lookup failed.', 500);
        }
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            nutrition_api_error('Invalid or expired token.', 401);
        }
        if (!empty($row['revoked_at'])) {
            nutrition_api_error('Token has been revoked.', 401);
        }
        $expiresAt = strtotime((string) ($row['expires_at'] ?? ''));
        if ($expiresAt === false || $expiresAt < time()) {
            nutrition_api_error('Token has expired. Please log in again.', 401);
        }

        $userId = (string) ($row['user_id'] ?? '');
        if (!nutrition_api_user_may_use_mobile($con, $userId)) {
            nutrition_api_error('This account cannot use the Nutrition mobile API.', 403);
        }

        $user = nutrition_api_load_user($con, $userId);
        if ($user === null) {
            nutrition_api_error('User account not found.', 401);
        }

        $tokenId = (string) $row['token_id'];
        $now = date('Y-m-d H:i:s');
        $touch = $con->prepare('UPDATE nutrition_api_tokens SET last_used_at = ? WHERE token_id = ?');
        if ($touch) {
            $touch->bind_param('ss', $now, $tokenId);
            $touch->execute();
            $touch->close();
        }

        if (!$allowMustChangePassword && nutrition_api_user_must_change_password($con, $userId)) {
            nutrition_api_error(
                'You must change your temporary password before continuing. Open Account → Change password.',
                403,
                ['code' => 'must_change_password']
            );
        }

        return [
            'user_id' => $userId,
            'token_id' => $tokenId,
            'active_barangay_id' => trim((string) ($row['active_barangay_id'] ?? '')),
            'user' => $user,
        ];
    }
}

if (!function_exists('nutrition_api_revoke_token')) {
    function nutrition_api_revoke_token(mysqli $con, string $tokenId): void
    {
        nutrition_api_ensure_tokens_table($con);
        $now = date('Y-m-d H:i:s');
        $stmt = $con->prepare(
            'UPDATE nutrition_api_tokens SET revoked_at = ? WHERE token_id = ? AND revoked_at IS NULL'
        );
        if ($stmt) {
            $stmt->bind_param('ss', $now, $tokenId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('nutrition_api_set_token_barangay')) {
    function nutrition_api_set_token_barangay(mysqli $con, string $tokenId, string $barangayId): bool
    {
        nutrition_api_ensure_tokens_table($con);
        $barangayId = trim($barangayId);
        $stmt = $con->prepare(
            'UPDATE nutrition_api_tokens SET active_barangay_id = ? WHERE token_id = ? AND revoked_at IS NULL'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $barangayId, $tokenId);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool) $ok;
    }
}

if (!function_exists('nutrition_api_user_can_access_barangay')) {
    function nutrition_api_user_can_access_barangay(mysqli $con, string $userId, string $barangayId): bool
    {
        $barangayId = trim($barangayId);
        if ($userId === '' || $barangayId === '') {
            return false;
        }
        foreach (nutrition_api_accessible_barangays($con, $userId) as $row) {
            if ((string) ($row['id'] ?? '') === $barangayId) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('nutrition_api_require_active_barangay')) {
    /**
     * @param array{user_id:string,token_id:string,active_barangay_id:string,user:array<string,mixed>} $auth
     * @return array{barangay_id:string,barangay_name:string}
     */
    function nutrition_api_require_active_barangay(mysqli $con, array $auth, ?string $overrideBarangayId = null): array
    {
        $userId = (string) ($auth['user_id'] ?? '');
        $barangayId = trim((string) ($overrideBarangayId ?? ''));
        if ($barangayId === '') {
            $barangayId = trim((string) ($auth['active_barangay_id'] ?? ''));
        }
        $accessible = [];
        if ($barangayId === '') {
            $accessible = nutrition_api_accessible_barangays($con, $userId);
            $barangayId = nutrition_api_default_barangay_id($con, $userId, $accessible);
            if ($barangayId === '' && count($accessible) === 1) {
                $barangayId = (string) ($accessible[0]['id'] ?? '');
            }
            $tokenId = trim((string) ($auth['token_id'] ?? ''));
            if ($barangayId !== '' && $tokenId !== '') {
                nutrition_api_set_token_barangay($con, $tokenId, $barangayId);
            }
        }
        if ($barangayId === '') {
            if (count($accessible) > 1) {
                nutrition_api_error(
                    'Select a barangay to continue.',
                    400,
                    ['code' => 'select_barangay']
                );
            }
            nutrition_api_error(
                'No barangay is assigned to this account. Ask an administrator to assign a barangay.',
                400,
                ['code' => 'no_barangay']
            );
        }
        if (!nutrition_api_user_can_access_barangay($con, $userId, $barangayId)) {
            nutrition_api_error('You cannot access this barangay.', 403);
        }
        $name = $barangayId;
        $stmt = $con->prepare('SELECT barangay FROM barangay_information WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $name = (string) ($stmt->get_result()->fetch_assoc()['barangay'] ?? $barangayId);
            $stmt->close();
        }

        return ['barangay_id' => $barangayId, 'barangay_name' => $name];
    }
}

if (!function_exists('nutrition_api_purok_options')) {
    /**
     * @return array<int, string>
     */
    function nutrition_api_purok_options(mysqli $con, string $userId, string $barangayId): array
    {
        $assigned = staff_assigned_purok_labels($con, $userId, $barangayId);
        if ($assigned !== []) {
            return array_values($assigned);
        }

        $puroks = [];
        $stmt = $con->prepare('SELECT purok FROM purok WHERE barangay_id = ? ORDER BY purok ASC');
        if ($stmt) {
            $stmt->bind_param('s', $barangayId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $label = trim((string) ($row['purok'] ?? ''));
                if ($label !== '') {
                    $puroks[] = $label;
                }
            }
            $stmt->close();
        }

        return $puroks;
    }
}

if (!function_exists('nutrition_api_load_user')) {
    /**
     * @return array<string, mixed>|null
     */
    function nutrition_api_load_user(mysqli $con, string $userId): ?array
    {
        nutrition_api_ensure_must_change_password_column($con);
        $hasRole = staff_role_column_exists($con);
        $hasBarangay = barangay_column_exists($con, 'users', 'barangay_id');
        $sql = 'SELECT id, username, first_name, middle_name, last_name, user_type, must_change_password'
            . ($hasRole ? ', staff_role' : '')
            . ($hasBarangay ? ', barangay_id' : '')
            . ' FROM users WHERE id = ? LIMIT 1';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('nutrition_api_user_payload')) {
    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    function nutrition_api_user_payload(mysqli $con, array $user): array
    {
        $userId = (string) ($user['id'] ?? '');
        $role = barangay_user_staff_role($con, $userId);

        $mustChange = array_key_exists('must_change_password', $user)
            ? ((int) $user['must_change_password'] === 1)
            : nutrition_api_user_must_change_password($con, $userId);

        return [
            'id' => $userId,
            'username' => (string) ($user['username'] ?? ''),
            'first_name' => (string) ($user['first_name'] ?? ''),
            'middle_name' => (string) ($user['middle_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
            'role' => $role,
            'role_label' => staff_role_label($role),
            'must_change_password' => $mustChange,
            'can_add_surveys' => nutrition_user_can_add_household_surveys($con, $userId),
            'can_edit_surveys' => nutrition_user_can_edit_household_surveys($con, $userId),
            'can_view_registered_households' => nutrition_user_can_view_registered_households($con, $userId),
        ];
    }
}

if (!function_exists('nutrition_api_accessible_barangays')) {
    /**
     * @return array<int, array{id:string,name:string}>
     */
    function nutrition_api_accessible_barangays(mysqli $con, string $userId): array
    {
        $out = [];
        $role = barangay_user_staff_role($con, $userId);

        if ($role === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR) {
            $bid = barangay_user_barangay_id($con, $userId);
            if ($bid !== null && $bid !== '') {
                $name = '';
                $stmt = $con->prepare('SELECT barangay FROM barangay_information WHERE id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('s', $bid);
                    $stmt->execute();
                    $name = (string) ($stmt->get_result()->fetch_assoc()['barangay'] ?? '');
                    $stmt->close();
                }
                $out[] = ['id' => (string) $bid, 'name' => $name !== '' ? $name : (string) $bid];
            }

            return $out;
        }

        if ($role === STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR) {
            $ids = staff_assigned_barangay_ids($con, $userId);
            if ($ids === []) {
                return [];
            }
            foreach ($ids as $bid) {
                $bid = (string) $bid;
                $name = $bid;
                $stmt = $con->prepare('SELECT barangay FROM barangay_information WHERE id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('s', $bid);
                    $stmt->execute();
                    $name = (string) ($stmt->get_result()->fetch_assoc()['barangay'] ?? $bid);
                    $stmt->close();
                }
                $out[] = ['id' => $bid, 'name' => $name];
            }
            usort($out, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

            return $out;
        }

        // SSA / Nutrition SA / Nutrition Admin — all barangays
        if (function_exists('barangay_list_all')) {
            foreach (barangay_list_all($con) as $row) {
                $out[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'name' => (string) ($row['barangay'] ?? ''),
                ];
            }
        }

        return array_values(array_filter($out, static fn (array $r): bool => $r['id'] !== ''));
    }
}

if (!function_exists('nutrition_api_default_barangay_id')) {
    /**
     * @param array<int, array{id:string,name:string}> $barangays
     */
    function nutrition_api_default_barangay_id(mysqli $con, string $userId, array $barangays): string
    {
        if (count($barangays) === 1) {
            return (string) $barangays[0]['id'];
        }
        $own = barangay_user_barangay_id($con, $userId);
        if ($own !== null && $own !== '') {
            foreach ($barangays as $b) {
                if ($b['id'] === (string) $own) {
                    return (string) $own;
                }
            }
        }

        return '';
    }
}
