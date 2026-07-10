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
                <?php
                $examLink = url('/imtahan/' . $c['access_token']);
                $waText = rawurlencode(__('share_exam_wa_text', [
                    'name' => person_full_name($c),
                    'link' => $examLink,
                    'password' => child_password_display($c['password_hint'] ?? null, $c['first_name'] ?? null, $c['birth_date'] ?? null),
                ]));
                ?>
                <article class="child-row">
                    <div>
                        <h3><?= e(person_full_name($c)) ?></h3>
                        <p class="muted"><?= e(grade_label((int)$c['grade'])) ?> · <?= e(sector_label($c['sector'])) ?></p>
                        <p class="tiny"><?= e(__('password')) ?>: <code><?= e(child_password_display($c['password_hint'] ?? null, $c['first_name'] ?? null, $c['birth_date'] ?? null)) ?></code></p>
                        <div class="exam-link-box">
                            <a class="btn btn-sm" href="<?= e($examLink) ?>" target="_blank" rel="noopener"><?= e(__('open_exam_panel')) ?></a>
                            <a class="btn btn-sm btn-ghost" href="https://wa.me/?text=<?= $waText ?>" target="_blank" rel="noopener"><?= e(__('share_whatsapp')) ?></a>
                            <a class="btn btn-sm btn-ghost" href="mailto:?subject=<?= rawurlencode(__('share_exam_mail_subject', ['name' => $c['first_name']])) ?>&body=<?= $waText ?>"><?= e(__('share_email')) ?></a>
                        </div>
                        <p class="tiny muted exam-link-url"><code><?= e($examLink) ?></code></p>
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
