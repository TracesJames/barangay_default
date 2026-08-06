<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/datatables_helper.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

if ($q === '' || strlen($q) < 2) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$like = datatables_search_like($q);
$where = nutrition_children_where($con, 'residence_information', 'residence_status');
$scopeId = barangay_resolve_scope_id($con);
if ($scopeId !== null && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
    $where[] = "residence_status.barangay_id='" . $con->real_escape_string($scopeId) . "'";
}
$where[] = '(residence_information.residence_id LIKE ?
    OR residence_information.first_name LIKE ?
    OR residence_information.last_name LIKE ?
    OR residence_information.middle_name LIKE ?
    OR CONCAT(residence_information.last_name, " ", residence_information.first_name) LIKE ?
    OR CONCAT(residence_information.first_name, " ", residence_information.last_name) LIKE ?)';
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT residence_information.residence_id, residence_information.first_name,
        residence_information.middle_name, residence_information.last_name, residence_information.age
        FROM residence_information
        INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id
        $whereSql
        ORDER BY residence_information.last_name ASC, residence_information.first_name ASC
        LIMIT ? OFFSET ?";

$stmt = $con->prepare($sql);
$limit = $perPage + 1;
$stmt->bind_param('ssssssii', $like, $like, $like, $like, $like, $like, $limit, $offset);
$stmt->execute();
$query = $stmt->get_result();

$results = [];
while ($row = $query->fetch_assoc()) {
    $middle = trim((string) ($row['middle_name'] ?? ''));
    $middleInitial = $middle !== '' ? $middle[0] . '. ' : '';
    $label = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $middleInitial . '(Age ' . ($row['age'] ?? '?') . ')');
    $results[] = [
        'id' => $row['residence_id'],
        'text' => strtoupper($label),
    ];
}

$more = count($results) > $perPage;
if ($more) {
    array_pop($results);
}

echo json_encode([
    'results' => $results,
    'pagination' => ['more' => $more],
]);
