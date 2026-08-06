<?php
/**
 * Replace legacy SweetAlert ajax error handlers with barangayAjaxError().
 */
$root = dirname(__DIR__);
$dirs = ['admin', 'secretary', 'resident', '.'];
$patterns = [
    [
        '/\.fail\s*\(\s*function\s*\(\s*\)\s*\{\s*Swal\.fire\s*\(\s*\{\s*title:\s*[\'"]<strong class="text-danger">Ooppss\.\.<\/strong>[\'"],\s*type:\s*[\'"]error[\'"],\s*html:\s*[\'"]<b>Something went wrong with ajax !<b>[\'"],\s*width:\s*[\'"]400px[\'"],\s*confirmButtonColor:\s*[\'"]#6610f2[\'"],\s*\}\s*\)\s*\}\s*\)/s',
        '.fail(barangayAjaxError)',
    ],
    [
        '/\.fail\s*\(\s*function\s*\(\s*\)\s*\{\s*Swal\.fire\s*\(\s*\{\s*title:\s*[\'"]Ooppss\.\.\.[\'"],\s*text:\s*[\'"]Something went wrong with ajax ![\'"],\s*type:\s*[\'"]error[\'"],\s*confirmButtonColor:\s*[\'"]#6610f2[\'"],\s*allowOutsideClick:\s*false,\s*width:\s*[\'"]400px[\'"],\s*\}\s*\)\s*\}\s*\)/s',
        '.fail(barangayAjaxError)',
    ],
];

$updated = 0;
foreach ($dirs as $dir) {
    $path = $dir === '.' ? $root : $root . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($path) && $dir !== '.') {
        continue;
    }
    $glob = $dir === '.' ? $root . '/*.php' : $path . '/*.php';
    foreach (glob($glob) as $file) {
        $content = file_get_contents($file);
        $original = $content;
        foreach ($patterns as [$pattern, $replacement]) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        if ($content !== $original) {
            file_put_contents($file, $content);
            $updated++;
            echo 'Updated: ' . str_replace($root . DIRECTORY_SEPARATOR, '', $file) . PHP_EOL;
        }
    }
}
echo "Done. {$updated} file(s) updated." . PHP_EOL;
