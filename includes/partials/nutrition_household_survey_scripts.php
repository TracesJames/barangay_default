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

function nutritionSyncRelationshipOther($card) {
  if (!$card || !$card.length) {
    return;
  }
  var $select = $card.find('select[name*="[relationship]"]');
  var $wrap = $card.find('.family-member-relationship-other-wrap');
  var $input = $card.find('input[name*="[relationship_other]"]');
  var isOther = String($select.val() || '') === 'Other Relative';
  $wrap.toggle(isOther);
  $input.prop('disabled', !isOther);
  if (!isOther) {
    $input.val('');
  }
}

function nutritionApplyRelationshipToCard($card, relationship, relationshipOther) {
  var stored = String(relationship || '').trim();
  var otherLabel = 'Other Relative';
  var options = window.nutritionRelationshipOptions || [];
  var selectVal = stored;
  var otherVal = String(relationshipOther || '').trim();

  if (!otherVal) {
    var m = stored.match(/^Other Relative\s*:\s*(.+)$/i);
    if (m) {
      selectVal = otherLabel;
      otherVal = String(m[1] || '').trim();
    } else if (stored && options.indexOf(stored) === -1) {
      selectVal = otherLabel;
      otherVal = stored;
    }
  } else {
    selectVal = otherLabel;
  }

  $card.find('select[name*="[relationship]"]').val(selectVal);
  $card.find('input[name*="[relationship_other]"]').prop('disabled', false).val(otherVal);
  nutritionSyncRelationshipOther($card);
  if (selectVal === otherLabel && otherVal) {
    $card.find('input[name*="[relationship_other]"]').val(otherVal);
  }
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
          '<label>Resident Number <span class="text-danger">*</span></label>' +
          '<input type="text" class="form-control nutrition-readonly-id family-member-residence-display" value="" readonly placeholder="Auto PSGC-Purok-Number">' +
          '<input type="hidden" class="family-member-residence-id" name="family_members[' + index + '][residence_id]" value="">' +
          '<small class="text-muted">Auto-generated as PSGC-Purok-Number when you save (or previewed from Purok).</small>' +
        '</div>' +
        '<div class="col-md-6 form-group">' +
          '<label>Name <span class="text-danger">*</span></label>' +
          '<input type="text" class="form-control" name="family_members[' + index + '][member_name]">' +
        '</div>' +
        '<div class="col-md-6 form-group">' +
          '<label>Relationship to Head of Household</label>' +
          '<select class="form-control family-member-relationship" name="family_members[' + index + '][relationship]">' + nutritionRelationshipOptionsHtml(index) + '</select>' +
          '<div class="family-member-relationship-other-wrap mt-2" style="display:none;">' +
            '<label class="mb-1">Please specify other relationship <span class="text-danger">*</span></label>' +
            '<input type="text" class="form-control family-member-relationship-other" name="family_members[' + index + '][relationship_other]" placeholder="e.g. Cousin, Uncle, Aunt, In-law, Stepchild" disabled maxlength="100">' +
            '<small class="text-muted">Use this only when the relationship is not in the list above (Nephew/Niece are already listed).</small>' +
          '</div>' +
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
          '<input type="text" class="form-control nutrition-date-mdy family-member-growth-trigger" name="family_members[' + index + '][birth_date]" placeholder="MM/DD/YYYY" inputmode="numeric" autocomplete="off">' +
          '<small class="text-muted">MM/DD/YYYY</small>' +
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
        '<div class="col-12 form-group">' +
          '<h6 class="mt-1 mb-2">Check if:</h6>' +
          '<div class="nutrition-checkbox-grid nutrition-checkbox-grid--inline">' +
            '<div class="custom-control custom-checkbox">' +
              '<input type="checkbox" class="custom-control-input family-member-4ps" id="family_is_4ps_' + index + '" name="family_members[' + index + '][is_4ps]" value="YES">' +
              '<label class="custom-control-label" for="family_is_4ps_' + index + '">4P’s Member</label>' +
            '</div>' +
            '<div class="custom-control custom-checkbox">' +
              '<input type="checkbox" class="custom-control-input family-member-ip" id="family_is_ip_' + index + '" name="family_members[' + index + '][is_ip]" value="YES">' +
              '<label class="custom-control-label" for="family_is_ip_' + index + '">IP’s Member</label>' +
            '</div>' +
            '<div class="custom-control custom-checkbox">' +
              '<input type="checkbox" class="custom-control-input family-member-na" id="family_is_na_' + index + '" name="family_members[' + index + '][is_na_member]" value="YES">' +
              '<label class="custom-control-label" for="family_is_na_' + index + '">N/A</label>' +
            '</div>' +
            '<div class="custom-control custom-checkbox">' +
              '<input type="checkbox" class="custom-control-input family-member-pwd" id="family_is_pwd_' + index + '" name="family_members[' + index + '][is_pwd]" value="YES">' +
              '<label class="custom-control-label" for="family_is_pwd_' + index + '">PWD</label>' +
            '</div>' +
            '<div class="custom-control custom-checkbox">' +
              '<input type="checkbox" class="custom-control-input family-member-solo-parent" id="family_is_solo_' + index + '" name="family_members[' + index + '][is_solo_parent]" value="YES">' +
              '<label class="custom-control-label" for="family_is_solo_' + index + '">Solo Parent</label>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="family-child-anthropometry nutrition-child-anthro" style="display:none;">' +
        '<div class="nutrition-child-anthro-banner mb-3">' +
          '<i class="fas fa-baby mr-1"></i>' +
          '<strong>Child 0–5 years</strong> — enter previous (optional) and present weight &amp; height. ' +
          'OPT results use <span class="nutrition-growth-sex-label">boy/girl</span> standards on the <strong>present</strong> date.' +
          '<div class="nutrition-growth-expected small mt-1"></div>' +
        '</div>' +
        '<div class="nutrition-anthro-block mb-3">' +
          '<div class="small text-muted font-weight-bold mb-2 text-uppercase">Previous measurement <span class="font-weight-normal">(optional)</span></div>' +
          '<div class="row">' +
            '<div class="col-md-4 form-group">' +
              '<label>Previous Date Measured</label>' +
              '<input type="text" class="form-control nutrition-date-mdy family-member-prev-date-measured" name="family_members[' + index + '][prev_date_measured]" value="" placeholder="MM/DD/YYYY" inputmode="numeric" autocomplete="off">' +
              '<small class="text-muted">MM/DD/YYYY</small>' +
            '</div>' +
            '<div class="col-md-4 form-group">' +
              '<label>Previous Weight (kg)</label>' +
              '<input type="number" min="0" step="0.01" class="form-control family-member-prev-weight" name="family_members[' + index + '][prev_weight_kg]" placeholder="kg">' +
            '</div>' +
            '<div class="col-md-4 form-group">' +
              '<label>Previous Height / Length (cm)</label>' +
              '<input type="number" min="0" step="0.1" class="form-control family-member-prev-height" name="family_members[' + index + '][prev_height_cm]" placeholder="cm">' +
            '</div>' +
          '</div>' +
          '<div class="row">' +
            '<div class="col-12 form-group nutrition-growth-result-col nutrition-growth-result-col--prev">' +
              '<label class="d-block">Nutrition Result (auto-computed from previous)</label>' +
              '<div class="nutrition-growth-results nutrition-growth-results--prev small">' +
                '<div><span class="text-muted">Weight for Age:</span> <span class="nutrition-growth-prev-wfa badge badge-secondary">—</span></div>' +
                '<div><span class="text-muted">Height for Age:</span> <span class="nutrition-growth-prev-hfa badge badge-secondary">—</span></div>' +
                '<div><span class="text-muted">Weight for Height/Length:</span> <span class="nutrition-growth-prev-wfh badge badge-secondary">—</span></div>' +
                '<div class="nutrition-prev-expected small mt-1"></div>' +
                '<input type="hidden" name="family_members[' + index + '][prev_weight_for_age]" value="">' +
                '<input type="hidden" name="family_members[' + index + '][prev_height_for_age]" value="">' +
                '<input type="hidden" name="family_members[' + index + '][prev_weight_for_height]" value="">' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="nutrition-anthro-block mb-2">' +
          '<div class="small text-muted font-weight-bold mb-2 text-uppercase">Present measurement</div>' +
          '<div class="row">' +
            '<div class="col-md-4 form-group">' +
              '<label>Present Date Measured <span class="text-danger">*</span></label>' +
              '<input type="text" class="form-control nutrition-date-mdy family-member-growth-input family-member-date-measured" name="family_members[' + index + '][date_measured]" value="" placeholder="MM/DD/YYYY" inputmode="numeric" autocomplete="off">' +
              '<small class="text-muted">MM/DD/YYYY</small>' +
            '</div>' +
            '<div class="col-md-4 form-group">' +
              '<label>Present Weight (kg) <span class="text-danger">*</span></label>' +
              '<input type="number" min="0" step="0.01" class="form-control family-member-growth-input family-member-present-weight" name="family_members[' + index + '][weight_kg]" placeholder="kg">' +
            '</div>' +
            '<div class="col-md-4 form-group">' +
              '<label>Present Height / Length (cm) <span class="text-danger">*</span></label>' +
              '<input type="number" min="0" step="0.1" class="form-control family-member-growth-input family-member-present-height" name="family_members[' + index + '][height_cm]" placeholder="cm">' +
            '</div>' +
          '</div>' +
          '<div class="row">' +
            '<div class="col-12 form-group nutrition-growth-result-col nutrition-growth-result-col--present">' +
              '<label class="d-block">Nutrition Result (auto-computed from present)</label>' +
              '<div class="nutrition-growth-results small">' +
                '<div><span class="text-muted">Weight for Age:</span> <span class="nutrition-growth-wfa badge badge-secondary">—</span></div>' +
                '<div><span class="text-muted">Height for Age:</span> <span class="nutrition-growth-hfa badge badge-secondary">—</span></div>' +
                '<div><span class="text-muted">Weight for Height/Length:</span> <span class="nutrition-growth-wfh badge badge-secondary">—</span></div>' +
                '<div class="nutrition-weight-gain mt-2 text-muted"></div>' +
                '<input type="hidden" name="family_members[' + index + '][weight_for_age]" value="">' +
                '<input type="hidden" name="family_members[' + index + '][height_for_age]" value="">' +
                '<input type="hidden" name="family_members[' + index + '][weight_for_height]" value="">' +
              '</div>' +
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

function nutritionSetGrowthResult($card, field, value, which) {
  which = which || 'present';
  var fieldMap = { wfa: 'weight_for_age', hfa: 'height_for_age', wfh: 'weight_for_height' };
  var key = fieldMap[field];
  var display = value || '—';
  var badgeClass = value ? nutritionGrowthBadgeClass(value) : 'badge-secondary';
  if (which === 'prev') {
    $card.find('.nutrition-growth-prev-' + field)
      .text(display)
      .removeClass('badge-secondary badge-success badge-warning badge-danger badge-info badge-primary')
      .addClass(badgeClass);
    $card.find('input[name*="[prev_' + key + ']"]').val(value || '');
    return;
  }
  $card.find('.nutrition-growth-' + field)
    .text(display)
    .removeClass('badge-secondary badge-success badge-warning badge-danger badge-info badge-primary')
    .addClass(badgeClass);
  $card.find('input').filter(function () {
    return new RegExp('\\[' + key + '\\]$').test(this.name || '');
  }).val(value || '');
}

function nutritionClearGrowthResults($card) {
  nutritionSetGrowthResult($card, 'wfa', '');
  nutritionSetGrowthResult($card, 'hfa', '');
  nutritionSetGrowthResult($card, 'wfh', '');
  nutritionSetGrowthResult($card, 'wfa', '', 'prev');
  nutritionSetGrowthResult($card, 'hfa', '', 'prev');
  nutritionSetGrowthResult($card, 'wfh', '', 'prev');
  $card.find('.nutrition-growth-expected').text('');
  $card.find('.nutrition-prev-expected').text('');
  $card.find('.nutrition-weight-gain').text('').removeClass('text-success text-danger text-warning');
}

function nutritionRefreshPreviousGrowth($card) {
  var gender = $card.find('select[name*="[gender]"]').val();
  var birthDate = ($card.find('input[name*="[birth_date]"]').val() || '').trim();
  var prevWeight = parseFloat($card.find('.family-member-prev-weight').val() || '0');
  var prevHeight = parseFloat($card.find('.family-member-prev-height').val() || '0');
  var prevDate = ($card.find('.family-member-prev-date-measured').val() || '').trim();
  var $prevExpected = $card.find('.nutrition-prev-expected');

  if (!(prevWeight > 0) || !(prevHeight > 0) || !prevDate || !birthDate || !gender) {
    nutritionSetGrowthResult($card, 'wfa', '', 'prev');
    nutritionSetGrowthResult($card, 'hfa', '', 'prev');
    nutritionSetGrowthResult($card, 'wfh', '', 'prev');
    $prevExpected.text('');
    return;
  }

  var birthYmd = nutritionToYmd(birthDate);
  var prevYmd = nutritionToYmd(prevDate);
  if (!birthYmd || !prevYmd) {
    nutritionSetGrowthResult($card, 'wfa', '', 'prev');
    nutritionSetGrowthResult($card, 'hfa', '', 'prev');
    nutritionSetGrowthResult($card, 'wfh', '', 'prev');
    $prevExpected.text('');
    return;
  }

  $.ajax({
    url: 'nutritionFamilyMemberGrowth.php',
    method: 'GET',
    dataType: 'json',
    cache: false,
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    data: {
      ajax: 1,
      gender: gender,
      birth_date: birthYmd,
      weight_kg: prevWeight,
      height_cm: prevHeight,
      date_measured: prevYmd
    },
    dataFilter: function (data) {
      return nutritionStripBom(data);
    }
  }).done(function (res) {
    if (!res || !res.ok || !res.is_child_0_to_5) {
      nutritionSetGrowthResult($card, 'wfa', '', 'prev');
      nutritionSetGrowthResult($card, 'hfa', '', 'prev');
      nutritionSetGrowthResult($card, 'wfh', '', 'prev');
      $prevExpected.text('');
      return;
    }
    nutritionSetGrowthResult($card, 'wfa', res.weight_for_age || '', 'prev');
    nutritionSetGrowthResult($card, 'hfa', res.height_for_age || '', 'prev');
    nutritionSetGrowthResult($card, 'wfh', res.weight_for_height || '', 'prev');
    if (nutritionCanShowOptUi()
        && res.sex_label && res.expected_weight_kg != null && res.expected_weight_kg !== ''
        && res.expected_height_cm != null && res.expected_height_cm !== '') {
      $prevExpected.text(
        'Expected median at previous date (' + res.sex_label + '): ~' +
        res.expected_weight_kg + ' kg · ~' + res.expected_height_cm + ' cm'
      );
    } else {
      $prevExpected.text('');
    }
  }).fail(function () {
    nutritionSetGrowthResult($card, 'wfa', '', 'prev');
    nutritionSetGrowthResult($card, 'hfa', '', 'prev');
    nutritionSetGrowthResult($card, 'wfh', '', 'prev');
    $prevExpected.text('');
  });
}

function nutritionUpdateWeightGain($card) {
  var $gain = $card.find('.nutrition-weight-gain');
  var prevW = parseFloat($card.find('.family-member-prev-weight').val() || '');
  var presentW = parseFloat($card.find('.family-member-present-weight').val() || '');
  var prevDate = ($card.find('.family-member-prev-date-measured').val() || '').trim();
  var presentDate = ($card.find('.family-member-date-measured').val() || '').trim();
  $gain.text('').removeClass('text-success text-danger text-warning');

  if (!(prevW > 0) || !(presentW > 0)) {
    return;
  }

  var delta = Math.round((presentW - prevW) * 100) / 100;
  var sign = delta > 0 ? '+' : '';
  var daysText = '';
  var prevYmd = nutritionToYmd(prevDate);
  var presentYmd = nutritionToYmd(presentDate);
  if (prevYmd && presentYmd) {
    var p = prevYmd.split('-').map(Number);
    var n = presentYmd.split('-').map(Number);
    var prevDt = new Date(p[0], p[1] - 1, p[2]);
    var presentDt = new Date(n[0], n[1] - 1, n[2]);
    if (!isNaN(prevDt.getTime()) && !isNaN(presentDt.getTime()) && presentDt >= prevDt) {
      var days = Math.round((presentDt - prevDt) / 86400000);
      daysText = ' in ' + days + ' day' + (days === 1 ? '' : 's');
    }
  }

  $gain.text('Weight change: ' + sign + delta.toFixed(2) + ' kg' + daysText);
  if (delta > 0) {
    $gain.addClass('text-success');
  } else if (delta < 0) {
    $gain.addClass('text-danger');
  } else {
    $gain.addClass('text-warning');
  }
}

function nutritionFormatAgeLabel(ageMonths, birthDate, referenceDate) {
  // Prefer exact Y/M/D from birthday when available.
  if (birthDate) {
    var birthYmd = nutritionToYmd(birthDate);
    var refYmd = nutritionToYmd(referenceDate || nutritionTodayMdy());
    if (birthYmd && refYmd) {
      var b = birthYmd.split('-').map(Number);
      var r = refYmd.split('-').map(Number);
      var birth = new Date(b[0], b[1] - 1, b[2]);
      var ref = new Date(r[0], r[1] - 1, r[2]);
      if (!isNaN(birth.getTime()) && !isNaN(ref.getTime()) && birth <= ref) {
        var years = ref.getFullYear() - birth.getFullYear();
        var months = ref.getMonth() - birth.getMonth();
        var days = ref.getDate() - birth.getDate();
        if (days < 0) {
          months -= 1;
          var prevMonth = new Date(ref.getFullYear(), ref.getMonth(), 0);
          days += prevMonth.getDate();
        }
        if (months < 0) {
          years -= 1;
          months += 12;
        }
        var totalMonths = (typeof ageMonths === 'number' && !isNaN(ageMonths))
          ? ageMonths
          : (years * 12 + months);
        return years + 'y ' + months + 'm ' + days + 'd (' + totalMonths + ' months)';
      }
    }
  }
  if (ageMonths === null || ageMonths === undefined || isNaN(ageMonths)) {
    return 'Enter birthday';
  }
  var y = Math.floor(ageMonths / 12);
  var m = ageMonths % 12;
  return y + 'y ' + m + 'm 0d (' + ageMonths + ' months)';
}

/** Strip UTF-8 BOM / leading junk so JSON.parse / dataType:json do not fail. */
function nutritionStripBom(text) {
  return String(text || '').replace(/^\uFEFF+/, '').replace(/^\s+/, '');
}

/** Convert Y-m-d or Date to MM/DD/YYYY. */
function nutritionFormatMdy(value) {
  if (!value) return '';
  var s = String(value).trim();
  var m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
  if (m) {
    return ('0' + m[2]).slice(-2) + '/' + ('0' + m[3]).slice(-2) + '/' + m[1];
  }
  m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (m) {
    return ('0' + m[1]).slice(-2) + '/' + ('0' + m[2]).slice(-2) + '/' + m[3];
  }
  var d = new Date(s);
  if (isNaN(d.getTime())) return '';
  return ('0' + (d.getMonth() + 1)).slice(-2) + '/' + ('0' + d.getDate()).slice(-2) + '/' + d.getFullYear();
}

/** Convert MM/DD/YYYY or Y-m-d to Y-m-d for APIs/DB. */
function nutritionToYmd(value) {
  if (!value) return '';
  var s = String(value).trim();
  var m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
  if (m) {
    return m[1] + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[3]).slice(-2);
  }
  m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (!m) return '';
  var mo = parseInt(m[1], 10);
  var day = parseInt(m[2], 10);
  var y = parseInt(m[3], 10);
  if (mo < 1 || mo > 12 || day < 1 || day > 31) return '';
  return y + '-' + ('0' + mo).slice(-2) + '-' + ('0' + day).slice(-2);
}

