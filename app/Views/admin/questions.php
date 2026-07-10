<?php
/** @var list<array<string,mixed>> $questions */
/** @var list<array<string,mixed>> $subjects */
/** @var array{grade:int,sector:string,subjectId:int,kind?:string} $filters */
/** @var list<int> $grades */
/** @var int $statsTotal */
/** @var string $statsMode */
/** @var list<array<string,mixed>> $statsRows */
/** @var int $listLimit */

$exportQs = [];
if ($filters['grade'] > 0) {
    $exportQs[] = 'grade=' . (int) $filters['grade'];
}
if ($filters['sector'] !== '') {
    $exportQs[] = 'sector=' . rawurlencode($filters['sector']);
}
if ($filters['subjectId'] > 0) {
    $exportQs[] = 'subject_id=' . (int) $filters['subjectId'];
}
if (($filters['kind'] ?? '') !== '') {
    $exportQs[] = 'kind=' . rawurlencode((string) $filters['kind']);
}
$exportBase = implode('&', $exportQs);
$exportUrl = static function (string $format) use ($exportBase): string {
    $q = 'format=' . rawurlencode($format);
    if ($exportBase !== '') {
        $q .= '&' . $exportBase;
    }
    return url('/admin/suallar/export?' . $q);
};
$subjectLabel = static function (array $row): string {
    return locale() === 'ru'
        ? (string) ($row['subject_name_ru'] ?? $row['subject_name'] ?? '')
        : (string) ($row['subject_name'] ?? '');
};
?>
<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/suallar/yeni') ?>"><?= e(__('add_question_math')) ?></a>
        <a class="btn" href="<?= url('/admin/suallar/sekilli') ?>"><?= e(__('add_question_image')) ?></a>
        <a class="btn btn-ghost" href="<?= url('/admin/suallar/elave') ?>"><?= e(__('add_question_paste')) ?></a>
    </div>

    <form class="filter-bar" method="get" action="<?= e(form_get_action()) ?>">
        <?= route_hidden('/admin/suallar') ?>
        <select name="grade">
            <option value="0"><?= e(__('all_grades')) ?></option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= $g ?>" <?= (int)$filters['grade'] === $g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sector">
            <option value=""><?= e(__('all_sectors')) ?></option>
            <option value="az" <?= $filters['sector'] === 'az' ? 'selected' : '' ?>><?= e(__('sector_az_short')) ?></option>
            <option value="ru" <?= $filters['sector'] === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru_short')) ?></option>
        </select>
        <select name="subject_id">
            <option value="0"><?= e(__('all_subjects')) ?></option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$filters['subjectId'] === (int)$s['id'] ? 'selected' : '' ?>><?= e(subject_name($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="kind">
            <option value=""><?= e(__('all_question_kinds')) ?></option>
            <option value="text" <?= ($filters['kind'] ?? '') === 'text' ? 'selected' : '' ?>><?= e(__('kind_text')) ?></option>
            <option value="image" <?= ($filters['kind'] ?? '') === 'image' ? 'selected' : '' ?>><?= e(__('kind_image')) ?></option>
        </select>
        <button class="btn btn-sm" type="submit"><?= e(__('filter')) ?></button>
    </form>

    <div class="question-stats">
        <div class="question-stats-head">
            <strong><?= e(__('stats_total', ['n' => (string) $statsTotal])) ?></strong>
            <div class="export-actions">
                <span class="muted export-label"><?= e(__('export_filtered')) ?>:</span>
                <a class="btn btn-sm btn-ghost" href="<?= e($exportUrl('excel')) ?>"><?= e(__('export_excel')) ?></a>
                <a class="btn btn-sm btn-ghost" href="<?= e($exportUrl('word')) ?>"><?= e(__('export_word')) ?></a>
                <a class="btn btn-sm btn-ghost" href="<?= e($exportUrl('pdf')) ?>" target="_blank" rel="noopener"><?= e(__('export_pdf')) ?></a>
            </div>
        </div>

        <?php if ($statsRows !== []): ?>
            <table class="table stats-table">
                <thead>
                <tr>
                    <?php if ($statsMode === 'by_grade'): ?>
                        <th><?= e(__('grade')) ?></th>
                        <th><?= e(__('sector')) ?></th>
                    <?php elseif ($statsMode === 'by_subject'): ?>
                        <th><?= e(__('subject')) ?></th>
                        <th><?= e(__('sector')) ?></th>
                    <?php else: ?>
                        <th><?= e(__('subject')) ?></th>
                        <th><?= e(__('grade')) ?></th>
                        <th><?= e(__('sector')) ?></th>
                    <?php endif; ?>
                    <th><?= e(__('question_count')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($statsRows as $row): ?>
                    <tr>
                        <?php if ($statsMode === 'by_grade'): ?>
                            <td><?= e(grade_label((int) $row['grade'])) ?></td>
                            <td><?= e(strtoupper((string) $row['sector'])) ?></td>
                        <?php elseif ($statsMode === 'by_subject'): ?>
                            <td><?= e($subjectLabel($row)) ?></td>
                            <td><?= e(strtoupper((string) $row['sector'])) ?></td>
                        <?php else: ?>
                            <td><?= e($subjectLabel($row)) ?></td>
                            <td><?= e(grade_label((int) $row['grade'])) ?></td>
                            <td><?= e(strtoupper((string) $row['sector'])) ?></td>
                        <?php endif; ?>
                        <td><strong><?= (int) $row['cnt'] ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($statsTotal > $listLimit): ?>
        <p class="muted showing-note"><?= e(__('showing_of_total', [
            'shown' => (string) min(count($questions), $listLimit),
            'total' => (string) $statsTotal,
        ])) ?></p>
    <?php endif; ?>

    <?php $canDelete = \App\Core\Auth::canDeleteQuestions(); ?>

    <?php if ($canDelete && !empty($questions)): ?>
    <form method="post" action="<?= e(form_get_action()) ?>" class="bulk-delete-bar" id="bulkDeleteForm"
          data-confirm-tpl="<?= e(__('confirm_delete_questions')) ?>"
          data-none-msg="<?= e(__('err_no_questions_selected')) ?>">
        <?= route_hidden('/admin/suallar/sil-secilen') ?>
        <?= csrf_field() ?>
        <div class="bulk-delete-inner" id="bulkIdsHost">
            <span class="bulk-delete-count" id="bulkSelectedCount"><?= e(__('selected_count', ['n' => '0'])) ?></span>
            <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled><?= e(__('delete_selected')) ?></button>
            <button type="button" class="btn btn-sm btn-ghost" id="bulkClearBtn"><?= e(__('clear_selection')) ?></button>
        </div>
    </form>
    <?php endif; ?>

    <table class="table" id="questionsTable">
        <thead>
        <tr>
            <?php if ($canDelete): ?>
                <th class="col-check">
                    <input type="checkbox" id="selectAllQuestions" title="<?= e(__('select_all')) ?>" aria-label="<?= e(__('select_all')) ?>">
                </th>
            <?php endif; ?>
            <th><?= e(__('id')) ?></th>
            <th><?= e(__('question')) ?></th>
            <th><?= e(__('subject')) ?></th>
            <th><?= e(__('grade')) ?></th>
            <th><?= e(__('sector')) ?></th>
            <th><?= e(__('answer_col')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($questions as $q): ?>
            <?php $isImage = question_is_image($q); ?>
            <tr>
                <?php if ($canDelete): ?>
                    <td class="col-check">
                        <input type="checkbox" class="q-check" value="<?= (int) $q['id'] ?>" aria-label="#<?= (int) $q['id'] ?>">
                    </td>
                <?php endif; ?>
                <td><?= (int)$q['id'] ?></td>
                <td class="q-preview">
                    <?php if ($isImage): ?>
                        <span class="badge badge-image"><?= e(__('kind_image')) ?></span>
                        <?php $thumb = question_img_src((string) $q['question_text']); ?>
                        <?php if ($thumb !== ''): ?>
                            <img src="<?= e($thumb) ?>" alt="" class="q-list-thumb">
                        <?php endif; ?>
                    <?php else: ?>
                        <?= e(mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags((string)$q['question_text']))), 0, 80, '…')) ?>
                    <?php endif; ?>
                </td>
                <td><?= e(locale() === 'ru' ? ($q['subject_name_ru'] ?? $q['subject_name']) : $q['subject_name']) ?></td>
                <td><?= (int)$q['grade'] ?></td>
                <td><?= e(strtoupper($q['sector'])) ?></td>
                <td><strong><?= e($q['correct_option']) ?></strong></td>
                <td class="actions-cell">
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/suallar/bax/' . $q['id']) ?>"><?= e(__('view_details')) ?></a>
                    <a class="btn btn-sm" href="<?= url($isImage ? '/admin/suallar/sekilli/duzelis/' . $q['id'] : '/admin/suallar/duzelis/' . $q['id']) ?>"><?= e(__('edit')) ?></a>
                    <?php if ($canDelete): ?>
                    <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form" data-confirm="<?= e(__('confirm_delete')) ?>">
                        <?= route_hidden('/admin/suallar/sil/' . (int) $q['id']) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(__('delete')) ?></button>
                    </form>
                    <?php else: ?>
                    <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form" data-confirm="<?= e(__('confirm_request_delete_question')) ?>">
                        <?= route_hidden('/admin/suallar/sil-sorgu/' . (int) $q['id']) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-ghost"><?= e(__('request_delete')) ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($questions)): ?>
            <tr><td colspan="<?= $canDelete ? 8 : 7 ?>" class="muted"><?= e(__('no_questions')) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($canDelete && !empty($questions)): ?>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var form = document.getElementById('bulkDeleteForm');
  var selectAll = document.getElementById('selectAllQuestions');
  var checks = Array.prototype.slice.call(document.querySelectorAll('.q-check'));
  var countEl = document.getElementById('bulkSelectedCount');
  var btn = document.getElementById('bulkDeleteBtn');
  var clearBtn = document.getElementById('bulkClearBtn');
  var host = document.getElementById('bulkIdsHost');
  if (!form || !checks.length) return;

  var countTpl = <?= json_encode(__('selected_count', ['n' => '__N__']), JSON_UNESCAPED_UNICODE) ?>;

  function selected() {
    return checks.filter(function (c) { return c.checked; });
  }

  function sync() {
    var sel = selected();
    var n = sel.length;
    if (countEl) countEl.textContent = countTpl.replace('__N__', String(n));
    if (btn) btn.disabled = n === 0;
    if (selectAll) {
      selectAll.checked = n > 0 && n === checks.length;
      selectAll.indeterminate = n > 0 && n < checks.length;
    }
    checks.forEach(function (c) {
      var row = c.closest('tr');
      if (row) row.classList.toggle('is-selected', c.checked);
    });
  }

  checks.forEach(function (c) {
    c.addEventListener('change', sync);
  });

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checks.forEach(function (c) { c.checked = selectAll.checked; });
      sync();
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      checks.forEach(function (c) { c.checked = false; });
      if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
      }
      sync();
    });
  }

  form.addEventListener('submit', function (e) {
    var sel = selected();
    if (!sel.length) {
      e.preventDefault();
      window.alert(form.getAttribute('data-none-msg') || '');
      return;
    }
    var tpl = form.getAttribute('data-confirm-tpl') || '';
    var msg = tpl.replace(':n', String(sel.length));
    if (msg && !window.confirm(msg)) {
      e.preventDefault();
      return;
    }
    form.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
    sel.forEach(function (c) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = c.value;
      form.appendChild(input);
    });
  });

  sync();
})();
</script>
<?php endif; ?>
