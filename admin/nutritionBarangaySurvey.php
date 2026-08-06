<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activePage = 'barangay_survey';
$nutritionPageTitle = 'Barangay Nutrition Survey';

$filters = [
    'purok' => trim((string) ($_GET['purok'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];

$report = nutrition_household_consolidated_report($con, (string) $barangay_id, $filters);
$koboSubmissions = nutrition_kobo_list_submissions($con, (string) $barangay_id);
$koboConfigured = nutrition_kobo_is_configured($nutritionSettings);
$koboFormUrl = trim((string) ($nutritionSettings['kobo_form_url'] ?? ''));
$koboLastSynced = trim((string) ($nutritionSettings['kobo_last_synced_at'] ?? ''));
$nutritionIncludeScriptsCsrf = true;
$nutritionExtraCss = ['../assets/plugins/sweetalert2/css/sweetalert2.min.css'];
$nutritionExtraJs = [
    '../assets/plugins/sweetalert2/js/sweetalert2.all.min.js',
    '../assets/js/barangay-ui.js',
];

$nutritionPageScript = $nutritionPageScript ?? '';

$nutritionPageScript .= <<<'HTML'
<script>
(function () {
  var params = new URLSearchParams(window.location.search);
  var highlight = params.get('highlight');
  if (!highlight) {
    return;
  }
  var $row = $('tr[data-survey-id="' + highlight.replace(/"/g, '') + '"]');
  if (!$row.length) {
    return;
  }
  $row.addClass('nutrition-consolidated-row--highlight');
  $('html, body').animate({ scrollTop: $row.offset().top - 96 }, 420);
})();
</script>
HTML;

if (nutrition_user_can_manage_household_surveys($con, (string) $user_id)) {
    $nutritionPageScript .= <<<'HTML'
<script>
function nutritionPostJson(url, data, onSuccess, onError) {
  if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
  data.csrf_token = $('input[name="csrf_token"]').val() || (window.barangayCsrfToken ? window.barangayCsrfToken() : '');
  $.post(url, data, onSuccess, 'json').fail(function (xhr) {
    var msg = 'Request failed.';
    try {
      var res = JSON.parse(xhr.responseText);
      if (res.error) msg = res.error;
    } catch (e) {}
    if (typeof onError === 'function') {
      onError(msg);
    } else {
      Swal.fire({ title: 'Error', text: msg, type: 'error' });
    }
  });
}

function nutritionSwalConfirmed(result) {
  return !!(result && (result.value || result.isConfirmed));
}

function nutritionDataAttr($el, name) {
  var value = $el.attr('data-' + name);
  return value === undefined || value === null ? '' : String(value);
}

$(document).on('click', '.nutrition-edit-head-btn', function () {
  var $btn = $(this);
  $('#nutritionEditHeadSurveyId').val(nutritionDataAttr($btn, 'survey-id'));
  $('#nutritionEditHeadLast').val($btn.data('head-last'));
  $('#nutritionEditHeadFirst').val($btn.data('head-first'));
  $('#nutritionEditHeadMiddle').val($btn.data('head-middle'));
  $('#nutritionEditHeadSuffix').val($btn.data('head-suffix'));
  $('#nutritionEditHeadModal').modal('show');
});

$('#nutritionEditHeadForm').on('submit', function (e) {
  e.preventDefault();
  nutritionPostJson('updateNutritionHouseholdSurveyNames.php', {
    action: 'household_head',
    survey_id: $('#nutritionEditHeadSurveyId').val(),
    head_last_name: $('#nutritionEditHeadLast').val(),
    head_first_name: $('#nutritionEditHeadFirst').val(),
    head_middle_name: $('#nutritionEditHeadMiddle').val(),
    head_suffix: $('#nutritionEditHeadSuffix').val()
  }, function (res) {
    Swal.fire({ title: 'Updated', text: res.message || 'Household head name saved.', type: 'success' })
      .then(function () { window.location.reload(); });
  });
});

var nutritionActiveMemberBtn = null;
$(document).on('click', '.nutrition-edit-member-btn', function () {
  nutritionActiveMemberBtn = $(this);
  $('#nutritionEditMemberId').val(nutritionDataAttr(nutritionActiveMemberBtn, 'member-id'));
  $('#nutritionEditMemberName').val(nutritionDataAttr(nutritionActiveMemberBtn, 'member-name'));
  $('#nutritionEditMemberModal').modal('show');
});

$('#nutritionEditMemberForm').on('submit', function (e) {
  e.preventDefault();
  nutritionPostJson('updateNutritionHouseholdSurveyNames.php', {
    action: 'family_member',
    member_id: $('#nutritionEditMemberId').val(),
    member_name: $('#nutritionEditMemberName').val()
  }, function (res) {
    $('#nutritionEditMemberModal').modal('hide');
    if (nutritionActiveMemberBtn) {
      nutritionActiveMemberBtn.data('member-name', res.member_name || $('#nutritionEditMemberName').val());
      nutritionActiveMemberBtn.closest('tr').find('.nutrition-member-name-cell').text(res.member_name || $('#nutritionEditMemberName').val());
    }
    Swal.fire({ title: 'Updated', text: res.message || 'Family member name saved.', type: 'success' });
  });
});

$(document).on('click', '.nutrition-delete-survey-btn', function () {
  var $btn = $(this);
  var surveyId = nutritionDataAttr($btn, 'survey-id');
  var headDisplay = nutritionDataAttr($btn, 'head-display') || 'this household';
  if (!surveyId) {
    Swal.fire({ title: 'Error', text: 'Missing survey record id.', type: 'error' });
    return;
  }
  Swal.fire({
    title: 'Delete household survey?',
    html: 'Remove <strong>' + $('<div>').text(headDisplay).html() + '</strong> and all family members?',
    type: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc3545',
    cancelButtonText: 'Cancel'
  }).then(function (result) {
    if (!nutritionSwalConfirmed(result)) return;
    nutritionPostJson('deleteNutritionHouseholdSurvey.php', { survey_id: surveyId }, function (res) {
      Swal.fire({ title: 'Deleted', text: res.message || 'Household survey removed.', type: 'success' })
        .then(function () { window.location.reload(); });
    });
  });
});
</script>
HTML;
}

if ($koboConfigured) {
    $nutritionPageScript .= <<<'HTML'
<script>
$('#syncKoboBtn').on('click', function () {
  var $btn = $(this).prop('disabled', true);
  if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
  $.post('syncNutritionKobo.php', { csrf_token: $('input[name="csrf_token"]').val() || (window.barangayCsrfToken ? window.barangayCsrfToken() : '') }, function (res) {
    Swal.fire({
      title: 'Synced',
      text: (res.message || 'KoBoToolbox data synced.') + ' (' + (res.synced || 0) + ' records)',
      type: 'success'
    }).then(function () { window.location.reload(); });
  }, 'json').fail(function (xhr) {
    var msg = 'Could not sync KoBoToolbox data.';
    try { var data = JSON.parse(xhr.responseText); if (data.error) msg = data.error; } catch (e) {}
    Swal.fire({ title: 'Sync failed', text: msg, type: 'error' });
  }).always(function () {
    $btn.prop('disabled', false);
  });
});
</script>
HTML;
}

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
require __DIR__ . '/../includes/partials/nutrition_barangay_consolidated_report.php';
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
