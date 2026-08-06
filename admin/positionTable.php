<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/datatables_helper.php';
require_once '../includes/helpers.php';

try {
    $col = ['position', 'position_limit'];
    $sql = "SELECT COUNT(*) AS total FROM position";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalData = $row['total'];  
    $stmt->close(); 

    $sql = "SELECT position_id, position, position_limit, position_description FROM position";
    
    if (!empty($_REQUEST['search']['value'])) {
        $searchValue = datatables_search_like($_REQUEST['search']['value']);
        $sql .= " WHERE position LIKE ? OR position_description LIKE ? OR position_limit LIKE ?";
    }

    if (isset($_REQUEST['order'])) {
        $sql .= datatables_order_clause($col, $_REQUEST['order'], ' ORDER BY position_id DESC');
    } else {
        $sql .= ' ORDER BY position_id DESC';
    }

    if ((int) ($_REQUEST['length'] ?? 10) != -1) {
        $sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);
    }

    $stmt = $con->prepare($sql);

    // Bind parameters if search is used
    if (!empty($_REQUEST['search']['value'])) {
        $stmt->bind_param('sss', $searchValue, $searchValue, $searchValue);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $subdata = [];
        $subdata[] = strtoupper($row['position']);
        $subdata[] = $row['position_limit'];
        $subdata[] = '<i style="cursor: pointer; color: yellow; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-edit text-lg px-3 viewPosition" id="' . $row['position_id'] . '"></i>
                      <i style="cursor: pointer; color: red; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;" class="fa fa-times text-lg px-3 deletePosition" id="' . $row['position_id'] . '"></i>';
        $data[] = $subdata;
    }


    $json_data = [
        'draw' => intval($_REQUEST['draw']),
        'recordsTotal' => intval($totalData),
        'recordsFiltered' => intval($totalData),
        'data' => $data,
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($json_data);

} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage(),
    ]);
}
?>
