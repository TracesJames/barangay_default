<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/nutrition_context.php';
nutrition_ensure_table($con);

$user_id = (string) $_SESSION['user_id'];
$stmt_user = $con->prepare('SELECT first_name, last_name, image, user_type FROM users WHERE id = ?');
$stmt_user->bind_param('s', $user_id);
$stmt_user->execute();
$row_user = $stmt_user->get_result()->fetch_assoc();
$first_name_user = $row_user['first_name'] ?? 'Admin';
$last_name_user = $row_user['last_name'] ?? '';
$user_type = $row_user['user_type'] ?? 'admin';
$user_image = $row_user['image'] ?? '';
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);

if (!barangay_user_can_access_nutrition_portal($con, $user_id)) {
    header('Location: dashboard.php');
    exit;
}

if (!nutrition_user_can_view_registered_households($con, $user_id)) {
    header('Location: nutritionDashboard.php');
    exit;
}

if (barangay_session_id() === null && barangay_user_can_open_nutrition_city_hub($con, $user_id)) {
    header('Location: barangayHub.php?picker=1&system=nutrition&view=picker');
    exit;
}

$activePage = 'assess';
$selectedResident = null;
$prefillId = trim((string) ($_GET['residence_id'] ?? ''));
if ($prefillId !== '') {
    $selectedResident = nutrition_load_resident($con, $prefillId, (string) $barangay_id);
}
$openAssessModal = $selectedResident !== null || isset($_GET['assess']);

$statusOptions = nutrition_status_options();

$purokFilter = [];
if (barangay_user_is_barangay_nutrition_scholar($con, $user_id)) {
    $purokFilter = staff_assigned_purok_labels($con, $user_id, (string) $barangay_id);
}
$households = nutrition_list_household_surveys(
    $con,
    (string) $barangay_id,
    0,
    $purokFilter !== [] ? ['purok_in' => $purokFilter] : []
);
$canAddHousehold = nutrition_user_can_add_household_surveys($con, $user_id);
$canEditHousehold = nutrition_user_can_edit_household_surveys($con, $user_id);

