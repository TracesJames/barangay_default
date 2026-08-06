<?php
/**
 * Remap Resident Numbers to PSA PSGC format: {PSGC}-{NNNNNN}
 * Example: 1001321024-000001
 *
 * Usage:
 *   php scripts/migrate_residence_id_to_psgc.php
 *   php scripts/migrate_residence_id_to_psgc.php --dry-run
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';
require_once __DIR__ . '/../includes/residence_family.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$pad = residence_number_pad_length();

$tables = [];
$result = $con->query(
    "SELECT TABLE_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND COLUMN_NAME = 'residence_id'
     ORDER BY TABLE_NAME"
);
while ($row = $result->fetch_assoc()) {
    $tables[] = (string) $row['TABLE_NAME'];
}

echo ($dryRun ? "DRY RUN — " : '') . "Building residence ID map...\n";

$con->query('DROP TEMPORARY TABLE IF EXISTS residence_id_psgc_map');
$ok = $con->query(
    "CREATE TEMPORARY TABLE residence_id_psgc_map (
        old_id VARCHAR(255) NOT NULL PRIMARY KEY,
        new_id VARCHAR(255) NOT NULL,
        UNIQUE KEY uq_new_id (new_id)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);
if (!$ok) {
    fwrite(STDERR, "Failed to create map table: {$con->error}\n");
    exit(1);
}

$insertMap = $con->prepare('INSERT INTO residence_id_psgc_map (old_id, new_id) VALUES (?, ?)');
if (!$insertMap) {
    fwrite(STDERR, "Prepare map insert failed: {$con->error}\n");
    exit(1);
}

$totalMapped = 0;
$totalSkipped = 0;

$barangays = $con->query('SELECT id, barangay, psgc_code FROM barangay_information ORDER BY barangay');
while ($brgy = $barangays->fetch_assoc()) {
    $barangayId = (string) $brgy['id'];
    $psgc = trim((string) ($brgy['psgc_code'] ?? ''));
    if ($psgc === '' || !preg_match('/^\d{10}$/', $psgc)) {
        $psgc = barangay_psgc_lookup_by_name((string) $brgy['barangay']);
    }
    if ($psgc === '' || !preg_match('/^\d{10}$/', $psgc)) {
        echo "SKIP {$brgy['barangay']}: no PSGC code\n";
        continue;
    }

    $stmt = $con->prepare(
        "SELECT residence_id
         FROM residence_status
         WHERE barangay_id = ?
         ORDER BY residence_id ASC"
    );
    $stmt->bind_param('s', $barangayId);
    $stmt->execute();
    $res = $stmt->get_result();

    $series = 1;
    $mappedHere = 0;
    $skippedHere = 0;
    $usedNew = [];

    while ($row = $res->fetch_assoc()) {
        $oldId = (string) $row['residence_id'];

        if (residence_is_psgc_number($oldId) && str_starts_with($oldId, $psgc . '-')) {
            $parts = explode('-', $oldId);
            $series = max($series, ((int) ($parts[1] ?? 0)) + 1);
            $usedNew[$oldId] = true;
            $skippedHere++;
            continue;
        }

        do {
            $newId = residence_format_number($psgc, $series);
            $series++;
        } while (isset($usedNew[$newId]));

        // Avoid colliding with an already-PSGC ID that we skipped.
        $check = $con->prepare('SELECT residence_id FROM residence_information WHERE residence_id = ? LIMIT 1');
        $check->bind_param('s', $newId);
        $check->execute();
        while ($check->get_result()->num_rows > 0) {
            $check->close();
            $newId = residence_format_number($psgc, $series);
            $series++;
            $check = $con->prepare('SELECT residence_id FROM residence_information WHERE residence_id = ? LIMIT 1');
            $check->bind_param('s', $newId);
            $check->execute();
        }
        $check->close();

        $usedNew[$newId] = true;
        $insertMap->bind_param('ss', $oldId, $newId);
        if (!$insertMap->execute()) {
            fwrite(STDERR, "Map insert failed ({$oldId} -> {$newId}): {$insertMap->error}\n");
            exit(1);
        }
        $mappedHere++;
    }
    $stmt->close();

    $totalMapped += $mappedHere;
    $totalSkipped += $skippedHere;
    echo "{$brgy['barangay']} ({$psgc}): will remap {$mappedHere}, already-ok {$skippedHere}\n";
}
$insertMap->close();

echo "\nTotal to remap: {$totalMapped}\n";
echo "Already PSGC format: {$totalSkipped}\n";

if ($dryRun || $totalMapped === 0) {
    echo $dryRun ? "\nDry run complete. No changes saved.\n" : "\nNothing to remap.\n";
    exit(0);
}

echo "\nApplying updates...\n";
$con->query('SET FOREIGN_KEY_CHECKS=0');
$con->begin_transaction();

try {
    foreach ($tables as $table) {
        $sql = "UPDATE `{$table}` t
                INNER JOIN residence_id_psgc_map m ON t.residence_id = m.old_id
                SET t.residence_id = m.new_id";
        if (!$con->query($sql)) {
            throw new RuntimeException("Failed updating {$table}: {$con->error}");
        }
        echo "  {$table}: {$con->affected_rows} row(s)\n";
    }

    // Resident accounts: id + username = residence number
    $sqlUsers = "UPDATE users u
                 INNER JOIN residence_id_psgc_map m ON u.id = m.old_id
                 SET u.id = m.new_id, u.username = m.new_id
                 WHERE u.user_type = 'resident'";
    if (!$con->query($sqlUsers)) {
        throw new RuntimeException("Failed updating users: {$con->error}");
    }
    echo "  users (resident): {$con->affected_rows} row(s)\n";

    $con->commit();
    echo "\nDone. Resident numbers now use PSGC format ({$pad}-digit series).\n";
} catch (Throwable $e) {
    $con->rollback();
    fwrite(STDERR, 'Migration failed and was rolled back: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    $con->query('SET FOREIGN_KEY_CHECKS=1');
}

echo "\nSample IDs:\n";
$sample = $con->query(
    "SELECT ri.residence_id, bi.barangay
     FROM residence_information ri
     INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
     LEFT JOIN barangay_information bi ON bi.id = rs.barangay_id
     WHERE ri.residence_id REGEXP '^[0-9]{10}-[0-9]{{$pad}}$'
     ORDER BY ri.residence_id ASC
     LIMIT 12"
);
if ($sample) {
    while ($row = $sample->fetch_assoc()) {
        echo "  {$row['residence_id']}\t{$row['barangay']}\n";
    }
}
