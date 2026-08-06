<?php

$nutritionPageScript = <<<'HTML'
<script>
var familyMemberIndex = 0;

function nutritionUpdateFamilyMembersEmptyState() {
  var count = $('#familyMembersContainer .nutrition-family-member-card').length;
  $('#familyMembersEmpty').toggle(count === 0);
  $('#familyMemberCountBadge').text(count);
}

function nutritionRelationshipOptionsHtml(index) {
  var options = window.nutritionRelationshipOptions || [];
  var html = '<option value="">Select relationship</option>';
  options.forEach(function (label) {
    html += '<option value="' + $('<div>').text(label).html() + '">' + $('<div>').text(label).html() + '</option>';
  });
  return html;
}

function nutritionFeedingCheckboxes(index, prefix, title) {
  var base = 'family_members[' + index + '][' + prefix + '_';
  return (
    '<div class="nutrition-feeding-group mt-3">' +
      '<label class="d-block mb-2 font-weight-bold">' + title + '</label>' +
      '<div class="nutrition-checkbox-grid">' +
        '<div class="custom-control custom-checkbox">' +
          '<input type="checkbox" class="custom-control-input" id="' + prefix + '_exclusive_' + index + '" name="' + base + 'exclusive_breastfeeding]" value="YES">' +
          '<label class="custom-control-label" for="' + prefix + '_exclusive_' + index + '">Exclusive Breastfeeding</label>' +
        '</div>' +
        '<div class="custom-control custom-checkbox">' +
          '<input type="checkbox" class="custom-control-input" id="' + prefix + '_mixed_' + index + '" name="' + base + 'mixed_feeding]" value="YES">' +
          '<label class="custom-control-label" for="' + prefix + '_mixed_' + index + '">Mixed Feeding</label>' +
        '</div>' +
        '<div class="custom-control custom-checkbox">' +
          '<input type="checkbox" class="custom-control-input" id="' + prefix + '_bottle_' + index + '" name="' + base + 'bottle_feeding]" value="YES">' +
          '<label class="custom-control-label" for="' + prefix + '_bottle_' + index + '">Bottle Feeding</label>' +
        '</div>' +
        '<div class="custom-control custom-checkbox">' +
          '<input type="checkbox" class="custom-control-input nutrition-other-feeding-toggle" id="' + prefix + '_other_' + index + '" name="' + base + 'other_feeding]" value="YES" data-target="#' + prefix + '_other_specify_' + index + '">' +
          '<label class="custom-control-label" for="' + prefix + '_other_' + index + '">Others</label>' +
        '</div>' +
      '</div>' +
      '<input type="text" class="form-control mt-2 nutrition-other-specify" id="' + prefix + '_other_specify_' + index + '" name="' + base + 'other_specify]" placeholder="Specify other feeding method" disabled>' +
    '</div>'
  );
}

