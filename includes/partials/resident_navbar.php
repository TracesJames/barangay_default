<?php
/**
 * Resident portal top navigation.
 *
 * @var string $residentNavActive  dashboard|profile
 * @var string $last_name_user
 * @var string $user_id
 */
if (!function_exists('barangay_h')) {
    require_once __DIR__ . '/../helpers.php';
}
if (!function_exists('barangay_default_logo_url')) {
    require_once __DIR__ . '/../barangay_context.php';
}

$residentNavActive = $residentNavActive ?? '';
$barangayLogoUrl = $barangayLogoUrl ?? barangay_default_logo_url('../');
$brandLabel = trim($barangay . ($zone !== '' ? ' ' . $zone : '') . ', ' . $district);
?>
<nav class="main-header navbar navbar-expand-md barangay-nav">
  <div class="container">
    <a href="dashboard.php" class="navbar-brand">
      <img
        src="<?= barangay_h($barangayLogoUrl) ?>"
        alt="<?= barangay_h($barangay) ?> logo"
        class="brand-image img-circle"
        onerror="this.onerror=null;this.src='<?= barangay_h(barangay_default_logo_url('../')) ?>';"
      >
      <span class="brand-text text-white" style="font-weight: 700"><?= barangay_h($brandLabel) ?></span>
    </a>

    <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse order-3" id="navbarCollapse"></div>

    <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
      <li class="nav-item">
        <a href="dashboard.php" class="nav-link text-white rightBar<?= $residentNavActive === 'dashboard' ? ' nav-active' : '' ?>"><i class="fas fa-home"></i> DASHBOARD</a>
      </li>
      <li class="nav-item">
        <a href="profile.php" class="nav-link text-white rightBar<?= $residentNavActive === 'profile' ? ' nav-active' : '' ?>" style="text-transform:uppercase;"><i class="fas fa-user-alt"></i> <?= barangay_h($last_name_user) ?>-<?= barangay_h($user_id) ?></a>
      </li>
      <li class="nav-item">
        <a href="../logout.php" class="nav-link text-white rightBar" style="text-transform:uppercase;"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </li>
    </ul>
  </div>
</nav>
