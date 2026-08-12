<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/csrf.php';

nutrition_ensure_module_tables($con);

$user_id = (string) $_SESSION['user_id'];
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);
$isCnpc = barangay_user_is_cnpc($con, $user_id);

if (!$isSuperAdmin && !$isBnsAdmin && !$isCityAdmin && !$isNutritionPortalAdmin && !$isCnpc) {
    header('Location: nutritionDashboard.php');
    exit;
}

barangay_clear_active();
csrf_token();
barangay_release_session_lock();

$stmt_user = $con->prepare('SELECT first_name, last_name, image, image_path, user_type FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc() ?: [];
$first_name_user = $row_user['first_name'] ?? 'Admin';
$last_name_user = $row_user['last_name'] ?? '';
$user_type = $row_user['user_type'] ?? 'admin';
$user_image = $row_user['image'] ?? '';
$user_image_path = $row_user['image_path'] ?? '';
$staffRoleLabel = staff_role_label(barangay_user_staff_role($con, $user_id));
if ($isNutritionPortalAdmin) {
    $staffRoleLabel = staff_role_label(STAFF_ROLE_NUTRITION_SUPER_ADMIN);
}
// Sidebar Accounts menu uses $isSuperAdmin || $isNutritionPortalAdmin.
$isSuperAdmin = $isSuperAdmin || $isNutritionPortalAdmin;
$activePage = 'nutrition_super_dashboard';
$brandLogo = barangay_default_logo_url('../');

$cnpcBarangayIds = $isCnpc ? staff_assigned_barangay_ids($con, $user_id) : null;
$barangayRows = nutrition_super_dashboard_rows($con, $cnpcBarangayIds);
$barangayCount = count($barangayRows);
$totalSurveys = array_sum(array_column($barangayRows, 'surveys'));

$childrenTotal = (int) array_sum(array_column($barangayRows, 'children'));
$assessedTotal = (int) array_sum(array_column($barangayRows, 'assessed'));
$pendingTotal = (int) array_sum(array_column($barangayRows, 'pending'));
$atRisk = (int) array_sum(array_column($barangayRows, 'at_risk'));
$teenagePregnant = (int) array_sum(array_column($barangayRows, 'teenage_pregnant'));
$coveragePct = $childrenTotal > 0 ? round(($assessedTotal / $childrenTotal) * 100, 1) : 0.0;

if ($isCnpc) {
    $hubTotals = [
        'children' => $childrenTotal,
        'assessed' => $assessedTotal,
        'pending' => $pendingTotal,
        'teenage_pregnant' => $teenagePregnant,
        'pregnant' => (int) array_sum(array_column($barangayRows, 'pregnant')),
    ];
    $statusTotals = [
        'underweight' => 0,
        'wasted' => 0,
        'severely_wasted' => 0,
        'stunted' => 0,
        'overweight' => 0,
        'obese' => 0,
    ];
    $severelyWasted = 0;
    $stunted = 0;
    $wasted = 0;
} else {
    $hubTotals = nutrition_hub_totals($con);
    $statusTotals = nutrition_hub_status_totals($con);
    $severelyWasted = (int) ($statusTotals['severely_wasted'] ?? 0);
    $stunted = (int) ($statusTotals['stunted'] ?? 0);
    $wasted = (int) ($statusTotals['wasted'] ?? 0);
    $atRisk = ($statusTotals['underweight'] ?? 0) + ($statusTotals['wasted'] ?? 0)
        + ($statusTotals['severely_wasted'] ?? 0) + ($statusTotals['stunted'] ?? 0)
        + ($statusTotals['overweight'] ?? 0) + ($statusTotals['obese'] ?? 0);
    $childrenTotal = (int) ($hubTotals['children'] ?? 0);
    $assessedTotal = (int) ($hubTotals['assessed'] ?? 0);
    $pendingTotal = (int) ($hubTotals['pending'] ?? 0);
    $teenagePregnant = (int) ($hubTotals['teenage_pregnant'] ?? 0);
    $coveragePct = $childrenTotal > 0 ? round(($assessedTotal / $childrenTotal) * 100, 1) : 0.0;
}

$noBnsBarangays = [];
$noSurveyBarangays = [];
$highRiskBarangays = [];
foreach ($barangayRows as $row) {
    $name = trim((string) ($row['barangay'] ?? ''));
    if ($name === '') {
        continue;
    }
    if (trim((string) ($row['bns_username'] ?? '')) === '') {
        $noBnsBarangays[] = $name;
    }
    if ((int) ($row['surveys'] ?? 0) === 0) {
        $noSurveyBarangays[] = $name;
    }
    $rowChildren = (int) ($row['children'] ?? 0);
    $rowAtRisk = (int) ($row['at_risk'] ?? 0);
    if ($rowChildren > 0 && ($rowAtRisk / $rowChildren) >= 0.25 && $rowAtRisk >= 3) {
        $highRiskBarangays[] = $name . ' (' . $rowAtRisk . ')';
    }
}

/** @var list<array{level:string,icon:string,title:string,detail:string,action:?string,href:?string}> $nutritionRecommendations */
$nutritionRecommendations = [];

if ($coveragePct < 50 && $childrenTotal > 0) {
    $nutritionRecommendations[] = [
        'level' => 'danger',
        'icon' => 'fa-clipboard-check',
        'title' => 'Low assessment coverage (' . $coveragePct . '%)',
        'detail' => 'Fewer than half of children have a latest assessment. Prioritize catch-up weighing and height measurement across barangays.',
        'action' => 'Open barangay picker',
        'href' => 'barangayHub.php?picker=1&system=nutrition&view=picker',
    ];
} elseif ($coveragePct < 80 && $childrenTotal > 0) {
    $nutritionRecommendations[] = [
        'level' => 'warning',
        'icon' => 'fa-percentage',
        'title' => 'Assessment coverage needs improvement (' . $coveragePct . '%)',
        'detail' => 'Aim for at least 80% coverage so city nutrition status is reliable for planning.',
        'action' => 'Review barangays',
        'href' => '#nutritionSuperBarangayTable',
    ];
}

if ($pendingTotal > 0) {
    $nutritionRecommendations[] = [
        'level' => $pendingTotal >= 50 ? 'danger' : 'warning',
        'icon' => 'fa-hourglass-half',
        'title' => number_format($pendingTotal) . ' children pending assessment',
        'detail' => 'Complete pending growth assessments so at-risk cases are not missed.',
        'action' => 'Go to assessments',
        'href' => 'barangayHub.php?picker=1&system=nutrition&view=picker',
    ];
}

if ($severelyWasted > 0 || $wasted > 0) {
    $nutritionRecommendations[] = [
        'level' => 'danger',
        'icon' => 'fa-exclamation-triangle',
        'title' => 'Wasting cases need follow-up',
        'detail' => number_format($severelyWasted) . ' severely wasted and ' . number_format($wasted) . ' wasted. Refer for clinical follow-up and supplementary feeding where available.',
        'action' => 'Print city report',
        'href' => 'nutritionSuperPrintReport.php',
    ];
}

if ($stunted > 0) {
    $nutritionRecommendations[] = [
        'level' => 'warning',
        'icon' => 'fa-child',
        'title' => number_format($stunted) . ' stunted children recorded',
        'detail' => 'Coordinate with RHU/BNS on long-term nutrition education, food security, and growth monitoring.',
        'action' => null,
        'href' => null,
    ];
}

if ($atRisk > 0 && $assessedTotal > 0 && ($atRisk / max(1, $assessedTotal)) >= 0.2) {
    $nutritionRecommendations[] = [
        'level' => 'warning',
        'icon' => 'fa-heartbeat',
        'title' => 'High at-risk share among assessed children',
        'detail' => number_format($atRisk) . ' at-risk cases (' . round(($atRisk / max(1, $assessedTotal)) * 100, 1) . '% of assessed). Schedule targeted interventions and household revisits.',
        'action' => null,
        'href' => null,
    ];
}

if ($teenagePregnant > 0) {
    $nutritionRecommendations[] = [
        'level' => 'warning',
        'icon' => 'fa-female',
        'title' => number_format($teenagePregnant) . ' teenage pregnant cases',
        'detail' => 'Ensure prenatal nutrition counseling and close monitoring with the rural health midwife.',
        'action' => 'Print pregnant list',
        'href' => 'nutritionSuperPregnantFamiliesPrint.php',
    ];
}

if ($noBnsBarangays !== []) {
    $sample = array_slice($noBnsBarangays, 0, 4);
    $extra = count($noBnsBarangays) - count($sample);
    $detail = 'No BNS username for: ' . implode(', ', $sample)
        . ($extra > 0 ? ' +' . $extra . ' more' : '')
        . '. Create or assign BNS accounts so barangay encoding can continue.';
    $nutritionRecommendations[] = [
        'level' => 'danger',
        'icon' => 'fa-user-plus',
        'title' => count($noBnsBarangays) . ' barangay(s) without a BNS account',
        'detail' => $detail,
        'action' => 'Account management',
        'href' => 'staffAccounts.php',
    ];
}

if ($noSurveyBarangays !== []) {
    $sample = array_slice($noSurveyBarangays, 0, 4);
    $extra = count($noSurveyBarangays) - count($sample);
    $nutritionRecommendations[] = [
        'level' => 'info',
        'icon' => 'fa-home',
        'title' => count($noSurveyBarangays) . ' barangay(s) with no household surveys',
        'detail' => 'Start encoding in: ' . implode(', ', $sample)
            . ($extra > 0 ? ' +' . $extra . ' more' : '') . '.',
        'action' => 'Open picker',
        'href' => 'barangayHub.php?picker=1&system=nutrition&view=picker',
    ];
}

if ($highRiskBarangays !== []) {
    $sample = array_slice($highRiskBarangays, 0, 5);
    $nutritionRecommendations[] = [
        'level' => 'danger',
        'icon' => 'fa-map-marker-alt',
        'title' => 'Priority barangays with high at-risk rates',
        'detail' => 'Focus support on: ' . implode(', ', $sample) . '.',
        'action' => null,
        'href' => null,
    ];
}

if ($nutritionRecommendations === []) {
    $nutritionRecommendations[] = [
        'level' => 'success',
        'icon' => 'fa-check-circle',
        'title' => 'No critical gaps detected',
        'detail' => 'Coverage and risk indicators look stable. Continue routine monitoring and keep household surveys updated.',
        'action' => 'Print city report',
        'href' => 'nutritionSuperPrintReport.php',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin Nutrition Dashboard | Nutrition Portal</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/css/super-dashboard.css?v=20260720b">
<?php require_once '../includes/head_csrf.php'; ?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260805n">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal nutrition-portal nutrition-super-dashboard">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark nutrition-navbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white mb-0">Nutrition Portal · Super Admin</h5>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link text-white" data-toggle="dropdown" href="#"><i class="far fa-user"></i></a>
        <div class="dropdown-menu dropdown-menu-right">
          <a href="nutritionAccountProfile.php" class="dropdown-item"><?= barangay_h(ucfirst($first_name_user) . ' ' . ucfirst($last_name_user)) ?></a>
          <div class="dropdown-divider"></div>
          <a href="../logout.php" class="dropdown-item">LOGOUT</a>
        </div>
      </li>
    </ul>
  </nav>

  <?php require __DIR__ . '/../includes/partials/super_nutrition_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <div class="nutrition-welcome">
          <div class="row align-items-center">
            <div class="col-auto d-none d-md-block">
              <img src="<?= barangay_h($brandLogo) ?>" alt="Valencia City" class="rounded-circle nutrition-welcome-logo">
            </div>
            <div class="col-lg-9">
              <h1>Nutrition Portal</h1>
              <p>City-wide nutrition profiling across all <?= number_format($barangayCount) ?> barangays in Valencia City, Bukidnon.</p>
              <div class="nutrition-date"><i class="far fa-calendar-alt mr-1"></i> <?= date('l, F j, Y') ?></div>
              <div class="nutrition-actions">
                <a href="barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-th-large"></i> Select Barangay
                </a>
                <a href="nutritionMellpiCityProfile.php" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-clipboard-list"></i> MELLPI City Profile
                </a>
                <a href="nutritionSuperPrintReport.php" target="_blank" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-print"></i> Print City Report
                </a>
                <a href="nutritionHubGuidePrint.php" target="_blank" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-file-pdf"></i> User Guide (PDF)
                </a>
                <a href="nutritionProcessFormPrint.php" target="_blank" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-clipboard-check"></i> Process Form (PDF)
                </a>
                <a href="nutritionSuperPregnantFamiliesPrint.php" target="_blank" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-female"></i> Pregnant Families
                </a>
                <a href="nutritionBnpReport.php?type=all_hh" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-book"></i> BNP 2026
                </a>
                <?php if ($isSuperAdmin) : ?>
                <a href="staffAccounts.php?role=<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR ?>" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-user-nurse"></i> BNS Accounts
                </a>
                <?php if (!$isNutritionPortalAdmin) : ?>
                <a href="superDashboard.php" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-city"></i> Barangay Super Admin
                </a>
                <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <h2 class="nutrition-section-heading"><i class="fas fa-bolt mr-2"></i>Quick Actions</h2>
        <div class="nutrition-workflow-grid">
          <a href="nutritionMellpiCityProfile.php" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-clipboard-list"></i></span>
            <span class="nutrition-workflow-card-title">MELLPI City Profile</span>
            <span class="nutrition-workflow-card-desc">Register MELLPI PRO FORM CM City/Municipality Profile Sheet for Valencia City.</span>
          </a>
          <a href="nutritionSuperPrintReport.php" target="_blank" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-print"></i></span>
            <span class="nutrition-workflow-card-title">Print City Report</span>
            <span class="nutrition-workflow-card-desc">City-wide MELLPI CM + BNP C1–C9 + e-OPT Plus consolidated report.</span>
          </a>
          <a href="nutritionHubGuidePrint.php" target="_blank" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-file-pdf"></i></span>
            <span class="nutrition-workflow-card-title">User Guide (PDF)</span>
            <span class="nutrition-workflow-card-desc">Step-by-step Nutrition Portal manual. Update this guide whenever Nutrition features change.</span>
          </a>
          <a href="nutritionProcessFormPrint.php" target="_blank" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-clipboard-check"></i></span>
            <span class="nutrition-workflow-card-title">Process Form (PDF)</span>
            <span class="nutrition-workflow-card-desc">SOP processes, school/Form C1 data sources, monthly checklist, and sign-off form.</span>
          </a>
          <a href="nutritionSuperPregnantFamiliesPrint.php" target="_blank" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-female"></i></span>
            <span class="nutrition-workflow-card-title">Families with Pregnant</span>
            <span class="nutrition-workflow-card-desc">Official Barangay Nutrition Profile for families with pregnant members across all barangays.</span>
          </a>
          <a href="barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-th-large"></i></span>
            <span class="nutrition-workflow-card-title">Open Barangay Nutrition</span>
            <span class="nutrition-workflow-card-desc">Pick a barangay to manage household surveys, assessments, and reports.</span>
          </a>
          <?php if ($isSuperAdmin) : ?>
          <a href="staffAccounts.php?role=<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR ?>" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-user-nurse"></i></span>
            <span class="nutrition-workflow-card-title">BNS Accounts</span>
            <span class="nutrition-workflow-card-desc">Manage Barangay Nutrition Scholar accounts per barangay.</span>
          </a>
          <a href="staffAccounts.php?role=<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN ?>" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-user-md"></i></span>
            <span class="nutrition-workflow-card-title">BNS Admin Accounts</span>
            <span class="nutrition-workflow-card-desc">Create city-wide BNS Admin accounts for nutrition oversight.</span>
          </a>
          <?php endif; ?>
          <a href="barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-home"></i></span>
            <span class="nutrition-workflow-card-title">Household Surveys</span>
            <span class="nutrition-workflow-card-desc">Open a barangay portal to register or update household nutrition surveys.</span>
          </a>
        </div>

        <h2 class="nutrition-section-heading"><i class="fas fa-chart-bar mr-2"></i>City Overview</h2>
        <div class="nutrition-stats">
          <div class="nutrition-stat nutrition-stat--children">
            <i class="fas fa-map-marked-alt nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($barangayCount) ?></div>
            <div class="nutrition-stat-label">Barangays</div>
          </div>
          <div class="nutrition-stat nutrition-stat--children">
            <i class="fas fa-child nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($hubTotals['children']) ?></div>
            <div class="nutrition-stat-label"><?= barangay_h(nutrition_children_age_label()) ?></div>
          </div>
          <div class="nutrition-stat nutrition-stat--assessed">
            <i class="fas fa-clipboard-check nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($hubTotals['assessed']) ?></div>
            <div class="nutrition-stat-label">Assessed</div>
          </div>
          <div class="nutrition-stat nutrition-stat--pending">
            <i class="fas fa-hourglass-half nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($hubTotals['pending']) ?></div>
            <div class="nutrition-stat-label">Pending Assessment</div>
          </div>
          <div class="nutrition-stat nutrition-stat--risk">
            <i class="fas fa-exclamation-triangle nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($atRisk) ?></div>
            <div class="nutrition-stat-label">At-Risk Cases</div>
          </div>
          <div class="nutrition-stat nutrition-stat--month">
            <i class="fas fa-calendar-check nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($hubTotals['this_month']) ?></div>
            <div class="nutrition-stat-label">Assessments This Month</div>
          </div>
          <div class="nutrition-stat nutrition-stat--assessed">
            <i class="fas fa-home nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($totalSurveys) ?></div>
            <div class="nutrition-stat-label">Household Surveys</div>
          </div>
          <a href="nutritionSuperPregnantFamiliesPrint.php" target="_blank" class="nutrition-stat nutrition-stat--pregnant">
            <i class="fas fa-female nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) ($hubTotals['pregnant'] ?? 0)) ?></div>
            <div class="nutrition-stat-label">Pregnant</div>
          </a>
          <a href="nutritionSuperPregnantFamiliesPrint.php" target="_blank" class="nutrition-stat nutrition-stat--pregnant">
            <i class="fas fa-baby nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) ($hubTotals['teenage_pregnant'] ?? 0)) ?></div>
            <div class="nutrition-stat-label">Teenage Pregnant</div>
          </a>
        </div>

        <div class="row">
          <div class="col-lg-7">
            <div class="card nutrition-panel">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>City Nutritional Status Breakdown</h3>
              </div>
              <div class="card-body">
                <div class="nutrition-status-grid">
                  <?php
                  $statusCards = [
                      ['key' => 'normal', 'label' => 'Normal', 'class' => 'is-normal'],
                      ['key' => 'underweight', 'label' => 'Underweight', 'class' => 'is-underweight'],
                      ['key' => 'wasted', 'label' => 'Wasted', 'class' => 'is-wasted'],
                      ['key' => 'severely_wasted', 'label' => 'Severely Wasted', 'class' => 'is-severe'],
                      ['key' => 'stunted', 'label' => 'Stunted', 'class' => 'is-stunted'],
                      ['key' => 'overweight', 'label' => 'Overweight', 'class' => 'is-overweight'],
                      ['key' => 'obese', 'label' => 'Obese', 'class' => 'is-obese'],
                  ];
                  foreach ($statusCards as $card) :
                  ?>
                  <div class="nutrition-status-chip <?= barangay_h($card['class']) ?>">
                    <span class="nutrition-status-count"><?= number_format($statusTotals[$card['key']] ?? 0) ?></span>
                    <span class="nutrition-status-name"><?= barangay_h($card['label']) ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card nutrition-panel">
              <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-seedling mr-2"></i>Nutrition Coverage</h3>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <span>Assessment coverage</span>
                    <strong><?= barangay_h((string) $coveragePct) ?>%</strong>
                  </div>
                  <div class="progress progress-sm">
                    <div class="progress-bar bg-success" style="width: <?= min(100, $coveragePct) ?>%"></div>
                  </div>
                </div>
                <ul class="list-unstyled mb-0">
                  <li class="mb-2"><i class="fas fa-child text-success mr-2"></i><?= number_format($childrenTotal) ?> children city-wide</li>
                  <li class="mb-2"><i class="fas fa-clipboard-check text-info mr-2"></i><?= number_format($assessedTotal) ?> with latest assessment</li>
                  <li class="mb-2"><i class="fas fa-home text-warning mr-2"></i><?= number_format($totalSurveys) ?> household surveys recorded</li>
                  <li><i class="fas fa-exclamation-triangle text-danger mr-2"></i><?= number_format($atRisk) ?> at-risk cases</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="card nutrition-panel nutrition-recommendations-panel">
          <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-lightbulb mr-2"></i>Issues &amp; Recommendations</h3>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3" style="font-size:0.9rem;">
              Based on current city nutrition totals. Address higher-priority items first.
            </p>
            <div class="nutrition-recommendations-list">
              <?php foreach ($nutritionRecommendations as $rec) :
                  $level = (string) ($rec['level'] ?? 'info');
                  $levelClass = 'is-' . preg_replace('/[^a-z]/', '', $level);
                  ?>
              <div class="nutrition-recommendation <?= barangay_h($levelClass) ?>">
                <div class="nutrition-recommendation-icon">
                  <i class="fas <?= barangay_h((string) ($rec['icon'] ?? 'fa-info-circle')) ?>"></i>
                </div>
                <div class="nutrition-recommendation-body">
                  <div class="nutrition-recommendation-title"><?= barangay_h((string) ($rec['title'] ?? '')) ?></div>
                  <div class="nutrition-recommendation-detail"><?= barangay_h((string) ($rec['detail'] ?? '')) ?></div>
                  <?php if (!empty($rec['href']) && !empty($rec['action'])) :
                      $recHref = (string) $rec['href'];
                      $openBlank = str_contains($recHref, 'Print') || str_starts_with($recHref, 'nutritionSuperPrint');
                      ?>
                  <a class="nutrition-recommendation-action" href="<?= barangay_h($recHref) ?>"<?= $openBlank ? ' target="_blank" rel="noopener"' : '' ?>>
                    <?= barangay_h((string) $rec['action']) ?> <i class="fas fa-arrow-right ml-1"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="card nutrition-panel">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-2"></i>All Barangays · Nutrition Summary</h3>
            <div class="card-tools">
              <a href="nutritionSuperPrintReport.php" target="_blank" class="btn btn-sm btn-outline-success mr-1">
                <i class="fas fa-print mr-1"></i> Print Report
              </a>
              <a href="barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker" class="btn btn-sm btn-success">
                <i class="fas fa-th-large mr-1"></i> Open Picker
              </a>
            </div>
          </div>
          <div class="card-body">
            <table id="nutritionSuperBarangayTable" class="table table-bordered table-striped table-dark mb-0">
              <thead>
                <tr>
                  <th>Barangay</th>
                  <th>Zone</th>
                  <th>Children</th>
                  <th>Assessed</th>
                  <th>Pending</th>
                  <th>At-Risk</th>
                  <th>Surveys</th>
                  <th>BNS Account</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($barangayRows as $row) : ?>
                <tr>
                  <td>
                    <img src="<?= barangay_h($row['logo']) ?>" alt="" class="barangay-logo-sm mr-2" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                    <strong><?= barangay_h($row['barangay']) ?></strong>
                  </td>
                  <td><?= barangay_h($row['zone']) ?></td>
                  <td><?= number_format((int) $row['children']) ?></td>
                  <td><?= number_format((int) $row['assessed']) ?></td>
                  <td><?= number_format((int) $row['pending']) ?></td>
                  <td><?= number_format((int) $row['at_risk']) ?></td>
                  <td><?= number_format((int) $row['surveys']) ?></td>
                  <td><code><?= barangay_h($row['bns_username'] !== '' ? $row['bns_username'] : '—') ?></code></td>
                  <td>
                    <form method="post" action="selectBarangay.php" class="d-inline js-open-nutrition-form">
                      <?= csrf_field(); ?>
                      <input type="hidden" name="barangay_id" value="<?= barangay_h($row['id']) ?>">
                      <input type="hidden" name="redirect" value="nutritionDashboard.php">
                      <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-external-link-alt mr-1"></i> Open
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-sm">
    <strong>Nutrition Portal</strong> — Valencia City
    <div class="float-right d-none d-sm-inline-block">v1.0</div>
  </footer>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script>
$(function () {
  $('#nutritionSuperBarangayTable').DataTable({
    responsive: true,
    autoWidth: false,
    order: [[0, 'asc']],
    pageLength: 10
  });

  $(document).on('submit', '.js-open-nutrition-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    if (typeof barangaySyncCsrfForms === 'function') {
      barangaySyncCsrfForms();
    }
    $.ajax({
      url: $form.attr('action') || 'selectBarangay.php',
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function (res) {
        window.location.href = res.redirect || 'nutritionDashboard.php';
      }
    }).fail(function () {
      alert('Could not open barangay nutrition dashboard. Please refresh and try again.');
    });
  });
});
</script>
</body>
</html>
