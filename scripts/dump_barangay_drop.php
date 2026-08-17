<?php

/**
 * Write a DROP + recreate SQL dump outside webroot.
 * Usage: php scripts/dump_barangay_drop.php
 *
 * Output: C:\xampp\secure\barangay_db\barangay_drop.sql
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$configPath = 'C:\\xampp\\secure\\barangay_db\\barangay_db.php';
if (!is_file($configPath)) {
    $configPath = 'C:\\xampp\\secure\\barangay_db.php';
}
$config = is_file($configPath) ? require $configPath : null;
if (!is_array($config)) {
    fwrite(STDERR, "Missing DB config.\n");
    exit(1);
}

$host = (string) ($config['host'] ?? 'localhost');
$user = (string) ($config['user'] ?? 'barangay_app');
$pass = (string) ($config['password'] ?? '');
$name = (string) ($config['name'] ?? 'barangay');

$outDir = 'C:\\xampp\\secure\\barangay_db';
if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Cannot create {$outDir}\n");
    exit(1);
}

$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (!is_file($mysqldump)) {
    fwrite(STDERR, "mysqldump not found: {$mysqldump}\n");
    exit(1);
}

$outFile = $outDir . DIRECTORY_SEPARATOR . $name . '_drop.sql';

/**
 * @return array{0:int,1:string}
 */
function dump_db(string $mysqldump, string $host, string $user, string $pass, string $name, string $outFile): array
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
        '--add-drop-database',
        '--add-drop-table',
        '--databases',
        $name,
        '--result-file=' . $outFile,
    ];
    $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        putenv('MYSQL_PWD');

        return [1, 'Failed to start mysqldump'];
    }
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    putenv('MYSQL_PWD');

    return [$code, $stderr];
}

[$code, $stderr] = dump_db($mysqldump, $host, $user, $pass, $name, $outFile);
$used = $user;
if ($code !== 0 || !is_file($outFile) || filesize($outFile) < 100) {
    echo "App-user dump failed (exit {$code}); retrying as root...\n";
    if (trim($stderr) !== '') {
        echo trim($stderr) . "\n";
    }
    [$code, $stderr] = dump_db($mysqldump, $host, 'root', '', $name, $outFile);
    $used = 'root';
}

if ($code !== 0 || !is_file($outFile) || filesize($outFile) < 100) {
    fwrite(STDERR, "Dump failed (exit {$code})\n" . trim($stderr) . "\n");
    exit(1);
}

$bytes = (int) filesize($outFile);
echo "Drop file OK: {$outFile} (" . number_format($bytes) . " bytes) via {$used}\n";
