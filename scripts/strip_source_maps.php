<?php
/**
 * Strip sourceMappingURL comments from assets CSS/JS (production hygiene).
 * Does not delete .map files; .htaccess already denies them.
 */
$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$count = 0;
foreach ($rii as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if ($ext !== 'css' && $ext !== 'js') {
        continue;
    }
    $path = $file->getPathname();
    $c = file_get_contents($path);
    if ($c === false || stripos($c, 'sourceMappingURL') === false) {
        continue;
    }
    $n = preg_replace('~/\*#\s*sourceMappingURL=.*?\*/~s', '', $c);
    $n = preg_replace('~//#\s*sourceMappingURL=.*$~m', '', $n);
    if ($n !== null && $n !== $c) {
        file_put_contents($path, $n);
        $count++;
        echo str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $path), PHP_EOL;
    }
}
echo "--- stripped: {$count} files ---", PHP_EOL;
