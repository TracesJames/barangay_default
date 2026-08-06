<?php
/**
 * Include shared report auto-fit CSS/JS on printable pages.
 *
 * Optional:
 *   $reportFitAssetBase — e.g. '../assets' (default) or 'assets'
 */
$reportFitAssetBase = isset($reportFitAssetBase) ? rtrim((string) $reportFitAssetBase, '/') : '../assets';
?>
<link rel="stylesheet" href="<?= barangay_h($reportFitAssetBase) ?>/css/report-fit.css?v=20260730a">
<script src="<?= barangay_h($reportFitAssetBase) ?>/js/report-fit.js?v=20260730a" defer></script>
