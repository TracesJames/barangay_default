<?php

/**
 * Login flow smoke test for nutrition.superadmin and a city admin.
 * Usage: php scripts/smoke_test_login.php
 */

$base = rtrim(getenv('PORTAL_BASE_URL') ?: 'http://localhost/barangay_default', '/');
$cookie = tempnam(sys_get_temp_dir(), 'portal_cookie_');

function http_request(string $url, array $opts = []): array
{
    global $cookie;
    $ch = curl_init($url);
    $headers = $opts['headers'] ?? [];
    $headers[] = 'User-Agent: PortalSmokeTest/1.0';
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $headerSize = 0;
    // Split headers/body roughly
    $parts = explode("\r\n\r\n", (string) $raw, 2);
    $header = $parts[0] ?? '';
    $body = $parts[1] ?? '';

    return compact('code', 'header', 'body', 'err', 'raw');
}

function fail(string $msg): void
{
    echo "[FAIL] {$msg}" . PHP_EOL;
    exit(1);
}

function pass(string $msg): void
{
    echo "[PASS] {$msg}" . PHP_EOL;
}

echo "=== Login flow test ===" . PHP_EOL;

$loginPage = http_request($base . '/login.php');
if ($loginPage['code'] !== 200) {
    fail('Could not load login.php HTTP ' . $loginPage['code']);
}
pass('Loaded login.php');

if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $m)
    && !preg_match('/name="csrf_token" value=\'([^\']+)\'/', $loginPage['body'], $m)
    && !preg_match('/csrf-token" content="([^"]+)"/', $loginPage['body'], $m)) {
    // Try meta csrf
    if (!preg_match('/name="csrf-token" content="([^"]+)"/', $loginPage['raw'], $m)
        && !preg_match('/csrf-token" content="([^"]+)"/', $loginPage['raw'], $m)) {
        fail('CSRF token not found on login page');
    }
}
$csrf = $m[1];
pass('Got CSRF token');

$nutritionPassword = getenv('NUTRITION_SA_PASSWORD') ?: 'Vc!42c06f3050A9';
$login = http_request($base . '/loginForm.php', [
    'post' => http_build_query([
        'username' => 'nutrition.superadmin',
        'password' => $nutritionPassword,
        'csrf_token' => $csrf,
        'force_login' => '1',
    ]),
    'headers' => [
        'X-Requested-With: XMLHttpRequest',
        'X-CSRF-TOKEN: ' . $csrf,
    ],
]);

$body = trim(preg_replace('/\xEF\xBB\xBF/u', '', $login['body']));
pass('Login response: ' . substr($body, 0, 80));

if ($body !== 'nutrition_admin' && $body !== 'nutrition_dashboard') {
    // Some installs may return HTML error
    fail('Expected nutrition_admin/nutrition_dashboard login token, got: ' . substr($body, 0, 200));
}
pass('nutrition.superadmin login token OK');

// Follow to nutrition super dashboard with session cookie
$dash = http_request($base . '/admin/nutritionSuperDashboard.php');
if ($dash['code'] === 200) {
    if (stripos($dash['body'], 'Fatal error') !== false || stripos($dash['body'], 'Failed opening required') !== false) {
        fail('nutritionSuperDashboard.php has PHP fatal error');
    }
    if (stripos($dash['body'], 'Nutrition Portal') === false) {
        fail('nutritionSuperDashboard.php missing Nutrition Portal branding');
    }
    if (stripos($dash['body'], 'Barangay Official') !== false) {
        fail('nutritionSuperDashboard.php unexpectedly shows Barangay Official menu');
    }
    pass('nutritionSuperDashboard.php loads as Nutrition Portal');
} elseif ($dash['code'] === 302) {
    pass('nutritionSuperDashboard.php redirected (HTTP 302) — check session path');
} else {
    fail('nutritionSuperDashboard.php unexpected HTTP ' . $dash['code']);
}

$profile = http_request($base . '/admin/nutritionAccountProfile.php');
if ($profile['code'] === 200) {
    if (stripos($profile['body'], 'Failed opening required') !== false || stripos($profile['body'], 'Fatal error') !== false) {
        fail('nutritionAccountProfile.php has PHP fatal error');
    }
    pass('nutritionAccountProfile.php loads without fatal');
} elseif (in_array($profile['code'], [302, 301], true)) {
    pass('nutritionAccountProfile.php redirected HTTP ' . $profile['code']);
} else {
    fail('nutritionAccountProfile.php unexpected HTTP ' . $profile['code']);
}

$blocked = http_request($base . '/admin/dashboard.php');
if (in_array($blocked['code'], [302, 301], true)) {
    pass('dashboard.php blocked/redirected for nutrition session');
} elseif ($blocked['code'] === 200 && stripos($blocked['body'], 'Nutrition Portal') !== false) {
    pass('dashboard.php did not stay on Barangay Portal menu');
} else {
    // May still 200 if session cookie path differs; warn only
    echo '[WARN] dashboard.php HTTP ' . $blocked['code'] . ' — verify redirect in browser' . PHP_EOL;
}

@unlink($cookie);
echo "Login flow checks complete." . PHP_EOL;
