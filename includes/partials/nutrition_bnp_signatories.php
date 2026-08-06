<?php

/**
 * BNP footer signatories — different for barangay vs city/SuperAdmin reports.
 *
 * Barangay:
 *   Prepared by — logged-in user name + role (fallback: BNS name from report/settings)
 *   Checked by  — Rural Health Midwife
 *   Noted by    — Punong Barangay / BNC Chairperson (chairman of the active barangay)
 *
 * City / SuperAdmin:
 *   City Nutrition Head — Hazel Dondonayos, RND
 *   Noted by — City Mayor / CNC Chairperson — Hon. Amie G. Galario
 *
 * Expected: $isCityWide, $bnsName, optional $barangay_id / $barangayId,
 *           optional $cityNutritionHeadName / $cityNutritionHeadTitle /
 *           $cityMayorName / $cityMayorTitle
 */
$isCityWide = !empty($isCityWide);
$bnsName = trim((string) ($bnsName ?? ''));
if (isset($con) && $con instanceof mysqli) {
    $preparedBy = nutrition_prepared_by_signatory($con, null, $bnsName);
} else {
    $preparedBy = ['name' => $bnsName, 'title' => 'Barangay Nutrition Scholar (BNS)'];
}
$preparedByName = trim((string) ($preparedBy['name'] ?? ''));
$preparedByTitle = trim((string) ($preparedBy['title'] ?? 'Barangay Nutrition Scholar (BNS)'));

$punongBarangayName = trim((string) ($punongBarangayName ?? ''));
if ($punongBarangayName === '' && isset($con) && $con instanceof mysqli && function_exists('barangay_punong_barangay_name')) {
    $signatoryBarangayId = trim((string) ($barangay_id ?? $barangayId ?? ''));
    $signatoryBarangayName = trim((string) ($barangay ?? $barangayName ?? ''));
    if ($signatoryBarangayId === '' && isset($bnpReport) && is_array($bnpReport)) {
        $signatoryBarangayId = trim((string) ($bnpReport['barangay_id'] ?? ''));
        if ($signatoryBarangayName === '') {
            $signatoryBarangayName = trim((string) ($bnpReport['barangay'] ?? $bnpReport['barangay_name'] ?? ''));
        }
    }
    if ($signatoryBarangayId === '' && isset($pregnantReport) && is_array($pregnantReport)) {
        $signatoryBarangayId = trim((string) ($pregnantReport['barangay_id'] ?? ''));
        if ($signatoryBarangayName === '') {
            $signatoryBarangayName = trim((string) ($pregnantReport['barangay'] ?? $pregnantReport['barangay_name'] ?? ''));
        }
    }
    if ($signatoryBarangayId === '') {
        $signatoryBarangayId = (string) (barangay_session_id() ?? '');
    }
    $punongBarangayName = barangay_punong_barangay_name($con, $signatoryBarangayId, $signatoryBarangayName);
}

$cityNutritionHeadName = trim((string) ($cityNutritionHeadName ?? 'Hazel Dondonayos, RND'));
$cityNutritionHeadTitle = trim((string) ($cityNutritionHeadTitle ?? 'City Nutrition Head'));
$cityMayorName = trim((string) ($cityMayorName ?? 'Hon. Amie G. Galario'));
$cityMayorTitle = trim((string) ($cityMayorTitle ?? 'City Mayor / CNC Chairperson'));
?>
<?php if ($isCityWide) : ?>
  <div class="bnp-sign" style="grid-template-columns:repeat(2,1fr);">
    <div class="text-center">
      <br><br>
      <strong><?= barangay_h($cityNutritionHeadName !== '' ? $cityNutritionHeadName : '_________________________') ?></strong><br>
      <small><?= barangay_h($cityNutritionHeadTitle) ?></small><br>
      <small>City Nutrition Council · City of Valencia</small>
    </div>
    <div class="text-center">
      <br><br>
      <strong><?= barangay_h($cityMayorName !== '' ? $cityMayorName : '_________________________') ?></strong><br>
      <small>Noted by</small><br>
      <small><?= barangay_h($cityMayorTitle) ?></small>
    </div>
  </div>
<?php else : ?>
  <div class="bnp-sign">
    <div class="text-center">
      <?php if ($preparedByName !== '') : ?>
      <br>
      <strong><?= barangay_h($preparedByName) ?></strong><br>
      <?php else : ?>
      <br><br>
      _________________________<br>
      <?php endif; ?>
      <small>Prepared by</small><br>
      <small><?= barangay_h($preparedByTitle) ?></small>
    </div>
    <div class="text-center">
      <br><br>
      _________________________<br>
      <small>Checked by</small><br>
      <small>Rural Health Midwife</small>
    </div>
    <div class="text-center">
      <?php if ($punongBarangayName !== '') : ?>
      <br>
      <strong><?= barangay_h($punongBarangayName) ?></strong><br>
      <?php else : ?>
      <br><br>
      _________________________<br>
      <?php endif; ?>
      <small>Approved by</small><br>
      <small>Punong Barangay / BNC Chairperson</small>
    </div>
  </div>
<?php endif; ?>
