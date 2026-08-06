<?php

require __DIR__ . '/../connection.php';
require __DIR__ . '/../includes/barangay_context.php';
require __DIR__ . '/../includes/datatables_helper.php';

$_POST['hub_stat'] = 'children';
$_POST['draw'] = '1';
$_POST['start'] = '0';
$_POST['length'] = '5';
$_POST['search'] = ['value' => ''];
$_POST['order'] = [['column' => '1', 'dir' => 'asc']];

$whereClause = barangay_hub_stat_where_clause($con, 'children');
$where = barangay_sql_where($whereClause);
$sql = 'SELECT ri.first_name, ri.last_name, ri.age, bi.barangay AS barangay_name
    FROM residence_information ri
    INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
    LEFT JOIN barangay_information bi ON rs.barangay_id = bi.id' . $where . ' LIMIT 5';
$result = $con->query($sql);
while ($row = $result->fetch_assoc()) {
    echo $row['last_name'] . ', ' . $row['first_name'] . ' | age ' . $row['age'] . ' | ' . $row['barangay_name'] . PHP_EOL;
}
