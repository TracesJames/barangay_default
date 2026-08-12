<?php
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/nutrition_context.php';
require_once '../includes/csrf.php';

$hubSystem = (isset($_GET['system']) && $_GET['system'] === 'nutrition') ? 'nutrition' : 'admin';
$hubIsNutrition = $hubSystem === 'nutrition';
$hubForcePicker = isset($_GET['view']) && $_GET['view'] === 'picker';

$user_id = $_SESSION['user_id'];
$stmt_user = $con->prepare('SELECT first_name, last_name, image FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'] ?? 'Admin';

$isSuperAdmin = barangay_user_is_super_admin($con, (string) $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, (string) $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, (string) $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, (string) $user_id);
$isCnpc = barangay_user_is_cnpc($con, (string) $user_id);
$canPickAllBarangays = $isSuperAdmin || $isCityAdmin || $isBnsAdmin || $isNutritionPortalAdmin;
$isPickerMode = isset($_GET['picker']) && $_GET['picker'] !== '' && $_GET['picker'] !== '0';

if ($isNutritionPortalAdmin && !$hubIsNutrition) {
    header('Location: barangayHub.php?picker=1&system=nutrition&view=picker');
    exit;
}

if ($isCnpc && !$hubIsNutrition) {
    header('Location: barangayHub.php?picker=1&system=nutrition&view=picker');
    exit;
}

if ($hubIsNutrition && $isPickerMode && !$hubForcePicker && ($isSuperAdmin || $isBnsAdmin) && !$isNutritionPortalAdmin && !$isCnpc) {
    header('Location: nutritionSuperDashboard.php');
    exit;
}

if ($isBnsAdmin && !$hubIsNutrition) {
    header('Location: barangayHub.php?picker=1&system=nutrition');
    exit;
}

$barangays = [];
if ($isCnpc) {
    $assignedIds = staff_assigned_barangay_ids($con, (string) $user_id);
    $assignedLookup = array_fill_keys($assignedIds, true);
    $barangays = array_values(array_filter(
        barangay_list_all($con),
        static fn (array $row): bool => isset($assignedLookup[(string) ($row['id'] ?? '')])
    ));
    if (!$isPickerMode && count($barangays) === 1) {
        barangay_set_active($barangays[0]['id']);
        header('Location: nutritionDashboard.php');
        exit;
    }
    if (!$isPickerMode && count($barangays) !== 1) {
        header('Location: barangayHub.php?picker=1&system=nutrition&view=picker');
        exit;
    }
} elseif (!$canPickAllBarangays) {
    if (!$isPickerMode) {
        if (barangay_load_active($con) !== null) {
            header('Location: dashboard.php');
            exit;
        }
        header('Location: barangayHub.php?picker=1');
        exit;
    }

    $userBarangayId = barangay_user_barangay_id($con, (string) $user_id);
    if ($userBarangayId !== null) {
        $barangays = array_values(array_filter(
            barangay_list_all($con),
            static fn (array $row): bool => (string) ($row['id'] ?? '') === $userBarangayId
        ));
        if (count($barangays) === 1) {
            barangay_set_active($barangays[0]['id']);
            header('Location: ' . ($hubIsNutrition ? 'nutritionDashboard.php' : 'dashboard.php'));
            exit;
        }
    }
} else {
    $barangays = barangay_list_all($con);
}

$hubShowManagement = $isSuperAdmin && !$isNutritionPortalAdmin;
$hubShowStats = $isSuperAdmin;
$hubSimplifiedCards = $isPickerMode && !$isSuperAdmin;
$barangayCount = count($barangays);

if ($canPickAllBarangays && $isPickerMode) {
    barangay_clear_active();
}
$activeId = barangay_session_id();

if ($isSuperAdmin && $barangayCount === 1 && !$isPickerMode) {
    barangay_set_active($barangays[0]['id']);
    header('Location: ' . ($hubIsNutrition ? 'nutritionDashboard.php' : 'dashboard.php'));
    exit;
}

if (($isCityAdmin || $isBnsAdmin) && $barangayCount === 1 && !$isPickerMode) {
    barangay_set_active($barangays[0]['id']);
    header('Location: ' . ($hubIsNutrition ? 'nutritionDashboard.php' : 'dashboard.php'));
    exit;
}

// Mint CSRF before releasing the session lock so Open Barangay / AJAX POSTs verify.
csrf_token();
barangay_release_session_lock();

$hubTotals = $hubShowStats
    ? ($hubIsNutrition ? nutrition_hub_totals($con) : barangay_hub_totals($con))
    : [];
$hubOpenRedirect = $hubIsNutrition ? 'nutritionDashboard.php' : 'dashboard.php';

$nutritionPickerById = [];
$totalHouseholdSurveys = 0;
if ($hubIsNutrition) {
    foreach (nutrition_super_dashboard_rows($con) as $nutritionRow) {
        $nutritionPickerById[(string) ($nutritionRow['id'] ?? '')] = $nutritionRow;
        $totalHouseholdSurveys += (int) ($nutritionRow['surveys'] ?? 0);
    }
    if ($hubShowStats) {
        $hubTotals['surveys'] = $totalHouseholdSurveys;
        $hubTotals['pregnant'] = (int) ($hubTotals['pregnant'] ?? nutrition_pregnant_count($con));
        $hubTotals['teenage_pregnant'] = (int) ($hubTotals['teenage_pregnant'] ?? nutrition_teenage_pregnant_count($con));
        $childrenTotal = max(0, (int) ($hubTotals['children'] ?? 0));
        $assessedTotal = max(0, (int) ($hubTotals['assessed'] ?? 0));
        $hubTotals['coverage'] = $childrenTotal > 0
            ? (int) round(($assessedTotal / $childrenTotal) * 100)
            : 0;
    }
}

$statCards = $hubIsNutrition ? [
    ['key' => 'children', 'icon' => 'fa-child', 'label' => nutrition_children_age_label(), 'border' => 'border-t-green-600', 'iconColor' => 'text-green-400', 'list' => false, 'hint' => 'Children aged 0–' . nutrition_child_max_age_years() . ' across all barangays'],
    ['key' => 'assessed', 'icon' => 'fa-clipboard-check', 'label' => 'Assessed', 'border' => 'border-t-cyan-600', 'iconColor' => 'text-cyan-700', 'list' => false, 'hint' => 'Residents with at least one nutrition assessment'],
    ['key' => 'pending', 'icon' => 'fa-hourglass-half', 'label' => 'Pending Assessment', 'border' => 'border-t-amber-500', 'iconColor' => 'text-amber-600', 'list' => false, 'hint' => 'Children not yet assessed this period'],
    ['key' => 'at_risk', 'icon' => 'fa-exclamation-triangle', 'label' => 'At-Risk Cases', 'border' => 'border-t-red-500', 'iconColor' => 'text-red-600', 'list' => false, 'hint' => 'Underweight, wasted, stunted, overweight, or obese cases'],
    ['key' => 'surveys', 'icon' => 'fa-home', 'label' => 'Household Surveys', 'border' => 'border-t-sky-500', 'iconColor' => 'text-sky-700', 'list' => false, 'hint' => 'Registered household nutrition surveys city-wide'],
    ['key' => 'pregnant', 'icon' => 'fa-female', 'label' => 'Pregnant', 'border' => 'border-t-rose-500', 'iconColor' => 'text-rose-600', 'list' => false, 'hint' => 'All pregnant individuals recorded in household surveys'],
    ['key' => 'teenage_pregnant', 'icon' => 'fa-baby', 'label' => 'Teenage Pregnant', 'border' => 'border-t-pink-500', 'iconColor' => 'text-pink-600', 'list' => false, 'hint' => 'Pregnant individuals marked Teenage in household surveys'],
    ['key' => 'this_month', 'icon' => 'fa-calendar-check', 'label' => 'This Month', 'border' => 'border-t-emerald-600', 'iconColor' => 'text-emerald-700', 'list' => false, 'hint' => 'Assessments recorded in the current month'],
    ['key' => 'coverage', 'icon' => 'fa-percentage', 'label' => 'Assessment Coverage', 'border' => 'border-t-lime-600', 'iconColor' => 'text-lime-700', 'list' => false, 'hint' => 'Share of children with at least one assessment'],
] : [
    ['key' => 'population', 'icon' => 'fa-users', 'label' => 'Population', 'border' => 'border-t-teal-400', 'iconColor' => 'text-teal-400/60', 'list' => true],
    ['key' => 'voters', 'icon' => 'fa-user-check', 'label' => 'Registered Voters', 'border' => 'border-t-green-500', 'iconColor' => 'text-green-500/60', 'list' => true],
    ['key' => 'non_voters', 'icon' => 'fa-user-times', 'label' => 'Non-Voters', 'border' => 'border-t-amber-400', 'iconColor' => 'text-amber-400/60', 'list' => true],
    ['key' => 'children', 'icon' => 'fa-child', 'label' => 'Children (0–17)', 'border' => 'border-t-cyan-400', 'iconColor' => 'text-cyan-400/60', 'list' => true],
    ['key' => 'senior', 'icon' => 'fa-blind', 'label' => 'Senior Citizens', 'border' => 'border-t-red-500', 'iconColor' => 'text-red-500/60', 'list' => true],
    ['key' => 'pwd', 'icon' => 'fa-wheelchair', 'label' => 'PWD', 'border' => 'border-t-brand', 'iconColor' => 'text-brand-light/60', 'list' => true],
    ['key' => 'single_parent', 'icon' => 'fa-baby', 'label' => 'Single Parents', 'border' => 'border-t-pink-500', 'iconColor' => 'text-pink-500/60', 'list' => true],
    ['key' => 'indigenous', 'icon' => 'fa-feather-alt', 'label' => 'Indigenous (IP)', 'border' => 'border-t-stone-400', 'iconColor' => 'text-stone-400/60', 'list' => true],
    ['key' => 'blotter', 'icon' => 'fa-book', 'label' => 'Blotter Records', 'border' => 'border-t-violet-500', 'iconColor' => 'text-violet-500/60', 'list' => false],
];

