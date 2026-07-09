(function () {
  var pick = document.getElementById('subjectPick');
  var totalEl = document.getElementById('totalQuestions');
  if (!pick || !totalEl) return;

  function recalc() {
    var total = 0;
    pick.querySelectorAll('.pick-row').forEach(function (row) {
      var check = row.querySelector('.subj-check');
      var count = row.querySelector('.subj-count');
      if (!check || !count) return;
      count.disabled = !check.checked;
      if (check.checked) {
        total += Math.max(1, parseInt(count.value, 10) || 0);
      }
    });
    totalEl.textContent = String(total);
  }

  pick.addEventListener('change', recalc);
  pick.addEventListener('input', recalc);
  recalc();
})();
