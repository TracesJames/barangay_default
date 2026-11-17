<?php

/**
 * CLI: check MariaDB/MySQL for the barangay portal.
 * Exit 0 = healthy, 1 = down / query failed.
 *
 * Usage:
 *   php scripts/mysql_health_watchdog.php
 *   php scripts/mysql_health_watchdog.php --quiet
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$quiet = in_array('--quiet', $argv, true);
$logDir = 'C:\\xampp\\secure\\barangay_ops';
$logFile = $logDir . DIRECTORY_SEPARATOR . 'mysql_watchdog.log';
$configPath = 'C:\\xampp\\secure\\barangay_db\\barangay_db.php';
$config = is_file($configPath) ? require $configPath : [];
$host = (string) ($config['host'] ?? '127.0.0.1');
$port = (int) ($config['port'] ?? 3000);
if ($port <= 0) {
    $port = 3000;
}
$user = (string) ($config['user'] ?? 'barangay_app');
$pass = (string) ($config['password'] ?? '');
$name = (string) ($config['name'] ?? 'barangay');

$notes = [];
$ok = false;

$sock = @fsockopen($host, $port, $errno, $errstr, 3);
if ($sock) {
    fclose($sock);
    $notes[] = "port {$port} open";
} else {
    $notes[] = "port {$port} closed ({$errno} {$errstr})";
}

mysqli_report(MYSQLI_REPORT_OFF);

$link = @new mysqli($host, $user, $pass, $name, $port);
if ($link->connect_error) {
    $notes[] = 'connect failed: ' . $link->connect_error;
}

if (!$link->connect_error) {
    $res = $link->query('SELECT 1 AS ok, DATABASE() AS db, VERSION() AS v');
    $row = $res ? $res->fetch_assoc() : null;
    if ($row && (int) ($row['ok'] ?? 0) === 1) {
        $ok = true;
        $notes[] = 'SELECT 1 ok db=' . ($row['db'] ?? '') . ' ver=' . ($row['v'] ?? '');
    } else {
        $notes[] = 'SELECT 1 failed';
    }
    $link->close();
}

$line = date('c') . ' ' . ($ok ? 'OK' : 'FAIL') . ' ' . implode('; ', $notes) . PHP_EOL;
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
@file_put_contents($logFile, $line, FILE_APPEND);

if (!$quiet) {
    echo $line;
    if (!$ok) {
        echo "Action: Start the Windows MariaDB service (port {$port}). Check C:\\Program Files\\MariaDB 13.0\\data\\*.err if it fails.\n";
    }
}

exit($ok ? 0 : 1);