function nutritionBuildFamilyMemberCard(index) {
  return (
    '<div class="nutrition-family-member-card mb-3" data-member-index="' + index + '">' +
      '<div class="d-flex justify-content-between align-items-center mb-3">' +
        '<h6 class="mb-0"><i class="fas fa-user mr-2"></i>Family Member #' + (index + 1) + '</h6>' +
        '<button type="button" class="btn btn-outline-danger btn-sm remove-family-member-btn"><i class="fas fa-trash-alt"></i></button>' +
      '</div>' +
      '<div class="row">' +
        '<div class="col-md-6 form-group">' +
          '<label>Name <span class="text-danger">*</span></label>' +
          '<input type="text" class="form-control" name="family_members[' + index + '][member_name]">' +
        '</div>' +
        '<div class="col-md-6 form-group">' +
          '<label>Relationship to Head of Household</label>' +
          '<select class="form-control" name="family_members[' + index + '][relationship]">' + nutritionRelationshipOptionsHtml(index) + '</select>' +
        '</div>' +
        '<div class="col-md-4 form-group">' +
          '<label>Gender (Boy / Girl)</label>' +
          '<select class="form-control family-member-growth-trigger" name="family_members[' + index + '][gender]">' +
            '<option value="">Select gender</option>' +
            '<option value="Male">Male (Boy)</option>' +
            '<option value="Female">Female (Girl)</option>' +
          '</select>' +
        '</div>' +
        '<div class="col-md-4 form-group">' +
          '<label>Birthday</label>' +
          '<input type="date" class="form-control family-member-growth-trigger" name="family_members[' + index + '][birth_date]">' +
        '</div>' +
        '<div class="col-md-4 form-group">' +
          '<label>Age (from birthday)</label>' +
          '<div class="form-control-plaintext nutrition-member-age-label text-muted">Enter birthday</div>' +
          '<input type="hidden" class="family-member-age-months" name="family_members[' + index + '][age_months_display]" value="">' +
        '</div>' +
        '<div class="col-md-4 form-group family-member-status-wrap" style="display:none;">' +
          '<label class="d-block mb-2">Status</label>' +
          '<div class="d-flex flex-wrap gap-3">' +
            '<div class="custom-control custom-checkbox mr-3">' +
              '<input type="checkbox" class="custom-control-input family-member-pregnant" id="family_pregnant_' + index + '" name="family_members[' + index + '][is_pregnant]" value="YES">' +
              '<label class="custom-control-label" for="family_pregnant_' + index + '">Pregnant</label>' +
            '</div>' +
            '<div class="custom-control custom-checkbox">' +
              '<input type="checkbox" class="custom-control-input family-member-lactating" id="family_lactating_' + index + '" name="family_members[' + index + '][is_lactating]" value="YES">' +
              '<label class="custom-control-label" for="family_lactating_' + index + '">Lactating</label>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="family-child-anthropometry nutrition-child-anthro" style="display:none;">' +
        '<div class="nutrition-child-anthro-banner mb-3">' +
          '<i class="fas fa-baby mr-1"></i>' +
          '<strong>Child 0–5 years</strong> — enter date measured, weight &amp; height. ' +
          'Results use <span class="nutrition-growth-sex-label">boy/girl</span> growth standards.' +
          '<div class="nutrition-growth-expected small mt-1 text-muted"></div>' +
        '</div>' +
        '<div class="row">' +
          '<div class="col-md-3 form-group">' +
            '<label>Date Measured <span class="text-danger">*</span></label>' +
            '<input type="date" class="form-control family-member-growth-input family-member-date-measured" name="family_members[' + index + '][date_measured]" value="">' +
          '</div>' +
          '<div class="col-md-2 form-group">' +
            '<label>Weight (kg) <span class="text-danger">*</span></label>' +
            '<input type="number" min="0" step="0.01" class="form-control family-member-growth-input" name="family_members[' + index + '][weight_kg]" placeholder="kg">' +
          '</div>' +
          '<div class="col-md-2 form-group">' +
            '<label>Height / Length (cm) <span class="text-danger">*</span></label>' +
            '<input type="number" min="0" step="0.1" class="form-control family-member-growth-input" name="family_members[' + index + '][height_cm]" placeholder="cm">' +
          '</div>' +
          '<div class="col-md-5 form-group">' +
            '<label class="d-block">Nutrition Result (auto-computed)</label>' +
            '<div class="nutrition-growth-results small">' +
              '<div><span class="text-muted">Weight for Age:</span> <span class="nutrition-growth-wfa badge badge-secondary">—</span></div>' +
              '<div><span class="text-muted">Height for Age:</span> <span class="nutrition-growth-hfa badge badge-secondary">—</span></div>' +
              '<div><span class="text-muted">Weight for Height/Length:</span> <span class="nutrition-growth-wfh badge badge-secondary">—</span></div>' +
              '<input type="hidden" name="family_members[' + index + '][weight_for_age]" value="">' +
              '<input type="hidden" name="family_members[' + index + '][height_for_age]" value="">' +
              '<input type="hidden" name="family_members[' + index + '][weight_for_height]" value="">' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="family-pregnant-fields nutrition-member-extra-fields" style="display:none;">' +
        '<div class="form-group col-md-6 px-0">' +
          '<label>How Many Months</label>' +
          '<input type="number" min="1" max="9" class="form-control nutrition-input-narrow" name="family_members[' + index + '][pregnancy_months]" placeholder="Months">' +
        '</div>' +
        '<div class="form-group col-md-12 px-0">' +
          '<label class="d-block font-weight-bold mb-2">Nutritional Status (Pregnant)</label>' +
          '<div class="nutrition-checkbox-grid nutrition-checkbox-grid--inline">' +
            (window.nutritionPregnantStatusOptions || []).map(function (label, i) {
              var id = 'pregnant_status_' + index + '_' + i;
              return (
                '<div class="custom-control custom-radio">' +
                  '<input type="radio" class="custom-control-input" id="' + id + '" name="family_members[' + index + '][pregnant_nutrition_status]" value="' + $('<div>').text(label).html() + '">' +
                  '<label class="custom-control-label" for="' + id + '">' + $('<div>').text(label).html() + '</label>' +
                '</div>'
              );
            }).join('') +
          '</div>' +
        '</div>' +
        nutritionFeedingCheckboxes(index, 'planned', 'Planned Infant Feeding Method') +
      '</div>' +
      '<div class="family-lactating-fields nutrition-member-extra-fields" style="display:none;">' +
        nutritionFeedingCheckboxes(index, 'lactating', 'Infant Feeding Method') +
      '</div>' +
    '</div>'
  );
}

