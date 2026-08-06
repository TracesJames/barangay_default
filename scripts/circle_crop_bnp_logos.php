<?php

/**
 * Re-copy source logos and convert to perfect transparent circles.
 */

$srcDir = 'C:/Users/ICT Valencia/.cursor/projects/c-xampp-htdocs-barangay-default/assets';
$logoDir = __DIR__ . '/../assets/logo';

function bnp_find_upload(string $dir, string $needle): ?string
{
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (str_contains($file, $needle) && is_file($dir . '/' . $file)) {
            return $dir . '/' . $file;
        }
    }

    return null;
}

function bnp_is_near_black(int $r, int $g, int $b, int $threshold = 40): bool
{
    return $r <= $threshold && $g <= $threshold && $b <= $threshold;
}

function bnp_is_checker_or_light(int $r, int $g, int $b): bool
{
    // Neutral light gray/white (baked checkerboard + paper).
    $neutral = abs($r - $g) <= 18 && abs($g - $b) <= 18 && abs($r - $b) <= 18;
    return $neutral && $r >= 175;
}

/**
 * @return resource|\GdImage
 */
function bnp_load_image(string $path)
{
    $info = getimagesize($path);
    if ($info === false) {
        throw new RuntimeException('Invalid image: ' . $path);
    }
    $img = match ($info[2]) {
        IMAGETYPE_PNG => imagecreatefrompng($path),
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        default => false,
    };
    if ($img === false) {
        throw new RuntimeException('Unable to load: ' . $path);
    }

    return $img;
}

/**
 * @param resource|\GdImage $src
 * @return array{0:int,1:int,2:int,3:int}
 */
function bnp_content_bounds($src, callable $isBackground): array
{
    $w = imagesx($src);
    $h = imagesy($src);
    $minX = $w;
    $minY = $h;
    $maxX = 0;
    $maxY = 0;
    $found = false;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($src, $x, $y);
            $a = ($rgba >> 24) & 0x7F;
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if ($a >= 120) {
                continue;
            }
            if ($isBackground($r, $g, $b)) {
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
        return [0, 0, $w - 1, $h - 1];
    }

    $pad = 4;
    return [
        max(0, $minX - $pad),
        max(0, $minY - $pad),
        min($w - 1, $maxX + $pad),
        min($h - 1, $maxY + $pad),
    ];
}

function bnp_make_circle_logo(string $src, string $dest, string $mode, int $size = 640): void
{
    $srcImg = bnp_load_image($src);
    $sw = imagesx($srcImg);
    $sh = imagesy($srcImg);

    $isBg = $mode === 'valencia'
        ? static fn (int $r, int $g, int $b): bool => bnp_is_near_black($r, $g, $b, 42)
        : static fn (int $r, int $g, int $b): bool => bnp_is_near_black($r, $g, $b, 30) || bnp_is_checker_or_light($r, $g, $b);

    [$x1, $y1, $x2, $y2] = bnp_content_bounds($srcImg, $isBg);
    $bw = $x2 - $x1 + 1;
    $bh = $y2 - $y1 + 1;
    $side = max($bw, $bh);

    $square = imagecreatetruecolor($side, $side);
    imagealphablending($square, false);
    imagesavealpha($square, true);
    $transparent = imagecolorallocatealpha($square, 0, 0, 0, 127);
    imagefilledrectangle($square, 0, 0, $side, $side, $transparent);

    $ox = (int) (($side - $bw) / 2);
    $oy = (int) (($side - $bh) / 2);

    for ($y = 0; $y < $bh; $y++) {
        for ($x = 0; $x < $bw; $x++) {
            $rgba = imagecolorat($srcImg, $x1 + $x, $y1 + $y);
            $a = ($rgba >> 24) & 0x7F;
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if ($a >= 120 || $isBg($r, $g, $b)) {
                imagesetpixel($square, $ox + $x, $oy + $y, $transparent);
            } else {
                $color = imagecolorallocatealpha($square, $r, $g, $b, 0);
                imagesetpixel($square, $ox + $x, $oy + $y, $color);
            }
        }
    }
    imagedestroy($srcImg);

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefilledrectangle($out, 0, 0, $size, $size, $transparent);
    imagealphablending($out, true);
    imagecopyresampled($out, $square, 0, 0, 0, 0, $size, $size, $side, $side);
    imagedestroy($square);

    imagealphablending($out, false);
    $cx = ($size - 1) / 2.0;
    $cy = ($size - 1) / 2.0;
    $radius = ($size / 2.0) - 1.0;
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $dist = sqrt((($x - $cx) ** 2) + (($y - $cy) ** 2));
            if ($dist > $radius + 0.8) {
                imagesetpixel($out, $x, $y, $transparent);
                continue;
            }
            if ($dist > $radius - 0.8) {
                $rgba = imagecolorat($out, $x, $y);
                $a = ($rgba >> 24) & 0x7F;
                if ($a >= 120) {
                    imagesetpixel($out, $x, $y, $transparent);
                    continue;
                }
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $fade = max(0, min(127, (int) round((($dist - ($radius - 0.8)) / 1.6) * 127)));
                imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, $fade));
            }
        }
    }

    imagepng($out, $dest, 6);
    imagedestroy($out);
}

$nncSrc = bnp_find_upload($srcDir, 'g9onogg9onogg9on');
$valSrc = bnp_find_upload($srcDir, 'kisspng-valencia-city');
if ($nncSrc === null || $valSrc === null) {
    fwrite(STDERR, "Source uploads not found.\n");
    exit(1);
}

file_put_contents($logoDir . '/national-nutrition-council.png', file_get_contents($nncSrc));
file_put_contents($logoDir . '/valencia-city.png', file_get_contents($valSrc));

bnp_make_circle_logo($logoDir . '/valencia-city.png', $logoDir . '/valencia-city.png', 'valencia');
echo "valencia-city.png ready\n";
bnp_make_circle_logo($logoDir . '/national-nutrition-council.png', $logoDir . '/national-nutrition-council.png', 'nnc');
echo "national-nutrition-council.png ready\n";
echo "Done.\n";
