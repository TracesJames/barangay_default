<?php

if (!function_exists('barangay_app_base_path')) {
    function barangay_app_base_path(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        if (preg_match('#^/([^/]+)/(?:admin|secretary|resident|signup)(?:/|$)#', $script, $matches)) {
            $base = '/' . $matches[1] . '/';
            return $base;
        }

        $dir = dirname($script);
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            $base = '/';
            return $base;
        }

        $base = rtrim($dir, '/') . '/';
        return $base;
    }
}

if (!function_exists('barangay_start_session')) {
    function barangay_start_session(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
        $crossOrigin = function_exists('barangay_cors_is_cross_origin_enabled')
            && barangay_cors_is_cross_origin_enabled();

        // SameSite=None requires Secure; only when FRONTEND_URL(S) is configured.
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => barangay_app_base_path(),
            'secure' => $crossOrigin ? true : $isHttps,
            'httponly' => true,
            'samesite' => $crossOrigin ? 'None' : 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('barangay_release_session_lock')) {
    /**
     * Release the session file lock before slow work so other tabs/requests are not blocked.
     */
    function barangay_release_session_lock(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}

if (!function_exists('barangay_should_release_session_lock')) {
    function barangay_should_release_session_lock(): bool
    {
        return barangay_is_datatables_endpoint();
    }
}

if (!function_exists('barangay_is_datatables_endpoint')) {
    /**
     * True for read-only DataTables JSON endpoints (script name ends with Table.php).
     * CSRF must not be skipped for other POSTs merely because draw=1 is present.
     */
    function barangay_is_datatables_endpoint(): bool
    {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return (bool) preg_match('/Table\.php$/i', $script);
    }
}

if (!function_exists('barangay_request_expects_json')) {
    function barangay_request_expects_json(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (str_contains($accept, 'application/json')) {
            return true;
        }
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($requestedWith === 'xmlhttprequest') {
            return true;
        }

        return isset($_POST['ajax']) || isset($_GET['ajax']);
    }
}

if (!function_exists('barangay_require_post')) {
    /**
     * Reject non-POST requests for state-changing endpoints (blocks GET CSRF).
     */
    function barangay_require_post(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            return;
        }
        http_response_code(405);
        header('Allow: POST');
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: text/plain; charset=utf-8');
            exit('Method not allowed');
        }
        exit('Method not allowed');
    }
}

if (!function_exists('barangay_deny_access')) {
    function barangay_deny_access(string $loginPath = '../login.php'): void
    {
        $wantsJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_POST['ajax'])
            || isset($_GET['ajax'])
            || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        if ($wantsJson) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Your session expired. Please refresh the page and sign in again.',
                'login' => $loginPath,
            ]);
            exit;
        }
        header('Location: ' . $loginPath);
        exit;
    }
}

if (!function_exists('barangay_h')) {
    function barangay_h(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('barangay_html_date')) {
    /** HTML date input value (Y-m-d). Empty for missing or invalid dates. */
    function barangay_html_date(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }
        $ts = strtotime($value);

        return $ts ? date('Y-m-d', $ts) : '';
    }
}

if (!function_exists('barangay_verify_password')) {
  function barangay_verify_password(string $plain, string $stored): bool
  {
    if ($stored === '') {
      return false;
    }
    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon2')) {
      return password_verify($plain, $stored);
    }
    return hash_equals($stored, $plain);
  }
}

if (!function_exists('barangay_hash_password')) {
  function barangay_hash_password(string $plain): string
  {
    return password_hash($plain, PASSWORD_DEFAULT);
  }
}

if (!function_exists('barangay_password_min_length')) {
    function barangay_password_min_length(): int
    {
        return 8;
    }
}

if (!function_exists('barangay_password_expiry_days')) {
    /** Days after which nutrition staff must rotate passwords. 0 = disabled. */
    function barangay_password_expiry_days(): int
    {
        return 90;
    }
}

