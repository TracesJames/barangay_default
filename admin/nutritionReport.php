<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activePage = 'report';
$nutritionPageTitle = 'Generate Report';
$snapshot = nutrition_report_snapshot($con, (string) $barangay_id, (string) $barangay);
$totals = $snapshot['totals'];
$atRisk = $totals['underweight'] + $totals['wasted'] + $totals['severely_wasted'] + $totals['stunted'] + $totals['overweight'] + $totals['obese'];

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
?>
        <?php
        $nutritionPageIcon = 'fa-file-alt';
        $nutritionPageHeading = 'Generate Report';
        $nutritionPageDescription = 'Summary of nutrition profiling data for ' . $barangay . '. Print or review status breakdown and recent assessments.';
        $nutritionPageActions = '
            <a href="nutritionPrintReport.php" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-print mr-1"></i> Print</a>
            <a href="nutritionProfiles.php" class="btn btn-outline-light btn-sm"><i class="fas fa-clipboard-list mr-1"></i> Profiles</a>';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>
        <div class="row">
          <div class="col-lg-12">
            <div class="card nutrition-panel">
              <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h3 class="card-title mb-0"><i class="fas fa-file-alt mr-2"></i>Nutrition Profiling Report</h3>
                <div class="btn-group">
                  <a href="nutritionPrintReport.php" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-print mr-1"></i> Print Report</a>
                  <a href="nutritionProfiles.php" class="btn btn-outline-light btn-sm"><i class="fas fa-clipboard-list mr-1"></i> View Profiles</a>
                </div>
              </div>
              <div class="card-body">
                <div class="nutrition-report-meta mb-4">
                  <h4 class="mb-1"><?= barangay_h($nutritionSettings['report_header'] ?? ('Barangay ' . $barangay . ' Nutrition Profiling')) ?></h4>
                  <p class="mb-0 text-muted"><?= barangay_h($barangay . ', ' . $district . ' · Generated ' . date('F j, Y g:i A')) ?></p>
                  <?php if (($nutritionSettings['nutrition_officer'] ?? '') !== '') : ?>
                  <p class="mb-0 text-muted">Nutrition Officer: <?= barangay_h($nutritionSettings['nutrition_officer']) ?></p>
                  <?php endif; ?>
                </div>

                <div class="row mb-4">
                  <div class="col-md-3 col-6 mb-3"><div class="nutrition-report-stat"><span><?= number_format($totals['children']) ?></span><small><?= barangay_h(nutrition_children_age_label()) ?></small></div></div>
                  <div class="col-md-3 col-6 mb-3"><div class="nutrition-report-stat"><span><?= number_format($totals['assessed']) ?></span><small>Assessed</small></div></div>
                  <div class="col-md-3 col-6 mb-3"><div class="nutrition-report-stat"><span><?= number_format($totals['pending']) ?></span><small>Pending</small></div></div>
                  <div class="col-md-3 col-6 mb-3"><div class="nutrition-report-stat"><span><?= number_format($atRisk) ?></span><small>At-Risk</small></div></div>
                </div>

                <h5 class="mb-3">Nutritional Status Summary</h5>
                <div class="table-responsive mb-4">
                  <table class="table table-dark table-bordered">
                    <thead><tr><th>Status</th><th>Count</th></tr></thead>
                    <tbody>
                      <?php foreach (nutrition_status_options() as $key => $label) : ?>
                      <tr>
                        <td><?= barangay_h($label) ?></td>
                        <td><?= number_format((int) ($totals[$key] ?? 0)) ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <h5 class="mb-3">Recent Household Surveys</h5>
                <div class="table-responsive mb-4">
                  <table class="table table-dark table-striped">
                    <thead><tr><th>Date</th><th>Household ID</th><th>Household Head</th><th>Purok</th><th>Gender</th><th>Occupation</th></tr></thead>
                    <tbody>
                      <?php if ($snapshot['household_surveys'] === []) : ?>
                      <tr><td colspan="6" class="text-center text-muted">No household surveys recorded.</td></tr>
                      <?php else : ?>
                      <?php foreach ($snapshot['household_surveys'] as $row) : ?>
                      <tr>
                        <td><?= barangay_h(date('M j, Y', strtotime((string) $row['survey_date']))) ?></td>
                        <td><code><?= barangay_h((string) ($row['house_hold_id'] ?? '')) ?></code></td>
                        <td><?= barangay_h(nutrition_household_head_display($row)) ?></td>
                        <td><?= barangay_h((string) ($row['purok_label'] ?? '')) ?></td>
                        <td><?= barangay_h((string) ($row['gender'] ?? '')) ?></td>
                        <td><?= barangay_h((string) ($row['occupation'] ?? '')) ?></td>
                      </tr>
                      <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <h5 class="mb-3">Barangay Survey Records</h5>
                <div class="table-responsive">
                  <table class="table table-dark table-striped">
                    <thead><tr><th>Period</th><th>Screened</th><th>Malnourished</th><th>At-Risk</th><th>Recommendations</th></tr></thead>
                    <tbody>
                      <?php if ($snapshot['barangay_surveys'] === []) : ?>
                      <tr><td colspan="5" class="text-center text-muted">No barangay surveys recorded.</td></tr>
                      <?php else : ?>
                      <?php foreach ($snapshot['barangay_surveys'] as $row) : ?>
                      <tr>
                        <td><?= barangay_h((string) $row['survey_period']) ?></td>
                        <td><?= number_format((int) ($row['children_screened'] ?? 0)) ?></td>
                        <td><?= number_format((int) ($row['malnourished_cases'] ?? 0)) ?></td>
                        <td><?= number_format((int) ($row['at_risk_cases'] ?? 0)) ?></td>
                        <td><?= barangay_h((string) ($row['recommendations'] ?? '')) ?></td>
                      </tr>
                      <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
<?php require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
