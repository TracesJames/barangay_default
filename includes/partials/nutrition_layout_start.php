<?php

/**
 * Opens the nutrition portal page shell.
 * Expects nutrition_init.php to have been loaded.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= barangay_h($nutritionPageTitle) ?> | Nutrition Portal</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
<?php
if (!empty($nutritionExtraCss) && is_array($nutritionExtraCss)) {
    foreach ($nutritionExtraCss as $cssPath) {
        echo '  <link rel="stylesheet" href="' . barangay_h($cssPath) . '">' . PHP_EOL;
    }
}
require_once __DIR__ . '/../head_csrf.php';
?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260811a">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal nutrition-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark nutrition-navbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <h5 class="nav-link text-white mb-0">Nutrition Portal · <?= barangay_h($barangay) ?></h5>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item d-none d-md-inline-block">
        <a class="nav-link text-white-50" href="nutritionDashboard.php"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
      </li>
    </ul>
  </nav>

  <?php require __DIR__ . '/nutrition_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
