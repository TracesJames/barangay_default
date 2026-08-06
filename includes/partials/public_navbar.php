<?php
/**
 * Public portal top navigation.
 *
 * @var string $publicNavActive  home|login
 * @var string $publicNavTitle   Brand label
 * @var string $publicNavLogo    Logo URL
 * @var string $publicBrandHref  Brand link target
 */
if (!function_exists('barangay_h')) {
    require_once __DIR__ . '/../helpers.php';
}
if (!function_exists('barangay_public_logo_url')) {
    require_once __DIR__ . '/../barangay_context.php';
}

$publicNavActive = $publicNavActive ?? '';
$publicNavTitle = $publicNavTitle ?? 'CITY OF VALENCIA PORTAL';
$publicNavLogo = $publicNavLogo ?? barangay_public_logo_url();
$publicBrandHref = $publicBrandHref ?? 'index.php';
?>
<nav class="main-header navbar navbar-expand-md navbar-dark barangay-nav">
  <div class="barangay-nav-inner">
    <a href="<?= barangay_h($publicBrandHref) ?>" class="navbar-brand">
      <img src="<?= barangay_h($publicNavLogo) ?>" alt="<?= barangay_h($publicNavTitle) ?>" class="brand-image img-circle">
      <span class="brand-text text-white"><?= barangay_h($publicNavTitle) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#publicNavbarCollapse" aria-controls="publicNavbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="publicNavbarCollapse">
      <ul class="navbar-nav barangay-public-nav">
        <li class="nav-item">
          <a href="index.php" class="nav-link rightBar<?= $publicNavActive === 'home' ? ' nav-active' : '' ?>">HOME</a>
        </li>
        <li class="nav-item">
          <a href="login.php" class="nav-link rightBar<?= $publicNavActive === 'login' ? ' nav-active' : '' ?>"><i class="fas fa-user-alt" aria-hidden="true"></i> LOGIN</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
