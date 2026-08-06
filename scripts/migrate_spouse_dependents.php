<?php
/**
 * Add spouse columns to residence_information and create residence_dependents table.
 */
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/barangay_context.php';

$spouseColumns = [
    'spouse_first_name' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `guardian_contact`",
    'spouse_middle_name' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `spouse_first_name`",
    'spouse_last_name' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `spouse_middle_name`",
    'spouse_suffix' => "VARCHAR(69) NOT NULL DEFAULT '' AFTER `spouse_last_name`",
    'spouse_birth_date' => "VARCHAR(69) NOT NULL DEFAULT '' AFTER `spouse_suffix`",
    'spouse_age' => "VARCHAR(11) NOT NULL DEFAULT '' AFTER `spouse_birth_date`",
    'spouse_occupation' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `spouse_age`",
    'spouse_contact' => "VARCHAR(69) NOT NULL DEFAULT '' AFTER `spouse_occupation`",
    'spouse_employer_name' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `spouse_contact`",
];

foreach ($spouseColumns as $column => $definition) {
    if (!barangay_column_exists($con, 'residence_information', $column)) {
        $con->query("ALTER TABLE `residence_information` ADD COLUMN `$column` $definition");
        echo "Added residence_information.$column\n";
    } else {
        echo "residence_information.$column already exists\n";
    }
}

$tableCheck = $con->query("SHOW TABLES LIKE 'residence_dependents'");
if ($tableCheck && $tableCheck->num_rows === 0) {
    $con->query(
        "CREATE TABLE `residence_dependents` (
            `a_i` int(11) NOT NULL AUTO_INCREMENT,
            `dependent_id` varchar(255) NOT NULL,
            `residence_id` varchar(255) NOT NULL,
            `first_name` varchar(255) NOT NULL DEFAULT '',
            `middle_name` varchar(255) NOT NULL DEFAULT '',
            `last_name` varchar(255) NOT NULL DEFAULT '',
            `suffix` varchar(69) NOT NULL DEFAULT '',
            `birth_date` varchar(69) NOT NULL DEFAULT '',
            `age` varchar(11) NOT NULL DEFAULT '',
            `gender` varchar(69) NOT NULL DEFAULT '',
            `relationship` varchar(255) NOT NULL DEFAULT '',
            `contact_number` varchar(69) NOT NULL DEFAULT '',
            `date_added` varchar(69) NOT NULL DEFAULT '',
            PRIMARY KEY (`a_i`),
            KEY `residence_id` (`residence_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
    echo "Created residence_dependents table\n";
} else {
    echo "residence_dependents table already exists\n";
}

echo "Done.\n";
