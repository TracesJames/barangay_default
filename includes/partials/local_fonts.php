<?php
/**
 * Self-hosted fonts (Source Sans 3 + Inter). No Google Fonts CDN.
 *
 * Optional: $localFontsDepth = 1 for pages one folder below project root (default 1 from admin/).
 */
$localFontsDepth = isset($localFontsDepth) ? (int) $localFontsDepth : 1;
$localFontsPrefix = str_repeat('../', max(0, $localFontsDepth));
?>
<link rel="stylesheet" href="<?= barangay_h($localFontsPrefix . 'assets/css/local-fonts.css') ?>">