if (!function_exists('barangay_validate_password_policy')) {
    /**
     * Shared password policy (web + API).
     * @return string|null Error message, or null when valid.
     */
    function barangay_validate_password_policy(string $plain, string $username = ''): ?string
    {
        $min = barangay_password_min_length();
        if (strlen($plain) < $min) {
            return "Password must be at least {$min} characters.";
        }
        if (!preg_match('/[A-Z]/', $plain) || !preg_match('/[a-z]/', $plain) || !preg_match('/[0-9]/', $plain)) {
            return 'Password must include upper- and lowercase letters and at least one number.';
        }
        $username = trim($username);
        if ($username !== '' && strcasecmp($plain, $username) === 0) {
            return 'Password cannot be the same as the username.';
        }
        if (preg_match('/^(password|12345678|qwerty123|admin123|changeme)$/i', $plain)) {
            return 'Password is too common. Choose a stronger password.';
        }

        return null;
    }
}

if (!function_exists('barangay_ensure_password_meta_columns')) {
    function barangay_ensure_password_meta_columns(mysqli $con): void
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
        if (!barangay_column_exists($con, 'users', 'must_change_password')) {
            @$con->query(
                "ALTER TABLE `users` ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0"
            );
        }
        if (!barangay_column_exists($con, 'users', 'password_changed_at')) {
            @$con->query(
                "ALTER TABLE `users` ADD COLUMN `password_changed_at` DATETIME NULL DEFAULT NULL"
            );
        }
    }
}

if (!function_exists('barangay_user_is_nutrition_password_role')) {
    function barangay_user_is_nutrition_password_role(mysqli $con, string $userId): bool
    {
        if ($userId === '' || !function_exists('barangay_user_staff_role')) {
            return false;
        }
        if (!defined('STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR')) {
            require_once __DIR__ . '/staff_permissions.php';
        }
        $role = barangay_user_staff_role($con, $userId);

        return in_array($role, [
            STAFF_ROLE_NUTRITION_SUPER_ADMIN,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN,
            STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR,
            STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR,
        ], true);
    }
}

if (!function_exists('barangay_user_must_change_password')) {
    function barangay_user_must_change_password(mysqli $con, string $userId): bool
    {
        if ($userId === '') {
            return false;
        }
        barangay_ensure_password_meta_columns($con);
        $stmt = $con->prepare(
            'SELECT must_change_password, password_changed_at FROM users WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        if ((int) ($row['must_change_password'] ?? 0) === 1) {
            return true;
        }

        $days = barangay_password_expiry_days();
        if ($days <= 0 || !barangay_user_is_nutrition_password_role($con, $userId)) {
            return false;
        }
        $changedAt = trim((string) ($row['password_changed_at'] ?? ''));
        if ($changedAt === '') {
            return false;
        }
        $ts = strtotime($changedAt);
        if ($ts === false) {
            return false;
        }

        return (time() - $ts) >= ($days * 86400);
    }
}

if (!function_exists('barangay_mark_password_changed')) {
    function barangay_mark_password_changed(mysqli $con, string $userId, bool $requireChangeNext = false): void
    {
        if ($userId === '') {
            return;
        }
        barangay_ensure_password_meta_columns($con);
        $flag = $requireChangeNext ? 1 : 0;
        $stmt = $con->prepare(
            'UPDATE users
             SET must_change_password = ?, password_changed_at = NOW()
             WHERE id = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('is', $flag, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('barangay_password_change_redirect')) {
    /** Relative path from admin/ (or login root) for forced password change. */
    function barangay_password_change_redirect(mysqli $con, string $userId): string
    {
        if (function_exists('barangay_user_can_access_nutrition_portal')
            && barangay_user_can_access_nutrition_portal($con, $userId)) {
            return 'admin/nutritionAccountProfile.php?force_password=1';
        }

        return 'admin/myProfile.php?force_password=1';
    }
}
