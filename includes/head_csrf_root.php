<?php
require_once __DIR__ . '/csrf.php';
echo '<meta name="csrf-token" content="' . barangay_h(csrf_token()) . '">' . PHP_EOL;
echo '<link rel="stylesheet" href="assets/css/barangay.css">' . PHP_EOL;
echo '<script src="assets/js/barangay-ui.js"></script>' . PHP_EOL;
