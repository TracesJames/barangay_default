<?php

/**
 * Household nutrition survey form and recent records.
 *
 * Expected variables:
 * - $defaultHouseholdId, $psgcCode, $defaultPurokNumber, $surveys
 * Optional:
 * - $summary (from nutrition_household_consolidated_report)
 * - $nutritionSurveyCardTitle, $nutritionSurveyListTitle, $nutritionSurveySaveLabel
 */
$nutritionSurveyCardTitle = $nutritionSurveyCardTitle ?? 'New Household Survey';
$nutritionSurveyListTitle = $nutritionSurveyListTitle ?? 'Recent Household Surveys';
$nutritionSurveySaveLabel = $nutritionSurveySaveLabel ?? 'Save Household Survey';
$summary = $summary ?? [];
$thisMonthCount = 0;
$currentMonth = date('Y-m');
foreach ($surveys as $surveyRow) {
    $surveyDate = (string) ($surveyRow['survey_date'] ?? '');
    if ($surveyDate !== '' && str_starts_with($surveyDate, $currentMonth)) {
        $thisMonthCount++;
    }
}
?>
        <?php
        $nutritionPageIcon = 'fa-home';
        $nutritionPageHeading = 'Household Nutrition Survey';
        $nutritionPageDescription = 'City Nutrition Committee Survey Form (Family Profile) for ' . $barangay . '. Match the official PRF fields: household head, living conditions, feeding practices, and family member nutrition status.';
        $nutritionPageActions = '
            <a href="nutritionHouseholdSurveyFormExcel.php?layout=form" class="btn btn-outline-light btn-sm" id="downloadHouseholdSurveyExcel">
              <i class="fas fa-file-excel mr-1"></i> Excel Form
            </a>
            <a href="nutritionHouseholdSurveyForm.php" target="_blank" class="btn btn-outline-light btn-sm" id="downloadHouseholdSurveyForm">
              <i class="fas fa-print mr-1"></i> Print Form
            </a>
            <a href="nutritionBarangaySurvey.php" class="btn btn-success btn-sm">
              <i class="fas fa-poll mr-1"></i> Consolidated Report
            </a>';
        require __DIR__ . '/nutrition_page_header.php';
        require_once __DIR__ . '/nutrition_prf_field_helpers.php';
        ?>

        <div class="nutrition-household-quickstats mb-4">
          <div class="nutrition-household-quickstat">
            <span><?= number_format((int) ($summary['households'] ?? count($surveys))) ?></span>
            <small>Total Households</small>
          </div>
          <div class="nutrition-household-quickstat">
            <span><?= number_format($thisMonthCount) ?></span>
            <small>Surveys This Month</small>
          </div>
          <div class="nutrition-household-quickstat">
            <span><?= number_format((int) ($summary['family_members'] ?? 0)) ?></span>
            <small>Family Members</small>
          </div>
          <div class="nutrition-household-quickstat">
            <span><?= number_format((int) ($summary['at_risk_members'] ?? 0)) ?></span>
            <small>At-Risk Members</small>
          </div>
        </div>

        <div class="card nutrition-panel mb-4">
          <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-edit mr-2"></i><?= barangay_h($nutritionSurveyCardTitle) ?></h3>
            <small class="text-muted d-block mt-1">Aligned with City Nutrition Committee Survey Form (Family Profile)</small>
          </div>
          <form id="householdSurveyForm">
            <?= csrf_field(); ?>
            <?php if (!empty($editingSurvey['survey_id'])) : ?>
            <input type="hidden" name="existing_survey_id" id="existing_survey_id" value="<?= barangay_h((string) $editingSurvey['survey_id']) ?>">
            <?php endif; ?>
            <div class="card-body">
              <div class="nutrition-info-callout">
                <i class="fas fa-info-circle"></i>
                <div>
                  Fill this registration using the official <strong>PRF / Family Profile</strong> survey. Load a registered barangay resident to reuse name and classification data.
                </div>
              </div>

              <nav class="nutrition-form-stepper" aria-label="Survey sections">
                <button type="button" class="nutrition-form-step is-active" data-step-target="#nutritionStepSurvey">
                  <span class="nutrition-form-step-num">1</span> Survey Info
                </button>
                <button type="button" class="nutrition-form-step" data-step-target="#nutritionStepHead">
                  <span class="nutrition-form-step-num">2</span> Household Head
                </button>
                <button type="button" class="nutrition-form-step" data-step-target="#nutritionStepLiving">
                  <span class="nutrition-form-step-num">3</span> Living Conditions
                </button>
                <button type="button" class="nutrition-form-step" data-step-target="#nutritionStepPractices">
                  <span class="nutrition-form-step-num">4</span> FP &amp; Feeding
                </button>
                <button type="button" class="nutrition-form-step" data-step-target="#nutritionStepFamily">
                  <span class="nutrition-form-step-num">5</span> Family Members
                </button>
              </nav>

              <div class="nutrition-form-section" id="nutritionStepSurvey">
                <h5 class="nutrition-form-section-title"><i class="fas fa-clipboard-list mr-2"></i>Survey Information</h5>
                <div class="row">
                  <div class="col-md-6 form-group">
                    <label>Name of Barangay</label>
                    <input type="text" class="form-control" value="<?= barangay_h($barangay) ?>" readonly>
                  </div>
                  <div class="col-md-3 form-group">
                    <label for="purok_number">Purok <span class="text-danger">*</span></label>
                    <input type="text" class="form-control nutrition-input-narrow" id="purok_number" name="purok_number" maxlength="32" value="<?= barangay_h((string) ($defaultPurokInput ?? $defaultPurokNumber ?? '1')) ?>" placeholder="1, 1A, A" autocomplete="off" required>
                    <small class="text-muted">Number and/or letters (e.g. 1, 1A, A)</small>
                  </div>
                  <div class="col-md-3 form-group">
                    <label for="survey_date">Survey Date <span class="text-danger">*</span></label>
                    <input type="text" class="form-control nutrition-date-mdy" id="survey_date" name="survey_date" value="<?= barangay_h(date('m/d/Y')) ?>" placeholder="MM/DD/YYYY" inputmode="numeric" autocomplete="off" required>
                    <small class="text-muted">Format: Month/Day/YYYY</small>
                  </div>
                  <div class="col-md-6 form-group">
                    <label for="bns_name">Name of BNS</label>
                    <input type="text" class="form-control" id="bns_name" name="bns_name" placeholder="Barangay Nutrition Scholar">
                  </div>
                  <div class="col-lg-6 form-group">
                    <label for="house_hold_id">Household No.</label>
                    <div class="input-group">
                      <input type="text" class="form-control nutrition-readonly-id" id="house_hold_id" name="house_hold_id" value="<?= barangay_h($defaultHouseholdId) ?>" readonly>
                      <div class="input-group-append">
                        <?php if (empty($editingSurvey['survey_id'])) : ?>
                        <button type="button" class="btn btn-outline-success" id="refreshHouseholdId" title="Refresh number">
                          <i class="fas fa-sync-alt"></i>
                        </button>
                        <?php endif; ?>
                      </div>
                    </div>
                    <small class="text-muted d-block mt-1">
                      Format: <code>PSGC-Purok-5DigitSeries</code> (e.g. <code><?= barangay_h($psgcCode) ?>-P1-00001</code>)
                    </small>
                  </div>
                </div>
              </div>

              <div class="nutrition-form-section" id="nutritionStepHead">
                <h5 class="nutrition-form-section-title"><i class="fas fa-user mr-2"></i>Household Head</h5>
                <div class="form-group">
                  <label for="barangay_residence_id">Load from Barangay Residents <span class="text-warning">*</span></label>
                  <select class="form-control" id="barangay_residence_id" name="residence_id" style="width: 100%;">
                    <option value="">Search registered resident…</option>
                  </select>
                  <small class="form-text text-warning">
                    Recommended: link to a registered resident so Nutrition Hub and Barangay Hub share one identity.
                    Unlinked surveys appear under <em>Unlinked Nutrition Households</em>.
                  </small>
                </div>
                <div class="row nutrition-name-fields-row">
                  <div class="col-md-6 col-lg-3 form-group">
                    <label for="head_last_name">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="head_last_name" name="head_last_name" required>
                  </div>
                  <div class="col-md-6 col-lg-3 form-group">
                    <label for="head_first_name">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="head_first_name" name="head_first_name" required>
                  </div>
                  <div class="col-md-6 col-lg-3 form-group">
                    <label for="head_middle_name">Middle Name</label>
                    <input type="text" class="form-control" id="head_middle_name" name="head_middle_name">
                  </div>
                  <div class="col-md-6 col-lg-3 form-group">
                    <label for="head_suffix">Suffix</label>
                    <input type="text" class="form-control" id="head_suffix" name="head_suffix" placeholder="Jr., Sr., III">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4 form-group">
                    <label for="birth_date">Birthday</label>
                    <input type="text" class="form-control nutrition-date-mdy" id="birth_date" name="birth_date" placeholder="MM/DD/YYYY" inputmode="numeric" autocomplete="off">
                    <small class="text-muted">Format: Month/Day/YYYY</small>
                  </div>
                  <div class="col-md-4 form-group">
                    <label for="gender">Gender <span class="text-danger">*</span></label>
                    <select class="form-control" id="gender" name="gender" required>
                      <option value="">Select gender</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                    </select>
                  </div>
                  <div class="col-md-4 form-group">
                    <label for="occupation">Occupation</label>
                    <input type="text" class="form-control" id="occupation" name="occupation" placeholder="e.g. Farmer, Teacher">
                  </div>
                </div>

                <div id="headFemaleStatusBlock" class="nutrition-head-female-status mt-3" style="display:none;">
                  <div class="nutrition-info-callout mb-3">
                    <i class="fas fa-female"></i>
                    <div>
                      Household head is <strong>Female</strong>. Please indicate if she is <strong>Pregnant</strong> and/or <strong>Lactating</strong>.
                    </div>
                  </div>
                  <div class="d-flex flex-wrap gap-3 mb-3">
                    <div class="custom-control custom-checkbox mr-3">
                      <input type="checkbox" class="custom-control-input" id="head_is_pregnant" name="head_is_pregnant" value="YES">
                      <label class="custom-control-label" for="head_is_pregnant">Pregnant</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" class="custom-control-input" id="head_is_lactating" name="head_is_lactating" value="YES">
                      <label class="custom-control-label" for="head_is_lactating">Lactating</label>
                    </div>
                  </div>

                  <div id="headPregnantFields" class="nutrition-member-extra-fields mb-3" style="display:none;">
                    <div class="form-group" style="max-width: 180px;">
                      <label for="head_pregnancy_months">How Many Months</label>
                      <input type="number" min="1" max="9" class="form-control nutrition-input-narrow" id="head_pregnancy_months" name="head_pregnancy_months" placeholder="Months">
                    </div>
                    <div class="form-group mb-0">
                      <label class="d-block font-weight-bold mb-2">Nutritional Status (Pregnant)</label>
                      <div class="nutrition-checkbox-grid nutrition-checkbox-grid--inline">
                        <?php foreach (nutrition_prf_pregnant_status_options() as $i => $opt) :
                            $id = 'head_pregnant_status_' . $i;
                            ?>
                          <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="<?= barangay_h($id) ?>" name="head_pregnant_nutrition_status" value="<?= barangay_h($opt) ?>">
                            <label class="custom-control-label" for="<?= barangay_h($id) ?>"><?= barangay_h($opt) ?></label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <div class="nutrition-feeding-group mt-3">
                      <label class="d-block mb-2 font-weight-bold">Planned Infant Feeding Method</label>
                      <div class="nutrition-checkbox-grid">
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="head_planned_exclusive" name="head_planned_exclusive_breastfeeding" value="YES">
                          <label class="custom-control-label" for="head_planned_exclusive">Exclusive Breastfeeding</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="head_planned_mixed" name="head_planned_mixed_feeding" value="YES">
                          <label class="custom-control-label" for="head_planned_mixed">Mixed Feeding</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="head_planned_bottle" name="head_planned_bottle_feeding" value="YES">
                          <label class="custom-control-label" for="head_planned_bottle">Bottle Feeding</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input nutrition-other-feeding-toggle" id="head_planned_other" name="head_planned_other_feeding" value="YES" data-target="#head_planned_other_specify">
                          <label class="custom-control-label" for="head_planned_other">Others</label>
                        </div>
                      </div>
                      <input type="text" class="form-control mt-2 nutrition-other-specify" id="head_planned_other_specify" name="head_planned_other_specify" placeholder="Specify other feeding method" disabled>
                    </div>
                  </div>

                  <div id="headLactatingFields" class="nutrition-member-extra-fields" style="display:none;">
                    <div class="nutrition-feeding-group">
                      <label class="d-block mb-2 font-weight-bold">Infant Feeding Method</label>
                      <div class="nutrition-checkbox-grid">
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="head_lactating_exclusive" name="head_lactating_exclusive_breastfeeding" value="YES">
                          <label class="custom-control-label" for="head_lactating_exclusive">Exclusive Breastfeeding</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="head_lactating_mixed" name="head_lactating_mixed_feeding" value="YES">
                          <label class="custom-control-label" for="head_lactating_mixed">Mixed Feeding</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="head_lactating_bottle" name="head_lactating_bottle_feeding" value="YES">
                          <label class="custom-control-label" for="head_lactating_bottle">Bottle Feeding</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input nutrition-other-feeding-toggle" id="head_lactating_other" name="head_lactating_other_feeding" value="YES" data-target="#head_lactating_other_specify">
                          <label class="custom-control-label" for="head_lactating_other">Others</label>
                        </div>
                      </div>
                      <input type="text" class="form-control mt-2 nutrition-other-specify" id="head_lactating_other_specify" name="head_lactating_other_specify" placeholder="Specify other feeding method" disabled>
                    </div>
                  </div>
                </div>

                <h6 class="mt-3 mb-2">Check if:</h6>
                <div class="nutrition-checkbox-grid nutrition-checkbox-grid--inline">
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_4ps" name="is_4ps" value="YES">
                    <label class="custom-control-label" for="is_4ps">4P’s Member</label>
                  </div>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_ip" name="is_ip" value="YES">
                    <label class="custom-control-label" for="is_ip">IP’s Member</label>
                  </div>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_na_member" name="is_na_member" value="YES">
                    <label class="custom-control-label" for="is_na_member">N/A</label>
                  </div>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_pwd" name="is_pwd" value="YES">
                    <label class="custom-control-label" for="is_pwd">PWD</label>
                  </div>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_solo_parent" name="is_solo_parent" value="YES">
                    <label class="custom-control-label" for="is_solo_parent">Solo Parent</label>
                  </div>
                </div>
              </div>

              <div class="nutrition-form-section" id="nutritionStepLiving">
                <h5 class="nutrition-form-section-title"><i class="fas fa-home mr-2"></i>Living Conditions (PRF)</h5>

                <div class="nutrition-prf-living-grid">
                  <div class="nutrition-prf-living-columns">
                    <div class="nutrition-prf-group">
                      <h6 class="nutrition-prf-group-title"><i class="fas fa-building mr-1"></i> Housing</h6>

                      <div class="nutrition-prf-field">
                        <?php nutrition_prf_field_label('III-A', 'Type of House'); ?>
                        <?php nutrition_prf_render_radio_group('house_ownership', nutrition_prf_house_ownership_options(), 'nutrition-toggle-other', 'compact'); ?>
                        <input type="text" class="form-control mt-2" id="house_ownership_other" name="house_ownership_other" placeholder="Others, pls. specify" style="display:none;">
                      </div>

                      <div class="nutrition-prf-field mb-0">
                        <?php nutrition_prf_field_label('III-E', 'Types of Dwelling Unit'); ?>
                        <?php nutrition_prf_render_radio_group('dwelling_type', nutrition_prf_dwelling_options(), '', 'wrap'); ?>
                      </div>
                    </div>

                    <div class="nutrition-prf-group">
                      <h6 class="nutrition-prf-group-title"><i class="fas fa-toilet mr-1"></i> Sanitation</h6>

                      <div class="nutrition-prf-field">
                        <?php nutrition_prf_field_label('III-B', 'Type of Toilet'); ?>
                        <?php nutrition_prf_render_radio_group('toilet_type', nutrition_prf_toilet_options(), '', 'wrap'); ?>
                      </div>

                      <div class="nutrition-prf-field mb-0">
                        <?php nutrition_prf_field_label('III-C', 'Type of Garbage Disposal'); ?>
                        <?php nutrition_prf_render_radio_group('garbage_disposal', nutrition_prf_garbage_options(), 'nutrition-garbage-toggle', 'compact'); ?>
                        <div id="garbageUncollectedOptions" class="nutrition-prf-suboptions mt-3 pt-2" style="display:none;">
                          <label class="d-block small text-muted mb-2">If Uncollected, pls check if:</label>
                          <?php nutrition_prf_render_radio_group('garbage_uncollected_type', nutrition_prf_garbage_uncollected_options(), '', 'wrap'); ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="nutrition-prf-living-full">
                    <h6 class="nutrition-prf-group-title"><i class="fas fa-tint mr-1"></i> Water &amp; Food Security</h6>

                    <div class="nutrition-prf-field">
                      <?php nutrition_prf_field_label('III-D', 'Type of Water Source'); ?>
                      <?php nutrition_prf_render_radio_group('water_source', nutrition_prf_water_source_options(), '', 'wrap'); ?>
                    </div>

                    <div class="nutrition-prf-field">
                      <?php nutrition_prf_field_label('III-F', 'Food Production Activities (check all that apply)'); ?>
                      <?php nutrition_prf_render_checkbox_group('food_production_activities', nutrition_prf_food_production_activity_options(), 'wrap'); ?>
                    </div>

                    <div class="nutrition-prf-field mb-0">
                      <?php nutrition_prf_field_label('III-G', 'Check below if:'); ?>
                      <div class="nutrition-prf-options nutrition-prf-options--wrap">
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="uses_iodized_salt" name="uses_iodized_salt" value="YES">
                          <label class="custom-control-label" for="uses_iodized_salt">HH using Iodized Salt</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="uses_sangkap_pinoy" name="uses_sangkap_pinoy" value="YES">
                          <label class="custom-control-label" for="uses_sangkap_pinoy">HH using Products with Sangkap Pinoy Seal</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="has_carenderia" name="has_carenderia" value="YES">
                          <label class="custom-control-label" for="has_carenderia">HH with Carenderia/Eatery</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="has_sari_sari_store" name="has_sari_sari_store" value="YES">
                          <label class="custom-control-label" for="has_sari_sari_store">HH with Sari-Sari Store</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="nutrition-form-section" id="nutritionStepPractices">
                <h5 class="nutrition-form-section-title"><i class="fas fa-heartbeat mr-2"></i>Family Planning &amp; Feeding Practices</h5>

                <div class="nutrition-prf-living-grid">
                  <div class="nutrition-prf-living-full">
                    <h6 class="nutrition-prf-group-title"><i class="fas fa-users-cog mr-1"></i> Family Planning</h6>

                    <div class="nutrition-prf-field mb-0">
                      <?php nutrition_prf_field_label('IV-A', 'Practices Family Planning'); ?>
                      <div class="nutrition-prf-options nutrition-prf-options--compact">
                        <div class="custom-control custom-radio">
                          <input type="radio" class="custom-control-input" id="fp_yes" name="practices_family_planning" value="YES">
                          <label class="custom-control-label" for="fp_yes">Yes</label>
                        </div>
                        <div class="custom-control custom-radio">
                          <input type="radio" class="custom-control-input" id="fp_no" name="practices_family_planning" value="NO" checked>
                          <label class="custom-control-label" for="fp_no">No</label>
                        </div>
                      </div>
                      <div id="familyPlanningMethodsWrap" class="nutrition-prf-suboptions mt-3 pt-2" style="display:none;">
                        <label class="d-block small text-muted mb-2">If yes, check method(s):</label>
                        <?php nutrition_prf_render_checkbox_group('family_planning_methods', nutrition_prf_family_planning_method_options(), 'wrap'); ?>
                      </div>
                    </div>
                  </div>

                  <div class="nutrition-prf-living-full">
                    <h6 class="nutrition-prf-group-title"><i class="fas fa-utensils mr-1"></i> Complementary Feeding (6–23 months)</h6>

                    <div class="nutrition-prf-living-columns">
                      <div class="nutrition-prf-field">
                        <?php nutrition_prf_field_label('IV-B', 'Common Meals Given'); ?>
                        <?php nutrition_prf_render_radio_group('complementary_meals', nutrition_prf_complementary_meal_options(), 'nutrition-meals-toggle', 'stacked'); ?>
                        <input type="text" class="form-control mt-2" id="complementary_meals_other" name="complementary_meals_other" placeholder="Others, pls. specify" style="display:none;">
                      </div>

                      <div class="nutrition-prf-field">
                        <?php nutrition_prf_field_label('IV-C', 'Common Snacks Given'); ?>
                        <?php nutrition_prf_render_radio_group('complementary_snacks', nutrition_prf_complementary_snack_options(), 'nutrition-snacks-toggle', 'stacked'); ?>
                        <input type="text" class="form-control mt-2" id="complementary_snacks_other" name="complementary_snacks_other" placeholder="Others, pls. specify" style="display:none;">
                      </div>
                    </div>
                  </div>

                  <div class="nutrition-prf-living-full">
                    <h6 class="nutrition-prf-group-title"><i class="fas fa-running mr-1"></i> Child Physical Activity</h6>

                    <div class="nutrition-prf-field mb-0">
                      <?php nutrition_prf_field_label('IV-D', 'Physical Activity of children (1–5 years old)'); ?>
                      <?php nutrition_prf_render_radio_group('child_physical_activity', nutrition_prf_physical_activity_options(), 'nutrition-activity-toggle', 'wrap'); ?>
                      <input type="text" class="form-control mt-2" id="child_physical_activity_other" name="child_physical_activity_other" placeholder="Others, pls. specify" style="display:none;">
                    </div>
                  </div>
                </div>
              </div>

              <div class="nutrition-form-section mb-0" id="nutritionStepFamily">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <h5 class="nutrition-form-section-title mb-0">
                    <i class="fas fa-users mr-2"></i>Family Members
                    <span class="badge badge-success ml-2" id="familyMemberCountBadge">0</span>
                  </h5>
                  <button type="button" class="btn btn-outline-success btn-sm" id="addFamilyMemberBtn">
                    <i class="fas fa-user-plus mr-1"></i> Add Family Member
                  </button>
                </div>
                <p class="text-muted small mb-3">
                  For children ages <strong>0–5 years</strong>, enter birthday and gender (boy/girl) — Weight and Height fields appear automatically.
                  WFA / HFA / WFH are computed from child growth standards. Pregnant/Lactating status can be recorded for eligible members.
                  No. of household members is computed automatically.
                </p>
                <div id="familyMembersContainer"></div>
                <div id="familyMembersEmpty" class="nutrition-family-empty text-center py-4">
                  <i class="fas fa-users fa-2x text-muted mb-2 d-block"></i>
                  <p class="text-muted mb-2">No family members added yet.</p>
                  <button type="button" class="btn btn-sm btn-outline-success nutrition-add-first-member">
                    <i class="fas fa-user-plus mr-1"></i> Add First Member
                  </button>
                </div>
                <div class="form-group mt-3 mb-0">
                  <label for="remarks">Remarks</label>
                  <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Optional notes"></textarea>
                </div>
              </div>
            </div>
            <div class="card-footer nutrition-form-sticky-bar d-flex flex-wrap gap-2 justify-content-between align-items-center">
              <small class="text-muted mb-0 d-none d-md-inline">Required: survey date, purok, household head name, and gender.</small>
              <div class="d-flex flex-wrap gap-2 ml-md-auto">
                <button type="button" class="btn btn-outline-light" id="resetHouseholdSurveyForm">
                  <i class="fas fa-undo mr-1"></i> Reset
                </button>
                <button type="submit" class="btn btn-success px-4">
                  <i class="fas fa-save mr-1"></i> <?= barangay_h($nutritionSurveySaveLabel) ?>
                </button>
              </div>
            </div>
          </form>
        </div>

        <div class="card nutrition-panel">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0"><i class="fas fa-history mr-2"></i><?= barangay_h($nutritionSurveyListTitle) ?></h3>
            <div class="input-group nutrition-household-search" style="max-width: 260px;">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
              </div>
              <input type="search" class="form-control" id="householdSurveySearch" placeholder="Search records...">
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive nutrition-table-desktop">
              <table class="table table-dark table-striped mb-0" id="householdSurveyTable">
                <thead>
                  <tr>
                    <th>Household ID</th>
                    <th>Date</th>
                    <th>Purok</th>
                    <th>Household Head</th>
                    <th>Members</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($surveys === []) : ?>
                  <tr class="nutrition-household-empty-row">
                    <td colspan="7" class="text-center text-muted py-4">No household surveys yet. Save your first survey above.</td>
                  </tr>
                  <?php else : ?>
                  <?php foreach ($surveys as $survey) :
                      $headDisplay = nutrition_household_head_display($survey);
                      $memberBadges = nutrition_household_member_badges($survey);
                      $statusBadges = [];
                      if (strtoupper((string) ($survey['has_pregnant'] ?? 'NO')) === 'YES') {
                          $statusBadges[] = 'Pregnant';
                      }
                      if (strtoupper((string) ($survey['has_lactating'] ?? 'NO')) === 'YES') {
                          $statusBadges[] = 'Lactating';
                      }
                      $searchText = strtolower(implode(' ', [
                          (string) ($survey['house_hold_id'] ?? ''),
                          $headDisplay,
                          (string) ($survey['purok_label'] ?? ''),
                          (string) ($survey['occupation'] ?? ''),
                          (string) ($survey['gender'] ?? ''),
                      ]));
                      $viewUrl = 'nutritionBarangaySurvey.php?highlight=' . urlencode((string) ($survey['survey_id'] ?? ''));
                      ?>
                  <tr data-search="<?= barangay_h($searchText) ?>">
                    <td><code><?= barangay_h((string) ($survey['house_hold_id'] ?? '')) ?></code></td>
                    <td><?= barangay_h(date('M j, Y', strtotime((string) $survey['survey_date']))) ?></td>
                    <td><?= barangay_h((string) ($survey['purok_label'] ?? '')) ?></td>
                    <td>
                      <?= barangay_h($headDisplay) ?>
                      <?php if ($memberBadges !== []) : ?>
                      <div class="nutrition-member-badges mt-1">
                        <?php foreach ($memberBadges as $badge) : ?>
                        <span class="badge badge-info"><?= barangay_h($badge) ?></span>
                        <?php endforeach; ?>
                      </div>
                      <?php endif; ?>
                    </td>
                    <td><?= number_format((int) ($survey['members_count'] ?? 0)) ?></td>
                    <td>
                      <?php if ($statusBadges === []) : ?>
                      <span class="text-muted">—</span>
                      <?php else : ?>
                      <?php foreach ($statusBadges as $badge) : ?>
                      <span class="badge badge-warning"><?= barangay_h($badge) ?></span>
                      <?php endforeach; ?>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <a href="<?= barangay_h($viewUrl) ?>" class="btn btn-xs btn-outline-success nutrition-table-action-btn">
                        <i class="fas fa-eye mr-1"></i> View
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="nutrition-mobile-records" id="householdSurveyMobileList">
              <?php if ($surveys === []) : ?>
              <div class="nutrition-empty-state">
                <i class="fas fa-inbox d-block"></i>
                <p class="mb-0">No household surveys yet.</p>
              </div>
              <?php else : ?>
              <?php foreach ($surveys as $survey) :
                  $headDisplay = nutrition_household_head_display($survey);
                  $searchText = strtolower(implode(' ', [
                      (string) ($survey['house_hold_id'] ?? ''),
                      $headDisplay,
                      (string) ($survey['purok_label'] ?? ''),
                  ]));
                  $viewUrl = 'nutritionBarangaySurvey.php?highlight=' . urlencode((string) ($survey['survey_id'] ?? ''));
                  ?>
              <div class="nutrition-mobile-record" data-search="<?= barangay_h($searchText) ?>">
                <div class="nutrition-mobile-record-title"><?= barangay_h($headDisplay) ?></div>
                <div class="nutrition-mobile-record-meta">
                  <code><?= barangay_h((string) ($survey['house_hold_id'] ?? '')) ?></code>
                  · <?= barangay_h((string) ($survey['purok_label'] ?? '')) ?>
                  · <?= barangay_h(date('M j, Y', strtotime((string) $survey['survey_date']))) ?>
                </div>
                <a href="<?= barangay_h($viewUrl) ?>" class="btn btn-sm btn-outline-success">
                  <i class="fas fa-eye mr-1"></i> View in Report
                </a>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