$totals = ['households' => 0, 'members' => 0, 'children' => 0, 'pregnant_lactating' => 0, 'moved_out' => 0];
$purokOptions = [];
foreach ($households as $household) {
    $purokLabel = trim((string) ($household['purok_label'] ?? ''));
    if ($purokLabel !== '') {
        $purokOptions[$purokLabel] = $purokLabel;
    }
    if (strtoupper((string) ($household['moved_out'] ?? 'NO')) === 'YES') {
        $totals['moved_out']++;
        continue;
    }
    $totals['households']++;
    $totals['members'] += (int) ($household['members_count'] ?? 0);
    $totals['children'] += (int) ($household['children_count'] ?? 0);
    if (strtoupper((string) ($household['has_pregnant'] ?? 'NO')) === 'YES'
        || strtoupper((string) ($household['has_lactating'] ?? 'NO')) === 'YES') {
        $totals['pregnant_lactating']++;
    }
}
natcasesort($purokOptions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registered Households | <?= barangay_h($barangay) ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
<?php require_once '../includes/head_csrf.php'; ?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260805n">
  <style>
    #householdTable td { vertical-align: middle; }
    #householdTable tr.household-moved-out td { opacity: .6; }
  </style>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal nutrition-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark nutrition-navbar">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link text-white" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><h5 class="nav-link text-white mb-0">Registered Households</h5></li>
    </ul>
  </nav>

  <?php require_once '../includes/partials/nutrition_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <?php
        $nutritionPageIcon = 'fa-home';
        $nutritionPageHeading = 'Registered Households';
        $nutritionPageDescription = 'Households registered through the household survey in ' . $barangay
            . ($purokFilter !== [] ? ' (your assigned puroks: ' . implode(', ', $purokFilter) . ')' : '') . '.';
        $nutritionPageActions = ($canAddHousehold
                ? '<a href="nutritionHouseholdSurvey.php" class="btn btn-light btn-sm mr-1"><i class="fas fa-plus mr-1"></i> New Household Survey</a>'
                : '')
            . '<button type="button" class="btn btn-outline-light btn-sm" data-toggle="modal" data-target="#nutritionAssessModal"><i class="fas fa-weight mr-1"></i> Individual Assessment</button>';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>

        <div class="row">
          <div class="col-6 col-lg-3">
            <div class="small-box bg-success">
              <div class="inner"><h3><?= number_format($totals['households']) ?></h3><p>Registered households</p></div>
              <div class="icon"><i class="fas fa-home"></i></div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="small-box bg-info">
              <div class="inner"><h3><?= number_format($totals['members']) ?></h3><p>Household members</p></div>
              <div class="icon"><i class="fas fa-users"></i></div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="small-box bg-primary">
              <div class="inner"><h3><?= number_format($totals['children']) ?></h3><p>Children</p></div>
              <div class="icon"><i class="fas fa-child"></i></div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="small-box bg-warning">
              <div class="inner"><h3><?= number_format($totals['pregnant_lactating']) ?></h3><p>With pregnant / lactating</p></div>
              <div class="icon"><i class="fas fa-baby"></i></div>
            </div>
          </div>
        </div>

        <div class="card nutrition-panel">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-2"></i>Household List</h3>
          </div>
          <div class="card-body">
            <div class="form-row mb-2">
              <div class="col-md-4 mb-2">
                <select id="householdPurokFilter" class="form-control form-control-sm">
                  <option value="">All puroks</option>
                  <?php foreach ($purokOptions as $purokLabel) : ?>
                  <option value="<?= barangay_h($purokLabel) ?>"><?= barangay_h($purokLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if ($totals['moved_out'] > 0) : ?>
              <div class="col-md-4 mb-2 d-flex align-items-center">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="householdShowMovedOut">
                  <label class="custom-control-label" for="householdShowMovedOut">Include moved-out households (<?= (int) $totals['moved_out'] ?>)</label>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <div class="table-responsive">
              <table class="table table-striped table-hover table-sm w-100" id="householdTable">
                <thead>
                  <tr>
                    <th>Household No.</th>
                    <th>Purok</th>
                    <th>Household Head</th>
                    <th class="text-center">Members</th>
                    <th class="text-center">Children</th>
                    <th>Status</th>
                    <th>Last Survey</th>
                    <th>BNS</th>
                    <th class="text-right no-sort">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($households as $household) :
                      $surveyId = (string) ($household['survey_id'] ?? '');
                      $movedOut = strtoupper((string) ($household['moved_out'] ?? 'NO')) === 'YES';
                      $surveyDate = (string) ($household['survey_date'] ?? '');
                      $statusBadges = [];
                      if (strtoupper((string) ($household['has_pregnant'] ?? 'NO')) === 'YES') {
                          $statusBadges[] = ['Pregnant', 'warning'];
                      }
                      if (strtoupper((string) ($household['has_lactating'] ?? 'NO')) === 'YES') {
                          $statusBadges[] = ['Lactating', 'info'];
                      }
                      foreach (nutrition_household_member_badges($household) as $badge) {
                          $statusBadges[] = [$badge, 'secondary'];
                      }
                      ?>
                  <tr class="<?= $movedOut ? 'household-moved-out' : '' ?>" data-moved-out="<?= $movedOut ? 'YES' : 'NO' ?>">
                    <td><code><?= barangay_h((string) ($household['house_hold_id'] ?? '')) ?></code></td>
                    <td><?= barangay_h((string) ($household['purok_label'] ?? '')) ?></td>
                    <td>
                      <?= barangay_h(nutrition_household_head_display($household)) ?>
                      <?php if ($movedOut) : ?>
                      <br><span class="badge badge-secondary">Moved out</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center"><?= number_format((int) ($household['members_count'] ?? 0)) ?></td>
                    <td class="text-center"><?= number_format((int) ($household['children_count'] ?? 0)) ?></td>
                    <td>
                      <?php if ($statusBadges === []) : ?>
                      <span class="text-muted">—</span>
                      <?php else : foreach ($statusBadges as [$label, $tone]) : ?>
                      <span class="badge badge-<?= $tone ?>"><?= barangay_h($label) ?></span>
                      <?php endforeach; endif; ?>
                    </td>
                    <td data-order="<?= barangay_h($surveyDate) ?>"><?= $surveyDate !== '' ? barangay_h(date('M j, Y', strtotime($surveyDate))) : '—' ?></td>
                    <td><?= barangay_h((string) ($household['bns_name'] ?? '')) ?></td>
                    <td class="text-right text-nowrap">
                      <a href="nutritionBarangaySurvey.php?highlight=<?= urlencode($surveyId) ?>" class="btn btn-xs btn-outline-success" title="View in consolidated report">
                        <i class="fas fa-eye"></i>
                      </a>
                      <?php if ($canEditHousehold && !$movedOut) : ?>
                      <a href="nutritionHouseholdSurvey.php?edit=<?= urlencode($surveyId) ?>" class="btn btn-xs btn-outline-warning" title="Edit household and members">
                        <i class="fas fa-edit"></i>
                      </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<div class="modal fade" id="nutritionAssessModal" tabindex="-1" role="dialog" aria-labelledby="nutritionAssessModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="nutritionAssessForm">
        <?= csrf_field(); ?>
        <div class="modal-header">
          <h5 class="modal-title" id="nutritionAssessModalTitle"><i class="fas fa-weight mr-2"></i>Individual Nutrition Assessment</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="residence_id">Child / Resident <span class="text-danger">*</span></label>
            <select id="residence_id" name="residence_id" class="form-control" required style="width:100%">
              <?php if ($selectedResident) :
                $label = trim($selectedResident['last_name'] . ', ' . $selectedResident['first_name']);
              ?>
              <option value="<?= barangay_h((string) $selectedResident['residence_id']) ?>" selected
                data-age="<?= barangay_h((string) nutrition_resident_age_years($selectedResident)) ?>">
                <?= barangay_h(strtoupper($label)) ?>
              </option>
              <?php endif; ?>
            </select>
            <small class="text-muted">Search children aged 0–<?= (int) nutrition_child_max_age_years() ?> in this barangay.</small>
          </div>

          <div class="row">
            <div class="col-md-4 form-group">
              <label for="assessment_date">Assessment Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="assessment_date" name="assessment_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4 form-group">
              <label for="weight_kg">Weight (kg) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.1" max="300" class="form-control" id="weight_kg" name="weight_kg" required>
            </div>
            <div class="col-md-4 form-group">
              <label for="height_cm">Height (cm) <span class="text-danger">*</span></label>
              <input type="number" step="0.1" min="10" max="250" class="form-control" id="height_cm" name="height_cm" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 form-group">
              <label for="muac_cm">MUAC (cm)</label>
              <input type="number" step="0.1" min="5" max="40" class="form-control" id="muac_cm" name="muac_cm" placeholder="Optional">
            </div>
            <div class="col-md-4 form-group">
              <label>BMI (auto)</label>
              <input type="text" class="form-control" id="bmi_preview" readonly placeholder="Calculated automatically">
            </div>
            <div class="col-md-4 form-group">
              <label for="nutritional_status">Nutritional Status <span class="text-danger">*</span></label>
              <select id="nutritional_status" name="nutritional_status" class="form-control" required>
                <?php foreach ($statusOptions as $value => $label) : ?>
                <option value="<?= barangay_h($value) ?>"><?= barangay_h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group mb-0">
            <label for="remarks">Remarks / Intervention Notes</label>
            <textarea id="remarks" name="remarks" class="form-control" rows="3" placeholder="Supplementary feeding, referral, follow-up schedule, etc."></textarea>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" id="nutritionAssessSubmit">
            <i class="fas fa-save mr-1"></i> Save Assessment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script src="../assets/js/barangay-ui.js"></script>
<script>
var householdTable = $('#householdTable').DataTable({
  order: [[6, 'desc']],
  pageLength: 25,
  columnDefs: [{ targets: 'no-sort', orderable: false }],
  language: {
    search: 'Search:',
    searchPlaceholder: 'Household no., head, BNS…',
    emptyTable: 'No registered households yet.<?= $canAddHousehold ? ' Use \u201cNew Household Survey\u201d to register one.' : '' ?>',
    zeroRecords: 'No households match your filters.'
  }
});

$.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
  if (settings.nTable.id !== 'householdTable') return true;
  var row = householdTable.row(dataIndex).node();
  if ($(row).data('moved-out') === 'YES' && !$('#householdShowMovedOut').is(':checked')) return false;
  var purok = $('#householdPurokFilter').val();
  return !purok || data[1] === purok;
});
householdTable.draw();

