<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/datatables_helper.php';

try {
    $voters = $con->real_escape_string($_POST['voters'] ?? '');
    $age = $con->real_escape_string($_POST['age'] ?? '');
    $status = $con->real_escape_string($_POST['status'] ?? '');
    $pwd = $con->real_escape_string($_POST['pwd'] ?? '');
    $senior = $con->real_escape_string($_POST['senior'] ?? '');
    $singleParent = $con->real_escape_string($_POST['single_parent'] ?? '');

    $whereClause = barangay_residents_where_clause($con, ["residence_status.archive = 'NO'"]);

    if ($voters !== '') {
        $whereClause[] = "residence_status.voters='$voters'";
    }
    if ($age !== '') {
        $whereClause[] = "residence_information.age='$age'";
    }
    if ($status !== '') {
        $whereClause[] = "residence_status.status='$status'";
    }
    if ($pwd !== '') {
        $whereClause[] = "residence_status.pwd='$pwd'";
    }
    if ($singleParent !== '') {
        $whereClause[] = "residence_status.single_parent='$singleParent'";
    }
    if ($senior !== '') {
        $whereClause[] = "residence_status.senior='$senior'";
    }

    $where = barangay_sql_where($whereClause);

    $baseFrom = ' FROM residence_information
        INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id' . $where;

    $countSql = 'SELECT COUNT(*) AS total' . $baseFrom;
    $countStmt = $con->prepare($countSql) or die($con->error);
    $countStmt->execute();
    $totalData = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $totalFiltered = $totalData;

    $sql = 'SELECT residence_information.first_name,
        residence_information.middle_name,
        residence_information.last_name,
        residence_information.age,
        residence_status.pwd_info,
        residence_status.single_parent,
        residence_status.voters,
        residence_status.status,
        residence_status.senior' . $baseFrom;

    $__orderColumns = [
        'residence_information.last_name',
        'residence_information.age',
        'residence_status.pwd_info',
        'residence_status.single_parent',
        'residence_status.voters',
        'residence_status.status',
        'residence_status.senior',
    ];
    $sql .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY residence_information.last_name ASC, residence_information.first_name ASC');
    $sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

    $query = $con->prepare($sql) or die($con->error);
    $query->execute();
    $result = $query->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['middle_name'] !== '') {
            $middleName = ucfirst($row['middle_name'])[0] . '.';
        } else {
            $middleName = '';
        }

        $data[] = [
            ucfirst($row['last_name']) . ' ' . ucfirst($row['first_name']) . ' ' . $middleName,
            $row['age'],
            $row['pwd_info'],
            $row['single_parent'],
            $row['voters'],
            $row['status'],
            $row['senior'],
        ];
    }

    echo json_encode([
        'draw' => (int) ($_REQUEST['draw'] ?? 0),
        'recordsTotal' => $totalData,
        'recordsFiltered' => $totalFiltered,
        'data' => $data,
        'total' => number_format($totalData),
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
}
