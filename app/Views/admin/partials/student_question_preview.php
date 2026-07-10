<?php
/**
 * Student-style question preview (exam look).
 *
 * @var array<string,mixed> $q
 * @var string|null $subjectName
 * @var int|null $index 1-based
 * @var int|null $total
 * @var bool $showCorrect highlight correct option (admin preview)
 * @var string|null $badge optional status badge text
 * @var string|null $badgeClass
 */
$q = $q ?? [];
$subjectName = $subjectName ?? '';
$index = isset($index) ? (int) $index : null;
$total = isset($total) ? (int) $total : null;
$showCorrect = !empty($showCorrect);
$badge = $badge ?? null;
$badgeClass = $badgeClass ?? 'preview-badge-ok';
$fmt = (string) ($q['content_format'] ?? 'plain');
$correct = strtoupper((string) ($q['correct_option'] ?? ''));

$opts = [
    'A' => $q['option_a'] ?? '',
    'B' => $q['option_b'] ?? '',
    'C' => $q['option_c'] ?? '',
    'D' => $q['option_d'] ?? '',
];
if (!empty($q['option_e'])) {
    $opts['E'] = $q['option_e'];
}
?>
<article class="question-box student-preview-box<?= $badge ? ' has-preview-badge' : '' ?>">
    <?php if ($badge): ?>
        <div class="preview-status-badge <?= e($badgeClass) ?>"><?= e($badge) ?></div>
    <?php endif; ?>
    <div class="q-meta">
        <span>
            <?php if ($index !== null && $total !== null): ?>
                <?= e(__('question_n_of', ['n' => (string) $index, 'total' => (string) $total])) ?>
            <?php else: ?>
                <?= e(__('math_student_preview')) ?>
            <?php endif; ?>
        </span>
        <?php if ($subjectName !== ''): ?>
            <span class="subject-tag"><?= e($subjectName) ?></span>
        <?php endif; ?>
    </div>
    <h2 class="q-title q-content"><?= render_question((string) ($q['question_text'] ?? ''), $fmt) ?></h2>
    <div class="options">
        <?php foreach ($opts as $letter => $text): ?>
            <?php
            $isCorrect = $showCorrect && $correct === $letter;
            $classes = 'option preview-option' . ($isCorrect ? ' selected' : '');
            ?>
            <div class="<?= e($classes) ?>" data-opt="<?= e($letter) ?>">
                <span class="opt-letter"><?= e($letter) ?></span>
                <span class="option-math"><?= render_question((string) $text, $fmt) ?></span>
                <?php if ($isCorrect): ?>
                    <strong class="correct-mark"><?= e(__('correct_label')) ?></strong>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</article>
