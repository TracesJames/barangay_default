/**
 * Appearance & accessibility preferences.
 * Persists to localStorage; applies body data-accent + a11y-* classes.
 */
(function (global) {
  'use strict';

  var STORAGE_KEY = 'barangay_appearance_prefs_v1';
  var DEFAULTS = {
    accent: 'default',
    textSize: 'normal',
    highContrast: false,
    reduceMotion: false
  };

  var ACCENTS = {
    default: { label: 'Default', color: '#14b8a6' },
    'pastel-red': { label: 'Coral', color: '#FFADAD' },
    'pastel-orange': { label: 'Peach', color: '#FFD6A5' },
    'pastel-yellow': { label: 'Yellow', color: '#FDFFB6' },
    'pastel-green': { label: 'Mint', color: '#CAFFBF' },
    'pastel-cyan': { label: 'Cyan', color: '#9BF6FF' },
    'pastel-blue': { label: 'Sky', color: '#A0C4FF' },
    'pastel-purple': { label: 'Lavender', color: '#BDB2FF' },
    'pastel-pink': { label: 'Pink', color: '#FFC6FF' }
  };

  var ACCENTS_NUTRITION = Object.assign({}, ACCENTS, {
    default: { label: 'Forest Green', color: '#16a34a' }
  });

  function accentCatalog() {
    var body = document.body;
    if (body && body.classList.contains('nutrition-portal')) {
      return ACCENTS_NUTRITION;
    }
    var panel = document.querySelector('[data-appearance-panel][data-portal="nutrition"]');
    return panel ? ACCENTS_NUTRITION : ACCENTS;
  }

  function readPrefs() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return Object.assign({}, DEFAULTS);
      }
      var parsed = JSON.parse(raw);
      var catalog = accentCatalog();
      return {
        accent: catalog[parsed.accent] ? parsed.accent : DEFAULTS.accent,
        textSize: ['normal', 'large', 'xlarge'].indexOf(parsed.textSize) >= 0
          ? parsed.textSize
          : DEFAULTS.textSize,
        highContrast: !!parsed.highContrast,
        reduceMotion: !!parsed.reduceMotion
      };
    } catch (e) {
      return Object.assign({}, DEFAULTS);
    }
  }

  function writePrefs(prefs) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch (e) {
      /* ignore quota / private mode */
    }
  }

  function applyPrefs(prefs) {
    var body = document.body;
    if (!body) {
      return;
    }
    prefs = prefs || readPrefs();
    var catalog = accentCatalog();
    var meta = catalog[prefs.accent] || catalog.default || ACCENTS.default;

    if (prefs.accent && prefs.accent !== 'default') {
      body.setAttribute('data-accent', prefs.accent);
    } else {
      body.removeAttribute('data-accent');
    }

    /* Always expose the active swatch color so CSS / preview can react */
    document.documentElement.style.setProperty('--user-accent-color', meta.color || '#16a34a');
    body.style.setProperty('--user-accent-color', meta.color || '#16a34a');

    body.classList.toggle('a11y-text-large', prefs.textSize === 'large');
    body.classList.toggle('a11y-text-xlarge', prefs.textSize === 'xlarge');
    body.classList.toggle('a11y-contrast', !!prefs.highContrast);
    body.classList.toggle('a11y-reduce-motion', !!prefs.reduceMotion);

    document.documentElement.style.setProperty(
      '--a11y-font-scale',
      prefs.textSize === 'xlarge' ? '1.25' : prefs.textSize === 'large' ? '1.12' : '1'
    );

    document.querySelectorAll('[data-appearance-preview]').forEach(function (el) {
      el.style.background = meta.color || '#16a34a';
      el.textContent = (meta.label || 'Default') + ' · ' + (meta.color || '');
    });
  }

  function saveAndApply(partial) {
    var prefs = Object.assign(readPrefs(), partial || {});
    writePrefs(prefs);
    applyPrefs(prefs);
    return prefs;
  }

  function resetPrefs() {
    writePrefs(Object.assign({}, DEFAULTS));
    applyPrefs(DEFAULTS);
    return Object.assign({}, DEFAULTS);
  }

  /** Early apply before paint (call from head inline script). */
  function bootEarly() {
    try {
      var prefs = readPrefs();
      var html = document.documentElement;
      html.style.setProperty(
        '--a11y-font-scale',
        prefs.textSize === 'xlarge' ? '1.25' : prefs.textSize === 'large' ? '1.12' : '1'
      );
      if (document.body) {
        applyPrefs(prefs);
      } else {
        document.addEventListener('DOMContentLoaded', function () {
          applyPrefs(prefs);
        });
      }
    } catch (e) {
      /* ignore */
    }
  }

  function bindPanel(root) {
    if (!root || root.getAttribute('data-appearance-bound') === '1') {
      return;
    }
    root.setAttribute('data-appearance-bound', '1');
    var prefs = readPrefs();

    function syncUi(p) {
      root.querySelectorAll('[data-accent-choice]').forEach(function (btn) {
        btn.classList.toggle('is-active', btn.getAttribute('data-accent-choice') === p.accent);
      });
      var textSel = root.querySelector('[name="appearance_text_size"]');
      if (textSel) {
        textSel.value = p.textSize;
      }
      var contrast = root.querySelector('[name="appearance_high_contrast"]');
      if (contrast) {
        contrast.checked = !!p.highContrast;
      }
      var motion = root.querySelector('[name="appearance_reduce_motion"]');
      if (motion) {
        motion.checked = !!p.reduceMotion;
      }
    }

    syncUi(prefs);

    root.querySelectorAll('[data-accent-choice]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var accent = btn.getAttribute('data-accent-choice') || 'default';
        var next = saveAndApply({ accent: accent });
        syncUi(next);
        var catalog = accentCatalog();
        var meta = catalog[next.accent] || catalog.default;
        if (typeof global.barangaySuccess === 'function') {
          global.barangaySuccess('Color updated', (meta.label || accent) + ' is now active.');
        } else if (typeof global.Swal !== 'undefined') {
          global.Swal.fire({
            title: 'Color updated',
            text: (meta.label || accent) + ' is now active.',
            type: 'success',
            timer: 1400,
            showConfirmButton: false
          });
        } else {
          /* Fallback if SweetAlert is not loaded yet */
          var preview = root.querySelector('[data-appearance-preview]');
          if (preview) {
            preview.textContent = 'Applied: ' + (meta.label || accent);
          }
        }
      });
    });

    var textSel = root.querySelector('[name="appearance_text_size"]');
    if (textSel) {
      textSel.addEventListener('change', function () {
        syncUi(saveAndApply({ textSize: textSel.value }));
      });
    }

    var contrast = root.querySelector('[name="appearance_high_contrast"]');
    if (contrast) {
      contrast.addEventListener('change', function () {
        syncUi(saveAndApply({ highContrast: contrast.checked }));
      });
    }

    var motion = root.querySelector('[name="appearance_reduce_motion"]');
    if (motion) {
      motion.addEventListener('change', function () {
        syncUi(saveAndApply({ reduceMotion: motion.checked }));
      });
    }

    var resetBtn = root.querySelector('[data-appearance-reset]');
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        syncUi(resetPrefs());
        if (typeof global.barangaySuccess === 'function') {
          var isNutrition = root.getAttribute('data-portal') === 'nutrition';
          global.barangaySuccess(
            'Appearance reset',
            isNutrition ? 'Forest Green and accessibility restored.' : 'Default colors and accessibility restored.'
          );
        }
      });
    }
  }

  global.BarangayAppearance = {
    ACCENTS: ACCENTS,
    ACCENTS_NUTRITION: ACCENTS_NUTRITION,
    DEFAULTS: DEFAULTS,
    read: readPrefs,
    apply: applyPrefs,
    save: saveAndApply,
    reset: resetPrefs,
    bootEarly: bootEarly,
    bindPanel: bindPanel
  };

  bootEarly();

  document.addEventListener('DOMContentLoaded', function () {
    applyPrefs(readPrefs());
    document.querySelectorAll('[data-appearance-panel]').forEach(bindPanel);
  });

  /* Backup: re-bind after full load (covers late-rendered panels) */
  window.addEventListener('load', function () {
    applyPrefs(readPrefs());
    document.querySelectorAll('[data-appearance-panel]').forEach(function (panel) {
      if (panel.getAttribute('data-appearance-bound') === '1') {
        return;
      }
      bindPanel(panel);
    });
  });
})(window);
