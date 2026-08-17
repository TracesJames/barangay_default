/**
 * Barangay Portal — shared UI helpers & chrome enhancements
 */
(function (global) {
  'use strict';

  function portalConfirmColor() {
    var accent = document.body.getAttribute('data-accent');
    if (accent && window.BarangayAppearance && BarangayAppearance.ACCENTS[accent]) {
      return BarangayAppearance.ACCENTS[accent].color;
    }
    if (document.body.classList.contains('nutrition-portal')) {
      return '#16a34a';
    }
    var hub = document.getElementById('tailwind-scope');
    if (hub && hub.classList.contains('hub-page--nutrition')) {
      return '#16a34a';
    }
    return '#14b8a6';
  }

  var BRAND_COLOR = '#14b8a6';

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function getAjaxMessage(xhr) {
    if (!xhr || !xhr.responseText) {
      return 'Something went wrong. Please try again or refresh the page.';
    }
    var text = xhr.responseText.trim();
    if (text === 'Invalid CSRF token') {
      return 'Your session security token expired. Please refresh the page and try again.';
    }
    if (text.indexOf('<') === 0 || text.indexOf('<!DOCTYPE') === 0) {
      return 'The server returned an unexpected response. Check your connection and try again.';
    }
    if (text.length > 220) {
      return text.substring(0, 220) + '…';
    }
    return text;
  }

  global.barangayAjaxError = function (xhr) {
    if (typeof Swal === 'undefined') {
      alert(getAjaxMessage(xhr));
      return;
    }
    Swal.fire({
      title: 'Request failed',
      html: '<p class="mb-0" style="line-height:1.5">' + escapeHtml(getAjaxMessage(xhr)) + '</p>',
      type: 'error',
      width: '420px',
      confirmButtonColor: portalConfirmColor(),
      confirmButtonText: 'OK'
    });
  };

  global.barangayAlert = function (type, title, message) {
    if (typeof Swal === 'undefined') {
      alert((title || '') + (message ? ': ' + message : ''));
      return;
    }
    var opts = {
      title: title || '',
      type: type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'error',
      width: '400px',
      confirmButtonColor: portalConfirmColor()
    };
    if (message) {
      opts.html = '<b>' + escapeHtml(message) + '</b>';
    }
    Swal.fire(opts);
  };

  global.barangaySuccess = function (title, message) {
    barangayAlert('success', title || 'Success', message);
  };

  global.barangayWarning = function (title, message) {
    barangayAlert('warning', title || 'Warning', message);
  };

  global.barangayError = function (title, message) {
    barangayAlert('error', title || 'Error', message);
  };

  function enhancePortalChrome() {
    var sidebar = document.querySelector('.main-sidebar');
    if (!sidebar) {
      return;
    }

    if (!document.body.classList.contains('barangay-super-dashboard')) {
      document.body.classList.add('barangay-portal');
    }

    document.querySelectorAll('.main-header .navbar-nav h5.nav-link').forEach(function (el) {
      if (el.textContent.trim() === '-') {
        var item = el.closest('.nav-item');
        if (item) {
          item.classList.add('barangay-nav-separator');
        }
      }
    });

    var barangayName = document.querySelector('.main-header .navbar-nav .nav-item h5.nav-link');
    var brandText = sidebar.querySelector('.brand-text');
    if (barangayName && brandText && !brandText.textContent.trim()) {
      brandText.textContent = barangayName.textContent.trim();
      brandText.classList.add('barangay-brand-name');
    }

    var page = window.location.pathname.split('/').pop() || 'dashboard.php';
    var pageBase = page.split('?')[0];
    document.querySelectorAll('.nav-sidebar .nav-link').forEach(function (link) {
      var href = link.getAttribute('href');
      if (!href || href === '#') {
        return;
      }
      var linkPage = href.split('/').pop().split('?')[0];
      if (linkPage === pageBase) {
        link.classList.add('active');
      }
    });
  }

  function enhancePageTitle() {
    if (document.title && document.title.trim() !== '') {
      return;
    }
    var heading = document.querySelector('.content-header h1, .content-header h3, .card-title');
    if (heading && heading.textContent.trim()) {
      document.title = heading.textContent.trim() + ' | City of Valencia Portal';
    }
  }

  function patchSweetAlertTheme() {
    if (typeof Swal === 'undefined' || !Swal.fire || Swal.__barangayThemePatched) {
      return;
    }
    var nativeFire = Swal.fire.bind(Swal);
    Swal.fire = function (arg1, arg2, arg3) {
      var opts = arg1;
      if (typeof arg1 === 'string') {
        opts = { title: arg1 };
        if (typeof arg2 === 'string') {
          opts.text = arg2;
        }
        if (typeof arg3 === 'string') {
          opts.icon = arg3;
          opts.type = arg3;
        }
      }
      if (opts && typeof opts === 'object') {
        opts = Object.assign({}, opts, { confirmButtonColor: portalConfirmColor() });
        return nativeFire(opts);
      }
      return nativeFire(arg1, arg2, arg3);
    };
    Swal.__barangayThemePatched = true;
  }

  function init() {
    patchSweetAlertTheme();
    dismissPreloader();
    enhancePortalChrome();
    enhancePageTitle();
  }

  function dismissPreloader() {
    var preloader = document.querySelector('.preloader');
    if (!preloader) {
      return;
    }
    preloader.style.height = '0';
    preloader.style.overflow = 'hidden';
    window.setTimeout(function () {
      preloader.style.display = 'none';
    }, 200);
  }

  if (global.jQuery) {
    global.jQuery.ajaxSetup({
      xhrFields: { withCredentials: true }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(typeof window !== 'undefined' ? window : this);
