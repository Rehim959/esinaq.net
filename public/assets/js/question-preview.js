/**
 * Shared student-exam style preview builder for admin forms.
 * window.esinaqBuildStudentPreview({ question, options, correct, subject, index, total, showCorrect })
 */
(function (global) {
  'use strict';

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fieldToHtml(raw) {
    var imgs = [];
    var s = String(raw == null ? '' : raw);
    s = s.replace(/<img\b[^>]*\bsrc\s*=\s*(["'])([^"']+)\1[^>]*>/gi, function (_, q, src) {
      var ok = /^\/uploads\/questions\/[a-zA-Z0-9._-]+\.(?:png|jpe?g|webp)$/i.test(src)
        || /^blob:/i.test(src)
        || /^data:image\//i.test(src);
      if (!ok) return '';
      var key = '%%IMG' + imgs.length + '%%';
      imgs.push('<img src="' + src.replace(/"/g, '') + '" alt="" class="q-diagram">');
      return key;
    });
    s = esc(s).replace(/\n/g, '<br>');
    imgs.forEach(function (tag, i) {
      s = s.replace('%%IMG' + i + '%%', tag);
    });
    return s;
  }

  function build(cfg) {
    cfg = cfg || {};
    var q = cfg.question || '';
    var options = cfg.options || {};
    var correct = String(cfg.correct || '').toUpperCase();
    var subject = cfg.subject || '';
    var index = cfg.index;
    var total = cfg.total;
    var showCorrect = !!cfg.showCorrect;
    var metaLeft = cfg.metaLeft || '';
    if (!metaLeft) {
      if (index != null && total != null) {
        metaLeft = 'Sual ' + index + ' / ' + total;
      } else {
        metaLeft = cfg.previewLabel || 'Şagird önizləməsi';
      }
    }

    var html = '<article class="question-box student-preview-box">';
    html += '<div class="q-meta"><span>' + esc(metaLeft) + '</span>';
    if (subject) html += '<span class="subject-tag">' + esc(subject) + '</span>';
    html += '</div>';
    html += '<h2 class="q-title q-content">' + fieldToHtml(q) + '</h2>';
    html += '<div class="options">';

    ['A', 'B', 'C', 'D', 'E'].forEach(function (letter) {
      var key = letter.toLowerCase();
      var text = options[letter] != null ? options[letter] : options[key];
      if (text == null || String(text).trim() === '') return;
      var isCorrect = showCorrect && correct === letter;
      html += '<div class="option preview-option' + (isCorrect ? ' selected' : '') + '">';
      html += '<span class="opt-letter">' + letter + '</span>';
      html += '<span class="option-math">' + fieldToHtml(text) + '</span>';
      if (isCorrect) {
        html += '<strong class="correct-mark">' + esc(cfg.correctLabel || 'Düzgün') + '</strong>';
      }
      html += '</div>';
    });

    html += '</div></article>';
    return html;
  }

  function renderInto(el, cfg) {
    if (!el) return;
    el.innerHTML = build(cfg);
    if (typeof global.esinaqRenderMath === 'function') global.esinaqRenderMath();
  }

  global.esinaqBuildStudentPreview = build;
  global.esinaqRenderStudentPreview = renderInto;
})(window);
