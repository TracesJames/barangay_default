<?php
/**
 * Remove BOM and blank lines before the opening <?php tag.
 */
$root = dirname(__DIR__);
$skip = ['vendor', 'node_modules', 'assets' . DIRECTORY_SEPARATOR . 'plugins', 'assets' . DIRECTORY_SEPARATOR . 'dist'];
$fixed = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
    foreach ($skip as $part) {
        if (str_contains($relative, str_replace('\\', '/', $part))) {
            continue 2;
        }
    }

    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $original = $content;
    if (str_starts_with($content, "\xEF\xBB\xBF")) {
        $content = substr($content, 3);
    }
    $content = preg_replace('/^[\s\r\n]+(?=<\?php)/', '', $content, 1);

    if ($content !== $original) {
        file_put_contents($path, $content);
        $fixed++;
        echo "fixed: $relative\n";
    }
}

echo "Total fixed: $fixed\n";
