<?php
/**
 * Remap barangay_information.id (and all barangay_id foreign values)
 * to the official PSA 10-digit PSGC codes for Valencia City.
 *
 * @see https://psa.gov.ph/classification/psgc/barangays/1001321000
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

barangay_ensure_psgc_column($con);
barangay_seed_psgc_codes($con);

$tablesWithBarangayId = [];
$result = $con->query(
    "SELECT TABLE_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND COLUMN_NAME = 'barangay_id'
     ORDER BY TABLE_NAME"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tablesWithBarangayId[] = (string) $row['TABLE_NAME'];
    }
}

$mapRows = [];
$q = $con->query(
    "SELECT id, barangay, psgc_code
     FROM barangay_information
     WHERE psgc_code IS NOT NULL AND psgc_code != ''
     ORDER BY barangay"
);
if (!$q) {
    fwrite(STDERR, "Failed to load barangays: {$con->error}\n");
    exit(1);
}

while ($row = $q->fetch_assoc()) {
    $oldId = (string) $row['id'];
    $psgc = trim((string) $row['psgc_code']);
    $name = (string) $row['barangay'];
    if ($psgc === '') {
        continue;
    }
    if (!preg_match('/^\d{10}$/', $psgc)) {
        echo "SKIP {$name}: invalid PSGC '{$psgc}'\n";
        continue;
    }
    if ($oldId === $psgc) {
        echo "OK {$name}: already using PSGC {$psgc}\n";
        continue;
    }
    $mapRows[] = [
        'old' => $oldId,
        'psgc' => $psgc,
        'name' => $name,
    ];
}

if ($mapRows === []) {
    echo "Nothing to remap. All barangay IDs already match PSGC codes.\n";
    exit(0);
}

echo 'Remapping ' . count($mapRows) . " barangay IDs to PSGC...\n";

$con->begin_transaction();

try {
    // Detect collisions: another row already using this PSGC as id
    foreach ($mapRows as $map) {
        $check = $con->prepare(
            'SELECT id, barangay FROM barangay_information WHERE id = ? AND id != ? LIMIT 1'
        );
        $check->bind_param('ss', $map['psgc'], $map['old']);
        $check->execute();
        $conflict = $check->get_result()->fetch_assoc();
        $check->close();
        if ($conflict) {
            throw new RuntimeException(
                "PSGC {$map['psgc']} already used by barangay '{$conflict['barangay']}' (id {$conflict['id']})"
            );
        }
    }

    // 1) Update foreign keys first (old -> new)
    foreach ($tablesWithBarangayId as $table) {
        $updatedTotal = 0;
        foreach ($mapRows as $map) {
            $sql = "UPDATE `{$table}` SET barangay_id = ? WHERE barangay_id = ?";
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException("Prepare failed for {$table}: {$con->error}");
            }
            $stmt->bind_param('ss', $map['psgc'], $map['old']);
            if (!$stmt->execute()) {
                throw new RuntimeException("Update failed for {$table}: {$stmt->error}");
            }
            $updatedTotal += $stmt->affected_rows;
            $stmt->close();
        }
        echo "  {$table}: updated {$updatedTotal} row(s)\n";
    }

    // 2) Remap primary keys on barangay_information
    foreach ($mapRows as $map) {
        $stmt = $con->prepare(
            'UPDATE barangay_information SET id = ?, psgc_code = ? WHERE id = ?'
        );
        if (!$stmt) {
            throw new RuntimeException("Prepare barangay_information failed: {$con->error}");
        }
        $stmt->bind_param('sss', $map['psgc'], $map['psgc'], $map['old']);
        if (!$stmt->execute()) {
            throw new RuntimeException(
                "Failed remapping {$map['name']} ({$map['old']} -> {$map['psgc']}): {$stmt->error}"
            );
        }
        $stmt->close();
        echo "  barangay_information: {$map['name']} {$map['old']} -> {$map['psgc']}\n";
    }

    $con->commit();
    echo "\nDone. Barangay IDs now use PSA PSGC 10-digit codes.\n";
    echo "Note: users with an active barangay session should re-select their barangay (or log in again).\n";
} catch (Throwable $e) {
    $con->rollback();
    fwrite(STDERR, 'Migration failed and was rolled back: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\n=== Verification ===\n";
$verify = $con->query('SELECT id, barangay, psgc_code FROM barangay_information ORDER BY barangay');
$mismatched = 0;
while ($row = $verify->fetch_assoc()) {
    $id = (string) $row['id'];
    $psgc = trim((string) ($row['psgc_code'] ?? ''));
    $ok = $id === $psgc && preg_match('/^\d{10}$/', $id);
    if (!$ok) {
        $mismatched++;
        echo "MISMATCH {$row['barangay']}: id={$id} psgc={$psgc}\n";
    } else {
        echo "OK {$row['barangay']}: {$id}\n";
    }
}
if ($mismatched === 0) {
    echo "All barangay IDs match PSGC codes.\n";
}