$('#householdPurokFilter, #householdShowMovedOut').on('change', function () {
  householdTable.draw();
});

function nutritionResidentAge() {
  var data = $('#residence_id').select2('data')[0];
  return data && data.element ? parseInt($(data.element).data('age') || '0', 10) : 0;
}

function nutritionUpdateBmi() {
  var weight = parseFloat($('#weight_kg').val() || '0');
  var height = parseFloat($('#height_cm').val() || '0');
  if (weight <= 0 || height <= 0) {
    $('#bmi_preview').val('');
    return null;
  }
  var bmi = weight / Math.pow(height / 100, 2);
  $('#bmi_preview').val(bmi.toFixed(2));
  return bmi;
}

function nutritionSuggestStatus(bmi, age) {
  if (!bmi || bmi <= 0) return 'normal';
  if (age < 5) {
    if (bmi < 14) return 'severely_wasted';
    if (bmi < 16) return 'wasted';
    if (bmi < 18.5) return 'underweight';
    if (bmi >= 25) return 'overweight';
    return 'normal';
  }
  if (bmi < 18.5) return 'underweight';
  if (bmi < 25) return 'normal';
  if (bmi < 30) return 'overweight';
  return 'obese';
}

$('#residence_id').select2({
  theme: 'bootstrap4',
  dropdownParent: $('#nutritionAssessModal'),
  placeholder: 'Search child by name or ID',
  minimumInputLength: 2,
  ajax: {
    url: 'nutritionResidentSearch.php',
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return { q: params.term, page: params.page || 1 };
    },
    processResults: function (data) {
      return { results: data.results || [], pagination: data.pagination || { more: false } };
    }
  }
});

