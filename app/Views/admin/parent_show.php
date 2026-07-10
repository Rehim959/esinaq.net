<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/valideynler') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
    </div>

    <div class="info-strip">
        <div><strong><?= e(person_full_name($parent)) ?></strong></div>
        <div><?= e(__('email')) ?>: <?= e($parent['email']) ?></div>
        <div><?= e(__('phone')) ?>: <?= e(($parent['phone'] ?? '') !== '' ? $parent['phone'] : '—') ?></div>
        <div><?= e(__('date')) ?>: <?= e(format_date($parent['created_at'], 'd.m.Y H:i')) ?></div>
    </div>

    <?php if (\App\Core\Auth::canManageAccounts()): ?>
    <div class="admin-panels">
        <section class="form-card">
            <h2><?= e(__('edit_parent_name')) ?></h2>
            <form method="post" action="<?= e(form_get_action()) ?>">
                <?= route_hidden('/admin/valideyn/ad/' . (int) $parent['id']) ?>
                <?= csrf_field() ?>
                <div class="grid-3">
                    <label><?= e(__('first_name')) ?>
                        <input type="text" name="first_name" required value="<?= e((string) $parent['first_name']) ?>">
                    </label>
                    <label><?= e(__('last_name')) ?>
                        <input type="text" name="last_name" required value="<?= e((string) $parent['last_name']) ?>">
                    </label>
                    <label><?= e(__('patronymic')) ?>
                        <input type="text" name="patronymic" required value="<?= e((string) ($parent['patronymic'] ?? '')) ?>">
                    </label>
                </div>
                <button class="btn" type="submit"><?= e(__('save_changes')) ?></button>
            </form>
        </section>

        <section class="form-card">
            <h2><?= e(__('reset_parent_password')) ?></h2>
            <form method="post" action="<?= e(form_get_action()) ?>">
                <?= route_hidden('/admin/valideyn/sifre/' . (int) $parent['id']) ?>
                <?= csrf_field() ?>
                <label><?= e(__('new_password')) ?>
                    <input type="text" name="new_password" minlength="6" required placeholder="YeniSifre123">
                </label>
                <button class="btn" type="submit"><?= e(__('reset_password_btn')) ?></button>
            </form>
        </section>

        <section class="form-card danger-zone">
            <h2><?= e(__('delete_parent')) ?></h2>
            <p class="muted"><?= e(__('delete_parent_help')) ?></p>
            <form method="post" action="<?= e(form_get_action()) ?>" data-confirm="<?= e(__('confirm_delete_parent')) ?>">
                <?= route_hidden('/admin/valideyn/sil/' . (int) $parent['id']) ?>
                <?= csrf_field() ?>
                <button class="btn btn-danger" type="submit"><?= e(__('delete')) ?></button>
            </form>
        </section>
    </div>
    <?php else: ?>
        <p class="muted"><?= e(__('moderator_accounts_readonly')) ?></p>
    <?php endif; ?>

    <h2><?= e(__('children_list')) ?></h2>
    <?php if (empty($children)): ?>
        <p class="muted"><?= e(__('no_children_admin')) ?></p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th><?= e(__('name')) ?></th>
                <th><?= e(__('birth_date')) ?></th>
                <th><?= e(__('grade')) ?></th>
                <th><?= e(__('sector')) ?></th>
                <th><?= e(__('gender')) ?></th>
                <th><?= e(__('password')) ?></th>
                <th><?= e(__('link')) ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($children as $c): ?>
                <tr>
                    <td><?= e(person_full_name($c)) ?></td>
                    <td><?= e(format_date($c['birth_date'], 'd.m.Y')) ?></td>
                    <td><?= e(grade_label((int)$c['grade'])) ?></td>
                    <td><?= e(sector_label($c['sector'])) ?></td>
                    <td><?= e($c['gender'] === 'girl' ? __('girl') : __('boy')) ?></td>
                    <td><code><?= e(child_password_display($c['password_hint'] ?? null, $c['first_name'] ?? null, $c['birth_date'] ?? null)) ?></code></td>
                    <td class="tiny"><code><?= e(url('/imtahan/' . $c['access_token'])) ?></code></td>
                    <td class="actions-cell">
                        <?php if (\App\Core\Auth::canManageAccounts()): ?>
                        <a class="btn btn-sm" href="<?= url('/admin/usaq/duzelis/' . $c['id'] . '?back=parent') ?>"><?= e(__('edit')) ?></a>
                        <form method="post" action="<?= e(form_get_action()) ?>" class="inline-reset">
                            <?= route_hidden('/admin/usaq/sifre/' . (int) $c['id']) ?>
                            <?= csrf_field() ?>
                            <input type="hidden" name="back" value="parent">
                            <input type="text" name="new_password" placeholder="<?= e(__('new_password')) ?>" class="input-sm">
                            <button class="btn btn-sm" type="submit"><?= e(__('reset_password_btn')) ?></button>
                        </form>
                        <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form" data-confirm="<?= e(__('confirm_delete_child')) ?>">
                            <?= route_hidden('/admin/usaq/sil/' . (int) $c['id']) ?>
                            <?= csrf_field() ?>
                            <input type="hidden" name="back" value="parent">
                            <button class="btn btn-sm btn-danger" type="submit"><?= e(__('delete')) ?></button>
                        </form>
                        <?php else: ?>
                            <span class="muted tiny">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2><?= e(__('exam_history')) ?></h2>
    <table class="table">
        <thead>
        <tr>
            <th><?= e(__('child')) ?></th>
            <th><?= e(__('exam')) ?></th>
            <th><?= e(__('status')) ?></th>
            <th><?= e(__('score')) ?></th>
            <th><?= e(__('date')) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($sessions ?? []) as $s): ?>
            <tr>
                <td><?= e(person_full_name($s)) ?></td>
                <td><?= e($s['title'] ?? '') ?></td>
                <td><?= e($s['status'] ?? '') ?></td>
                <td><?= $s['percentage'] !== null ? e((string) $s['percentage']) . '% (' . e((string) $s['letter_grade']) . ')' : '—' ?></td>
                <td><?= e(format_date($s['finished_at'] ?? $s['started_at'] ?? null, 'd.m.Y H:i')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?>
            <tr><td colspan="5" class="muted"><?= e(__('no_results_yet')) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
