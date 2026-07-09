<div class="exam-list-page container">
    <header class="exam-list-head">
        <div>
            <div class="exam-brand">eSınaq</div>
            <h1><?= e($child['first_name']) ?>, salam!</h1>
            <p class="muted"><?= e(grade_label((int)$child['grade'])) ?> · <?= e(sector_label($child['sector'])) ?></p>
        </div>
    </header>

    <?php if (empty($exams)): ?>
        <div class="empty-state">
            <h2>Hazırda aktiv imtahan yoxdur</h2>
            <p>Müəllim imtahanı başladanda burada görünəcək.</p>
        </div>
    <?php else: ?>
        <div class="exam-cards">
            <?php foreach ($exams as $ex): ?>
                <article class="exam-card">
                    <h3><?= e($ex['title']) ?></h3>
                    <p class="muted"><?= (int)$ex['duration_minutes'] ?> dəqiqə</p>
                    <?php if (in_array($ex['my_status'], ['submitted', 'timed_out'], true)): ?>
                        <div class="score-badge"><?= e($ex['my_letter']) ?> · <?= e((string)$ex['my_percentage']) ?>%</div>
                        <a class="btn btn-sm" href="<?= url('/imtahan/' . $token . '/netice/' . $ex['my_session_id']) ?>">Nəticəyə bax</a>
                    <?php elseif ($ex['my_status'] === 'in_progress'): ?>
                        <a class="btn" href="<?= url('/imtahan/' . $token . '/kec/' . $ex['my_session_id']) ?>">Davam et</a>
                    <?php else: ?>
                        <form method="post" action="<?= url('/imtahan/' . $token . '/basla/' . $ex['id']) ?>">
                            <?= csrf_field() ?>
                            <button class="btn" type="submit">İmtahana başla</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
