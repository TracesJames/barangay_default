<?php

include_once '../connection.php';
include_once '../includes/auth_certificate_staff.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

if ($q === '' || strlen($q) < 2) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$escaped = $con->real_escape_string($q);
$like = '%' . $escaped . '%';
$where = barangay_residents_where_clause($con, ["residence_status.archive = 'NO'"]);
$where[] = "(residence_information.residence_id LIKE '$like'
    OR residence_information.first_name LIKE '$like'
    OR residence_information.last_name LIKE '$like'
    OR residence_information.middle_name LIKE '$like'
    OR CONCAT(residence_information.last_name, ' ', residence_information.first_name) LIKE '$like'
    OR CONCAT(residence_information.first_name, ' ', residence_information.last_name) LIKE '$like')";
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT residence_information.residence_id, residence_information.first_name,
        residence_information.middle_name, residence_information.last_name
        FROM residence_information
        INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id
        $whereSql
        ORDER BY residence_information.last_name ASC, residence_information.first_name ASC
        LIMIT " . ($perPage + 1) . " OFFSET $offset";
$query = $con->query($sql);

$results = [];
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $middle = trim((string) ($row['middle_name'] ?? ''));
        $middleInitial = $middle !== '' ? $middle[0] . '. ' : '';
        $label = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $middleInitial . '(' . $row['residence_id'] . ')');
        $results[] = [
            'id' => $row['residence_id'],
            'text' => strtoupper($label),
        ];
    }
}

$more = count($results) > $perPage;
if ($more) {
    array_pop($results);
}

echo json_encode([
    'results' => $results,
    'pagination' => ['more' => $more],
]);