$('#weight_kg, #height_cm').on('input', function () {
  var bmi = nutritionUpdateBmi();
  if (bmi) {
    $('#nutritional_status').val(nutritionSuggestStatus(bmi, nutritionResidentAge()));
  }
});

$('#nutritionAssessForm').on('submit', function (e) {
  e.preventDefault();
  if (typeof barangaySyncCsrfForms === 'function') {
    barangaySyncCsrfForms();
  }
  var $btn = $('#nutritionAssessSubmit');
  $btn.prop('disabled', true);

  $.ajax({
    url: 'saveNutritionAssessment.php',
    type: 'POST',
    data: $(this).serialize(),
    dataType: 'json',
    success: function (res) {
      Swal.fire({
        title: 'Assessment saved',
        text: res.message || 'Nutrition assessment recorded successfully.',
        type: 'success',
        confirmButtonColor: '#28a745'
      }).then(function () {
        window.location.href = res.redirect || 'nutritionProfiles.php';
      });
    }
  }).fail(function (xhr) {
    $btn.prop('disabled', false);
    var msg = 'Could not save assessment.';
    try {
      var data = JSON.parse(xhr.responseText);
      if (data.error) msg = data.error;
    } catch (err) {}
    Swal.fire({ title: 'Error', text: msg, type: 'error', confirmButtonColor: '#6610f2' });
  });
});

<?php if ($openAssessModal) : ?>
$('#nutritionAssessModal').modal('show');
<?php endif; ?>
</script>
</body>
</html>
