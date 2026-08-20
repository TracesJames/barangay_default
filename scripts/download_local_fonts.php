<?php
/**
 * Download Inter + Source Sans 3 for offline/local hosting (no fonts.googleapis.com).
 *
 * php scripts/download_local_fonts.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__);
$fontsDir = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts';

$sets = [
    'source-sans-3' => 'https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap',
    'inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
];

function fetch_css(string $url): string
{
    $ctx = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);

    return is_string($body) ? $body : '';
}

function extract_font_urls(string $css): array
{
    preg_match_all('/url\((https:\/\/fonts\.gstatic\.com[^)]+)\)/', $css, $m);

    return array_values(array_unique($m[1] ?? []));
}

$faceRules = [];
foreach ($sets as $folder => $cssUrl) {
    $targetDir = $fontsDir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        fwrite(STDERR, "Cannot create {$targetDir}\n");
        exit(1);
    }

    $css = fetch_css($cssUrl);
    if ($css === '') {
        fwrite(STDERR, "Failed to fetch CSS for {$folder}\n");
        exit(1);
    }

    $urls = extract_font_urls($css);
    $localCss = $css;
    foreach ($urls as $i => $url) {
        $ext = str_contains($url, '.woff2') ? 'woff2' : (str_contains($url, '.woff') ? 'woff' : 'ttf');
        $file = $folder . '-' . ($i + 1) . '.' . $ext;
        $dest = $targetDir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($dest)) {
            $bin = @file_get_contents($url, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]]));
            if ($bin === false) {
                fwrite(STDERR, "Failed download: {$url}\n");
                exit(1);
            }
            file_put_contents($dest, $bin);
            echo "Downloaded {$file}\n";
        }
        $localCss = str_replace($url, '../fonts/' . $folder . '/' . $file, $localCss);
    }

    $faceRules[] = "/* {$folder} */\n" . trim($localCss);
}

$outCss = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'local-fonts.css';
$header = "/**\n * Self-hosted fonts — no external fonts.googleapis.com / fonts.gstatic.com\n * Regenerate: php scripts/download_local_fonts.php\n */\n\n";
file_put_contents($outCss, $header . implode("\n\n", $faceRules) . "\n");
echo "Wrote {$outCss}\n";
