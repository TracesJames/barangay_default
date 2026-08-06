/**
 * Reliable html2canvas → jsPDF for long nutrition print pages.
 * Captures in fixed-height viewport slices so browsers never allocate a huge canvas
 * (which previously produced blank/empty city report PDFs).
 */
(function (global) {
  'use strict';

  var DEFAULT_SCALE = 1;
  var SLICE_CSS_PX = 1400;
  var JPEG_QUALITY = 0.9;
  var MAX_CANVAS_EDGE = 8192;

  function setStatus(fn, text) {
    if (typeof fn === 'function') {
      fn(text || '');
    }
  }

  function isUsableCanvas(canvas) {
    if (!canvas || canvas.width < 8 || canvas.height < 8) {
      return false;
    }
    try {
      var ctx = canvas.getContext('2d');
      if (!ctx) {
        return false;
      }
      var sample = ctx.getImageData(
        Math.floor(canvas.width / 2),
        Math.min(20, canvas.height - 1),
        1,
        1
      ).data;
      // Reject fully transparent samples from failed captures.
      if (sample[3] === 0) {
        return false;
      }
    } catch (err) {
      // Tainted canvas — still usable if toDataURL works later.
    }
    return true;
  }

  function canvasToJpeg(canvas) {
    try {
      return canvas.toDataURL('image/jpeg', JPEG_QUALITY);
    } catch (err) {
      console.error('toDataURL failed', err);
      return null;
    }
  }

  /**
   * Top-level blocks under the print root.
   * @param {HTMLElement} root
   * @returns {HTMLElement[]}
   */
  function collectSections(root) {
    var kids = Array.prototype.slice.call(root.children || []).filter(function (el) {
      if (!el || el.nodeType !== 1) {
        return false;
      }
      if (el.classList && el.classList.contains('no-print')) {
        return false;
      }
      var style = global.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden') {
        return false;
      }
      return Math.max(el.offsetHeight || 0, el.scrollHeight || 0) > 4;
    });

    if (kids.length === 0) {
      return [root];
    }

    // Prefer marked sections when present; expand tall wrappers into sub-sections.
    var marked = Array.prototype.slice.call(root.querySelectorAll('[data-pdf-section]'));
    if (marked.length > 1) {
      var expandedMarked = [];
      marked.forEach(function (el) {
        var h = Math.max(el.offsetHeight || 0, el.scrollHeight || 0);
        var sub = Array.prototype.slice.call(el.querySelectorAll('.eopt-section'));
        if (h > SLICE_CSS_PX * 2 && sub.length > 1) {
          expandedMarked = expandedMarked.concat(sub);
          return;
        }
        if (Math.max(el.offsetHeight || 0, el.scrollHeight || 0) > 4) {
          expandedMarked.push(el);
        }
      });
      if (expandedMarked.length) {
        return expandedMarked;
      }
    }

    // Flatten e-OPT / tall wrappers into their section children when huge.
    var expanded = [];
    kids.forEach(function (el) {
      var h = Math.max(el.offsetHeight || 0, el.scrollHeight || 0);
      var sectionKids = el.querySelectorAll
        ? Array.prototype.slice.call(el.querySelectorAll(':scope > .eopt-section, :scope > .mellpi-form, :scope > .bnp-form'))
        : [];
      if (h > SLICE_CSS_PX * 2 && sectionKids.length > 1) {
        expanded = expanded.concat(sectionKids);
        return;
      }
      // Direct e-OPT sections are already siblings under root.
      expanded.push(el);
    });

    return expanded.length ? expanded : kids;
  }

  /**
   * Capture element as one or more canvases using an off-screen clipped clone.
   * @param {HTMLElement} el
   * @param {number} scale
   * @returns {Promise<HTMLCanvasElement[]>}
   */
  function captureElementSlices(el, scale) {
    var totalH = Math.max(el.scrollHeight || 0, el.offsetHeight || 0);
    var totalW = Math.max(el.scrollWidth || 0, el.offsetWidth || 0, 600);
    if (totalH < 4 || totalW < 4) {
      return Promise.resolve([]);
    }

    // Short enough — single capture.
    if (totalH * scale <= MAX_CANVAS_EDGE && totalH <= SLICE_CSS_PX * 1.5) {
      return global
        .html2canvas(el, {
          scale: scale,
          useCORS: true,
          allowTaint: false,
          backgroundColor: '#ffffff',
          logging: false,
          imageTimeout: 10000,
          removeContainer: true,
        })
        .then(function (canvas) {
          return isUsableCanvas(canvas) ? [canvas] : [];
        })
        .catch(function (err) {
          console.error('single capture failed', err);
          return [];
        });
    }

    var slices = [];
    var top = 0;

    function nextSlice() {
      if (top >= totalH) {
        return Promise.resolve(slices);
      }

      var sliceH = Math.min(SLICE_CSS_PX, totalH - top);
      var holder = document.createElement('div');
      holder.setAttribute('data-nutrition-pdf-slice', '1');
      holder.style.cssText = [
        'position:fixed',
        'left:-12000px',
        'top:0',
        'width:' + totalW + 'px',
        'height:' + sliceH + 'px',
        'overflow:hidden',
        'background:#ffffff',
        'z-index:-1',
        'pointer-events:none',
        'box-sizing:border-box',
      ].join(';');

      var clone = el.cloneNode(true);
      clone.style.boxSizing = 'border-box';
      clone.style.width = totalW + 'px';
      clone.style.maxWidth = totalW + 'px';
      clone.style.margin = '0';
      clone.style.marginTop = -top + 'px';
      clone.style.transform = 'none';
      holder.appendChild(clone);
      document.body.appendChild(holder);

      var captureScale = scale;
      if (sliceH * captureScale > MAX_CANVAS_EDGE) {
        captureScale = Math.max(0.6, MAX_CANVAS_EDGE / sliceH);
      }

      return global
        .html2canvas(holder, {
          scale: captureScale,
          width: totalW,
          height: sliceH,
          windowWidth: totalW,
          windowHeight: sliceH,
          useCORS: true,
          allowTaint: false,
          backgroundColor: '#ffffff',
          logging: false,
          imageTimeout: 10000,
          removeContainer: true,
        })
        .then(function (canvas) {
          if (holder.parentNode) {
            holder.parentNode.removeChild(holder);
          }
          if (isUsableCanvas(canvas)) {
            slices.push(canvas);
          }
          top += sliceH;
          return nextSlice();
        })
        .catch(function (err) {
          console.error('slice capture failed', err);
          if (holder.parentNode) {
            holder.parentNode.removeChild(holder);
          }
          top += sliceH;
          return nextSlice();
        });
    }

    return nextSlice();
  }

  /**
   * @param {*} pdf
   * @param {HTMLCanvasElement} canvas
   * @param {{margin:number, usableWidth:number, usableHeight:number, pageStarted:boolean}} state
   */
  function appendCanvasPages(pdf, canvas, state) {
    var imgData = canvasToJpeg(canvas);
    if (!imgData) {
      return;
    }

    var imgWidth = state.usableWidth;
    var imgHeight = (canvas.height * imgWidth) / canvas.width;
    var heightLeft = imgHeight;
    var position = state.margin;

    if (state.pageStarted) {
      pdf.addPage();
    }
    state.pageStarted = true;

    pdf.addImage(imgData, 'JPEG', state.margin, position, imgWidth, imgHeight);
    heightLeft -= state.usableHeight;

    while (heightLeft > 1) {
      position = state.margin - (imgHeight - heightLeft);
      pdf.addPage();
      pdf.addImage(imgData, 'JPEG', state.margin, position, imgWidth, imgHeight);
      heightLeft -= state.usableHeight;
    }
  }

  /**
   * @param {{
   *   root: HTMLElement,
   *   filename: string,
   *   button?: HTMLButtonElement|null,
   *   setStatus?: function(string): void,
   *   scale?: number
   * }} options
   * @returns {Promise<void>}
   */
  function nutritionDownloadPrintPdf(options) {
    var root = options.root;
    var filename = options.filename || 'nutrition-report.pdf';
    var btn = options.button || null;
    var status = options.setStatus;
    var scale = typeof options.scale === 'number' ? options.scale : DEFAULT_SCALE;

    if (!root) {
      setStatus(status, 'Print content not found. Use Print → Save as PDF.');
      return Promise.resolve();
    }
    if (!global.html2canvas || !global.jspdf || !global.jspdf.jsPDF) {
      setStatus(status, 'PDF library failed to load. Use Print → Save as PDF instead.');
      return Promise.resolve();
    }

    if (btn) {
      btn.disabled = true;
    }

    var usedReportFit = false;
    if (global.barangayReportFit && typeof global.barangayReportFit.beginCapture === 'function') {
      global.barangayReportFit.beginCapture();
      usedReportFit = true;
    } else {
      document.body.classList.add('is-pdf-capture');
    }

    // Hide toolbar so it never appears in captures.
    var toolbars = document.querySelectorAll('.no-print');
    var prevDisplay = [];
    Array.prototype.forEach.call(toolbars, function (el, i) {
      prevDisplay[i] = el.style.display;
      el.style.display = 'none';
    });

    setStatus(status, 'Preparing PDF… scanning sections…');

    var sections = collectSections(root);
    var jsPDF = global.jspdf.jsPDF;
    var pdf = new jsPDF('p', 'mm', 'a4');
    var pageWidth = pdf.internal.pageSize.getWidth();
    var pageHeight = pdf.internal.pageSize.getHeight();
    var margin = 8;
    var state = {
      margin: margin,
      usableWidth: pageWidth - margin * 2,
      usableHeight: pageHeight - margin * 2,
      pageStarted: false,
    };

    var index = 0;
    var captured = 0;

    function restoreToolbar() {
      Array.prototype.forEach.call(toolbars, function (el, i) {
        el.style.display = prevDisplay[i] || '';
      });
      if (btn) {
        btn.disabled = false;
      }
      if (usedReportFit && global.barangayReportFit && typeof global.barangayReportFit.endCapture === 'function') {
        global.barangayReportFit.endCapture();
      } else {
        document.body.classList.remove('is-pdf-capture');
      }
    }

    function nextSection() {
      if (index >= sections.length) {
        restoreToolbar();
        if (!state.pageStarted || captured === 0) {
          setStatus(status, 'Could not capture pages. Use Print → Save as PDF instead.');
          return Promise.resolve();
        }
        try {
          pdf.save(filename);
          setStatus(status, 'PDF downloaded (' + captured + ' part' + (captured === 1 ? '' : 's') + ').');
        } catch (err) {
          console.error(err);
          setStatus(status, 'Could not save PDF. Use Print → Save as PDF instead.');
        }
        return Promise.resolve();
      }

      var section = sections[index++];
      setStatus(status, 'Preparing PDF… section ' + index + ' of ' + sections.length + '…');

      // Ensure section is in view for more accurate layout metrics.
      try {
        section.scrollIntoView({ block: 'nearest' });
      } catch (err) {}

      return captureElementSlices(section, scale)
        .then(function (canvases) {
          canvases.forEach(function (canvas) {
            try {
              appendCanvasPages(pdf, canvas, state);
              captured += 1;
            } catch (err) {
              console.error('append failed', err);
            }
          });
        })
        .catch(function (err) {
          console.error('section failed', err);
        })
        .then(nextSection);
    }

    return nextSection().catch(function (err) {
      console.error(err);
      restoreToolbar();
      setStatus(status, 'Could not create PDF. Use Print → Save as PDF instead.');
    });
  }

  global.nutritionDownloadPrintPdf = nutritionDownloadPrintPdf;
})(typeof window !== 'undefined' ? window : this);