function nutritionGrowthBadgeClass(result) {
  var value = (result || '').toLowerCase();
  if (value === 'suw' || value === 'severely stunted' || value === 'sev wasted') return 'badge-danger';
  if (value === 'uw' || value === 'stunted' || value === 'wasted') return 'badge-warning';
  if (value === 'ow' || value === 'ob') return 'badge-info';
  if (value === 'tall') return 'badge-primary';
  if (value === 'normal') return 'badge-success';
  return 'badge-secondary';
}

function nutritionSetGrowthResult($card, field, value) {
  var fieldMap = { wfa: 'weight_for_age', hfa: 'height_for_age', wfh: 'weight_for_height' };
  var display = value || '—';
  var badgeClass = value ? nutritionGrowthBadgeClass(value) : 'badge-secondary';
  $card.find('.nutrition-growth-' + field)
    .text(display)
    .removeClass('badge-secondary badge-success badge-warning badge-danger badge-info badge-primary')
    .addClass(badgeClass);
  $card.find('input[name*="[' + fieldMap[field] + ']"]').val(value || '');
}

function nutritionClearGrowthResults($card) {
  nutritionSetGrowthResult($card, 'wfa', '');
  nutritionSetGrowthResult($card, 'hfa', '');
  nutritionSetGrowthResult($card, 'wfh', '');
  $card.find('.nutrition-growth-expected').text('');
}

function nutritionFormatAgeLabel(ageMonths) {
  if (ageMonths === null || ageMonths === undefined || isNaN(ageMonths)) {
    return 'Enter birthday';
  }
  var years = Math.floor(ageMonths / 12);
  var months = ageMonths % 12;
  return years + 'y ' + months + 'm (' + ageMonths + ' months)';
}

function nutritionRefreshFamilyMemberGrowth($card) {
  var gender = $card.find('select[name*="[gender]"]').val();
  var birthDate = $card.find('input[name*="[birth_date]"]').val();
  var weight = parseFloat($card.find('input[name*="[weight_kg]"]').val() || '0');
  var height = parseFloat($card.find('input[name*="[height_cm]"]').val() || '0');
  var surveyDate = $('#survey_date').val();
  var $dateMeasured = $card.find('input[name*="[date_measured]"]');
  var dateMeasured = ($dateMeasured.val() || '').trim();
  var $anthro = $card.find('.family-child-anthropometry');
  var $ageLabel = $card.find('.nutrition-member-age-label');

  if (!birthDate) {
    $ageLabel.text('Enter birthday').addClass('text-muted');
    $card.find('.family-member-age-months').val('');
    $anthro.hide();
    nutritionClearGrowthResults($card);
    return;
  }

  if (!dateMeasured) {
    dateMeasured = surveyDate || new Date().toISOString().slice(0, 10);
    $dateMeasured.val(dateMeasured);
  }

  $.getJSON('nutritionFamilyMemberGrowth.php', {
    gender: gender || '',
    birth_date: birthDate,
    weight_kg: weight > 0 ? weight : 0,
    height_cm: height > 0 ? height : 0,
    survey_date: surveyDate,
    date_measured: dateMeasured
  }, function (res) {
    if (!res || !res.ok) {
      nutritionClearGrowthResults($card);
      return;
    }

    $ageLabel.text(res.age_label || nutritionFormatAgeLabel(res.age_months)).removeClass('text-muted');
    $card.find('.family-member-age-months').val(res.age_months != null ? res.age_months : '');

    if (!res.is_child_0_to_5) {
      $anthro.hide();
      $card.find('input[name*="[weight_kg]"], input[name*="[height_cm]"], input[name*="[date_measured]"]').val('');
      nutritionClearGrowthResults($card);
      if (res.age_months != null && res.age_months > 60) {
        $ageLabel.html(
          $('<span>').text(res.age_label || nutritionFormatAgeLabel(res.age_months)).prop('outerHTML') +
          ' <span class="badge badge-secondary ml-1">Over 5 yrs — no OPT weighing</span>'
        );
      }
      return;
    }

    $anthro.show();
    if (!$dateMeasured.val()) {
      $dateMeasured.val(dateMeasured);
    }
    var sexText = res.sex_label ? (res.sex_label + ' standards') : 'boy/girl standards (select gender)';
    $card.find('.nutrition-growth-sex-label').text(sexText);

    if (res.expected_weight_kg && res.expected_height_cm && res.sex_label) {
      $card.find('.nutrition-growth-expected').text(
        'Expected median for this age (' + res.sex_label + '): ~' +
        res.expected_weight_kg + ' kg · ~' + res.expected_height_cm + ' cm'
      );
    } else if (!gender) {
      $card.find('.nutrition-growth-expected').text('Select Male (Boy) or Female (Girl) to apply the correct growth standard.');
    } else {
      $card.find('.nutrition-growth-expected').text('');
    }

    if (weight > 0 && height > 0 && gender) {
      nutritionSetGrowthResult($card, 'wfa', res.weight_for_age || '');
      nutritionSetGrowthResult($card, 'hfa', res.height_for_age || '');
      nutritionSetGrowthResult($card, 'wfh', res.weight_for_height || '');
    } else {
      nutritionSetGrowthResult($card, 'wfa', '');
      nutritionSetGrowthResult($card, 'hfa', '');
      nutritionSetGrowthResult($card, 'wfh', '');
    }
  }).fail(function () {
    $ageLabel.text('Could not compute age').addClass('text-muted');
    $anthro.hide();
    nutritionClearGrowthResults($card);
  });
}

