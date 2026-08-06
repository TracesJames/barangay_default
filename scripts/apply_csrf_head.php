<?php

$base = dirname(__DIR__);
$sets = [
    $base . '/admin' => "../includes/head_csrf.php",
    $base . '/secretary' => "../includes/head_csrf.php",
    $base . '/resident' => "../includes/head_csrf.php",
];

$snippet = "<?php require_once '__PATH__'; ?>\n";

foreach ($sets as $dir => $path) {
    foreach (glob($dir . '/*.php') as $file) {
        $content = file_get_contents($file);
        if (!str_contains($content, '</head>') || str_contains($content, 'head_csrf.php')) {
            continue;
        }
        if (!str_contains($content, 'jquery')) {
            continue;
        }
        $insert = str_replace('__PATH__', $path, $snippet);
        $content = str_replace('</head>', $insert . '</head>', $content);
        file_put_contents($file, $content);
        echo 'CSRF head: ' . basename($file) . PHP_EOL;
    }
}

$rootFiles = ['register.php', 'forgot.php', 'recoverAccount.php', 'index.php'];
foreach ($rootFiles as $name) {
    $file = $base . '/' . $name;
    if (!file_exists($file)) {
        continue;
    }
    $content = file_get_contents($file);
    if (!str_contains($content, '</head>') || str_contains($content, 'head_csrf')) {
        continue;
    }
    $insert = "<?php require_once 'includes/head_csrf_root.php'; ?>\n";
    $content = str_replace('</head>', $insert . '</head>', $content);
    file_put_contents($file, $content);
    echo 'CSRF head root: ' . $name . PHP_EOL;
}

echo "Done.\n";
