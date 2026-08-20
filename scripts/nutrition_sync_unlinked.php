<?php
/**
 * CLI: auto-link unlinked nutrition household surveys to barangay residents.
 *
 * php scripts/nutrition_sync_unlinked.php
 * php scripts/nutrition_sync_unlinked.php --barangay-id=abc123 --limit=50
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/connection.php';
require_once $root . '/includes/nutrition_residence_sync.php';

nutrition_ensure_module_tables($con);

$barangayId = null;
$limit = 200;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--barangay-id=')) {
        $barangayId = trim(substr($arg, 14));
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$stats = nutrition_auto_link_unlinked_surveys($con, $barangayId !== '' && $barangayId !== null ? $barangayId : null, $limit, 'cli');

echo 'Nutrition survey auto-link' . PHP_EOL;
echo '  linked:    ' . $stats['linked'] . PHP_EOL;
echo '  no_match:  ' . $stats['no_match'] . PHP_EOL;
echo '  skipped:   ' . $stats['skipped'] . PHP_EOL;
if ($stats['errors'] !== []) {
    echo '  errors:' . PHP_EOL;
    foreach ($stats['errors'] as $err) {
        echo '    - ' . $err . PHP_EOL;
    }
}

exit($stats['errors'] === [] ? 0 : 1);
