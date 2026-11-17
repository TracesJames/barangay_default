<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/csrf.php';
require_once '../includes/partials/nutrition_init.php';

$canRepair = $isSsa || $isNutritionPortalAdmin || $isBnsAdmin || $isCnpc;
if (!$canRepair) {
    http_response_code(403);
    exit('You do not have permission to use the survey repair tools.');
}

$barangayId = (string) ($barangay_id ?? barangay_session_id() ?? '');
$activePage = 'survey_repair';
$nutritionPageTitle = 'Survey Repair';
$flash = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = trim((string) ($_POST['action'] ?? ''));
    $ids = $_POST['survey_ids'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));

    if ($action === 'align_headers') {
        if ($ids === []) {
            $flashError = 'Select at least one survey to align.';
        } else {
            $ok = 0;
            foreach ($ids as $surveyId) {
                $stmt = $con->prepare('SELECT survey_id, barangay_id FROM nutrition_household_survey WHERE survey_id = ? LIMIT 1');
                if (!$stmt) {
                    continue;
                }
                $stmt->bind_param('s', $surveyId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$row) {
                    continue;
                }
                $sidBarangay = (string) ($row['barangay_id'] ?? '');
                if ($barangayId !== '' && $sidBarangay !== $barangayId
                    && !barangay_user_can_pick_barangay($con, $user_id)
                    && !barangay_user_is_cnpc($con, $user_id)
                    && !barangay_user_is_ssa($con, $user_id)) {
                    continue;
                }
                if (nutrition_align_survey_header_to_members($con, $surveyId)) {
                    nutrition_log_household_survey_history(
                        $con,
                        $surveyId,
                        $sidBarangay !== '' ? $sidBarangay : $barangayId,
                        $user_id,
                        'align_headers',
                        'Aligned header counts to saved member rows'
                    );
                    $ok++;
                }
            }
            $flash = "Aligned header counts on {$ok} survey(s). Re-encode members when real child data is available.";
        }
    }
}

$scopeBarangayId = $barangayId;
$cityView = ($isSsa || $isNutritionPortalAdmin || $isCnpc) && isset($_GET['city']) && (string) $_GET['city'] === '1';
if ($cityView) {
    $scopeBarangayId = '';
}

$incomplete = nutrition_list_incomplete_household_surveys($con, $scopeBarangayId, 800);
$audit = nutrition_audit_bns_purok_scope($con, $cityView ? '' : $barangayId);

