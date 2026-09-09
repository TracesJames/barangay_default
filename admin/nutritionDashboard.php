<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activePage = 'dashboard';
$nutritionPageTitle = 'Nutrition Dashboard';

$barangayId = (string) $barangay_id;
$totals = nutrition_scoped_totals($con, $barangayId);
$atRisk = (int) ($totals['at_risk'] ?? 0);

$householdSurveyCount = 0;
if (barangay_table_exists($con, 'nutrition_household_survey')) {
    $surveyCountStmt = $con->prepare(
        'SELECT COUNT(*) AS total FROM nutrition_household_survey WHERE barangay_id = ?'
    );
    if ($surveyCountStmt) {
        $surveyCountStmt->bind_param('s', $barangayId);
        $surveyCountStmt->execute();
        $householdSurveyCount = (int) ($surveyCountStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $surveyCountStmt->close();
    }
}

$coverage = ((int) $totals['children'] > 0)
    ? (int) round(((int) $totals['assessed'] / (int) $totals['children']) * 100)
    : 0;

$recentRows = nutrition_recent_activity_rows($con, $barangayId, 8);

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
?>
        <div class="nutrition-welcome">
          <div class="row align-items-center">
            <div class="col-auto d-none d-md-block">
              <img src="<?= barangay_h($sidebarLogo) ?>" alt="" class="rounded-circle nutrition-welcome-logo">
            </div>
            <div class="col-lg-8">
              <h1>Nutrition Portal</h1>
              <p><?= barangay_h($barangay . ' · ' . $zone . ' · ' . $district) ?> — Monitor child nutrition and growth assessments</p>
              <div class="nutrition-date"><i class="far fa-calendar-alt mr-1"></i> <?= date('l, F j, Y') ?></div>
              <?php if (!empty($isSsa) || $isBnsAdmin || $isNutritionPortalAdmin || $isCnpc) : ?>
              <div class="nutrition-actions mt-2">
                <a href="nutritionSuperDashboard.php" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-th-large"></i> City Nutrition Dashboard
                </a>
                <a href="barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-exchange-alt"></i> Switch Barangay
                </a>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if ((int) $totals['children'] === 0 && $householdSurveyCount === 0) : ?>
        <div class="alert alert-dark border-success nutrition-empty-hint mb-4">
          <i class="fas fa-info-circle mr-2 text-success"></i>
          No household surveys or child assessments yet for <strong><?= barangay_h($barangay) ?></strong>.
          Start with <a href="nutritionHouseholdSurvey.php" class="text-success font-weight-bold">Household Survey</a>
          or <a href="nutritionAssess.php" class="text-success font-weight-bold">New Assessment</a>.
        </div>
        <?php endif; ?>

        <h2 class="nutrition-section-heading"><i class="fas fa-bolt mr-2"></i>Quick Actions</h2>
        <div class="nutrition-workflow-grid">
          <a href="nutritionHouseholdSurvey.php" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-home"></i></span>
            <span class="nutrition-workflow-card-title">Household Survey</span>
            <span class="nutrition-workflow-card-desc">Record household head and family member nutrition data with auto growth assessment.</span>
          </a>
          <a href="nutritionAssess.php" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-weight"></i></span>
            <span class="nutrition-workflow-card-title">New Assessment</span>
            <span class="nutrition-workflow-card-desc">Link a nutrition assessment to an existing barangay resident record.</span>
          </a>
          <a href="nutritionBarangaySurvey.php" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-poll"></i></span>
            <span class="nutrition-workflow-card-title">Consolidated Report</span>
            <span class="nutrition-workflow-card-desc">View, filter, and print all household surveys for this barangay.</span>
          </a>
          <a href="nutritionEoptPrint.php" target="_blank" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-file-medical"></i></span>
            <span class="nutrition-workflow-card-title">e-OPT Plus Print</span>
            <span class="nutrition-workflow-card-desc">Form 1A / 1B / 1C, DQC, graphs, and monitoring lists from household survey data.</span>
          </a>
          <a href="nutritionProfiles.php" class="nutrition-workflow-card">
            <span class="nutrition-workflow-card-icon"><i class="fas fa-clipboard-list"></i></span>
            <span class="nutrition-workflow-card-title">Nutrition Profiles</span>
            <span class="nutrition-workflow-card-desc">Browse assessed residents and filter by nutritional status.</span>
          </a>
        </div>

        <h2 class="nutrition-section-heading"><i class="fas fa-chart-bar mr-2"></i>Overview</h2>
        <div class="nutrition-stats">
          <a href="nutritionBarangaySurvey.php" class="nutrition-stat nutrition-stat--children">
            <i class="fas fa-child nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) $totals['children']) ?></div>
            <div class="nutrition-stat-label"><?= barangay_h(nutrition_children_age_label()) ?></div>
          </a>
          <a href="nutritionProfiles.php" class="nutrition-stat nutrition-stat--assessed">
            <i class="fas fa-clipboard-check nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) $totals['assessed']) ?></div>
            <div class="nutrition-stat-label">Assessed</div>
          </a>
          <a href="nutritionHouseholdSurvey.php" class="nutrition-stat nutrition-stat--pending">
            <i class="fas fa-hourglass-half nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) $totals['pending']) ?></div>
            <div class="nutrition-stat-label">Pending Assessment</div>
          </a>
          <a href="nutritionProfiles.php?status=at_risk" class="nutrition-stat nutrition-stat--risk">
            <i class="fas fa-exclamation-triangle nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($atRisk) ?></div>
            <div class="nutrition-stat-label">At-Risk Cases</div>
          </a>
          <a href="nutritionBarangaySurvey.php" class="nutrition-stat nutrition-stat--surveys">
            <i class="fas fa-home nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($householdSurveyCount) ?></div>
            <div class="nutrition-stat-label">Household Surveys</div>
          </a>
          <div class="nutrition-stat nutrition-stat--month" title="Based on measurement / assessment date in the current month">
            <i class="fas fa-calendar-check nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) $totals['this_month']) ?></div>
            <div class="nutrition-stat-label">Assessments This Month</div>
          </div>
          <a href="nutritionPregnantFamiliesReport.php" class="nutrition-stat nutrition-stat--pregnant">
            <i class="fas fa-female nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) ($totals['pregnant'] ?? 0)) ?></div>
            <div class="nutrition-stat-label">Pregnant</div>
          </a>
          <a href="nutritionPregnantFamiliesReport.php" class="nutrition-stat nutrition-stat--pregnant">
            <i class="fas fa-baby nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format((int) ($totals['teenage_pregnant'] ?? 0)) ?></div>
            <div class="nutrition-stat-label">Teenage Pregnant</div>
          </a>
          <div class="nutrition-stat nutrition-stat--coverage">
            <i class="fas fa-percentage nutrition-stat-icon"></i>
            <div class="nutrition-stat-value"><?= number_format($coverage) ?>%</div>
            <div class="nutrition-stat-label">Assessment Coverage</div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-7">
            <div class="card nutrition-panel">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Nutritional Status Breakdown</h3>
              </div>
              <div class="card-body">
                <div class="nutrition-status-grid">
                  <?php
                  $statusCards = [
                      ['key' => 'normal', 'label' => 'Normal', 'class' => 'is-normal'],
                      ['key' => 'underweight', 'label' => 'Underweight', 'class' => 'is-underweight'],
                      ['key' => 'wasted', 'label' => 'Wasted', 'class' => 'is-wasted'],
                      ['key' => 'severely_wasted', 'label' => 'Severely Wasted', 'class' => 'is-severe'],
                      ['key' => 'stunted', 'label' => 'Stunted', 'class' => 'is-stunted'],
                      ['key' => 'overweight', 'label' => 'Overweight', 'class' => 'is-overweight'],
                      ['key' => 'obese', 'label' => 'Obese', 'class' => 'is-obese'],
                  ];
                  foreach ($statusCards as $card) :
                  ?>
                  <a href="nutritionProfiles.php?status=<?= urlencode($card['key']) ?>" class="nutrition-status-chip <?= barangay_h($card['class']) ?>">
                    <span class="nutrition-status-count"><?= number_format((int) ($totals[$card['key']] ?? 0)) ?></span>
                    <span class="nutrition-status-name"><?= barangay_h($card['label']) ?></span>
                  </a>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card nutrition-panel">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-history mr-2"></i>Recent Activity</h3>
                <a href="nutritionBarangaySurvey.php" class="btn btn-xs btn-outline-success">Surveys</a>
              </div>
              <div class="card-body p-0">
                <?php if ($recentRows === []) : ?>
                <div class="p-4 text-center text-muted">
                  No assessments yet for this barangay.
                  <a href="nutritionHouseholdSurvey.php" class="text-success">Add a household survey</a>
                  or
                  <a href="nutritionAssess.php" class="text-success">new assessment</a>.
                </div>
                <?php else : ?>
                <div class="table-responsive">
                  <table class="table table-dark table-sm mb-0 nutrition-recent-table">
                    <thead>
                      <tr>
                        <th>Child / Resident</th>
                        <th>Date</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentRows as $row) :
                          $dateRaw = trim((string) ($row['date'] ?? ''));
                          $dateLabel = $dateRaw !== '' ? date('M j, Y', strtotime($dateRaw)) : '—';
                          $sourceLabel = ($row['source'] ?? '') === 'survey' ? 'Survey' : 'Assessment';
                      ?>
                      <tr>
                        <td>
                          <?= barangay_h((string) ($row['name'] ?? '')) ?>
                          <br>
                          <small class="text-muted">
                            <?= barangay_h((string) ($row['age_label'] ?? '')) ?>
                            <?= ($row['age_label'] ?? '') !== '' ? ' · ' : '' ?>
                            <?= barangay_h($sourceLabel) ?>
                          </small>
                        </td>
                        <td><?= barangay_h($dateLabel) ?></td>
                        <td>
                          <span class="badge <?= nutrition_status_badge_class((string) ($row['status'] ?? 'normal')) ?>">
                            <?= barangay_h(nutrition_status_label((string) ($row['status'] ?? 'normal'))) ?>
                          </span>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
<?php
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
