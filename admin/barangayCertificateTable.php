<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/datatables_helper.php';

try {
    $var_date_request = $con->real_escape_string($_REQUEST['date_request'] ?? '');
    $var_date_issued = $con->real_escape_string($_REQUEST['date_issued'] ?? '');
    $var_date_expired = $con->real_escape_string($_REQUEST['date_expired'] ?? '');
    $var_status = $con->real_escape_string($_REQUEST['status'] ?? '');
    $var_barangay_id = $con->real_escape_string($_REQUEST['barangay_id'] ?? '');

    $whereClause = barangay_certificates_where_clause($con, ['1=1']);

    if ($var_barangay_id !== '' && barangay_column_exists($con, 'residence_status', 'barangay_id')) {
        $whereClause[] = "residence_status.barangay_id='" . $con->real_escape_string($var_barangay_id) . "'";
    }

    if ($var_date_request !== '') {
        $whereClause[] = "certificate_request.date_request='$var_date_request'";
    }
    if ($var_date_issued !== '') {
        $whereClause[] = "certificate_request.date_issued='$var_date_issued'";
    }
    if ($var_date_expired !== '') {
        $whereClause[] = "certificate_request.date_expired='$var_date_expired'";
    }
    if ($var_status !== '') {
        $whereClause[] = "certificate_request.status='$var_status'";
    }

    $where = barangay_sql_where($whereClause);

    $hasResidenceBarangayId = barangay_column_exists($con, 'residence_status', 'barangay_id');
    $barangaySelect = $hasResidenceBarangayId
        ? 'barangay_information.barangay AS barangay_name, barangay_information.id AS barangay_id, barangay_information.image AS barangay_image, barangay_information.image_path AS barangay_image_path'
        : 'NULL AS barangay_name, NULL AS barangay_id, NULL AS barangay_image, NULL AS barangay_image_path';
    $barangayJoin = $hasResidenceBarangayId
        ? ' LEFT JOIN barangay_information ON residence_status.barangay_id = barangay_information.id'
        : '';

    $sql_residencey = "SELECT certificate_request.*, residence_information.first_name, residence_information.middle_name, residence_information.last_name, residence_information.residence_id,
        $barangaySelect
        FROM certificate_request
        LEFT JOIN residence_information ON certificate_request.residence_id = residence_information.residence_id
        LEFT JOIN residence_status ON residence_information.residence_id = residence_status.residence_id" . $barangayJoin . $where;

    if (!empty($_REQUEST['search']['value'])) {
        $search = $con->real_escape_string($_REQUEST['search']['value']);
        $sql_residencey .= " AND (certificate_request.residence_id LIKE '%$search%'";
        $sql_residencey .= " OR residence_information.last_name LIKE '%$search%'";
        $sql_residencey .= " OR certificate_request.purpose LIKE '%$search%'";
        $sql_residencey .= " OR residence_information.first_name LIKE '%$search%'";
        if ($hasResidenceBarangayId) {
            $sql_residencey .= " OR barangay_information.barangay LIKE '%$search%'";
        }
        $sql_residencey .= ')';
    }

    $query_residency = $con->query($sql_residencey) or die($con->error);
    $totalData = $query_residency->num_rows;

    $__orderColumns = $hasResidenceBarangayId
        ? ['barangay_information.barangay', 'certificate_request.residence_id', 'certificate_request.purpose', 'certificate_request.date_request', 'certificate_request.status']
        : ['certificate_request.residence_id', 'certificate_request.purpose', 'certificate_request.date_request', 'certificate_request.status'];
    $sql_residencey .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY certificate_request.date_request DESC');
    $sql_residencey .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

    $query_residency = $con->query($sql_residencey) or die($con->error);

    $data = [];
    while ($row_residency = $query_residency->fetch_assoc()) {
        $date_today = date('Y-m-d');

        if ($row_residency['status'] === 'PENDING') {
            $status = '<span class="badge badge-warning">' . barangay_h($row_residency['status']) . '</span>';
            $tools = '<i style="cursor:pointer;color:yellow;text-shadow:-1px 0 black,0 1px black,1px 0 black,0 -1px black;" class="fas fa-eye text-lg px-2 acceptStatus" id="' . barangay_h($row_residency['residence_id']) . '" data-id="' . barangay_h($row_residency['id']) . '" data-toggle="tooltip" title="View Request"></i>';
        } elseif ($row_residency['status'] === 'ACCEPTED') {
            $status = '<span class="badge badge-success">' . barangay_h($row_residency['status']) . '</span>';
            if ($row_residency['date_expired'] < $date_today) {
                $tools = '<i style="cursor:pointer;color:red;text-shadow:-1px 0 black,0 1px black,1px 0 black,0 -1px black;" class="fas fa-times-circle text-lg px-2 acceptStatus" id="' . barangay_h($row_residency['residence_id']) . '" data-id="' . barangay_h($row_residency['id']) . '" data-toggle="tooltip" title="Expired"></i>';
            } else {
                $tools = '<a href="printRequest.php?request=' . barangay_h($row_residency['residence_id']) . '&purpose=' . barangay_h($row_residency['id']) . '" target="_blank" style="cursor:pointer;color:pink;text-shadow:-1px 0 black,0 1px black,1px 0 black,0 -1px black;" class="fas fa-print text-lg px-2" data-toggle="tooltip" title="Print"></a>
                <i style="cursor:pointer;color:lime;text-shadow:-1px 0 black,0 1px black,1px 0 black,0 -1px black;" class="fas fa-check text-lg px-2 acceptStatus" id="' . barangay_h($row_residency['residence_id']) . '" data-id="' . barangay_h($row_residency['id']) . '" data-toggle="tooltip" title="View Record"></i>';
            }
        } else {
            $status = '<span class="badge badge-danger">' . barangay_h($row_residency['status']) . '</span>';
            $tools = '<i style="cursor:pointer;color:red;text-shadow:-1px 0 black,0 1px black,1px 0 black,0 -1px black;" class="fas fa-times text-lg px-2 acceptStatus" id="' . barangay_h($row_residency['residence_id']) . '" data-id="' . barangay_h($row_residency['id']) . '" data-toggle="tooltip" title="View Record"></i>';
        }

        $date_issued = $row_residency['date_issued'] !== '' && $row_residency['date_issued'] !== 'none'
            ? date('m/d/Y', strtotime($row_residency['date_issued']))
            : '';
        $date_expired = $row_residency['date_expired'] !== '' && $row_residency['date_expired'] !== 'none'
            ? date('m/d/Y', strtotime($row_residency['date_expired']))
            : '';

        $barangayLogo = barangay_logo_url([
            'image' => $row_residency['barangay_image'] ?? '',
            'image_path' => $row_residency['barangay_image_path'] ?? '',
        ], '../');
        $barangayCell = '<img src="' . barangay_h($barangayLogo) . '" alt="" class="barangay-logo-sm mr-2">'
            . barangay_h($row_residency['barangay_name'] ?? '—');

        $data[] = [
            $barangayCell,
            barangay_h($row_residency['residence_id']),
            barangay_h($row_residency['first_name'] . ' ' . $row_residency['last_name']),
            barangay_h($row_residency['purpose']),
            barangay_h($row_residency['date_request']),
            $date_issued,
            $date_expired,
            $status,
            $tools,
        ];
    }

    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => intval($totalData),
        'recordsFiltered' => intval($totalData),
        'data' => $data,
        'total' => number_format($totalData),
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
}
