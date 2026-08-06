<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';

$query = $_GET;
$query['type'] = 'pregnant';
header('Location: nutritionBnpReport.php?' . http_build_query($query));
exit;