function nutritionRefreshFamilyMemberLabels() {
  $('#familyMembersContainer .nutrition-family-member-card').each(function (idx) {
    $(this).find('h6').first().html('<i class="fas fa-user mr-2"></i>Family Member #' + (idx + 1));
  });
}

function nutritionToggleOtherSpecify($checkbox) {
  var target = $($checkbox.data('target'));
  if (!$checkbox.is(':checked')) {
    target.prop('disabled', true).val('');
    return;
  }
  target.prop('disabled', false).focus();
}

$('#addFamilyMemberBtn, .nutrition-add-first-member').on('click', function () {
  $('#familyMembersContainer').append(nutritionBuildFamilyMemberCard(familyMemberIndex));
  familyMemberIndex++;
  nutritionUpdateFamilyMembersEmptyState();
  var $lastCard = $('#familyMembersContainer .nutrition-family-member-card').last();
  if ($lastCard.length) {
    $('html, body').animate({ scrollTop: $lastCard.offset().top - 120 }, 250);
    $lastCard.find('input[name*="[member_name]"]').focus();
  }
});

$(document).on('click', '.remove-family-member-btn', function () {
  $(this).closest('.nutrition-family-member-card').remove();
  nutritionRefreshFamilyMemberLabels();
  nutritionUpdateFamilyMembersEmptyState();
});

$(document).on('change', '.family-member-pregnant', function () {
  $(this).closest('.nutrition-family-member-card').find('.family-pregnant-fields').toggle(this.checked);
});

$(document).on('change', '.family-member-lactating', function () {
  $(this).closest('.nutrition-family-member-card').find('.family-lactating-fields').toggle(this.checked);
});

function nutritionClearHeadFemaleStatus() {
  $('#head_is_pregnant, #head_is_lactating').prop('checked', false);
  $('#head_pregnancy_months').val('');
  $('input[name="head_pregnant_nutrition_status"]').prop('checked', false);
  $('#head_planned_exclusive, #head_planned_mixed, #head_planned_bottle, #head_planned_other').prop('checked', false);
  $('#head_lactating_exclusive, #head_lactating_mixed, #head_lactating_bottle, #head_lactating_other').prop('checked', false);
  $('#head_planned_other_specify, #head_lactating_other_specify').prop('disabled', true).val('');
  $('#headPregnantFields, #headLactatingFields').hide();
}