$orphanCount = 0;
$unassessedCount = 0;
foreach ($incomplete as $row) {
    if (($row['issue'] ?? '') === 'orphan_header') {
        $orphanCount++;
    } elseif (($row['issue'] ?? '') === 'unassessed_members') {
        $unassessedCount++;
    }
}

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
?>
        <?php
        $nutritionPageIcon = 'fa-tools';
        $nutritionPageHeading = 'Survey Repair / Re-assess';
        $nutritionPageDescription = 'Find household surveys with missing members or no growth data, then open them to re-encode or align header counts.';
        $nutritionPageActions = $cityView
            ? '<a class="btn btn-outline-light btn-sm" href="nutritionSurveyRepair.php">This barangay</a>'
            : '<a class="btn btn-outline-light btn-sm" href="nutritionSurveyRepair.php?city=1">City-wide</a>';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>

        <?php if ($flash !== '') : ?>
        <div class="alert alert-success"><?= barangay_h($flash) ?></div>
        <?php endif; ?>
        <?php if ($flashError !== '') : ?>
        <div class="alert alert-danger"><?= barangay_h($flashError) ?></div>
        <?php endif; ?>

        <div class="row mb-3">
          <div class="col-md-4">
            <div class="card nutrition-panel mb-3">
              <div class="card-body">
                <div class="text-muted small">Incomplete surveys</div>
                <div class="h3 mb-0"><?= number_format(count($incomplete)) ?></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card nutrition-panel mb-3">
              <div class="card-body">
                <div class="text-muted small">Orphan headers (no members)</div>
                <div class="h3 mb-0"><?= number_format($orphanCount) ?></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card nutrition-panel mb-3">
              <div class="card-body">
                <div class="text-muted small">Members without assessment</div>
                <div class="h3 mb-0"><?= number_format($unassessedCount) ?></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card nutrition-panel mb-4">
          <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Incomplete household surveys</h3>
          </div>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="align_headers">
            <div class="card-body table-responsive p-0">
              <table class="table table-striped table-hover mb-0">
                <thead>
                  <tr>
                    <th style="width:2rem"><input type="checkbox" id="repairCheckAll"></th>
                    <th>Barangay</th>
                    <th>Purok</th>
                    <th>Household</th>
                    <th>Head</th>
                    <th>Children</th>
                    <th>Members</th>
                    <th>Assessed</th>
                    <th>Issue</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($incomplete === []) : ?>
                  <tr><td colspan="10" class="text-center text-muted py-4">No incomplete surveys in this scope.</td></tr>
                <?php else : ?>
                  <?php foreach ($incomplete as $row) :
                      $head = trim((string) ($row['household_head'] ?? ''));
                      if ($head === '') {
                          $head = trim(($row['head_last_name'] ?? '') . ', ' . ($row['head_first_name'] ?? ''));
                      }
                      $surveyId = (string) ($row['survey_id'] ?? '');
                      ?>
                  <tr>
                    <td><input type="checkbox" name="survey_ids[]" value="<?= barangay_h($surveyId) ?>" class="repair-check"></td>
                    <td><?= barangay_h((string) ($row['barangay_name'] ?? '')) ?></td>
                    <td><?= barangay_h((string) ($row['purok_label'] ?? '')) ?></td>
                    <td><?= barangay_h((string) ($row['house_hold_id'] ?? '')) ?></td>
                    <td><?= barangay_h($head) ?></td>
                    <td><?= (int) ($row['children_count'] ?? 0) ?></td>
                    <td><?= (int) ($row['member_rows'] ?? 0) ?></td>
                    <td><?= (int) ($row['assessed_rows'] ?? 0) ?></td>
                    <td><span class="badge badge-warning"><?= barangay_h((string) ($row['issue_label'] ?? '')) ?></span></td>
                    <td class="text-nowrap">
                      <a class="btn btn-sm btn-success" href="nutritionHouseholdSurvey.php?edit=<?= urlencode($surveyId) ?>">
                        <i class="fas fa-edit"></i> Re-encode
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php if ($incomplete !== []) : ?>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div class="small text-muted">
                <strong>Align headers</strong> sets children/members counts to saved member rows (0 when none).
                Prefer <em>Re-encode</em> when the household still has children to assess.
              </div>
              <button type="submit" class="btn btn-warning btn-sm"
                      onclick="return confirm('Align selected survey headers to member rows? This clears inflated children counts when no members exist.');">
                <i class="fas fa-sync mr-1"></i> Align selected headers
              </button>
            </div>
            <?php endif; ?>
          </form>
        </div>

        <div class="card nutrition-panel mb-4">
          <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-map-marked-alt mr-2"></i>BNS scope audit</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th>Barangay</th>
                  <th>Puroks</th>
                  <th>BNS</th>
                  <th>Gaps</th>
                  <th>Overlaps</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($audit as $row) : ?>
                <tr>
                  <td><?= barangay_h((string) $row['barangay']) ?></td>
                  <td><?= (int) $row['master_count'] ?></td>
                  <td>
                    <?= (int) $row['bns_count'] ?>
                    <?php foreach ($row['bns'] as $bns) : ?>
                      <div class="small text-muted">
                        <?= barangay_h($bns['username']) ?>
                        <?php if (!empty($bns['all_scope'])) : ?>
                          <span class="badge badge-info">all-scope</span>
                        <?php else : ?>
                          (<?= count($bns['assigned']) ?>)
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </td>
                  <td><?= $row['gaps'] === [] ? '—' : barangay_h(implode(', ', $row['gaps'])) ?></td>
                  <td>
                    <?php if ($row['overlaps'] === []) : ?>
                      —
                    <?php else : ?>
                      <?php foreach ($row['overlaps'] as $ov) : ?>
                        <div class="small"><?= barangay_h($ov['label'] . ': ' . implode(', ', $ov['bns'])) ?></div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($row['ok'])) : ?>
                      <span class="badge badge-success">OK</span>
                    <?php else : ?>
                      <span class="badge badge-warning">Review</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
<?php
$nutritionPageScript = <<<'JS'
<script>
(function () {
  var all = document.getElementById('repairCheckAll');
  if (!all) return;
  all.addEventListener('change', function () {
    document.querySelectorAll('.repair-check').forEach(function (el) {
      el.checked = all.checked;
    });
  });
})();
</script>
JS;
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
