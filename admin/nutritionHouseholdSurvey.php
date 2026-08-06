<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activePage = 'household_survey';
$nutritionPageTitle = 'Household Nutrition Survey';
$surveys = nutrition_list_household_surveys($con, (string) $barangay_id);
$report = nutrition_household_consolidated_report($con, (string) $barangay_id);
$summary = $report['summary'] ?? [];
$relationshipOptions = nutrition_relationship_options();
$defaultPurokNumber = 1;
$defaultHouseholdId = nutrition_generate_household_reference(
    $con,
    (string) $barangay_id,
    (string) $defaultPurokNumber,
    (string) $barangay
);
$psgcCode = nutrition_barangay_psgc_code($con, (string) $barangay_id, (string) $barangay);
$nutritionIncludeScriptsCsrf = true;
$nutritionExtraCss = [
    '../assets/plugins/select2/css/select2.min.css',
    '../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css',
    '../assets/plugins/sweetalert2/css/sweetalert2.min.css',
];
$nutritionExtraJs = [
    '../assets/plugins/select2/js/select2.full.min.js',
    '../assets/plugins/sweetalert2/js/sweetalert2.all.min.js',
    '../assets/js/barangay-ui.js',
];

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
require __DIR__ . '/../includes/partials/nutrition_household_survey_content.php';
?>
<script>
window.nutritionRelationshipOptions = <?= json_encode($relationshipOptions, JSON_UNESCAPED_UNICODE) ?>;
window.nutritionPregnantStatusOptions = <?= json_encode(nutrition_prf_pregnant_status_options(), JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php
require __DIR__ . '/../includes/partials/nutrition_household_survey_scripts.php';
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
