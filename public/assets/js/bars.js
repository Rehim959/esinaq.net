(function () {
  document.querySelectorAll('.bar-fill[data-pct]').forEach(function (el) {
    var pct = parseFloat(el.getAttribute('data-pct') || '0');
    if (isNaN(pct)) pct = 0;
    el.style.width = Math.max(0, Math.min(100, pct)) + '%';
  });
})();