function nutritionSyncHeadFemaleStatus() {
  var isFemale = $('#gender').val() === 'Female';
  $('#headFemaleStatusBlock').toggle(isFemale);
  if (!isFemale) {
    nutritionClearHeadFemaleStatus();
    return;
  }
  $('#headPregnantFields').toggle($('#head_is_pregnant').is(':checked'));
  $('#headLactatingFields').toggle($('#head_is_lactating').is(':checked'));
}

function nutritionSyncFamilyMemberFemaleStatus($card) {
  var gender = $card.find('select[name*="[gender]"]').val();
  var isFemale = gender === 'Female';
  $card.find('.family-member-status-wrap').toggle(isFemale);
  if (!isFemale) {
    $card.find('.family-member-pregnant, .family-member-lactating').prop('checked', false);
    $card.find('.family-pregnant-fields, .family-lactating-fields').hide();
    $card.find('input[name*="[pregnancy_months]"]').val('');
    $card.find('input[name*="[pregnant_nutrition_status]"]').prop('checked', false);
    $card.find('.family-pregnant-fields input[type="checkbox"], .family-lactating-fields input[type="checkbox"]').prop('checked', false);
    $card.find('.nutrition-other-specify').prop('disabled', true).val('');
  }
}

$('#gender').on('change', nutritionSyncHeadFemaleStatus);
$('#head_is_pregnant').on('change', function () {
  $('#headPregnantFields').toggle(this.checked);
  if (!this.checked) {
    $('#head_pregnancy_months').val('');
    $('input[name="head_pregnant_nutrition_status"]').prop('checked', false);
    $('#head_planned_exclusive, #head_planned_mixed, #head_planned_bottle, #head_planned_other').prop('checked', false);
    $('#head_planned_other_specify').prop('disabled', true).val('');
  }
});
$('#head_is_lactating').on('change', function () {
  $('#headLactatingFields').toggle(this.checked);
  if (!this.checked) {
    $('#head_lactating_exclusive, #head_lactating_mixed, #head_lactating_bottle, #head_lactating_other').prop('checked', false);
    $('#head_lactating_other_specify').prop('disabled', true).val('');
  }
});

$(document).on('change', '.nutrition-family-member-card select[name*="[gender]"]', function () {
  nutritionSyncFamilyMemberFemaleStatus($(this).closest('.nutrition-family-member-card'));
});

$(document).on('change', '.nutrition-other-feeding-toggle', function () {
  nutritionToggleOtherSpecify($(this));
});

function nutritionSyncPrfOtherField(radioName, otherInputSelector, triggerValue) {
  var selected = $('input[name="' + radioName + '"]:checked').val() || '';
  var $other = $(otherInputSelector);
  if (selected === triggerValue) {
    $other.show().prop('disabled', false);
  } else {
    $other.hide().prop('disabled', true).val('');
  }
}

function nutritionSyncPrfToggles() {
  nutritionSyncPrfOtherField('house_ownership', '#house_ownership_other', 'Others');
  nutritionSyncPrfOtherField('complementary_meals', '#complementary_meals_other', 'Others');
  nutritionSyncPrfOtherField('complementary_snacks', '#complementary_snacks_other', 'Others');
  nutritionSyncPrfOtherField('child_physical_activity', '#child_physical_activity_other', 'Others');

  var garbage = $('input[name="garbage_disposal"]:checked').val() || '';
  if (garbage === 'Uncollected') {
    $('#garbageUncollectedOptions').show();
  } else {
    $('#garbageUncollectedOptions').hide();
    $('input[name="garbage_uncollected_type"]').prop('checked', false);
  }

  var fp = $('input[name="practices_family_planning"]:checked').val() || 'NO';
  if (fp === 'YES') {
    $('#familyPlanningMethodsWrap').show();
  } else {
    $('#familyPlanningMethodsWrap').hide();
    $('#familyPlanningMethodsWrap input[type="checkbox"]').prop('checked', false);
  }
}

$(document).on('change', 'input[name="house_ownership"], input[name="garbage_disposal"], input[name="practices_family_planning"], input[name="complementary_meals"], input[name="complementary_snacks"], input[name="child_physical_activity"]', nutritionSyncPrfToggles);

$('#is_na_member').on('change', function () {
  if (this.checked) {
    $('#is_4ps, #is_ip').prop('checked', false);
  }
});
$('#is_4ps, #is_ip').on('change', function () {
  if (this.checked) {
    $('#is_na_member').prop('checked', false);
  }
});

nutritionSyncPrfToggles();

