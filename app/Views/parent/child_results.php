<div class="page">
    <div class="page-actions">
        <a href="<?= url('/valideyn') ?>" class="btn btn-ghost btn-sm">← Geri</a>
        <a class="btn btn-sm" href="<?= e($examLink) ?>" target="_blank">İmtahan linki</a>
    </div>

    <div class="info-strip">
        <div><strong><?= e($child['first_name'] . ' ' . $child['last_name']) ?></strong></div>
        <div><?= e(grade_label((int)$child['grade'])) ?> · <?= e(sector_label($child['sector'])) ?></div>
        <div class="tiny">Şifrə: <code><?= e($child['password_hint']) ?></code></div>
    </div>

    <?php if (!empty($weakSubjects)): ?>
        <h2>Fənn üzrə performans</h2>
        <div class="bar-list">
            <?php foreach ($weakSubjects as $w): ?>
                <div class="bar-row">
                    <span><?= e($w['name_az']) ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= min(100, (float)$w['avg_pct']) ?>%"></div></div>
                    <strong><?= number_format((float)$w['avg_pct'], 0) ?>%</strong>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($monthly)): ?>
        <h2>Aylar üzrə orta bal</h2>
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

    <h2>İmtahan tarixçəsi</h2>
    <?php if (empty($sessions)): ?>
        <p class="muted">Hələ bitmiş imtahan yoxdur.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr><th>İmtahan</th><th>Tarix</th><th>Bal</th><th>Hərf</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?= e($s['title']) ?></td>
                    <td><?= e(format_date($s['finished_at'])) ?></td>
                    <td><?= e((string)$s['score']) ?>/<?= e((string)$s['max_score']) ?> (<?= e((string)$s['percentage']) ?>%)</td>
                    <td><span class="letter letter-<?= e($s['letter_grade']) ?>"><?= e($s['letter_grade']) ?></span></td>
                    <td><a href="<?= url('/valideyn/netice/' . $s['id']) ?>">Səhv suallar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
