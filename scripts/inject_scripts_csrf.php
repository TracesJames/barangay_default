<?php
/**
 * Insert scripts_csrf.php immediately after jQuery on portal and public pages.
 */
$root = dirname(__DIR__);
$dirs = [
    $root . '/admin' => 1,
    $root . '/secretary' => 1,
    $root . '/resident' => 1,
    $root => 0,
];

$jqueryNeedle = '<script src="';
$updated = 0;

foreach ($dirs as $dir => $depth) {
    if (!is_dir($dir) && $depth !== 0) {
        continue;
    }
    $glob = $depth === 0 ? $dir . '/*.php' : $dir . '/*.php';
    foreach (glob($glob) as $file) {
        $content = file_get_contents($file);
        if (strpos($content, 'scripts_csrf.php') !== false) {
            continue;
        }
        if (!preg_match('/<script src="[^"]*jquery\.min\.js"><\/script>/', $content, $match, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        $insert = $match[0][0] . "\n<?php \$barangay_script_depth = {$depth}; require_once "
            . ($depth === 0 ? "'includes/scripts_csrf.php'" : "'../includes/scripts_csrf.php'")
            . "; ?>\n";
        $pos = $match[0][1];
        $newContent = substr($content, 0, $pos) . $insert . substr($content, $pos + strlen($match[0][0]));
        file_put_contents($file, $newContent);
        $updated++;
        echo 'Updated: ' . str_replace($root . DIRECTORY_SEPARATOR, '', $file) . PHP_EOL;
    }
}

echo "Done. {$updated} file(s) updated." . PHP_EOL;
