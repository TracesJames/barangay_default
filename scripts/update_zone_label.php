<?php
require_once dirname(__DIR__) . '/connection.php';
$con->query("UPDATE barangay_information SET zone = 'PUROK' WHERE zone = 'Zone'");
echo 'Updated rows: ' . $con->affected_rows . PHP_EOL;
