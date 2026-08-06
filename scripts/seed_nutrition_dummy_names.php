<?php

/**
 * Seed dummy BNS / nutrition officer names per barangay
 * and sample MELLPI City Profile registration data.
 *
 * Usage: php scripts/seed_nutrition_dummy_names.php
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';
require_once dirname(__DIR__) . '/includes/nutrition_context.php';
require_once dirname(__DIR__) . '/includes/nutrition_mellpi.php';

nutrition_ensure_module_tables($con);
nutrition_mellpi_ensure_table($con);

$firstNames = [
    'Maria', 'Ana', 'Rosa', 'Liza', 'Jenny', 'Grace', 'Helen', 'Carmen', 'Nora', 'Fe',
    'Cristina', 'Rowena', 'Marites', 'Gloria', 'Evelyn', 'Josefa', 'Lorna', 'Myrna', 'Susan', 'Teresa',
    'Raquel', 'Irene', 'Cecilia', 'Corazon', 'Imelda', 'Lourdes', 'Maricel', 'Nancy', 'Olivia', 'Patricia',
];
$lastNames = [
    'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Gonzales', 'Ramos',
    'Lopez', 'Diaz', 'Castillo', 'Villanueva', 'Aquino', 'Navarro', 'Perez', 'Rivera', 'Morales', 'Del Rosario',
    'Fernandez', 'Castro', 'Gutierrez', 'Salazar', 'Padilla', 'Mercado', 'Domingo', 'Aguilar', 'Santiago', 'Valdez',
];

$barangays = barangay_list_all($con);
$updatedOfficers = 0;
$i = 0;

foreach ($barangays as $brgy) {
    $id = (string) ($brgy['id'] ?? '');
    $name = (string) ($brgy['barangay'] ?? '');
    if ($id === '' || barangay_is_placeholder_name($name)) {
        continue;
    }

    $settings = nutrition_load_settings($con, $id, $name);
    $officer = trim((string) ($settings['nutrition_officer'] ?? ''));
    if ($officer !== '') {
        continue;
    }

    $first = $firstNames[$i % count($firstNames)];
    $last = $lastNames[$i % count($lastNames)];
    $dummy = $first . ' ' . $last;
    $i++;

    $settings['nutrition_officer'] = $dummy;
    if (($settings['report_header'] ?? '') === '') {
        $settings['report_header'] = 'Barangay ' . $name . ' Nutrition Profiling';
    }
    if (nutrition_save_settings($con, $id, $settings)) {
        $updatedOfficers++;
        echo "BNS name: {$name} -> {$dummy}\n";
    }
}

$year = (int) date('Y');
$y1 = $year - 2;
$y2 = $year - 1;
$y3 = $year;

$mellpi = [
    'city_name' => 'City of Valencia',
    'province' => 'Bukidnon',
    'income_class' => '1st Class',
    'date_of_monitoring' => date('Y-m-d'),
    'period_covered' => 'CY ' . $year,
    'community' => [
        'income_classification' => '1st Class Component City',
        'hh_safe_water' => '18540',
        'hh_sanitary_toilets' => '17220',
        'day_care_centers' => '42',
        'public_elementary_schools' => '38',
        'public_secondary_schools' => '12',
        'barangay_health_stations' => '31',
        'retail_outlets' => '520',
        'bakeries' => '48',
        'public_markets' => '3',
        'transport_terminals' => '2',
        'pct_at_risk_pregnant' => '8.5',
        'pct_exclusive_bf_5th_month' => '62.3',
        'idd_pregnant' => '2.1',
        'idd_lactating' => '1.8',
        'terrain' => 'Plateau / rolling hills',
    ],
    'population_snapshot' => [
        '0_59_estimated' => '18500',
        '0_59_actual' => '186',
        'pregnant_estimated' => '980',
        'pregnant_actual' => '62',
        'lactating_estimated' => '1120',
        'lactating_actual' => '31',
    ],
    'years' => [$y1, $y2, $y3],
    'preschool' => [
        'wfa' => [
            'Normal' => [$y1 => '8200', $y2 => '8450', $y3 => '8600'],
            'Underweight' => [$y1 => '610', $y2 => '580', $y3 => '560'],
            'Severely Underweight' => [$y1 => '95', $y2 => '88', $y3 => '82'],
            'Overweight' => [$y1 => '210', $y2 => '225', $y3 => '235'],
        ],
        'wfh' => [
            'Normal' => [$y1 => '8500', $y2 => '8700', $y3 => '8850'],
            'Wasted' => [$y1 => '320', $y2 => '305', $y3 => '290'],
            'Severely Wasted' => [$y1 => '55', $y2 => '48', $y3 => '42'],
            'Overweight' => [$y1 => '180', $y2 => '195', $y3 => '205'],
            'Obese' => [$y1 => '70', $y2 => '75', $y3 => '80'],
        ],
        'hfa' => [
            'Normal' => [$y1 => '7800', $y2 => '8050', $y3 => '8200'],
            'Stunted' => [$y1 => '980', $y2 => '940', $y3 => '910'],
            'Severely Stunted' => [$y1 => '140', $y2 => '125', $y3 => '118'],
            'Tall' => [$y1 => '90', $y2 => '95', $y3 => '98'],
        ],
    ],
    'school' => [
        'Normal' => [$y1 => '11200', $y2 => '11450', $y3 => '11600'],
        'Wasted' => [$y1 => '410', $y2 => '395', $y3 => '380'],
        'Severely Wasted' => [$y1 => '62', $y2 => '58', $y3 => '55'],
        'Overweight' => [$y1 => '320', $y2 => '340', $y3 => '355'],
        'Obese' => [$y1 => '110', $y2 => '118', $y3 => '125'],
    ],
    'pregnant_status' => [
        'Normal' => [$y1 => '720', $y2 => '745', $y3 => '760'],
        'Nutritionally at-risk' => [$y1 => '95', $y2 => '88', $y3 => '82'],
        'Overweight' => [$y1 => '70', $y2 => '75', $y3 => '78'],
        'Obese' => [$y1 => '25', $y2 => '28', $y3 => '30'],
    ],
    'bns' => [
        'total' => '31',
        'new' => '3',
        'existing' => '28',
    ],
    'hazards' => [
        ['type_month' => 'Flooding / June–September', 'affected' => 'Brgy. Batangan, Lurogan — ~420 HH'],
        ['type_month' => 'Landslide / July–October', 'affected' => 'Brgy. Mt. Nebo, Lourdes — ~85 HH'],
        ['type_month' => 'Drought / March–May', 'affected' => 'Agricultural barangays — citywide advisory'],
        ['type_month' => '', 'affected' => ''],
        ['type_month' => '', 'affected' => ''],
    ],
    'land_use' => [
        'Residential' => ['land_area' => '3,250 ha', 'bgy_covered' => '31', 'remarks' => 'Urban + peri-urban'],
        'Commercial' => ['land_area' => '420 ha', 'bgy_covered' => '8', 'remarks' => 'Poblacion / market areas'],
        'Industrial' => ['land_area' => '180 ha', 'bgy_covered' => '3', 'remarks' => 'Agro-industrial'],
        'Agricultural' => ['land_area' => '18,600 ha', 'bgy_covered' => '24', 'remarks' => 'Pineapple / sugarcane / rice'],
        'Forest land/Mineral land/National Park' => ['land_area' => '4,100 ha', 'bgy_covered' => '6', 'remarks' => 'Watershed / forest reserves'],
    ],
];

if (nutrition_mellpi_save_profile($con, $mellpi, 'seed')) {
    echo "MELLPI city profile dummy data saved.\n";
} else {
    echo "Failed to save MELLPI dummy data.\n";
}

echo "Done. Updated {$updatedOfficers} barangay BNS/officer name(s).\n";
