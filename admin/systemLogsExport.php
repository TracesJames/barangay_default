<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/audit_log.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';

$userId = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_is_super_admin($con, $userId) && !barangay_user_is_ssa($con, $userId)) {
    http_response_code(403);
    exit('Forbidden');
}

barangay_audit_ensure_columns($con);

$statusFilter = trim((string) ($_GET['status_filter'] ?? ''));
$barangayFilter = trim((string) ($_GET['barangay_filter'] ?? ''));
$entityFilter = trim((string) ($_GET['entity_filter'] ?? ''));
$userFilter = trim((string) ($_GET['user_filter'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));

$where = [];
if ($statusFilter !== '') {
    $where[] = "activity_log.status = '" . $con->real_escape_string($statusFilter) . "'";
}
if ($barangayFilter !== '' && barangay_column_exists($con, 'activity_log', 'barangay_id')) {
    $where[] = "activity_log.barangay_id = '" . $con->real_escape_string($barangayFilter) . "'";
}
if ($entityFilter !== '' && barangay_column_exists($con, 'activity_log', 'entity_type')) {
    $where[] = "activity_log.entity_type = '" . $con->real_escape_string($entityFilter) . "'";
}
if ($userFilter !== '' && barangay_column_exists($con, 'activity_log', 'user_id')) {
    $where[] = "activity_log.user_id = '" . $con->real_escape_string($userFilter) . "'";
}
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) && barangay_column_exists($con, 'activity_log', 'created_at')) {
    $where[] = "DATE(activity_log.created_at) >= '" . $con->real_escape_string($dateFrom) . "'";
}
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) && barangay_column_exists($con, 'activity_log', 'created_at')) {
    $where[] = "DATE(activity_log.created_at) <= '" . $con->real_escape_string($dateTo) . "'";
}
if ($search !== '') {
    $like = '%' . $con->real_escape_string($search) . '%';
    $where[] = "(activity_log.message LIKE '{$like}' OR activity_log.date LIKE '{$like}')";
}

$sql = 'SELECT id, message, status, user_id, barangay_id, entity_type, entity_id, date, created_at FROM activity_log';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC LIMIT 10000';

$result = $con->query($sql);
$filename = 'system_logs_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'message', 'status', 'user_id', 'barangay_id', 'entity_type', 'entity_id', 'date', 'created_at']);
while ($result && ($row = $result->fetch_assoc())) {
    fputcsv($out, [
        $row['id'] ?? '',
        $row['message'] ?? '',
        $row['status'] ?? '',
        $row['user_id'] ?? '',
        $row['barangay_id'] ?? '',
        $row['entity_type'] ?? '',
        $row['entity_id'] ?? '',
        $row['date'] ?? '',
        $row['created_at'] ?? '',
    ]);
}
fclose($out);
exit;
