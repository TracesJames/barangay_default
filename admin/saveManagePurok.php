<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/csrf.php';
require_once '../includes/audit_log.php';

csrf_verify();

$sessionUserId = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_can_manage_puroks($con, $sessionUserId)) {
    http_response_code(403);
    exit('Forbidden');
}

$action = trim((string) ($_POST['action'] ?? ''));
$barangayId = trim((string) ($_POST['barangay_id'] ?? ''));
if ($barangayId === '') {
    $barangayId = (string) ($barangay_id ?? barangay_session_id() ?? '');
}

if ($barangayId === ''
    || (!staff_user_can_access_barangay($con, $sessionUserId, $barangayId)
        && !barangay_user_can_pick_barangay($con, $sessionUserId)
        && !barangay_user_is_ssa($con, $sessionUserId))) {
    header('Location: managePuroks.php?error=' . rawurlencode('Invalid barangay.'));
    exit;
}

$name = trim((string) preg_replace('/\s+/u', ' ', (string) ($_POST['purok_name'] ?? '')));
$leader = trim((string) ($_POST['purok_leader'] ?? ''));
$purokId = trim((string) ($_POST['purok_id'] ?? ''));
$bulkNames = trim((string) ($_POST['bulk_names'] ?? ''));

$redirect = static function (string $query) use ($barangayId): void {
    header('Location: managePuroks.php?' . $query);
    exit;
};

if (!barangay_table_exists($con, 'purok') || !barangay_column_exists($con, 'purok', 'barangay_id')) {
    $redirect('error=' . rawurlencode('Purok table is not available.'));
}

barangay_purok_ensure_active_column($con);

$addOne = static function (mysqli $con, string $barangayId, string $name, string $leader) use ($redirect): bool {
    if ($name === '') {
        return false;
    }
    $exists = barangay_find_purok_by_label($con, $barangayId, $name);
    if ($exists !== null) {
        return false;
    }
    $newId = barangay_generate_purok_id($con, $barangayId);
    $hasActive = barangay_column_exists($con, 'purok', 'is_active');
    if ($hasActive) {
        $stmt = $con->prepare('INSERT INTO purok (purok_id, barangay_id, purok, leader, is_active) VALUES (?, ?, ?, ?, 1)');
    } else {
        $stmt = $con->prepare('INSERT INTO purok (purok_id, barangay_id, purok, leader) VALUES (?, ?, ?, ?)');
    }
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ssss', $newId, $barangayId, $name, $leader);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
};

if ($action === 'add') {
    if ($name === '') {
        $redirect('error=' . rawurlencode('Purok / Sitio name is required.'));
    }
    if (!$addOne($con, $barangayId, $name, $leader)) {
        $redirect('error=' . rawurlencode('That Purok / Sitio already exists or could not be added.'));
    }
    barangay_audit_log($con, 'Added purok/sitio: ' . $name, 'create', [
        'barangay_id' => $barangayId,
        'entity_type' => 'purok',
        'entity_id' => $name,
    ]);
    $redirect('msg=' . rawurlencode('Purok / Sitio added.'));
}

if ($action === 'bulk_add') {
    $lines = preg_split('/\r\n|\r|\n/', $bulkNames) ?: [];
    $added = 0;
    $skipped = 0;
    foreach ($lines as $line) {
        $line = trim((string) preg_replace('/\s+/u', ' ', $line));
        if ($line === '') {
            continue;
        }
        // Allow "NAME | leader" or "NAME, leader"
        $parts = preg_split('/\s*[|,]\s*/', $line, 2) ?: [$line];
        $lineName = trim((string) ($parts[0] ?? ''));
        $lineLeader = trim((string) ($parts[1] ?? ''));
        if ($lineName === '') {
            continue;
        }
        if ($addOne($con, $barangayId, $lineName, $lineLeader)) {
            $added++;
        } else {
            $skipped++;
        }
    }
    if ($added === 0) {
        $redirect('error=' . rawurlencode('No new Purok / Sitio added. Check duplicates or empty list.'));
    }
    barangay_audit_log($con, "Bulk added {$added} purok/sitio (skipped {$skipped})", 'create', [
        'barangay_id' => $barangayId,
        'entity_type' => 'purok',
    ]);
    $redirect('msg=' . rawurlencode("Added {$added} Purok / Sitio" . ($skipped > 0 ? " (skipped {$skipped} duplicates/blank)." : '.')));
}

