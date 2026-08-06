<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/datatables_helper.php';

$draw = (int) ($_POST['draw'] ?? 0);
$start = (int) ($_POST['start'] ?? 0);
$length = (int) ($_POST['length'] ?? 10);
$searchValue = trim((string) ($_POST['search']['value'] ?? ''));
$statusFilter = trim((string) ($_POST['status_filter'] ?? ''));
$barangayId = (string) ($barangay_id ?? '');

if ($barangayId === '') {
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'No active barangay selected.',
    ]);
    exit;
}

$baseSql = "FROM nutrition_assessment na
    INNER JOIN residence_information ri ON na.residence_id = ri.residence_id
    INNER JOIN (
        SELECT residence_id, MAX(assessment_date) AS latest_date
        FROM nutrition_assessment
        WHERE barangay_id = ?
        GROUP BY residence_id
    ) latest ON latest.residence_id = na.residence_id AND latest.latest_date = na.assessment_date
    WHERE na.barangay_id = ?";

$where = [];
$types = 'ss';
$params = [$barangayId, $barangayId];

if ($statusFilter === 'at_risk') {
    $where[] = "na.nutritional_status != 'normal'";
} elseif ($statusFilter !== '' && isset(nutrition_status_options()[$statusFilter])) {
    $where[] = 'na.nutritional_status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

if ($searchValue !== '') {
    $like = datatables_search_like($searchValue);
    $where[] = '(ri.residence_id LIKE ? OR ri.first_name LIKE ? OR ri.last_name LIKE ? OR ri.middle_name LIKE ?)';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}

$whereSql = $where !== [] ? ' AND ' . implode(' AND ', $where) : '';

$countTotalStmt = $con->prepare(
    "SELECT COUNT(*) AS total FROM nutrition_assessment na
     INNER JOIN (
        SELECT residence_id, MAX(assessment_date) AS latest_date
        FROM nutrition_assessment
        WHERE barangay_id = ?
        GROUP BY residence_id
     ) latest ON latest.residence_id = na.residence_id AND latest.latest_date = na.assessment_date
     WHERE na.barangay_id = ?"
);
$countTotalStmt->bind_param('ss', $barangayId, $barangayId);
$countTotalStmt->execute();
$recordsTotal = (int) ($countTotalStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countTotalStmt->close();

$countFilteredSql = "SELECT COUNT(*) AS total $baseSql $whereSql";
$countFilteredStmt = $con->prepare($countFilteredSql);
$countFilteredStmt->bind_param($types, ...$params);
$countFilteredStmt->execute();
$recordsFiltered = (int) ($countFilteredStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countFilteredStmt->close();

$orderColumns = [
    'ri.residence_id',
    'ri.last_name',
    'ri.age',
    'na.assessment_date',
    'na.weight_kg',
    'na.height_cm',
    'na.bmi',
    'na.nutritional_status',
];
$orderSql = datatables_order_clause($orderColumns, $_POST['order'] ?? null, ' ORDER BY na.assessment_date DESC');
$limitSql = datatables_limit_clause($start, $length);

$dataSql = "SELECT ri.residence_id, ri.first_name, ri.middle_name, ri.last_name, ri.age,
    na.assessment_date, na.weight_kg, na.height_cm, na.bmi, na.nutritional_status, na.assessment_id
    $baseSql $whereSql $orderSql $limitSql";
$dataStmt = $con->prepare($dataSql);
$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$result = $dataStmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $name = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ?? ''));
    $status = (string) $row['nutritional_status'];
    $statusBadge = '<span class="badge ' . nutrition_status_badge_class($status) . '">'
        . barangay_h(nutrition_status_label($status)) . '</span>';
    $assessUrl = 'nutritionAssess.php?residence_id=' . urlencode((string) $row['residence_id']);
    $actions = '<a href="' . barangay_h($assessUrl) . '" class="btn btn-xs btn-success"><i class="fas fa-weight"></i> Re-assess</a>';

    $rows[] = [
        barangay_h((string) $row['residence_id']),
        barangay_h($name),
        barangay_h((string) ($row['age'] ?? '')),
        barangay_h(date('M j, Y', strtotime((string) $row['assessment_date']))),
        barangay_h(number_format((float) $row['weight_kg'], 2)),
        barangay_h(number_format((float) $row['height_cm'], 2)),
        barangay_h($row['bmi'] !== null ? number_format((float) $row['bmi'], 2) : '—'),
        $statusBadge,
        $actions,
    ];
}
$dataStmt->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $rows,
]);
