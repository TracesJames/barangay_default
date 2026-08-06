<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';
require_once '../includes/residence_import.php';

$activeBarangay = barangay_require_active($con, '../admin/barangayHub.php?picker=1');
residence_import_stream_registration_template($con, $activeBarangay);
