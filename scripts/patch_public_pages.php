<?php
/**
 * Remove duplicate inline styles from public pages and apply shared theme classes.
 */
$files = [
    __DIR__ . '/../register.php' => 'Register | Barangay Portal',
    __DIR__ . '/../forgot.php' => 'Forgot Password | Barangay Portal',
];

foreach ($files as $file => $title) {
    if (!is_file($file)) {
        continue;
    }
    $content = file_get_contents($file);
    $content = preg_replace('/\s*<style>.*?<\/style>\s*/s', "\n", $content, 1);
    if (strpos($content, 'assets/css/barangay.css') === false) {
        $content = str_replace(
            "  <link rel=\"stylesheet\" href=\"assets/dist/css/adminlte.min.css\">\n",
            "  <link rel=\"stylesheet\" href=\"assets/dist/css/adminlte.min.css\">\n  <link rel=\"stylesheet\" href=\"assets/css/barangay.css\">\n",
            $content
        );
    }
    if (strpos($content, 'head_csrf_root.php') === false) {
        $content = str_replace('</head>', "<?php require_once 'includes/head_csrf_root.php'; ?>\n</head>", $content);
    }
    $content = preg_replace('/<title>.*?<\/title>/', '<title>' . $title . '</title>', $content, 1);
    $content = str_replace(
        'style="background-color: #0037af"',
        'class="barangay-nav"',
        $content
    );
    $content = preg_replace(
        '/<nav class="main-header navbar navbar-expand-md barangay-nav">/',
        '<nav class="main-header navbar navbar-expand-md barangay-nav">',
        $content
    );
    $content = str_replace(
        'nav class="main-header navbar navbar-expand-md " class="barangay-nav"',
        'nav class="main-header navbar navbar-expand-md barangay-nav"',
        $content
    );
    $content = str_replace(
        'nav class="main-header navbar navbar-expand-md  class="barangay-nav""',
        'nav class="main-header navbar navbar-expand-md barangay-nav"',
        $content
    );
    if (strpos($content, 'barangay-hero') === false) {
        $content = str_replace(
            '<div class="content-wrapper" >',
            '<div class="content-wrapper barangay-hero">',
            $content
        );
        $content = str_replace(
            '<div class="content-wrapper">',
            '<div class="content-wrapper barangay-hero">',
            $content
        );
    }
    $content = str_replace(
        'footer class="main-footer text-white" style="background-color: #0037af"',
        'footer class="main-footer text-white barangay-footer"',
        $content
    );
    file_put_contents($file, $content);
    echo 'Patched: ' . basename($file) . PHP_EOL;
}

echo "Done.\n";
