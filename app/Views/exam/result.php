<div class="result-page container">
    <div class="result-hero">
        <div class="exam-brand"><span class="brand-e">e</span>Sınaq</div>
        <h1><?= e(__('result_ready', ['name' => $child['first_name']])) ?></h1>
        <div class="result-score">
            <div class="big-letter letter-<?= e($session['letter_grade']) ?>"><?= e($session['letter_grade']) ?></div>
            <div>
                <div class="pct"><?= e((string)$session['percentage']) ?>%</div>
                <div class="grade-band"><?= e($band ?? '') ?></div>
                <div class="muted"><?= e(__('of_correct', ['score' => (string)$session['score'], 'max' => (string)$session['max_score']])) ?></div>
                <p class="motivate"><?= e($message) ?></p>
            </div>
        </div>
        <?php if (!empty($revealAnswers)): ?>
            <div class="result-stats">
                <span class="ok">✓ <?= e(__('correct_count', ['n' => (string)$correct])) ?></span>
                <span class="bad">✗ <?= e(__('wrong_count', ['n' => (string)$wrong])) ?></span>
                <span class="muted">○ <?= e(__('blank_count', ['n' => (string)$blank])) ?></span>
            </div>
        <?php else: ?>
            <div class="result-locked">
                <strong><?= e(__('answers_locked_title')) ?></strong>
                <p><?= e(__('review_after_finish')) ?></p>
            </div>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= url('/imtahan/' . $token . '/siyahi') ?>"><?= e(__('back_to_exam_list')) ?></a>
    </div>

    <?php if (!empty($revealAnswers)): ?>
        <h2><?= e(__('per_question_result')) ?></h2>
        <?php foreach ($details as $i => $q): ?>
            <?php
            $status = $q['selected_option'] === null ? 'blank' : ((int)$q['is_correct'] === 1 ? 'correct' : 'wrong');
            $map = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d'],'E'=>$q['option_e']];
            $subjName = locale() === 'ru' ? ($q['subject_name_ru'] ?? $q['subject_name']) : $q['subject_name'];
            ?>
            <article class="review-item <?= $status ?>">
                <header>
                    <?php if ($status === 'correct'): ?>
                        <span class="badge badge-ok"><?= e(__('correct_label')) ?> ✓</span>
                    <?php elseif ($status === 'wrong'): ?>
                        <span class="badge badge-wrong"><?= e(__('wrong_label')) ?> ✗</span>
                    <?php else: ?>
                        <span class="badge"><?= e(__('unanswered')) ?></span>
                    <?php endif; ?>
                    <span class="muted"><?= e(__('question')) ?> <?= $i + 1 ?> · <?= e($subjName) ?></span>
                </header>
                <p class="q-text"><?= e($q['question_text']) ?></p>
                <?php if ($q['selected_option']): ?>
                    <p><?= e(__('your_answer')) ?>: <strong><?= e($q['selected_option']) ?></strong> — <?= e($map[$q['selected_option']] ?? '') ?></p>
                <?php endif; ?>
                <?php if ($status !== 'correct'): ?>
                    <p><?= e(__('correct_answer')) ?>: <strong class="ok"><?= e($q['correct_option']) ?></strong> — <?= e($map[$q['correct_option']] ?? '') ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <h2><?= e(__('your_selections')) ?></h2>
        <?php foreach ($details as $i => $q): ?>
            <?php
            $map = ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d'],'E'=>$q['option_e']];
            $subjName = locale() === 'ru' ? ($q['subject_name_ru'] ?? $q['subject_name']) : $q['subject_name'];
            ?>
            <article class="review-item blank">
                <header>
                    <span class="muted"><?= e(__('question')) ?> <?= $i + 1 ?> · <?= e($subjName) ?></span>
                </header>
                <p class="q-text"><?= e($q['question_text']) ?></p>
                <?php if ($q['selected_option']): ?>
                    <p><?= e(__('your_answer')) ?>: <strong><?= e($q['selected_option']) ?></strong> — <?= e($map[$q['selected_option']] ?? '') ?></p>
                <?php else: ?>
                    <p class="muted"><?= e(__('unanswered')) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
