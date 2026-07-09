(function () {
  var cfgEl = document.getElementById('examTakeConfig');
  var shell = document.querySelector('.exam-shell');
  if (!cfgEl || !shell) return;

  var cfg;
  try {
    cfg = JSON.parse(cfgEl.textContent || '{}');
  } catch (e) {
    return;
  }

  var endsAt = parseInt(shell.dataset.ends, 10) * 1000;
  var csrf = cfg.csrf || '';
  var answerUrl = cfg.answerUrl || '';
  var qUrls = cfg.qUrls || [];
  var subjectByIndex = cfg.subjectByIndex || {};
  var minLabel = cfg.minLabel || 'min';
  var warnTpl = cfg.warnTpl || '__N__';
  var submitConfirm = cfg.submitConfirm || '';
  var subjectsLeftConfirm = cfg.subjectsLeftConfirm || '';
  var answered = cfg.answered || {};
  var submitted = false;

  function pad(n) { return String(n).padStart(2, '0'); }

  function unfinishedSubjects() {
    var left = {};
    Object.keys(subjectByIndex).forEach(function (i) {
      var idx = parseInt(i, 10);
      var dot = document.querySelector('.q-dot[data-qi="' + idx + '"]');
      if (!dot || !dot.classList.contains('done')) {
        left[subjectByIndex[idx]] = true;
      }
    });
    return Object.keys(left).filter(Boolean);
  }

  function refreshUnfinishedHint() {
    var list = unfinishedSubjects();
    var hint = document.getElementById('unfinishedHint');
    var names = document.getElementById('unfinishedNames');
    if (!hint || !names) return;
    if (list.length === 0) {
      hint.hidden = true;
      names.textContent = '';
    } else {
      hint.hidden = false;
      names.textContent = list.join(', ');
    }
  }

  function confirmSubmit() {
    var left = unfinishedSubjects();
    if (left.length > 0) {
      return window.confirm(subjectsLeftConfirm.replace('__LIST__', left.join(', ')));
    }
    return window.confirm(submitConfirm);
  }

  function autoSubmit() {
    if (submitted) return;
    submitted = true;
    var form = document.getElementById('autoSubmitForm');
    if (form) form.submit();
  }

  function tick() {
    var left = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
    var h = Math.floor(left / 3600);
    var m = Math.floor((left % 3600) / 60);
    var s = left % 60;
    var el = document.getElementById('timer');
    var side = document.getElementById('timerSide');
    var alertBox = document.getElementById('timeAlert');
    var alertText = document.getElementById('timeAlertText');

    if (el) {
      el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
      el.classList.toggle('timer-danger', left > 0 && left <= 900);
    }
    if (side) {
      side.textContent = Math.ceil(left / 60) + ' ' + minLabel;
      side.classList.toggle('timer-danger', left > 0 && left <= 900);
    }
    if (alertBox) {
      if (left > 0 && left <= 900) {
        alertBox.hidden = false;
        if (alertText) {
          alertText.textContent = warnTpl.replace('__N__', String(Math.max(1, Math.ceil(left / 60))));
        }
      } else {
        alertBox.hidden = true;
      }
    }
    if (left <= 0) {
      autoSubmit();
    }
  }
  tick();
  setInterval(tick, 1000);

  document.querySelectorAll('.js-submit-exam').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (submitted) return;
      if (!confirmSubmit()) {
        e.preventDefault();
        return;
      }
      submitted = true;
    });
  });

  document.querySelectorAll('.option').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var box = document.getElementById('questionBox');
      if (!box) return;
      var qid = box.dataset.qid;
      var opt = btn.dataset.opt;
      document.querySelectorAll('.option').forEach(function (b) { b.classList.remove('selected'); });
      btn.classList.add('selected');

      var fd = new FormData();
      fd.append('_csrf', csrf);
      fd.append('question_id', qid);
      fd.append('option', opt);

      fetch(answerUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (res) { return res.json().catch(function () { return {}; }); })
        .then(function (data) {
          if (data && data.error === 'timeout' && data.redirect) {
            window.location.href = data.redirect;
          }
        })
        .catch(function () {});

      var idx = parseInt(box.dataset.index, 10);
      answered[qid] = opt;
      var list = document.getElementById('answeredList');
      if (list) {
        var li = list.querySelector('[data-qi="' + idx + '"]');
        if (!li) {
          li = document.createElement('li');
          li.dataset.qi = String(idx);
          list.appendChild(li);
        }
        var href = qUrls[idx] || ('?q=' + idx);
        li.innerHTML = '<a href="' + href + '">' + (idx + 1) + '. ' + opt + '</a>';
      }
      var dot = document.querySelector('.q-dot[data-qi="' + idx + '"]');
      if (dot) dot.classList.add('done');
      refreshUnfinishedHint();
    });
  });
})();