function nutritionTodayMdy() {
  var d = new Date();
  return ('0' + (d.getMonth() + 1)).slice(-2) + '/' + ('0' + d.getDate()).slice(-2) + '/' + d.getFullYear();
}

function nutritionInitDatePickers(context) {
  var $scope = context ? $(context) : $(document);
  if (!$.fn.datepicker) {
    return;
  }
  $scope.find('.nutrition-date-mdy').each(function () {
    var $input = $(this);
    if ($input.hasClass('hasDatepicker')) {
      return;
    }
    $input.attr('placeholder', 'MM/DD/YYYY');
    $input.datepicker({
      dateFormat: 'mm/dd/yy',
      changeMonth: true,
      changeYear: true,
      yearRange: '1920:+0',
      maxDate: 0,
      showAnim: '',
      showButtonPanel: false,
      constrainInput: true,
      // Keep calendar outside cards/scroll areas so it is not clipped on dark layout.
      beforeShow: function (input, inst) {
        setTimeout(function () {
          var $dp = inst.dpDiv;
          $dp.appendTo('body');
          $dp.addClass('nutrition-ui-datepicker');
          $dp.css({
            zIndex: 2000,
            position: 'absolute'
          });
        }, 0);
      },
      onSelect: function () {
        $(this).trigger('change');
      }
    });
  });
}

