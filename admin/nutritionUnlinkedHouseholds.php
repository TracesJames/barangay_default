<?php
/**
 * Nutrition household surveys not linked to a barangay residence_id.
 */
include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/nutrition_context.php';

$user_id = (string) ($_SESSION['user_id'] ?? '');
if (!barangay_user_is_super_admin($con, $user_id)
    && !barangay_user_is_city_admin($con, $user_id)
    && !barangay_user_is_nutrition_portal_admin($con, $user_id)
    && !barangay_user_is_bns_admin($con, $user_id)) {
    header('Location: dashboard.php');
    exit;
}

nutrition_ensure_module_tables($con);

$rows = [];
if (barangay_table_exists($con, 'nutrition_household_survey')) {
    $sql = "SELECT s.survey_id, s.barangay_id, s.house_hold_id, s.head_first_name, s.head_last_name,
                   s.survey_date, s.residence_id, b.barangay
            FROM nutrition_household_survey s
            LEFT JOIN barangay_information b ON b.id = s.barangay_id
            WHERE s.residence_id IS NULL OR TRIM(s.residence_id) = ''
            ORDER BY s.survey_date DESC, s.survey_id DESC
            LIMIT 500";
    $res = $con->query($sql);
    while ($res && ($r = $res->fetch_assoc())) {
        $rows[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Unlinked Nutrition Households</title>
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/plugins/sweetalert2/css/sweetalert2.min.css">
  <?php require_once '../includes/head_csrf.php'; ?>
  <style>
    .link-resident-select { min-width: 220px; }
    .select2-container { width: 100% !important; }
  </style>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed barangay-portal">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item"><span class="nav-link">Unlinked Nutrition Households</span></li>
    </ul>
  </nav>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="nutritionSuperDashboard.php" class="brand-link text-center"><span class="brand-text font-weight-light">Nutrition Hub</span></a>
    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column">
          <li class="nav-item"><a href="nutritionSuperDashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
          <li class="nav-item"><a href="cityReportPack.php" class="nav-link"><i class="nav-icon fas fa-file-alt"></i><p>City Report Pack</p></a></li>
          <li class="nav-item"><a href="nutritionUnlinkedHouseholds.php" class="nav-link active"><i class="nav-icon fas fa-link"></i><p>Unlinked Households</p></a></li>
        </ul>
      </nav>
    </div>
  </aside>
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <h1>Unlinked Nutrition Households</h1>
        <p class="text-muted mb-0">Surveys without a barangay <code>residence_id</code>. Search a resident in the same barangay and click Link.</p>
      </div>
    </section>
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">Showing <?= count($rows) ?> (max 500)</div>
          <div class="card-body table-responsive p-0">
            <table class="table table-striped table-sm mb-0" id="unlinkedTable">
              <thead>
                <tr>
                  <th>Household ID</th>
                  <th>Barangay</th>
                  <th>Head</th>
                  <th>Survey Date</th>
                  <th style="min-width:280px">Link to resident</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($rows === []): ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">All household surveys are linked. Good work.</td></tr>
                <?php else: ?>
                  <?php foreach ($rows as $row): ?>
                    <tr data-survey-id="<?= barangay_h((string) $row['survey_id']) ?>">
                      <td><code><?= barangay_h((string) ($row['house_hold_id'] ?: $row['survey_id'])) ?></code></td>
                      <td><?= barangay_h((string) ($row['barangay'] ?? $row['barangay_id'] ?? '')) ?></td>
                      <td><?= barangay_h(trim(($row['head_first_name'] ?? '') . ' ' . ($row['head_last_name'] ?? ''))) ?></td>
                      <td><?= barangay_h((string) ($row['survey_date'] ?? '')) ?></td>
                      <td>
                        <div class="d-flex align-items-center" style="gap:6px;">
                          <select class="form-control form-control-sm link-resident-select"
                                  data-barangay-id="<?= barangay_h((string) ($row['barangay_id'] ?? '')) ?>"
                                  data-survey-id="<?= barangay_h((string) $row['survey_id']) ?>">
                            <option value="">Search resident…</option>
                          </select>
                          <button type="button" class="btn btn-xs btn-primary btn-link-residence"
                                  data-survey-id="<?= barangay_h((string) $row['survey_id']) ?>"
                                  data-barangay-id="<?= barangay_h((string) ($row['barangay_id'] ?? '')) ?>">
                            Link
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/adminlte.js"></script>
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<script src="../assets/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../assets/js/csrf.js"></script>
<script src="../assets/js/barangay-ui.js"></script>
<script>
(function ($) {
  function csrfToken() {
    var el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
  }

  function initResidentSelect($el) {
    var brgy = $el.data('barangay-id') || '';
    $el.select2({
      theme: 'bootstrap4',
      placeholder: 'Search resident…',
      allowClear: true,
      width: '100%',
      ajax: {
        url: 'nutritionHouseholdResidentSearch.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term || '', page: params.page || 1, barangay_id: brgy };
        },
        processResults: function (data) {
          return {
            results: (data && data.results) ? data.results : [],
            pagination: { more: !!(data && data.pagination && data.pagination.more) }
          };
        }
      }
    });
  }

  $('.link-resident-select').each(function () {
    initResidentSelect($(this));
  });

  $(document).on('click', '.btn-link-residence', function () {
    var $btn = $(this);
    var surveyId = $btn.data('survey-id');
    var barangayId = $btn.data('barangay-id') || '';
    var $row = $btn.closest('tr');
    var residenceId = $.trim($row.find('.link-resident-select').val() || '');
    if (!residenceId) {
      Swal.fire({ title: 'Select a resident', text: 'Search and pick a resident first.', type: 'info' });
      return;
    }
    $btn.prop('disabled', true);
    $.post('linkNutritionHouseholdResidence.php', {
      survey_id: surveyId,
      residence_id: residenceId,
      barangay_id: barangayId,
      csrf_token: csrfToken()
    }, function (res) {
      Swal.fire({
        title: 'Linked',
        text: (res && res.message) ? res.message : 'Survey linked.',
        type: 'success'
      }).then(function () {
        $row.fadeOut(200, function () { $(this).remove(); });
      });
    }, 'json').fail(function (xhr) {
      var msg = 'Could not link survey.';
      try {
        var data = JSON.parse(xhr.responseText);
        if (data.error) msg = data.error;
      } catch (e) {}
      Swal.fire({ title: 'Error', text: msg, type: 'error' });
    }).always(function () {
      $btn.prop('disabled', false);
    });
  });
})(jQuery);
</script>
</body>
</html>
