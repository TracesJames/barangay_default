<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

if ($q === '' || strlen($q) < 2) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$excludeRaw = $_REQUEST['exclude'] ?? [];
if (!is_array($excludeRaw)) {
    $excludeRaw = array_filter(array_map('trim', explode(',', (string) $excludeRaw)));
}
$excludeIds = [];
foreach ($excludeRaw as $id) {
    $id = trim((string) $id);
    if ($id !== '') {
        $excludeIds[] = $id;
    }
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

if ($excludeIds !== []) {
    $escapedExclude = array_map(static fn ($id) => "'" . $con->real_escape_string($id) . "'", $excludeIds);
    $where[] = 'residence_information.residence_id NOT IN (' . implode(',', $escapedExclude) . ')';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$sql = "SELECT residence_information.residence_id, residence_information.first_name,
        residence_information.middle_name, residence_information.last_name,
        residence_information.image_path
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
        $middleInitial = $middle !== '' ? mb_substr($middle, 0, 1) . '. ' : '';
        $label = trim($row['last_name'] . ' ' . $row['first_name'] . ' ' . $middleInitial);
        $imagePath = trim((string) ($row['image_path'] ?? ''));
        $results[] = [
            'id' => $row['residence_id'],
            'text' => strtoupper($label),
            'image' => $imagePath !== '' ? $imagePath : '../assets/dist/img/blank_image.png',
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
