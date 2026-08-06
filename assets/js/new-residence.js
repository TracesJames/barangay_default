/**
 * New Resident form enhancements (admin + secretary).
 * Expects #newResidenceForm, tab pills, and optional #nr_age_display.
 */
(function (window, $) {
  'use strict';

  function computeAgeYears(isoDate) {
    if (!isoDate) return null;
    var parts = String(isoDate).split('-');
    if (parts.length !== 3) return null;
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10) - 1;
    var d = parseInt(parts[2], 10);
    if (!y || isNaN(m) || isNaN(d)) return null;
    var born = new Date(y, m, d);
    if (isNaN(born.getTime())) return null;
    var today = new Date();
    var age = today.getFullYear() - born.getFullYear();
    var md = today.getMonth() - born.getMonth();
    if (md < 0 || (md === 0 && today.getDate() < born.getDate())) age -= 1;
    return age < 0 ? null : age;
  }

  function updateAgeBadge() {
    var $badge = $('#nr_age_display');
    if (!$badge.length) return;
    var age = computeAgeYears($('#add_birth_date').val());
    $badge.removeClass('is-minor is-senior');
    if (age === null) {
      $badge.text('Age will appear here').hide();
      return;
    }
    var label = age + ' year' + (age === 1 ? '' : 's') + ' old';
    if (age <= 17) {
      $badge.addClass('is-minor').text(label + ' · Minor — guardian required');
    } else if (age >= 60) {
      $badge.addClass('is-senior').text(label + ' · Senior citizen');
    } else {
      $badge.text(label);
    }
    $badge.show();
    updatePreviewMeta(age);
  }

  function updatePreviewMeta(age) {
    var $meta = $('#nr_preview_meta');
    if (!$meta.length) return;
    var gender = $('#add_gender').val() || '';
    var voters = $('#add_voters').val() || '';
    var bits = [];
    if (gender) bits.push(gender);
    if (age !== null && age !== undefined) bits.push(age + ' yrs');
    if (voters) bits.push(voters === 'YES' ? 'Voter' : 'Non-voter');
    $meta.text(bits.join(' · '));
  }

  function syncPreviewName() {
    $('#keyup_first_name').text($('#add_first_name').val() || '');
    $('#keyup_last_name').text($('#add_last_name').val() || '');
  }

  function setActiveStep(tabId) {
    var map = {
      'basic-info': 1,
      'other-info': 2,
      guardian: 3,
      'spouse-info': 4,
      'dependents-info': 5,
      'family-info': 4
    };
    var current = map[tabId] || 1;
    $('.nr-steps li').each(function () {
      var step = parseInt($(this).data('step'), 10);
      $(this).removeClass('active done');
      if (step === current) $(this).addClass('active');
      else if (step < current) $(this).addClass('done');
    });
  }

  function showTabByPaneId(paneId) {
    var $link = $('a[href="#' + paneId + '"]');
    if ($link.length) $link.tab('show');
  }

  function paneIdForElement(el) {
    var $pane = $(el).closest('.tab-pane');
    return $pane.length ? $pane.attr('id') : null;
  }

  function jumpToFirstInvalid() {
    var $invalid = $('#newResidenceForm .is-invalid:visible, #newResidenceForm .is-invalid').first();
    if (!$invalid.length) return;
    var paneId = paneIdForElement($invalid);
    if (paneId) showTabByPaneId(paneId);
    try {
      $invalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch (e) {
      /* ignore */
    }
  }

  function parseSaveResult(raw) {
    var text = (raw || '').toString().trim();
    if (text === 'success' || text.indexOf('success|') === 0) {
      var parts = text.split('|');
      return { ok: true, residenceId: parts[1] || '' };
    }
    try {
      var json = JSON.parse(text);
      if (json && (json.ok === true || json.status === 'success')) {
        return { ok: true, residenceId: json.residence_id || json.residenceId || '' };
      }
    } catch (e) {
      /* plain error string */
    }
    return { ok: false, error: text };
  }

  function wireTabNav() {
    var order = ['basic-info', 'other-info', 'guardian'];
    if ($('#spouse-info').length) order.push('spouse-info');
    if ($('#dependents-info').length) order.push('dependents-info');
    if ($('#family-info').length) order.push('family-info');

    $(document).on('click', '.nr-btn-next', function (e) {
      e.preventDefault();
      var $pane = $(this).closest('.tab-pane');
      var id = $pane.attr('id');
      var idx = order.indexOf(id);
      if (idx >= 0 && idx < order.length - 1) showTabByPaneId(order[idx + 1]);
    });

    $(document).on('click', '.nr-btn-prev', function (e) {
      e.preventDefault();
      var $pane = $(this).closest('.tab-pane');
      var id = $pane.attr('id');
      var idx = order.indexOf(id);
      if (idx > 0) showTabByPaneId(order[idx - 1]);
    });

    $('.nr-steps li').on('click', function () {
      var target = $(this).data('target');
      if (target) showTabByPaneId(target);
    });

    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
      var href = $(e.target).attr('href') || '';
      setActiveStep(href.replace('#', ''));
    });

    setActiveStep('basic-info');
  }

  function clampBirthDateInput() {
    var $dob = $('#add_birth_date');
    if (!$dob.length) return;
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var dd = String(today.getDate()).padStart(2, '0');
    $dob.attr('max', yyyy + '-' + mm + '-' + dd);
    if (!$dob.attr('min')) $dob.attr('min', '1900-01-01');
  }

  function setDuplicateAlert(isDuplicate, level, message) {
    var $alert = $('#nr_duplicate_alert');
    if (!$alert.length) return;
    level = level || 'block';
    message = message || 'This name is already registered in the Barangay.';
    $alert
      .toggleClass('is-visible', !!isDuplicate)
      .toggleClass('nr-duplicate-alert--warn', !!isDuplicate && level === 'warn')
      .toggleClass('nr-duplicate-alert--block', !!isDuplicate && level === 'block')
      .html(
        isDuplicate
          ? '<i class="fas fa-exclamation-triangle"></i> ' + $('<div>').text(message).html()
          : ''
      );
    $('#add_first_name, #add_middle_name, #add_last_name, #add_suffix').toggleClass(
      'is-invalid',
      !!isDuplicate && level === 'block'
    );
    $('#add_birth_date').toggleClass('is-invalid', !!isDuplicate && level === 'block');
  }

  function checkDuplicateName() {
    var first = $.trim($('#add_first_name').val() || '');
    var last = $.trim($('#add_last_name').val() || '');
    if (first.length < 2 || last.length < 2) {
      setDuplicateAlert(false);
      return;
    }

    if (typeof barangaySyncCsrfForms === 'function') {
      barangaySyncCsrfForms();
    }

    var payload = {
      add_first_name: first,
      add_middle_name: $.trim($('#add_middle_name').val() || ''),
      add_last_name: last,
      add_suffix: $.trim($('#add_suffix').val() || ''),
      add_birth_date: $.trim($('#add_birth_date').val() || '')
    };
    var $csrf = $('#newResidenceForm input[name="csrf_token"]').first();
    if ($csrf.length) {
      payload.csrf_token = $csrf.val();
    }

    $.ajax({
      url: 'checkResidenceName.php',
      type: 'POST',
      data: payload,
      dataType: 'json'
    }).done(function (res) {
      if (res && res.duplicate) {
        setDuplicateAlert(true, res.level || 'block', res.message || '');
      } else {
        setDuplicateAlert(false);
      }
    }).fail(function () {
      /* ignore transient check failures */
    });
  }

  function wireDuplicateNameCheck() {
    var timer = null;
    $('#add_first_name, #add_middle_name, #add_last_name, #add_suffix, #add_birth_date')
      .on('blur change', function () {
        checkDuplicateName();
      })
      .on('input', function () {
        setDuplicateAlert(false);
        clearTimeout(timer);
        timer = setTimeout(checkDuplicateName, 450);
      });
  }

  window.barangayNewResidence = {
    computeAgeYears: computeAgeYears,
    updateAgeBadge: updateAgeBadge,
    syncPreviewName: syncPreviewName,
    jumpToFirstInvalid: jumpToFirstInvalid,
    parseSaveResult: parseSaveResult,
    wireTabNav: wireTabNav,
    clampBirthDateInput: clampBirthDateInput,
    updatePreviewMeta: updatePreviewMeta,
    checkDuplicateName: checkDuplicateName,
    setDuplicateAlert: setDuplicateAlert
  };

  function showQueryFlash() {
    try {
      var params = new URLSearchParams(window.location.search || '');
      if (params.get('saved') === '1') {
        var rid = params.get('residence_id') || '';
        var idHtml = rid
          ? '<p class="mb-2">Residence No. <strong>' + $('<div>').text(rid).html() + '</strong></p>'
          : '';
        Swal.fire({
          title: '<strong class="text-success">Resident Saved</strong>',
          icon: 'success',
          html: idHtml + '<b>New resident was registered successfully.</b>',
          width: '440px',
          confirmButtonColor: '#6610f2',
          showDenyButton: true,
          confirmButtonText: 'Add another',
          denyButtonText: 'View all residents',
          allowOutsideClick: false
        }).then(function (result) {
          if (result.isDenied) {
            window.location.href = 'allResidence.php';
            return;
          }
          window.history.replaceState({}, document.title, window.location.pathname);
        });
        return;
      }
      var err = params.get('error');
      if (!err) return;
      var messages = {
        duplicate: 'This name is already registered in the Barangay.',
        minor_guardian: 'Residents 17 years old and below must have a Guardian name or Parent name.',
        validation: 'Please fill in all required fields.',
        image: 'Invalid image file. Use JPG, PNG, or GIF.',
        server: 'Unable to save the resident. Please try again.'
      };
      Swal.fire({
        title: '<strong class="text-danger">' + (err === 'duplicate' ? 'Already Registered' : 'ERROR') + '</strong>',
        icon: err === 'duplicate' ? 'warning' : 'error',
        html: '<b>' + (messages[err] || 'Unable to complete the request.') + '</b>',
        width: '440px',
        confirmButtonColor: '#6610f2'
      }).then(function () {
        window.history.replaceState({}, document.title, window.location.pathname);
        if (err === 'duplicate') {
          $('#basic-info-tab').tab('show');
          $('#add_first_name').focus();
        }
      });
    } catch (e) {
      /* ignore */
    }
  }

  $(function () {
    if (!$('#newResidenceForm').length) return;
    clampBirthDateInput();
    wireTabNav();
    wireDuplicateNameCheck();
    updateAgeBadge();
    syncPreviewName();
    showQueryFlash();

    $('#add_birth_date').on('change input', updateAgeBadge);
    $('#add_gender, #add_voters').on('change', function () {
      updatePreviewMeta(computeAgeYears($('#add_birth_date').val()));
    });
    $('#add_first_name, #add_last_name, #add_middle_name, #add_suffix').on('input keyup', syncPreviewName);
  });
})(window, jQuery);
