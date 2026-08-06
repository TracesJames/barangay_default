<?php

/**
 * Single active session per user account (default).
 * - Login issues a new token stored in users.active_session_token
 * - Concurrent login from another browser/device is blocked while the session is active
 * - Auth checks reject requests whose session token no longer matches
 *
 * Exception: Super Super Admin (SSA) may use multiple browsers/tabs at once.
 * For SSA we keep a per-browser session token in $_SESSION only and do not
 * enforce a single DB token (so a new login does not kick other sessions).
 */

require_once __DIR__ . '/helpers.php';

if (!function_exists('barangay_session_guard_idle_seconds')) {
    /** Idle window after which another device may sign in without logout. */
    function barangay_session_guard_idle_seconds(): int
    {
        return 45 * 60;
    }
}

if (!function_exists('barangay_session_guard_allows_multi_login')) {
    /**
     * True when this account may hold multiple concurrent browser sessions.
     * Currently: Super Super Admin (SSA) only.
     */
    function barangay_session_guard_allows_multi_login(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }
        if (!function_exists('barangay_user_is_ssa')) {
            require_once __DIR__ . '/staff_permissions.php';
        }

        return barangay_user_is_ssa($con, $userId);
    }
}

if (!function_exists('barangay_session_guard_ensure_columns')) {
    function barangay_session_guard_ensure_columns(mysqli $con): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!function_exists('barangay_column_exists')) {
            require_once __DIR__ . '/barangay_context.php';
        }
        if (!barangay_table_exists($con, 'users')) {
            return;
        }
        if (!barangay_column_exists($con, 'users', 'active_session_token')) {
            @$con->query(
                "ALTER TABLE users ADD COLUMN active_session_token VARCHAR(64) NULL DEFAULT NULL"
            );
        }
        if (!barangay_column_exists($con, 'users', 'session_last_seen')) {
            @$con->query(
                "ALTER TABLE users ADD COLUMN session_last_seen DATETIME NULL DEFAULT NULL"
            );
        }
    }
}

if (!function_exists('barangay_session_guard_issue')) {
    /**
     * Create a session token for this user.
     * Non-SSA: sole active token in DB (invalidates any previous device).
     * SSA: local session token only so other browsers stay signed in.
     */
    function barangay_session_guard_issue(mysqli $con, string $userId): string
    {
        barangay_session_guard_ensure_columns($con);
        $token = bin2hex(random_bytes(32));
        $_SESSION['active_session_token'] = $token;

        if (barangay_session_guard_allows_multi_login($con, $userId)) {
            // Do not overwrite DB token — other SSA sessions must keep working.
            barangay_session_guard_touch($con, $userId);
            return $token;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $con->prepare(
            'UPDATE users SET active_session_token = ?, session_last_seen = ? WHERE id = ? LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('sss', $token, $now, $userId);
            $stmt->execute();
            $stmt->close();
        }
        return $token;
    }
}

if (!function_exists('barangay_session_guard_clear')) {
    function barangay_session_guard_clear(mysqli $con, string $userId): void
    {
        barangay_session_guard_ensure_columns($con);
        // SSA multi-login: only end this browser session; leave DB token alone
        // so other open SSA browsers are not marked idle/logged-out globally.
        if (!barangay_session_guard_allows_multi_login($con, $userId)) {
            $stmt = $con->prepare(
                'UPDATE users SET active_session_token = NULL, session_last_seen = NULL WHERE id = ? LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('s', $userId);
                $stmt->execute();
                $stmt->close();
            }
        }
        unset($_SESSION['active_session_token']);
    }
}

if (!function_exists('barangay_session_guard_is_active_elsewhere')) {
    /**
     * True when this account already has a fresh session that is not the caller's token.
     */
    function barangay_session_guard_is_active_elsewhere(
        mysqli $con,
        string $userId,
        ?string $callerToken = null
    ): bool {
        barangay_session_guard_ensure_columns($con);
        if (!barangay_column_exists($con, 'users', 'active_session_token')) {
            return false;
        }
        if (barangay_session_guard_allows_multi_login($con, $userId)) {
            return false;
        }

        $stmt = $con->prepare(
            'SELECT active_session_token, session_last_seen FROM users WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $token = trim((string) ($row['active_session_token'] ?? ''));
        if ($token === '') {
            return false;
        }
        if ($callerToken !== null && $callerToken !== '' && hash_equals($token, $callerToken)) {
            return false;
        }

        $lastSeen = trim((string) ($row['session_last_seen'] ?? ''));
        if ($lastSeen === '') {
            return true;
        }
        $ts = strtotime($lastSeen);
        if ($ts === false) {
            return true;
        }
        return (time() - $ts) <= barangay_session_guard_idle_seconds();
    }
}

if (!function_exists('barangay_session_guard_touch')) {
    function barangay_session_guard_touch(mysqli $con, string $userId): void
    {
        barangay_session_guard_ensure_columns($con);
        if (!barangay_column_exists($con, 'users', 'session_last_seen')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $stmt = $con->prepare('UPDATE users SET session_last_seen = ? WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('ss', $now, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (!function_exists('barangay_session_guard_enforce')) {
    /**
     * Call from authenticated pages. Drops the session if another login took over
     * or the stored token no longer matches.
     */
    function barangay_session_guard_enforce(mysqli $con, string $loginPath = '../login.php'): void
    {
        if (empty($_SESSION['user_id'])) {
            return;
        }

        barangay_session_guard_ensure_columns($con);
        if (!barangay_column_exists($con, 'users', 'active_session_token')) {
            return;
        }

        $userId = (string) $_SESSION['user_id'];
        $localToken = (string) ($_SESSION['active_session_token'] ?? '');
        $multiLogin = barangay_session_guard_allows_multi_login($con, $userId);

        // SSA (and other multi-login roles): keep every browser session alive.
        if ($multiLogin) {
            if ($localToken === '') {
                barangay_session_guard_issue($con, $userId);
            }
            $lastTouch = (int) ($_SESSION['session_last_touch'] ?? 0);
            if (time() - $lastTouch >= 60) {
                barangay_session_guard_touch($con, $userId);
                $_SESSION['session_last_touch'] = time();
            }
            return;
        }

        $stmt = $con->prepare(
            'SELECT active_session_token FROM users WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $dbToken = trim((string) ($row['active_session_token'] ?? ''));

        // Legacy sessions before this feature — issue a token once.
        if ($dbToken === '' && $localToken === '') {
            barangay_session_guard_issue($con, $userId);
            return;
        }

        if ($localToken === '' || $dbToken === '' || !hash_equals($dbToken, $localToken)) {
            unset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['username'], $_SESSION['active_session_token']);
            $sep = str_contains($loginPath, '?') ? '&' : '?';
            barangay_deny_access($loginPath . $sep . 'reason=session_taken');
        }

        // Throttle last_seen writes (once per ~60s) to reduce DB load.
        $lastTouch = (int) ($_SESSION['session_last_touch'] ?? 0);
        if (time() - $lastTouch >= 60) {
            barangay_session_guard_touch($con, $userId);
            $_SESSION['session_last_touch'] = time();
        }
    }
}
