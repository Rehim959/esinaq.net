/**
 * eSinaq math question editor: formula toolbar + geometry canvas + live preview.
 */
(function () {
  'use strict';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function insertAtCursor(textarea, before, after) {
    after = after || '';
    textarea.focus();
    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var val = textarea.value;
    var selected = val.slice(start, end);
    var next = val.slice(0, start) + before + selected + after + val.slice(end);
    textarea.value = next;
    var pos = start + before.length + selected.length;
    textarea.setSelectionRange(pos, pos);
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function activeField(root) {
    var id = root.getAttribute('data-active-field') || 'question_text';
    return root.querySelector('[name="' + id + '"]') || root.querySelector('[name="question_text"]');
  }

  function bindToolbar(root) {
    $$('.math-tool', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var ta = activeField(root);
        if (!ta) return;
        var snip = btn.getAttribute('data-snip') || '';
        var wrap = btn.getAttribute('data-wrap');
        if (wrap) {
          var parts = wrap.split('||');
          insertAtCursor(ta, parts[0] || '', parts[1] || '');
        } else {
          insertAtCursor(ta, snip, '');
        }
        updatePreview(root);
      });
    });

    $$('textarea[name], input[name^="option_"]', root).forEach(function (el) {
      el.addEventListener('focus', function () {
        root.setAttribute('data-active-field', el.getAttribute('name'));
      });
      el.addEventListener('input', function () { updatePreview(root); });
    });
    var correctSel = root.querySelector('[name="correct_option"]');
    if (correctSel) {
      correctSel.addEventListener('change', function () { updatePreview(root); });
    }
    var subjectSel = document.querySelector('[name="subject_id"]');
    if (subjectSel) {
      subjectSel.addEventListener('change', function () { updatePreview(root); });
    }
  }

  function updatePreview(root) {
    var box = $('.math-preview-body', root);
    if (!box) return;
    var q = (root.querySelector('[name="question_text"]') || {}).value || '';
    var opts = {};
    ['a', 'b', 'c', 'd', 'e'].forEach(function (k) {
      var el = root.querySelector('[name="option_' + k + '"]');
      opts[k.toUpperCase()] = el ? el.value : '';
    });
    var correctEl = root.querySelector('[name="correct_option"]');
    var correct = correctEl ? correctEl.value : 'A';
    var subjectEl = document.querySelector('[name="subject_id"]');
    var subject = '';
    if (subjectEl && subjectEl.selectedOptions && subjectEl.selectedOptions[0]) {
      subject = subjectEl.selectedOptions[0].textContent || '';
    }
    var cfg = {
      question: q,
      options: opts,
      correct: correct,
      subject: subject,
      showCorrect: true,
      previewLabel: root.getAttribute('data-preview-label') || 'Şagird önizləməsi',
      correctLabel: root.getAttribute('data-correct-label') || 'Düzgün'
    };
    if (typeof window.esinaqRenderStudentPreview === 'function') {
      window.esinaqRenderStudentPreview(box, cfg);
      return;
    }
    // Fallback if shared script missing
    box.innerHTML = '<div class="q-content">' + (q || '') + '</div>';
  }

  /* ---------- Geometry canvas ---------- */
  function GeometryCanvas(canvas, root) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.root = root;
    this.tool = 'pen';
    this.drawing = false;
    this.start = null;
    this.shapes = [];
    this.current = null;
    this.color = '#0f172a';
    this.lineWidth = 2;
    this.eraserSize = 14;
    this.selectedIndex = -1;
    this.dragMode = null; // 'move' | 'resize' | null
    this.dragOffset = { x: 0, y: 0 };
    this.cursorPos = null;

    var self = this;
    canvas.addEventListener('mousedown', function (e) { self.onDown(e); });
    canvas.addEventListener('mousemove', function (e) {
      self.cursorPos = self.pos(e);
      self.onMove(e);
      if (self.tool === 'eraser') self.redraw(!!self.current);
    });
    canvas.addEventListener('mouseup', function (e) { self.onUp(e); });
    canvas.addEventListener('mouseleave', function (e) {
      self.cursorPos = null;
      if (self.drawing || self.dragMode) self.onUp(e);
      self.redraw(!!self.current);
    });
    canvas.addEventListener('dblclick', function (e) { self.onDblClick(e); });
    canvas.addEventListener('touchstart', function (e) {
      e.preventDefault();
      self.onDown(self.touchToMouse(e));
    }, { passive: false });
    canvas.addEventListener('touchmove', function (e) {
      e.preventDefault();
      self.onMove(self.touchToMouse(e));
    }, { passive: false });
    canvas.addEventListener('touchend', function (e) {
      e.preventDefault();
      self.onUp(self.touchToMouse(e));
    }, { passive: false });

    document.addEventListener('keydown', function (e) {
      if (!self.root.contains(document.activeElement) && document.activeElement !== self.canvas) {
        // allow delete when canvas interaction recently used
      }
      if ((e.key === 'Delete' || e.key === 'Backspace') && self.selectedIndex >= 0) {
        var tag = (document.activeElement && document.activeElement.tagName) || '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        e.preventDefault();
        self.shapes.splice(self.selectedIndex, 1);
        self.selectedIndex = -1;
        self.redraw();
      }
    });
  }

  GeometryCanvas.prototype.touchToMouse = function (e) {
    var t = e.changedTouches[0] || e.touches[0];
    if (!t) return { clientX: 0, clientY: 0 };
    return { clientX: t.clientX, clientY: t.clientY };
  };

  GeometryCanvas.prototype.pos = function (e) {
    var r = this.canvas.getBoundingClientRect();
    return {
      x: (e.clientX - r.left) * (this.canvas.width / r.width),
      y: (e.clientY - r.top) * (this.canvas.height / r.height)
    };
  };

  GeometryCanvas.prototype.setTool = function (tool) {
    this.tool = tool;
    if (tool !== 'select') {
      // keep selection visible only in select mode handles
    }
    this.canvas.style.cursor = tool === 'select' ? 'default' : (tool === 'eraser' ? 'cell' : 'crosshair');
    this.redraw();
  };

  GeometryCanvas.prototype.labelFont = function (s) {
    var size = s.fontSize || 16;
    return '600 ' + size + 'px "Plus Jakarta Sans", sans-serif';
  };

  GeometryCanvas.prototype.labelBounds = function (s) {
    var size = s.fontSize || 16;
    this.ctx.font = this.labelFont(s);
    var w = this.ctx.measureText(s.text || '').width;
    var h = size * 1.2;
    return { x: s.x, y: s.y - h + 4, w: w, h: h, size: size };
  };

  GeometryCanvas.prototype.resizeHandle = function (s) {
    var b = this.labelBounds(s);
    return { x: b.x + b.w + 2, y: b.y + b.h - 8, r: 7 };
  };

  GeometryCanvas.prototype.hitLabel = function (p) {
    for (var i = this.shapes.length - 1; i >= 0; i--) {
      var s = this.shapes[i];
      if (s.type !== 'label') continue;
      var b = this.labelBounds(s);
      if (p.x >= b.x - 4 && p.x <= b.x + b.w + 14 && p.y >= b.y - 4 && p.y <= b.y + b.h + 4) {
        return i;
      }
    }
    return -1;
  };

  GeometryCanvas.prototype.hitResizeHandle = function (p, idx) {
    if (idx < 0) return false;
    var s = this.shapes[idx];
    if (!s || s.type !== 'label') return false;
    var h = this.resizeHandle(s);
    var dx = p.x - h.x, dy = p.y - h.y;
    return dx * dx + dy * dy <= h.r * h.r * 2.5;
  };

  GeometryCanvas.prototype.onDown = function (e) {
    var p = this.pos(e);
    this.start = p;

    if (this.tool === 'select') {
      if (this.selectedIndex >= 0 && this.hitResizeHandle(p, this.selectedIndex)) {
        this.dragMode = 'resize';
        this.drawing = true;
        return;
      }
      var idx = this.hitLabel(p);
      this.selectedIndex = idx;
      if (idx >= 0) {
        var s = this.shapes[idx];
        this.dragMode = 'move';
        this.dragOffset = { x: p.x - s.x, y: p.y - s.y };
        this.drawing = true;
      } else {
        this.dragMode = null;
        this.drawing = false;
      }
      this.redraw();
      return;
    }

    if (this.tool === 'eraser') {
      var li = this.hitLabel(p);
      if (li >= 0) {
        this.shapes.splice(li, 1);
        if (this.selectedIndex === li) this.selectedIndex = -1;
        else if (this.selectedIndex > li) this.selectedIndex--;
        this.redraw();
        this.drawing = false;
        return;
      }
      this.drawing = true;
      this.current = { type: 'erase', points: [p], width: this.eraserSize };
      return;
    }

    this.drawing = true;
    this.selectedIndex = -1;

    if (this.tool === 'pen') {
      this.current = { type: 'path', points: [p], color: this.color, width: this.lineWidth };
    } else if (this.tool === 'label') {
      var text = prompt(this.root.getAttribute('data-label-prompt') || 'Etiket / dərəcə:', '90°');
      if (text) {
        this.shapes.push({
          type: 'label', x: p.x, y: p.y, text: text, color: this.color, fontSize: 18
        });
        this.selectedIndex = this.shapes.length - 1;
        this.redraw();
        // switch hint: user can move with Select tool
      }
      this.drawing = false;
    } else {
      this.current = { type: this.tool, x1: p.x, y1: p.y, x2: p.x, y2: p.y, color: this.color, width: this.lineWidth };
    }
  };

  GeometryCanvas.prototype.onMove = function (e) {
    var p = this.pos(e);

    if (this.tool === 'select' && this.drawing && this.selectedIndex >= 0) {
      var s = this.shapes[this.selectedIndex];
      if (!s || s.type !== 'label') return;
      if (this.dragMode === 'move') {
        s.x = p.x - this.dragOffset.x;
        s.y = p.y - this.dragOffset.y;
      } else if (this.dragMode === 'resize') {
        var b = this.labelBounds(s);
        var newSize = Math.max(10, Math.min(72, p.x - s.x));
        // use distance from label origin for intuitive scale
        var dist = Math.sqrt(Math.pow(p.x - s.x, 2) + Math.pow(p.y - (s.y - (s.fontSize || 16)), 2));
        s.fontSize = Math.max(10, Math.min(72, Math.round(dist * 0.45) || newSize));
      }
      this.redraw();
      return;
    }

    if (!this.drawing || !this.current) return;
    if (this.current.type === 'path' || this.current.type === 'erase') {
      this.current.points.push(p);
    } else {
      this.current.x2 = p.x;
      this.current.y2 = p.y;
    }
    this.redraw(true);
  };

  GeometryCanvas.prototype.onUp = function () {
    if (this.tool === 'select') {
      this.drawing = false;
      this.dragMode = null;
      this.redraw();
      return;
    }
    if (!this.drawing) return;
    this.drawing = false;
    if (this.current) {
      this.shapes.push(this.current);
      this.current = null;
    }
    this.redraw();
  };

  GeometryCanvas.prototype.onDblClick = function (e) {
    var p = this.pos(e);
    var idx = this.hitLabel(p);
    if (idx < 0) return;
    var s = this.shapes[idx];
    var next = prompt(this.root.getAttribute('data-label-prompt') || 'Etiket:', s.text);
    if (next !== null && next !== '') {
      s.text = next;
      this.selectedIndex = idx;
      this.redraw();
    }
  };

  GeometryCanvas.prototype.nudgeFont = function (delta) {
    if (this.selectedIndex < 0) return;
    var s = this.shapes[this.selectedIndex];
    if (!s || s.type !== 'label') return;
    s.fontSize = Math.max(10, Math.min(72, (s.fontSize || 16) + delta));
    this.redraw();
  };

  GeometryCanvas.prototype.nudgeEraser = function (delta) {
    this.eraserSize = Math.max(4, Math.min(48, this.eraserSize + delta));
    this.syncEraserUi();
    this.redraw(!!this.current);
  };

  GeometryCanvas.prototype.setEraserSize = function (size) {
    this.eraserSize = Math.max(4, Math.min(48, Math.round(size)));
    this.syncEraserUi();
    this.redraw(!!this.current);
  };

  GeometryCanvas.prototype.syncEraserUi = function () {
    var label = this.root.querySelector('.geo-eraser-size-val');
    if (label) label.textContent = String(this.eraserSize);
    var range = this.root.querySelector('.geo-eraser-range');
    if (range) range.value = String(this.eraserSize);
  };

  GeometryCanvas.prototype.deleteSelected = function () {
    if (this.selectedIndex < 0) return;
    this.shapes.splice(this.selectedIndex, 1);
    this.selectedIndex = -1;
    this.redraw();
  };

  GeometryCanvas.prototype.clear = function () {
    this.shapes = [];
    this.current = null;
    this.selectedIndex = -1;
    this.redraw();
  };

  GeometryCanvas.prototype.undo = function () {
    this.shapes.pop();
    if (this.selectedIndex >= this.shapes.length) this.selectedIndex = -1;
    this.redraw();
  };

  GeometryCanvas.prototype.redraw = function (withCurrent) {
    var ctx = this.ctx;
    var w = this.canvas.width;
    var h = this.canvas.height;
    ctx.clearRect(0, 0, w, h);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, w, h);
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    for (var x = 0; x < w; x += 20) {
      ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, h); ctx.stroke();
    }
    for (var y = 0; y < h; y += 20) {
      ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
    }
    var list = this.shapes.slice();
    if (withCurrent && this.current) list.push(this.current);
    list.forEach(function (s) { this.drawShape(s); }.bind(this));

    if (this.selectedIndex >= 0 && this.shapes[this.selectedIndex] && this.shapes[this.selectedIndex].type === 'label') {
      this.drawSelection(this.shapes[this.selectedIndex]);
    }
    if (this.tool === 'eraser' && this.cursorPos) {
      ctx.save();
      ctx.strokeStyle = 'rgba(234, 88, 12, 0.85)';
      ctx.fillStyle = 'rgba(234, 88, 12, 0.12)';
      ctx.lineWidth = 1.5;
      ctx.beginPath();
      ctx.arc(this.cursorPos.x, this.cursorPos.y, this.eraserSize / 2, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
      ctx.restore();
    }
  };

  GeometryCanvas.prototype.drawSelection = function (s) {
    var ctx = this.ctx;
    var b = this.labelBounds(s);
    ctx.save();
    ctx.strokeStyle = '#ea580c';
    ctx.lineWidth = 1.5;
    ctx.setLineDash([4, 3]);
    ctx.strokeRect(b.x - 4, b.y - 4, b.w + 16, b.h + 8);
    ctx.setLineDash([]);
    var hdl = this.resizeHandle(s);
    ctx.fillStyle = '#ea580c';
    ctx.beginPath();
    ctx.arc(hdl.x, hdl.y, hdl.r, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = '#fff';
    ctx.font = 'bold 10px sans-serif';
    ctx.fillText('↔', hdl.x - 5, hdl.y + 3);
    ctx.restore();
  };

  GeometryCanvas.prototype.drawShape = function (s) {
    var ctx = this.ctx;
    ctx.strokeStyle = s.color || this.color;
    ctx.fillStyle = s.color || this.color;
    ctx.lineWidth = s.width || this.lineWidth;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    if (s.type === 'erase') {
      if (!s.points || s.points.length < 2) return;
      ctx.save();
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = s.width || 14;
      ctx.beginPath();
      ctx.moveTo(s.points[0].x, s.points[0].y);
      for (var ei = 1; ei < s.points.length; ei++) ctx.lineTo(s.points[ei].x, s.points[ei].y);
      ctx.stroke();
      ctx.restore();
      return;
    }

    if (s.type === 'path') {
      if (!s.points || s.points.length < 2) return;
      ctx.beginPath();
      ctx.moveTo(s.points[0].x, s.points[0].y);
      for (var i = 1; i < s.points.length; i++) ctx.lineTo(s.points[i].x, s.points[i].y);
      ctx.stroke();
      return;
    }
    if (s.type === 'label') {
      ctx.font = this.labelFont(s);
      ctx.fillStyle = s.color || this.color;
      ctx.fillText(s.text, s.x, s.y);
      return;
    }

    var x1 = s.x1, y1 = s.y1, x2 = s.x2, y2 = s.y2;
    var minX = Math.min(x1, x2), minY = Math.min(y1, y2);
    var ww = Math.abs(x2 - x1), hh = Math.abs(y2 - y1);

    if (s.type === 'line' || s.type === 'angle') {
      ctx.beginPath();
      ctx.moveTo(x1, y1);
      ctx.lineTo(x2, y2);
      ctx.stroke();
      if (s.type === 'angle') {
        ctx.beginPath();
        ctx.moveTo(x1, y1);
        ctx.lineTo(x1 + Math.max(ww, 40), y1);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(x1, y1, 18, 0, Math.atan2(y2 - y1, x2 - x1), false);
        ctx.stroke();
      }
      return;
    }
    if (s.type === 'circle') {
      var r = Math.sqrt(ww * ww + hh * hh) / 2;
      var cx = (x1 + x2) / 2, cy = (y1 + y2) / 2;
      ctx.beginPath();
      ctx.arc(cx, cy, Math.max(r, 2), 0, Math.PI * 2);
      ctx.stroke();
      ctx.beginPath();
      ctx.arc(cx, cy, 2, 0, Math.PI * 2);
      ctx.fill();
      return;
    }
    if (s.type === 'square') {
      var side = Math.max(ww, hh);
      ctx.strokeRect(minX, minY, side, side);
      return;
    }
    if (s.type === 'rect') {
      ctx.strokeRect(minX, minY, ww, hh);
      return;
    }
    if (s.type === 'triangle') {
      ctx.beginPath();
      ctx.moveTo(minX + ww / 2, minY);
      ctx.lineTo(minX + ww, minY + hh);
      ctx.lineTo(minX, minY + hh);
      ctx.closePath();
      ctx.stroke();
      return;
    }
    if (s.type === 'trapezoid') {
      var inset = Math.max(ww * 0.2, 8);
      ctx.beginPath();
      ctx.moveTo(minX + inset, minY);
      ctx.lineTo(minX + ww - inset, minY);
      ctx.lineTo(minX + ww, minY + hh);
      ctx.lineTo(minX, minY + hh);
      ctx.closePath();
      ctx.stroke();
      return;
    }
    if (s.type === 'rhombus') {
      ctx.beginPath();
      ctx.moveTo(minX + ww / 2, minY);
      ctx.lineTo(minX + ww, minY + hh / 2);
      ctx.lineTo(minX + ww / 2, minY + hh);
      ctx.lineTo(minX, minY + hh / 2);
      ctx.closePath();
      ctx.stroke();
      return;
    }
    if (s.type === 'parallelogram') {
      var skew = ww * 0.25;
      ctx.beginPath();
      ctx.moveTo(minX + skew, minY);
      ctx.lineTo(minX + ww, minY);
      ctx.lineTo(minX + ww - skew, minY + hh);
      ctx.lineTo(minX, minY + hh);
      ctx.closePath();
      ctx.stroke();
      return;
    }
    if (s.type === 'cone') {
      var cx2 = minX + ww / 2;
      ctx.beginPath();
      ctx.moveTo(cx2, minY);
      ctx.lineTo(minX + ww, minY + hh * 0.85);
      ctx.lineTo(minX, minY + hh * 0.85);
      ctx.closePath();
      ctx.stroke();
      ctx.beginPath();
      ctx.ellipse(cx2, minY + hh * 0.85, ww / 2, Math.max(hh * 0.12, 4), 0, 0, Math.PI * 2);
      ctx.stroke();
      return;
    }
    if (s.type === 'box') {
      var dx = ww * 0.35, dy = hh * 0.25;
      var x = minX, y = minY + dy;
      ctx.beginPath();
      ctx.rect(x, y, ww - dx, hh - dy);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(x, y);
      ctx.lineTo(x + dx, y - dy);
      ctx.lineTo(x + ww, y - dy);
      ctx.lineTo(x + ww - dx, y);
      ctx.closePath();
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(x + ww - dx, y);
      ctx.lineTo(x + ww, y - dy);
      ctx.lineTo(x + ww, y + hh - 2 * dy);
      ctx.lineTo(x + ww - dx, y + hh - dy);
      ctx.closePath();
      ctx.stroke();
      return;
    }
  };

  GeometryCanvas.prototype.toBlob = function (cb) {
    // export without selection chrome
    var keep = this.selectedIndex;
    this.selectedIndex = -1;
    this.redraw();
    this.canvas.toBlob(function (blob) {
      this.selectedIndex = keep;
      this.redraw();
      cb(blob);
    }.bind(this), 'image/png');
  };

  function bindCanvas(root) {
    var canvas = $('.geo-canvas', root);
    if (!canvas) return null;
    var geo = new GeometryCanvas(canvas, root);
    geo.redraw();
    geo.syncEraserUi();

    $$('.geo-tool', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('.geo-tool', root).forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        geo.setTool(btn.getAttribute('data-tool'));
      });
    });
    var clearBtn = $('.geo-clear', root);
    if (clearBtn) clearBtn.addEventListener('click', function () { geo.clear(); });
    var undoBtn = $('.geo-undo', root);
    if (undoBtn) undoBtn.addEventListener('click', function () { geo.undo(); });
    var bigger = $('.geo-font-up', root);
    if (bigger) bigger.addEventListener('click', function () { geo.nudgeFont(2); });
    var smaller = $('.geo-font-down', root);
    if (smaller) smaller.addEventListener('click', function () { geo.nudgeFont(-2); });
    var delBtn = $('.geo-delete', root);
    if (delBtn) delBtn.addEventListener('click', function () { geo.deleteSelected(); });
    var erUp = $('.geo-eraser-up', root);
    if (erUp) erUp.addEventListener('click', function () { geo.nudgeEraser(2); });
    var erDown = $('.geo-eraser-down', root);
    if (erDown) erDown.addEventListener('click', function () { geo.nudgeEraser(-2); });
    var erRange = $('.geo-eraser-range', root);
    if (erRange) {
      erRange.addEventListener('input', function () {
        geo.setEraserSize(parseInt(erRange.value, 10) || 14);
      });
    }

    var insertBtn = $('.geo-insert', root);
    if (insertBtn) {
      insertBtn.addEventListener('click', function () {
        var uploadUrl = root.getAttribute('data-upload-url');
        var csrf = root.getAttribute('data-csrf');
        if (!uploadUrl) return;
        insertBtn.disabled = true;
        geo.toBlob(function (blob) {
          if (!blob) { insertBtn.disabled = false; return; }
          var fd = new FormData();
          fd.append('_csrf', csrf);
          fd.append('diagram', blob, 'diagram.png');
          fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              insertBtn.disabled = false;
              if (!data || !data.ok || !data.url) {
                alert((data && data.error) || 'Upload failed');
                return;
              }
              var ta = root.querySelector('[name="question_text"]');
              if (ta) {
                insertAtCursor(ta, '\n<img src="' + data.url + '" alt="diagram">\n', '');
                updatePreview(root);
              }
            })
            .catch(function () {
              insertBtn.disabled = false;
              alert('Upload failed');
            });
        });
      });
    }
    return geo;
  }

  function init() {
    $$('.math-editor').forEach(function (root) {
      bindToolbar(root);
      bindCanvas(root);
      updatePreview(root);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
