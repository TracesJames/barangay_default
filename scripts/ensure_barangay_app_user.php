<?php

/**
 * Probe DB users and ensure barangay_app (not root) is configured.
 * Usage:
 *   php scripts/ensure_barangay_app_user.php          # probe only
 *   php scripts/ensure_barangay_app_user.php --fix   # create/grant + update secure config
 */

declare(strict_types=1);

$fix = in_array('--fix', $argv, true);
$configPath = 'C:\\xampp\\secure\\barangay_db\\barangay_db.php';
if (!is_file($configPath)) {
    $configPath = 'C:\\xampp\\secure\\barangay_db.php';
}
if (!is_file($configPath)) {
    fwrite(STDERR, "Missing {$configPath}\n");
    exit(1);
}

$config = require $configPath;
$host = (string) ($config['host'] ?? 'localhost');
$user = (string) ($config['user'] ?? 'barangay_app');
$pass = (string) ($config['password'] ?? '');
$name = (string) ($config['name'] ?? 'barangay');

function try_connect(string $host, string $user, string $pass, string $name): array
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $m = @new mysqli($host, $user, $pass, $name);
    if ($m->connect_error) {
        return [false, $m->connect_error];
    }
    $m->close();

    return [true, ''];
}

[$cfgOk, $cfgErr] = try_connect($host, $user, $pass, $name);
[$rootOk] = try_connect($host, 'root', '', $name);
[$appOk] = try_connect($host, 'barangay_app', $pass, $name);

echo "Config user={$user} db={$name} host={$host}\n";
echo $cfgOk ? "Config connect: OK\n" : "Config connect: FAIL ({$cfgErr})\n";
echo $rootOk ? "Root connect: OK\n" : "Root connect: FAIL\n";
echo $appOk ? "barangay_app (same pass) connect: OK\n" : "barangay_app (same pass) connect: FAIL\n";

$needsMigrate = strtolower($user) === 'root' || !$cfgOk || !$appOk;
if (!$needsMigrate && strtolower($user) === 'barangay_app' && $cfgOk) {
    echo "Already on barangay_app. No fix needed.\n";
    exit(0);
}

if (!$fix) {
    if (strtolower($user) === 'root') {
        echo "Config still uses root. Run with --fix to migrate to barangay_app.\n";
        exit(2);
    }
    echo "Run with --fix to recreate grants for barangay_app (local XAMPP).\n";
    exit(2);
}

if (!$rootOk) {
    fwrite(STDERR, "Cannot fix without root access.\n");
    exit(1);
}

$newPass = ($pass !== '' && strtolower($user) !== 'root')
    ? $pass
    : ('BgApp!' . bin2hex(random_bytes(6)));

$root = new mysqli($host, 'root', '', 'mysql');
if ($root->connect_error) {
    fwrite(STDERR, 'Root mysql connect failed: ' . $root->connect_error . "\n");
    exit(1);
}

$escUser = $root->real_escape_string('barangay_app');
$escPass = $root->real_escape_string($newPass);
$escDb = $root->real_escape_string($name);

$statements = [
    "CREATE USER IF NOT EXISTS '{$escUser}'@'localhost' IDENTIFIED BY '{$escPass}'",
    "CREATE USER IF NOT EXISTS '{$escUser}'@'127.0.0.1' IDENTIFIED BY '{$escPass}'",
    "ALTER USER '{$escUser}'@'localhost' IDENTIFIED BY '{$escPass}'",
    "ALTER USER '{$escUser}'@'127.0.0.1' IDENTIFIED BY '{$escPass}'",
    "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, TRIGGER, EVENT ON `{$escDb}`.* TO '{$escUser}'@'localhost'",
    "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, TRIGGER, EVENT ON `{$escDb}`.* TO '{$escUser}'@'127.0.0.1'",
    'FLUSH PRIVILEGES',
];

foreach ($statements as $sql) {
    if (!$root->query($sql)) {
        echo 'Warn: ' . $root->error . "\n";
    }
}
$root->close();

[$fixedOk, $fixedErr] = try_connect($host, 'barangay_app', $newPass, $name);
if (!$fixedOk) {
    fwrite(STDERR, "barangay_app still cannot connect: {$fixedErr}\n");
    exit(1);
}

$export = var_export([
    'host' => $host,
    'user' => 'barangay_app',
    'password' => $newPass,
    'name' => $name,
], true);

$php = "<?php\n\n// Outside webroot — do not commit.\nreturn {$export};\n";
$bak = $configPath . '.bak_' . date('Ymd_His');
if (!copy($configPath, $bak)) {
    fwrite(STDERR, "Could not backup secure config\n");
    exit(1);
}
if (file_put_contents($configPath, $php) === false) {
    fwrite(STDERR, "Could not write secure config\n");
    exit(1);
}

// Verify app bootstrap still connects (may use fallback, but prefer app user).
require_once dirname(__DIR__) . '/connection.php';
$using = '';
if (isset($con) && $con instanceof mysqli) {
    $res = $con->query('SELECT CURRENT_USER() AS u');
    $row = $res ? $res->fetch_assoc() : null;
    $using = (string) ($row['u'] ?? '');
}

echo "barangay_app grants OK. Secure config updated (backup: {$bak}).\n";
echo 'Password length: ' . strlen($newPass) . " (stored only in C:\\xampp\\secure\\barangay_db\\barangay_db.php)\n";
echo ($using !== '' ? "connection.php CURRENT_USER: {$using}\n" : '');
exit(0);
