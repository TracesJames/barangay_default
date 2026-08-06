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

if (barangay_session_id() === null && ($isSuperAdmin || $isCityAdmin)) {
    header('Location: barangayHub.php?picker=1&system=nutrition');
    exit;
}

$activePage = 'assess';
$selectedResident = null;
$prefillId = trim((string) ($_GET['residence_id'] ?? ''));
if ($prefillId !== '') {
    $selectedResident = nutrition_load_resident($con, $prefillId, (string) $barangay_id);
}

$statusOptions = nutrition_status_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>New Nutrition Assessment | <?= barangay_h($barangay) ?></title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
<?php require_once '../includes/head_csrf.php'; ?>
  <link rel="stylesheet" href="../assets/css/nutrition-dashboard.css?v=20260805n">
</head>
<body class="hold-transition dark-mode sidebar-mini layout-footer-fixed barangay-portal nutrition-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark nutrition-navbar">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link text-white" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-sm-inline-block"><h5 class="nav-link text-white mb-0">New Assessment</h5></li>
    </ul>
  </nav>

  <?php require_once '../includes/partials/nutrition_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <?php
        $nutritionPageIcon = 'fa-weight';
        $nutritionPageHeading = 'New Nutrition Assessment';
        $nutritionPageDescription = 'Record a nutrition assessment for a registered barangay resident (children 0–' . nutrition_child_max_age_years() . '). For household surveys with family members, use Household Survey instead.';
        $nutritionPageActions = '<a href="nutritionHouseholdSurvey.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home mr-1"></i> Household Survey</a>';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card nutrition-panel">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-weight mr-2"></i>Record Nutrition Assessment</h3>
              </div>
              <form id="nutritionAssessForm">
                <?= csrf_field(); ?>
                <div class="card-body">
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

                  <div class="form-group">
                    <label for="remarks">Remarks / Intervention Notes</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="3" placeholder="Supplementary feeding, referral, follow-up schedule, etc."></textarea>
                  </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                  <a href="nutritionProfiles.php" class="btn btn-secondary">Cancel</a>
                  <button type="submit" class="btn btn-success" id="nutritionAssessSubmit">
                    <i class="fas fa-save mr-1"></i> Save Assessment
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<?php $barangay_script_depth = 1; require_once '../includes/scripts_csrf.php'; ?>
<script src="../assets/js/barangay-ui.js"></script>
<script>
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
</script>
</body>
</html>
