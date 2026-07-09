<div class="page">
    <div class="page-actions">
        <a href="<?= url('/valideyn') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
        <a class="btn btn-sm" href="<?= e($examLink) ?>" target="_blank"><?= e(__('exam_link')) ?></a>
    </div>

    <div class="info-strip">
        <div><strong><?= e($child['first_name'] . ' ' . $child['last_name']) ?></strong></div>
        <div><?= e(grade_label((int)$child['grade'])) ?> · <?= e(sector_label($child['sector'])) ?></div>
        <div class="tiny"><?= e(__('password')) ?>: <code><?= e(child_password_display($child['password_hint'] ?? null, $child['first_name'] ?? null, $child['birth_date'] ?? null)) ?></code></div>
    </div>

    <?php if (!empty($weakSubjects)): ?>
        <h2><?= e(__('performance_by_subject')) ?></h2>
        <div class="bar-list">
            <?php foreach ($weakSubjects as $w): ?>
                <div class="bar-row">
                    <span><?= e(locale() === 'ru' ? ($w['name_ru'] ?? $w['name_az']) : $w['name_az']) ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= min(100, (float)$w['avg_pct']) ?>%"></div></div>
                    <strong><?= number_format((float)$w['avg_pct'], 0) ?>%</strong>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($monthly)): ?>
        <h2><?= e(__('monthly_avg')) ?></h2>
        <div class="bar-list">
            <?php foreach ($monthly as $m): ?>
                <div class="bar-row">
                    <span><?= e($m['ym']) ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= min(100, (float)$m['avg_pct']) ?>%"></div></div>
                    <strong><?= number_format((float)$m['avg_pct'], 0) ?>%</strong>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2><?= e(__('exam_history')) ?></h2>
    <?php if (empty($sessions)): ?>
        <p class="muted"><?= e(__('no_finished_exams')) ?></p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr><th><?= e(__('exam')) ?></th><th><?= e(__('date')) ?></th><th><?= e(__('score')) ?></th><th><?= e(__('letter')) ?></th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?= e($s['title']) ?></td>
                    <td><?= e(format_date($s['finished_at'])) ?></td>
                    <td><?= e((string)$s['score']) ?>/<?= e((string)$s['max_score']) ?> (<?= e((string)$s['percentage']) ?>%)</td>
                    <td><span class="letter letter-<?= e($s['letter_grade']) ?>"><?= e($s['letter_grade']) ?></span></td>
                    <td><a href="<?= url('/valideyn/netice/' . $s['id']) ?>"><?= e(__('wrong_questions')) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
