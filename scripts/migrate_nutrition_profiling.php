<?php
/**
 * Create nutrition_assessment table for the Barangay Nutrition Profiling System.
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

if (!barangay_table_exists($con, 'nutrition_assessment')) {
    $sql = "CREATE TABLE `nutrition_assessment` (
        `assessment_id` VARCHAR(32) NOT NULL,
        `residence_id` VARCHAR(32) NOT NULL,
        `barangay_id` VARCHAR(32) NOT NULL,
        `assessment_date` DATE NOT NULL,
        `weight_kg` DECIMAL(5,2) NOT NULL,
        `height_cm` DECIMAL(5,2) NOT NULL,
        `bmi` DECIMAL(5,2) DEFAULT NULL,
        `muac_cm` DECIMAL(4,1) DEFAULT NULL,
        `nutritional_status` VARCHAR(32) NOT NULL DEFAULT 'normal',
        `remarks` TEXT DEFAULT NULL,
        `assessed_by` VARCHAR(32) DEFAULT NULL,
        `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`assessment_id`),
        KEY `idx_nutrition_barangay` (`barangay_id`),
        KEY `idx_nutrition_residence` (`residence_id`),
        KEY `idx_nutrition_date` (`assessment_date`),
        KEY `idx_nutrition_status` (`nutritional_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($con->query($sql)) {
        echo "Created nutrition_assessment table\n";
    } else {
        echo "Failed to create nutrition_assessment: {$con->error}\n";
        exit(1);
    }
} else {
    echo "nutrition_assessment table already exists\n";
}

echo "Done.\n";
