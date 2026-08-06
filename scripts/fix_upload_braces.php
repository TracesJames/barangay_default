<?php

$base = dirname(__DIR__);
$dirs = [$base . '/admin', $base . '/secretary', $base . '/resident', $base . '/signup'];

foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.php') as $file) {
        $content = file_get_contents($file);
        $original = $content;

        // Remove stray closing brace left by upload patch
        $content = preg_replace(
            "/(\\\$new_image_path = \\\$upload\\['path'\\];\\s*\\n)\\}\\s*\\n\\}/",
            "$1}",
            $content
        );

        if ($content !== $original) {
            file_put_contents($file, $content);
            echo 'Fixed brace: ' . basename($file) . PHP_EOL;
        }
    }
}

echo "Done.\n";