$(document).on('input change', '.family-member-growth-input, .family-member-growth-trigger', function () {
  nutritionRefreshFamilyMemberGrowth($(this).closest('.nutrition-family-member-card'));
});

$('#survey_date').on('change', function () {
  $('#familyMembersContainer .nutrition-family-member-card').each(function () {
    nutritionRefreshFamilyMemberGrowth($(this));
  });
});

function nutritionRefreshHouseholdId() {
  var purok = $('#purok_number').val();
  if (!purok || parseInt(purok, 10) < 1) return;
  $.getJSON('nutritionNextHouseholdId.php', { purok: purok }, function (res) {
    if (res && res.household_id) {
      $('#house_hold_id').val(res.household_id);
    }
  });
}

$('#purok_number').on('input change', function () {
  nutritionRefreshHouseholdId();
  var purok = ($(this).val() || '').trim();
  var excelHref = 'nutritionHouseholdSurveyFormExcel.php?layout=form';
  var printHref = 'nutritionHouseholdSurveyForm.php';
  if (purok !== '') {
    excelHref += '&purok=' + encodeURIComponent(purok);
    printHref += '?purok=' + encodeURIComponent(purok);
  }
  $('#downloadHouseholdSurveyExcel').attr('href', excelHref);
  $('#downloadHouseholdSurveyForm').attr('href', printHref);
});
$('#refreshHouseholdId').on('click', nutritionRefreshHouseholdId);

function nutritionResetHouseholdSurveyForm() {
  var $form = $('#householdSurveyForm');
  $form[0].reset();
  $('#barangay_residence_id').val(null).trigger('change');
  $('#familyMembersContainer').empty();
  familyMemberIndex = 0;
  nutritionUpdateFamilyMembersEmptyState();
  nutritionRefreshHouseholdId();
  $('#survey_date').val(new Date().toISOString().slice(0, 10));
  $('#fp_no').prop('checked', true);
  nutritionSyncPrfToggles();
  nutritionSyncHeadFemaleStatus();
}

function nutritionApplyResidentPrefill(resident) {
  if (!resident) {
    return;
  }

  $('#head_last_name').val(resident.head_last_name || '');
  $('#head_first_name').val(resident.head_first_name || '');
  $('#head_middle_name').val(resident.head_middle_name || '');
  $('#head_suffix').val(resident.head_suffix || '');
  $('#birth_date').val(resident.birth_date || '');
  $('#gender').val(resident.gender || '');
  $('#occupation').val(resident.occupation || '');
  $('#is_4ps').prop('checked', resident.is_4ps === 'YES');
  $('#is_pwd').prop('checked', resident.is_pwd === 'YES');
  $('#is_ip').prop('checked', resident.is_ip === 'YES');
  $('#is_solo_parent').prop('checked', resident.is_solo_parent === 'YES');
  nutritionSyncHeadFemaleStatus();

  if (resident.purok_number) {
    $('#purok_number').val(resident.purok_number).trigger('change');
  }

  $('#familyMembersContainer').empty();
  familyMemberIndex = 0;
  var members = Array.isArray(resident.family_members) ? resident.family_members : [];
  members.forEach(function (member) {
    $('#familyMembersContainer').append(nutritionBuildFamilyMemberCard(familyMemberIndex));
    var $card = $('#familyMembersContainer .nutrition-family-member-card').last();
    $card.find('input[name*="[member_name]"]').val(member.member_name || '');
    $card.find('select[name*="[relationship]"]').val(member.relationship || '');
    $card.find('select[name*="[gender]"]').val(member.gender || '');
    $card.find('input[name*="[birth_date]"]').val(member.birth_date || '');
    familyMemberIndex++;
    nutritionSyncFamilyMemberFemaleStatus($card);
    nutritionRefreshFamilyMemberGrowth($card);
  });
  nutritionUpdateFamilyMembersEmptyState();
  nutritionRefreshFamilyMemberLabels();
}

