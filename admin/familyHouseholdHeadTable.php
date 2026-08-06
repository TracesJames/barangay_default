<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/residence_family.php';
require_once '../includes/datatables_helper.php';

try {
    if (!residence_has_household_head_column($con)) {
        echo json_encode([
            'draw' => (int) ($_REQUEST['draw'] ?? 0),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'total' => '0',
        ]);
        exit;
    }

    $firstName = $con->real_escape_string($_POST['first_name'] ?? '');
    $middleName = $con->real_escape_string($_POST['middle_name'] ?? '');
    $lastName = $con->real_escape_string($_POST['last_name'] ?? '');
    $residentId = $con->real_escape_string($_POST['resident_id'] ?? '');
    $purok = trim($_POST['purok'] ?? '');

    $whereClause = barangay_residents_where_clause($con, [
        "residence_status.archive = 'NO'",
        "residence_status.household_head = 'YES'",
    ]);

    if ($firstName !== '') {
        $whereClause[] = "residence_information.first_name LIKE '%$firstName%'";
    }
    if ($middleName !== '') {
        $whereClause[] = "residence_information.middle_name LIKE '%$middleName%'";
    }
    if ($lastName !== '') {
        $whereClause[] = "residence_information.last_name LIKE '%$lastName%'";
    }
    if ($residentId !== '') {
        $whereClause[] = "residence_information.residence_id='$residentId'";
    }

    barangay_append_purok_filter($con, $whereClause, $purok);

    $where = barangay_sql_where($whereClause);
    $baseFrom = ' FROM residence_information
        INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id' . $where;

    $countSql = 'SELECT COUNT(*) AS total' . $baseFrom;
    $countStmt = $con->prepare($countSql) or die($con->error);
    $countStmt->execute();
    $totalData = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $totalFiltered = $totalData;

    $sql = 'SELECT residence_information.residence_id,
        residence_information.first_name,
        residence_information.middle_name,
        residence_information.last_name,
        residence_information.age,
        residence_information.contact_number,
        residence_information.address,
        residence_information.image,
        residence_information.image_path,
        residence_status.status,
        residence_status.voters' . $baseFrom;

    $__orderColumns = [
        'residence_information.residence_id',
        'residence_information.last_name',
        'residence_information.age',
        'residence_information.contact_number',
        'residence_information.address',
        'residence_status.status',
    ];
    $sql .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, ' ORDER BY residence_information.last_name ASC, residence_information.first_name ASC');
    $sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

    $query = $con->prepare($sql) or die($con->error);
    $query->execute();
    $result = $query->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['image'])) {
            $image = '<img src="' . barangay_h($row['image_path']) . '" alt="resident" class="img-circle" width="40">';
        } else {
            $image = '<img src="../assets/dist/img/blank_image.png" alt="resident" class="img-circle" width="40">';
        }

        $middle = trim((string) ($row['middle_name'] ?? ''));
        $middleInitial = $middle !== '' ? ucfirst($middle)[0] . '. ' : '';
        $name = ucfirst($row['last_name']) . ', ' . ucfirst($row['first_name']) . ' ' . $middleInitial;

        $actions = '<button type="button" class="btn btn-sm btn-warning remove-household-head" data-id="' . barangay_h($row['residence_id']) . '"><i class="fas fa-user-minus"></i> Remove</button>';
        $actions .= ' <i style="cursor:pointer;color:#ff0;" class="fa fa-user-edit text-lg px-2 viewResidence" id="' . barangay_h($row['residence_id']) . '"></i>';

        $data[] = [
            $image,
            $row['residence_id'],
            $name,
            $row['age'],
            $row['contact_number'],
            $row['address'],
            $row['status'],
            $actions,
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
