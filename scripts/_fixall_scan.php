<?php
$base = 'http://localhost/barangay_default';
$cookie = tempnam(sys_get_temp_dir(), 'fixall_');

function http(string $url, array $opts = []): array
{
    global $cookie;
    $ch = curl_init($url);
    $h = $opts['headers'] ?? [];
    $h[] = 'User-Agent: FixAll/1';
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 0,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_HEADER => 1,
        CURLOPT_HTTPHEADER => $h,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    preg_match('/^Location:\s*(.+)$/mi', substr((string) $raw, 0, $hs), $m);

    return ['code' => $code, 'loc' => trim($m[1] ?? ''), 'body' => substr((string) $raw, $hs)];
}

function bad(string $b): array
{
    $f = [];
    foreach ([
        '/Fatal error:[^\n<]+/i',
        '/Parse error:[^\n<]+/i',
        '/Unknown column [^\n<]+/i',
        '/Table \'[^\']+\' doesn\'t exist/i',
        '/Uncaught [^\n<]+/i',
        '/Failed opening required[^\n<]+/i',
        '/mysqli_sql_exception:[^\n<]+/i',
    ] as $re) {
        if (preg_match_all($re, $b, $m)) {
            foreach ($m[0] as $x) {
                $f[] = substr(trim($x), 0, 240);
            }
        }
    }

    return array_values(array_unique($f));
}

function clean(string $s): string
{
    return trim(preg_replace('/\xEF\xBB\xBF/u', '', $s));
}

function extract_csrf(string $html): string
{
    if (preg_match('/name="csrf_token"[^>]*value="([^"]+)"/', $html, $m)
        || preg_match('/csrf-token" content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    return '';
}

function login(string $user, string $pass, string $hub = ''): array
{
    global $base, $cookie;
    @unlink($cookie);
    $cookie = tempnam(sys_get_temp_dir(), 'fixall_');
    $lp = http($base . '/login.php' . ($hub !== '' ? ('?hub=' . $hub) : ''));
    $csrf = extract_csrf($lp['body']);
    $tok = clean(http($base . '/loginForm.php', [
        'post' => http_build_query([
            'username' => $user,
            'password' => $pass,
            'csrf_token' => $csrf,
            'force_login' => '1',
        ]),
        'headers' => [
            'X-Requested-With: XMLHttpRequest',
            'X-CSRF-TOKEN: ' . $csrf,
        ],
    ])['body']);

    return ['token' => $tok, 'csrf' => $csrf];
}

function open_barangay(string $barangayId, string $csrf, string $system = 'nutrition'): string
{
    global $base;

    return clean(http($base . '/admin/selectBarangay.php', [
        'post' => http_build_query([
            'barangay_id' => $barangayId,
            'system' => $system,
            'csrf_token' => $csrf,
            'redirect' => $system === 'nutrition' ? 'nutritionDashboard.php' : 'dashboard.php',
        ]),
        'headers' => [
            'X-Requested-With: XMLHttpRequest',
            'X-CSRF-TOKEN: ' . $csrf,
        ],
    ])['body']);
}

function check(string $label, string $path, bool $want200 = true): int
{
    global $base;
    $r = http($base . '/' . $path);
    $e = bad($r['body']);
    $ok = !$e && ($want200 ? $r['code'] === 200 : in_array($r['code'], [200, 301, 302], true));
    // staffAccounts role normalize redirect is OK
    if (!$ok && $r['code'] === 302 && str_contains($path, 'staffAccounts.php') && str_contains($r['loc'], 'staffAccounts.php')) {
        $ok = true;
    }
    echo ($ok ? 'PASS' : 'FAIL') . " [$label] /$path HTTP {$r['code']}"
        . ($r['loc'] !== '' ? " ->{$r['loc']}" : '')
        . ($e ? ' :: ' . implode(' | ', $e) : '')
        . "\n";

    return $ok ? 0 : 1;
}

require 'C:/xampp/htdocs/barangay_default/connection.php';
$con->query("UPDATE users SET active_session_token=NULL, session_last_seen=NULL WHERE username IN ('nutrition.superadmin','tlequin','secretary123')");

$pw = 'Vc!42c06f3050A9';
$fail = 0;
$barangayId = (string) ($con->query('SELECT id FROM barangay_information ORDER BY barangay LIMIT 1')->fetch_assoc()['id'] ?? '');

echo "=== Nutrition SA (city pages) ===\n";
$L = login('nutrition.superadmin', $pw, 'nutrition');
echo "nutrition_login={$L['token']}\n";
if (!in_array($L['token'], ['nutrition_admin', 'nutrition_dashboard'], true)) {
    echo "FAIL login nutrition\n";
    $fail++;
}
$fail += check('city', 'admin/nutritionSuperDashboard.php');
$fail += check('city', 'admin/nutritionAccountProfile.php');
$fail += check('city', 'admin/nutritionSuperPrintReport.php');
$fail += check('city', 'admin/nutritionMellpiCityProfile.php');
$fail += check('city', 'admin/nutritionProcessFormPrint.php');
$fail += check('city', 'admin/cityReportPack.php');
$fail += check('city', 'admin/barangayHub.php?picker=1&system=nutrition&view=picker');
$fail += check('city', 'admin/staffAccounts.php', false);
$fail += check('blocked', 'admin/dashboard.php', false);
$fail += check('blocked', 'admin/allResidence.php', false);

echo "=== Nutrition SA (open barangay) ===\n";
$csrf = extract_csrf(http($base . '/admin/barangayHub.php?picker=1&system=nutrition&view=picker')['body']) ?: $L['csrf'];
$open = open_barangay($barangayId, $csrf, 'nutrition');
echo "open=$open\n";
if (!str_contains($open, '"ok"')) {
    echo "FAIL open barangay\n";
    $fail++;
}
$fail += check('brgy', 'admin/nutritionDashboard.php');
$fail += check('brgy', 'admin/nutritionHouseholdSurvey.php');
$fail += check('brgy', 'admin/nutritionHouseholdSurveyForm.php');
$fail += check('brgy', 'admin/nutritionAssess.php');
$fail += check('brgy', 'admin/nutritionSettings.php');

echo "=== SSA ===\n";
$L = login('tlequin', $pw, 'barangay');
echo "ssa_login={$L['token']}\n";
$fail += check('city', 'admin/superDashboard.php');
$fail += check('city', 'admin/barangayHub.php?picker=1');
$fail += check('city', 'admin/staffAccounts.php');
$fail += check('city', 'admin/nutritionSuperDashboard.php');
$csrf = extract_csrf(http($base . '/admin/barangayHub.php?picker=1')['body']) ?: $L['csrf'];
$open = open_barangay($barangayId, $csrf, 'barangay');
echo "ssa_open=$open\n";
$fail += check('brgy', 'admin/dashboard.php');
$fail += check('brgy', 'admin/allResidence.php');
$fail += check('brgy', 'admin/certificateTable.php');

echo "=== Secretary ===\n";
$L = login('secretary123', $pw, 'barangay');
echo "secretary_login={$L['token']}\n";
$fail += check('sec', 'secretary/dashboard.php');
$fail += check('sec', 'secretary/allResidence.php');
$fail += check('sec', 'secretary/newResidence.php');
$fail += check('sec', 'secretary/allOfficial.php');
$fail += check('sec', 'secretary/blotterRecord.php');
$fail += check('sec', 'secretary/certificateTable.php');
$fail += check('sec', 'secretary/requestStatus.php');

echo "FAILS=$fail\n";
@unlink($cookie);
@unlink(__DIR__ . '/_sessprobe.php');
exit($fail > 0 ? 1 : 0);
