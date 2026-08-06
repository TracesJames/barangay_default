<?php

/**
 * Expose active barangay variables for admin UI (sidebar logo, navbar labels).
 * Requires $con and auth_admin (barangay_context loaded).
 */
if (!isset($con) || !($con instanceof mysqli)) {
    return;
}

$activeBarangay = barangay_load_active($con);

if ($activeBarangay) {
    $barangay_id = $activeBarangay['id'];
    $barangay = $activeBarangay['barangay'];
    $zone = $activeBarangay['zone'];
    $district = $activeBarangay['district'];
    $image = $activeBarangay['image'];
    $image_path = $activeBarangay['image_path'];
    $id = $activeBarangay['id'];
    $postal_address = $activeBarangay['postal_address'] ?? '';
    $default_municipality = $activeBarangay['address'] ?? '';
    $address = $default_municipality;
    $sidebarLogo = barangay_admin_logo_url($activeBarangay);
    $barangayPurokOptions = barangay_purok_filter_options($con, $barangay_id);
} else {
    $barangay_id = null;
    $barangay = 'City of Valencia Portal';
    $zone = '';
    $district = '';
    $image = '';
    $image_path = '';
    $id = '';
    $postal_address = '';
    $default_municipality = '';
    $address = '';
    $sidebarLogo = '../assets/logo/valencia-city.png';
    $barangayPurokOptions = [];
}

$canIssueCertificate = barangay_user_can_issue_certificate($con, (string) ($_SESSION['user_id'] ?? ''));
