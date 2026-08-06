<?php

$insert = <<<'HTML'
              <li class="nav-item">
                <a href="familyHouseholdHead.php" class="nav-link ">
                  <i class="fas fa-circle nav-icon text-red"></i>
                  <p>Family House Hold</p>
                </a>
              </li>
HTML;

$dirs = [
    __DIR__ . '/../admin',
    __DIR__ . '/../secretary',
];

foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.php') as $file) {
        $content = file_get_contents($file);
        if ($content === false || str_contains($content, 'familyHouseholdHead.php')) {
            continue;
        }
        if (!str_contains($content, 'href="archiveResidence.php"')) {
            continue;
        }

        $updated = preg_replace(
            '/(\s+<li class="nav-item">\s+<a href="archiveResidence\.php")/',
            $insert . '$1',
            $content,
            1,
            $count
        );

        if ($count > 0) {
            file_put_contents($file, $updated);
            echo basename($file) . PHP_EOL;
        }
    }
}
