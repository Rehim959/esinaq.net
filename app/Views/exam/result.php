<div class="result-page container">
    <div class="result-hero">
        <div class="exam-brand">eSınaq</div>
        <h1><?= e($child['first_name']) ?>, nəticən hazırdır!</h1>
        <div class="result-score">
            <div class="big-letter letter-<?= e($session['letter_grade']) ?>"><?= e($session['letter_grade']) ?></div>
            <div>
                <div class="pct"><?= e((string)$session['percentage']) ?>%</div>
                <div class="muted"><?= e((string)$session['score']) ?> / <?= e((string)$session['max_score']) ?> düzgün</div>
                <p class="motivate"><?= e($message) ?></p>
            </div>
        </div>
        <div class="result-stats">
            <span class="ok">✓ <?= (int)$correct ?> düzgün</span>
            <span class="bad">✗ <?= (int)$wrong ?> səhv</span>
            <span class="muted">○ <?= (int)$blank ?> boş</span>
        </div>
        <a class="btn btn-ghost" href="<?= url('/imtahan/' . $token . '/siyahi') ?>">İmtahan siyahısına qayıt</a>
    </div>

    <h2>Hər sual üzrə nəticə</h2>
    <?php foreach ($details as $i => $q): ?>
        <?php
        $status = $q['selected_option'] === null ? 'blank' : ((int)$q['is_correct'] === 1 ? 'correct' : 'wrong');
        $map = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d'],'E'=>$q['option_e']];
        ?>
        <article class="review-item <?= $status ?>">
            <header>
                <?php if ($status === 'correct'): ?>
                    <span class="badge badge-ok">DÜZGÜN ✓</span>
                <?php elseif ($status === 'wrong'): ?>
                    <span class="badge badge-wrong">SƏHV ✗</span>
                <?php else: ?>
                    <span class="badge">Cavablanmayıb</span>
                <?php endif; ?>
                <span class="muted">Sual <?= $i + 1 ?> · <?= e($q['subject_name']) ?></span>
            </header>
            <p class="q-text"><?= e($q['question_text']) ?></p>
            <?php if ($q['selected_option']): ?>
                <p>Sizin cavab: <strong><?= e($q['selected_option']) ?></strong> — <?= e($map[$q['selected_option']] ?? '') ?></p>
            <?php endif; ?>
            <?php if ($status !== 'correct'): ?>
                <p>Düzgün cavab: <strong class="ok"><?= e($q['correct_option']) ?></strong> — <?= e($map[$q['correct_option']] ?? '') ?></p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
