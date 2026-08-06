<?php
/**
 * Remove legacy barangay_information while-loops and unify sidebar logos on admin pages.
 */
$adminDir = realpath(__DIR__ . '/../admin');
$files = glob($adminDir . '/*.php') ?: [];

$whilePattern = '/\n\s*\$sql = "SELECT \* FROM `barangay_information`";.*?while\s*\(\$row = \$result->fetch_assoc\(\)\)\{.*?\}\s*\n/s';

$logoBlock = <<<'HTML'
    <?php 
        if($image != '' || $image != null || !empty($image)){
          echo '<img src="'.$image_path.'" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }else{
          echo ' <img src="../assets//logo//logo.png" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }

      ?>
HTML;

$logoReplacement = <<<'HTML'
      <img src="<?= barangay_h($sidebarLogo) ?>" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="<?= barangay_h($barangay) ?>" style="width: 70%;">
HTML;

$logoBlock2 = <<<'HTML'
    <?php 
        if($image != '' || $image != null || !empty($image)){
          echo '<img src="'.$image_path.'" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }else{
          echo ' <img src="../assets/logo/logo.png" id="logo_image" class="img-circle elevation-5 img-bordered-sm" alt="logo" style="width: 70%;">';
        }

      ?>
HTML;

$activeBlockPattern = '/\n\s*\$activeBarangay = barangay_require_active\(\$con, \'barangayHub\.php\'\);\s*\n\s*\$barangay_id = \$activeBarangay\[\'id\'\];\s*\n\s*\$barangay = \$activeBarangay\[\'barangay\'\];\s*\n\s*\$zone = \$activeBarangay\[\'zone\'\];\s*\n\s*\$district = \$activeBarangay\[\'district\'\];\s*\n\s*\$image = \$activeBarangay\[\'image\'\];\s*\n\s*\$image_path = \$activeBarangay\[\'image_path\'\];\s*\n\s*\$id = \$activeBarangay\[\'id\'\];\s*\n(\s*\$default_municipality = \$activeBarangay\[\'address\'\] \?\? \'\';\s*\n\s*\$sidebarLogo = barangay_admin_logo_url\(\$activeBarangay\);\s*\n)?/s';

foreach ($files as $file) {
    $name = basename($file);
    if ($name === 'barangayHub.php' || $name === 'selectBarangay.php' || $name === 'createBarangay.php' || $name === 'updateBarangayLogo.php') {
        continue;
    }

    $content = file_get_contents($file);
    $original = $content;

    $content = preg_replace($whilePattern, "\n", $content);

    $content = str_replace($logoBlock, $logoReplacement, $content);
    $content = str_replace($logoBlock2, $logoReplacement, $content);

    // Pages that manually load active barangay duplicate auth_admin vars — remove duplicate block.
    $content = preg_replace($activeBlockPattern, "\n", $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Patched: $name\n";
    }
}

echo "Done.\n";
