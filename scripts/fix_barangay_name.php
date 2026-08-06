<?php
require_once __DIR__ . '/../connection.php';
$stmt = $con->prepare("UPDATE barangay_information SET barangay = 'Barangay' WHERE barangay = 'Barnagay'");
$stmt->execute();
echo 'Rows updated: ' . $stmt->affected_rows . PHP_EOL;
