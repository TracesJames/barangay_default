<?php

/**
 * Consolidated household nutrition report for barangay survey page.
 *
 * Expected:
 * - $report (from nutrition_household_consolidated_report)
 * - $filters (purok, date_from, date_to)
 * - $barangay, $district, $nutritionSettings
 */
$summary = $report['summary'] ?? [];
$householdRows = $report['households'] ?? [];
$purokOptions = $report['purok_options'] ?? [];
$filters = $filters ?? [];
$filterPurok = (string) ($filters['purok'] ?? '');
$filterDateFrom = (string) ($filters['date_from'] ?? '');
$filterDateTo = (string) ($filters['date_to'] ?? '');

$printQuery = http_build_query(array_filter([
    'purok' => $filterPurok,
    'date_from' => $filterDateFrom,
    'date_to' => $filterDateTo,
]));
$koboSubmissions = $koboSubmissions ?? [];
$koboConfigured = $koboConfigured ?? nutrition_kobo_is_configured($nutritionSettings ?? []);
$koboFormUrl = $koboFormUrl ?? trim((string) ($nutritionSettings['kobo_form_url'] ?? ''));
$koboLastSynced = $koboLastSynced ?? trim((string) ($nutritionSettings['kobo_last_synced_at'] ?? ''));
$koboEnabled = ($nutritionSettings['kobo_enabled'] ?? 'NO') === 'YES';
$sessionUserId = (string) ($_SESSION['user_id'] ?? '');
$canEditHouseholdSurveyNames = nutrition_user_can_edit_household_survey_names($con, $sessionUserId);
$canDeleteHouseholdSurveys = nutrition_user_can_delete_household_surveys($con, $sessionUserId);
$canAddHouseholdSurveys = nutrition_user_can_add_household_surveys($con, $sessionUserId);
$canEditHouseholdSurveys = nutrition_user_can_edit_household_surveys($con, $sessionUserId);
$canManageHouseholdSurveys = $canEditHouseholdSurveyNames || $canDeleteHouseholdSurveys || $canEditHouseholdSurveys;
$canManageNutritionSettings = !barangay_user_is_bns_admin($con, $sessionUserId)
    && !barangay_user_is_barangay_nutrition_scholar($con, $sessionUserId);
