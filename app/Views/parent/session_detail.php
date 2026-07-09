<div class="page">
    <a href="<?= url('/valideyn/usaq/' . $session['child_id']) ?>" class="btn btn-ghost btn-sm">← Geri</a>
    <div class="info-strip">
        <div><strong><?= e($session['first_name'] . ' ' . $session['last_name']) ?></strong> — <?= e($session['title']) ?></div>
        <div>Bal: <?= e((string)$session['score']) ?>/<?= e((string)$session['max_score']) ?> · <?= e((string)$session['percentage']) ?>% · <?= e($session['letter_grade']) ?></div>
    </div>

    <h2>Yalnız səhv suallar</h2>
    <?php if (empty($wrong)): ?>
        <p class="success-text">Əla! Heç bir səhv sual yoxdur.</p>
    <?php else: ?>
        <?php foreach ($wrong as $i => $q): ?>
            <article class="review-item wrong">
                <header>
                    <span class="badge badge-wrong">SƏHV</span>
                    <span class="muted"><?= e($q['subject_name']) ?></span>
                </header>
                <p class="q-text"><?= e($q['question_text']) ?></p>
                <p>Sizin cavab: <strong><?= e($q['selected_option'] ?? '—') ?></strong>
                    <?php
                    $sel = $q['selected_option'];
                    $map = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d'],'E'=>$q['option_e']];
                    if ($sel && isset($map[$sel])) echo ' — ' . e($map[$sel]);
                    ?>
                </p>
                <p>Düzgün cavab: <strong class="ok"><?= e($q['correct_option']) ?></strong>
                    — <?= e($map[$q['correct_option']] ?? '') ?>
                </p>
                <?php if (!empty($q['explanation'])): ?>
                    <p class="hint"><?= e($q['explanation']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
