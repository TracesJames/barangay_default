<?php
/**
 * Seed official PSA PSGC codes for Valencia City barangays.
 *
 * @see https://psa.gov.ph/classification/psgc/barangays/1001321000
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

barangay_ensure_psgc_column($con);
$result = barangay_seed_psgc_codes($con);

echo "PSGC seed complete.\n";
echo "Updated: {$result['updated']}\n";
echo "Skipped (already set): {$result['skipped']}\n";

if (!empty($result['missing'])) {
    echo "No PSGC mapping for:\n";
    foreach ($result['missing'] as $name) {
        echo "  - {$name}\n";
    }
}

echo "\nBarangay PSGC codes:\n";
foreach (barangay_list_all($con) as $row) {
    $name = (string) ($row['barangay'] ?? '');
    $code = trim((string) ($row['psgc_code'] ?? ''));
    if ($code === '') {
        $code = barangay_psgc_lookup_by_name($name);
    }
    echo sprintf("  %-18s %s\n", $name, $code !== '' ? $code : '(not set)');
}
