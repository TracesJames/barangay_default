<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';
require_once __DIR__ . '/../includes/staff_permissions.php';
require_once __DIR__ . '/../includes/helpers.php';

$checkBarangay = $con->query("SHOW COLUMNS FROM `users` LIKE 'barangay_id'");
if ($checkBarangay && $checkBarangay->num_rows === 0) {
    $con->query("ALTER TABLE `users` ADD COLUMN `barangay_id` VARCHAR(255) NULL AFTER `user_type`");
    echo "Added barangay_id column to users\n";
}

$checkRole = $con->query("SHOW COLUMNS FROM `users` LIKE 'staff_role'");
if ($checkRole && $checkRole->num_rows === 0) {
    $con->query("ALTER TABLE `users` ADD COLUMN `staff_role` VARCHAR(69) NOT NULL DEFAULT '' AFTER `user_type`");
    echo "Added staff_role column to users\n";
}

if (barangay_column_exists($con, 'users', 'staff_role') && barangay_column_exists($con, 'users', 'barangay_id')) {
    $con->query("UPDATE users SET staff_role = 'ssa' WHERE user_type = 'admin' AND (barangay_id IS NULL OR barangay_id = '') AND (staff_role = '' OR staff_role IS NULL)");
}

$username = 'nutrition.superadmin';
$password = getenv('NUTRITION_SA_SEED_PASSWORD') ?: ('Vc!' . bin2hex(random_bytes(5)) . 'A9');
$firstName = 'Nutrition';
$lastName = 'Super Admin';
$contact = '09000000000';

$stmt = $con->prepare(
    "SELECT id, username, staff_role FROM users
     WHERE username = ? OR staff_role = 'nutrition_super_admin' OR (user_type = 'admin' AND username LIKE 'nutrition%')
     LIMIT 1"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $existingId = (string) $existing['id'];
    if (($existing['staff_role'] ?? '') !== STAFF_ROLE_NUTRITION_SUPER_ADMIN) {
        $fix = $con->prepare('UPDATE users SET staff_role = ? WHERE id = ?');
        $role = STAFF_ROLE_NUTRITION_SUPER_ADMIN;
        $fix->bind_param('ss', $role, $existingId);
        $fix->execute();
        $fix->close();
        echo "Updated existing account to Nutrition Hub Super Admin (SA).\n";
    }
    echo "Nutrition Hub Super Admin already exists.\n";
    echo "Username: {$existing['username']}\n";
    echo "User ID: {$existing['id']}\n";
    exit(0);
}

$userId = (string) hexdec(uniqid());
$hash = barangay_hash_password($password);
$middleName = '';
$userType = 'admin';
$staffRole = STAFF_ROLE_NUTRITION_SUPER_ADMIN;
$image = '';
$imagePath = '';
$barangayId = '';

$insert = $con->prepare(
    'INSERT INTO users (id, first_name, middle_name, last_name, username, password, user_type, staff_role, contact_number, image, image_path, barangay_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$insert->bind_param(
    'ssssssssssss',
    $userId,
    $firstName,
    $middleName,
    $lastName,
    $username,
    $hash,
    $userType,
    $staffRole,
    $contact,
    $image,
    $imagePath,
    $barangayId
);

if (!$insert->execute()) {
    fwrite(STDERR, 'Failed to create account: ' . $con->error . PHP_EOL);
    exit(1);
}

$insert->close();

$outFile = __DIR__ . '/nutrition_super_admin_account.txt';
$lines = [
    'Nutrition Hub Super Admin (SA) Account',
    str_repeat('-', 40),
    'Username: ' . $username,
    'Password: ' . $password,
    'Login URL: http://localhost/barangay_default/login.php',
    'Nutrition Hub: http://localhost/barangay_default/admin/nutritionSuperDashboard.php',
    '',
    'This account can access Nutrition Hub only (not Barangay Hub).',
    'After login, open the city Nutrition Super Dashboard.',
];
file_put_contents($outFile, implode(PHP_EOL, $lines) . PHP_EOL);

echo "Created Nutrition Hub Super Admin (SA) account.\n";
echo "Username: {$username}\n";
echo "Password: {$password}\n";
echo "Saved to: {$outFile}\n";
