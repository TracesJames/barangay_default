<?php

/**
 * Resolve barangay/city logos for certificate print pages.
 */
if (!isset($con) || !($con instanceof mysqli)) {
    return;
}

if (!function_exists('barangay_logo_url')) {
    require_once __DIR__ . '/barangay_context.php';
}

if (empty($row_barangay_information)) {
    if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'resident' && !empty($_SESSION['user_id'])) {
        $row_barangay_information = barangay_load_for_resident($con, (string) $_SESSION['user_id']) ?? [];
    } else {
        $row_barangay_information = barangay_load_active($con) ?? [];
    }
}

$printBarangayLogoUrl = barangay_logo_url($row_barangay_information, '../');
if (str_contains($printBarangayLogoUrl, 'black.png')) {
    $printBarangayLogoUrl = barangay_default_logo_url('../');
}
$printCityLogoUrl = barangay_default_logo_url('../');
$certificateWatermarkUrl = $printCityLogoUrl;

$image = '<img src="' . barangay_h($printBarangayLogoUrl) . '" class="certificate-logo" id="barangay_logo" alt="barangay logo">';
$cityLogoHtml = '<img src="' . barangay_h($printCityLogoUrl) . '" class="certificate-logo" id="valencia_city" alt="City of Valencia logo">';
$certificateWatermarkHtml = '<img src="' . barangay_h($certificateWatermarkUrl) . '" class="certificate-watermark" alt="" aria-hidden="true">';

/**
 * Resolve Punong Barangay (chairman) for the active barangay — signature above printed name.
 */
$row_official = null;
$official_middle_name = '';
$official_image = '';
$punongBarangayName = '';
$punongBarangayTitle = 'Punong Barangay';

$barangayScopeId = trim((string) ($row_barangay_information['id'] ?? ''));
if ($barangayScopeId === '') {
    $barangayScopeId = (string) (barangay_session_id() ?? '');
}

$positionStmt = $con->prepare("SELECT position_id FROM position WHERE LOWER(position) = 'chairman' LIMIT 1");
if ($positionStmt) {
    $positionStmt->execute();
    $chairmanPositionId = (string) ($positionStmt->get_result()->fetch_assoc()['position_id'] ?? '');
    $positionStmt->close();

    if ($chairmanPositionId !== '') {
        $hasOfficialScope = barangay_column_exists($con, 'official_status', 'barangay_id');
        if ($hasOfficialScope && $barangayScopeId !== '') {
            $officialStmt = $con->prepare(
                "SELECT oi.first_name, oi.middle_name, oi.last_name, oi.image, oi.image_path
                 FROM official_information oi
                 INNER JOIN official_status os ON oi.official_id = os.official_id
                 WHERE os.position = ? AND os.barangay_id = ?
                 ORDER BY os.a_i ASC
                 LIMIT 1"
            );
            if ($officialStmt) {
                $officialStmt->bind_param('ss', $chairmanPositionId, $barangayScopeId);
                $officialStmt->execute();
                $row_official = $officialStmt->get_result()->fetch_assoc() ?: null;
                $officialStmt->close();
            }
        } else {
            $officialStmt = $con->prepare(
                "SELECT oi.first_name, oi.middle_name, oi.last_name, oi.image, oi.image_path
                 FROM official_information oi
                 INNER JOIN official_status os ON oi.official_id = os.official_id
                 WHERE os.position = ?
                 ORDER BY os.a_i ASC
                 LIMIT 1"
            );
            if ($officialStmt) {
                $officialStmt->bind_param('s', $chairmanPositionId);
                $officialStmt->execute();
                $row_official = $officialStmt->get_result()->fetch_assoc() ?: null;
                $officialStmt->close();
            }
        }
    }
}

if (is_array($row_official)) {
    $count_official = 1;
    $middle = trim((string) ($row_official['middle_name'] ?? ''));
    $official_middle_name = $middle !== '' ? (mb_substr($middle, 0, 1) . '.') : '';
    $punongBarangayName = trim(
        trim((string) ($row_official['first_name'] ?? '')) . ' '
        . ($official_middle_name !== '' ? $official_middle_name . ' ' : '')
        . trim((string) ($row_official['last_name'] ?? ''))
    );

    $imagePath = trim((string) ($row_official['image_path'] ?? ''));
    $imageName = trim((string) ($row_official['image'] ?? ''));
    if ($imageName !== '' && $imagePath !== '') {
        $official_image = '<img src="' . barangay_h($imagePath) . '" class="punong-barangay-signature" id="barangay_official" alt="Punong Barangay signature">';
    } else {
        $official_image = '<div class="punong-barangay-signature-blank" aria-hidden="true"></div>';
    }
} else {
    $count_official = 0;
}
