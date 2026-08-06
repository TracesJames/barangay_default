<?php

const BARANGAY_UPLOAD_DIR = __DIR__ . '/../assets/uploads';
const BARANGAY_UPLOAD_WEB_PATH = '../assets/uploads/';

const BARANGAY_ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const BARANGAY_ALLOWED_IMAGE_MIMES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
];

if (!function_exists('barangay_ensure_upload_dir')) {
    function barangay_ensure_upload_dir(): void
    {
        if (!is_dir(BARANGAY_UPLOAD_DIR)) {
            mkdir(BARANGAY_UPLOAD_DIR, 0755, true);
        }
        $htaccess = BARANGAY_UPLOAD_DIR . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar)$\">\n  Require all denied\n</FilesMatch>\n");
        }
    }
}

if (!function_exists('barangay_validate_image_upload')) {
    function barangay_validate_image_upload(array $file, int $maxBytes = 5242880): array
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file uploaded'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed'];
        }
        if ($file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'File too large'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, BARANGAY_ALLOWED_IMAGE_MIMES, true)) {
            return ['ok' => false, 'error' => 'Invalid file type'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, BARANGAY_ALLOWED_IMAGE_EXTENSIONS, true)) {
            return ['ok' => false, 'error' => 'Invalid file extension'];
        }

        if (@getimagesize($file['tmp_name']) === false) {
            return ['ok' => false, 'error' => 'Invalid image file'];
        }

        return ['ok' => true, 'ext' => $ext, 'mime' => $mime];
    }
}

if (!function_exists('barangay_store_image_upload')) {
    function barangay_store_image_upload(array $file): array
    {
        $validation = barangay_validate_image_upload($file);
        if (!$validation['ok']) {
            return $validation;
        }

        barangay_ensure_upload_dir();
        $newName = bin2hex(random_bytes(16)) . '.' . $validation['ext'];
        $destPath = BARANGAY_UPLOAD_DIR . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'error' => 'Could not save file'];
        }

        return [
            'ok' => true,
            'filename' => $newName,
            'path' => BARANGAY_UPLOAD_WEB_PATH . $newName,
            'absolute_path' => $destPath,
        ];
    }
}
