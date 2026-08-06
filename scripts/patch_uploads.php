<?php

/**
 * Replace legacy image upload blocks with hardened upload helper.
 */

$base = dirname(__DIR__);

$files = [
    $base . '/admin/addNewOfficial.php' => 'add_image',
    $base . '/admin/addAdministrator.php' => 'image',
    $base . '/admin/addNewResidence.php' => 'add_image',
    $base . '/admin/editOfficial.php' => 'edit_image',
    $base . '/admin/editResidence.php' => 'edit_image',
    $base . '/admin/editEndOfficial.php' => 'edit_image',
    $base . '/admin/editUserAdministrator.php' => 'edit_image',
    $base . '/admin/updateSettings.php' => 'logo',
    $base . '/secretary/addNewResidence.php' => 'add_image',
    $base . '/secretary/editOfficial.php' => 'edit_image',
    $base . '/secretary/editResidence.php' => 'edit_image',
    $base . '/signup/newResidence.php' => 'add_image_residence',
    $base . '/resident/editResidence.php' => 'edit_image',
];

$snippet = <<<'PHP'

require_once '../includes/upload_helper.php';
$new_image_name = '';
$new_image_path = '';
if (!empty($_FILES['__FIELD__']['name'])) {
    $upload = barangay_store_image_upload($_FILES['__FIELD__']);
    if (!$upload['ok']) {
        exit('errorImage');
    }
    $new_image_name = $upload['filename'];
    $new_image_path = $upload['path'];
}

PHP;

$signupSnippet = str_replace("../includes/", "includes/", $snippet);

foreach ($files as $file => $field) {
    if (!file_exists($file)) {
        echo "Missing: $file\n";
        continue;
    }
    $content = file_get_contents($file);
    if (str_contains($content, 'barangay_store_image_upload')) {
        echo "Skip (already patched): " . basename($file) . "\n";
        continue;
    }

    $replacement = str_replace('__FIELD__', $field, str_contains($file, 'signup') ? $signupSnippet : $snippet);

    $pattern = '/if\s*\(\s*isset\s*\(\s*\$[a-zA-Z_]+\s*\)\s*\)\s*\{[\s\S]*?move_uploaded_file\s*\([\s\S]*?\}[\s\S]*?\}/';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, trim($replacement), $content, 1);
        file_put_contents($file, $content);
        echo "Patched upload: " . basename($file) . "\n";
    } else {
        echo "No upload block found: " . basename($file) . "\n";
    }
}

echo "Done.\n";
