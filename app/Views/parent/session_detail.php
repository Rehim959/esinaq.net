<div class="page">
    <a href="<?= url('/valideyn/usaq/' . $session['child_id']) ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
    <div class="info-strip">
        <div><strong><?= e($session['first_name'] . ' ' . $session['last_name']) ?></strong> — <?= e($session['title']) ?></div>
        <div><?= e(__('score')) ?>: <?= e((string)$session['score']) ?>/<?= e((string)$session['max_score']) ?> · <?= e((string)$session['percentage']) ?>% · <?= e($session['letter_grade']) ?></div>
    </div>

    <h2><?= e(__('wrong_questions_only')) ?></h2>
    <?php if (empty($wrong)): ?>
        <p class="success-text"><?= e(__('no_wrong_questions')) ?></p>
    <?php else: ?>
        <?php foreach ($wrong as $i => $q): ?>
            <article class="review-item wrong">
                <header>
                    <span class="badge badge-wrong"><?= e(__('wrong_label')) ?></span>
                    <span class="muted"><?= e(locale() === 'ru' ? ($q['subject_name_ru'] ?? $q['subject_name']) : $q['subject_name']) ?></span>
                </header>
                <p class="q-text"><?= e($q['question_text']) ?></p>
                <p><?= e(__('your_answer')) ?>: <strong><?= e($q['selected_option'] ?? '—') ?></strong>
                    <?php
                    $sel = $q['selected_option'];
                    $map = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d'],'E'=>$q['option_e']];
                    if ($sel && isset($map[$sel])) echo ' — ' . e($map[$sel]);
                    ?>
                </p>
                <p><?= e(__('correct_answer')) ?>: <strong class="ok"><?= e($q['correct_option']) ?></strong>
                    — <?= e($map[$q['correct_option']] ?? '') ?>
                </p>
                <?php if (!empty($q['explanation'])): ?>
                    <p class="hint"><?= e($q['explanation']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
