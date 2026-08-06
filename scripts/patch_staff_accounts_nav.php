<?php
/**
 * Patch admin sidebar: userAdministrator -> staffAccounts, Administrator -> Staff Accounts
 */
$root = dirname(__DIR__) . '/admin';
$files = glob($root . '/*.php') ?: [];
$count = 0;
foreach ($files as $file) {
    if (basename($file) === 'userAdministrator.php') {
        continue;
    }
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $new = str_replace(
        ['href="userAdministrator.php"', '<p>Administrator</p>'],
        ['href="staffAccounts.php"', '<p>Staff Accounts</p>'],
        $content
    );
    if ($new !== $content) {
        file_put_contents($file, $new);
        $count++;
    }
}
echo "Patched $count admin files.\n";
