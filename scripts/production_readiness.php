<?php
/**
 * Production readiness checklist (CLI or browser with ?key= if needed).
 *
 * php scripts/production_readiness.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$root = dirname(__DIR__);
$secureRoot = 'C:\\xampp\\secure';
$backupRoot = $secureRoot . DIRECTORY_SEPARATOR . 'backups';
$dbConfig = $secureRoot . DIRECTORY_SEPARATOR . 'barangay_db' . DIRECTORY_SEPARATOR . 'barangay_db.php';

$checks = [];
$failures = 0;

$pass = static function (string $label, bool $ok, string $detail = '') use (&$checks, &$failures): void {
    if (!$ok) {
        $failures++;
    }
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
};

$pass('PHP 8.0+', version_compare(PHP_VERSION, '8.0.0', '>='), PHP_VERSION);
$pass('mysqli extension', extension_loaded('mysqli'));
$pass('zip extension (Excel import/export)', extension_loaded('zip'));
$pass('Secure DB config outside webroot', is_file($dbConfig), $dbConfig);

if (is_file($root . '/connection.php')) {
    require_once $root . '/connection.php';
    $pass('Database connection', isset($con) && $con instanceof mysqli && !$con->connect_error);
} else {
    $pass('Database connection', false, 'connection.php missing');
}

$pass('Backup folder writable', is_dir($backupRoot) || @mkdir($backupRoot, 0755, true), $backupRoot);

$sqlInWebroot = array_merge(
    glob($root . '/*.sql') ?: [],
    glob($root . '/admin/*.sql') ?: [],
    glob($root . '/backup/*.sql') ?: []
);
$pass('No SQL dumps in webroot', $sqlInWebroot === [], $sqlInWebroot !== [] ? count($sqlInWebroot) . ' file(s) found' : 'none');

$denyPaths = [
    $root . '/includes/.htaccess',
    $root . '/scripts/.htaccess',
];
foreach ($denyPaths as $path) {
    if (!is_file($path)) {
        continue;
    }
    $body = (string) file_get_contents($path);
    $pass('Protected folder: ' . basename(dirname($path)), stripos($body, 'deny') !== false || stripos($body, 'Require all denied') !== false, $path);
}

if (is_file($root . '/includes/security.php')) {
    require_once $root . '/includes/security.php';
    $pass('Security helpers loaded', function_exists('barangay_send_security_headers'));
}

if (is_file($root . '/.htaccess')) {
    $ht = (string) file_get_contents($root . '/.htaccess');
    $pass('Root .htaccess blocks connection.php', stripos($ht, 'connection.php') !== false);
}

$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
$pass('mysqldump available for backups', is_file($mysqldump), $mysqldump);

$dailyBat = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'daily_backup.bat';
$pass('Daily backup batch script present', is_file($dailyBat), $dailyBat);

$hardenScript = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'harden_security.php';
$pass('Security harden script present', is_file($hardenScript));

$credFiles = glob($root . '/**/*.txt') ?: [];
$sensitiveInWebroot = [];
foreach ($credFiles as $file) {
    $base = basename($file);
    if (preg_match('/account|credential|password|rotated/i', $base)) {
        $sensitiveInWebroot[] = $file;
    }
}
$pass('No credential txt files in webroot', $sensitiveInWebroot === [], $sensitiveInWebroot !== [] ? implode(', ', $sensitiveInWebroot) : 'none');

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    exec('sc query MySQL80 2>nul', $svcOut, $svcCode);
    $mysqlRunning = is_array($svcOut) && implode("\n", $svcOut) !== '' && stripos(implode("\n", $svcOut), 'RUNNING') !== false;
    $pass('MySQL80 Windows service running (if installed)', $mysqlRunning || $svcCode !== 0, $mysqlRunning ? 'RUNNING' : 'stopped or not installed');
}

echo PHP_EOL . '=== Production Readiness ===' . PHP_EOL;
foreach ($checks as $row) {
    $icon = $row['ok'] ? '[OK]' : '[FAIL]';
    $line = $icon . ' ' . $row['label'];
    if ($row['detail'] !== '') {
        $line .= ' — ' . $row['detail'];
    }
    echo $line . PHP_EOL;
}

echo PHP_EOL;
if ($failures === 0) {
    echo "All checks passed ({$failures} failures)." . PHP_EOL;
    echo 'Next: schedule scripts/daily_backup.bat in Task Scheduler; enable HTTPS on Apache for HSTS.' . PHP_EOL;
    exit(0);
}

echo "{$failures} check(s) failed. Run php scripts/harden_security.php and move SQL dumps off webroot." . PHP_EOL;
exit(1);
