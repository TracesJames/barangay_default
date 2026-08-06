<?php

/**
 * Seed dummy nutrition assessments for children (0–19) across all barangays.
 *
 * Usage: php scripts/seed_nutrition_dummy_assessments.php
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';
require_once dirname(__DIR__) . '/includes/nutrition_context.php';

nutrition_ensure_module_tables($con);

$con->query("DELETE FROM nutrition_assessment WHERE remarks LIKE 'Dummy seed assessment%'");

$maxAge = nutrition_child_max_age_years();
$ageCondition = nutrition_children_age_condition('ri');
$statusPool = [
    'normal', 'normal', 'normal', 'normal',
    'underweight', 'wasted', 'stunted',
    'severely_wasted', 'overweight', 'obese', 'normal',
];

$sql = "SELECT ri.residence_id, ri.first_name, ri.last_name, ri.birth_date, ri.age, ri.gender, rs.barangay_id
    FROM residence_information ri
    INNER JOIN residence_status rs ON ri.residence_id = rs.residence_id
    WHERE rs.archive = 'NO' AND {$ageCondition}
    ORDER BY rs.barangay_id, ri.last_name, ri.first_name";

$result = $con->query($sql);
if (!$result) {
    fwrite(STDERR, "Query failed: {$con->error}\n");
    exit(1);
}

$children = $result->fetch_all(MYSQLI_ASSOC);
$totalChildren = count($children);
if ($totalChildren === 0) {
    echo "No children (0–{$maxAge}) found to assess.\n";
    exit(0);
}

// Assess ~70% so Pending Assessment remains visible.
$targetCount = max(1, (int) floor($totalChildren * 0.7));
$created = 0;
$byStatus = array_fill_keys(array_keys(nutrition_status_options()), 0);
$assessmentDate = date('Y-m-d');
$monthStart = date('Y-m-01');

$stmt = $con->prepare(
    'INSERT INTO nutrition_assessment
     (assessment_id, residence_id, barangay_id, assessment_date, weight_kg, height_cm, bmi, muac_cm, nutritional_status, remarks, assessed_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    fwrite(STDERR, "Prepare failed: {$con->error}\n");
    exit(1);
}

foreach ($children as $index => $child) {
    if ($created >= $targetCount) {
        break;
    }

    // Skip every ~3rd child to leave pending cases.
    if ($index % 10 === 3 || $index % 10 === 7 || $index % 10 === 9) {
        continue;
    }

    $residenceId = (string) ($child['residence_id'] ?? '');
    $barangayId = (string) ($child['barangay_id'] ?? '');
    if ($residenceId === '' || $barangayId === '') {
        continue;
    }

    $ageYears = nutrition_resident_age_years($child);
    $status = $statusPool[$index % count($statusPool)];

    // Age-based anthropometrics for plausible demo values.
    $weight = max(3.0, round(8 + ($ageYears * 2.4) + (($index % 5) * 0.35), 2));
    $height = max(50.0, round(70 + ($ageYears * 6.2) + (($index % 4) * 0.8), 1));
    if ($status === 'underweight' || $status === 'wasted' || $status === 'severely_wasted') {
        $weight = max(3.0, round($weight * 0.78, 2));
    } elseif ($status === 'overweight' || $status === 'obese') {
        $weight = round($weight * 1.28, 2);
    }
    if ($status === 'stunted') {
        $height = max(50.0, round($height * 0.88, 1));
    }

    $bmi = nutrition_calculate_bmi($weight, $height);
    $bmiValue = $bmi !== null ? (string) $bmi : null;
    $muac = $ageYears < 5 ? round(12.5 + (($index % 6) * 0.4), 1) : null;
    $muacValue = $muac !== null ? (string) $muac : null;

    // Spread some assessments earlier this month / last month for variety.
    $dateOffset = $index % 5;
    if ($dateOffset === 0) {
        $date = date('Y-m-d', strtotime($monthStart . ' +' . ($index % 12) . ' days'));
    } elseif ($dateOffset === 1) {
        $date = date('Y-m-d', strtotime('-20 days'));
    } else {
        $date = $assessmentDate;
    }

    $assessmentId = (string) hexdec(uniqid());
    $remarks = 'Dummy seed assessment for nutrition demo reports.';
    $assessedBy = 'seed';
    $weightStr = (string) $weight;
    $heightStr = (string) $height;

    $stmt->bind_param(
        'sssssssssss',
        $assessmentId,
        $residenceId,
        $barangayId,
        $date,
        $weightStr,
        $heightStr,
        $bmiValue,
        $muacValue,
        $status,
        $remarks,
        $assessedBy
    );

    if (!$stmt->execute()) {
        echo "Skip {$residenceId}: {$stmt->error}\n";
        continue;
    }

    $created++;
    $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
}

$stmt->close();

echo "Children available (0–{$maxAge}): {$totalChildren}\n";
echo "Assessments created: {$created}\n";
foreach ($byStatus as $status => $count) {
    if ($count > 0) {
        echo "  {$status}: {$count}\n";
    }
}

$hub = nutrition_hub_totals($con);
echo "Hub totals — assessed={$hub['assessed']} pending={$hub['pending']} at_risk={$hub['at_risk']} this_month={$hub['this_month']} teenage_pregnant={$hub['teenage_pregnant']}\n";
echo "Done.\n";
