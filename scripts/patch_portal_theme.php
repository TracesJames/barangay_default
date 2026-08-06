<?php
/**
 * Apply shared barangay-portal body class across admin, secretary, and resident pages.
 */
$roots = [
    __DIR__ . '/../admin',
    __DIR__ . '/../secretary',
    __DIR__ . '/../resident',
];

$updated = 0;

foreach ($roots as $root) {
    foreach (glob($root . '/*.php') ?: [] as $file) {
        $content = file_get_contents($file);
        if ($content === false || strpos($content, '<body class=') === false) {
            continue;
        }

        $newContent = preg_replace_callback(
            '/<body class="([^"]*)">/',
            static function (array $m): string {
                $classes = preg_split('/\s+/', trim($m[1])) ?: [];
                $classes = array_values(array_filter($classes, static fn ($c) => $c !== ''));

                if (!in_array('barangay-portal', $classes, true)) {
                    $classes[] = 'barangay-portal';
                }

                // Normalize spacing in legacy class strings.
                $classes = array_values(array_unique($classes));
                return '<body class="' . implode(' ', $classes) . '">';
            },
            $content,
            1,
            $count
        );

        if ($count && $newContent !== $content) {
            file_put_contents($file, $newContent);
            echo 'Updated body: ' . str_replace(__DIR__ . '/../', '', $file) . PHP_EOL;
            $updated++;
        }
    }
}

// Remove duplicate barangay.css when head_csrf.php already loads it.
$residentFiles = glob(__DIR__ . '/../resident/*.php') ?: [];
foreach ($residentFiles as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $needle = "  <link rel=\"stylesheet\" href=\"../assets/css/barangay.css\">\n";
    if (strpos($content, $needle) !== false && strpos($content, 'head_csrf.php') !== false) {
        $newContent = str_replace($needle, '', $content);
        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            echo 'Removed duplicate barangay.css: ' . basename($file) . PHP_EOL;
            $updated++;
        }
    }
}

echo "Done. {$updated} file(s) updated.\n";
