<?php

/**
 * Install preferred Valencia LGU mark (seal + "City of Valencia" text).
 * php scripts/process_lgu_logo.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__);
$logoDir = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'logo';
$src = $logoDir . DIRECTORY_SEPARATOR . '_lgu_source.png';
$upload = 'C:/Users/ICT Valencia/.cursor/projects/c-xampp-htdocs-barangay-default/assets/c__Users_ICT_Valencia_AppData_Roaming_Cursor_User_workspaceStorage_7f4eedf1ae6c8efab100cb8ece7ec884_images_image-60dac53f-6a66-47ff-abf7-a3a5cb1de03a.png';

if (is_file($upload)) {
    copy($upload, $src);
}

if (!is_file($src)) {
    fwrite(STDERR, "Source LGU image not found.\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "PHP GD required.\n");
    exit(1);
}

$info = getimagesize($src);
$img = match ($info[2] ?? 0) {
    IMAGETYPE_PNG => imagecreatefrompng($src),
    IMAGETYPE_JPEG => imagecreatefromjpeg($src),
    default => false,
};
if ($img === false) {
    fwrite(STDERR, "Cannot load source.\n");
    exit(1);
}

$sw = imagesx($img);
$sh = imagesy($img);

// Detect near-white / near-black background and make transparent; keep seal + green text.
$isBg = static function (int $r, int $g, int $b): bool {
    // Pure / near white paper
    if ($r >= 245 && $g >= 245 && $b >= 245) {
        return true;
    }
    // Near black block
    if ($r <= 28 && $g <= 28 && $b <= 28) {
        return true;
    }
    // Light gray paper
    $neutral = abs($r - $g) <= 12 && abs($g - $b) <= 12 && abs($r - $b) <= 12;

    return $neutral && $r >= 235;
};

$minX = $sw;
$minY = $sh;
$maxX = 0;
$maxY = 0;
$found = false;
for ($y = 0; $y < $sh; $y++) {
    for ($x = 0; $x < $sw; $x++) {
        $c = imagecolorat($img, $x, $y);
        $a = ($c >> 24) & 0x7F;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($a >= 120 || $isBg($r, $g, $b)) {
            continue;
        }
        $found = true;
        $minX = min($minX, $x);
        $minY = min($minY, $y);
        $maxX = max($maxX, $x);
        $maxY = max($maxY, $y);
    }
}
if (!$found) {
    $minX = 0;
    $minY = 0;
    $maxX = $sw - 1;
    $maxY = $sh - 1;
}

$pad = 2;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($sw - 1, $maxX + $pad);
$maxY = min($sh - 1, $maxY + $pad);
$bw = $maxX - $minX + 1;
$bh = $maxY - $minY + 1;

// Upscale for print clarity while keeping aspect (seal + caption).
$targetH = 320;
$scale = $targetH / $bh;
$tw = max(1, (int) round($bw * $scale));
$th = $targetH;

$out = imagecreatetruecolor($tw, $th);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $tw, $th, $transparent);

// Nearest-neighbor via manual sample for crisp small source text
for ($y = 0; $y < $th; $y++) {
    for ($x = 0; $x < $tw; $x++) {
        $sx = $minX + (int) floor($x / $scale);
        $sy = $minY + (int) floor($y / $scale);
        if ($sx > $maxX) {
            $sx = $maxX;
        }
        if ($sy > $maxY) {
            $sy = $maxY;
        }
        $c = imagecolorat($img, $sx, $sy);
        $a = ($c >> 24) & 0x7F;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($a >= 120 || $isBg($r, $g, $b)) {
            imagesetpixel($out, $x, $y, $transparent);
        } else {
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, 0));
        }
    }
}
imagedestroy($img);

$dest = $logoDir . DIRECTORY_SEPARATOR . 'valencia-city-lgu.png';
imagepng($out, $dest, 6);
imagedestroy($out);

@unlink($logoDir . DIRECTORY_SEPARATOR . 'valencia-city-lgu.jpg');

echo "Wrote {$dest} ({$tw}x{$th})\n";
