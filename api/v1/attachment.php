<?php

require_once __DIR__ . '/bootstrap.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    nutrition_api_error('Method not allowed. Use POST multipart.', 405);
}

$auth = nutrition_api_authenticate($con);
$override = trim((string) ($_POST['barangay_id'] ?? ''));
$ctx = nutrition_api_require_active_barangay($con, $auth, $override !== '' ? $override : null);

$surveyId = trim((string) ($_POST['survey_id'] ?? ''));
$memberId = trim((string) ($_POST['member_id'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));

if ($surveyId === '') {
    nutrition_api_error('survey_id is required.', 422);
}

$survey = nutrition_load_household_survey_by_id($con, $surveyId, $ctx['barangay_id']);
if ($survey === null) {
    nutrition_api_error('Household survey not found.', 404);
}
if (!staff_user_can_access_purok($con, $auth['user_id'], (string) ($survey['purok_label'] ?? ''))) {
    nutrition_api_error('Household survey not found.', 404);
}

if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
    nutrition_api_error('Missing photo file (field name: photo).', 422);
}

$file = $_FILES['photo'];
$err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($err !== UPLOAD_ERR_OK) {
    nutrition_api_error('Photo upload failed (error ' . $err . ').', 400);
}

$tmp = (string) ($file['tmp_name'] ?? '');
$size = (int) ($file['size'] ?? 0);
if ($tmp === '' || !is_uploaded_file($tmp)) {
    nutrition_api_error('Invalid upload.', 400);
}
if ($size <= 0 || $size > 5 * 1024 * 1024) {
    nutrition_api_error('Photo must be 1 byte–5 MB.', 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) ($finfo->file($tmp) ?: '');
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
if (!isset($allowed[$mime])) {
    nutrition_api_error('Only JPEG, PNG, or WebP photos are allowed.', 422);
}
$ext = $allowed[$mime];

if (!barangay_table_exists($con, 'nutrition_survey_attachment')) {
    $con->query(
        "CREATE TABLE IF NOT EXISTS `nutrition_survey_attachment` (
            `attachment_id` VARCHAR(32) NOT NULL,
            `survey_id` VARCHAR(32) NOT NULL,
            `barangay_id` VARCHAR(32) NOT NULL,
            `member_id` VARCHAR(32) NOT NULL DEFAULT '',
            `stored_name` VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL DEFAULT '',
            `mime_type` VARCHAR(64) NOT NULL DEFAULT '',
            `byte_size` INT UNSIGNED NOT NULL DEFAULT 0,
            `note` VARCHAR(255) NOT NULL DEFAULT '',
            `uploaded_by` VARCHAR(64) NOT NULL DEFAULT '',
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`attachment_id`),
            KEY `idx_survey` (`survey_id`),
            KEY `idx_barangay` (`barangay_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

$secureRoot = 'C:\\xampp\\secure\\barangay_uploads\\nutrition';
if (!is_dir($secureRoot) && !@mkdir($secureRoot, 0750, true) && !is_dir($secureRoot)) {
    // Fallback under project (blocked by .htaccess if present).
    $secureRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'nutrition_uploads';
    if (!is_dir($secureRoot) && !@mkdir($secureRoot, 0750, true) && !is_dir($secureRoot)) {
        nutrition_api_error('Could not create upload directory.', 500);
    }
}

$brgyDir = $secureRoot . DIRECTORY_SEPARATOR . preg_replace('/[^a-zA-Z0-9_-]/', '_', $ctx['barangay_id']);
if (!is_dir($brgyDir) && !@mkdir($brgyDir, 0750, true) && !is_dir($brgyDir)) {
    nutrition_api_error('Could not create barangay upload directory.', 500);
}

$attachmentId = (string) hexdec(uniqid());
$storedName = $attachmentId . '.' . $ext;
$dest = $brgyDir . DIRECTORY_SEPARATOR . $storedName;
if (!@move_uploaded_file($tmp, $dest)) {
    nutrition_api_error('Could not store photo.', 500);
}

$original = basename((string) ($file['name'] ?? ('photo.' . $ext)));
$userId = (string) ($auth['user_id'] ?? '');
$stmt = $con->prepare(
    'INSERT INTO nutrition_survey_attachment
     (attachment_id, survey_id, barangay_id, member_id, stored_name, original_name, mime_type, byte_size, note, uploaded_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    @unlink($dest);
    nutrition_api_error('Could not save attachment record.', 500);
}
$stmt->bind_param(
    'sssssssiss',
    $attachmentId,
    $surveyId,
    $ctx['barangay_id'],
    $memberId,
    $storedName,
    $original,
    $mime,
    $size,
    $note,
    $userId
);
if (!$stmt->execute()) {
    $stmt->close();
    @unlink($dest);
    nutrition_api_error('Could not save attachment record.', 500);
}
$stmt->close();

nutrition_api_send([
    'ok' => true,
    'attachment_id' => $attachmentId,
    'survey_id' => $surveyId,
    'member_id' => $memberId,
    'mime_type' => $mime,
    'byte_size' => $size,
    'message' => 'Photo attached.',
]);
