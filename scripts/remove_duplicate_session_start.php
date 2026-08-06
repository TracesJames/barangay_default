<?php

$base = dirname(__DIR__);
$dirs = [$base . '/admin', $base . '/secretary', $base . '/resident'];
$authPattern = '/includes\/auth_(admin|secretary|resident)\.php/';

foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.php') as $file) {
        $content = file_get_contents($file);
        if (!preg_match($authPattern, $content)) {
            continue;
        }
        if (!str_contains($content, 'session_start()')) {
            continue;
        }

        $newContent = preg_replace('/^\s*session_start\(\);\s*\r?\n/m', '', $content, -1, $count);
        if ($count > 0 && $newContent !== $content) {
            file_put_contents($file, $newContent);
            echo 'Removed session_start: ' . basename($file) . PHP_EOL;
        }
    }
}

echo "Done.\n";
