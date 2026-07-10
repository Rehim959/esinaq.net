<?php
/** @var array<string,mixed> $draft */
$items = is_array($draft['items'] ?? null) ? $draft['items'] : [];
$total = count($items);
$willSave = (int) ($draft['will_save'] ?? 0);
$subjectName = (string) ($draft['subject_name'] ?? '');
$token = (string) ($draft['token'] ?? '');
?>
<div class="page import-preview-page">
    <div class="import-preview-toolbar">
        <div>
            <strong><?= e(__('import_preview_title')) ?></strong>
            <p class="muted"><?= e(__('import_preview_help', [
                'total' => (string) $total,
                'save' => (string) $willSave,
                'skip' => (string) max(0, $total - $willSave),
            ])) ?></p>
            <p class="muted tiny">
                <?= e(__('subject')) ?>: <?= e($subjectName) ?>
                · <?= e(grade_label((int) ($draft['grade'] ?? 0))) ?>
                · <?= e(strtoupper((string) ($draft['sector'] ?? ''))) ?>
            </p>
        </div>
        <div class="import-preview-actions">
            <form method="post" action="<?= url('/admin/suallar/elave/geri') ?>" class="inline-form">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit"><?= e(__('import_preview_back')) ?></button>
            </form>
            <?php if ($willSave > 0): ?>
                <form method="post" action="<?= url('/admin/suallar/elave/tesdiq') ?>" class="inline-form"
                      data-confirm="<?= e(__('confirm_import_save', ['n' => (string) $willSave])) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="draft_token" value="<?= e($token) ?>">
                    <button class="btn" type="submit"><?= e(__('import_preview_save', ['n' => (string) $willSave])) ?></button>
                </form>
            <?php else: ?>
                <button class="btn" type="button" disabled><?= e(__('import_preview_none')) ?></button>
            <?php endif; ?>
        </div>
    </div>

    <div class="import-preview-shell" id="importPreviewShell" data-total="<?= (int) $total ?>">
        <aside class="import-preview-map">
            <h3><?= e(__('import_preview_map')) ?></h3>
            <div class="q-map import-q-map" id="importQMap">
                <?php foreach ($items as $i => $item): ?>
                    <?php
                    $ok = ($item['status'] ?? '') === 'ok';
                    $cls = $ok ? 'q-dot' : 'q-dot q-dot-skip';
                    ?>
                    <button type="button" class="<?= e($cls) ?><?= $i === 0 ? ' current' : '' ?>"
                            data-index="<?= (int) $i ?>"
                            title="#<?= (int) ($item['n'] ?? $i + 1) ?>">
                        <?= (int) ($item['n'] ?? $i + 1) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="muted tiny"><?= e(__('import_preview_map_hint')) ?></p>
        </aside>

        <section class="import-preview-main">
            <?php foreach ($items as $i => $item): ?>
                <?php
                $ok = ($item['status'] ?? '') === 'ok';
                $badge = $ok ? __('import_preview_will_save') : __('import_preview_will_skip');
                $badgeClass = $ok ? 'preview-badge-ok' : 'preview-badge-skip';
                ?>
                <div class="import-preview-slide<?= $i === 0 ? ' is-active' : '' ?>" data-index="<?= (int) $i ?>" <?= $i === 0 ? '' : 'hidden' ?>>
                    <?php
                    $q = $item;
                    $index = (int) ($item['n'] ?? $i + 1);
                    $showCorrect = true;
                    require __DIR__ . '/partials/student_question_preview.php';
                    ?>
                    <?php if (!$ok && !empty($item['reason'])): ?>
                        <p class="import-skip-reason muted"><?= e((string) $item['reason']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="exam-nav import-preview-nav">
                <button type="button" class="btn btn-ghost" id="importPrevBtn" disabled>← <?= e(__('previous')) ?></button>
                <span class="muted" id="importPosLabel">1 / <?= (int) $total ?></span>
                <button type="button" class="btn" id="importNextBtn" <?= $total <= 1 ? 'disabled' : '' ?>><?= e(__('next')) ?> →</button>
            </div>
        </section>
    </div>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var shell = document.getElementById('importPreviewShell');
  if (!shell) return;
  var total = parseInt(shell.getAttribute('data-total') || '0', 10) || 0;
  var idx = 0;
  var slides = Array.prototype.slice.call(shell.querySelectorAll('.import-preview-slide'));
  var dots = Array.prototype.slice.call(document.querySelectorAll('#importQMap .q-dot'));
  var prevBtn = document.getElementById('importPrevBtn');
  var nextBtn = document.getElementById('importNextBtn');
  var pos = document.getElementById('importPosLabel');

  function show(i) {
    if (i < 0 || i >= total) return;
    idx = i;
    slides.forEach(function (el, n) {
      var on = n === idx;
      el.hidden = !on;
      el.classList.toggle('is-active', on);
    });
    dots.forEach(function (el, n) {
      el.classList.toggle('current', n === idx);
    });
    if (prevBtn) prevBtn.disabled = idx <= 0;
    if (nextBtn) nextBtn.disabled = idx >= total - 1;
    if (pos) pos.textContent = (idx + 1) + ' / ' + total;
    if (typeof window.esinaqRenderMath === 'function') window.esinaqRenderMath();
  }

  if (prevBtn) prevBtn.addEventListener('click', function () { show(idx - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { show(idx + 1); });
  dots.forEach(function (el) {
    el.addEventListener('click', function () {
      show(parseInt(el.getAttribute('data-index') || '0', 10) || 0);
    });
  });
  show(0);
})();
</script>
