<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/datatables_helper.php';
require_once '../includes/audit_log.php';
require_once '../includes/barangay_context.php';

try {
    barangay_audit_ensure_columns($con);

    $statusFilter = trim((string) ($_REQUEST['status_filter'] ?? ''));
    $barangayFilter = trim((string) ($_REQUEST['barangay_filter'] ?? ''));
    $entityFilter = trim((string) ($_REQUEST['entity_filter'] ?? ''));

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
    if (!empty($_REQUEST['search']['value'])) {
        $search = datatables_sql_like($con, (string) $_REQUEST['search']['value']);
        $where[] = "(activity_log.message LIKE '" . $search . "' OR activity_log.date LIKE '" . $search . "')";
    }

    $sql = 'SELECT * FROM activity_log';
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $countRes = $con->query($sql);
    $totalData = $countRes ? $countRes->num_rows : 0;

    $__orderColumns = [
        'activity_log.id',
        'activity_log.message',
        'activity_log.status',
        'activity_log.barangay_id',
        'activity_log.entity_type',
        'activity_log.date',
    ];
    $sql .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY activity_log.id DESC');
    $sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

    $result = $con->query($sql);
    $data = [];
    $hasStatus = true;
    $hasBrgy = barangay_column_exists($con, 'activity_log', 'barangay_id');
    $hasEntity = barangay_column_exists($con, 'activity_log', 'entity_type');

    while ($result && ($row = $result->fetch_assoc())) {
        $sub = [];
        $sub[] = $row['id'];
        $sub[] = htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sub[] = htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sub[] = $hasBrgy ? htmlspecialchars((string) ($row['barangay_id'] ?? '—'), ENT_QUOTES, 'UTF-8') : '—';
        $entity = '';
        if ($hasEntity) {
            $entity = trim((string) ($row['entity_type'] ?? ''));
            if (!empty($row['entity_id'])) {
                $entity .= ($entity !== '' ? ': ' : '') . $row['entity_id'];
            }
        }
        $sub[] = $entity !== '' ? htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') : '—';
        $sub[] = htmlspecialchars((string) ($row['date'] ?? ''), ENT_QUOTES, 'UTF-8');
        $data[] = $sub;
    }

    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => intval($totalData),
        'recordsFiltered' => intval($totalData),
        'data' => $data,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage(),
    ]);
}