/** Client-side age in completed months (accepts MM/DD/YYYY or Y-m-d). */
function nutritionAgeInMonthsLocal(birthDate, referenceDate) {
  var birthYmd = nutritionToYmd(birthDate);
  var refYmd = nutritionToYmd(referenceDate || nutritionTodayMdy());
  if (!birthYmd || !refYmd) return null;
  var b = birthYmd.split('-').map(Number);
  var r = refYmd.split('-').map(Number);
  var birth = new Date(b[0], b[1] - 1, b[2]);
  var ref = new Date(r[0], r[1] - 1, r[2]);
  if (isNaN(birth.getTime()) || isNaN(ref.getTime()) || birth > ref) return null;
  var months = (ref.getFullYear() - birth.getFullYear()) * 12 + (ref.getMonth() - birth.getMonth());
  if (ref.getDate() < birth.getDate()) months -= 1;
  return Math.max(0, months);
}

function nutritionSexLabelFromGender(gender) {
  if (gender === 'Female') return 'girl';
  if (gender === 'Male') return 'boy';
  return '';
}

function nutritionCanShowOptUi() {
  return window.nutritionShowOptResults !== false && window.nutritionShowExpectedMedian !== false;
}

function nutritionApplyExpectedMedian($card, sexLabel, expectedWeight, expectedHeight, gender) {
  if (!nutritionCanShowOptUi()) {
    $card.find('.nutrition-growth-expected').text('');
    $card.find('.nutrition-prev-expected').text('');
    return;
  }
  var sex = sexLabel || nutritionSexLabelFromGender(gender);
  // Template already ends with " standards"; only put boy / girl / boy/girl here.
  $card.find('.nutrition-growth-sex-label').text(sex || 'boy/girl');
  if (sex && expectedWeight != null && expectedWeight !== '' && expectedHeight != null && expectedHeight !== '') {
    $card.find('.nutrition-growth-expected').text(
      'Expected median for this age (' + sex + '): ~' +
      expectedWeight + ' kg · ~' + expectedHeight + ' cm'
    );
    return;
  }
  if (!gender) {
    $card.find('.nutrition-growth-expected').text(
      'Select Male (Boy) or Female (Girl) to apply the correct growth standard.'
    );
    return;
  }
  if (sex) {
    $card.find('.nutrition-growth-expected').text(
      'Using ' + sex + ' growth standards. Enter birthday to load the expected median weight and height.'
    );
    return;
  }
  $card.find('.nutrition-growth-expected').text('');
}