$hubDefaultDistrict = 'Valencia City';
$hubDefaultAddress = 'Valencia City, Bukidnon';
$hubDefaultLogo = barangay_default_logo_url('../');
$hubExistingNames = array_map(static fn (array $row): string => (string) ($row['barangay'] ?? ''), $barangays);
$hubAdminAccounts = [];
$hubSecretaryAccounts = [];
if (barangay_column_exists($con, 'users', 'barangay_id')) {
    $adminResult = $con->query("SELECT barangay_id, username FROM users WHERE user_type = 'admin' AND barangay_id IS NOT NULL AND barangay_id != ''");
    if ($adminResult) {
        while ($adminRow = $adminResult->fetch_assoc()) {
            $hubAdminAccounts[(string) $adminRow['barangay_id']] = (string) $adminRow['username'];
        }
    }
    $secretaryResult = $con->query("SELECT barangay_id, username FROM users WHERE user_type = 'secretary' AND barangay_id IS NOT NULL AND barangay_id != ''");
    if ($secretaryResult) {
        while ($secretaryRow = $secretaryResult->fetch_assoc()) {
            $hubSecretaryAccounts[(string) $secretaryRow['barangay_id']] = (string) $secretaryRow['username'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $isPickerMode
      ? ($hubIsNutrition ? 'Choose Barangay · Nutrition Portal' : 'Choose Barangay · City of Valencia Portal')
      : ($hubIsNutrition ? 'Nutrition Portal' : 'City of Valencia Portal') ?> | Valencia City</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/barangay-hub-page.css">
<?php
require_once '../includes/partials/tailwind_cdn.php';
require_once '../includes/head_csrf.php';
?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260805n">
</head>
<body class="barangay-portal min-h-screen font-sans antialiased text-white">

  <div id="tailwind-scope" class="hub-page min-h-screen text-white<?= $hubIsNutrition ? ' hub-page--nutrition' : '' ?>">
  <!-- Ambient glow -->
  <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
    <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full hub-glow-a blur-3xl"></div>
    <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full hub-glow-b blur-3xl"></div>
  </div>

  <div class="relative z-10">
    <!-- Top bar -->
    <nav class="hub-topbar mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4">
      <div class="flex min-w-0 items-center gap-3">
        <img src="<?= barangay_h($hubDefaultLogo) ?>" alt="Valencia City" class="h-10 w-10 shrink-0 rounded-full border <?= $hubIsNutrition ? 'border-green-700/25' : 'border-white/20' ?> object-cover shadow-md" onerror="this.onerror=null;this.src='../assets/logo/valencia-city.png';">
        <div class="min-w-0">
          <div class="truncate text-sm font-extrabold tracking-wide <?= $hubIsNutrition ? 'text-white' : 'text-white' ?>"><?= barangay_h(barangay_portal_brand_name($con, (string) $user_id, $hubIsNutrition)) ?></div>
          <div class="truncate text-xs <?= $hubIsNutrition ? 'text-white/60' : 'text-white/50' ?>"><?= barangay_h(barangay_portal_brand_tagline($con, (string) $user_id, $hubIsNutrition)) ?></div>
        </div>
      </div>
      <div class="flex flex-wrap items-center justify-end gap-3 sm:gap-5">
        <div class="flex flex-wrap items-center gap-2">
          <?php if (!$isNutritionPortalAdmin) : ?>
          <a href="barangayHub.php?picker=1" class="hub-system-switch <?= !$hubIsNutrition ? 'is-active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> City of Valencia Portal
          </a>
          <?php endif; ?>
          <a href="barangayHub.php?picker=1&amp;system=nutrition<?= $isNutritionPortalAdmin ? '&amp;view=picker' : '' ?>" class="hub-system-switch <?= $hubIsNutrition ? 'is-active' : '' ?>">
            <i class="fas fa-seedling"></i> Nutrition Portal
          </a>
        </div>
        <?php if ($hubShowManagement && !$hubIsNutrition) : ?>
        <a href="barangayCertificates.php" class="text-sm font-semibold text-white/70 transition hover:text-white">
          <i class="fas fa-certificate mr-1.5"></i>Certificates
        </a>
        <a href="superDashboard.php" class="text-sm font-semibold text-white/70 transition hover:text-white">
          <i class="fas fa-city mr-1.5"></i>Super Admin
        </a>
        <?php elseif ($hubIsNutrition && ($isSuperAdmin || $isBnsAdmin)) : ?>
        <a href="nutritionSuperDashboard.php" class="text-sm font-semibold text-white/70 transition hover:text-white">
          <i class="fas fa-chart-pie mr-1.5"></i>Super Admin Dashboard
        </a>
        <?php elseif ($isPickerMode) : ?>
        <a href="dashboard.php" class="text-sm font-semibold <?= $hubIsNutrition ? 'text-white/70 hover:text-white' : 'text-white/70 hover:text-white' ?> transition">
          <i class="fas fa-arrow-left mr-1.5"></i>Back
        </a>
        <?php endif; ?>
        <a href="../logout.php" class="text-sm font-semibold <?= $hubIsNutrition ? 'text-white/70 hover:text-white' : 'text-white/70 hover:text-white' ?> transition">
          <i class="fas fa-sign-out-alt mr-1.5"></i>Logout
        </a>
      </div>
    </nav>

    <?php if ($isPickerMode && ($isSuperAdmin || $isCityAdmin)) : ?>
    <div class="mx-auto max-w-7xl px-5 pt-6">
      <div class="hub-picker-banner <?= $hubIsNutrition ? 'hub-picker-banner--nutrition hub-picker-banner--rich' : '' ?> rounded-2xl px-5 py-4 text-center sm:px-6 sm:py-5">
        <?php if ($hubIsNutrition) : ?>
        <p class="text-sm font-bold text-white sm:text-base">
          <i class="fas fa-seedling mr-2 text-green-400"></i>
          Choose a barangay to continue nutrition profiling
        </p>
        <p class="mt-1 text-xs text-white/60 sm:text-sm">
          Open household surveys, child assessments, BNP reports, and pregnant family profiles for that barangay.
        </p>
        <?php if ($isSuperAdmin || $isBnsAdmin) : ?>
        <div class="hub-nutrition-quicklinks mt-4 flex flex-wrap items-center justify-center gap-2">
          <a href="nutritionSuperDashboard.php" class="hub-nutrition-quicklink"><i class="fas fa-chart-pie"></i> City Dashboard</a>
          <a href="nutritionSuperPregnantFamiliesPrint.php" target="_blank" class="hub-nutrition-quicklink"><i class="fas fa-female"></i> Pregnant Families</a>
          <a href="nutritionSuperPrintReport.php" target="_blank" class="hub-nutrition-quicklink"><i class="fas fa-print"></i> City Report</a>
          <a href="cityReportPack.php" class="hub-nutrition-quicklink"><i class="fas fa-file-alt"></i> City Report Pack</a>
          <a href="nutritionMellpiCityProfile.php" class="hub-nutrition-quicklink"><i class="fas fa-clipboard-list"></i> MELLPI</a>
        </div>
        <?php endif; ?>
        <?php else : ?>
        <p class="text-sm font-bold text-teal-200 sm:text-base">
          <i class="fas fa-hand-pointer mr-2 text-teal-300"></i>
          Select a barangay to open its admin dashboard
        </p>
        <p class="mt-1 text-xs text-white/55 sm:text-sm">
          <?= $isSuperAdmin
              ? 'You can still add barangays, manage accounts, and update logos from this page.'
              : 'Choose the barangay you want to manage for this session.' ?>
        </p>
        <?php endif; ?>
      </div>
    </div>
    <?php elseif ($isPickerMode) : ?>
    <div class="mx-auto max-w-7xl px-5 pt-6">
      <div class="hub-picker-banner <?= $hubIsNutrition ? 'hub-picker-banner--nutrition hub-picker-banner--rich' : '' ?> rounded-2xl px-5 py-4 text-center sm:px-6 sm:py-5">
        <p class="text-sm font-bold <?= $hubIsNutrition ? 'text-white' : 'text-teal-200' ?> sm:text-base">
          <i class="fas <?= $hubIsNutrition ? 'fa-seedling' : 'fa-hand-pointer' ?> mr-2 <?= $hubIsNutrition ? 'text-green-400' : 'text-teal-300' ?>"></i>
          <?= $hubIsNutrition ? 'Choose a barangay to continue nutrition profiling' : 'Select a barangay to open its admin dashboard' ?>
        </p>
        <p class="mt-1 text-xs <?= $hubIsNutrition ? 'text-white/60' : 'text-white/55' ?> sm:text-sm">
          <?= $hubIsNutrition
              ? 'Open household surveys, assessments, and barangay nutrition reports for this session.'
              : 'Choose the barangay you want to manage for this session.' ?>
        </p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="mx-auto max-w-7xl px-5 pb-6 pt-8 text-center sm:pt-10">
      <div class="mb-3 inline-flex items-center gap-2 rounded-full border <?= $hubIsNutrition ? 'border-green-400/30 bg-white/[0.06] text-emerald-100/80' : 'border-white/10 bg-white/5 text-white/60' ?> px-4 py-1.5 text-xs font-bold uppercase tracking-widest">
        <i class="fas <?= $hubIsNutrition ? 'fa-leaf text-green-400' : 'fa-map-marker-alt text-teal-400' ?>"></i>
        Valencia City, Bukidnon
      </div>
      <h1 class="text-3xl font-extrabold tracking-tight <?= $hubIsNutrition ? 'text-white' : 'text-white' ?> sm:text-4xl">
        <?= $isPickerMode
            ? ($hubIsNutrition ? 'Nutrition Portal · Choose Barangay' : 'City of Valencia Portal · Choose Barangay')
            : ($hubIsNutrition ? 'Nutrition Portal' : 'City of Valencia Portal') ?>
      </h1>
      <p class="mt-2 text-base <?= $hubIsNutrition ? 'text-white/60' : 'text-white/60' ?>">
        Hello, <?= barangay_h(ucfirst($first_name_user)) ?> —
        <span class="font-semibold <?= $hubIsNutrition ? 'text-white' : 'text-white/80' ?>"><?= number_format($barangayCount) ?> barangay<?= $barangayCount === 1 ? '' : 's' ?></span>
        <?= $isPickerMode ? ' available' : ' registered' ?>
        <?= $hubIsNutrition ? ' for nutrition profiling' : '' ?>
      </p>
    </header>

    <?php if ($hubShowStats) : ?>
    <?php if ($hubIsNutrition && $isPickerMode) : ?>
    <section class="mx-auto mb-6 max-w-7xl px-5">
      <div class="hub-nutrition-tips">
        <div class="hub-nutrition-tip">
          <strong><i class="fas fa-home mr-1"></i> Household Survey</strong>
          <span>Encode household heads, children, and pregnant/lactating members with auto growth results.</span>
        </div>
        <div class="hub-nutrition-tip">
          <strong><i class="fas fa-weight mr-1"></i> Child Assessment</strong>
          <span>Profile residents aged 0–<?= (int) nutrition_child_max_age_years() ?> and track at-risk nutritional status.</span>
        </div>
        <div class="hub-nutrition-tip">
          <strong><i class="fas fa-file-medical mr-1"></i> BNP &amp; e-OPT</strong>
          <span>Generate barangay nutrition profile forms and consolidated print reports from survey data.</span>
        </div>
        <div class="hub-nutrition-tip">
          <strong><i class="fas fa-female mr-1"></i> Pregnant Families</strong>
          <span>Review teenage and other pregnant nutrition categories for BNP Families with Pregnant.</span>
        </div>
      </div>
    </section>
    <?php endif; ?>
    <section class="mx-auto mb-10 max-w-7xl px-5">
      <h2 class="mb-4 text-center text-xs font-extrabold uppercase tracking-[0.15em] <?= $hubIsNutrition ? 'text-white/55' : 'text-white/50' ?>">
        <i class="fas fa-chart-bar mr-2"></i><?= $hubIsNutrition ? 'City-wide Nutrition Summary' : 'City-wide Summary' ?>
      </h2>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-<?= $hubIsNutrition ? '4' : '3' ?> sm:gap-4">
        <?php foreach ($statCards as $card) :
          $value = (int) ($hubTotals[$card['key']] ?? 0);
          $isListCard = !empty($card['list']) && $value > 0;
          $cardClasses = 'group hub-stat-card flex items-center gap-3 rounded-xl border border-white/10 border-l-[3px] '
            . barangay_h(str_replace('border-t-', 'border-l-', $card['border']))
            . ' bg-white/[0.04] px-4 py-2.5 backdrop-blur-sm transition hover:bg-white/[0.07] hover:shadow-glow sm:py-3';
          if ($isListCard) {
              $cardClasses .= ' hub-stat-card--clickable cursor-pointer';
          } elseif ($hubIsNutrition && !empty($card['hint'])) {
              $cardClasses .= ' hub-stat-card--nutrition-hint';
          }
          $cardHref = $hubIsNutrition ? 'nutritionDashboard.php' : 'allResidence.php';
          if ($isListCard && $card['key'] === 'blotter') {
              $cardHref = 'blotterRecord.php';
          } elseif ($isListCard && $card['key'] !== 'population') {
              $cardHref = 'allResidence.php?filter=' . urlencode($card['key']);
          }
          $displayValue = ($hubIsNutrition && $card['key'] === 'coverage')
              ? number_format($value) . '%'
              : number_format($value);
        ?>
        <?php if ($isListCard) : ?>
        <a href="<?= barangay_h($cardHref) ?>" class="<?= $cardClasses ?>" title="View resident list">
        <?php else : ?>
        <div class="<?= $cardClasses ?>"<?= ($hubIsNutrition && !empty($card['hint'])) ? ' title="' . barangay_h((string) $card['hint']) . '"' : '' ?>>
        <?php endif; ?>
          <i class="fas <?= barangay_h($card['icon']) ?> shrink-0 text-lg <?= barangay_h($card['iconColor']) ?> sm:text-xl"></i>
          <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
            <div class="text-xl font-extrabold leading-none tabular-nums text-white sm:text-2xl"><?= barangay_h($displayValue) ?></div>
            <div class="text-right text-[0.6rem] font-bold uppercase leading-tight tracking-wide text-white/70 sm:text-[0.65rem]">
              <?= barangay_h($card['label']) ?>
            </div>
          </div>
        <?php if ($isListCard) : ?>
        </a>
        <?php else : ?>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Barangay grid -->
    <section class="mx-auto max-w-7xl px-5 pb-16">
      <div class="hub-toolbar mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-extrabold uppercase tracking-wider <?= $hubIsNutrition ? 'text-white' : 'text-white' ?>">
            <i class="fas fa-th-large mr-2 <?= $hubIsNutrition ? 'text-green-400' : 'text-accent' ?>"></i><?= $hubIsNutrition ? 'Select Barangay' : 'All Barangays' ?>
          </h2>
          <span id="hubVisibleCount" class="inline-flex min-w-[2rem] items-center justify-center rounded-full border <?= $hubIsNutrition ? 'border-green-700/35 bg-green-700/10 text-white' : 'border-teal-400/40 bg-teal-400/15 text-teal-400' ?> px-2.5 py-0.5 text-xs font-extrabold">
            <?= number_format($barangayCount) ?>
          </span>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="relative min-w-0 sm:min-w-[280px]">
            <i class="fas fa-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm <?= $hubIsNutrition ? 'text-white/40' : 'text-white/40' ?>"></i>
            <input type="search"
              id="hubBarangaySearch"
              class="w-full rounded-full border py-2.5 pl-10 pr-4 text-sm outline-none transition <?= $hubIsNutrition
                ? 'border-white/10 bg-white/[0.06] text-white placeholder-white/40 focus:border-brand/60 focus:bg-white/[0.08] focus:ring-2 focus:ring-brand/20'
                : 'border-white/10 bg-white/[0.06] text-white placeholder-white/40 focus:border-brand/60 focus:bg-white/[0.08] focus:ring-2 focus:ring-brand/20' ?>"
              placeholder="<?= $hubIsNutrition ? 'Search barangay for nutrition…' : 'Search barangay, zone, or district…' ?>"
              autocomplete="off">
          </div>
          <?php if ($hubShowManagement && !$hubIsNutrition) : ?>
          <button type="button"
            id="openCreateBarangayModal"
            class="inline-flex items-center justify-center gap-2 rounded-full border border-teal-400/50 bg-teal-400/15 px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-teal-400 transition hover:bg-teal-400 hover:text-white">
            <i class="fas fa-plus"></i> Add Barangay
          </button>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($hubIsNutrition) : ?>
      <div class="hub-nutrition-filters mb-5 flex flex-wrap items-center gap-2" id="hubNutritionFilters" role="toolbar" aria-label="Nutrition barangay filters">
        <button type="button" class="hub-nutrition-filter is-active" data-nutrition-filter="all">All</button>
        <button type="button" class="hub-nutrition-filter" data-nutrition-filter="surveys">With Surveys</button>
        <button type="button" class="hub-nutrition-filter" data-nutrition-filter="pending">Has Pending</button>
        <button type="button" class="hub-nutrition-filter" data-nutrition-filter="at_risk">At-Risk</button>
        <button type="button" class="hub-nutrition-filter" data-nutrition-filter="teenage">Teenage Pregnant</button>
        <button type="button" class="hub-nutrition-filter" data-nutrition-filter="no_survey">No Surveys Yet</button>
      </div>
      <?php endif; ?>

      <div id="hubBarangayGrid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 <?= ($hubSimplifiedCards || $hubIsNutrition) ? 'xl:grid-cols-4' : 'xl:grid-cols-5' ?>">
        <?php foreach ($barangays as $row) :
          $logo = barangay_logo_url($row, '../');
          $nutritionStats = $hubIsNutrition ? ($nutritionPickerById[(string) $row['id']] ?? []) : [];
          $residents = $hubIsNutrition
              ? (int) ($nutritionStats['assessed'] ?? 0)
              : barangay_count_residents($con, $row['id']);
          $isActive = ($activeId === $row['id']);
          $adminUsername = $hubAdminAccounts[$row['id']] ?? '';
          $secretaryUsername = $hubSecretaryAccounts[$row['id']] ?? '';
          $searchKey = strtolower(trim($row['barangay'] . ' ' . $row['zone'] . ' ' . $row['district']));
          $cardBorder = $isActive
            ? ($hubIsNutrition
                ? 'border-green-400 shadow-[0_0_0_1px_#4ade80,0_12px_32px_rgba(34,197,94,0.22)]'
                : 'border-teal-400 shadow-[0_0_0_1px_#14b8a6,0_12px_32px_rgba(20,184,166,0.22)]')
            : ($hubIsNutrition
                ? 'border-white/10 hover:border-green-400/45 hover:bg-green-500/10 hover:shadow-card'
                : 'border-white/10 hover:border-brand/45 hover:bg-brand/10 hover:shadow-card');
          $zoneLabel = trim((string) ($row['zone'] ?? ''));
          if (strcasecmp($zoneLabel, 'PUROK') === 0) {
              $zoneLabel = '';
          }
          $metaLabel = trim($zoneLabel . ($zoneLabel !== '' ? ' · ' : '') . ($row['district'] ?? ''));
          $nChildren = (int) ($nutritionStats['children'] ?? 0);
          $nAssessed = (int) ($nutritionStats['assessed'] ?? 0);
          $nPending = (int) ($nutritionStats['pending'] ?? 0);
          $nAtRisk = (int) ($nutritionStats['at_risk'] ?? 0);
          $nSurveys = (int) ($nutritionStats['surveys'] ?? 0);
          $nTeenage = (int) ($nutritionStats['teenage_pregnant'] ?? 0);
          $nCoverage = $nChildren > 0 ? (int) round(($nAssessed / $nChildren) * 100) : 0;
          $bnsUsername = (string) ($nutritionStats['bns_username'] ?? '');
        ?>
        <div class="hub-card-wrap flex"
          data-search="<?= barangay_h($searchKey) ?>"
          data-surveys="<?= $nSurveys ?>"
          data-pending="<?= $nPending ?>"
          data-at-risk="<?= $nAtRisk ?>"
          data-teenage="<?= $nTeenage ?>">
          <div class="hub-card relative flex w-full flex-col items-center rounded-2xl border-2 bg-white/[0.04] px-4 pb-5 pt-6 text-center text-white transition duration-200 hover:-translate-y-1 <?= $hubSimplifiedCards ? 'hub-card--picker' : '' ?><?= $hubIsNutrition ? ' hub-card--nutrition' : '' ?> <?= barangay_h($cardBorder) ?>">
            <?php if ($hubShowManagement && !$hubIsNutrition) : ?>
            <div class="absolute left-2.5 top-2.5 flex flex-col gap-1.5">
              <button type="button"
                class="js-barangay-account flex h-8 w-8 items-center justify-center rounded-full border text-xs transition hover:scale-105 <?= $adminUsername !== '' ? 'border-teal-400/40 bg-teal-500/15 text-teal-300 hover:bg-teal-500 hover:text-white' : 'border-amber-400/40 bg-amber-500/15 text-amber-300 hover:bg-amber-500 hover:text-white' ?>"
                data-barangay-id="<?= barangay_h($row['id']) ?>"
                data-barangay-name="<?= barangay_h($row['barangay']) ?>"
                data-admin-username="<?= barangay_h($adminUsername) ?>"
                title="<?= $adminUsername !== '' ? 'Admin: ' . barangay_h($adminUsername) : 'Create admin account' ?>">
                <i class="fas fa-user-shield"></i>
              </button>
              <button type="button"
                class="js-barangay-secretary flex h-8 w-8 items-center justify-center rounded-full border text-xs transition hover:scale-105 <?= $secretaryUsername !== '' ? 'border-violet-400/40 bg-violet-500/15 text-violet-300 hover:bg-violet-500 hover:text-white' : 'border-amber-400/40 bg-amber-500/15 text-amber-300 hover:bg-amber-500 hover:text-white' ?>"
                data-barangay-id="<?= barangay_h($row['id']) ?>"
                data-barangay-name="<?= barangay_h($row['barangay']) ?>"
                data-secretary-username="<?= barangay_h($secretaryUsername) ?>"
                title="<?= $secretaryUsername !== '' ? 'Secretary: ' . barangay_h($secretaryUsername) : 'Create secretary account' ?>">
                <i class="fas fa-user-tie"></i>
              </button>
            </div>
            <button type="button"
              class="js-delete-barangay absolute right-2.5 top-2.5 flex h-8 w-8 items-center justify-center rounded-full border border-red-400/40 bg-red-500/15 text-xs text-red-300 transition hover:scale-105 hover:bg-red-500 hover:text-white"
              data-barangay-id="<?= barangay_h($row['id']) ?>"
              data-barangay-name="<?= barangay_h($row['barangay']) ?>"
              title="Delete barangay">
              <i class="fas fa-trash-alt"></i>
            </button>
            <?php elseif ($hubIsNutrition && $nAtRisk > 0) : ?>
            <span class="hub-nutrition-risk-badge absolute right-2.5 top-2.5" title="<?= number_format($nAtRisk) ?> at-risk cases">
              <i class="fas fa-exclamation-triangle"></i> <?= number_format($nAtRisk) ?>
            </span>
            <?php endif; ?>

            <div class="relative mb-4 inline-block">
              <img src="<?= barangay_h($logo) ?>"
                alt="<?= barangay_h($row['barangay']) ?> logo"
                class="hub-card-logo h-20 w-20 rounded-full border-[3px] border-white/20 object-cover sm:h-[92px] sm:w-[92px]"
                id="hub-logo-<?= barangay_h($row['id']) ?>"
                data-barangay-id="<?= barangay_h($row['id']) ?>"
                onerror="this.onerror=null;this.src='<?= barangay_h($hubDefaultLogo) ?>';">
              <?php if ($hubShowManagement && !$hubIsNutrition) : ?>
              <button type="button"
                class="js-change-logo absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full border-2 border-black bg-gradient-to-b from-accent to-brand text-xs text-white shadow-md transition hover:scale-110"
                data-barangay-id="<?= barangay_h($row['id']) ?>"
                data-barangay-name="<?= barangay_h($row['barangay']) ?>"
                data-logo-url="<?= barangay_h($logo) ?>"
                title="Change logo">
                <i class="fas fa-camera"></i>
              </button>
              <?php endif; ?>
            </div>

            <p class="hub-card-name mb-0.5 text-sm font-extrabold leading-snug text-white sm:text-base"><?= barangay_h($row['barangay']) ?></p>
            <p class="mb-3 text-[0.7rem] leading-relaxed text-white/50"><?= barangay_h($metaLabel !== '' ? $metaLabel : ($row['district'] ?? '')) ?></p>

            <?php if ($hubIsNutrition) : ?>
            <div class="hub-nutrition-card-stats mb-3 w-full">
              <div class="hub-nutrition-card-stat"><span><?= number_format($nChildren) ?></span><small>Children</small></div>
              <div class="hub-nutrition-card-stat"><span><?= number_format($nAssessed) ?></span><small>Assessed</small></div>
              <div class="hub-nutrition-card-stat"><span><?= number_format($nSurveys) ?></span><small>Surveys</small></div>
              <div class="hub-nutrition-card-stat"><span><?= number_format($nTeenage) ?></span><small>Teen Preg</small></div>
            </div>
            <div class="hub-nutrition-coverage mb-3 w-full" title="<?= number_format($nCoverage) ?>% assessment coverage">
              <div class="hub-nutrition-coverage-meta">
                <span>Coverage</span>
                <strong><?= number_format($nCoverage) ?>%</strong>
              </div>
              <div class="hub-nutrition-coverage-track">
                <div class="hub-nutrition-coverage-fill" style="width: <?= max(0, min(100, $nCoverage)) ?>%"></div>
              </div>
            </div>
            <?php if ($bnsUsername !== '') : ?>
            <span class="mb-3 inline-block max-w-full truncate rounded-full border border-green-400/25 bg-green-400/10 px-3 py-1 text-[0.6rem] font-semibold text-green-300">
              <i class="fas fa-user-nurse mr-1"></i><?= barangay_h($bnsUsername) ?>
            </span>
            <?php else : ?>
            <span class="mb-3 inline-block rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[0.6rem] font-semibold text-white/45">
              <i class="fas fa-user-nurse mr-1"></i>No BNS account
            </span>
            <?php endif; ?>
            <?php else : ?>
            <span class="mb-3 inline-block rounded-full bg-white/10 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-white/80">
              <i class="fas fa-users mr-1"></i><?= number_format($residents) ?> residents
            </span>
            <?php if ($hubShowManagement) : ?>
            <?php if ($adminUsername !== '') : ?>
            <span class="js-barangay-account-label hub-account-chip mb-1 inline-block max-w-full truncate rounded-full border border-teal-400/25 bg-teal-400/10 px-3 py-1 text-[0.6rem] font-semibold text-teal-300" data-barangay-id="<?= barangay_h($row['id']) ?>">
              <i class="fas fa-user-shield mr-1"></i><?= barangay_h($adminUsername) ?>
            </span>
            <?php else : ?>
            <span class="js-barangay-account-label hub-account-chip mb-1 inline-block rounded-full border border-amber-400/25 bg-amber-400/10 px-3 py-1 text-[0.6rem] font-semibold text-amber-300" data-barangay-id="<?= barangay_h($row['id']) ?>">
              <i class="fas fa-exclamation-circle mr-1"></i>No admin account
            </span>
            <?php endif; ?>
            <?php if ($secretaryUsername !== '') : ?>
            <span class="js-barangay-secretary-label hub-account-chip mb-3 inline-block max-w-full truncate rounded-full border border-violet-400/25 bg-violet-400/10 px-3 py-1 text-[0.6rem] font-semibold text-violet-300" data-barangay-id="<?= barangay_h($row['id']) ?>">
              <i class="fas fa-user-tie mr-1"></i><?= barangay_h($secretaryUsername) ?>
            </span>
            <?php else : ?>
            <span class="js-barangay-secretary-label hub-account-chip mb-3 inline-block rounded-full border border-amber-400/25 bg-amber-400/10 px-3 py-1 text-[0.6rem] font-semibold text-amber-300" data-barangay-id="<?= barangay_h($row['id']) ?>">
              <i class="fas fa-exclamation-circle mr-1"></i>No secretary account
            </span>
            <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="selectBarangay.php" class="js-open-barangay-form mt-auto w-full">
              <?= csrf_field(); ?>
              <input type="hidden" name="barangay_id" value="<?= barangay_h($row['id']) ?>">
              <input type="hidden" name="redirect" value="<?= barangay_h($hubOpenRedirect) ?>">
              <button type="submit"
                class="hub-open-btn js-open-barangay w-full rounded-full border py-2.5 text-[0.7rem] font-bold uppercase tracking-wider transition<?= $hubIsNutrition ? ' hub-open-btn--nutrition' : '' ?>">
                <?php if ($hubIsNutrition) : ?>
                  <?= $hubSimplifiedCards ? 'Open Nutrition' : 'Open Nutrition Dashboard' ?>
                <?php else : ?>
                  <?= $hubSimplifiedCards ? 'Select Barangay' : 'Open Dashboard' ?>
                <?php endif; ?>
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>

        <div id="hubBarangayEmpty" class="col-span-full hidden py-16 text-center text-white/50">
          <i class="fas fa-search mb-4 block text-4xl opacity-30"></i>
          <p class="text-base"><?= $hubIsNutrition ? 'No barangay matches this nutrition filter.' : 'No barangay matches your search.' ?></p>
        </div>
      </div>
    </section>
  </div>

  <!-- Modals (Tailwind) -->
  <div id="logoBarangayModal" class="hub-modal hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="logoBarangayModalTitle">
    <div class="hub-modal-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" data-hub-modal-close="logoBarangayModal"></div>
    <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-[#0a0a0a] shadow-2xl">
      <form id="logoBarangayForm" enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <input type="hidden" name="barangay_id" id="logoBarangayId" value="">
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
          <h5 id="logoBarangayModalTitle" class="text-base font-bold text-white">
            <i class="fas fa-camera mr-1.5 text-accent"></i> Change Logo
          </h5>
          <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-white/60 transition hover:bg-white/10 hover:text-white" data-hub-modal-close="logoBarangayModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="px-5 py-6 text-center">
          <p class="mb-4 text-sm text-white/60" id="logoBarangayName"></p>
          <img src="" alt="" id="logoBarangayPreview" class="mx-auto mb-5 h-28 w-28 rounded-full border-4 border-brand/30 object-cover shadow-lg">
          <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-full border border-accent/50 bg-accent/15 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-accent/30">
            <i class="fas fa-upload"></i> Choose Image
            <input type="file" name="logo" id="logoBarangayFile" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" required>
          </label>
          <p class="mt-3 text-xs text-white/40">JPG, PNG, GIF, or WebP · max 5 MB</p>
        </div>
        <div class="flex justify-end gap-3 border-t border-white/10 px-5 py-4">
          <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/10" data-hub-modal-close="logoBarangayModal">Cancel</button>
          <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2 text-sm font-bold text-white transition hover:bg-brand-dark">
            <i class="fas fa-save"></i> Save Logo
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="barangayAccountModal" class="hub-modal hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="barangayAccountModalTitle">
    <div class="hub-modal-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" data-hub-modal-close="barangayAccountModal"></div>
    <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-[#0a0a0a] shadow-2xl">
      <form id="barangayAccountForm">
        <?= csrf_field(); ?>
        <input type="hidden" name="barangay_id" id="accountBarangayId" value="">
        <input type="hidden" name="reset" id="accountResetFlag" value="0">
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
          <h5 id="barangayAccountModalTitle" class="text-base font-bold text-white">
            <i class="fas fa-user-shield mr-1.5 text-teal-400"></i> Barangay Admin Account
          </h5>
          <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-white/60 transition hover:bg-white/10 hover:text-white" data-hub-modal-close="barangayAccountModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="space-y-4 px-5 py-5">
          <p class="text-sm text-white/60" id="accountBarangayName"></p>
          <div id="accountExistingWrap" class="hidden rounded-lg border border-white/10 bg-black/20 px-3 py-3 text-sm">
            <div class="text-white/50">Current username</div>
            <code id="accountExistingUsername" class="mt-1 block font-mono text-teal-300"></code>
          </div>
          <div id="accountPreviewWrap" class="rounded-lg border border-white/10 bg-black/20 px-3 py-3 text-sm">
            <div class="text-white/50">Username preview</div>
            <code id="accountUsernamePreview" class="mt-1 block font-mono text-teal-300"></code>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="accountPassword">Password</label>
            <div class="relative">
              <input type="password" id="accountPassword" name="password" class="hub-input pr-10" placeholder="Strong password (required)" minlength="8" autocomplete="new-password">
              <button type="button" id="accountTogglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/40 transition hover:text-white/70" aria-label="Show password">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <p class="hub-field-error hidden" data-error-for="password"></p>
            <p class="mt-1.5 text-xs text-white/40">Minimum 6 characters. Used for the barangay admin portal login.</p>
          </div>
        </div>
        <div class="flex flex-col-reverse gap-3 border-t border-white/10 px-5 py-4 sm:flex-row sm:justify-end">
          <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/10" data-hub-modal-close="barangayAccountModal">Cancel</button>
          <button type="button" id="accountResetBtn" class="hidden rounded-full border border-amber-400/40 bg-amber-500/15 px-4 py-2 text-sm font-bold text-amber-300 transition hover:bg-amber-500 hover:text-white">
            <i class="fas fa-key mr-1"></i> Reset Password
          </button>
          <button type="submit" id="accountSubmitBtn" class="inline-flex items-center gap-2 rounded-full bg-teal-500 px-5 py-2 text-sm font-bold text-white transition hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-60">
            <i class="fas fa-user-plus" id="accountSubmitIcon"></i>
            <span id="accountSubmitText">Create Account</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="barangaySecretaryAccountModal" class="hub-modal hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="barangaySecretaryAccountModalTitle">
    <div class="hub-modal-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" data-hub-modal-close="barangaySecretaryAccountModal"></div>
    <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-[#0a0a0a] shadow-2xl">
      <form id="barangaySecretaryAccountForm">
        <?= csrf_field(); ?>
        <input type="hidden" name="barangay_id" id="secretaryAccountBarangayId" value="">
        <input type="hidden" name="reset" id="secretaryAccountResetFlag" value="0">
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
          <h5 id="barangaySecretaryAccountModalTitle" class="text-base font-bold text-white">
            <i class="fas fa-user-tie mr-1.5 text-violet-400"></i> Barangay Secretary Account
          </h5>
          <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-white/60 transition hover:bg-white/10 hover:text-white" data-hub-modal-close="barangaySecretaryAccountModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="space-y-4 px-5 py-5">
          <p class="text-sm text-white/60" id="secretaryAccountBarangayName"></p>
          <div id="secretaryAccountExistingWrap" class="hidden rounded-lg border border-white/10 bg-black/20 px-3 py-3 text-sm">
            <div class="text-white/50">Current username</div>
            <code id="secretaryAccountExistingUsername" class="mt-1 block font-mono text-violet-300"></code>
          </div>
          <div id="secretaryAccountPreviewWrap" class="rounded-lg border border-white/10 bg-black/20 px-3 py-3 text-sm">
            <div class="text-white/50">Username preview</div>
            <code id="secretaryAccountUsernamePreview" class="mt-1 block font-mono text-violet-300"></code>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="secretaryAccountPassword">Password</label>
            <div class="relative">
              <input type="password" id="secretaryAccountPassword" name="password" class="hub-input pr-10" placeholder="Strong password (required)" minlength="8" autocomplete="new-password">
              <button type="button" id="secretaryAccountTogglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/40 transition hover:text-white/70" aria-label="Show password">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <p class="hub-field-error hidden" data-error-for="password"></p>
            <p class="mt-1.5 text-xs text-white/40">Minimum 6 characters. Used for the barangay secretary portal login.</p>
          </div>
        </div>
        <div class="flex flex-col-reverse gap-3 border-t border-white/10 px-5 py-4 sm:flex-row sm:justify-end">
          <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/10" data-hub-modal-close="barangaySecretaryAccountModal">Cancel</button>
          <button type="button" id="secretaryAccountResetBtn" class="hidden rounded-full border border-amber-400/40 bg-amber-500/15 px-4 py-2 text-sm font-bold text-amber-300 transition hover:bg-amber-500 hover:text-white">
            <i class="fas fa-key mr-1"></i> Reset Password
          </button>
          <button type="submit" id="secretaryAccountSubmitBtn" class="inline-flex items-center gap-2 rounded-full bg-violet-500 px-5 py-2 text-sm font-bold text-white transition hover:bg-violet-600 disabled:cursor-not-allowed disabled:opacity-60">
            <i class="fas fa-user-plus" id="secretaryAccountSubmitIcon"></i>
            <span id="secretaryAccountSubmitText">Create Account</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="createBarangayModal" class="hub-modal hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="createBarangayModalTitle">
    <div class="hub-modal-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" data-hub-modal-close="createBarangayModal"></div>
    <div class="relative z-10 max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-white/10 bg-[#0a0a0a] shadow-2xl">
      <form id="createBarangayForm" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
          <div>
            <h5 id="createBarangayModalTitle" class="text-base font-bold text-white">
              <i class="fas fa-plus-circle mr-1.5 text-teal-400"></i> Create New Barangay
            </h5>
            <p class="mt-1 text-xs text-white/50">Register a barangay portal for Valencia City.</p>
          </div>
          <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white/60 transition hover:bg-white/10 hover:text-white" data-hub-modal-close="createBarangayModal" aria-label="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="space-y-6 px-5 py-5">
          <section>
            <h6 class="mb-3 text-[0.65rem] font-extrabold uppercase tracking-[0.14em] text-teal-400/90">
              <i class="fas fa-map-marker-alt mr-1.5"></i>Barangay Details
            </h6>
            <div class="space-y-4">
              <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="createBarangayName">Barangay Name</label>
                <input type="text" id="createBarangayName" name="barangay" class="hub-input" placeholder="e.g. Bagontaas" maxlength="120" required autocomplete="off">
                <p class="hub-field-error hidden" data-error-for="barangay"></p>
                <p class="mt-1.5 text-xs text-white/40">Official barangay name as it appears on records and certificates.</p>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="createBarangayZone">Default Purok Label</label>
                  <input type="text" id="createBarangayZone" name="zone" class="hub-input" value="PUROK" placeholder="e.g. PUROK 1" required>
                  <p class="hub-field-error hidden" data-error-for="zone"></p>
                </div>
                <div>
                  <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="createBarangayDistrict">District</label>
                  <input type="text" id="createBarangayDistrict" name="district" class="hub-input" value="<?= barangay_h($hubDefaultDistrict) ?>" placeholder="e.g. Valencia City" required>
                  <p class="hub-field-error hidden" data-error-for="district"></p>
                </div>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="createBarangayAddress">City / Address</label>
                <input type="text" id="createBarangayAddress" name="address" class="hub-input" value="<?= barangay_h($hubDefaultAddress) ?>" placeholder="e.g. Valencia City, Bukidnon" required>
                <p class="hub-field-error hidden" data-error-for="address"></p>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="createBarangayPostal">Postal Address</label>
                <input type="text" id="createBarangayPostal" name="postal_address" class="hub-input" value="<?= barangay_h($hubDefaultAddress) ?>" placeholder="Full mailing address for certificates" required>
                <p class="hub-field-error hidden" data-error-for="postal_address"></p>
              </div>
            </div>
          </section>

          <section>
            <h6 class="mb-3 text-[0.65rem] font-extrabold uppercase tracking-[0.14em] text-accent/90">
              <i class="fas fa-image mr-1.5"></i>Branding
            </h6>
            <div class="flex flex-col items-center gap-4 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-5 sm:flex-row sm:items-start">
              <img src="<?= barangay_h($hubDefaultLogo) ?>" alt="" id="createBarangayLogoPreview" class="h-24 w-24 shrink-0 rounded-full border-4 border-brand/30 object-cover shadow-lg">
              <div class="min-w-0 flex-1 text-center sm:text-left">
                <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-full border border-accent/50 bg-accent/15 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-accent/30">
                  <i class="fas fa-upload"></i> Choose Logo
                  <input type="file" name="logo" id="createBarangayLogoFile" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                </label>
                <p class="mt-2 text-xs text-white/40">JPG, PNG, GIF, or WebP · max 5 MB · optional</p>
                <p class="hub-field-error hidden" data-error-for="logo"></p>
              </div>
            </div>
          </section>

          <section>
            <h6 class="mb-3 text-[0.65rem] font-extrabold uppercase tracking-[0.14em] text-violet-400/90">
              <i class="fas fa-user-shield mr-1.5"></i>Portal Admin Account
            </h6>
            <div class="space-y-4 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-4">
              <div class="rounded-lg border border-white/10 bg-black/20 px-3 py-3 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <span class="text-white/50">Username</span>
                  <code id="createBarangayAdminUserPreview" class="rounded-md bg-white/10 px-2 py-1 font-mono text-xs text-teal-300">barangay.admin</code>
                </div>
                <p class="mt-2 text-xs text-white/40">Auto-generated from the barangay name. Used to log in to this barangay's admin portal.</p>
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-white/60" for="createBarangayAdminPassword">Admin Password</label>
                <div class="relative">
                  <input type="password" id="createBarangayAdminPassword" name="admin_password" class="hub-input pr-10" placeholder="Leave blank to auto-generate a strong password" minlength="8" autocomplete="new-password">
                  <button type="button" id="createBarangayTogglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/40 transition hover:text-white/70" aria-label="Show password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <p class="hub-field-error hidden" data-error-for="admin_password"></p>
                <p class="mt-1.5 text-xs text-white/40">Minimum 6 characters. Share these credentials with the barangay admin after creation.</p>
              </div>
            </div>
          </section>
        </div>
        <div class="flex flex-col-reverse gap-3 border-t border-white/10 px-5 py-4 sm:flex-row sm:justify-end">
          <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/10" data-hub-modal-close="createBarangayModal">Cancel</button>
          <button type="submit" id="createBarangaySubmit" class="inline-flex items-center justify-center gap-2 rounded-full bg-teal-500 px-5 py-2 text-sm font-bold text-white transition hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-60">
            <i class="fas fa-save" id="createBarangaySubmitIcon"></i>
            <span id="createBarangaySubmitText">Create &amp; Open</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <style>
    .hub-input {
      width: 100%;
      border-radius: 0.75rem;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(255, 255, 255, 0.06);
      padding: 0.625rem 0.875rem;
      font-size: 0.875rem;
      color: #fff;
      outline: none;
      transition: border-color 0.15s ease, background 0.15s ease;
    }
    .hub-input:focus {
      border-color: rgba(20, 184, 166, 0.65);
      background: rgba(255, 255, 255, 0.08);
      box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.18);
    }
    .hub-input.hub-input-invalid {
      border-color: rgba(248, 113, 113, 0.7);
      box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.12);
    }
    .hub-field-error {
      margin-top: 0.375rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #fca5a5;
    }
    .hub-field-error:not(.hidden) {
      display: block;
    }
    .hub-modal:not(.hidden) {
      display: flex;
    }
    .hub-stat-card--clickable:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
    }
    .hub-stat-card--clickable:focus-visible {
      outline: 2px solid rgba(45, 212, 191, 0.8);
      outline-offset: 2px;
    }
  </style>

  <script src="../assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
  <script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
  <script src="../assets/js/barangay-ui.js"></script>
  <script>
  var logoBarangayTargetId = null;
  var hubDefaultLogoUrl = <?= json_encode($hubDefaultLogo, JSON_UNESCAPED_SLASHES) ?>;
  var hubExistingBarangayNames = <?= json_encode(array_values(array_map('strtolower', $hubExistingNames)), JSON_UNESCAPED_UNICODE) ?>;
  var hubDefaultDistrict = <?= json_encode($hubDefaultDistrict, JSON_UNESCAPED_UNICODE) ?>;
  var hubDefaultAddress = <?= json_encode($hubDefaultAddress, JSON_UNESCAPED_UNICODE) ?>;
  var hubOpenRedirect = <?= json_encode($hubOpenRedirect, JSON_UNESCAPED_SLASHES) ?>;

  function hubSlugAdminUsername(name) {
    var slug = String(name || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
    return (slug || 'barangay') + '.admin';
  }

  function hubSlugSecretaryUsername(name) {
    var slug = String(name || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
    return (slug || 'barangay') + '.secretary';
  }

  function hubClearCreateBarangayErrors() {
    $('#createBarangayForm .hub-field-error').addClass('hidden').text('');
    $('#createBarangayForm .hub-input').removeClass('hub-input-invalid');
  }

  function hubShowCreateBarangayErrors(fields, message) {
    hubClearCreateBarangayErrors();
    if (fields && typeof fields === 'object') {
      Object.keys(fields).forEach(function (key) {
        var $error = $('#createBarangayForm [data-error-for="' + key + '"]');
        var $input = $('#createBarangayForm [name="' + key + '"]');
        if ($error.length) {
          $error.text(fields[key]).removeClass('hidden');
        }
        if ($input.length) {
          $input.addClass('hub-input-invalid');
        }
      });
    }
    if (message) {
      Swal.fire({ title: 'Cannot create barangay', text: message, icon: 'error', confirmButtonColor: '#6610f2' });
    }
  }

  function hubResetCreateBarangayForm() {
    var $form = $('#createBarangayForm');
    $form[0].reset();
    hubClearCreateBarangayErrors();
    $('#createBarangayZone').val('PUROK');
    $('#createBarangayDistrict').val(hubDefaultDistrict);
    $('#createBarangayAddress').val(hubDefaultAddress);
    $('#createBarangayPostal').val(hubDefaultAddress);
    $('#createBarangayLogoPreview').attr('src', hubDefaultLogoUrl);
    $('#createBarangayAdminUserPreview').text('barangay.admin');
    $('#createBarangayAdminPassword').attr('type', 'password');
    $('#createBarangayTogglePassword i').removeClass('fa-eye-slash').addClass('fa-eye');
    $('#createBarangaySubmit').prop('disabled', false);
    $('#createBarangaySubmitIcon').removeClass('fa-spinner fa-spin').addClass('fa-save');
    $('#createBarangaySubmitText').text('Create & Open');
  }

  function hubValidateCreateBarangayForm() {
    hubClearCreateBarangayErrors();
    var fields = {};
    var name = ($('#createBarangayName').val() || '').trim();
    var zone = ($('#createBarangayZone').val() || '').trim();
    var district = ($('#createBarangayDistrict').val() || '').trim();
    var address = ($('#createBarangayAddress').val() || '').trim();
    var postal = ($('#createBarangayPostal').val() || '').trim();
    var password = ($('#createBarangayAdminPassword').val() || '').trim();

    if (!name) {
      fields.barangay = 'Barangay name is required.';
    } else if (name.length < 2) {
      fields.barangay = 'Barangay name must be at least 2 characters.';
    } else if (hubExistingBarangayNames.indexOf(name.toLowerCase()) !== -1) {
      fields.barangay = 'This barangay name is already registered.';
    }
    if (!zone) { fields.zone = 'Default purok label is required.'; }
    if (!district) { fields.district = 'District is required.'; }
    if (!address) { fields.address = 'City / address is required.'; }
    if (!postal) { fields.postal_address = 'Postal address is required.'; }
    if (password && password.length < 6) {
      fields.admin_password = 'Admin password must be at least 6 characters.';
    }

    if (Object.keys(fields).length) {
      hubShowCreateBarangayErrors(fields);
      var firstKey = Object.keys(fields)[0];
      var $first = $('#createBarangayForm [name="' + firstKey + '"]');
      if ($first.length) { $first.trigger('focus'); }
      return false;
    }
    return true;
  }

  function hubOpenModal(modalId) {
    var $modal = $('#' + modalId);
    $modal.removeClass('hidden');
    $('body').addClass('overflow-hidden');
    if (modalId === 'createBarangayModal') {
      hubResetCreateBarangayForm();
    }
    $modal.find('input[type="text"], input:not([type="hidden"])').filter(':visible').first().trigger('focus');
  }

  function hubCloseModal(modalId) {
    $('#' + modalId).addClass('hidden');
    if ($('.hub-modal:not(.hidden)').length === 0) {
      $('body').removeClass('overflow-hidden');
    }
  }

  $(document).on('click', '[data-hub-modal-close]', function () {
    hubCloseModal($(this).data('hub-modal-close'));
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      $('.hub-modal:not(.hidden)').each(function () {
        hubCloseModal(this.id);
      });
    }
  });

  $('#openCreateBarangayModal').on('click', function () {
    hubOpenModal('createBarangayModal');
  });

  if (window.location.hash === '#create') {
    hubOpenModal('createBarangayModal');
  }

  $('#createBarangayName').on('input', function () {
    $('#createBarangayAdminUserPreview').text(hubSlugAdminUsername($(this).val()));
    $('[data-error-for="barangay"]').addClass('hidden');
    $(this).removeClass('hub-input-invalid');
  });

  $('#createBarangayForm .hub-input').not('#createBarangayName').on('input', function () {
    var name = $(this).attr('name');
    if (name) {
      $('[data-error-for="' + name + '"]').addClass('hidden');
      $(this).removeClass('hub-input-invalid');
    }
  });

  $('#createBarangayLogoFile').on('change', function () {
    var file = this.files && this.files[0];
    $('[data-error-for="logo"]').addClass('hidden');
    if (!file) {
      $('#createBarangayLogoPreview').attr('src', hubDefaultLogoUrl);
      return;
    }
    if (file.size > 5242880) {
      hubShowCreateBarangayErrors({ logo: 'Logo must be 5 MB or smaller.' });
      this.value = '';
      $('#createBarangayLogoPreview').attr('src', hubDefaultLogoUrl);
      return;
    }
    var reader = new FileReader();
    reader.onload = function (e) {
      $('#createBarangayLogoPreview').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  });

  $('#createBarangayTogglePassword').on('click', function () {
    var $input = $('#createBarangayAdminPassword');
    var $icon = $(this).find('i');
    var show = $input.attr('type') === 'password';
    $input.attr('type', show ? 'text' : 'password');
    $icon.toggleClass('fa-eye fa-eye-slash');
  });

  $('#createBarangayForm').on('submit', function (e) {
    e.preventDefault();
    if (!hubValidateCreateBarangayForm()) { return; }

    if (typeof barangaySyncCsrfForms === 'function') {
      barangaySyncCsrfForms();
    }

    var $submit = $('#createBarangaySubmit');
    $submit.prop('disabled', true);
    $('#createBarangaySubmitIcon').removeClass('fa-save').addClass('fa-spinner fa-spin');
    $('#createBarangaySubmitText').text('Creating…');

    $.ajax({
      url: 'createBarangay.php',
      type: 'POST',
      data: new FormData(this),
      contentType: false,
      processData: false,
      dataType: 'json',
      success: function (res) {
        hubCloseModal('createBarangayModal');
        var adminHtml = '';
        if (res.admin && res.admin.username) {
          adminHtml = '<div class="mt-3 rounded-lg bg-black/20 px-3 py-3 text-left text-sm">' +
            '<div class="mb-1 text-white/50">Barangay admin login</div>' +
            '<div><span class="text-white/60">Username:</span> <code class="text-teal-300">' + $('<div>').text(res.admin.username).html() + '</code></div>' +
            '<div class="mt-1"><span class="text-white/60">Password:</span> <code class="text-teal-300">' + $('<div>').text(res.admin.password || '(auto-generated — copy now)').html() + '</code></div>' +
            '<p class="mt-2 mb-0 text-xs text-white/45">Save these credentials before leaving this page.</p></div>';
        }
        Swal.fire({
          title: 'Barangay created',
          html: '<p><b>' + $('<div>').text(res.barangay || 'New barangay').html() + '</b> is ready.</p>' + adminHtml,
          icon: 'success',
          confirmButtonText: 'Open Dashboard',
          confirmButtonColor: '#20c997',
          showCancelButton: true,
          cancelButtonText: 'Stay in Hub',
          cancelButtonColor: '#6c757d'
        }).then(function (result) {
          if (result.isConfirmed) {
            window.location.href = res.redirect || 'dashboard.php';
            return;
          }
          window.location.reload();
        });
      }
    }).fail(function (xhr) {
      $submit.prop('disabled', false);
      $('#createBarangaySubmitIcon').removeClass('fa-spinner fa-spin').addClass('fa-save');
      $('#createBarangaySubmitText').text('Create & Open');

      var data = null;
      try { data = JSON.parse(xhr.responseText); } catch (err) {}
      if (data && (data.fields || data.error)) {
        hubShowCreateBarangayErrors(data.fields || {}, data.error || 'Could not create barangay.');
        return;
      }
      barangayAjaxError(xhr);
    });
  });

  var accountBarangayTargetId = null;

  function hubOpenAccountModal($trigger) {
    var barangayId = $trigger.data('barangay-id');
    var barangayName = $trigger.data('barangay-name');
    var adminUsername = String($trigger.data('admin-username') || '').trim();
    var hasAccount = adminUsername !== '';

    accountBarangayTargetId = barangayId;
    $('#accountBarangayId').val(barangayId);
    $('#accountResetFlag').val('0');
    $('#accountBarangayName').text(barangayName);
    $('#accountPassword').val('').attr('type', 'password');
    $('#accountTogglePassword i').removeClass('fa-eye-slash').addClass('fa-eye');
    $('#barangayAccountForm [data-error-for="password"]').addClass('hidden');
    $('#accountPassword').removeClass('hub-input-invalid');

    if (hasAccount) {
      $('#accountExistingWrap').removeClass('hidden');
      $('#accountPreviewWrap').addClass('hidden');
      $('#accountExistingUsername').text(adminUsername);
      $('#accountResetBtn').removeClass('hidden');
      $('#accountSubmitBtn').addClass('hidden');
    } else {
      $('#accountExistingWrap').addClass('hidden');
      $('#accountPreviewWrap').removeClass('hidden');
      $('#accountUsernamePreview').text(hubSlugAdminUsername(barangayName));
      $('#accountResetBtn').addClass('hidden');
      $('#accountSubmitBtn').removeClass('hidden');
      $('#accountSubmitIcon').removeClass('fa-spinner fa-spin').addClass('fa-user-plus');
      $('#accountSubmitText').text('Create Account');
      $('#accountSubmitBtn').prop('disabled', false);
    }

    hubOpenModal('barangayAccountModal');
  }

  function hubUpdateAccountCard(barangayId, username) {
    var $btn = $('.js-barangay-account[data-barangay-id="' + barangayId + '"]');
    var $label = $('.js-barangay-account-label[data-barangay-id="' + barangayId + '"]');
    $btn.data('admin-username', username);
    $btn.attr('title', 'Admin: ' + username);
    $btn.removeClass('border-amber-400/40 bg-amber-500/15 text-amber-300 hover:bg-amber-500')
      .addClass('border-teal-400/40 bg-teal-500/15 text-teal-300 hover:bg-teal-500');
    $label.removeClass('border-amber-400/25 bg-amber-400/10 text-amber-300')
      .addClass('border-teal-400/25 bg-teal-400/10 text-teal-300')
      .html('<i class="fas fa-user-shield mr-1"></i>' + $('<div>').text(username).html());
  }

  function hubSubmitBarangayAccount(resetPassword) {
    var password = ($('#accountPassword').val() || '').trim();
    if (password && password.length < 6) {
      $('#barangayAccountForm [data-error-for="password"]').text('Password must be at least 6 characters.').removeClass('hidden');
      $('#accountPassword').addClass('hub-input-invalid').trigger('focus');
      return;
    }

    if (typeof barangaySyncCsrfForms === 'function') {
      barangaySyncCsrfForms();
    }

    $('#accountResetFlag').val(resetPassword ? '1' : '0');
    var $submit = $('#accountSubmitBtn');
    var $reset = $('#accountResetBtn');
    $submit.prop('disabled', true);
    $reset.prop('disabled', true);
    $('#accountSubmitIcon').removeClass('fa-user-plus').addClass('fa-spinner fa-spin');
    $('#accountSubmitText').text(resetPassword ? 'Resetting…' : 'Creating…');

    $.ajax({
      url: 'createBarangayAdmin.php',
      type: 'POST',
      data: $('#barangayAccountForm').serialize(),
      dataType: 'json',
      success: function (res) {
        hubCloseModal('barangayAccountModal');
        var adminHtml = '';
        if (res.admin && res.admin.username) {
          adminHtml = '<div class="mt-3 rounded-lg bg-black/20 px-3 py-3 text-left text-sm">' +
            '<div><span class="text-white/60">Username:</span> <code class="text-teal-300">' + $('<div>').text(res.admin.username).html() + '</code></div>';
          if (res.admin.password) {
            adminHtml += '<div class="mt-1"><span class="text-white/60">Password:</span> <code class="text-teal-300">' + $('<div>').text(res.admin.password).html() + '</code></div>';
          }
          adminHtml += '<p class="mt-2 mb-0 text-xs text-white/45">Save these credentials before leaving this page.</p></div>';
        }
        Swal.fire({
          title: res.created ? 'Account created' : (res.reset ? 'Password reset' : 'Account exists'),
          html: '<p>' + $('<div>').text(res.message || '').html() + '</p>' + adminHtml,
          icon: 'success',
          confirmButtonColor: '#20c997'
        });
        if (res.admin && res.admin.username && accountBarangayTargetId) {
          hubUpdateAccountCard(accountBarangayTargetId, res.admin.username);
        }
      }
    }).fail(function (xhr) {
      $submit.prop('disabled', false);
      $reset.prop('disabled', false);
      $('#accountSubmitIcon').removeClass('fa-spinner fa-spin').addClass('fa-user-plus');
      $('#accountSubmitText').text(resetPassword ? 'Reset Password' : 'Create Account');

      var data = null;
      try { data = JSON.parse(xhr.responseText); } catch (err) {}
      if (data && data.fields && data.fields.password) {
        $('#barangayAccountForm [data-error-for="password"]').text(data.fields.password).removeClass('hidden');
        $('#accountPassword').addClass('hub-input-invalid');
        return;
      }
      if (data && data.error) {
        Swal.fire({ title: 'Account error', text: data.error, icon: 'error', confirmButtonColor: '#6610f2' });
        return;
      }
      barangayAjaxError(xhr);
    });
  }

  $(document).on('click', '.js-barangay-account', function (e) {
    e.preventDefault();
    e.stopPropagation();
    hubOpenAccountModal($(this));
  });

  $('#accountTogglePassword').on('click', function () {
    var $input = $('#accountPassword');
    var $icon = $(this).find('i');
    var show = $input.attr('type') === 'password';
    $input.attr('type', show ? 'text' : 'password');
    $icon.toggleClass('fa-eye fa-eye-slash');
  });

  $('#accountResetBtn').on('click', function () {
    hubSubmitBarangayAccount(true);
  });

  $('#barangayAccountForm').on('submit', function (e) {
    e.preventDefault();
    hubSubmitBarangayAccount(false);
  });

  var secretaryAccountBarangayTargetId = null;

  function hubOpenSecretaryAccountModal($trigger) {
    var barangayId = $trigger.data('barangay-id');
    var barangayName = $trigger.data('barangay-name');
    var secretaryUsername = String($trigger.data('secretary-username') || '').trim();
    var hasAccount = secretaryUsername !== '';

    secretaryAccountBarangayTargetId = barangayId;
    $('#secretaryAccountBarangayId').val(barangayId);
    $('#secretaryAccountResetFlag').val('0');
    $('#secretaryAccountBarangayName').text(barangayName);
    $('#secretaryAccountPassword').val('').attr('type', 'password');
    $('#secretaryAccountTogglePassword i').removeClass('fa-eye-slash').addClass('fa-eye');
    $('#barangaySecretaryAccountForm [data-error-for="password"]').addClass('hidden');
    $('#secretaryAccountPassword').removeClass('hub-input-invalid');

    if (hasAccount) {
      $('#secretaryAccountExistingWrap').removeClass('hidden');
      $('#secretaryAccountPreviewWrap').addClass('hidden');
      $('#secretaryAccountExistingUsername').text(secretaryUsername);
      $('#secretaryAccountResetBtn').removeClass('hidden');
      $('#secretaryAccountSubmitBtn').addClass('hidden');
    } else {
      $('#secretaryAccountExistingWrap').addClass('hidden');
      $('#secretaryAccountPreviewWrap').removeClass('hidden');
      $('#secretaryAccountUsernamePreview').text(hubSlugSecretaryUsername(barangayName));
      $('#secretaryAccountResetBtn').addClass('hidden');
      $('#secretaryAccountSubmitBtn').removeClass('hidden');
      $('#secretaryAccountSubmitIcon').removeClass('fa-spinner fa-spin').addClass('fa-user-plus');
      $('#secretaryAccountSubmitText').text('Create Account');
      $('#secretaryAccountSubmitBtn').prop('disabled', false);
    }

    hubOpenModal('barangaySecretaryAccountModal');
  }

  function hubUpdateSecretaryAccountCard(barangayId, username) {
    var $btn = $('.js-barangay-secretary[data-barangay-id="' + barangayId + '"]');
    var $label = $('.js-barangay-secretary-label[data-barangay-id="' + barangayId + '"]');
    $btn.data('secretary-username', username);
    $btn.attr('title', 'Secretary: ' + username);
    $btn.removeClass('border-amber-400/40 bg-amber-500/15 text-amber-300 hover:bg-amber-500')
      .addClass('border-violet-400/40 bg-violet-500/15 text-violet-300 hover:bg-violet-500');
    $label.removeClass('border-amber-400/25 bg-amber-400/10 text-amber-300')
      .addClass('border-violet-400/25 bg-violet-400/10 text-violet-300')
      .html('<i class="fas fa-user-tie mr-1"></i>' + $('<div>').text(username).html());
  }

  function hubSubmitBarangaySecretaryAccount(resetPassword) {
    var password = ($('#secretaryAccountPassword').val() || '').trim();
    if (password && password.length < 6) {
      $('#barangaySecretaryAccountForm [data-error-for="password"]').text('Password must be at least 6 characters.').removeClass('hidden');
      $('#secretaryAccountPassword').addClass('hub-input-invalid').trigger('focus');
      return;
    }

    if (typeof barangaySyncCsrfForms === 'function') {
      barangaySyncCsrfForms();
    }

    $('#secretaryAccountResetFlag').val(resetPassword ? '1' : '0');
    var $submit = $('#secretaryAccountSubmitBtn');
    var $reset = $('#secretaryAccountResetBtn');
    $submit.prop('disabled', true);
    $reset.prop('disabled', true);
    $('#secretaryAccountSubmitIcon').removeClass('fa-user-plus').addClass('fa-spinner fa-spin');
    $('#secretaryAccountSubmitText').text(resetPassword ? 'Resetting…' : 'Creating…');

    $.ajax({
      url: 'createBarangaySecretary.php',
      type: 'POST',
      data: $('#barangaySecretaryAccountForm').serialize(),
      dataType: 'json',
      success: function (res) {
        hubCloseModal('barangaySecretaryAccountModal');
        var secretaryHtml = '';
        if (res.secretary && res.secretary.username) {
          secretaryHtml = '<div class="mt-3 rounded-lg bg-black/20 px-3 py-3 text-left text-sm">' +
            '<div><span class="text-white/60">Username:</span> <code class="text-violet-300">' + $('<div>').text(res.secretary.username).html() + '</code></div>';
          if (res.secretary.password) {
            secretaryHtml += '<div class="mt-1"><span class="text-white/60">Password:</span> <code class="text-violet-300">' + $('<div>').text(res.secretary.password).html() + '</code></div>';
          }
          secretaryHtml += '<p class="mt-2 mb-0 text-xs text-white/45">Save these credentials before leaving this page.</p></div>';
        }
        Swal.fire({
          title: res.created ? 'Account created' : (res.reset ? 'Password reset' : 'Account exists'),
          html: '<p>' + $('<div>').text(res.message || '').html() + '</p>' + secretaryHtml,
          icon: 'success',
          confirmButtonColor: '#8b5cf6'
        });
        if (res.secretary && res.secretary.username && secretaryAccountBarangayTargetId) {
          hubUpdateSecretaryAccountCard(secretaryAccountBarangayTargetId, res.secretary.username);
        }
      }
    }).fail(function (xhr) {
      $submit.prop('disabled', false);
      $reset.prop('disabled', false);
      $('#secretaryAccountSubmitIcon').removeClass('fa-spinner fa-spin').addClass('fa-user-plus');
      $('#secretaryAccountSubmitText').text(resetPassword ? 'Reset Password' : 'Create Account');

      var data = null;
      try { data = JSON.parse(xhr.responseText); } catch (err) {}
      if (data && data.fields && data.fields.password) {
        $('#barangaySecretaryAccountForm [data-error-for="password"]').text(data.fields.password).removeClass('hidden');
        $('#secretaryAccountPassword').addClass('hub-input-invalid');
        return;
      }
      if (data && data.error) {
        Swal.fire({ title: 'Account error', text: data.error, icon: 'error', confirmButtonColor: '#6610f2' });
        return;
      }
      barangayAjaxError(xhr);
    });
  }

  $(document).on('click', '.js-barangay-secretary', function (e) {
    e.preventDefault();
    e.stopPropagation();
    hubOpenSecretaryAccountModal($(this));
  });

  $('#secretaryAccountTogglePassword').on('click', function () {
    var $input = $('#secretaryAccountPassword');
    var $icon = $(this).find('i');
    var show = $input.attr('type') === 'password';
    $input.attr('type', show ? 'text' : 'password');
    $icon.toggleClass('fa-eye fa-eye-slash');
  });

  $('#secretaryAccountResetBtn').on('click', function () {
    hubSubmitBarangaySecretaryAccount(true);
  });

  $('#barangaySecretaryAccountForm').on('submit', function (e) {
    e.preventDefault();
    hubSubmitBarangaySecretaryAccount(false);
  });

  var hubNutritionFilter = 'all';

  function filterHubBarangays() {
    var query = ($('#hubBarangaySearch').val() || '').toLowerCase().trim();
    var visible = 0;

    $('#hubBarangayGrid .hub-card-wrap').each(function () {
      var $card = $(this);
      var searchOk = !query || String($card.data('search') || '').indexOf(query) !== -1;
      var filterOk = true;
      if (hubNutritionFilter === 'surveys') {
        filterOk = Number($card.attr('data-surveys') || 0) > 0;
      } else if (hubNutritionFilter === 'pending') {
        filterOk = Number($card.attr('data-pending') || 0) > 0;
      } else if (hubNutritionFilter === 'at_risk') {
        filterOk = Number($card.attr('data-at-risk') || 0) > 0;
      } else if (hubNutritionFilter === 'teenage') {
        filterOk = Number($card.attr('data-teenage') || 0) > 0;
      } else if (hubNutritionFilter === 'no_survey') {
        filterOk = Number($card.attr('data-surveys') || 0) === 0;
      }
      var match = searchOk && filterOk;
      $card.toggleClass('hidden', !match);
      if (match) { visible++; }
    });

    $('#hubVisibleCount').text(visible.toLocaleString());
    $('#hubBarangayEmpty').toggleClass('hidden', visible > 0);
  }

  $('#hubBarangaySearch').on('input', filterHubBarangays);

  $(document).on('click', '#hubNutritionFilters .hub-nutrition-filter', function () {
    hubNutritionFilter = String($(this).data('nutrition-filter') || 'all');
    $('#hubNutritionFilters .hub-nutrition-filter').removeClass('is-active');
    $(this).addClass('is-active');
    filterHubBarangays();
  });

  $(document).on('submit', '.js-open-barangay-form', function (e) {
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
        window.location.href = res.redirect || hubOpenRedirect || 'dashboard.php';
      }
    }).fail(function (xhr) {
      var msg = 'Could not open barangay dashboard.';
      try {
        var data = JSON.parse(xhr.responseText);
        if (data.error) { msg = data.error; }
      } catch (err) {
        if (xhr.responseText && xhr.responseText.indexOf('CSRF') !== -1) {
          msg = 'Your session expired. Please refresh the page and try again.';
        }
      }
      Swal.fire({ title: 'Request failed', text: msg, icon: 'error', confirmButtonColor: '#6610f2' });
    });
  });

  $('.js-change-logo').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    logoBarangayTargetId = $(this).data('barangay-id');
    $('#logoBarangayId').val(logoBarangayTargetId);
    $('#logoBarangayName').text($(this).data('barangay-name'));
    $('#logoBarangayPreview').attr('src', $(this).data('logo-url'));
    $('#logoBarangayFile').val('');
    hubOpenModal('logoBarangayModal');
  });

  $('#logoBarangayFile').on('change', function () {
    var file = this.files && this.files[0];
    if (!file) { return; }
    var reader = new FileReader();
    reader.onload = function (e) {
      $('#logoBarangayPreview').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  });

  $('#logoBarangayForm').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
      url: 'updateBarangayLogo.php',
      type: 'POST',
      data: new FormData(this),
      contentType: false,
      processData: false,
      dataType: 'json',
      success: function (res) {
        if (res.logo_url && logoBarangayTargetId) {
          $('#hub-logo-' + logoBarangayTargetId).attr('src', res.logo_url + '?t=' + Date.now());
          $('.js-change-logo[data-barangay-id="' + logoBarangayTargetId + '"]').data('logo-url', res.logo_url);
        }
        hubCloseModal('logoBarangayModal');
        Swal.fire({ title: 'Logo updated', icon: 'success', timer: 1500, showConfirmButton: false });
      }
    }).fail(function (xhr) {
      var msg = 'Could not update logo.';
      try { var data = JSON.parse(xhr.responseText); if (data.error) { msg = data.error; } } catch (err) {}
      Swal.fire({ title: 'Error', text: msg, icon: 'error', confirmButtonColor: '#6610f2' });
    });
  });


  $('.js-delete-barangay').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var barangayId = $(this).data('barangay-id');
    var barangayName = $(this).data('barangay-name');

    Swal.fire({
      title: 'Delete barangay?',
      html: 'Remove <b>' + barangayName + '</b>? This cannot be undone.<br><small class="text-muted">All residents, officials, blotter records, puroks, and related data for this barangay will be permanently deleted.</small>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel'
    }).then(function (result) {
      if (!result.isConfirmed) { return; }
      $.ajax({
        url: 'deleteBarangay.php',
        type: 'POST',
        data: { barangay_id: barangayId, csrf_token: $('meta[name="csrf-token"]').attr('content') },
        dataType: 'json',
        success: function () {
          Swal.fire({ title: 'Deleted', text: barangayName + ' has been removed.', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(function () { window.location.reload(); });
        }
      }).fail(function (xhr) {
        var msg = 'Could not delete barangay.';
        try { var data = JSON.parse(xhr.responseText); if (data.error) { msg = data.error; } } catch (err) {}
        Swal.fire({ title: 'Cannot delete', text: msg, icon: 'error', confirmButtonColor: '#6610f2' });
      });
    });
  });
  </script>
  </div>
</body>
</html>
