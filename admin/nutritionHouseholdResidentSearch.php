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

$barangayId = trim((string) ($_GET['barangay_id'] ?? $_POST['barangay_id'] ?? ''));
if ($barangayId === '') {
    $barangayId = (string) ($barangay_id ?? '');
}
if ($barangayId === '') {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$like = datatables_search_like($q);
$where = ["residence_status.archive = 'NO'"];
if (barangay_column_exists($con, 'residence_status', 'barangay_id')) {
    $where[] = "residence_status.barangay_id = '" . $con->real_escape_string($barangayId) . "'";
}
$where[] = '(residence_information.residence_id LIKE ?
    OR residence_information.first_name LIKE ?
    OR residence_information.last_name LIKE ?
    OR residence_information.middle_name LIKE ?
    OR CONCAT(residence_information.last_name, " ", residence_information.first_name) LIKE ?
    OR CONCAT(residence_information.first_name, " ", residence_information.last_name) LIKE ?)';
$whereSql = 'WHERE ' . implode(' AND ', $where);

$hasHouseholdHead = barangay_column_exists($con, 'residence_status', 'household_head');
$headSelect = $hasHouseholdHead
    ? ', residence_status.household_head'
    : ', \'NO\' AS household_head';
$orderHead = $hasHouseholdHead
    ? "CASE WHEN residence_status.household_head = 'YES' THEN 0 ELSE 1 END,"
    : '';

$sql = "SELECT residence_information.residence_id, residence_information.first_name,
        residence_information.middle_name, residence_information.last_name,
        residence_information.age {$headSelect}
        FROM residence_information
        INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id
        {$whereSql}
        ORDER BY
          {$orderHead}
          residence_information.last_name ASC,
          residence_information.first_name ASC
        LIMIT ? OFFSET ?";

$stmt = $con->prepare($sql);
if (!$stmt) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$limit = $perPage + 1;
$stmt->bind_param('ssssssii', $like, $like, $like, $like, $like, $like, $limit, $offset);
$stmt->execute();
$query = $stmt->get_result();

$results = [];
while ($row = $query->fetch_assoc()) {
    $middle = trim((string) ($row['middle_name'] ?? ''));
    $middleInitial = $middle !== '' ? $middle[0] . '. ' : '';
    $isHead = strtoupper((string) ($row['household_head'] ?? 'NO')) === 'YES';
    $label = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $middleInitial);
    if ($isHead) {
        $label .= ' (Household Head)';
    }
    $age = trim((string) ($row['age'] ?? ''));
    if ($age !== '') {
        $label .= ' · Age ' . $age;
    }
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
