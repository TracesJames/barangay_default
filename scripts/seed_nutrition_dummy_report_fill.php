<?php

/**
 * Fill remaining MELLPI year blanks and ensure teenage pregnant demo counts.
 *
 * Usage: php scripts/seed_nutrition_dummy_report_fill.php
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';
require_once dirname(__DIR__) . '/includes/nutrition_context.php';
require_once dirname(__DIR__) . '/includes/nutrition_mellpi.php';

nutrition_ensure_module_tables($con);
nutrition_mellpi_ensure_table($con);

$p = nutrition_mellpi_load_profile($con);
$y = (int) date('Y');
$y3 = $y;

$p['preschool']['wfa']['Normal'][$y3] = '8600';
$p['preschool']['wfa']['Underweight'][$y3] = '560';
$p['preschool']['wfa']['Severely Underweight'][$y3] = '82';
$p['preschool']['wfa']['Overweight'][$y3] = '235';
$p['preschool']['wfh']['Normal'][$y3] = '8850';
$p['preschool']['wfh']['Wasted'][$y3] = '290';
$p['preschool']['wfh']['Severely Wasted'][$y3] = '42';
$p['preschool']['wfh']['Overweight'][$y3] = '205';
$p['preschool']['wfh']['Obese'][$y3] = '80';
$p['preschool']['hfa']['Normal'][$y3] = '8200';
$p['preschool']['hfa']['Stunted'][$y3] = '910';
$p['preschool']['hfa']['Severely Stunted'][$y3] = '118';
$p['preschool']['hfa']['Tall'][$y3] = '98';

$p['population_snapshot']['0_59_actual'] = '186';
$p['population_snapshot']['pregnant_actual'] = '62';
$p['population_snapshot']['lactating_actual'] = '31';
$p['bns']['total'] = '31';
$p['bns']['existing'] = '28';

if (nutrition_mellpi_save_profile($con, $p, 'seed')) {
    echo "MELLPI current-year fields filled.\n";
} else {
    echo "Failed to update MELLPI profile.\n";
}

// Promote some Normal pregnant demo rows to Teenage for richer BNP column B.
$con->query(
    "UPDATE nutrition_household_survey
     SET head_pregnant_nutrition_status = 'Teenage'
     WHERE remarks LIKE 'Dummy seed data%'
       AND UPPER(head_is_pregnant) = 'YES'
       AND head_pregnant_nutrition_status = 'Normal'
       AND MOD(CRC32(survey_id), 3) = 0"
);
$con->query(
    "UPDATE nutrition_household_family_member m
     INNER JOIN nutrition_household_survey s ON s.survey_id = m.survey_id
     SET m.pregnant_nutrition_status = 'Teenage'
     WHERE s.remarks LIKE 'Dummy seed data%'
       AND UPPER(m.is_pregnant) = 'YES'
       AND m.pregnant_nutrition_status = 'Normal'
       AND MOD(CRC32(m.member_id), 3) = 0"
);

$hub = nutrition_hub_totals($con);
$status = nutrition_hub_status_totals($con);

echo 'Teenage pregnant: ' . nutrition_teenage_pregnant_count($con) . "\n";
echo "Assessed: {$hub['assessed']} | Pending: {$hub['pending']} | At-risk: {$hub['at_risk']} | This month: {$hub['this_month']}\n";
echo 'Status breakdown:';
foreach ($status as $key => $count) {
    echo " {$key}={$count}";
}
echo "\nDone.\n";
