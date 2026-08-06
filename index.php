<?php
include_once 'connection.php';
require_once 'includes/barangay_context.php';
require_once 'includes/nutrition_context.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_type'])) {
    $user_id = $_SESSION['user_id'];
    $sql = 'SELECT user_type FROM users WHERE id = ? LIMIT 1';
    $query = $con->prepare($sql);
    if ($query) {
        $query->bind_param('s', $user_id);
        $query->execute();
        $row = $query->get_result()->fetch_assoc();
        $account_type = $row['user_type'] ?? '';

        if ($account_type === 'admin') {
            nutrition_admin_redirect_if_needed($con, (string) $user_id);
            if (barangay_user_is_super_admin($con, (string) $user_id)) {
                header('Location: admin/superDashboard.php');
            } elseif (barangay_user_is_city_admin($con, (string) $user_id)) {
                header('Location: admin/barangayHub.php?picker=1');
            } else {
                header('Location: admin/dashboard.php');
            }
            exit;
        }

        if ($account_type === 'secretary') {
            header('Location: secretary/dashboard.php');
            exit;
        }

        if ($account_type !== '') {
            header('Location: resident/dashboard.php');
            exit;
        }
    }
}

$sql = 'SELECT * FROM `barangay_information` ORDER BY barangay ASC LIMIT 1';
$query = $con->prepare($sql) or die($con->error);
$query->execute();
$result = $query->get_result();
$portalBarangay = $result->fetch_assoc();
if ($portalBarangay) {
    $postal_address = $portalBarangay['postal_address'] ?? '';
} else {
    $postal_address = '';
}
$navLogo = barangay_public_logo_url();
$barangayCount = count(barangay_list_all($con));

$slideDir = __DIR__ . '/assets/images/portal-slides';
$slides = [];
if (is_dir($slideDir)) {
    foreach (scandir($slideDir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            continue;
        }
        $slides[] = 'assets/images/portal-slides/' . rawurlencode($file);
    }
    sort($slides, SORT_NATURAL | SORT_FLAG_CASE);
}
if ($slides === []) {
    $slides[] = 'assets/logo/cover.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home | City of Valencia Portal</title>
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <?php require_once 'includes/head_csrf_root.php'; ?>
  <link rel="stylesheet" href="assets/css/public-portal.css?v=20260729e">
</head>
<body class="hold-transition layout-top-nav barangay-portal public-portal-page public-portal-page--slideshow">

<div class="wrapper">

  <?php
  $publicNavActive = 'home';
  $publicNavTitle = 'CITY OF VALENCIA PORTAL';
  $publicNavLogo = $navLogo;
  require_once 'includes/partials/public_navbar.php';
  ?>

  <div class="content-wrapper public-portal-bg">
    <div class="public-slideshow" aria-hidden="true">
      <div class="public-slideshow__track">
        <?php foreach ($slides as $i => $slideUrl): ?>
          <div
            class="public-slideshow__slide<?= $i === 0 ? ' is-active' : '' ?>"
            style="background-image: url('<?= barangay_h($slideUrl) ?>');"
          ></div>
        <?php endforeach; ?>
      </div>
      <div class="public-slideshow__veil"></div>
    </div>

    <div class="content">
      <div class="public-hero-wrap">
        <div class="card public-welcome-card">
          <div class="card-body">
            <div class="public-welcome-card__header">
              <span class="public-badge"><i class="fas fa-landmark"></i> Valencia City · Bukidnon</span>
            </div>

            <div class="public-welcome-card__content">
              <img src="<?= barangay_h($navLogo) ?>" alt="Valencia City seal" class="public-welcome-card__seal">
              <h1 class="public-welcome-title">Welcome to the City of Valencia Portal</h1>
              <p class="public-welcome-sub"><?= $barangayCount > 0 ? number_format($barangayCount) . ' barangays connected' : 'Sign in to get started' ?></p>
              <p class="public-welcome-hint">Log in to access Barangay Hub or Nutrition Hub</p>

              <div class="public-welcome-actions">
                <a href="login.php" class="btn public-btn-login"><i class="fas fa-sign-in-alt mr-2"></i>Login</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="main-footer text-white barangay-footer">
    <div class="float-right d-none d-sm-block"></div>
  <?php if ($postal_address !== ''): ?>
  <i class="fas fa-map-marker-alt"></i> <?= barangay_h($postal_address) ?>
  <?php endif; ?>
  </footer>

</div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<?php $barangay_script_depth = 0; require_once 'includes/scripts_csrf.php'; ?>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<script>
(function () {
  var slides = document.querySelectorAll('.public-slideshow__slide');
  if (slides.length < 2) return;
  var index = 0;
  setInterval(function () {
    slides[index].classList.remove('is-active');
    index = (index + 1) % slides.length;
    slides[index].classList.add('is-active');
  }, 5500);
})();
</script>

</body>
</html>
