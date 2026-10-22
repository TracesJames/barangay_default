<?php

require_once __DIR__ . '/bootstrap.php';

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'POST'], true)) {
    nutrition_api_error('Method not allowed.', 405);
}

nutrition_api_authenticate($con);

nutrition_api_send([
    'ok' => true,
    'relationships' => nutrition_relationship_options(),
    'genders' => ['Male', 'Female'],
    'toilet_types' => nutrition_prf_toilet_options(),
    'toilet_sanitary' => nutrition_prf_toilet_sanitary_options(),
    'toilet_unsanitary' => nutrition_prf_toilet_unsanitary_options(),
    'garbage_disposal' => nutrition_prf_garbage_options(),
    'garbage_uncollected' => nutrition_prf_garbage_uncollected_options(),
    'water_sources' => nutrition_prf_water_source_options(),
    'house_ownership' => nutrition_prf_house_ownership_options(),
    'dwelling_types' => nutrition_prf_dwelling_options(),
    'food_production_activities' => nutrition_prf_food_production_activity_options(),
    'family_planning_methods' => nutrition_prf_family_planning_method_options(),
    'complementary_meals' => nutrition_prf_complementary_meal_options(),
    'complementary_snacks' => nutrition_prf_complementary_snack_options(),
    'child_physical_activity' => nutrition_prf_physical_activity_options(),
    'pregnant_nutrition_status' => nutrition_prf_pregnant_status_options(),
]);
