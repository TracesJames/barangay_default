<?php
/**
 * Streams the release APK from E:\barangay-releases (C: disk is full).
 */
$file = 'E:/barangay-releases/nutrition_bns.apk';
if (!is_file($file)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'APK not found on server.';
    exit;
}

$size = filesize($file);
header('Content-Type: application/vnd.android.package-archive');
header('Content-Length: ' . $size);
header('Content-Disposition: attachment; filename="nutrition_bns.apk"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
readfile($file);
exit;
