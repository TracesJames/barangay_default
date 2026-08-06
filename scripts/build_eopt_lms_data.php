<?php
/**
 * CLI: Parse eOPT raw text dumps into PHP LMS data file.
 * php scripts/build_eopt_lms_data.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden\n");
}

$base = dirname(__DIR__);
$sources = [
    'Female' => $base . '/scripts/data/eopt_female_raw.txt',
    'Male' => $base . '/scripts/data/eopt_male_raw.txt',
];
$outPhp = $base . '/includes/nutrition_eopt_lms_data.php';

/**
 * @return array{0:float,1:float,2:float,3:float}|null
 */
function eopt_pack_lms(array $n, int $offset): ?array
{
    if (!isset($n[$offset + 3])) {
        return null;
    }
    return [
        (float) $n[$offset],
        (float) $n[$offset + 1],
        (float) $n[$offset + 2],
        (float) $n[$offset + 3],
    ];
}

/**
 * @param array<int, float|string> $nums
 * @return array<string, array<string, array{0:float,1:float,2:float,3:float}>>
 */
function eopt_parse_sex_file(string $path): array
{
    $tables = [
        'wfl' => [],
        'wfh' => [],
        'hfa' => [],
        'wfa' => [],
        'muac' => [],
    ];

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException("Cannot read $path");
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || !preg_match('/^\d/', $line)) {
            continue;
        }
        if (!preg_match_all('/-?\d+(?:\.\d+)?/', $line, $m)) {
            continue;
        }
        $n = $m[0];
        $c = count($n);

        // 25: WFL + WFH + HFA + WFA + MUAC
        // 20: WFL + HFA + WFA + MUAC
        // 15: HFA + WFA + MUAC
        // 10: HFA + WFA
        if ($c === 25) {
            $len = (float) $n[0];
            $lms = eopt_pack_lms($n, 1);
            if ($lms) {
                $tables['wfl'][(string) (int) round($len * 10)] = $lms;
            }
            $ht = (float) $n[5];
            $lms = eopt_pack_lms($n, 6);
            if ($lms) {
                $tables['wfh'][(string) (int) round($ht * 10)] = $lms;
            }
            $day = (string) (int) $n[10];
            $lms = eopt_pack_lms($n, 11);
            if ($lms) {
                $tables['hfa'][$day] = $lms;
            }
            $day = (string) (int) $n[15];
            $lms = eopt_pack_lms($n, 16);
            if ($lms) {
                $tables['wfa'][$day] = $lms;
            }
            $day = (string) (int) $n[20];
            $lms = eopt_pack_lms($n, 21);
            if ($lms) {
                $tables['muac'][$day] = $lms;
            }
        } elseif ($c === 20) {
            $len = (float) $n[0];
            // Distinguish WFL (length ~45-120) vs day-leading rows: first token with decimal often length
            if (str_contains((string) $n[0], '.') || $len >= 40.0 && $len <= 130.0 && (float) $n[1] < 2) {
                $lms = eopt_pack_lms($n, 1);
                if ($lms) {
                    $tables['wfl'][(string) (int) round($len * 10)] = $lms;
                }
                $day = (string) (int) $n[5];
                $lms = eopt_pack_lms($n, 6);
                if ($lms) {
                    $tables['hfa'][$day] = $lms;
                }
                $day = (string) (int) $n[10];
                $lms = eopt_pack_lms($n, 11);
                if ($lms) {
                    $tables['wfa'][$day] = $lms;
                }
                $day = (string) (int) $n[15];
                $lms = eopt_pack_lms($n, 16);
                if ($lms) {
                    $tables['muac'][$day] = $lms;
                }
            }
        } elseif ($c === 15) {
            $day = (string) (int) $n[0];
            $lms = eopt_pack_lms($n, 1);
            if ($lms) {
                $tables['hfa'][$day] = $lms;
            }
            $day = (string) (int) $n[5];
            $lms = eopt_pack_lms($n, 6);
            if ($lms) {
                $tables['wfa'][$day] = $lms;
            }
            $day = (string) (int) $n[10];
            $lms = eopt_pack_lms($n, 11);
            if ($lms) {
                $tables['muac'][$day] = $lms;
            }
        } elseif ($c === 10) {
            $day = (string) (int) $n[0];
            $lms = eopt_pack_lms($n, 1);
            if ($lms) {
                $tables['hfa'][$day] = $lms;
            }
            $day = (string) (int) $n[5];
            $lms = eopt_pack_lms($n, 6);
            if ($lms) {
                $tables['wfa'][$day] = $lms;
            }
        }
    }

    return $tables;
}

$data = [];
foreach ($sources as $sex => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing $path\n");
        exit(1);
    }
    $data[$sex] = eopt_parse_sex_file($path);
    foreach ($data[$sex] as $ind => $rows) {
        echo "$sex $ind rows=" . count($rows) . "\n";
    }
}

$export = var_export($data, true);
$php = <<<PHP
<?php
/**
 * Region 10 eOPT Plus ver2 LMS reference tables (Female + Male).
 * Auto-built by scripts/build_eopt_lms_data.php — do not edit by hand.
 *
 * Structure: [sex][indicator][indexKey] = [L, M, S, SD]
 * Indicators: wfl (length cm*10), wfh (height cm*10), hfa/wfa/muac (age days as string)
 */
return {$export};

PHP;

if (file_put_contents($outPhp, $php) === false) {
    fwrite(STDERR, "Cannot write $outPhp\n");
    exit(1);
}

echo "Wrote $outPhp (" . filesize($outPhp) . " bytes)\n";
