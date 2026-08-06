<?php
/**
 * Set voter status for all active residents:
 * - voters = NO for ages 0-17
 * - voters = YES for ages 18+
 *
 * Usage: php scripts/sync_voters_by_age.php [--dry-run]
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$yes = 'YES';
$no = 'NO';
$childCondition = barangay_children_age_condition('ri');

echo ($dryRun ? "DRY RUN — " : '') . "Syncing voter status by age...\n";

$childSql = "UPDATE residence_status rs
    INNER JOIN residence_information ri ON rs.residence_id = ri.residence_id
    SET rs.voters = ?
    WHERE rs.archive = ?
      AND ($childCondition)";

$adultSql = "UPDATE residence_status rs
    INNER JOIN residence_information ri ON rs.residence_id = ri.residence_id
    SET rs.voters = ?
    WHERE rs.archive = ?
      AND NOT ($childCondition)";

if ($dryRun) {
    $countChildSql = "SELECT COUNT(*) AS total FROM residence_status rs
        INNER JOIN residence_information ri ON rs.residence_id = ri.residence_id
        WHERE rs.archive = ? AND ($childCondition)";
    $stmt = $con->prepare($countChildSql);
    $stmt->bind_param('s', $no);
    $stmt->execute();
    $children = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    $countAdultSql = "SELECT COUNT(*) AS total FROM residence_status rs
        INNER JOIN residence_information ri ON rs.residence_id = ri.residence_id
        WHERE rs.archive = ? AND NOT ($childCondition)";
    $stmt = $con->prepare($countAdultSql);
    $stmt->bind_param('s', $no);
    $stmt->execute();
    $adults = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    echo "Would set voters=NO (0-17): {$children}\n";
    echo "Would set voters=YES (18+): {$adults}\n";
    exit(0);
}

$stmtChild = $con->prepare($childSql);
$stmtChild->bind_param('ss', $no, $no);
$stmtChild->execute();
$childrenUpdated = $stmtChild->affected_rows;

$stmtAdult = $con->prepare($adultSql);
$stmtAdult->bind_param('ss', $yes, $no);
$stmtAdult->execute();
$adultsUpdated = $stmtAdult->affected_rows;

echo "Set voters=NO (0-17): {$childrenUpdated}\n";
echo "Set voters=YES (18+): {$adultsUpdated}\n";

$result = $con->query(
    "SELECT bi.barangay,
            SUM(CASE WHEN rs.voters = 'YES' THEN 1 ELSE 0 END) AS voters_yes,
            SUM(CASE WHEN rs.voters = 'NO' THEN 1 ELSE 0 END) AS voters_no
     FROM residence_status rs
     INNER JOIN barangay_information bi ON rs.barangay_id = bi.id
     WHERE rs.archive = 'NO'
     GROUP BY bi.id, bi.barangay
     ORDER BY bi.barangay"
);

echo "\nPer barangay summary:\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf(
        "  %-30s voters: %s | non-voters: %s\n",
        $row['barangay'],
        number_format((int) $row['voters_yes']),
        number_format((int) $row['voters_no'])
    );
}

echo "\nDone.\n";
