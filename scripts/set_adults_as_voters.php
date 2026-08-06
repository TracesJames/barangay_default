<?php
/**
 * Adults (18+) -> voters YES; children (0-17) -> voters NO.
 */
require_once __DIR__ . '/../connection.php';

$before = $con->query('SELECT voters, COUNT(*) AS c FROM residence_status GROUP BY voters');
echo "Before:\n";
while ($row = $before->fetch_assoc()) {
    echo '  ' . $row['voters'] . ' = ' . $row['c'] . "\n";
}

echo "Setting all residents to voters=YES...\n";
if (!$con->query("UPDATE residence_status SET voters = 'YES'")) {
    fwrite(STDERR, 'Bulk YES failed: ' . $con->error . "\n");
    exit(1);
}
echo '  affected: ' . $con->affected_rows . "\n";

echo "Setting children 0-17 to voters=NO...\n";

// By stored age
$sqlByAge = "
UPDATE residence_status rs
INNER JOIN residence_information ri ON ri.residence_id = rs.residence_id
SET rs.voters = 'NO'
WHERE ri.age <> ''
  AND ri.age REGEXP '^[0-9]+$'
  AND CAST(ri.age AS UNSIGNED) <= 17
";
if (!$con->query($sqlByAge)) {
    fwrite(STDERR, 'Child-by-age failed: ' . $con->error . "\n");
    exit(1);
}
echo '  by age affected: ' . $con->affected_rows . "\n";

// By birth date when age is blank
$sqlByBirth = "
UPDATE residence_status rs
INNER JOIN residence_information ri ON ri.residence_id = rs.residence_id
SET rs.voters = 'NO'
WHERE (ri.age IS NULL OR TRIM(ri.age) = '')
  AND ri.birth_date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
  AND ri.birth_date NOT IN ('0000-00-00', '1900-01-01')
  AND TIMESTAMPDIFF(YEAR, ri.birth_date, CURDATE()) <= 17
";
if (!$con->query($sqlByBirth)) {
    fwrite(STDERR, 'Child-by-birth failed: ' . $con->error . "\n");
    exit(1);
}
echo '  by birthdate affected: ' . $con->affected_rows . "\n";

$after = $con->query('SELECT voters, COUNT(*) AS c FROM residence_status GROUP BY voters');
echo "After:\n";
while ($row = $after->fetch_assoc()) {
    echo '  ' . $row['voters'] . ' = ' . $row['c'] . "\n";
}

$check = $con->query("
SELECT
  SUM(CASE WHEN ri.age REGEXP '^[0-9]+$' AND CAST(ri.age AS UNSIGNED) <= 17 AND rs.voters = 'YES' THEN 1 ELSE 0 END) AS child_still_yes,
  SUM(CASE WHEN ri.age REGEXP '^[0-9]+$' AND CAST(ri.age AS UNSIGNED) >= 18 AND rs.voters = 'NO' THEN 1 ELSE 0 END) AS adult_still_no
FROM residence_status rs
INNER JOIN residence_information ri ON ri.residence_id = rs.residence_id
")->fetch_assoc();

echo 'Children still YES: ' . ($check['child_still_yes'] ?? 0) . "\n";
echo 'Adults still NO: ' . ($check['adult_still_no'] ?? 0) . "\n";
echo "Done.\n";