if ($action === 'rename') {
    if ($purokId === '' || $name === '') {
        $redirect('error=' . rawurlencode('Purok / Sitio name is required.'));
    }
    $stmt = $con->prepare('SELECT purok_id, purok FROM purok WHERE purok_id = ? AND barangay_id = ? LIMIT 1');
    if (!$stmt) {
        $redirect('error=' . rawurlencode('Could not update Purok / Sitio.'));
    }
    $stmt->bind_param('ss', $purokId, $barangayId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$current) {
        $redirect('error=' . rawurlencode('Purok / Sitio not found.'));
    }
    $oldName = trim((string) ($current['purok'] ?? ''));
    $dup = barangay_find_purok_by_label($con, $barangayId, $name);
    if ($dup !== null && trim((string) ($dup['purok_id'] ?? '')) !== $purokId) {
        $redirect('error=' . rawurlencode('Another Purok / Sitio already uses that name.'));
    }
    $upd = $con->prepare('UPDATE purok SET purok = ?, leader = ? WHERE purok_id = ? AND barangay_id = ?');
    if (!$upd) {
        $redirect('error=' . rawurlencode('Could not update Purok / Sitio.'));
    }
    $upd->bind_param('ssss', $name, $leader, $purokId, $barangayId);
    if (!$upd->execute()) {
        $upd->close();
        $redirect('error=' . rawurlencode('Could not update Purok / Sitio.'));
    }
    $upd->close();

    if ($oldName !== '' && strcasecmp($oldName, $name) !== 0) {
        if (barangay_table_exists($con, 'nutrition_household_survey')) {
            $sync = $con->prepare(
                'UPDATE nutrition_household_survey SET purok_label = ? WHERE barangay_id = ? AND purok_label = ?'
            );
            if ($sync) {
                $sync->bind_param('sss', $name, $barangayId, $oldName);
                $sync->execute();
                $sync->close();
            }
        }
        if (barangay_table_exists($con, 'staff_purok_assignment')) {
            $sync = $con->prepare(
                'UPDATE staff_purok_assignment SET purok_label = ? WHERE barangay_id = ? AND purok_label = ?'
            );
            if ($sync) {
                $sync->bind_param('sss', $name, $barangayId, $oldName);
                $sync->execute();
                $sync->close();
            }
        }
    }

    $redirect('msg=' . rawurlencode('Purok / Sitio updated.'));
}

if ($action === 'deactivate' || $action === 'activate') {
    if ($purokId === '' || !barangay_column_exists($con, 'purok', 'is_active')) {
        $redirect('error=' . rawurlencode('Soft-deactivate is not available.'));
    }
    $active = $action === 'activate' ? 1 : 0;
    $upd = $con->prepare('UPDATE purok SET is_active = ? WHERE purok_id = ? AND barangay_id = ?');
    if (!$upd) {
        $redirect('error=' . rawurlencode('Could not update status.'));
    }
    $upd->bind_param('iss', $active, $purokId, $barangayId);
    if (!$upd->execute()) {
        $upd->close();
        $redirect('error=' . rawurlencode('Could not update status.'));
    }
    $upd->close();
    barangay_audit_log(
        $con,
        ($active ? 'Activated' : 'Deactivated') . ' purok/sitio ' . $purokId,
        'update',
        ['barangay_id' => $barangayId, 'entity_type' => 'purok', 'entity_id' => $purokId]
    );
    $redirect('msg=' . rawurlencode($active ? 'Purok / Sitio activated.' : 'Purok / Sitio deactivated (history kept).'));
}

if ($action === 'delete') {
    if ($purokId === '') {
        $redirect('error=' . rawurlencode('Missing Purok / Sitio.'));
    }
    $stmt = $con->prepare('SELECT purok_id, purok FROM purok WHERE purok_id = ? AND barangay_id = ? LIMIT 1');
    if (!$stmt) {
        $redirect('error=' . rawurlencode('Could not delete Purok / Sitio.'));
    }
    $stmt->bind_param('ss', $purokId, $barangayId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$current) {
        $redirect('error=' . rawurlencode('Purok / Sitio not found.'));
    }
    $label = trim((string) ($current['purok'] ?? ''));
    if (barangay_purok_is_referenced($con, $barangayId, $purokId, $label)) {
        $redirect('error=' . rawurlencode('Cannot delete a Purok / Sitio that is in use. Deactivate it instead.'));
    }
    $del = $con->prepare('DELETE FROM purok WHERE purok_id = ? AND barangay_id = ?');
    if (!$del) {
        $redirect('error=' . rawurlencode('Could not delete Purok / Sitio.'));
    }
    $del->bind_param('ss', $purokId, $barangayId);
    if (!$del->execute()) {
        $del->close();
        $redirect('error=' . rawurlencode('Could not delete Purok / Sitio.'));
    }
    $del->close();
    $redirect('msg=' . rawurlencode('Purok / Sitio deleted.'));
}

$redirect('error=' . rawurlencode('Unknown action.'));
