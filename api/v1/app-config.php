<?php

/**
 * Public mobile app config (version gate). Auth optional.
 */
require_once __DIR__ . '/bootstrap.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    nutrition_api_error('Method not allowed. Use GET.', 405);
}

// Bump min_version_code when a forced update is required for field APKs.
$minVersionCode = 2;
$minVersionName = '0.2.0';
$latestVersionName = '0.2.0';
$forceUpdate = false;
$updateMessage = 'A newer Nutrition BNS app is available. Please install the latest APK from your city nutrition office.';
$updateUrl = '';

// Optional override via env (production).
$envMin = getenv('NUTRITION_BNS_MIN_VERSION_CODE');
if ($envMin !== false && trim((string) $envMin) !== '' && ctype_digit(trim((string) $envMin))) {
    $minVersionCode = (int) trim((string) $envMin);
}
$envForce = getenv('NUTRITION_BNS_FORCE_UPDATE');
if ($envForce !== false) {
    $forceUpdate = in_array(strtolower(trim((string) $envForce)), ['1', 'true', 'yes'], true);
}
$envUrl = getenv('NUTRITION_BNS_UPDATE_URL');
if ($envUrl !== false && trim((string) $envUrl) !== '') {
    $updateUrl = trim((string) $envUrl);
}

$clientCode = (int) ($_GET['version_code'] ?? 0);
$needsUpdate = $clientCode > 0 && $clientCode < $minVersionCode;

nutrition_api_send([
    'ok' => true,
    'min_version_code' => $minVersionCode,
    'min_version_name' => $minVersionName,
    'latest_version_name' => $latestVersionName,
    'force_update' => $forceUpdate || $needsUpdate,
    'needs_update' => $needsUpdate,
    'update_message' => $updateMessage,
    'update_url' => $updateUrl,
    'api_version' => 'v1',
]);
