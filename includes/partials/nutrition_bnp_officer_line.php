<?php

/**
 * BNP officer name line (top of form).
 * Barangay → Name of BNS
 * City / SuperAdmin → City Nutrition Head
 *
 * Expected: $isCityWide, $bnsName, optional $cityNutritionHeadName / $cityNutritionHeadTitle
 */
$isCityWide = !empty($isCityWide);
$bnsName = trim((string) ($bnsName ?? ''));
if (isset($con) && $con instanceof mysqli) {
    $preparedBy = nutrition_prepared_by_signatory($con, null, $bnsName);
    $bnsName = trim((string) ($preparedBy['name'] ?? $bnsName));
}
$cityNutritionHeadName = trim((string) ($cityNutritionHeadName ?? 'Hazel Dondonayos, RND'));
$cityNutritionHeadTitle = trim((string) ($cityNutritionHeadTitle ?? 'City Nutrition Head'));

if ($isCityWide) :
    ?>
  <div class="bnp-bns">
    City Nutrition Head:
    <strong><?= barangay_h($cityNutritionHeadName !== '' ? $cityNutritionHeadName : '______________________________') ?></strong>
  </div>
    <?php
else :
    ?>
  <div class="bnp-bns">
    Name of BNS:
    <?= barangay_h($bnsName !== '' ? $bnsName : '______________________________') ?>
  </div>
    <?php
endif;
