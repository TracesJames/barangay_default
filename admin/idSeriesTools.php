<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/residence_family.php';
require_once '../includes/nutrition_context.php';

$sessionUserId = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_is_super_admin($con, $sessionUserId)
    && !barangay_user_is_ssa($con, $sessionUserId)
    && !barangay_user_can_mutate_barangay_records($con, $sessionUserId)) {
    http_response_code(403);
    exit('Forbidden');
}

$barangayId = (string) ($barangay_id ?? barangay_session_id() ?? '');
$barangayName = (string) ($barangay ?? '');
if ($barangayId === '') {
    header('Location: dashboard.php');
    exit;
}
if ($barangayName === '') {
    $loaded = barangay_load_by_id($con, $barangayId);
    $barangayName = (string) ($loaded['barangay'] ?? 'Barangay');
}

$psgc = function_exists('barangay_resolve_psgc_code')
    ? barangay_resolve_psgc_code($con, $barangayId, $barangayName)
    : '';
$psfc = function_exists('nutrition_resolve_psfc_code')
    ? nutrition_resolve_psfc_code($con, $barangayId, $barangayName)
    : $psgc;

/**
 * @param array<int,int> $used
 * @return array{max:int,gaps:array<int,int>,duplicates:array<int,array{id:string,count:int}>}
 */
$analyzeSeries = static function (array $used, array $duplicateMap): array {
    $nums = array_keys($used);
    sort($nums);
    $max = $nums !== [] ? max($nums) : 0;
    $gaps = [];
    for ($i = 1; $i <= $max && count($gaps) < 200; $i++) {
        if (!isset($used[$i])) {
            $gaps[] = $i;
        }
    }
    $dups = [];
    foreach ($duplicateMap as $id => $count) {
        if ($count > 1) {
            $dups[] = ['id' => (string) $id, 'count' => (int) $count];
        }
    }
    usort($dups, static fn ($a, $b) => $b['count'] <=> $a['count']);

    return ['max' => $max, 'gaps' => $gaps, 'duplicates' => $dups];
};

$hubUsed = [];
$hubDup = [];
if ($psgc !== '' && barangay_table_exists($con, 'residence_information')) {
    $like = $psgc . '-%';
    $stmt = $con->prepare('SELECT residence_id FROM residence_information WHERE residence_id LIKE ?');
    if ($stmt) {
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $res = $stmt->get_result();
        $pad = function_exists('residence_number_pad_length') ? residence_number_pad_length() : 6;
        while ($row = $res->fetch_assoc()) {
            $id = (string) ($row['residence_id'] ?? '');
            $hubDup[$id] = ($hubDup[$id] ?? 0) + 1;
            if (preg_match('/^' . preg_quote($psgc, '/') . '-(\d{' . $pad . '})$/', $id, $m)) {
                $hubUsed[(int) $m[1]] = 1;
            }
        }
        $stmt->close();
    }
}
$hubReport = $analyzeSeries($hubUsed, $hubDup);

