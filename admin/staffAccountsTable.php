<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/datatables_helper.php';
require_once '../includes/staff_accounts.php';

staff_accounts_ensure_schema($con);

try {
    $columns = ['u.first_name', 'u.first_name', 'u.username', 'b.barangay'];
    $searchValue = datatables_search_like($_REQUEST['search']['value'] ?? '');
    $roleFilter = trim((string) ($_REQUEST['role_filter'] ?? ''));
    $barangayFilter = trim((string) ($_REQUEST['barangay_filter'] ?? ''));

    $scope = staff_account_scope_where($con);
    $where = [$scope['sql']];
    $types = $scope['types'];
    $params = $scope['params'];

    if ($roleFilter === STAFF_ROLE_SSA) {
        $where[] = "u.staff_role = 'ssa'";
    } elseif ($roleFilter === STAFF_ROLE_SUPER_ADMIN) {
        $where[] = "u.staff_role = 'super_admin'";
    } elseif ($roleFilter === STAFF_ROLE_NUTRITION_SUPER_ADMIN) {
        $where[] = "u.staff_role = 'nutrition_super_admin'";
    } elseif ($roleFilter === STAFF_ROLE_ADMIN) {
        $where[] = "u.staff_role = 'admin'";
    } elseif ($roleFilter === STAFF_ROLE_BARANGAY_ADMIN) {
        $where[] = "u.staff_role = 'barangay_admin'";
    } elseif ($roleFilter === STAFF_ROLE_BARANGAY_STAFF) {
        $where[] = "u.staff_role = 'barangay_staff'";
    } elseif ($roleFilter === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR) {
        $where[] = "u.staff_role = 'barangay_nutrition_scholar'";
    } elseif ($roleFilter === STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN) {
        $where[] = "u.staff_role = 'barangay_nutrition_scholar_admin'";
    }

    if ($barangayFilter !== ''
        && (staff_account_actor_is_ssa($con) || staff_account_actor_is_super_admin($con) || staff_account_actor_is_nutrition_sa($con))
        && barangay_column_exists($con, 'users', 'barangay_id')) {
        $where[] = 'u.barangay_id = ?';
        $types .= 's';
        $params[] = $barangayFilter;
    }

    $whereSql = ' WHERE ' . implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*) AS total FROM users u' . $whereSql;
    $countStmt = $con->prepare($countSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalData = (int) $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $sql = "SELECT u.id, u.first_name, u.middle_name, u.last_name, u.username, u.user_type, u.staff_role, u.barangay_id,
                   u.image, u.image_path, b.barangay AS barangay_name
            FROM users u
            LEFT JOIN barangay_information b ON u.barangay_id = b.id" . $whereSql;
    $filterTypes = $types;
    $filterParams = $params;

    if (!empty($_REQUEST['search']['value'])) {
        $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR b.barangay LIKE ?)';
        $filterTypes .= 'ssss';
        $filterParams = array_merge($filterParams, [$searchValue, $searchValue, $searchValue, $searchValue]);
    }

    $filteredCount = $totalData;
    if (!empty($_REQUEST['search']['value'])) {
        $countFilteredSql = 'SELECT COUNT(*) AS total FROM users u LEFT JOIN barangay_information b ON u.barangay_id = b.id' . $whereSql
            . ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR b.barangay LIKE ?)';
        $countFilteredStmt = $con->prepare($countFilteredSql);
        $countFilteredStmt->bind_param($filterTypes, ...$filterParams);
        $countFilteredStmt->execute();
        $filteredCount = (int) $countFilteredStmt->get_result()->fetch_assoc()['total'];
        $countFilteredStmt->close();
    }

    $sql .= datatables_order_clause($columns, $_REQUEST['order'] ?? null, ' ORDER BY u.username ASC');
    $sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);

    $stmt = $con->prepare($sql);
    if ($filterTypes !== '') {
        $stmt->bind_param($filterTypes, ...$filterParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    $actorId = (string) ($_SESSION['user_id'] ?? '');

    while ($row = $result->fetch_assoc()) {
        $role = staff_account_resolve_role($row);
        $canEdit = staff_account_can_manage($con, $row, 'edit');
        $canDelete = staff_account_can_manage($con, $row, 'delete') && (string) $row['id'] !== $actorId;

        if (!empty($row['image'])) {
            $image = '<span class="pop"><img src="' . barangay_h($row['image_path']) . '" alt="user" class="img-circle" width="40"></span>';
        } else {
            $image = '<span class="pop"><img src="../assets/dist/img/image.png" alt="user" class="img-circle" width="40"></span>';
        }

        $middle = $row['middle_name'] !== '' ? $row['middle_name'][0] . '. ' : '';
        $barangayLabel = match ($role) {
            STAFF_ROLE_SSA, STAFF_ROLE_SUPER_ADMIN, STAFF_ROLE_NUTRITION_SUPER_ADMIN => 'System',
            STAFF_ROLE_ADMIN, STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN => 'All Barangays',
            default => barangay_h((string) ($row['barangay_name'] ?? '—')),
        };

        $actions = '';
        if ($canEdit) {
            $actions .= '<button type="button" class="btn btn-sm btn-warning viewStaffAccount mr-1" data-id="' . barangay_h($row['id']) . '" title="Edit"><i class="fas fa-user-edit"></i></button>';
            $actions .= '<button type="button" class="btn btn-sm btn-info resetStaffPassword mr-1" data-id="' . barangay_h($row['id']) . '" title="Reset Password"><i class="fas fa-key"></i></button>';
        }
        if ($canDelete) {
            $actions .= '<button type="button" class="btn btn-sm btn-danger deleteStaffAccount" data-id="' . barangay_h($row['id']) . '" title="Delete"><i class="fas fa-trash"></i></button>';
        }
        if ($actions === '') {
            $actions = '<span class="text-muted">—</span>';
        }

        $data[] = [
            $image,
            barangay_h($row['first_name'] . ' ' . $middle . $row['last_name']),
            barangay_h($row['username']),
            staff_account_role_badge($role),
            $barangayLabel,
            $actions,
        ];
    }

    echo json_encode([
        'draw' => intval($_REQUEST['draw'] ?? 0),
        'recordsTotal' => $totalData,
        'recordsFiltered' => $filteredCount,
        'data' => $data,
        'total' => number_format($totalData),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
}