function nutritionApplyLocalAgeFallback($card, birthDate, dateMeasured, $ageLabel, $anthro, gender) {
  var ageMonths = nutritionAgeInMonthsLocal(birthDate, dateMeasured);
  if (ageMonths === null) {
    $ageLabel.text('Could not compute age').addClass('text-muted');
    $card.find('.family-member-age-months').val('');
    $anthro.hide();
    nutritionClearGrowthResults($card);
    return;
  }
  $ageLabel.text(nutritionFormatAgeLabel(ageMonths, birthDate, dateMeasured)).removeClass('text-muted');
  $card.find('.family-member-age-months').val(ageMonths);
  if (ageMonths > 60) {
    $ageLabel.html(
      $('<span>').text(nutritionFormatAgeLabel(ageMonths, birthDate, dateMeasured)).prop('outerHTML') +
      ' <span class="badge badge-secondary ml-1">Over 5 yrs — no OPT weighing</span>'
    );
    $anthro.hide();
    nutritionClearGrowthResults($card);
  } else {
    $anthro.show();
    nutritionApplyExpectedMedian($card, '', null, null, gender || '');
  }
}

function nutritionRefreshFamilyMemberGrowth($card) {
  var gender = $card.find('select[name*="[gender]"]').val();
  var birthDate = $card.find('input[name*="[birth_date]"]').val();
  var weight = parseFloat($card.find('.family-member-present-weight').val() || '0');
  var height = parseFloat($card.find('.family-member-present-height').val() || '0');
  var surveyDate = $('#survey_date').val();
  var $dateMeasured = $card.find('.family-member-date-measured');
  var dateMeasured = ($dateMeasured.val() || '').trim();
  var $anthro = $card.find('.family-child-anthropometry');
  var $ageLabel = $card.find('.nutrition-member-age-label');

  nutritionUpdateWeightGain($card);
  nutritionRefreshPreviousGrowth($card);

  if (!birthDate) {
    $ageLabel.text('Enter birthday').addClass('text-muted');
    $card.find('.family-member-age-months').val('');
    $anthro.hide();
    nutritionClearGrowthResults($card);
    if (gender === 'Male' || gender === 'Female') {
      nutritionApplyExpectedMedian($card, '', null, null, gender);
    }
    return;
  }

  if (!dateMeasured) {
    dateMeasured = surveyDate || nutritionTodayMdy();
    $dateMeasured.val(dateMeasured);
  }

  var birthYmd = nutritionToYmd(birthDate);
  var measuredYmd = nutritionToYmd(dateMeasured);
  var surveyYmd = nutritionToYmd(surveyDate);

  // Show the 0–5 panel + boy/girl expected line immediately (BNS / all roles),
  // then fill exact medians and OPT badges when the growth API responds.
  nutritionApplyLocalAgeFallback($card, birthDate, dateMeasured, $ageLabel, $anthro, gender || '');

  $.ajax({
    url: 'nutritionFamilyMemberGrowth.php',
    method: 'GET',
    dataType: 'json',
    cache: false,
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    data: {
      ajax: 1,
      gender: gender || '',
      birth_date: birthYmd || birthDate,
      weight_kg: weight > 0 ? weight : 0,
      height_cm: height > 0 ? height : 0,
      survey_date: surveyYmd || surveyDate,
      date_measured: measuredYmd || dateMeasured
    },
    dataFilter: function (data) {
      return nutritionStripBom(data);
    }
  }).done(function (res) {
    if (!res || !res.ok) {
      nutritionApplyLocalAgeFallback($card, birthDate, dateMeasured, $ageLabel, $anthro, gender);
      return;
    }

    $ageLabel.text(res.age_label || nutritionFormatAgeLabel(res.age_months, birthDate, dateMeasured)).removeClass('text-muted');
    $card.find('.family-member-age-months').val(res.age_months != null ? res.age_months : '');

    if (!res.is_child_0_to_5) {
      $anthro.hide();
      $card.find('.family-member-present-weight, .family-member-present-height, .family-member-date-measured').val('');
      $card.find('.family-member-prev-weight, .family-member-prev-height, .family-member-prev-date-measured').val('');
      nutritionClearGrowthResults($card);
      if (res.age_months != null && res.age_months > 60) {
        $ageLabel.html(
          $('<span>').text(res.age_label || nutritionFormatAgeLabel(res.age_months, birthDate, dateMeasured)).prop('outerHTML') +
          ' <span class="badge badge-secondary ml-1">Over 5 yrs — no OPT weighing</span>'
        );
      }
      return;
    }

    $anthro.show();
    if (!$dateMeasured.val()) {
      $dateMeasured.val(dateMeasured);
    }
    nutritionApplyExpectedMedian(
      $card,
      res.sex_label || '',
      res.expected_weight_kg,
      res.expected_height_cm,
      gender || ''
    );

    if (weight > 0 && height > 0 && gender) {
      nutritionSetGrowthResult($card, 'wfa', res.weight_for_age || '');
      nutritionSetGrowthResult($card, 'hfa', res.height_for_age || '');
      nutritionSetGrowthResult($card, 'wfh', res.weight_for_height || '');
    } else {
      nutritionSetGrowthResult($card, 'wfa', '');
      nutritionSetGrowthResult($card, 'hfa', '');
      nutritionSetGrowthResult($card, 'wfh', '');
    }
    nutritionUpdateWeightGain($card);
  }).fail(function () {
    // Endpoint may fail (auth/BOM/HTML); still show age and boy/girl standard for BNS.
    nutritionApplyLocalAgeFallback($card, birthDate, dateMeasured, $ageLabel, $anthro, gender);
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

function nutritionSetMemberResidentNumber($card, residentNumber) {
  var value = $.trim(residentNumber || '');
  $card.find('.family-member-residence-id').val(value);
  $card.find('.family-member-residence-display').val(value);
}

function nutritionSyncHeadResidentNumberDisplay() {
  var rid = $.trim($('#house_hold_id').val() || '');
  $('#house_hold_id_display').val(rid);
}

function nutritionIsEditSurveyMode() {
  return $.trim($('#existing_survey_id').val() || '') !== '';
}

$('#addFamilyMemberBtn, .nutrition-add-first-member').on('click', function () {
  $('#familyMembersContainer').append(nutritionBuildFamilyMemberCard(familyMemberIndex));
  familyMemberIndex++;
  nutritionUpdateFamilyMembersEmptyState();
  var $lastCard = $('#familyMembersContainer .nutrition-family-member-card').last();
  if ($lastCard.length) {
    nutritionInitDatePickers($lastCard);
    nutritionSyncRelationshipOther($lastCard);
    nutritionRefreshHouseholdId();
    $('html, body').animate({ scrollTop: $lastCard.offset().top - 120 }, 250);
  }
});

$(document).on('click', '.remove-family-member-btn', function () {
  $(this).closest('.nutrition-family-member-card').remove();
  nutritionRefreshFamilyMemberLabels();
  nutritionUpdateFamilyMembersEmptyState();
  if (!nutritionIsEditSurveyMode()) {
    nutritionRefreshHouseholdId();
  }
});

$(document).on('change', '.family-member-relationship', function () {
  nutritionSyncRelationshipOther($(this).closest('.nutrition-family-member-card'));
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
  var $card = $(this).closest('.nutrition-family-member-card');
  nutritionSyncFamilyMemberFemaleStatus($card);
  nutritionRefreshFamilyMemberGrowth($card);
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
  // Legacy "Collected" maps to Collected/Segregated when editing older surveys
  if (garbage === 'Collected') {
    $('input[name="garbage_disposal"][value="Collected/Segregated"]').prop('checked', true);
  }

  var fp = $('input[name="practices_family_planning"]:checked').val() || 'NO';
  if (fp === 'YES') {
    $('#familyPlanningMethodsWrap').show();
  } else {
    $('#familyPlanningMethodsWrap').hide();
    $('#familyPlanningMethodsWrap input[type="checkbox"]').prop('checked', false);
    $('#family_planning_methods_other').hide().prop('disabled', true).val('');
  }
  nutritionSyncFpOtherSpecify();
}

function nutritionSyncFpOtherSpecify() {
  var fpYes = ($('input[name="practices_family_planning"]:checked').val() || 'NO') === 'YES';
  var othersChecked = $('input[name="family_planning_methods[]"][value="Others"]').is(':checked');
  var $other = $('#family_planning_methods_other');
  if (fpYes && othersChecked) {
    $other.show().prop('disabled', false);
  } else {
    $other.hide().prop('disabled', true).val('');
  }
}

$(document).on('change', 'input[name="house_ownership"], input[name="garbage_disposal"], input[name="practices_family_planning"], input[name="complementary_meals"], input[name="complementary_snacks"], input[name="child_physical_activity"]', nutritionSyncPrfToggles);
$(document).on('change', 'input[name="family_planning_methods[]"]', nutritionSyncFpOtherSpecify);

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

$(document).on('change', '.family-member-na', function () {
  if (this.checked) {
    $(this).closest('.nutrition-family-member-card')
      .find('.family-member-4ps, .family-member-ip')
      .prop('checked', false);
  }
});
$(document).on('change', '.family-member-4ps, .family-member-ip', function () {
  if (this.checked) {
    $(this).closest('.nutrition-family-member-card')
      .find('.family-member-na')
      .prop('checked', false);
  }
});

nutritionSyncPrfToggles();

$(document).on('input change', '.family-member-growth-input, .family-member-growth-trigger, .family-member-prev-weight, .family-member-prev-height, .family-member-prev-date-measured', function () {
  nutritionRefreshFamilyMemberGrowth($(this).closest('.nutrition-family-member-card'));
});

$('#survey_date').on('change', function () {
  $('#familyMembersContainer .nutrition-family-member-card').each(function () {
    nutritionRefreshFamilyMemberGrowth($(this));
  });
});

function nutritionRefreshHouseholdId() {
  var purok = ($('#purok_number').val() || '').trim();
  if (!purok) {
    if (!nutritionIsEditSurveyMode()) {
      $('#house_hold_id').val('');
      nutritionSyncHeadResidentNumberDisplay();
    }
    return;
  }
  var isEdit = nutritionIsEditSurveyMode();
  var $memberCards = $('#familyMembersContainer .nutrition-family-member-card');
  var memberCount = $memberCards.length;
  var previewCount = isEdit ? Math.max(1, memberCount) : (1 + memberCount);
  $.getJSON('nutritionNextHouseholdId.php', { purok: purok, count: previewCount }, function (res) {
    if (($('#purok_number').val() || '').trim() !== purok) {
      return;
    }
    if (!res || !res.ok) {
      return;
    }
    var numbers = Array.isArray(res.resident_numbers) ? res.resident_numbers : [];
    if (!isEdit) {
      var headNumber = res.resident_number || res.household_id || numbers[0] || '';
      if (headNumber) {
        $('#house_hold_id').val(headNumber);
        nutritionSyncHeadResidentNumberDisplay();
      }
      $memberCards.each(function (index) {
        nutritionSetMemberResidentNumber($(this), numbers[index + 1] || '');
      });
      return;
    }
    // Edit mode: keep locked head/member numbers; only preview blanks for new members.
    var assignIndex = 0;
    $memberCards.each(function () {
      var $card = $(this);
      var existing = $.trim($card.find('.family-member-residence-id').val() || '');
      if (existing !== '') {
        nutritionSetMemberResidentNumber($card, existing);
        return;
      }
      nutritionSetMemberResidentNumber($card, numbers[assignIndex] || '');
      assignIndex++;
    });
  });
}

$('#purok_number').on('input change', function () {
  nutritionRefreshHouseholdId();
  var purok = ($(this).val() || '').trim();
  var excelHref = 'nutritionHouseholdSurveyFormExcel.php?layout=form';
  var printHref = 'nutritionHouseholdSurveyForm.php';
  var pdfHref = 'nutritionHouseholdSurveyForm.php?pdf=1';
  if (purok !== '') {
    excelHref += '&purok=' + encodeURIComponent(purok);
    printHref += '?purok=' + encodeURIComponent(purok);
    pdfHref += '&purok=' + encodeURIComponent(purok);
  }
  $('#downloadHouseholdSurveyExcel').attr('href', excelHref);
  $('#downloadHouseholdSurveyForm').attr('href', printHref);
  $('#downloadHouseholdSurveyPdf').attr('href', pdfHref);
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
  $('#survey_date').val(nutritionTodayMdy());
  nutritionInitDatePickers($form);
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
  $('#birth_date').val(nutritionFormatMdy(resident.birth_date || ''));
  $('#gender').val(resident.gender || '');
  $('#occupation').val(resident.occupation || '');
  $('#is_4ps').prop('checked', resident.is_4ps === 'YES');
  $('#is_pwd').prop('checked', resident.is_pwd === 'YES');
  $('#is_ip').prop('checked', resident.is_ip === 'YES');
  $('#is_solo_parent').prop('checked', resident.is_solo_parent === 'YES');
  nutritionSyncHeadFemaleStatus();

  var purokValue = resident.purok_label || resident.purok_input || resident.purok_number || '';
  if (purokValue) {
    var $purok = $('#purok_number');
    var matched = $purok.find('option').filter(function () {
      return String($(this).val()).toLowerCase() === String(purokValue).toLowerCase();
    }).first();
    // Keep Purok / Sitio locked to master list — do not append free-text options.
    if (matched.length) {
      $purok.val(matched.val()).trigger('change');
    }
  }

  $('#familyMembersContainer').empty();
  familyMemberIndex = 0;
  var members = Array.isArray(resident.family_members) ? resident.family_members : [];
  members.forEach(function (member) {
    $('#familyMembersContainer').append(nutritionBuildFamilyMemberCard(familyMemberIndex));
    var $card = $('#familyMembersContainer .nutrition-family-member-card').last();
    nutritionInitDatePickers($card);
    nutritionSetMemberResidentNumber($card, '');
    $card.find('input[name*="[member_name]"]').val(member.member_name || '');
    nutritionApplyRelationshipToCard($card, member.relationship || '', member.relationship_other || '');
    $card.find('select[name*="[gender]"]').val(member.gender || '');
    $card.find('input[name*="[birth_date]"]').val(nutritionFormatMdy(member.birth_date || ''));
    familyMemberIndex++;
    nutritionSyncFamilyMemberFemaleStatus($card);
    nutritionRefreshFamilyMemberGrowth($card);
  });
  nutritionUpdateFamilyMembersEmptyState();
  nutritionRefreshFamilyMemberLabels();
  nutritionRefreshHouseholdId();
}

$('#barangay_residence_id').select2({
  theme: 'bootstrap4',
  placeholder: 'Optional: search Hub resident to prefill…',
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
  if (!residenceId || window.nutritionSkipResidentPrefill) {
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
      title: 'Hub resident details loaded',
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
nutritionInitDatePickers(document);

function nutritionApplySurveyEditPayload(data) {
  if (!data || typeof data !== 'object') {
    return;
  }

  if (data.survey_id && !$('#existing_survey_id').length) {
    $('#householdSurveyForm').prepend(
      $('<input>', { type: 'hidden', name: 'existing_survey_id', id: 'existing_survey_id', value: data.survey_id })
    );
  }

  (function () {
    var editPurok = data.purok_label || data.purok_input || data.purok_number || '';
    var $purok = $('#purok_number');
    if (!editPurok) {
      $purok.val('').trigger('change');
      return;
    }
    var match = $purok.find('option').filter(function () {
      return String($(this).val()).toLowerCase() === String(editPurok).toLowerCase();
    }).first();
    $purok.val(match.length ? match.val() : '').trigger('change');
  })();
  $('#survey_date').val(data.survey_date || '');
  $('#bns_name').val(data.bns_name || window.nutritionDefaultBnsName || '');
  $('#house_hold_id').val(data.house_hold_id || '');
  nutritionSyncHeadResidentNumberDisplay();
  $('#head_last_name').val(data.head_last_name || '');
  $('#head_first_name').val(data.head_first_name || '');
  $('#head_middle_name').val(data.head_middle_name || '');
  $('#head_suffix').val(data.head_suffix || '');
  $('#birth_date').val(data.birth_date || '');
  $('#gender').val(data.gender || '');
  $('#occupation').val(data.occupation || '');
  $('#is_4ps').prop('checked', !!data.is_4ps);
  $('#is_pwd').prop('checked', !!data.is_pwd);
  $('#is_ip').prop('checked', !!data.is_ip);
  $('#is_solo_parent').prop('checked', !!data.is_solo_parent);
  $('#is_na_member').prop('checked', !!data.is_na_member);

  if (data.residence_id) {
    var rid = String(data.residence_id);
    // Only treat as Hub link when it is not an auto PSGC-Purok-##### Resident Number.
    if (!/^\d{10}-P[A-Z0-9]+-\d{5}$/i.test(rid)) {
      var opt = new Option('Hub resident ' + rid, rid, true, true);
      window.nutritionSkipResidentPrefill = true;
      $('#barangay_residence_id').append(opt).val(rid).trigger('change');
      window.nutritionSkipResidentPrefill = false;
    }
  }

  nutritionSyncHeadFemaleStatus();
  $('#head_is_pregnant').prop('checked', !!data.head_is_pregnant);
  $('#head_is_lactating').prop('checked', !!data.head_is_lactating);
  nutritionSyncHeadFemaleStatus();
  $('#head_pregnancy_months').val(data.head_pregnancy_months || '');
  if (data.head_pregnant_nutrition_status) {
    $('input[name="head_pregnant_nutrition_status"][value="' + String(data.head_pregnant_nutrition_status).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  $('#head_planned_exclusive').prop('checked', !!data.head_planned_exclusive_breastfeeding);
  $('#head_planned_mixed').prop('checked', !!data.head_planned_mixed_feeding);
  $('#head_planned_bottle').prop('checked', !!data.head_planned_bottle_feeding);
  $('#head_planned_other').prop('checked', !!data.head_planned_other_feeding);
  $('#head_planned_other_specify').val(data.head_planned_other_specify || '').prop('disabled', !data.head_planned_other_feeding);
  $('#head_lactating_exclusive').prop('checked', !!data.head_lactating_exclusive_breastfeeding);
  $('#head_lactating_mixed').prop('checked', !!data.head_lactating_mixed_feeding);
  $('#head_lactating_bottle').prop('checked', !!data.head_lactating_bottle_feeding);
  $('#head_lactating_other').prop('checked', !!data.head_lactating_other_feeding);
  $('#head_lactating_other_specify').val(data.head_lactating_other_specify || '').prop('disabled', !data.head_lactating_other_feeding);

  if (data.house_ownership) {
    $('input[name="house_ownership"][value="' + String(data.house_ownership).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  $('#house_ownership_other').val(data.house_ownership_other || '');
  if (data.toilet_type) {
    var toiletMap = {
      'Water Sealed': 'Pour/Flush type with septic tank',
      'Covered Pit': 'Ventilated Pit (VIP) Latrine',
      'Open Pit': 'Open Pit Latrine',
      'No Toilet': 'Without toilet'
    };
    var toiletVal = toiletMap[data.toilet_type] || data.toilet_type;
    $('input[name="toilet_type"][value="' + String(toiletVal).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  if (data.garbage_disposal) {
    var garbageVal = data.garbage_disposal === 'Collected' ? 'Collected/Segregated' : data.garbage_disposal;
    $('input[name="garbage_disposal"][value="' + String(garbageVal).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  if (data.garbage_uncollected_type) {
    $('input[name="garbage_uncollected_type"][value="' + String(data.garbage_uncollected_type).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  if (data.water_source) {
    var waterMap = {
      'Pipe Water System': 'Pipe Water System (level III)',
      'Communal Water Source': 'Communal source piped water system',
      'Mineral': 'Mineral water/water dispensing stores',
      'Mineral water': 'Mineral water/water dispensing stores'
    };
    var waterVal = waterMap[data.water_source] || data.water_source;
    $('input[name="water_source"][value="' + String(waterVal).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  if (data.dwelling_type) {
    $('input[name="dwelling_type"][value="' + String(data.dwelling_type).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }

  var foodActs = String(data.food_production || '').split(/[,|]/);
  $('input[name="food_production_activities[]"]').prop('checked', false);
  foodActs.forEach(function (act) {
    act = $.trim(act);
    if (!act) return;
    $('input[name="food_production_activities[]"][value="' + act.replace(/"/g, '\\"') + '"]').prop('checked', true);
  });

  $('#uses_iodized_salt').prop('checked', !!data.uses_iodized_salt);
  $('#uses_sangkap_pinoy').prop('checked', !!data.uses_sangkap_pinoy);
  $('#has_carenderia').prop('checked', !!data.has_carenderia);
  $('#has_sari_sari_store').prop('checked', !!data.has_sari_sari_store);

  if (data.practices_family_planning) {
    $('#fp_yes').prop('checked', true);
  } else {
    $('#fp_no').prop('checked', true);
  }
  var fpMethods = String(data.family_planning_methods || '').split(/[,|]/);
  $('input[name="family_planning_methods[]"]').prop('checked', false);
  var fpOtherDetail = '';
  fpMethods.forEach(function (m) {
    m = $.trim(m);
    if (!m) return;
    if (m === 'Others' || m.indexOf('Others:') === 0) {
      $('input[name="family_planning_methods[]"][value="Others"]').prop('checked', true);
      if (m.indexOf('Others:') === 0) {
        fpOtherDetail = $.trim(m.replace(/^Others:\s*/, ''));
      }
      return;
    }
    $('input[name="family_planning_methods[]"][value="' + m.replace(/"/g, '\\"') + '"]').prop('checked', true);
  });
  $('#family_planning_methods_other').val(fpOtherDetail);

  if (data.complementary_meals) {
    $('input[name="complementary_meals"][value="' + String(data.complementary_meals).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  $('#complementary_meals_other').val(data.complementary_meals_other || '');
  if (data.complementary_snacks) {
    $('input[name="complementary_snacks"][value="' + String(data.complementary_snacks).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  $('#complementary_snacks_other').val(data.complementary_snacks_other || '');
  if (data.child_physical_activity) {
    $('input[name="child_physical_activity"][value="' + String(data.child_physical_activity).replace(/"/g, '\\"') + '"]').prop('checked', true);
  }
  $('#child_physical_activity_other').val(data.child_physical_activity_other || '');
  $('#remarks').val(data.remarks || '');

  nutritionSyncPrfToggles();

  $('#familyMembersContainer').empty();
  familyMemberIndex = 0;
  var members = Array.isArray(data.family_members) ? data.family_members : [];
  members.forEach(function (member) {
    $('#familyMembersContainer').append(nutritionBuildFamilyMemberCard(familyMemberIndex));
    var $card = $('#familyMembersContainer .nutrition-family-member-card').last();
    nutritionInitDatePickers($card);
    nutritionSetMemberResidentNumber($card, member.residence_id || '');
    $card.find('input[name*="[member_name]"]').val(member.member_name || '');
    nutritionApplyRelationshipToCard($card, member.relationship || '', member.relationship_other || '');
    $card.find('select[name*="[gender]"]').val(member.gender || '');
    $card.find('input[name*="[birth_date]"]').val(member.birth_date || '');
    $card.find('.family-member-present-weight').val(member.weight_kg || '');
    $card.find('.family-member-present-height').val(member.height_cm || '');
    $card.find('.family-member-date-measured').val(member.date_measured || '');
    $card.find('.family-member-prev-weight').val(member.prev_weight_kg || '');
    $card.find('.family-member-prev-height').val(member.prev_height_cm || '');
    $card.find('.family-member-prev-date-measured').val(member.prev_date_measured || '');
    $card.find('input[name*="[weight_for_age]"]').val(member.weight_for_age || '');
    $card.find('input[name*="[height_for_age]"]').val(member.height_for_age || '');
    $card.find('input[name*="[weight_for_height]"]').val(member.weight_for_height || '');
    $card.find('.family-member-pregnant').prop('checked', !!member.is_pregnant);
    $card.find('.family-member-lactating').prop('checked', !!member.is_lactating);
    $card.find('.family-member-4ps').prop('checked', !!member.is_4ps);
    $card.find('.family-member-ip').prop('checked', !!member.is_ip);
    $card.find('.family-member-na').prop('checked', !!member.is_na_member);
    $card.find('.family-member-pwd').prop('checked', !!member.is_pwd);
    $card.find('.family-member-solo-parent').prop('checked', !!member.is_solo_parent);
    $card.find('input[name*="[pregnancy_months]"]').val(member.pregnancy_months || '');
    if (member.pregnant_nutrition_status) {
      $card.find('input[name*="[pregnant_nutrition_status]"][value="' + String(member.pregnant_nutrition_status).replace(/"/g, '\\"') + '"]').prop('checked', true);
    }
    $card.find('input[name*="[planned_exclusive_breastfeeding]"]').prop('checked', !!member.planned_exclusive_breastfeeding);
    $card.find('input[name*="[planned_mixed_feeding]"]').prop('checked', !!member.planned_mixed_feeding);
    $card.find('input[name*="[planned_bottle_feeding]"]').prop('checked', !!member.planned_bottle_feeding);
    $card.find('input[name*="[planned_other_feeding]"]').prop('checked', !!member.planned_other_feeding);
    $card.find('input[name*="[planned_other_specify]"]').val(member.planned_other_specify || '').prop('disabled', !member.planned_other_feeding);
    $card.find('input[name*="[lactating_exclusive_breastfeeding]"]').prop('checked', !!member.lactating_exclusive_breastfeeding);
    $card.find('input[name*="[lactating_mixed_feeding]"]').prop('checked', !!member.lactating_mixed_feeding);
    $card.find('input[name*="[lactating_bottle_feeding]"]').prop('checked', !!member.lactating_bottle_feeding);
    $card.find('input[name*="[lactating_other_feeding]"]').prop('checked', !!member.lactating_other_feeding);
    $card.find('input[name*="[lactating_other_specify]"]').val(member.lactating_other_specify || '').prop('disabled', !member.lactating_other_feeding);
    familyMemberIndex++;
    nutritionSyncFamilyMemberFemaleStatus($card);
    nutritionRefreshFamilyMemberGrowth($card);
  });
  nutritionUpdateFamilyMembersEmptyState();
  nutritionRefreshFamilyMemberLabels();
}

if (window.nutritionSurveyEditPayload) {
  nutritionApplySurveyEditPayload(window.nutritionSurveyEditPayload);
} else {
  nutritionSyncHeadResidentNumberDisplay();
  nutritionRefreshHouseholdId();
}

$('#uploadHouseholdSurveyExcelBtn').on('click', function () {
  $('#uploadHouseholdSurveyExcelInput').val('').trigger('click');
});

$('#uploadHouseholdSurveyExcelInput').on('change', function () {
  var file = this.files && this.files[0];
  if (!file) {
    return;
  }
  if (!/\.xlsx$/i.test(file.name)) {
    Swal.fire({ title: 'Excel file required', text: 'Upload the filled Excel Form (.xlsx).', type: 'warning' });
    return;
  }
  var $btn = $('#uploadHouseholdSurveyExcelBtn');
  var originalHtml = $btn.html();
  $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Reading…');

  var formData = new FormData();
  formData.append('survey_file', file);
  var csrf = $('meta[name="csrf-token"]').attr('content');
  if (csrf) {
    formData.append('csrf_token', csrf);
  }

  $.ajax({
    url: 'nutritionHouseholdSurveyImport.php',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    dataFilter: function (data) {
      return nutritionStripBom(data);
    }
  }).done(function (res) {
    if (!res || !res.ok || !res.payload) {
      Swal.fire({ title: 'Could not read file', text: (res && res.error) || 'The Excel file could not be read.', type: 'error' });
      return;
    }
    nutritionResetHouseholdSurveyForm();
    nutritionApplySurveyEditPayload(res.payload);
    $('#purok_number').trigger('change');
    if (res.payload.house_hold_id) {
      $('#house_hold_id').val(res.payload.house_hold_id);
    }

    var head = [res.payload.head_last_name, res.payload.head_first_name].filter(Boolean).join(', ');
    var html = '<div class="text-left">' +
      '<div><strong>Household head:</strong> ' + $('<div>').text(head || '—').html() + '</div>' +
      '<div><strong>Family members:</strong> ' + (res.members || 0) + '</div>' +
      '<div><strong>Resident Number:</strong> <code>' + $('<div>').text(res.payload.house_hold_id || res.payload.residence_id || 'auto on save').html() + '</code></div>';
    if (res.warnings && res.warnings.length) {
      html += '<div class="mt-2 text-warning"><strong>Check:</strong><ul class="mb-0 pl-3">' +
        res.warnings.map(function (w) { return '<li>' + $('<div>').text(w).html() + '</li>'; }).join('') +
        '</ul></div>';
    }
    html += '<div class="mt-2 small text-muted">Review the form below, then click Save.</div></div>';
    Swal.fire({ title: 'Excel form loaded', html: html, type: 'success' });
    var $form = $('#householdSurveyForm');
    if ($form.length) {
      $('html, body').animate({ scrollTop: $form.offset().top - 88 }, 280);
    }
  }).fail(function (xhr) {
    var msg = 'Could not upload the Excel file.';
    try {
      var parsed = JSON.parse(nutritionStripBom(xhr.responseText || '{}'));
      if (parsed.error) msg = parsed.error;
    } catch (e) {}
    Swal.fire({ title: 'Upload failed', text: msg, type: 'error' });
  }).always(function () {
    $btn.prop('disabled', false).html(originalHtml);
  });
});

(function () {
  var purok = ($('#purok_number').val() || '').trim();
  var excelHref = 'nutritionHouseholdSurveyFormExcel.php?layout=form';
  var printHref = 'nutritionHouseholdSurveyForm.php';
  var pdfHref = 'nutritionHouseholdSurveyForm.php?pdf=1';
  if (purok !== '') {
    excelHref += '&purok=' + encodeURIComponent(purok);
    printHref += '?purok=' + encodeURIComponent(purok);
    pdfHref += '&purok=' + encodeURIComponent(purok);
  }
  $('#downloadHouseholdSurveyExcel').attr('href', excelHref);
  $('#downloadHouseholdSurveyForm').attr('href', printHref);
  $('#downloadHouseholdSurveyPdf').attr('href', pdfHref);
})();

function nutritionRefreshCompletenessChecklist() {
  var $list = $('#nutritionCompletenessList');
  var $badge = $('#nutritionCompletenessBadge');
  if (!$list.length) return true;
  var items = [];
  var incomplete = [];
  var child05 = 0;
  var child05Ok = 0;
  var memberCount = $('#familyMembersContainer .nutrition-family-member-card').length;

  $('#familyMembersContainer .nutrition-family-member-card').each(function () {
    var $card = $(this);
    var name = $.trim($card.find('input[name*="[member_name]"]').val() || 'Child');
    var ageRaw = $card.find('.family-member-age-months').val();
    var ageMonths = ageRaw === '' || ageRaw == null ? null : parseInt(ageRaw, 10);
    if (ageMonths === null || isNaN(ageMonths)) {
      var birth = $.trim($card.find('input[name*="[birth_date]"]').val() || '');
      var measured = $.trim($card.find('.family-member-date-measured').val() || '') || nutritionTodayMdy();
      ageMonths = nutritionAgeInMonthsLocal(birth, measured);
    }
    if (ageMonths === null || isNaN(ageMonths) || ageMonths > 60) {
      return;
    }
    child05++;
    var weight = parseFloat($card.find('.family-member-present-weight').val() || '0');
    var height = parseFloat($card.find('.family-member-present-height').val() || '0');
    if (weight > 0 && height > 0) {
      child05Ok++;
      items.push({ ok: true, text: name + ' (0–5): weight & height OK' });
    } else {
      var missing = [];
      if (!(weight > 0)) missing.push('weight');
      if (!(height > 0)) missing.push('height');
      var detail = 'Missing ' + missing.join(' & ');
      incomplete.push(name + ': ' + detail);
      items.push({ ok: false, text: name + ' (0–5): ' + detail });
    }
  });

  items.unshift({
    ok: child05 === 0 || child05Ok === child05,
    text: child05 === 0
      ? 'No children ages 0–5 on this form'
      : ('Children 0–5 measured: ' + child05Ok + '/' + child05)
  });
  items.unshift({
    ok: true,
    text: memberCount + ' family member(s) listed (besides household head)'
  });

  $list.empty();
  items.forEach(function (item) {
    $list.append(
      $('<li/>')
        .addClass(item.ok ? 'text-success' : 'text-warning')
        .text((item.ok ? '✓ ' : '⚠ ') + item.text)
    );
  });
  if (incomplete.length) {
    $badge.removeClass('badge-success badge-secondary').addClass('badge-warning').text('Incomplete');
  } else {
    $badge.removeClass('badge-warning badge-secondary').addClass('badge-success').text('Ready');
  }
  return incomplete.length === 0;
}

$(document).on(
  'input change blur',
  '#familyMembersContainer input, #familyMembersContainer select, #survey_date, #head_is_pregnant',
  function () {
    nutritionRefreshCompletenessChecklist();
  }
);
$(document).on('click', '#addFamilyMemberBtn, .nutrition-add-first-member', function () {
  setTimeout(nutritionRefreshCompletenessChecklist, 50);
});
setTimeout(nutritionRefreshCompletenessChecklist, 200);

function nutritionSubmitHouseholdSurvey($form, residentNumber) {
  var isEdit = nutritionIsEditSurveyMode();
  if (typeof barangaySyncCsrfForms === 'function') barangaySyncCsrfForms();
  $.post('saveNutritionHouseholdSurvey.php', $form.serialize(), function (res) {
    $('#allow_duplicate_override').val('0');
    var savedNumber = (res && (res.house_hold_id || res.residence_id)) || residentNumber;
    Swal.fire({
      title: isEdit ? 'Updated' : 'Saved',
      html: (res.message || (isEdit ? 'Household survey updated.' : 'Household survey recorded.')) +
        '<div class="mt-2">Resident Number: <code>' + $('<div>').text(savedNumber).html() + '</code></div>',
      type: 'success'
    }).then(function () {
      if (isEdit) {
        window.location.href = 'nutritionBarangaySurvey.php?highlight=' + encodeURIComponent(res.survey_id || '');
        return;
      }
      window.location.reload();
    });
  }, 'json').fail(function (xhr) {
    var msg = 'Could not save survey.';
    var code = '';
    try {
      var data = JSON.parse(xhr.responseText);
      if (data.error) msg = data.error;
      if (data.code) code = data.code;
      if (data.checklist) nutritionRefreshCompletenessChecklist();
    } catch (err) {
      if (xhr.status === 403) {
        msg = 'Session expired. Please refresh the page and try again.';
      } else if (xhr.responseText) {
        msg = 'Server error while saving. Please try again.';
      }
    }
    if (code === 'duplicate_override_allowed') {
      Swal.fire({
        title: 'Possible duplicate household',
        html: '<p class="mb-2">' + $('<div>').text(msg).html() + '</p>'
          + '<p class="mb-0">CNPC / Nutrition Admin can continue if this is a different household.</p>',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Save anyway',
        cancelButtonText: 'Cancel'
      }).then(function (result) {
        if (result.value || result.isConfirmed) {
          $('#allow_duplicate_override').val('1');
          nutritionSubmitHouseholdSurvey($form, residentNumber);
        } else {
          $('#allow_duplicate_override').val('0');
        }
      });
      return;
    }
    Swal.fire({ title: code === 'incomplete' ? 'Incomplete survey' : 'Error', text: msg, type: 'error' });
  });
}

$('#householdSurveyForm').on('submit', function (e) {
  e.preventDefault();
  var $form = $(this);
  $('#familyMembersContainer .nutrition-family-member-card').each(function () {
    $(this).find('.nutrition-other-feeding-toggle').each(function () {
      nutritionToggleOtherSpecify($(this));
    });
  });

  var missingOtherSpecify = false;
  $('#familyMembersContainer .nutrition-family-member-card').each(function () {
    var $card = $(this);
    nutritionSyncRelationshipOther($card);
    if (String($card.find('select[name*="[relationship]"]').val() || '') === 'Other Relative'
        && $.trim($card.find('input[name*="[relationship_other]"]').val() || '') === '') {
      missingOtherSpecify = true;
      $card.find('input[name*="[relationship_other]"]').focus();
      return false;
    }
  });
  if (missingOtherSpecify) {
    Swal.fire({
      title: 'Specify relationship',
      text: 'For “Other Relative”, please describe the relationship (e.g. Cousin, Uncle, Aunt, In-law).',
      type: 'warning'
    });
    return;
  }

  var residentNumber = $.trim($('#house_hold_id').val() || '');
  if (residentNumber === '') {
    Swal.fire({
      title: 'Resident Number required',
      text: 'Enter a Purok first so the Resident Number (PSGC-Purok-Number) can be auto-generated.',
      type: 'warning'
    });
    $('#purok_number').focus();
    return;
  }

  if (!nutritionRefreshCompletenessChecklist()) {
    Swal.fire({
      title: 'Completeness checklist',
      text: 'Add weight and height for every child ages 0–5 before saving.',
      type: 'warning'
    });
    return;
  }

  nutritionSubmitHouseholdSurvey($form, residentNumber);
});

(function nutritionLangToggleInit() {
  var lang = localStorage.getItem('nutrition_form_lang') || 'en';
  function applyLang(next) {
    lang = next;
    localStorage.setItem('nutrition_form_lang', lang);
    $('[data-i18n-en][data-i18n-bis]').each(function () {
      var $el = $(this);
      var en = $el.attr('data-i18n-en') || '';
      var bis = $el.attr('data-i18n-bis') || '';
      var required = $el.find('.text-danger').length ? ' <span class="text-danger">*</span>' : '';
      $el.html((lang === 'bis' ? bis : en) + required);
    });
    $('#nutritionLangToggle').html(
      lang === 'bis'
        ? '<i class="fas fa-language mr-1"></i> English labels'
        : '<i class="fas fa-language mr-1"></i> Bisaya labels'
    );
  }
  $(document).on('click', '#nutritionLangToggle', function () {
    applyLang(lang === 'bis' ? 'en' : 'bis');
  });
  applyLang(lang);
})();
</script>
HTML;
