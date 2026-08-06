<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/residence_import.php';

$activeBarangay = barangay_require_active($con, 'barangayHub.php?picker=1');
residence_import_stream_registration_template($con, $activeBarangay);
