<?php

/**
 * Shared Super Admin sidebar.
 *
 * Expects: $brandLogo or $sidebarLogo, $first_name_user, $last_name_user, $user_image,
 *          $staffRoleLabel, $activePage (optional)
 */
$superBrandLogo = $brandLogo ?? $sidebarLogo ?? '../assets/logo/valencia-city.png';
$superActivePage = $activePage ?? '';
$staffRoleLabel = $staffRoleLabel ?? 'Super Admin';
$isStaffAccountsPage = $superActivePage === 'staff_accounts' || $superActivePage === 'residents';
$userAvatarUrl = barangay_user_avatar_url($user_image ?? '', $user_image_path ?? '', '../');
$portalBrandName = isset($con)
    ? barangay_portal_brand_name($con, (string) ($_SESSION['user_id'] ?? ''), false)
    : 'City of Valencia Portal';
$portalBrandTagline = isset($con)
    ? barangay_portal_brand_tagline($con, (string) ($_SESSION['user_id'] ?? ''), false)
    : 'City of Valencia · Barangay Management';
$canSwitchToNutritionHub = isset($con, $_SESSION['user_id'])
    && function_exists('barangay_user_is_ssa')
    && barangay_user_is_ssa($con, (string) $_SESSION['user_id']);
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
  <a href="superDashboard.php" class="brand-link text-center super-brand-link">
    <img src="<?= barangay_h($superBrandLogo) ?>" alt="Valencia City" class="super-brand-logo img-circle elevation-5">
    <span class="brand-text font-weight-light d-block"><?= barangay_h($portalBrandName) ?></span>
    <small class="d-block text-teal"><?= barangay_h($portalBrandTagline) ?></small>
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
        <a href="myProfile.php" class="d-block text-bold"><?= barangay_h(ucfirst($first_name_user) . ' ' . ucfirst($last_name_user)) ?></a>
        <span class="badge badge-danger text-uppercase px-2 py-1 mt-1"><?= barangay_h($staffRoleLabel) ?></span>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-header">CITY PORTAL</li>
        <li class="nav-item">
          <a href="superDashboard.php" class="nav-link <?= $superActivePage === 'super_dashboard' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-city"></i>
            <p>Super Admin Dashboard</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="barangayHub.php?picker=1" class="nav-link <?= $superActivePage === 'barangay_hub' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-th-large"></i>
            <p>Manage Barangays</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Barangay Dashboard</p>
          </a>
        </li>

        <li class="nav-header">ACCOUNTS</li>
        <li class="nav-item <?= $isStaffAccountsPage ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= $isStaffAccountsPage ? 'bg-indigo' : '' ?>">
            <i class="nav-icon fas fa-user-shield"></i>
            <p>Users <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="usersResident.php" class="nav-link <?= $superActivePage === 'residents' ? 'active' : '' ?>">
                <i class="fas fa-circle nav-icon text-red"></i>
                <p>Resident</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="staffAccounts.php" class="nav-link <?= $superActivePage === 'staff_accounts' ? 'active' : '' ?>">
                <i class="fas fa-circle nav-icon text-red"></i>
                <p>Staff Accounts</p>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-header">SYSTEM</li>
        <li class="nav-item">
          <a href="barangayCertificates.php" class="nav-link <?= $superActivePage === 'certificates' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-certificate"></i>
            <p>Certificates</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="systemLog.php" class="nav-link <?= $superActivePage === 'system_log' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-history"></i>
            <p>System Logs</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="myProfile.php" class="nav-link <?= $superActivePage === 'my_profile' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-user-cog"></i>
            <p>My Profile</p>
          </a>
        </li>
        <?php if ($canSwitchToNutritionHub) : ?>
        <li class="nav-item">
          <a href="nutritionSuperDashboard.php" class="nav-link">
            <i class="nav-icon fas fa-apple-alt"></i>
            <p>Switch to Nutrition Hub</p>
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
