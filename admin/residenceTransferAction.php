<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/residence_transfer_http.php';

residence_transfer_http_action($con);
