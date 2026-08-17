<?php

/**
 * Sidebar for Super Admin / BNS Admin city-wide nutrition dashboard.
 *
 * Expects: $brandLogo or $sidebarLogo, $first_name_user, $last_name_user, $user_image,
 *          $staffRoleLabel, $activePage (optional), $isSuperAdmin (optional)
 */
$superBrandLogo = $brandLogo ?? $sidebarLogo ?? '../assets/logo/valencia-city.png';
$superActivePage = $activePage ?? '';
$staffRoleLabel = $staffRoleLabel ?? 'Super Admin';
$isSuperAdmin = !empty($isSuperAdmin);
$isNutritionPortalAdmin = !empty($isNutritionPortalAdmin)
    || (isset($con, $_SESSION['user_id']) && function_exists('barangay_user_is_nutrition_portal_admin')
        && barangay_user_is_nutrition_portal_admin($con, (string) $_SESSION['user_id']));
$portalBrandName = isset($con)
    ? barangay_portal_brand_name($con, (string) ($_SESSION['user_id'] ?? ''), true)
    : 'Nutrition Portal';
$portalBrandTagline = isset($con)
    ? barangay_portal_brand_tagline($con, (string) ($_SESSION['user_id'] ?? ''), true)
    : 'Valencia City · Nutrition Profiling';
$isStaffAccountsPage = in_array($superActivePage, ['staff_accounts', 'bns', 'bns_admin', 'cnpc', 'nutrition_sa'], true);
$isSsaActor = isset($con, $_SESSION['user_id']) && function_exists('barangay_user_is_ssa')
    && barangay_user_is_ssa($con, (string) $_SESSION['user_id']);
$activeNutritionBarangayId = function_exists('barangay_session_id') ? barangay_session_id() : null;
$userAvatarUrl = barangay_user_avatar_url($user_image ?? '', $user_image_path ?? '', '../');
?>
<aside class="main-sidebar sidebar-dark-success elevation-4 sidebar-no-expand nutrition-sidebar super-nutrition-sidebar">
  <a href="nutritionSuperDashboard.php" class="brand-link text-center nutrition-brand-link">
    <img src="<?= barangay_h($superBrandLogo) ?>" alt="Valencia City" class="nutrition-brand-logo img-circle elevation-5">
    <span class="brand-text font-weight-light d-block mt-2"><?= barangay_h($portalBrandName) ?></span>
    <small class="d-block text-success"><?= barangay_h($portalBrandTagline) ?><?= $isNutritionPortalAdmin ? ' · Super Admin' : '' ?></small>
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
        <span class="badge badge-success text-uppercase px-2 py-1 mt-1"><?= barangay_h($staffRoleLabel) ?></span>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-header">CITY NUTRITION</li>
        <li class="nav-item">
          <a href="nutritionSuperDashboard.php" class="nav-link <?= $superActivePage === 'nutrition_super_dashboard' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>Super Admin Dashboard</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker" class="nav-link <?= $superActivePage === 'nutrition_picker' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-th-large"></i>
            <p>Select Barangay</p>
          </a>
        </li>
        <?php if ($activeNutritionBarangayId !== null) : ?>
        <li class="nav-item">
          <a href="nutritionDashboard.php" class="nav-link <?= $superActivePage === 'dashboard' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Barangay Dashboard</p>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a href="nutritionMellpiCityProfile.php" class="nav-link <?= $superActivePage === 'mellpi_city_profile' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-list"></i>
            <p>MELLPI City Profile</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionSuperPrintReport.php" target="_blank" class="nav-link">
            <i class="nav-icon fas fa-print"></i>
            <p>Print City Report</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionHubGuidePrint.php" target="_blank" class="nav-link">
            <i class="nav-icon fas fa-file-pdf"></i>
            <p>User Guide (PDF)</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="nutritionProcessFormPrint.php" target="_blank" class="nav-link">
            <i class="nav-icon fas fa-clipboard-check"></i>
            <p>Process Form (PDF)</p>
          </a>
        </li>

        <?php
        $canSwitchToBarangayHub = $isSuperAdmin && !$isNutritionPortalAdmin
            && isset($con, $_SESSION['user_id'])
            && function_exists('barangay_user_is_ssa')
            && barangay_user_is_ssa($con, (string) $_SESSION['user_id']);
        $canManageStaffAccounts = isset($con, $_SESSION['user_id'])
            && function_exists('barangay_user_can_manage_staff_accounts')
            && barangay_user_can_manage_staff_accounts($con, (string) $_SESSION['user_id']);
        ?>
        <?php if ($canSwitchToBarangayHub) : ?>
        <li class="nav-header">PORTALS</li>
        <li class="nav-item">
          <a href="superDashboard.php" class="nav-link">
            <i class="nav-icon fas fa-city"></i>
            <p>Switch to City of Valencia Portal</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="barangayHub.php?picker=1" class="nav-link">
            <i class="nav-icon fas fa-building"></i>
            <p>Manage Barangays</p>
          </a>
        </li>
        <?php endif; ?>

        <?php if ($canManageStaffAccounts) : ?>
        <li class="nav-header">ACCOUNTS</li>
        <li class="nav-item <?= $isStaffAccountsPage ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= $isStaffAccountsPage ? 'active' : '' ?>">
            <i class="nav-icon fas fa-user-shield"></i>
            <p>Users <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <?php if ($isSsaActor) : ?>
            <li class="nav-item">
              <a href="staffAccounts.php?hub=nutrition&amp;role=<?= STAFF_ROLE_NUTRITION_SUPER_ADMIN ?>" class="nav-link <?= $superActivePage === 'nutrition_sa' ? 'active' : '' ?>">
                <i class="fas fa-circle nav-icon text-warning"></i>
                <p>Nutrition Super Admin (SA)</p>
              </a>
            </li>
            <?php endif; ?>
            <?php if ($isNutritionPortalAdmin || $isSsaActor) : ?>
            <li class="nav-item">
              <a href="staffAccounts.php?hub=nutrition&amp;role=<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR_ADMIN ?>" class="nav-link <?= $superActivePage === 'bns_admin' ? 'active' : '' ?>">
                <i class="fas fa-circle nav-icon text-red"></i>
                <p>Nutrition Admin (A)</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="staffAccounts.php?hub=nutrition&amp;role=<?= STAFF_ROLE_CITY_NUTRITION_PROGRAM_COORDINATOR ?>" class="nav-link <?= $superActivePage === 'cnpc' ? 'active' : '' ?>">
                <i class="fas fa-circle nav-icon text-info"></i>
                <p>CNPC Accounts</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="staffAccounts.php?hub=nutrition&amp;role=<?= STAFF_ROLE_BARANGAY_NUTRITION_SCHOLAR ?>" class="nav-link <?= $superActivePage === 'bns' ? 'active' : '' ?>">
                <i class="fas fa-circle nav-icon text-success"></i>
                <p>BNS Accounts</p>
              </a>
            </li>
            <?php endif; ?>
            <?php if ($isSsaActor && !$isNutritionPortalAdmin) : ?>
            <li class="nav-item">
              <a href="staffAccounts.php" class="nav-link <?= $superActivePage === 'staff_accounts' ? 'active' : '' ?>">
                <i class="fas fa-circle nav-icon text-red"></i>
                <p>All Staff Accounts</p>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <li class="nav-header">ACCOUNT</li>
        <li class="nav-item">
          <a href="nutritionAccountProfile.php" class="nav-link <?= $superActivePage === 'profile' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-user-cog"></i>
            <p>My Profile</p>
          </a>
        </li>
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