?>
        <?php
        $nutritionPageIcon = 'fa-poll';
        $nutritionPageHeading = 'Consolidated Household Nutrition Report';
        $nutritionPageDescription = $barangay . ', ' . $district . ' · Generated ' . date('F j, Y g:i A');
        if (($nutritionSettings['nutrition_officer'] ?? '') !== '') {
            $nutritionPageDescription .= ' · Officer: ' . ($nutritionSettings['nutrition_officer']);
        }
        $printHref = 'nutritionBarangaySurveyPrint.php' . ($printQuery !== '' ? '?' . $printQuery : '');
        $pregnantPrintHref = 'nutritionPregnantFamiliesPrint.php' . ($printQuery !== '' ? '?' . $printQuery : '');
        $nutritionPageActions = '
            <a href="' . barangay_h($printHref) . '" target="_blank" class="btn btn-success btn-sm">
              <i class="fas fa-print mr-1"></i> Print Report
            </a>
            <a href="nutritionPregnantFamiliesReport.php" class="btn btn-outline-light btn-sm">
              <i class="fas fa-female mr-1"></i> Families with Pregnant
            </a>
            <a href="' . barangay_h($pregnantPrintHref) . '" target="_blank" class="btn btn-outline-success btn-sm">
              <i class="fas fa-print mr-1"></i> Print Pregnant
            </a>
            <a href="nutritionBnpReport.php" class="btn btn-outline-light btn-sm">
              <i class="fas fa-book mr-1"></i> BNP 2026
            </a>';
        if ($canAddHouseholdSurveys) {
            $nutritionPageActions .= '
            <a href="nutritionHouseholdSurvey.php" class="btn btn-outline-light btn-sm">
              <i class="fas fa-home mr-1"></i> New Survey
            </a>';
        }
        require __DIR__ . '/nutrition_page_header.php';
        ?>

        <div class="nutrition-consolidated-stats mb-4">
          <div class="nutrition-consolidated-stat nutrition-consolidated-stat--households">
            <div class="nutrition-consolidated-stat-value"><?= number_format((int) ($summary['households'] ?? 0)) ?></div>
            <div class="nutrition-consolidated-stat-label">Households Surveyed</div>
          </div>
          <div class="nutrition-consolidated-stat nutrition-consolidated-stat--members">
            <div class="nutrition-consolidated-stat-value"><?= number_format((int) ($summary['family_members'] ?? 0)) ?></div>
            <div class="nutrition-consolidated-stat-label">Family Members</div>
          </div>
          <div class="nutrition-consolidated-stat nutrition-consolidated-stat--assessed">
            <div class="nutrition-consolidated-stat-value"><?= number_format((int) ($summary['assessed_members'] ?? 0)) ?></div>
            <div class="nutrition-consolidated-stat-label">Assessed Members</div>
          </div>
          <div class="nutrition-consolidated-stat nutrition-consolidated-stat--malnourished">
            <div class="nutrition-consolidated-stat-value"><?= number_format((int) ($summary['malnourished'] ?? 0)) ?></div>
            <div class="nutrition-consolidated-stat-label">Malnourished</div>
          </div>
          <div class="nutrition-consolidated-stat nutrition-consolidated-stat--risk">
            <div class="nutrition-consolidated-stat-value"><?= number_format((int) ($summary['at_risk_members'] ?? 0)) ?></div>
            <div class="nutrition-consolidated-stat-label">At-Risk Members</div>
          </div>
          <div class="nutrition-consolidated-stat nutrition-consolidated-stat--pregnant">
            <div class="nutrition-consolidated-stat-value"><?= number_format((int) ($summary['pregnant'] ?? 0)) ?></div>
            <div class="nutrition-consolidated-stat-label">Pregnant</div>
          </div>
          <div class="nutrition-consolidated-stat nutrition-consolidated-stat--lactating">
            <div class="nutrition-consolidated-stat-value"><?= number_format((int) ($summary['lactating'] ?? 0)) ?></div>
            <div class="nutrition-consolidated-stat-label">Lactating</div>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-3 col-6 mb-3">
            <div class="nutrition-consolidated-mini-stat">
              <span><?= number_format((int) ($summary['four_ps'] ?? 0)) ?></span>
              <small>4Ps Households</small>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3">
            <div class="nutrition-consolidated-mini-stat">
              <span><?= number_format((int) ($summary['pwd'] ?? 0)) ?></span>
              <small>PWD Households</small>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3">
            <div class="nutrition-consolidated-mini-stat">
              <span><?= number_format((int) ($summary['ip'] ?? 0)) ?></span>
              <small>IP Households</small>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3">
            <div class="nutrition-consolidated-mini-stat">
              <span><?= number_format((int) ($summary['solo_parent'] ?? 0)) ?></span>
              <small>Solo Parent Households</small>
            </div>
          </div>
        </div>

        <div class="card nutrition-panel mb-4">
          <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-filter mr-2"></i>Filter Report</h3></div>
          <div class="card-body">
            <form method="get" class="row align-items-end">
              <div class="col-md-3 form-group mb-md-0">
                <label for="filter_purok">Purok</label>
                <select class="form-control" id="filter_purok" name="purok">
                  <option value="">All puroks</option>
                  <?php foreach ($purokOptions as $purokLabel) : ?>
                  <option value="<?= barangay_h($purokLabel) ?>" <?= $filterPurok === $purokLabel ? 'selected' : '' ?>><?= barangay_h($purokLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 form-group mb-md-0">
                <label for="filter_date_from">Date From</label>
                <input type="date" class="form-control" id="filter_date_from" name="date_from" value="<?= barangay_h($filterDateFrom) ?>">
              </div>
              <div class="col-md-3 form-group mb-md-0">
                <label for="filter_date_to">Date To</label>
                <input type="date" class="form-control" id="filter_date_to" name="date_to" value="<?= barangay_h($filterDateTo) ?>">
              </div>
              <div class="col-md-3 form-group mb-0">
                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-search mr-1"></i> Apply Filter</button>
                <a href="nutritionBarangaySurvey.php" class="btn btn-outline-light btn-block mt-2">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <div class="card nutrition-panel mb-4">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0"><i class="fas fa-tablet-alt mr-2"></i>KoBoToolbox Data</h3>
            <div class="d-flex flex-wrap gap-2">
              <?php if ($koboFormUrl !== '') : ?>
              <a href="<?= barangay_h($koboFormUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm">
                <i class="fas fa-external-link-alt mr-1"></i> Open KoBo Form
              </a>
              <?php endif; ?>
              <?php if ($canAddHouseholdSurveys && $koboConfigured) : ?>
              <button type="button" class="btn btn-success btn-sm" id="syncKoboBtn">
                <i class="fas fa-sync-alt mr-1"></i> Sync from KoBo
              </button>
              <?php endif; ?>
              <?php if ($canManageNutritionSettings) : ?>
              <a href="nutritionSettings.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-cog mr-1"></i> KoBo Settings
              </a>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-body">
            <?php if (!$koboEnabled) : ?>
            <p class="text-muted mb-0"><?= $canManageNutritionSettings
                ? 'KoBoToolbox is not enabled. Turn it on under <a href="nutritionSettings.php">Nutrition Settings</a> to collect field data with KoBo forms and sync submissions here.'
                : 'KoBoToolbox is not enabled for this barangay.' ?></p>
            <?php elseif (!$koboConfigured) : ?>
            <p class="text-muted mb-0"><?= $canManageNutritionSettings
                ? 'KoBoToolbox is enabled but not fully configured. Add your server URL, API token, and form Asset UID in <a href="nutritionSettings.php">Nutrition Settings</a>.'
                : 'KoBoToolbox is enabled but not fully configured.' ?></p>
            <?php else : ?>
            <p class="text-muted small mb-3">
              Last synced: <?= $koboLastSynced !== '' ? barangay_h(date('M j, Y g:i A', strtotime($koboLastSynced))) : 'Not yet synced' ?>
              · <?= number_format(count($koboSubmissions)) ?> submission<?= count($koboSubmissions) === 1 ? '' : 's' ?> stored locally
            </p>
            <div class="table-responsive">
              <table class="table table-dark table-striped mb-0">
                <thead>
                  <tr>
                    <th>Submitted</th>
                    <th>Household / ID</th>
                    <th>Purok</th>
                    <th>Respondent</th>
                    <th>Synced</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($koboSubmissions === []) : ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">No KoBo submissions synced yet. Click <strong>Sync from KoBo</strong> after data is collected in the field.</td></tr>
                  <?php else : ?>
                  <?php foreach ($koboSubmissions as $koboRow) : ?>
                  <tr>
                    <td><?= ($koboRow['submitted_at'] ?? '') !== '' ? barangay_h(date('M j, Y g:i A', strtotime((string) $koboRow['submitted_at']))) : '—' ?></td>
                    <td><?= barangay_h((string) ($koboRow['household_label'] ?? '—')) ?></td>
                    <td><?= barangay_h((string) ($koboRow['purok_label'] ?? '—')) ?></td>
                    <td><?= barangay_h((string) ($koboRow['respondent_name'] ?? '—')) ?></td>
                    <td><?= ($koboRow['date_synced'] ?? '') !== '' ? barangay_h(date('M j, Y g:i A', strtotime((string) $koboRow['date_synced']))) : '—' ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card nutrition-panel">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>All Household Surveys</h3>
            <span class="badge badge-success"><?= number_format(count($householdRows)) ?> record<?= count($householdRows) === 1 ? '' : 's' ?></span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive nutrition-consolidated-table-wrap">
              <table class="table table-dark table-striped mb-0 nutrition-consolidated-table">
                <thead>
                  <tr>
                    <th style="width:2.5rem;"></th>
                    <th>Household ID</th>
                    <th>Survey Date</th>
                    <th>Purok</th>
                    <th>Household Head</th>
                    <th>Gender</th>
                    <th>Occupation</th>
                    <th>Members</th>
                    <?php if ($canManageHouseholdSurveys) : ?>
                    <th class="text-center" style="width:9.5rem;">Actions</th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($householdRows === []) : ?>
                  <tr><td colspan="<?= $canManageHouseholdSurveys ? 9 : 8 ?>" class="text-center text-muted py-4">No household surveys recorded yet.</td></tr>
                  <?php else : ?>
                  <?php foreach ($householdRows as $index => $row) :
                      $survey = $row['survey'];
                      $members = $row['members'];
                      $surveyId = (string) ($survey['survey_id'] ?? ('row-' . $index));
                      $collapseId = 'household-detail-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $surveyId);
                      $headDisplay = nutrition_household_head_display($survey);
                      $badges = nutrition_household_member_badges($survey);
                      ?>
                  <tr class="nutrition-consolidated-row<?= !empty($row['has_at_risk']) ? ' nutrition-consolidated-row--risk' : '' ?>" data-survey-id="<?= barangay_h($surveyId) ?>">
                    <td class="text-center">
                      <?php if ($members !== []) : ?>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-success nutrition-consolidated-toggle"
                        data-toggle="collapse"
                        data-target="#<?= barangay_h($collapseId) ?>"
                        aria-expanded="false"
                        aria-controls="<?= barangay_h($collapseId) ?>"
                      >
                        <i class="fas fa-chevron-down"></i>
                      </button>
                      <?php endif; ?>
                    </td>
                    <td><code><?= barangay_h((string) ($survey['house_hold_id'] ?? '')) ?></code></td>
                    <td><?= barangay_h(date('M j, Y', strtotime((string) $survey['survey_date']))) ?></td>
                    <td><?= barangay_h((string) ($survey['purok_label'] ?? '')) ?></td>
                    <td>
                      <?= barangay_h($headDisplay) ?>
                      <?php if ($badges !== []) : ?>
                      <div class="nutrition-member-badges mt-1">
                        <?php foreach ($badges as $badge) : ?>
                        <span class="badge badge-info"><?= barangay_h($badge) ?></span>
                        <?php endforeach; ?>
                      </div>
                      <?php endif; ?>
                    </td>
                    <td><?= barangay_h((string) ($survey['gender'] ?? '')) ?></td>
                    <td><?= barangay_h((string) ($survey['occupation'] ?? '')) ?></td>
                    <td><?= number_format((int) ($row['member_count'] ?? 0)) ?></td>
                    <?php if ($canManageHouseholdSurveys) : ?>
                    <td class="text-center text-nowrap">
                      <?php if ($canEditHouseholdSurveys) : ?>
                      <a
                        href="nutritionHouseholdSurvey.php?edit=<?= urlencode($surveyId) ?>"
                        class="btn btn-sm btn-outline-success"
                        title="Edit registered household survey"
                      >
                        <i class="fas fa-edit"></i>
                      </a>
                      <?php endif; ?>
                      <?php if ($canEditHouseholdSurveyNames) : ?>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-light nutrition-edit-head-btn"
                        title="Edit household head name"
                        data-survey-id="<?= barangay_h($surveyId) ?>"
                        data-head-last="<?= barangay_h((string) ($survey['head_last_name'] ?? '')) ?>"
                        data-head-first="<?= barangay_h((string) ($survey['head_first_name'] ?? '')) ?>"
                        data-head-middle="<?= barangay_h((string) ($survey['head_middle_name'] ?? '')) ?>"
                        data-head-suffix="<?= barangay_h((string) ($survey['head_suffix'] ?? '')) ?>"
                        data-head-display="<?= barangay_h($headDisplay) ?>"
                      >
                        <i class="fas fa-user-edit"></i>
                      </button>
                      <?php endif; ?>
                      <?php if ($canDeleteHouseholdSurveys) : ?>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-danger nutrition-delete-survey-btn"
                        title="Delete household survey"
                        data-survey-id="<?= barangay_h($surveyId) ?>"
                        data-head-display="<?= barangay_h($headDisplay) ?>"
                      >
                        <i class="fas fa-trash-alt"></i>
                      </button>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>
                  </tr>
                  <?php if ($members !== [] || $canManageHouseholdSurveys) : ?>
                  <tr class="collapse nutrition-consolidated-detail" id="<?= barangay_h($collapseId) ?>">
                    <td colspan="<?= $canManageHouseholdSurveys ? 9 : 8 ?>" class="p-0">
                      <div class="nutrition-consolidated-detail-wrap">
                        <div class="row mb-3">
                          <div class="col-md-4"><strong>Birthday:</strong> <?= ($survey['birth_date'] ?? '') !== '' ? barangay_h(date('M j, Y', strtotime((string) $survey['birth_date']))) : '—' ?></div>
                          <div class="col-md-8"><strong>Classification:</strong>
                            <?= $badges !== [] ? barangay_h(implode(', ', $badges)) : '—' ?>
                          </div>
                        </div>
                      <?php if ($members !== []) : ?>
                        <h6 class="text-uppercase text-muted mb-3">Family Members</h6>
                        <div class="table-responsive">
                          <table class="table table-sm table-dark mb-0">
                            <thead>
                              <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Gender</th>
                                <th>Birthday</th>
                                <th>Status</th>
                                <th>Weight</th>
                                <th>Height</th>
                                <th>WFA</th>
                                <th>HFA</th>
                                <th>WFH</th>
                                <?php if ($canEditHouseholdSurveyNames) : ?>
                                <th class="text-center" style="width:4rem;">Edit</th>
                                <?php endif; ?>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($members as $member) :
                                  $memberBadges = [];
                                  if (strtoupper((string) ($member['is_pregnant'] ?? 'NO')) === 'YES') {
                                      $memberBadges[] = 'Pregnant';
                                  }
                                  if (strtoupper((string) ($member['is_lactating'] ?? 'NO')) === 'YES') {
                                      $memberBadges[] = 'Lactating';
                                  }
                                  $memberId = (string) ($member['member_id'] ?? '');
                                  ?>
                              <tr>
                                <td class="nutrition-member-name-cell"><?= barangay_h((string) ($member['member_name'] ?? '')) ?></td>
                                <td><?= barangay_h((string) ($member['relationship'] ?? '')) ?></td>
                                <td><?= barangay_h((string) ($member['gender'] ?? '')) ?></td>
                                <td><?= ($member['birth_date'] ?? '') !== '' ? barangay_h(date('M j, Y', strtotime((string) $member['birth_date']))) : '—' ?></td>
                                <td><?= $memberBadges !== [] ? barangay_h(implode(', ', $memberBadges)) : '—' ?></td>
                                <td><?= ($member['weight_kg'] ?? '') !== '' && $member['weight_kg'] !== null ? barangay_h((string) $member['weight_kg']) . ' kg' : '—' ?></td>
                                <td><?= ($member['height_cm'] ?? '') !== '' && $member['height_cm'] !== null ? barangay_h((string) $member['height_cm']) . ' cm' : '—' ?></td>
                                <?php foreach (['weight_for_age', 'height_for_age', 'weight_for_height'] as $growthField) :
                                    $growthValue = trim((string) ($member[$growthField] ?? ''));
                                    ?>
                                <td>
                                  <?php if ($growthValue !== '') : ?>
                                  <span class="badge <?= barangay_h(nutrition_growth_result_badge_class($growthValue)) ?>"><?= barangay_h($growthValue) ?></span>
                                  <?php else : ?>
                                  —
                                  <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                                <?php if ($canEditHouseholdSurveyNames) : ?>
                                <td class="text-center">
                                  <button
                                    type="button"
                                    class="btn btn-xs btn-outline-light nutrition-edit-member-btn"
                                    title="Edit member name"
                                    data-member-id="<?= barangay_h($memberId) ?>"
                                    data-member-name="<?= barangay_h((string) ($member['member_name'] ?? '')) ?>"
                                  >
                                    <i class="fas fa-pen"></i>
                                  </button>
                                </td>
                                <?php endif; ?>
                              </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      <?php elseif ($canEditHouseholdSurveyNames) : ?>
                        <p class="text-muted mb-0">No family members recorded for this household.</p>
                      <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endif; ?>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

<?php if ($canEditHouseholdSurveyNames) : ?>
        <?= csrf_field(); ?>
        <div class="modal fade" id="nutritionEditHeadModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content nutrition-panel">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i>Edit Household Head Name</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              </div>
              <form id="nutritionEditHeadForm">
                <div class="modal-body">
                  <input type="hidden" name="survey_id" id="nutritionEditHeadSurveyId">
                  <div class="form-group">
                    <label for="nutritionEditHeadLast">Last Name</label>
                    <input type="text" class="form-control" id="nutritionEditHeadLast" name="head_last_name" required>
                  </div>
                  <div class="form-group">
                    <label for="nutritionEditHeadFirst">First Name</label>
                    <input type="text" class="form-control" id="nutritionEditHeadFirst" name="head_first_name" required>
                  </div>
                  <div class="form-group">
                    <label for="nutritionEditHeadMiddle">Middle Name</label>
                    <input type="text" class="form-control" id="nutritionEditHeadMiddle" name="head_middle_name">
                  </div>
                  <div class="form-group mb-0">
                    <label for="nutritionEditHeadSuffix">Suffix</label>
                    <input type="text" class="form-control" id="nutritionEditHeadSuffix" name="head_suffix" placeholder="Jr., Sr., III">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-success">Save Name</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="modal fade" id="nutritionEditMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content nutrition-panel">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen mr-2"></i>Edit Member Name</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              </div>
              <form id="nutritionEditMemberForm">
                <div class="modal-body">
                  <input type="hidden" name="member_id" id="nutritionEditMemberId">
                  <div class="form-group mb-0">
                    <label for="nutritionEditMemberName">Member Name</label>
                    <input type="text" class="form-control" id="nutritionEditMemberName" name="member_name" required>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-success">Save Name</button>
                </div>
              </form>
            </div>
          </div>
        </div>
<?php elseif ($canDeleteHouseholdSurveys) : ?>
        <?= csrf_field(); ?>
<?php endif; ?>
