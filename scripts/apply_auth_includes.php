<?php

$base = dirname(__DIR__);
$folders = [
    $base . '/admin' => 'auth_admin.php',
    $base . '/secretary' => 'auth_secretary.php',
    $base . '/resident' => 'auth_resident.php',
];

foreach ($folders as $dir => $authFile) {
    foreach (glob($dir . '/*.php') as $file) {
        $content = file_get_contents($file);
        if (str_contains($content, "includes/$authFile")) {
            continue;
        }
        $needle = "include_once '../connection.php';";
        if (!str_contains($content, $needle)) {
            echo "SKIP (no connection): " . basename($file) . PHP_EOL;
            continue;
        }
        $replacement = $needle . "\ninclude_once '../includes/$authFile';";
        $newContent = str_replace($needle, $replacement, $content, $count);
        if ($count > 0) {
            file_put_contents($file, $newContent);
            echo "Updated: " . basename($file) . PHP_EOL;
        }
    }
}

echo "Done.\n";
