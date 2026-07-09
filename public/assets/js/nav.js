(function () {
  var btn = document.getElementById('navToggle');
  var nav = document.getElementById('siteNav');
  if (!btn || !nav) return;
  btn.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.classList.toggle('open', open);
  });
})();
