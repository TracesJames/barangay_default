<?php
/**
 * System-wide connection / function / schema health scan.
 * Usage: php tools/_system_health_scan.php
 */

$root = dirname(__DIR__);
$fail = 0;
$pass = 0;
$warn = 0;

function scan_ok(string $label, bool $ok, string $detail = ''): void
{
    global $fail, $pass;
    if ($ok) {
        $pass++;
        echo '[PASS] ' . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
    } else {
        $fail++;
        echo '[FAIL] ' . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
    }
}

function scan_warn(string $label, string $detail = ''): void
{
    global $warn;
    $warn++;
    echo '[WARN] ' . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
}

echo "=== 1. PHP syntax lint (app files) ===" . PHP_EOL;

$skipDirs = [
    DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . '_',
];
$skipFiles = [
    '_tmp_',
    '_diag_',
    '_system_health_scan.php',
];

$phpFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    $skip = false;
    foreach ($skipDirs as $d) {
        if (str_contains($path, $d)) {
            $skip = true;
            break;
        }
    }
    foreach ($skipFiles as $s) {
        if (str_contains(basename($path), $s)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }
    $phpFiles[] = $path;
}

$lintFail = [];
foreach ($phpFiles as $path) {
    $out = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    if ($code !== 0) {
        $lintFail[] = substr($path, strlen($root) + 1) . ': ' . implode(' ', $out);
    }
}
scan_ok('PHP lint', $lintFail === [], count($phpFiles) . ' files');
foreach ($lintFail as $row) {
    echo '       ' . $row . PHP_EOL;
}

echo PHP_EOL . "=== 2. Database connection + core tables ===" . PHP_EOL;
require $root . '/connection.php';
scan_ok('mysqli ping', isset($con) && $con instanceof mysqli && $con->ping());

$requiredTables = [
    'users',
    'barangay_information',
    'residence_information',
    'residence_status',
    'official_information',
    'official_status',
    'position',
    'nutrition_household_survey',
    'nutrition_household_family_member',
    'nutrition_assessment',
    'certificate',
    'blotter_record',
    'activity_log',
];
$missingTables = [];
foreach ($requiredTables as $t) {
    $r = $con->query("SHOW TABLES LIKE '" . $con->real_escape_string($t) . "'");
    if (!$r || $r->num_rows === 0) {
        $missingTables[] = $t;
    }
}
scan_ok('Required tables exist', $missingTables === [], $missingTables === [] ? count($requiredTables) . ' tables' : implode(', ', $missingTables));

$requiredCols = [
    ['residence_status', 'barangay_id'],
    ['official_status', 'barangay_id'],
    ['users', 'staff_role'],
    ['nutrition_household_survey', 'barangay_id'],
    ['nutrition_household_family_member', 'survey_id'],
];
foreach ($requiredCols as [$table, $col]) {
    $r = $con->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    scan_ok("Column {$table}.{$col}", $r && $r->num_rows > 0);
}

echo PHP_EOL . "=== 3. Include files + helper functions ===" . PHP_EOL;
$includes = [
    'includes/helpers.php',
    'includes/barangay_context.php',
    'includes/staff_permissions.php',
    'includes/nutrition_context.php',
    'includes/csrf.php',
    'includes/auth_admin.php',
    'includes/upload_helper.php',
    'includes/nutrition_bnp_reports.php',
    'includes/nutrition_eopt_reports.php',
    'includes/staff_accounts.php',
];
foreach ($includes as $rel) {
    scan_ok($rel, is_file($root . '/' . $rel));
}

require_once $root . '/includes/csrf.php';
require_once $root . '/includes/barangay_context.php';
require_once $root . '/includes/staff_permissions.php';
require_once $root . '/includes/nutrition_context.php';
require_once $root . '/includes/upload_helper.php';

$fns = [
    'barangay_require_active',
    'barangay_list_all',
    'barangay_hub_totals',
    'barangay_get_position_row',
    'barangay_position_limit_reached',
    'official_parse_term_range',
    'official_validate_term_range',
    'barangay_store_image_upload',
    'barangay_user_is_ssa',
    'barangay_user_is_cnpc',
    'barangay_user_can_open_nutrition_city_hub',
    'nutrition_hub_totals',
    'nutrition_scoped_totals',
    'nutrition_survey_children_totals',
    'nutrition_pregnant_count',
    'nutrition_ensure_module_tables',
    'nutrition_children_age_label',
    'staff_role_label',
    'csrf_token',
];
foreach ($fns as $fn) {
    scan_ok("function {$fn}()", function_exists($fn));
}

