<?php
/**
 * Seed Valencia City barangays into barangay_information.
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

$barangays = [
    'Bagontaas',
    'Banlag',
    'Barobo',
    'Batangan',
    'Catumbalon',
    'Colonia',
    'Concepcion',
    'Dagat-Kidavao',
    'Guinoyuran',
    'Kahapunan',
    'Laligan',
    'Lilingayon',
    'Lourdes',
    'Lumbayao',
    'Lumbo',
    'Lurogan',
    'Maapag',
    'Mabuhay',
    'Mailag',
    'Mt. Nebo',
    'Nabago',
    'Pinatilan',
    'Poblacion',
    'San Carlos',
    'San Isidro',
    'Sinabuagan',
    'Sinayawan',
    'Sugod',
    'Tongantongan',
    'Tugaya',
    'Vintar',
];

$zone = 'PUROK';
$district = 'Valencia City';
$address = 'Valencia City, Bukidnon';
$postal = 'Valencia City, Bukidnon';
$logo = 'logo.png';
$logoPath = '../assets/dist/img/logo.png';

$inserted = 0;
$skipped = 0;

$check = $con->prepare('SELECT id FROM barangay_information WHERE barangay = ? LIMIT 1');
$insert = $con->prepare(
    'INSERT INTO barangay_information (id, barangay, zone, district, address, postal_address, image, image_path)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

foreach ($barangays as $name) {
    $check->bind_param('s', $name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "Skipped (exists): $name\n";
        $skipped++;
        continue;
    }

    $id = barangay_generate_id();
    $insert->bind_param('ssssssss', $id, $name, $zone, $district, $address, $postal, $logo, $logoPath);
    $insert->execute();
    echo "Added: $name ($id)\n";
    $inserted++;
}

$total = $con->query('SELECT COUNT(*) AS c FROM barangay_information')->fetch_assoc()['c'] ?? 0;
echo "\nDone. Inserted: $inserted, Skipped: $skipped, Total in database: $total\n";
