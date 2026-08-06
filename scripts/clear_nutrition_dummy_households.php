<?php

/**
 * Remove all dummy household survey seed data.
 * Usage: php scripts/clear_nutrition_dummy_households.php
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/barangay_context.php';

function clear_table_exists(mysqli $con, string $table): bool
{
    $table = $con->real_escape_string($table);
    $result = $con->query("SHOW TABLES LIKE '{$table}'");

    return $result && $result->num_rows > 0;
}

function clear_count(mysqli $con, string $sql): int
{
    $result = $con->query($sql);
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();

    return (int) ($row['c'] ?? 0);
}

if (!clear_table_exists($con, 'nutrition_household_survey')) {
    fwrite(STDERR, "Table nutrition_household_survey not found.\n");
    exit(1);
}

$dummySurveys = clear_count(
    $con,
    "SELECT COUNT(*) AS c FROM nutrition_household_survey WHERE remarks LIKE 'Dummy seed data%'"
);
$dummyMembers = 0;
if (clear_table_exists($con, 'nutrition_household_family_member')) {
    $dummyMembers = clear_count(
        $con,
        "SELECT COUNT(*) AS c
         FROM nutrition_household_family_member fm
         INNER JOIN nutrition_household_survey s ON s.survey_id = fm.survey_id
         WHERE s.remarks LIKE 'Dummy seed data%'"
    );
}
$dummyAssessments = 0;
if (clear_table_exists($con, 'nutrition_assessment')) {
    $dummyAssessments = clear_count(
        $con,
        "SELECT COUNT(*) AS c FROM nutrition_assessment WHERE remarks LIKE 'Dummy seed assessment%'"
    );
}

echo "Dummy surveys found: {$dummySurveys}\n";
echo "Dummy family members found: {$dummyMembers}\n";
echo "Dummy assessments found: {$dummyAssessments}\n";

$con->begin_transaction();
try {
    $deletedMembers = 0;
    if (clear_table_exists($con, 'nutrition_household_family_member')) {
        $con->query(
            "DELETE fm FROM nutrition_household_family_member fm
             INNER JOIN nutrition_household_survey s ON s.survey_id = fm.survey_id
             WHERE s.remarks LIKE 'Dummy seed data%'"
        );
        $deletedMembers = (int) $con->affected_rows;
    }

    $con->query("DELETE FROM nutrition_household_survey WHERE remarks LIKE 'Dummy seed data%'");
    $deletedSurveys = (int) $con->affected_rows;

    $deletedAssessments = 0;
    if (clear_table_exists($con, 'nutrition_assessment')) {
        $con->query("DELETE FROM nutrition_assessment WHERE remarks LIKE 'Dummy seed assessment%'");
        $deletedAssessments = (int) $con->affected_rows;
    }

    $con->commit();
} catch (Throwable $e) {
    $con->rollback();
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$remainingDummy = clear_count(
    $con,
    "SELECT COUNT(*) AS c FROM nutrition_household_survey WHERE remarks LIKE 'Dummy seed data%'"
);
$totalSurveys = clear_count($con, 'SELECT COUNT(*) AS c FROM nutrition_household_survey');

echo "Deleted family members: {$deletedMembers}\n";
echo "Deleted surveys: {$deletedSurveys}\n";
echo "Deleted dummy assessments: {$deletedAssessments}\n";
echo "Remaining dummy surveys: {$remainingDummy}\n";
echo "Total household surveys left: {$totalSurveys}\n";
echo "Done.\n";
