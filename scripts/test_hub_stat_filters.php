<?php

require __DIR__ . '/../connection.php';
require __DIR__ . '/../includes/barangay_context.php';

foreach (barangay_hub_resident_stat_keys() as $key) {
    $where = barangay_sql_where(barangay_hub_stat_where_clause($con, $key));
    $sql = 'SELECT COUNT(*) AS c FROM residence_information ri
        INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id' . $where;
    $count = (int) ($con->query($sql)->fetch_assoc()['c'] ?? 0);
    echo $key . ': ' . $count . PHP_EOL;
}
