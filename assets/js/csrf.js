(function (global) {
  'use strict';

  function getToken() {
    var el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
  }

  function syncFormTokens() {
    var token = getToken();
    if (!token) {
      return;
    }
    document.querySelectorAll('input[name="csrf_token"]').forEach(function (input) {
      input.value = token;
    });
  }

  function attachCsrf() {
    var token = getToken();
    if (!token || typeof global.jQuery === 'undefined') {
      return false;
    }

    global.jQuery.ajaxPrefilter(function (options) {
      options.xhrFields = options.xhrFields || {};
      options.xhrFields.withCredentials = true;
      if (!options.type || options.type.toUpperCase() !== 'POST') {
        return;
      }
      options.headers = options.headers || {};
      if (!options.headers['X-CSRF-Token']) {
        options.headers['X-CSRF-Token'] = token;
      }
      options.headers['X-Requested-With'] = 'XMLHttpRequest';
    });

    return true;
  }

  function bindFormSync() {
    document.addEventListener('submit', function (event) {
      syncFormTokens();
    }, true);
  }

  function init() {
    syncFormTokens();
    bindFormSync();
    if (attachCsrf()) {
      return;
    }
    var tries = 0;
    var timer = setInterval(function () {
      if (attachCsrf() || ++tries > 200) {
        clearInterval(timer);
      }
    }, 25);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  global.addEventListener('load', function () {
    syncFormTokens();
    attachCsrf();
  });

  global.barangayCsrfToken = getToken;
  global.barangaySyncCsrfForms = syncFormTokens;
})(window);
