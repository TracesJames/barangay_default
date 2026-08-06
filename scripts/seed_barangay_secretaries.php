<?php
/**
 * Create one barangay-scoped secretary account per barangay.
 * Default password for all new accounts: barangay123
 */
require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';

$check = $con->query("SHOW COLUMNS FROM `users` LIKE 'barangay_id'");
if ($check && $check->num_rows === 0) {
    $con->query("ALTER TABLE `users` ADD COLUMN `barangay_id` VARCHAR(255) NULL AFTER `user_type`");
    echo "Added barangay_id to users\n";
}

$defaultPassword = 'barangay123';
$barangays = barangay_list_all($con);
$created = 0;
$skipped = 0;
$accounts = [];

foreach ($barangays as $row) {
    $result = barangay_create_secretary_for_barangay($con, $row['id'], $row['barangay'], $defaultPassword);
    if ($result === null) {
        $skipped++;
        $existing = barangay_load_secretary_account($con, (string) $row['id']);
        if ($existing) {
            $accounts[] = [
                'barangay' => $row['barangay'],
                'username' => $existing['username'],
                'password' => $defaultPassword,
            ];
            echo "Skipped (has secretary): {$row['barangay']} — {$existing['username']}\n";
            continue;
        }
        echo "Skipped: {$row['barangay']}\n";
        continue;
    }
    $created++;
    $accounts[] = $result;
    echo "Created: {$result['barangay']} | username: {$result['username']} | password: {$result['password']}\n";
}

$outFile = __DIR__ . '/barangay_secretary_accounts.txt';
$lines = ["Barangay Secretary Accounts (default password: $defaultPassword)", str_repeat('-', 60)];
foreach ($accounts as $acc) {
    $lines[] = sprintf('%-28s  %-22s  %s', $acc['barangay'], $acc['username'], $acc['password']);
}
file_put_contents($outFile, implode(PHP_EOL, $lines) . PHP_EOL);

echo "\nCreated: $created, Skipped: $skipped\n";
echo "Account list saved to: $outFile\n";
