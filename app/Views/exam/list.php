<div class="exam-list-page container">
    <header class="exam-list-head">
        <div>
            <div class="exam-brand"><?= brand_html('nav') ?></div>
            <h1><?= e(__('hello_name_comma', ['name' => $child['first_name']])) ?></h1>
            <p class="muted"><?= e(grade_label((int)$child['grade'])) ?> · <?= e(sector_label($child['sector'])) ?></p>
        </div>
    </header>

    <?php if (empty($exams)): ?>
        <div class="empty-state">
            <h2><?= e(__('no_active_exams_title')) ?></h2>
            <p><?= e(__('no_active_exams_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="exam-cards">
            <?php foreach ($exams as $ex): ?>
                <article class="exam-card">
                    <h3><?= e($ex['title']) ?></h3>
                    <p class="muted"><?= (int)$ex['duration_minutes'] ?> <?= e(__('minutes')) ?></p>
                    <?php if (in_array($ex['my_status'], ['submitted', 'timed_out'], true)): ?>
                        <div class="score-badge"><?= e($ex['my_letter']) ?> · <?= e((string)$ex['my_percentage']) ?>%</div>
                        <a class="btn btn-sm" href="<?= url('/imtahan/' . $token . '/netice/' . $ex['my_session_id']) ?>"><?= e(__('view_result')) ?></a>
                    <?php elseif ($ex['my_status'] === 'in_progress'): ?>
                        <a class="btn" href="<?= url('/imtahan/' . $token . '/kec/' . $ex['my_session_id']) ?>"><?= e(__('continue')) ?></a>
                    <?php else: ?>
                        <form method="post" action="<?= url('/imtahan/' . $token . '/basla/' . $ex['id']) ?>">
                            <?= csrf_field() ?>
                            <button class="btn" type="submit"><?= e(__('start_exam')) ?></button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
