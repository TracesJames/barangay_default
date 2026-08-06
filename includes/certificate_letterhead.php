<?php

$certHeader = barangay_certificate_header($row_barangay_information ?? []);

?>
      <?= barangay_h($certHeader['country']) ?> <br>
      <?= barangay_h($certHeader['province']) ?> <br>
      <?= barangay_h($certHeader['city']) ?> <br>
      <?= barangay_h($certHeader['barangay_line']) ?><br>
      <?= barangay_h($certHeader['office']) ?>
