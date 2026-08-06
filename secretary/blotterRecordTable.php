<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/barangay_context.php';
require_once '../includes/datatables_helper.php';

try {
    header('Content-Type: application/json; charset=utf-8');

    $whereClause = barangay_blotter_where_clause($con);

    if (!empty($_REQUEST['search']['value'])) {
        $search = datatables_sql_like($con, (string) $_REQUEST['search']['value']);
        $whereClause[] = '(blotter_record.status LIKE \'' . $search . '\' '
            . 'OR blotter_record.blotter_id LIKE \'' . $search . '\' '
            . 'OR blotter_record.remarks LIKE \'' . $search . '\' '
            . 'OR blotter_record.type_of_incident LIKE \'' . $search . '\' '
            . 'OR blotter_record.location_incident LIKE \'' . $search . '\' '
            . 'OR blotter_record.date_incident LIKE \'' . $search . '\' '
            . 'OR blotter_record.date_reported LIKE \'' . $search . '\')';
    }

    $where = barangay_sql_where($whereClause);
    $sql_blooter_check = 'SELECT * FROM blotter_record' . $where;

    $query_blotter_check = $con->prepare($sql_blooter_check) or die($con->error);
    $query_blotter_check->execute();
    $result_blotter_check = $query_blotter_check->get_result();
    $totalData = $result_blotter_check->num_rows;

    $__orderColumns = ['blotter_id', 'blotter_id', 'status', 'remarks', 'type_of_incident', 'location_incident', 'date_incident', 'date_reported'];
    $sql_blooter_check .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY date_reported DESC');
    $sql_blooter_check .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

    $query_blotter_check = $con->prepare($sql_blooter_check) or die($con->error);
    $query_blotter_check->execute();
    $result_blotter_check = $query_blotter_check->get_result();
    $data = [];
    while ($row_blotter_check = $result_blotter_check->fetch_assoc()) {
        date_default_timezone_set('Asia/Manila');
        $date_incident = date('m/d/Y - h:i A', strtotime($row_blotter_check['date_incident']));
        $date_reported = date('m/d/Y - h:i A', strtotime($row_blotter_check['date_reported']));

        if ($row_blotter_check['status'] == 'NEW') {
            $status_blotter = '<span class="badge badge-primary">' . barangay_h($row_blotter_check['status']) . '</span>';
        } else {
            $status_blotter = '<span class="badge badge-warning">' . barangay_h($row_blotter_check['status']) . '</span>';
        }

        if ($row_blotter_check['remarks'] == 'CLOSED') {
            $remarks_blotter = '<span class="badge badge-success">' . barangay_h($row_blotter_check['remarks']) . '</span>';
        } else {
            $remarks_blotter = '<span class="badge badge-danger">' . barangay_h($row_blotter_check['remarks']) . '</span>';
        }

        $bid = barangay_h((string) $row_blotter_check['blotter_id']);
        $subdata = [];
        $subdata[] = '<input type="checkbox" id="' . $bid . '" class="sub_checkbox">';
        $subdata[] = $bid;
        $subdata[] = $status_blotter;
        $subdata[] = $remarks_blotter;
        $subdata[] = barangay_h((string) $row_blotter_check['type_of_incident']);
        $subdata[] = barangay_h((string) $row_blotter_check['location_incident']);
        $subdata[] = $date_incident;
        $subdata[] = $date_reported;
        $subdata[] = '<i style="cursor: pointer; color: yellow; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-book-open text-lg px-2 viewRecords" id="' . $bid . '"></i>';
        $data[] = $subdata;
    }

    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => intval($totalData),
        'recordsFiltered' => intval($totalData),
        'data' => $data,
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Unable to load blotter records.',
    ]);
}
