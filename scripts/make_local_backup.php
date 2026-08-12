<?php

/**
 * Local project + MariaDB backup into /backup (web-denied via .htaccess).
 * Usage: php scripts/make_local_backup.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$backupRoot = $root . DIRECTORY_SEPARATOR . 'backup';
$stamp = date('Ymd_His');
$runDir = $backupRoot . DIRECTORY_SEPARATOR . 'run_' . $stamp;

if (!is_dir($backupRoot) && !mkdir($backupRoot, 0755, true)) {
    fwrite(STDERR, "Cannot create backup/\n");
    exit(1);
}

if (!is_dir($runDir) && !mkdir($runDir, 0755, true)) {
    fwrite(STDERR, "Cannot create {$runDir}\n");
    exit(1);
}

$htaccess = $backupRoot . DIRECTORY_SEPARATOR . '.htaccess';
if (!is_file($htaccess)) {
    file_put_contents($htaccess, "Require all denied\n");
}

$configPath = 'C:\\xampp\\secure\\barangay_db\\barangay_db.php';
if (!is_file($configPath)) {
    $configPath = 'C:\\xampp\\secure\\barangay_db.php';
}
$config = is_file($configPath) ? require $configPath : null;
if (!is_array($config)) {
    fwrite(STDERR, "Missing DB config at {$configPath}\n");
    exit(1);
}

$host = (string) ($config['host'] ?? 'localhost');
$user = (string) ($config['user'] ?? 'root');
$pass = (string) ($config['password'] ?? '');
$name = (string) ($config['name'] ?? 'barangay');

$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (!is_file($mysqldump)) {
    fwrite(STDERR, "mysqldump not found: {$mysqldump}\n");
    exit(1);
}

$sqlFile = $runDir . DIRECTORY_SEPARATOR . $name . '_' . $stamp . '.sql';
$descriptor = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

/**
 * @return array{0:int,1:string,2:string}
 */
function run_mysqldump(string $mysqldump, string $host, string $user, string $pass, string $name, string $sqlFile, array $descriptor): array
{
    putenv('MYSQL_PWD=' . $pass);
    $cmd = [
        $mysqldump,
        '--host=' . $host,
        '--user=' . $user,
        '--single-transaction',
        '--routines',
        '--triggers',
        '--events',
        '--default-character-set=utf8mb4',
        '--result-file=' . $sqlFile,
        $name,
    ];
    $proc = proc_open($cmd, $descriptor, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        putenv('MYSQL_PWD');

        return [1, '', 'Failed to start mysqldump'];
    }
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    putenv('MYSQL_PWD');

    return [$code, $stdout, $stderr];
}

[$code, $stdout, $stderr] = run_mysqldump($mysqldump, $host, $user, $pass, $name, $sqlFile, $descriptor);
$userUsed = $user;

if ($code !== 0 || !is_file($sqlFile) || filesize($sqlFile) < 100) {
    if ($user !== 'root') {
        echo "App-user dump failed (exit {$code}); retrying as root...\n";
        if (trim($stderr) !== '') {
            echo trim($stderr) . "\n";
        }
        [$code, $stdout, $stderr] = run_mysqldump($mysqldump, $host, 'root', '', $name, $sqlFile, $descriptor);
        $userUsed = 'root';
    }
}

if ($code !== 0 || !is_file($sqlFile) || filesize($sqlFile) < 100) {
    fwrite(STDERR, "DB dump failed (exit {$code})\n" . trim($stderr) . "\n");
    exit(1);
}

$sqlBytes = (int) filesize($sqlFile);
echo "DB dump OK: {$sqlFile} (" . number_format($sqlBytes) . " bytes) via {$userUsed}\n";

$zipFile = $runDir . DIRECTORY_SEPARATOR . 'code_' . $stamp . '.zip';
$tar = 'C:\\Windows\\System32\\tar.exe';
$excludeArgs = [
    '--exclude=backup',
    '--exclude=assets/uploads',
    '--exclude=_update_backup_20260727',
    '--exclude=_update_backup_20260727b',
    '--exclude=node_modules',
    '--exclude=vendor',
    '--exclude=.git',
    '--exclude=*.zip',
    '--exclude=*.sql',
    '--exclude=*.sql.gz',
];

if (!is_file($tar)) {
    fwrite(STDERR, "tar.exe not found; skipping code zip\n");
    exit(1);
}

$tarCmd = array_merge([$tar, '-a', '-cf', $zipFile, '-C', $root], $excludeArgs, ['.']);
$proc = proc_open($tarCmd, $descriptor, $pipes, null, null, ['bypass_shell' => true]);
if (!is_resource($proc)) {
    fwrite(STDERR, "Failed to start tar for code zip\n");
    exit(1);
}
fclose($pipes[0]);
stream_get_contents($pipes[1]);
$tarErr = (string) stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$tarCode = proc_close($proc);
if ($tarCode !== 0 || !is_file($zipFile)) {
    fwrite(STDERR, "Code zip failed (exit {$tarCode})\n" . trim($tarErr) . "\n");
    exit(1);
}

$manifest = [
    'created_at' => date('c'),
    'db_name' => $name,
    'db_host' => $host,
    'db_user_used' => $userUsed,
    'sql_file' => basename($sqlFile),
    'sql_bytes' => $sqlBytes,
    'code_zip' => basename($zipFile),
    'code_zip_bytes' => (int) filesize($zipFile),
    'notes' => 'Local safety backup before stability/permission hardening.',
];
file_put_contents(
    $runDir . DIRECTORY_SEPARATOR . 'MANIFEST.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
);

echo 'Code zip OK: ' . $zipFile . ' (' . number_format((int) filesize($zipFile)) . " bytes)\n";
echo "Backup folder: {$runDir}\n";
echo "Done.\n";
