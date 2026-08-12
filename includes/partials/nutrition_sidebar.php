<?php

/**
 * Shared sidebar for the Barangay Nutrition Profiling System.
 *
 * Expects: $first_name_user, $last_name_user, $user_type, $user_image, $barangay,
 *          $sidebarLogo, $isSuperAdmin, $isCityAdmin, $isNutritionScholar, $isBnsAdmin,
 *          $staffRoleLabel, $activePage
 */
$nutritionActivePage = $activePage ?? '';
$isNutritionScholar = !empty($isNutritionScholar);
$isBnsAdmin = !empty($isBnsAdmin);
$staffRoleLabel = $staffRoleLabel ?? ($user_type ?? 'admin');
$showNutritionHub = !empty($isSuperAdmin) || !empty($isCityAdmin) || $isBnsAdmin;
// Nutrition Admin (A): view/reports + name edits only — no settings / new surveys.
$showNutritionSettings = !$isNutritionScholar && !$isBnsAdmin;
$showNutritionDataEntry = !$isBnsAdmin;
$isNutritionPortalAdmin = !empty($isNutritionPortalAdmin)
    || (isset($con, $_SESSION['user_id']) && function_exists('barangay_user_is_nutrition_portal_admin')
        && barangay_user_is_nutrition_portal_admin($con, (string) $_SESSION['user_id']));
$hideBarangayAdminSwitch = $isNutritionScholar || $isBnsAdmin || $isNutritionPortalAdmin;
$portalBrandName = isset($con)
    ? barangay_portal_brand_name($con, (string) ($_SESSION['user_id'] ?? ''), true)
    : 'Nutrition Portal';
$userAvatarUrl = barangay_user_avatar_url($user_image ?? '', $user_image_path ?? '', '../');
?>
<aside class="main-sidebar sidebar-dark-success elevation-4 sidebar-no-expand nutrition-sidebar">
  <a href="nutritionDashboard.php" class="brand-link text-center nutrition-brand-link">
    <img src="<?= barangay_h($sidebarLogo) ?>" class="nutrition-brand-logo img-circle elevation-5" alt="<?= barangay_h($barangay) ?>">
    <span class="brand-text font-weight-light d-block mt-2"><?= barangay_h($portalBrandName) ?></span>
    <small class="d-block text-success"><?= barangay_h($barangay) ?></small>
  </a>

  <div class="sidebar">
    <div class="user-panel super-user-panel mt-3 pb-3 mb-3">
      <div class="image">
        <?php if ($userAvatarUrl !== '') : ?>
          <img src="<?= barangay_h($userAvatarUrl) ?>" class="super-user-avatar" alt="User" onerror="this.outerHTML='<span class=&quot;super-user-avatar super-user-avatar--fallback&quot; aria-hidden=&quot;true&quot;><i class=&quot;fas fa-user&quot;></i></span>'">
        <?php else : ?>
          <span class="super-user-avatar super-user-avatar--fallback" aria-hidden="true"><i class="fas fa-user"></i></span>
        <?php endif; ?>
      </div>
      <div class="info">
        <a href="nutritionAccountProfile.php" class="d-block text-bold"><?= barangay_h(ucfirst($first_name_user) . ' ' . ucfirst($last_name_user)) ?></a>
        <small class="text-muted text-uppercase"><?= barangay_h($staffRoleLabel) ?></small>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu">
        <?php if ($showNutritionHub) : ?>
        <li class="nav-item">
          <a href="<?= !empty($isSuperAdmin) || !empty($isBnsAdmin) ? 'nutritionSuperDashboard.php' : 'barangayHub.php?picker=1&amp;system=nutrition' ?>" class="nav-link">
            <i class="nav-icon fas fa-th-large"></i>
            <p>All Barangays</p>
          </a>
        </li>
        <?php endif; ?>

        <li class="nav-header">Overview</li>
        <li class="nav-item">
          <a href="nutritionDashboard.php" class="nav-link <?= $nutritionActivePage === 'dashboard' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <?php if ($showNutritionDataEntry) : ?>
        <li class="nav-header">Data Entry</li>
        <li class="nav-item">
          <a href="nutritionHouseholdSurvey.php" class="nav-link <?= $nutritionActivePage === 'household_survey' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-home"></i>
            <p>Household Survey</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionAssess.php" class="nav-link <?= $nutritionActivePage === 'assess' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-weight"></i>
            <p>New Assessment</p>
          </a>
        </li>
        <?php endif; ?>

        <li class="nav-header">Reports</li>
        <li class="nav-item">
          <a href="nutritionBnpReport.php" class="nav-link <?= $nutritionActivePage === 'bnp_report' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-book"></i>
            <p>BNP Reports 2026</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionBnpReport.php?type=eopt" class="nav-link <?= $nutritionActivePage === 'eopt_report' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-notes-medical"></i>
            <p>e-OPT Plus Tool</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionMellpiBarangayProfile.php" class="nav-link <?= $nutritionActivePage === 'mellpi_barangay_profile' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-check"></i>
            <p>MELLPI PRO Form</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionBarangaySurvey.php" class="nav-link <?= $nutritionActivePage === 'barangay_survey' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-poll"></i>
            <p>Consolidated Report</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionBnpReport.php?type=pregnant" class="nav-link <?= $nutritionActivePage === 'pregnant_report' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-female"></i>
            <p>Families with Pregnant</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionProfiles.php" class="nav-link <?= $nutritionActivePage === 'profiles' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-list"></i>
            <p>Nutrition Profiles</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionReport.php" class="nav-link <?= $nutritionActivePage === 'report' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-file-alt"></i>
            <p>Generate Report</p>
          </a>
        </li>

        <li class="nav-header">Help</li>
        <li class="nav-item">
          <a href="nutritionProcessFormPrint.php" target="_blank" class="nav-link">
            <i class="nav-icon fas fa-clipboard-check"></i>
            <p>Process Form (PDF)</p>
          </a>
        </li>

        <li class="nav-header">Account</li>
        <li class="nav-item">
          <a href="nutritionAccountProfile.php" class="nav-link <?= $nutritionActivePage === 'profile' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-user-circle"></i>
            <p>Account Profile</p>
          </a>
        </li>
        <?php if ($showNutritionSettings) : ?>
        <li class="nav-item">
          <a href="nutritionSettings.php" class="nav-link <?= $nutritionActivePage === 'settings' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-cog"></i>
            <p>Settings</p>
          </a>
        </li>
        <?php endif; ?>

        <li class="nav-header">Switch Portal</li>
        <?php if (!$hideBarangayAdminSwitch) : ?>
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link">
            <i class="nav-icon fas fa-building"></i>
            <p>City of Valencia Portal</p>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a href="../logout.php" class="nav-link">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>Logout</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
