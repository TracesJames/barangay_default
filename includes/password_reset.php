<?php

/**
 * Password reset one-time tokens (replaces last-4 phone-digit recovery).
 */
if (!function_exists('barangay_ensure_password_reset_table')) {
    function barangay_ensure_password_reset_table(mysqli $con): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        $con->query(
            "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` VARCHAR(64) NOT NULL,
                `username` VARCHAR(191) NOT NULL,
                `token_hash` VARCHAR(255) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `request_ip` VARCHAR(64) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`),
                KEY `idx_prt_user` (`user_id`),
                KEY `idx_prt_username` (`username`),
                KEY `idx_prt_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $ready = true;
    }
}

if (!function_exists('barangay_password_reset_normalize_phone')) {
    function barangay_password_reset_normalize_phone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}

if (!function_exists('barangay_password_reset_rate_limited')) {
    function barangay_password_reset_rate_limited(mysqli $con, string $username, string $ip): bool
    {
        barangay_ensure_password_reset_table($con);
        $since = date('Y-m-d H:i:s', time() - 900);
        $stmt = $con->prepare(
            'SELECT COUNT(*) AS c FROM password_reset_tokens
             WHERE (username = ? OR request_ip = ?) AND created_at >= ?'
        );
        $stmt->bind_param('sss', $username, $ip, $since);
        $stmt->execute();
        $count = (int) (($stmt->get_result()->fetch_assoc()['c'] ?? 0));
        $stmt->close();

        return $count >= 5;
    }
}

if (!function_exists('barangay_password_reset_issue_token')) {
    /**
     * @return array{ok:bool,token?:string,message:string}
     */
    function barangay_password_reset_issue_token(
        mysqli $con,
        string $usernameOrId,
        string $contactNumber,
        string $ip = ''
    ): array {
        require_once __DIR__ . '/helpers.php';
        barangay_ensure_password_reset_table($con);

        $usernameOrId = trim($usernameOrId);
        $contactNumber = barangay_password_reset_normalize_phone($contactNumber);
        $ip = substr(trim($ip), 0, 64);

        if ($usernameOrId === '' || strlen($contactNumber) < 10) {
            return ['ok' => false, 'message' => 'invalid'];
        }

        if (barangay_password_reset_rate_limited($con, $usernameOrId, $ip)) {
            return ['ok' => false, 'message' => 'rate_limited'];
        }

        $stmt = $con->prepare(
            'SELECT id, username, contact_number FROM users WHERE username = ? OR id = ? LIMIT 1'
        );
        $stmt->bind_param('ss', $usernameOrId, $usernameOrId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Same response shape whether user exists or not (except when contact matches).
        if (!$user) {
            return ['ok' => false, 'message' => 'invalid'];
        }

        $stored = barangay_password_reset_normalize_phone((string) ($user['contact_number'] ?? ''));
        if ($stored === '' || !hash_equals($stored, $contactNumber)) {
            return ['ok' => false, 'message' => 'invalid'];
        }

        $userId = (string) $user['id'];
        $username = (string) $user['username'];

        // Invalidate previous unused tokens for this user.
        $now = date('Y-m-d H:i:s');
        $invalidate = $con->prepare(
            'UPDATE password_reset_tokens SET used_at = ? WHERE user_id = ? AND used_at IS NULL'
        );
        $invalidate->bind_param('ss', $now, $userId);
        $invalidate->execute();
        $invalidate->close();

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 900);
        $insert = $con->prepare(
            'INSERT INTO password_reset_tokens
             (user_id, username, token_hash, expires_at, created_at, request_ip)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insert->bind_param('ssssss', $userId, $username, $hash, $expires, $now, $ip);
        $insert->execute();
        $insert->close();

        return [
            'ok' => true,
            'token' => $token,
            'username' => $username,
            'message' => 'issued',
        ];
    }
}

if (!function_exists('barangay_password_reset_consume_token')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function barangay_password_reset_consume_token(
        mysqli $con,
        string $username,
        string $token,
        string $newPassword
    ): array {
        require_once __DIR__ . '/helpers.php';
        barangay_ensure_password_reset_table($con);

        $username = trim($username);
        $token = trim($token);
        if ($username === '' || $token === '' || strlen($newPassword) < 8) {
            return ['ok' => false, 'message' => 'invalid'];
        }

        $hash = hash('sha256', $token);
        $now = date('Y-m-d H:i:s');
        $stmt = $con->prepare(
            'SELECT id, user_id FROM password_reset_tokens
             WHERE username = ? AND token_hash = ? AND used_at IS NULL AND expires_at >= ?
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->bind_param('sss', $username, $hash, $now);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return ['ok' => false, 'message' => 'token'];
        }

        $userId = (string) $row['user_id'];
        $tokenId = (int) $row['id'];
        $passwordHash = barangay_hash_password($newPassword);

        $con->begin_transaction();
        try {
            $updUser = $con->prepare('UPDATE users SET password = ? WHERE id = ? AND username = ?');
            $updUser->bind_param('sss', $passwordHash, $userId, $username);
            $updUser->execute();
            if ($updUser->affected_rows < 1) {
                $updUser->close();
                throw new RuntimeException('user');
            }
            $updUser->close();

            $updToken = $con->prepare('UPDATE password_reset_tokens SET used_at = ? WHERE id = ?');
            $updToken->bind_param('si', $now, $tokenId);
            $updToken->execute();
            $updToken->close();

            $con->commit();
        } catch (Throwable $e) {
            $con->rollback();

            return ['ok' => false, 'message' => 'invalid'];
        }

        return ['ok' => true, 'message' => 'updated'];
    }
}
