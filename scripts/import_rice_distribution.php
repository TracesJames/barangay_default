<?php
/**
 * Import rice distribution resident list into barangay portal.
 *
 * Usage:
 *   php scripts/import_rice_distribution.php
 *   php scripts/import_rice_distribution.php --file="C:\path\file.xlsx"
 *   php scripts/import_rice_distribution.php --barangay=Bagontaas --limit=100
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/rice_distribution_import.php';

$defaultFile = 'C:\\Users\\trace\\Downloads\\rice_distribution_second_wave_list.xlsx';
$file = $defaultFile;
$onlyBarangay = null;
$limit = 0;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $file = substr($arg, 7);
    } elseif (str_starts_with($arg, '--barangay=')) {
        $onlyBarangay = substr($arg, 11);
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    }
}

if (!is_file($file)) {
    fwrite(STDERR, "File not found: $file\n");
    exit(1);
}

echo "Importing from: $file\n";
if ($onlyBarangay !== null) {
    echo "Barangay filter: $onlyBarangay\n";
}
if ($limit > 0) {
    echo "Row limit: $limit\n";
}

$started = microtime(true);
$result = rice_distribution_import_workbook($con, $file, $onlyBarangay, $limit);
$elapsed = round(microtime(true) - $started, 2);

echo "\nDone in {$elapsed}s\n";
echo 'Inserted: ' . $result['inserted'] . PHP_EOL;
echo 'Skipped: ' . $result['skipped'] . PHP_EOL;
echo 'Failed: ' . $result['failed'] . PHP_EOL;

if ($result['errors'] !== []) {
    echo "Errors:\n";
    foreach ($result['errors'] as $error) {
        echo " - $error\n";
    }
}