echo PHP_EOL . "=== 4. Runtime helper calls ===" . PHP_EOL;
try {
    $hub = nutrition_hub_totals($con);
    scan_ok('nutrition_hub_totals()', is_array($hub) && isset($hub['children'], $hub['assessed']));
    $surveyKids = nutrition_survey_children_totals($con);
    scan_ok('nutrition_survey_children_totals()', is_array($surveyKids));
    $preg = nutrition_pregnant_count($con);
    scan_ok('nutrition_pregnant_count()', is_int($preg));
    $barangays = barangay_list_all($con);
    scan_ok('barangay_list_all()', is_array($barangays) && count($barangays) > 0, (string) count($barangays));
    $bh = barangay_hub_totals($con);
    scan_ok('barangay_hub_totals()', is_array($bh));
    nutrition_ensure_module_tables($con);
    scan_ok('nutrition_ensure_module_tables()', true);
} catch (Throwable $e) {
    scan_ok('runtime helper calls', false, $e->getMessage());
}

echo PHP_EOL . "=== 5. Cross-file include/require paths ===" . PHP_EOL;
$missingRequires = [];
$requireRe = '/(?:include|require)(?:_once)?\s*\(?\s*[\'"]([^\'"]+)[\'"]/';
$scanDirs = ['admin', 'secretary', 'resident', 'includes'];
foreach ($scanDirs as $dir) {
    $full = $root . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($full)) {
        continue;
    }
    $dit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS));
    foreach ($dit as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        $src = file_get_contents($file->getPathname());
        if ($src === false) {
            continue;
        }
        if (!preg_match_all($requireRe, $src, $m)) {
            continue;
        }
        $baseDir = dirname($file->getPathname());
        foreach ($m[1] as $inc) {
            if (str_starts_with($inc, 'http') || str_contains($inc, '$')) {
                continue;
            }
            $resolved = $inc;
            if (!preg_match('#^([A-Za-z]:\\\\|/|\\\\\\\\)#', $inc)) {
                $resolved = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $inc);
            }
            $real = realpath($resolved);
            if ($real === false || !is_file($real)) {
                $missingRequires[] = substr($file->getPathname(), strlen($root) + 1) . ' -> ' . $inc;
            }
        }
    }
}
$missingRequires = array_values(array_unique($missingRequires));
scan_ok('Static include/require paths', $missingRequires === [], $missingRequires === [] ? 'ok' : count($missingRequires) . ' missing');
foreach (array_slice($missingRequires, 0, 40) as $row) {
    echo '       ' . $row . PHP_EOL;
}
if (count($missingRequires) > 40) {
    echo '       … ' . (count($missingRequires) - 40) . ' more' . PHP_EOL;
}

