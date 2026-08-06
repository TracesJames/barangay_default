<?php
require_once __DIR__ . '/csrf.php';
$assetBase = $appearanceAssetBase ?? '../assets';
echo '<meta name="csrf-token" content="' . barangay_h(csrf_token()) . '">' . PHP_EOL;
echo '<link rel="stylesheet" href="' . barangay_h($assetBase) . '/css/barangay.css?v=20260729a">' . PHP_EOL;
echo '<link rel="stylesheet" href="' . barangay_h($assetBase) . '/css/portal-themes.css?v=20260805m">' . PHP_EOL;
echo '<link rel="stylesheet" href="' . barangay_h($assetBase) . '/css/pastel-themes.css?v=20260805p">' . PHP_EOL;
echo '<link rel="stylesheet" href="' . barangay_h($assetBase) . '/css/appearance-accessibility.css?v=20260805p">' . PHP_EOL;
echo '<script src="' . barangay_h($assetBase) . '/js/appearance-prefs.js?v=20260805p"></script>' . PHP_EOL;
echo '<script>if (window.BarangayAppearance) { BarangayAppearance.bootEarly(); }</script>' . PHP_EOL;
echo '<script src="' . barangay_h($assetBase) . '/js/barangay-ui.js"></script>' . PHP_EOL;
