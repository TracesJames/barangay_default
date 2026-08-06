<?php
/**
 * Include after jQuery on portal/public pages so CSRF ajax headers are applied.
 */
$depth = $barangay_script_depth ?? 0;
$prefix = $depth === 0 ? 'assets/' : str_repeat('../', $depth) . 'assets/';
echo '<script src="' . $prefix . 'js/csrf.js"></script>' . PHP_EOL;
echo '<script src="' . $prefix . 'js/barangay-ui.js"></script>' . PHP_EOL;
