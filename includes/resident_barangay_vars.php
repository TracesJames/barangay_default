<?php

/**
 * Expose the logged-in resident's barangay for portal headers and certificates.
 */
if (!isset($con) || !($con instanceof mysqli) || !isset($_SESSION['user_id'])) {
    return;
}

$residenceId = (string) $_SESSION['user_id'];
$residentBarangayRow = barangay_load_for_resident($con, $residenceId);
$row_barangay_information = $residentBarangayRow ?? [];

if ($residentBarangayRow) {
    $barangay = $residentBarangayRow['barangay'];
    $zone = trim((string) ($residentBarangayRow['zone'] ?? ''));
    if (strcasecmp($zone, 'PUROK') === 0) {
        $zone = '';
    }
    $district = $residentBarangayRow['district'];
    $image = $residentBarangayRow['image'];
    $image_path = $residentBarangayRow['image_path'];
    $barangayLogoUrl = barangay_logo_url($residentBarangayRow, '../');
    $id = $residentBarangayRow['id'];
    $postal_address = $residentBarangayRow['postal_address'] ?? '';
    $row_barangay_information = $residentBarangayRow;
    $row_barangay_information['zone'] = $zone;
} else {
    $barangay = 'City of Valencia Portal';
    $zone = '';
    $district = '';
    $image = '';
    $image_path = '';
    $barangayLogoUrl = barangay_default_logo_url('../');
    $id = '';
    $postal_address = '';
}
