/**
 * Auto-render KaTeX in .math-content / .q-content elements.
 */
(function () {
  function run() {
    if (typeof renderMathInElement !== 'function') return;
    var nodes = document.querySelectorAll('.math-content, .q-content, .q-title, .q-text, .option-math');
    nodes.forEach(function (el) {
      try {
        renderMathInElement(el, {
          delimiters: [
            { left: '\\[', right: '\\]', display: true },
            { left: '\\(', right: '\\)', display: false }
          ],
          throwOnError: false
        });
      } catch (e) {}
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
  window.esinaqRenderMath = run;
})();
