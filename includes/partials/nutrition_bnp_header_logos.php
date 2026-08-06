<?php

/**
 * BNP form header banner:
 * Barangay report — left: barangay logo | center: titles | right: NNC + City
 * City / SuperAdmin — left: NNC | center: titles | right: City
 *
 * Expected (optional):
 * - $bnpHeaderCenterHtml
 * - $bnpBarangayLogoUrl, $bnpCityLogoUrl, $bnpNncLogoUrl
 * - $barangayName / $barangay
 * - $isCityWide
 * - $activeBarangay, $sidebarLogo
 * - $assetPrefix (default '../')
 */
$assetPrefix = $assetPrefix ?? '../';
$barangayName = $barangayName ?? ($barangay ?? 'Barangay');
$bnpHeaderCenterHtml = $bnpHeaderCenterHtml ?? '';
$isCityWide = !empty($isCityWide);

if (empty($bnpCityLogoUrl)) {
    $bnpCityLogoUrl = barangay_default_logo_url($assetPrefix);
}
if (empty($bnpNncLogoUrl)) {
    $bnpNncLogoUrl = $assetPrefix . 'assets/logo/national-nutrition-council.png';
}
if (empty($bnpBarangayLogoUrl)) {
    if (!empty($sidebarLogo)) {
        $bnpBarangayLogoUrl = $sidebarLogo;
    } elseif (!empty($activeBarangay) && is_array($activeBarangay)) {
        $bnpBarangayLogoUrl = barangay_logo_url($activeBarangay, $assetPrefix);
    } else {
        $bnpBarangayLogoUrl = $bnpCityLogoUrl;
    }
}

$bnpLogoCacheBust = '20260722c';
$bnpAppendCache = static function (string $url) use ($bnpLogoCacheBust): string {
    $sep = str_contains($url, '?') ? '&' : '?';

    return $url . $sep . 'v=' . $bnpLogoCacheBust;
};
$bnpNncLogoUrl = $bnpAppendCache($bnpNncLogoUrl);
$bnpBarangayLogoUrl = $bnpAppendCache($bnpBarangayLogoUrl);
$bnpCityLogoUrl = $bnpAppendCache($bnpCityLogoUrl);
$bannerClass = 'bnp-header-banner ' . ($isCityWide ? 'bnp-header-banner--city' : 'bnp-header-banner--barangay');
?>
<div class="<?= barangay_h($bannerClass) ?>">
  <div class="bnp-logo-side bnp-logo-side-left">
    <?php if ($isCityWide) : ?>
    <div class="bnp-logo-cell">
      <span class="bnp-logo-circle">
        <img src="<?= barangay_h($bnpNncLogoUrl) ?>" alt="National Nutrition Council" class="bnp-logo-img">
      </span>
      <div class="bnp-logo-caption">National Nutrition Council</div>
    </div>
    <?php else : ?>
    <div class="bnp-logo-cell">
      <span class="bnp-logo-circle">
        <img src="<?= barangay_h($bnpBarangayLogoUrl) ?>" alt="Barangay <?= barangay_h($barangayName) ?>" class="bnp-logo-img">
      </span>
      <div class="bnp-logo-caption">Barangay <?= barangay_h($barangayName) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <div class="bnp-header-center">
    <?= $bnpHeaderCenterHtml ?>
  </div>

  <div class="bnp-logo-side bnp-logo-side-right">
    <?php if (!$isCityWide) : ?>
    <div class="bnp-logo-cell">
      <span class="bnp-logo-circle">
        <img src="<?= barangay_h($bnpNncLogoUrl) ?>" alt="National Nutrition Council" class="bnp-logo-img">
      </span>
      <div class="bnp-logo-caption">National Nutrition Council</div>
    </div>
    <?php endif; ?>
    <div class="bnp-logo-cell">
      <span class="bnp-logo-circle">
        <img src="<?= barangay_h($bnpCityLogoUrl) ?>" alt="City of Valencia" class="bnp-logo-img">
      </span>
      <div class="bnp-logo-caption">City of Valencia</div>
    </div>
  </div>
</div>
