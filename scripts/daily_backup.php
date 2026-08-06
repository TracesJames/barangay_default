<?php

/**
 * Daily backup: database SQL + uploads copy.
 *
 * CLI: php scripts/daily_backup.php
 * Windows Task Scheduler: scripts/daily_backup.bat
 *
 * Output: C:\xampp\secure\backups\YYYY-MM-DD\
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$root = dirname(__DIR__);
require_once $root . '/connection.php';
require_once $root . '/includes/helpers.php';

$secureRoot = 'C:\\xampp\\secure\\backups';
$day = date('Y-m-d');
$outDir = $secureRoot . DIRECTORY_SEPARATOR . $day;
$keepDays = 14;

if (!is_dir($secureRoot) && !mkdir($secureRoot, 0755, true) && !is_dir($secureRoot)) {
    fwrite(STDERR, "Cannot create backup root: {$secureRoot}\n");
    exit(1);
}
if (!is_dir($outDir) && !mkdir($outDir, 0755, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Cannot create day folder: {$outDir}\n");
    exit(1);
}

$stamp = date('Ymd_His');
$sqlFile = $outDir . DIRECTORY_SEPARATOR . 'barangay_' . $stamp . '.sql';
$uploadsZip = $outDir . DIRECTORY_SEPARATOR . 'uploads_' . $stamp . '.zip';
$logFile = $outDir . DIRECTORY_SEPARATOR . 'backup.log';

function blog(string $path, string $line): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL;
    file_put_contents($path, $line, FILE_APPEND);
    echo $line;
}

blog($logFile, 'Starting daily backup');

$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
$okSql = false;
if (is_file($mysqldump)) {
    $passArg = DB_PASSWORD !== '' ? '-p' . escapeshellarg(DB_PASSWORD) : '';
    $cmd = sprintf(
        '"%s" -h%s -u%s %s --single-transaction --routines --triggers %s > %s',
        $mysqldump,
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        $passArg,
        escapeshellarg(DB_NAME),
        escapeshellarg($sqlFile)
    );
    // Windows cmd redirection needs careful quoting
    $cmd = '"' . $mysqldump . '" -h' . DB_HOST . ' -u' . DB_USER
        . (DB_PASSWORD !== '' ? ' -p' . DB_PASSWORD : '')
        . ' --single-transaction --routines --triggers ' . DB_NAME
        . ' > "' . $sqlFile . '" 2> "' . $outDir . DIRECTORY_SEPARATOR . 'mysqldump.err"';
    exec('cmd /C ' . $cmd, $out, $code);
    $okSql = $code === 0 && is_file($sqlFile) && filesize($sqlFile) > 100;
    blog($logFile, $okSql ? 'mysqldump OK: ' . basename($sqlFile) : 'mysqldump failed code=' . $code);
}

if (!$okSql) {
    // Fallback: PHP dump of key tables
    $fh = fopen($sqlFile, 'wb');
    if ($fh) {
        fwrite($fh, "-- PHP fallback dump " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n");
        $tables = [];
        $res = $con->query('SHOW TABLES');
        while ($res && ($row = $res->fetch_row())) {
            $tables[] = $row[0];
        }
        foreach ($tables as $table) {
            $create = $con->query('SHOW CREATE TABLE `' . $con->real_escape_string($table) . '`');
            $crow = $create ? $create->fetch_assoc() : null;
            if ($crow) {
                $createSql = $crow['Create Table'] ?? ($crow['Create View'] ?? '');
                fwrite($fh, "\nDROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n");
            }
            $data = $con->query('SELECT * FROM `' . $con->real_escape_string($table) . '`');
            while ($data && ($r = $data->fetch_assoc())) {
                $cols = array_map(static fn ($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($r));
                $vals = [];
                foreach ($r as $v) {
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = "'" . $con->real_escape_string((string) $v) . "'";
                    }
                }
                fwrite($fh, 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n");
            }
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        $okSql = is_file($sqlFile) && filesize($sqlFile) > 100;
        blog($logFile, $okSql ? 'PHP fallback dump OK' : 'PHP fallback dump failed');
    }
}

$uploadsDir = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
$okUploads = false;
if (is_dir($uploadsDir) && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($uploadsZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            $local = substr($path, strlen($uploadsDir) + 1);
            $zip->addFile($path, str_replace('\\', '/', $local));
        }
        $zip->close();
        $okUploads = is_file($uploadsZip);
    }
}
blog($logFile, $okUploads ? 'Uploads zip OK' : 'Uploads zip skipped/failed');

// Prune old day folders
$dirs = glob($secureRoot . DIRECTORY_SEPARATOR . '20*', GLOB_ONLYDIR) ?: [];
rsort($dirs);
foreach (array_slice($dirs, $keepDays) as $old) {
    $files = glob($old . DIRECTORY_SEPARATOR . '*') ?: [];
    foreach ($files as $f) {
        @unlink($f);
    }
    @rmdir($old);
    blog($logFile, 'Pruned ' . basename($old));
}

$ok = $okSql;
blog($logFile, $ok ? 'BACKUP SUCCESS' : 'BACKUP FAILED');
exit($ok ? 0 : 1);
