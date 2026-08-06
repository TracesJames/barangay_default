<?php

require __DIR__ . '/../connection.php';
require __DIR__ . '/../includes/barangay_context.php';

$t = microtime(true);
$hub = barangay_hub_totals($con);
echo 'hub_totals: ' . round(microtime(true) - $t, 2) . 's population=' . $hub['population'] . PHP_EOL;

$t = microtime(true);
$rows = barangay_super_dashboard_rows($con);
echo 'super_rows: ' . round(microtime(true) - $t, 2) . 's count=' . count($rows) . PHP_EOL;

$t = microtime(true);
$scoped = barangay_scoped_resident_totals($con, '1');
echo 'scoped_totals: ' . round(microtime(true) - $t, 2) . 's total=' . $scoped['total'] . PHP_EOL;