$('#barangay_residence_id').select2({
  theme: 'bootstrap4',
  placeholder: 'Search registered resident…',
  allowClear: true,
  minimumInputLength: 2,
  ajax: {
    url: 'nutritionHouseholdResidentSearch.php',
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

$('#barangay_residence_id').on('change', function () {
  var residenceId = $(this).val();
  if (!residenceId) {
    return;
  }

  $.getJSON('nutritionHouseholdResidentPrefill.php', { residence_id: residenceId }, function (res) {
    if (!res || !res.ok || !res.resident) {
      Swal.fire('Not found', (res && res.error) || 'Could not load resident data.', 'warning');
      return;
    }
    nutritionApplyResidentPrefill(res.resident);
    Swal.fire({
      toast: true,
      position: 'top-end',
      type: 'success',
      title: 'Barangay resident loaded',
      showConfirmButton: false,
      timer: 1800
    });
  }).fail(function (xhr) {
    var msg = 'Could not load resident data.';
    try {
      var parsed = JSON.parse(xhr.responseText || '{}');
      if (parsed.error) msg = parsed.error;
    } catch (e) {}
    Swal.fire('Error', msg, 'error');
  });
});

$('#resetHouseholdSurveyForm').on('click', function () {
  Swal.fire({
    title: 'Reset form?',
    text: 'All entered household and family member data will be cleared.',
    type: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Reset',
    cancelButtonText: 'Cancel'
  }).then(function (result) {
    if (nutritionSwalConfirmed(result)) {
      nutritionResetHouseholdSurveyForm();
    }
  });
});

$('#householdSurveySearch').on('input', function () {
  var query = ($(this).val() || '').toLowerCase().trim();
  $('#householdSurveyTable tbody tr').each(function () {
    if ($(this).hasClass('nutrition-household-empty-row')) {
      return;
    }
    var haystack = String($(this).data('search') || '');
    $(this).toggle(query === '' || haystack.indexOf(query) !== -1);
  });
  $('#householdSurveyMobileList .nutrition-mobile-record').each(function () {
    var haystack = String($(this).data('search') || '');
    $(this).toggle(query === '' || haystack.indexOf(query) !== -1);
  });
});

$('.nutrition-form-step').on('click', function () {
  var target = $(this).data('step-target');
  if (!target) {
    return;
  }
  $('.nutrition-form-step').removeClass('is-active');
  $(this).addClass('is-active');
  var $el = $(target);
  if ($el.length) {
    $('html, body').animate({ scrollTop: $el.offset().top - 88 }, 280);
  }
});

nutritionUpdateFamilyMembersEmptyState();
nutritionSyncHeadFemaleStatus();

(function () {
  var purok = ($('#purok_number').val() || '').trim();
  var excelHref = 'nutritionHouseholdSurveyFormExcel.php?layout=form';
  var printHref = 'nutritionHouseholdSurveyForm.php';
  if (purok !== '') {
    excelHref += '&purok=' + encodeURIComponent(purok);
    printHref += '?purok=' + encodeURIComponent(purok);
  }
  $('#downloadHouseholdSurveyExcel').attr('href', excelHref);
  $('#downloadHouseholdSurveyForm').attr('href', printHref);
})();

$('#householdSurveyForm').on('submit', function (e) {
  e.preventDefault();
  var $form = $(this);
  $('#familyMembersContainer .nutrition-family-member-card').each(function () {
    $(this).find('.nutrition-other-feeding-toggle').each(function () {
      nutritionToggleOtherSpecify($(this));
    });
  });
  var linkedResidence = $.trim($('#barangay_residence_id').val() || '');
  var doSave = function () {
    if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
    $.post('saveNutritionHouseholdSurvey.php', $form.serialize(), function (res) {
      Swal.fire({
        title: 'Saved',
        html: (res.message || 'Household survey recorded.') +
          (res.household_id ? '<div class="mt-2"><code>' + $('<div>').text(res.household_id).html() + '</code></div>' : ''),
        type: 'success'
      }).then(function () { window.location.reload(); });
    }, 'json').fail(function (xhr) {
      var msg = 'Could not save survey.';
      try {
        var data = JSON.parse(xhr.responseText);
        if (data.error) msg = data.error;
      } catch (err) {
        if (xhr.status === 403) {
          msg = 'Session expired. Please refresh the page and try again.';
        } else if (xhr.responseText) {
          msg = 'Server error while saving. Please try again.';
        }
      }
      Swal.fire({ title: 'Error', text: msg, type: 'error' });
    });
  };
  if (linkedResidence === '') {
    Swal.fire({
      title: 'No resident link',
      html: 'This household is not linked to a Barangay resident. Link now for unified Hub records, or continue without linking.',
      type: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Save anyway',
      cancelButtonText: 'Go back'
    }).then(function (result) {
      if (result.value) doSave();
    });
    return;
  }
  doSave();
});
</script>
HTML;
