(function ($, global) {
  'use strict';

  function calcAge(birthDate) {
    if (!birthDate) {
      return null;
    }

    var today = new Date();
    var dob = new Date(birthDate + 'T00:00:00');
    if (Number.isNaN(dob.getTime())) {
      return null;
    }

    var age = today.getFullYear() - dob.getFullYear();
    var monthDiff = today.getMonth() - dob.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
      age -= 1;
    }

    return age;
  }

  function isMinor(age) {
    return age !== null && age <= 17;
  }

  function hasGuardianInfo() {
    return $.trim($('#add_guardian').val()) !== ''
      || $.trim($('#add_fathers_name').val()) !== ''
      || $.trim($('#add_mothers_name').val()) !== '';
  }

  function updateMinorGuardianState(switchTab) {
    var age = calcAge($('#add_birth_date').val());
    var minor = isMinor(age);

    $('#minor-guardian-notice').toggleClass('d-none', !minor);
    $('#guardian-tab').toggleClass('text-warning font-weight-bold', minor);
    $('.minor-guardian-field label').toggleClass('text-warning', minor);

    if (minor && switchTab) {
      $('#guardian-tab').tab('show');
    }

    if ($('#add_guardian').length && typeof $('#add_guardian').valid === 'function') {
      $('#add_guardian').valid();
    }
  }

  if ($.validator && !$.validator.methods.minorGuardianGroup) {
    $.validator.addMethod('minorGuardianGroup', function () {
      var age = calcAge($('#add_birth_date').val());
      if (!isMinor(age)) {
        return true;
      }
      return hasGuardianInfo();
    }, 'Guardian or parent name is required for residents 17 years old and below.');
  }

  global.barangayMinorGuardianRules = {
    add_guardian: { minorGuardianGroup: true }
  };

  $(function () {
    if (!$('#add_birth_date').length) {
      return;
    }

    $('#add_birth_date').on('change input', function () {
      updateMinorGuardianState(true);
    });

    $('#add_guardian, #add_fathers_name, #add_mothers_name').on('input', function () {
      updateMinorGuardianState(false);
    });

    updateMinorGuardianState(false);
  });
})(jQuery, window);
