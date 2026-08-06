/**
 * Viewport auto-fit for printable report pages (phone / tablet / laptop / desktop).
 * Scales A4-style report roots to fit the screen; resets for print & PDF capture.
 */
(function (global) {
  'use strict';

  var A4_CSS_PX = 794; // ~210mm at 96dpi
  var fitTimer = null;
  var watching = false;

  function qsAll(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function isNoPrint(el) {
    return !!(el && el.classList && el.classList.contains('no-print'));
  }

  function shouldSkipTag(el) {
    if (!el || el.nodeType !== 1) return true;
    var tag = (el.tagName || '').toUpperCase();
    return tag === 'SCRIPT' || tag === 'STYLE' || tag === 'LINK' || tag === 'META' || tag === 'NOSCRIPT';
  }

  function ensureRoots() {
    var roots = qsAll('[data-report-fit="root"], [id$="PrintRoot"]');
    if (roots.length) {
      return roots;
    }

    var cert = document.querySelector('.certificate-document');
    if (cert) {
      cert.setAttribute('data-report-fit', 'root');
      return [cert];
    }

    var bodyKids = Array.prototype.slice.call(document.body.children).filter(function (el) {
      return !shouldSkipTag(el) && !isNoPrint(el);
    });
    if (!bodyKids.length) {
      return [];
    }

    var root = document.createElement('div');
    root.id = 'autoReportPrintRoot';
    root.setAttribute('data-report-fit', 'root');
    bodyKids[0].parentNode.insertBefore(root, bodyKids[0]);
    bodyKids.forEach(function (el) {
      root.appendChild(el);
    });
    return [root];
  }

  function wrapRoot(root) {
    if (root.closest && root.closest('.report-scale-wrap')) {
      return root.closest('.report-scale-wrap');
    }

    var viewport = document.createElement('div');
    viewport.className = 'report-viewport';

    var wrap = document.createElement('div');
    wrap.className = 'report-scale-wrap';

    root.parentNode.insertBefore(viewport, root);
    viewport.appendChild(wrap);
    wrap.appendChild(root);

    if (!root.classList.contains('report-paper')) {
      root.classList.add('report-paper');
    }
    return wrap;
  }

  function fitOne(wrap, root) {
    if (!wrap || !root || document.body.classList.contains('is-pdf-capture')) {
      return;
    }

    wrap.style.transform = 'none';
    wrap.style.height = 'auto';
    wrap.style.width = '';
    wrap.style.maxWidth = '';

    var preferA4 = root.getAttribute('data-report-fit') === 'a4'
      || root.id === 'cityReportPrintRoot'
      || root.id === 'guidePrintRoot'
      || root.id === 'bnpPrintRoot';

    if (preferA4) {
      root.style.width = '210mm';
      root.style.maxWidth = '210mm';
      wrap.style.width = '210mm';
      wrap.style.maxWidth = '210mm';
    }

    var designWidth = Math.max(
      root.scrollWidth || 0,
      root.offsetWidth || 0,
      wrap.offsetWidth || 0,
      preferA4 ? A4_CSS_PX : 0
    );
    if (designWidth < 32) {
      return;
    }

    var gutter = window.innerWidth < 768 ? 12 : 24;
    var available = Math.max(240, window.innerWidth - gutter);
    var scale = Math.min(1, available / designWidth);

    wrap.style.transformOrigin = 'top center';
    wrap.style.transform = 'scale(' + scale + ')';
    var naturalHeight = Math.max(root.scrollHeight || 0, wrap.scrollHeight || 0, root.offsetHeight || 0);
    wrap.style.height = (naturalHeight * scale) + 'px';
    wrap.setAttribute('data-fit-scale', String(scale.toFixed(4)));
  }

  function fitAll() {
    if (document.body.classList.contains('is-pdf-capture')) {
      return;
    }
    var roots = ensureRoots();
    roots.forEach(function (root) {
      var wrap = wrapRoot(root);
      fitOne(wrap, root);
    });
  }

  function scheduleFit() {
    if (fitTimer) {
      global.clearTimeout(fitTimer);
    }
    fitTimer = global.setTimeout(fitAll, 60);
  }

  function beginCapture() {
    document.body.classList.add('is-pdf-capture');
    qsAll('.report-scale-wrap').forEach(function (wrap) {
      wrap.style.transform = 'none';
      wrap.style.height = 'auto';
    });
  }

  function endCapture() {
    document.body.classList.remove('is-pdf-capture');
    scheduleFit();
  }

  function withPrintLayout(run) {
    beginCapture();
    return Promise.resolve()
      .then(run)
      .then(function (result) {
        endCapture();
        return result;
      }, function (err) {
        endCapture();
        throw err;
      });
  }

  function bind() {
    if (watching) {
      return;
    }
    watching = true;
    global.addEventListener('resize', scheduleFit);
    global.addEventListener('orientationchange', scheduleFit);
    global.addEventListener('beforeprint', beginCapture);
    global.addEventListener('afterprint', endCapture);
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', scheduleFit);
    } else {
      scheduleFit();
    }
    global.addEventListener('load', function () {
      scheduleFit();
      global.setTimeout(scheduleFit, 400);
      global.setTimeout(scheduleFit, 1200);
    });
  }

  global.barangayReportFit = {
    fit: fitAll,
    schedule: scheduleFit,
    beginCapture: beginCapture,
    endCapture: endCapture,
    withPrintLayout: withPrintLayout,
    bind: bind,
  };

  bind();
})(typeof window !== 'undefined' ? window : this);
