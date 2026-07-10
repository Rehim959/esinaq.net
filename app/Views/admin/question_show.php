<?php
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
?>
<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/suallar') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
        <a href="<?= url('/admin/suallar/duzelis/' . $q['id']) ?>" class="btn btn-sm"><?= e(__('edit')) ?></a>
    </div>

    <div class="info-strip">
        <div><strong>#<?= (int)$q['id'] ?></strong></div>
        <div><?= e(__('subject')) ?>: <?= e($subj) ?></div>
        <div><?= e(__('grade')) ?>: <?= e(grade_label((int)$q['grade'])) ?></div>
        <div><?= e(__('sector')) ?>: <?= e(sector_label($q['sector'])) ?></div>
        <div><?= e(__('answer_col')) ?>: <strong><?= e($q['correct_option']) ?></strong></div>
    </div>

    <article class="form-card question-detail-card">
        <h2 class="q-title"><?= e($q['question_text']) ?></h2>
        <ul class="option-review-list">
            <?php foreach ($opts as $letter => $text): ?>
                <li class="<?= $q['correct_option'] === $letter ? 'is-correct' : '' ?>">
                    <span class="opt-letter"><?= e($letter) ?></span>
                    <span><?= e($text) ?></span>
                    <?php if ($q['correct_option'] === $letter): ?>
                        <strong class="correct-mark"><?= e(__('correct_label')) ?></strong>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
