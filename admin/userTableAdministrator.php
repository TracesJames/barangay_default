<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/datatables_helper.php';
require_once '../includes/helpers.php';

require_once '../includes/barangay_context.php';

try {
    $columns = ['u.first_name', 'u.first_name', 'u.username'];
    $searchValue = datatables_search_like($_REQUEST['search']['value'] ?? '');
    $scopeId = barangay_resolve_scope_id($con);
    $scopeSql = '';
    $scopeTypes = '';
    $scopeParams = [];

    if ($scopeId !== null && barangay_column_exists($con, 'users', 'barangay_id')) {
        $scopeSql = ' AND u.barangay_id = ?';
        $scopeTypes = 's';
        $scopeParams = [$scopeId];
    }

    $countSql = "SELECT COUNT(*) AS total FROM users u WHERE u.user_type NOT IN ('resident', 'admin')" . $scopeSql;
    $countStmt = $con->prepare($countSql);
    if ($scopeTypes !== '') {
        $countStmt->bind_param($scopeTypes, ...$scopeParams);
    }
    $countStmt->execute();
    $totalData = (int) $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $sql = "SELECT u.id, u.first_name, u.middle_name, u.last_name, u.username, u.image, u.image_path
            FROM users u
            WHERE u.user_type NOT IN ('resident', 'admin')" . $scopeSql;
    $types = $scopeTypes;
    $params = $scopeParams;

    if (!empty($_REQUEST['search']['value'])) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ?)";
        $types .= 'sss';
        $params = [$searchValue, $searchValue, $searchValue];
    }

    $sql .= datatables_order_clause($columns, $_REQUEST['order'] ?? null, ' ORDER BY u.username DESC');
    $sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

    $stmt = $con->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['image'])) {
            $image = '<span style="cursor: pointer;" class="pop"><img src="' . barangay_h($row['image_path']) . '" alt="user_image" class="img-circle" width="40"></span>';
        } else {
            $image = '<span style="cursor: pointer;" class="pop"><img src="../assets/dist/img/image.png" alt="user_image" class="img-circle" width="40"></span>';
        }

        $middle_name = $row['middle_name'] !== '' ? $row['middle_name'][0] . '. ' : '';

        $data[] = [
            $image,
            barangay_h($row['first_name'] . ' ' . $middle_name . $row['last_name']),
            barangay_h($row['username']),
            '********',
            '<i style="cursor: pointer; color: yellow; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-user-edit text-lg px-3 viewUserAdministrator" id="' . barangay_h($row['id']) . '"></i>
            <i style="cursor: pointer; color: red; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-times text-lg px-3 deleteUserAdministrator" id="' . barangay_h($row['id']) . '"></i>',
        ];
    }

    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => $totalData,
        'recordsFiltered' => $totalData,
        'data' => $data,
        'total' => number_format($totalData),
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
}