echo PHP_EOL . "=== 6. Project function calls vs definitions ===" . PHP_EOL;
$defined = [];
$called = [];
$defRe = '/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/';
$callRe = '/(?<!function\s)(?<!->)(?<!::)\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/';
$ignoreCalls = array_flip([
    'array', 'list', 'unset', 'isset', 'empty', 'echo', 'print', 'exit', 'die',
    'include', 'include_once', 'require', 'require_once', 'return',
    'if', 'elseif', 'while', 'for', 'foreach', 'switch', 'catch', 'match',
    'count', 'in_array', 'array_map', 'array_filter', 'array_sum', 'array_column',
    'array_keys', 'array_values', 'array_unique', 'array_merge', 'array_slice',
    'implode', 'explode', 'preg_match', 'preg_match_all', 'preg_replace',
    'str_contains', 'str_starts_with', 'str_ends_with', 'strlen', 'trim',
    'strtolower', 'strtoupper', 'ucfirst', 'htmlspecialchars', 'json_encode',
    'json_decode', 'date', 'time', 'strtotime', 'round', 'max', 'min', 'abs',
    'intval', 'floatval', 'number_format', 'sprintf', 'vsprintf', 'header',
    'http_response_code', 'session_start', 'session_destroy', 'session_name',
    'session_id', 'session_status', 'session_set_cookie_params',
    'password_hash', 'password_verify', 'hash', 'random_bytes', 'bin2hex',
    'file_get_contents', 'file_put_contents', 'is_file', 'is_dir', 'realpath',
    'dirname', 'basename', 'mkdir', 'unlink', 'copy', 'rename', 'glob',
    'move_uploaded_file', 'pathinfo', 'filesize', 'file_exists',
    'defined', 'define', 'constant', 'function_exists', 'class_exists',
    'method_exists', 'is_array', 'is_string', 'is_int', 'is_float', 'is_bool',
    'is_null', 'is_numeric', 'is_object', 'is_callable', 'gettype',
    'int', 'float', 'string', 'bool', 'void', 'mixed', 'never',
    'mysqli', 'DateTime', 'Exception', 'Throwable', 'RuntimeException',
    'compact', 'extract', 'range', 'shuffle', 'sort', 'ksort', 'usort',
    'md5', 'sha1', 'uniqid', 'rand', 'mt_rand', 'microtime',
    'ob_start', 'ob_end_clean', 'ob_get_clean', 'ob_end_flush',
    'curl_init', 'curl_setopt', 'curl_setopt_array', 'curl_exec', 'curl_close',
    'curl_getinfo', 'curl_error', 'http_build_query', 'parse_url',
    'filter_var', 'filter_input', 'htmlspecialchars_decode',
    'mb_strtoupper', 'mb_strlen', 'mb_substr', 'chr', 'ord',
    'floor', 'ceil', 'pow', 'sqrt', 'intdiv',
    'preg_split', 'str_replace', 'str_pad', 'substr', 'strstr', 'strpos',
    'ltrim', 'rtrim', 'nl2br', 'strip_tags', 'addslashes', 'stripslashes',
    'urlencode', 'urldecode', 'rawurlencode', 'rawurldecode',
    'base64_encode', 'base64_decode', 'gzcompress', 'gzuncompress',
    'fopen', 'fclose', 'fread', 'fwrite', 'fgets', 'feof',
    'scandir', 'opendir', 'readdir', 'closedir',
    'putenv', 'getenv', 'ini_set', 'ini_get', 'error_reporting',
    'set_error_handler', 'set_exception_handler',
    'php_sapi_name', 'phpversion', 'extension_loaded',
    'var_dump', 'print_r', 'debug_backtrace',
    'sleep', 'usleep', 'exec', 'passthru', 'system', 'shell_exec',
    'escapeshellarg', 'escapeshellcmd',
    'preg_quote', 'ctype_digit', 'ctype_alnum', 'ctype_alpha',
    'array_flip', 'array_key_exists', 'array_search', 'end', 'reset',
    'next', 'prev', 'current', 'key', 'each',
    'call_user_func', 'call_user_func_array',
    'get_defined_functions', 'get_included_files',
    'spl_autoload_register', 'class_alias',
    'iterator_to_array', 'iterator_count',
    'tempnam', 'sys_get_temp_dir',
    'preg_last_error', 'json_last_error',
    'setcookie', 'setrawcookie',
    'parse_str', 'http_response_code',
    'flush', 'ob_flush',
    'bccomp', 'bcmul', 'bcadd', 'bcdiv',
    'imagecreatefromjpeg', 'imagecreatefrompng', 'imagejpeg', 'imagepng',
    'imagedestroy', 'imagesx', 'imagesy', 'imagecopyresampled',
    'imagecreatetruecolor', 'imagecolorallocate', 'imagefilledellipse',
    'ZipArchive', 'SimpleXMLElement',
    'PDO', 'mysqli_report',
    'assert_true', 'fail', 'pass', 'http_request', 'http', 'check', 'login',
    'open_barangay', 'bad', 'clean', 'extract_csrf',
    'scan_ok', 'scan_warn',
]);

foreach ($phpFiles as $path) {
    $src = file_get_contents($path);
    if ($src === false) {
        continue;
    }
    if (preg_match_all($defRe, $src, $dm)) {
        foreach ($dm[1] as $fn) {
            $defined[$fn] = true;
        }
    }
    if (preg_match_all($callRe, $src, $cm)) {
        foreach ($cm[1] as $fn) {
            if (isset($ignoreCalls[$fn]) || function_exists($fn)) {
                continue;
            }
            if (!preg_match('/^(barangay_|nutrition_|staff_|official_|csrf_|residence_)/', $fn)) {
                continue;
            }
            $called[$fn][$path] = true;
        }
    }
}

$undefined = [];
foreach ($called as $fn => $files) {
    if (!isset($defined[$fn]) && !function_exists($fn)) {
        $undefined[$fn] = array_keys($files);
    }
}
scan_ok('Project helper functions resolve', $undefined === [], $undefined === [] ? 'ok' : count($undefined) . ' undefined');
foreach ($undefined as $fn => $files) {
    $first = substr($files[0], strlen($root) + 1);
    echo "       {$fn}() from {$first}" . (count($files) > 1 ? ' (+' . (count($files) - 1) . ')' : '') . PHP_EOL;
}

echo PHP_EOL . "=== SUMMARY ===" . PHP_EOL;
echo "Passed: {$pass}  Failed: {$fail}  Warnings: {$warn}" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
