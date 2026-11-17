<?php

/**
 * Sprint 1 data fixes:
 *  - Assign all Colonia puroks to Claudene Encabo (explicit scope)
 *  - Optional: align orphan survey headers (--align-orphans)
 *  - Optional: rotate all BNS passwords (--rotate-passwords)
 *  - Print BNS purok audit summary
 *
 * Usage:
 *   php scripts/sprint1_apply.php
 *   php scripts/sprint1_apply.php --align-orphans
 *   php scripts/sprint1_apply.php --rotate-passwords
 *   php scripts/sprint1_apply.php --align-orphans --rotate-passwords
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';
require_once dirname(__DIR__) . '/includes/staff_permissions.php';
require_once dirname(__DIR__) . '/includes/nutrition_context.php';
require_once dirname(__DIR__) . '/includes/api_auth.php';

$alignOrphans = in_array('--align-orphans', $argv, true);
$rotatePasswords = in_array('--rotate-passwords', $argv, true);

echo "=== Sprint 1 apply ===\n";

// 1) Claudene Encabo / Colonia — assign every master purok explicitly
$col = $con->query(
    "SELECT id, barangay FROM barangay_information WHERE barangay LIKE '%Colonia%' LIMIT 1"
)->fetch_assoc();
if (!$col) {
    fwrite(STDERR, "Colonia barangay not found.\n");
    exit(1);
}
$colId = (string) $col['id'];
$user = $con->query(
    "SELECT id, username, first_name, last_name FROM users
     WHERE username = 'claudene.encabo' OR (barangay_id = '" . $con->real_escape_string($colId) . "'
       AND staff_role = 'barangay_nutrition_scholar')
     ORDER BY CASE WHEN username = 'claudene.encabo' THEN 0 ELSE 1 END
     LIMIT 1"
)->fetch_assoc();
if (!$user) {
    fwrite(STDERR, "Claudene Encabo / Colonia BNS not found.\n");
    exit(1);
}

$labels = [];
foreach (barangay_list_puroks($con, $colId) as $row) {
    $label = trim((string) ($row['purok'] ?? ''));
    if ($label !== '') {
        $labels[] = $label;
    }
}
staff_set_purok_assignments($con, (string) $user['id'], $colId, $labels);
$assigned = staff_assigned_purok_labels($con, (string) $user['id'], $colId);
echo 'Colonia BNS ' . $user['username'] . ' assigned puroks=' . count($assigned) . "\n";
foreach ($assigned as $label) {
    echo "  - {$label}\n";
}

// 2) Orphan header align (optional)
$orphans = nutrition_list_incomplete_household_surveys($con, '', 2000);
$orphanHeaders = array_values(array_filter(
    $orphans,
    static fn (array $r): bool => ($r['issue'] ?? '') === 'orphan_header'
));
echo 'Incomplete surveys=' . count($orphans) . ' orphan_headers=' . count($orphanHeaders) . "\n";
if ($alignOrphans) {
    $n = 0;
    foreach ($orphanHeaders as $row) {
        if (nutrition_align_survey_header_to_members($con, (string) $row['survey_id'])) {
            $n++;
        }
    }
    echo "Aligned orphan headers={$n}\n";
    $orphans = nutrition_list_incomplete_household_surveys($con, '', 2000);
    $orphanHeaders = array_values(array_filter(
        $orphans,
        static fn (array $r): bool => ($r['issue'] ?? '') === 'orphan_header'
    ));
    echo 'After align: incomplete=' . count($orphans) . ' orphan_headers=' . count($orphanHeaders) . "\n";
} else {
    echo "Skip orphan align (pass --align-orphans to clear inflated children counts).\n";
}

// 3) Password rotation (optional)
if ($rotatePasswords) {
    nutrition_api_ensure_must_change_password_column($con);
    $opsDir = 'C:\\xampp\\secure\\barangay_ops';
    if (!is_dir($opsDir) && !mkdir($opsDir, 0755, true) && !is_dir($opsDir)) {
        fwrite(STDERR, "Cannot create {$opsDir}\n");
        exit(1);
    }
    $jsonPath = $opsDir . DIRECTORY_SEPARATOR . 'rotated_bns_passwords.json';
    $accounts = [];
    $res = $con->query(
        "SELECT id, username, first_name, last_name, barangay_id
         FROM users
         WHERE staff_role = 'barangay_nutrition_scholar'
         ORDER BY username ASC"
    );
    $upd = $con->prepare('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ? LIMIT 1');
    if (!$upd) {
        fwrite(STDERR, "Cannot prepare password update.\n");
        exit(1);
    }
    while ($res && ($row = $res->fetch_assoc())) {
        $plain = 'Bns!' . bin2hex(random_bytes(4));
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        $id = (string) $row['id'];
        $upd->bind_param('ss', $hash, $id);
        $upd->execute();
        $brgy = '';
        if (!empty($row['barangay_id'])) {
            $b = barangay_load_by_id($con, (string) $row['barangay_id']);
            $brgy = (string) ($b['barangay'] ?? '');
        }
        $accounts[] = [
            'username' => (string) $row['username'],
            'password' => $plain,
            'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'barangay' => $brgy,
            'user_id' => $id,
        ];
    }
    $upd->close();
    $payload = [
        'rotated_at' => date('c'),
        'note' => 'Temporary passwords — BNS must change on next login.',
        'accounts' => $accounts,
    ];
    file_put_contents(
        $jsonPath,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
    );
    echo 'Rotated BNS passwords=' . count($accounts) . " → {$jsonPath}\n";
} else {
    echo "Skip password rotation (pass --rotate-passwords).\n";
}

// 4) Audit summary
$audit = nutrition_audit_bns_purok_scope($con);
$review = 0;
$ok = 0;
foreach ($audit as $row) {
    if (!empty($row['ok'])) {
        $ok++;
    } else {
        $review++;
        echo 'REVIEW ' . $row['barangay']
            . ' gaps=' . count($row['gaps'])
            . ' overlaps=' . count($row['overlaps'])
            . ' bns=' . $row['bns_count'] . "\n";
    }
}
echo "Audit OK barangays={$ok} needs_review={$review}\n";
echo "Done. Open admin/nutritionSurveyRepair.php for the repair UI.\n";
