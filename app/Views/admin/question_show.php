<?php
/** @var array<string,mixed> $q */
/** @var list<array<string,mixed>> $subjects */
/** @var list<int> $grades */
$subj = locale() === 'ru' ? ($q['subject_name_ru'] ?? $q['subject_name']) : $q['subject_name'];
$opts = [
    'A' => $q['option_a'],
    'B' => $q['option_b'],
    'C' => $q['option_c'],
    'D' => $q['option_d'],
];
if (!empty($q['option_e'])) {
    $opts['E'] = $q['option_e'];
}
$subjects = $subjects ?? [];
$grades = $grades ?? grades_list();
?>
<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/suallar') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
        <a href="<?= url(question_is_image($q) ? '/admin/suallar/sekilli/duzelis/' . $q['id'] : '/admin/suallar/duzelis/' . $q['id']) ?>" class="btn btn-sm"><?= e(__('edit')) ?></a>
    </div>

    <div class="info-strip">
        <div><strong>#<?= (int)$q['id'] ?></strong></div>
        <div><?= e(__('subject')) ?>: <?= e($subj) ?></div>
        <div><?= e(__('grade')) ?>: <?= e(grade_label((int)$q['grade'])) ?></div>
        <div><?= e(__('sector')) ?>: <?= e(sector_label($q['sector'])) ?></div>
        <div><?= e(__('answer_col')) ?>: <strong><?= e($q['correct_option']) ?></strong></div>
    </div>

    <section class="form-card question-move-card">
        <h2 class="question-move-title"><?= e(__('move_question_title')) ?></h2>
        <p class="muted question-move-help"><?= e(__('move_question_help')) ?></p>
        <form method="post" action="<?= e(form_get_action()) ?>" class="question-move-form">
            <?= route_hidden('/admin/suallar/yer/' . (int) $q['id']) ?>
            <?= csrf_field() ?>
            <div class="grid-3">
                <label><?= e(__('subject')) ?>
                    <select name="subject_id" required>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (int) $q['subject_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e(subject_name($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e(__('grade')) ?>
                    <select name="grade" required>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g ?>" <?= (int) $q['grade'] === $g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e(__('sector')) ?>
                    <select name="sector" required>
                        <option value="az" <?= $q['sector'] === 'az' ? 'selected' : '' ?>><?= e(__('sector_az_short')) ?></option>
                        <option value="ru" <?= $q['sector'] === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru_short')) ?></option>
                    </select>
                </label>
            </div>
            <button class="btn" type="submit"><?= e(__('move_question_btn')) ?></button>
        </form>
    </section>

    <article class="form-card question-detail-card">
        <h2 class="q-title q-content"><?= render_question($q['question_text'], $q['content_format'] ?? 'plain') ?></h2>
        <ul class="option-review-list">
            <?php
            $fmt = $q['content_format'] ?? 'plain';
            foreach ($opts as $letter => $text): ?>
                <li class="<?= $q['correct_option'] === $letter ? 'is-correct' : '' ?>">
                    <span class="opt-letter"><?= e($letter) ?></span>
                    <span class="option-math"><?= render_question($text, $fmt) ?></span>
                    <?php if ($q['correct_option'] === $letter): ?>
                        <strong class="correct-mark"><?= e(__('correct_label')) ?></strong>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
