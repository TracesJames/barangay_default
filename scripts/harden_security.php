<?php
/**
 * CLI: create MySQL app user, rotate default passwords, move dumps out of webroot.
 * Run: php scripts/harden_security.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$secureDir = 'C:\\xampp\\secure';
if (!is_dir($secureDir)) {
    mkdir($secureDir, 0700, true);
}

require_once $root . '/includes/helpers.php';

$mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
if (!is_file($mysql)) {
    $mysql = 'mysql';
}

$dbPass = bin2hex(random_bytes(12)); // 24 hex chars
$dbUser = 'barangay_app';
$dbName = 'barangay';

$sqlCreate = <<<SQL
CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}';
ALTER USER '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON `{$dbName}`.* TO '{$dbUser}'@'localhost';
FLUSH PRIVILEGES;
SQL;

$tmpSql = $secureDir . DIRECTORY_SEPARATOR . '_create_db_user.sql';
file_put_contents($tmpSql, $sqlCreate);

$cmd = '"' . $mysql . '" -u root < "' . $tmpSql . '"';
passthru($cmd, $exitCode);
@unlink($tmpSql);

if ($exitCode !== 0) {
    fwrite(STDERR, "Warning: MySQL user create may have failed (exit {$exitCode}). Continuing with password rotation using root.\n");
}

$dbConfigPath = $secureDir . DIRECTORY_SEPARATOR . 'barangay_db.php';
$dbConfigPhp = "<?php\nreturn [\n    'host' => 'localhost',\n    'user' => " . var_export($dbUser, true) . ",\n    'password' => " . var_export($dbPass, true) . ",\n    'name' => " . var_export($dbName, true) . ",\n];\n";
file_put_contents($dbConfigPath, $dbConfigPhp);

// Connect as root for rotation (before app switches)
$con = @new mysqli('localhost', 'root', '', $dbName);
if ($con->connect_error) {
    fwrite(STDERR, 'DB connect failed: ' . $con->connect_error . "\n");
    exit(1);
}
$con->set_charset('utf8mb4');

$defaults = ['admin123', 'nutrition123', 'barangay123', 'secretary123', 'password', '123456'];
$newPassword = 'Vc!' . bin2hex(random_bytes(5)) . 'A9'; // strong temp shared password for rotated accounts
$newHash = barangay_hash_password($newPassword);

$res = $con->query('SELECT id, username, user_type, password FROM users');
$rotated = [];
while ($row = $res->fetch_assoc()) {
    $stored = (string) ($row['password'] ?? '');
    $matched = false;
    foreach ($defaults as $plain) {
        if (barangay_verify_password($plain, $stored)) {
            $matched = true;
            break;
        }
    }
    // Also catch exact plaintext stores of defaults
    if (!$matched && in_array($stored, $defaults, true)) {
        $matched = true;
    }
    if (!$matched) {
        continue;
    }
    $id = (string) $row['id'];
    $stmt = $con->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bind_param('ss', $newHash, $id);
    $stmt->execute();
    $stmt->close();
    $rotated[] = [
        'id' => $id,
        'username' => (string) $row['username'],
        'user_type' => (string) $row['user_type'],
    ];
}

$credPath = $secureDir . DIRECTORY_SEPARATOR . 'rotated_credentials_' . date('Ymd_His') . '.txt';
$lines = [];
$lines[] = 'Barangay portal — rotated credentials (KEEP OFFLINE / OUTSIDE WEBROOT)';
$lines[] = 'Generated: ' . date('c');
$lines[] = '';
$lines[] = 'MySQL app user:';
$lines[] = "  user: {$dbUser}";
$lines[] = "  password: {$dbPass}";
$lines[] = "  database: {$dbName}";
$lines[] = "  config: {$dbConfigPath}";
$lines[] = '';
$lines[] = 'Temporary password for rotated login accounts:';
$lines[] = "  {$newPassword}";
$lines[] = 'Change this after first login.';
$lines[] = '';
$lines[] = 'Rotated accounts (' . count($rotated) . '):';
foreach ($rotated as $u) {
    $lines[] = '  - ' . $u['username'] . ' (' . $u['user_type'] . ') id=' . $u['id'];
}
file_put_contents($credPath, implode(PHP_EOL, $lines) . PHP_EOL);

// Move SQL dumps / credential txt out of webroot
$backupSecure = $secureDir . DIRECTORY_SEPARATOR . 'webroot_moved_' . date('Ymd_His');
mkdir($backupSecure, 0700, true);

$moveList = [
    $root . DIRECTORY_SEPARATOR . 'barangay.sql',
    $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'nutrition_super_admin_account.txt',
    $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'barangay_admin_accounts.txt',
    $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'barangay_secretary_accounts.txt',
];
foreach (glob($root . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $sqlFile) {
    $moveList[] = $sqlFile;
}

$moved = [];
foreach ($moveList as $src) {
    if (!is_file($src)) {
        continue;
    }
    $dest = $backupSecure . DIRECTORY_SEPARATOR . basename(dirname($src)) . '_' . basename($src);
    if (@rename($src, $dest)) {
        $moved[] = $src . ' -> ' . $dest;
    } else {
        // fallback copy+delete
        if (@copy($src, $dest)) {
            @unlink($src);
            $moved[] = $src . ' -> ' . $dest;
        }
    }
}

echo "Secure dir: {$secureDir}\n";
echo "DB config: {$dbConfigPath}\n";
echo "Credentials file: {$credPath}\n";
echo 'Rotated accounts: ' . count($rotated) . "\n";
echo 'Moved files: ' . count($moved) . "\n";
foreach ($moved as $m) {
    echo "  {$m}\n";
}
echo "\nIMPORTANT: Open {$credPath} and store passwords safely, then delete that file when done.\n";
