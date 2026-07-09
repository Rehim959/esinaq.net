<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/valideyn/usaq-elave') ?>"><?= e(__('add_child_plus')) ?></a>
    </div>

    <?php if (empty($children)): ?>
        <div class="empty-state">
            <h2><?= e(__('no_children_title')) ?></h2>
            <p><?= e(__('no_children_text')) ?></p>
            <a class="btn" href="<?= url('/valideyn/usaq-elave') ?>"><?= e(__('add_first_child')) ?></a>
        </div>
    <?php else: ?>
        <div class="child-list">
            <?php foreach ($children as $c): ?>
                <article class="child-row">
                    <div>
                        <h3><?= e($c['first_name'] . ' ' . $c['last_name']) ?></h3>
                        <p class="muted"><?= e(grade_label((int)$c['grade'])) ?> · <?= e(sector_label($c['sector'])) ?></p>
                        <p class="tiny"><?= e(__('link')) ?>: <code><?= e(url('/imtahan/' . $c['access_token'])) ?></code></p>
                        <p class="tiny"><?= e(__('password')) ?>: <code><?= e($c['password_hint']) ?></code></p>
                    </div>
                    <div class="child-meta">
                        <?php $last = $stats[$c['id']][0] ?? null; ?>
                        <?php if ($last): ?>
                            <div class="score-badge"><?= e((string)$last['letter_grade']) ?> · <?= e((string)$last['percentage']) ?>%</div>
                            <div class="tiny muted"><?= e($last['title']) ?></div>
                        <?php else: ?>
                            <div class="muted"><?= e(__('no_results_yet')) ?></div>
                        <?php endif; ?>
                        <a class="btn btn-sm" href="<?= url('/valideyn/usaq/' . $c['id']) ?>"><?= e(__('report')) ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
