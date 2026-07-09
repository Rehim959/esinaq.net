<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/valideyn/usaq-elave') ?>">+ Uşaq əlavə et</a>
    </div>

    <?php if (empty($children)): ?>
        <div class="empty-state">
            <h2>Hələ uşaq əlavə etməmisiniz</h2>
            <p>Övladınızı sistemə əlavə edin — ona unikal imtahan linki e-poçtunuza gələcək.</p>
            <a class="btn" href="<?= url('/valideyn/usaq-elave') ?>">İlk uşağı əlavə et</a>
        </div>
    <?php else: ?>
        <div class="child-list">
            <?php foreach ($children as $c): ?>
                <article class="child-row">
                    <div>
                        <h3><?= e($c['first_name'] . ' ' . $c['last_name']) ?></h3>
                        <p class="muted"><?= e(grade_label((int)$c['grade'])) ?> · <?= e(sector_label($c['sector'])) ?></p>
                        <p class="tiny">Link: <code><?= e(url('/imtahan/' . $c['access_token'])) ?></code></p>
                        <p class="tiny">Şifrə: <code><?= e($c['password_hint']) ?></code></p>
                    </div>
                    <div class="child-meta">
                        <?php $last = $stats[$c['id']][0] ?? null; ?>
                        <?php if ($last): ?>
                            <div class="score-badge"><?= e((string)$last['letter_grade']) ?> · <?= e((string)$last['percentage']) ?>%</div>
                            <div class="tiny muted"><?= e($last['title']) ?></div>
                        <?php else: ?>
                            <div class="muted">Hələ nəticə yoxdur</div>
                        <?php endif; ?>
                        <a class="btn btn-sm" href="<?= url('/valideyn/usaq/' . $c['id']) ?>">Hesabat</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
