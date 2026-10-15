/**
 * Appearance & accessibility preferences.
 * Persists to localStorage; applies body data-accent + a11y-* classes.
 */
(function (global) {
  'use strict';

  var STORAGE_KEY = 'barangay_appearance_prefs_v1';
  var TEXT_SIZES = ['normal', 'large', 'xlarge'];
  var FONT_SCALE = { normal: '1', large: '1.12', xlarge: '1.25' };

  function systemPrefersReducedMotion() {
    try {
      return !!(global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches);
    } catch (e) {
      return false;
    }
  }

  var DEFAULTS = {
    accent: 'default',
    textSize: 'normal',
    highContrast: false,
    underlineLinks: false,
    reduceMotion: systemPrefersReducedMotion()
  };

  var ACCENTS = {
    default: { label: 'Teal', color: '#14b8a6' },
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
        textSize: TEXT_SIZES.indexOf(parsed.textSize) >= 0 ? parsed.textSize : DEFAULTS.textSize,
        highContrast: !!parsed.highContrast,
        underlineLinks: !!parsed.underlineLinks,
        reduceMotion: typeof parsed.reduceMotion === 'boolean' ? parsed.reduceMotion : DEFAULTS.reduceMotion
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
    body.classList.toggle('a11y-underline-links', !!prefs.underlineLinks);
    body.classList.toggle('a11y-reduce-motion', !!prefs.reduceMotion);

    document.documentElement.style.setProperty('--a11y-font-scale', FONT_SCALE[prefs.textSize] || '1');

    document.querySelectorAll('[data-appearance-preview]').forEach(function (el) {
      el.textContent = (meta.label || 'Default') + ' \u00b7 ' + (meta.color || '');
    });
  }

  function saveAndApply(partial) {
    var prefs = Object.assign(readPrefs(), partial || {});
    writePrefs(prefs);
    applyPrefs(prefs);
    return prefs;
  }

  function resetPrefs() {
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch (e) {
      /* ignore */
    }
    var prefs = Object.assign({}, DEFAULTS);
    applyPrefs(prefs);
    return prefs;
  }

  /** Early apply before paint (call from head inline script). */
  function bootEarly() {
    try {
      var prefs = readPrefs();
      document.documentElement.style.setProperty('--a11y-font-scale', FONT_SCALE[prefs.textSize] || '1');
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

  var TOGGLES = {
    appearance_high_contrast: 'highContrast',
    appearance_underline_links: 'underlineLinks',
    appearance_reduce_motion: 'reduceMotion'
  };

  function bindPanel(root) {
    if (!root || root.getAttribute('data-appearance-bound') === '1') {
      return;
    }
    root.setAttribute('data-appearance-bound', '1');

    var status = root.querySelector('[data-appearance-status]');
    var statusIdle = status ? status.innerHTML : '';
    var statusTimer = null;

    function flashStatus(message) {
      if (!status) {
        return;
      }
      status.classList.add('is-saved');
      status.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' + message;
      clearTimeout(statusTimer);
      statusTimer = setTimeout(function () {
        status.classList.remove('is-saved');
        status.innerHTML = statusIdle;
      }, 2200);
    }

    var swatches = Array.prototype.slice.call(root.querySelectorAll('[data-accent-choice]'));

    function syncUi(p) {
      swatches.forEach(function (btn) {
        var active = btn.getAttribute('data-accent-choice') === p.accent;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-checked', active ? 'true' : 'false');
        btn.setAttribute('tabindex', active ? '0' : '-1');
      });
      root.querySelectorAll('[name="appearance_text_size"]').forEach(function (input) {
        input.checked = input.value === p.textSize;
        var option = input.closest('.text-size-option');
        if (option) {
          option.classList.toggle('is-active', input.checked);
        }
      });
      Object.keys(TOGGLES).forEach(function (name) {
        var input = root.querySelector('[name="' + name + '"]');
        if (input) {
          input.checked = !!p[TOGGLES[name]];
          var option = input.closest('.a11y-option');
          if (option) {
            option.classList.toggle('is-on', input.checked);
          }
        }
      });
    }

    function chooseAccent(btn) {
      var accent = btn.getAttribute('data-accent-choice') || 'default';
      var next = saveAndApply({ accent: accent });
      syncUi(next);
      var meta = accentCatalog()[next.accent] || accentCatalog().default;
      flashStatus((meta.label || accent) + ' applied');
    }

    syncUi(readPrefs());

    swatches.forEach(function (btn, index) {
      btn.addEventListener('click', function () {
        chooseAccent(btn);
      });
      btn.addEventListener('keydown', function (e) {
        var step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
        if (e.key === 'Home') {
          step = -index;
        } else if (e.key === 'End') {
          step = swatches.length - 1 - index;
        }
        if (step === undefined) {
          return;
        }
        e.preventDefault();
        var target = swatches[(index + step + swatches.length) % swatches.length];
        target.focus();
        chooseAccent(target);
      });
    });

    root.querySelectorAll('[name="appearance_text_size"]').forEach(function (input) {
      input.addEventListener('change', function () {
        syncUi(saveAndApply({ textSize: input.value }));
        var label = input.closest('.text-size-option');
        flashStatus('Text size: ' + (label ? label.textContent.replace('Aa', '').trim() : input.value));
      });
    });

    Object.keys(TOGGLES).forEach(function (name) {
      var input = root.querySelector('[name="' + name + '"]');
      if (!input) {
        return;
      }
      input.addEventListener('change', function () {
        var partial = {};
        partial[TOGGLES[name]] = input.checked;
        syncUi(saveAndApply(partial));
        var title = input.closest('.a11y-option');
        title = title ? title.querySelector('strong') : null;
        flashStatus((title ? title.textContent : 'Option') + (input.checked ? ' on' : ' off'));
      });
    });

    var resetBtn = root.querySelector('[data-appearance-reset]');
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        syncUi(resetPrefs());
        flashStatus('Defaults restored');
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
    document.querySelectorAll('[data-appearance-panel]').forEach(bindPanel);
  });
})(window);