$nutUsed = [];
$nutDup = [];
if ($psfc !== '') {
    $like = $psfc . '-%';
    if (barangay_table_exists($con, 'nutrition_household_survey')) {
        $stmt = $con->prepare(
            'SELECT house_hold_id AS ref_id FROM nutrition_household_survey WHERE barangay_id = ? AND house_hold_id LIKE ?'
        );
        if ($stmt) {
            $stmt->bind_param('ss', $barangayId, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $id = (string) ($row['ref_id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $nutDup[$id] = ($nutDup[$id] ?? 0) + 1;
                if (preg_match('/-(\d{5})$/', $id, $m)) {
                    $nutUsed[(int) $m[1]] = 1;
                }
            }
            $stmt->close();
        }
        $stmt = $con->prepare(
            'SELECT residence_id AS ref_id FROM nutrition_household_survey WHERE barangay_id = ? AND residence_id LIKE ?'
        );
        if ($stmt) {
            $stmt->bind_param('ss', $barangayId, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $id = (string) ($row['ref_id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $nutDup[$id] = ($nutDup[$id] ?? 0) + 1;
                if (preg_match('/-(\d{5})$/', $id, $m)) {
                    $nutUsed[(int) $m[1]] = 1;
                }
            }
            $stmt->close();
        }
    }
    if (barangay_table_exists($con, 'nutrition_household_family_member')) {
        $stmt = $con->prepare(
            'SELECT residence_id AS ref_id FROM nutrition_household_family_member WHERE barangay_id = ? AND residence_id LIKE ?'
        );
        if ($stmt) {
            $stmt->bind_param('ss', $barangayId, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $id = (string) ($row['ref_id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $nutDup[$id] = ($nutDup[$id] ?? 0) + 1;
                if (preg_match('/-(\d{5})$/', $id, $m)) {
                    $nutUsed[(int) $m[1]] = 1;
                }
            }
            $stmt->close();
        }
    }
}
$nutReport = $analyzeSeries($nutUsed, $nutDup);

$export = (string) ($_GET['export'] ?? '') === '1';
if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="id_series_' . preg_replace('/\W+/', '_', $barangayName) . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['scheme', 'type', 'value', 'detail']);
    fputcsv($out, ['hub', 'max', (string) $hubReport['max'], $psgc]);
    foreach ($hubReport['gaps'] as $g) {
        fputcsv($out, ['hub', 'gap', (string) $g, '']);
    }
    foreach ($hubReport['duplicates'] as $d) {
        fputcsv($out, ['hub', 'duplicate', $d['id'], (string) $d['count']]);
    }
    fputcsv($out, ['nutrition', 'max', (string) $nutReport['max'], $psfc]);
    foreach ($nutReport['gaps'] as $g) {
        fputcsv($out, ['nutrition', 'gap', (string) $g, '']);
    }
    foreach ($nutReport['duplicates'] as $d) {
        fputcsv($out, ['nutrition', 'duplicate', $d['id'], (string) $d['count']]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ID Series Tools — <?= barangay_h($barangayName) ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <?php require_once '../includes/head_csrf.php'; ?>
</head>
<body class="hold-transition dark-mode layout-top-nav">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand-md navbar-dark">
    <div class="container">
      <a href="dashboard.php" class="navbar-brand"><i class="fas fa-arrow-left mr-2"></i>Back</a>
      <span class="navbar-text text-white">ID Series — <?= barangay_h($barangayName) ?></span>
    </div>
  </nav>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="m-0">Resident / Household ID tools</h1>
            <p class="text-muted mb-0">Gap and duplicate report only. Rewriting existing IDs requires a DB backup and is not done here.</p>
          </div>
          <a class="btn btn-success" href="idSeriesTools.php?export=1"><i class="fas fa-file-csv mr-1"></i> Export CSV</a>
        </div>
      </div>
    </div>
    <div class="content">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header"><h3 class="card-title mb-0">Barangay Hub residence IDs</h3></div>
              <div class="card-body">
                <p><strong>PSGC:</strong> <code><?= barangay_h($psgc !== '' ? $psgc : '—') ?></code></p>
                <p><strong>Max series:</strong> <?= (int) $hubReport['max'] ?> · <strong>Gaps:</strong> <?= count($hubReport['gaps']) ?> · <strong>Duplicate IDs:</strong> <?= count($hubReport['duplicates']) ?></p>
                <?php if ($hubReport['gaps'] !== []) : ?>
                <p class="mb-1"><strong>Missing numbers (first 50):</strong></p>
                <code class="d-block small" style="white-space:normal;"><?= barangay_h(implode(', ', array_slice($hubReport['gaps'], 0, 50))) ?></code>
                <?php endif; ?>
                <?php if ($hubReport['duplicates'] !== []) : ?>
                <ul class="mt-2 mb-0">
                  <?php foreach (array_slice($hubReport['duplicates'], 0, 20) as $d) : ?>
                  <li><code><?= barangay_h($d['id']) ?></code> × <?= (int) $d['count'] ?></li>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header"><h3 class="card-title mb-0">Nutrition household / resident numbers</h3></div>
              <div class="card-body">
                <p><strong>PSFC:</strong> <code><?= barangay_h($psfc !== '' ? $psfc : '—') ?></code></p>
                <p><strong>Max series:</strong> <?= (int) $nutReport['max'] ?> · <strong>Gaps:</strong> <?= count($nutReport['gaps']) ?> · <strong>Duplicate IDs:</strong> <?= count($nutReport['duplicates']) ?></p>
                <?php if ($nutReport['gaps'] !== []) : ?>
                <p class="mb-1"><strong>Missing numbers (first 50):</strong></p>
                <code class="d-block small" style="white-space:normal;"><?= barangay_h(implode(', ', array_slice($nutReport['gaps'], 0, 50))) ?></code>
                <?php endif; ?>
                <?php if ($nutReport['duplicates'] !== []) : ?>
                <ul class="mt-2 mb-0">
                  <?php foreach (array_slice($nutReport['duplicates'], 0, 20) as $d) : ?>
                  <li><code><?= barangay_h($d['id']) ?></code> × <?= (int) $d['count'] ?></li>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="alert alert-secondary">
          Next new Hub ID uses max+1 (gaps are not auto-filled). Nutrition series is barangay-wide trailing 5 digits. To remumber existing records, take a backup first and coordinate with admin — this page will not rewrite IDs.
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
