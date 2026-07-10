<div class="page">
    <form class="filter-bar" method="get" action="<?= e(form_get_action()) ?>">
        <?= route_hidden('/admin/usaqlar') ?>
        <input type="search" name="q" value="<?= e($search ?? '') ?>" placeholder="<?= e(__('search_children_placeholder')) ?>" class="search-input">
        <button class="btn btn-sm" type="submit"><?= e(__('search')) ?></button>
        <?php if (!empty($search)): ?>
            <a class="btn btn-sm btn-ghost" href="<?= url('/admin/usaqlar') ?>"><?= e(__('clear')) ?></a>
        <?php endif; ?>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th><?= e(__('id')) ?></th>
            <th><?= e(__('name')) ?></th>
            <th><?= e(__('parents')) ?></th>
            <th><?= e(__('birth_date')) ?></th>
            <th><?= e(__('grade')) ?></th>
            <th><?= e(__('sector')) ?></th>
            <th><?= e(__('gender')) ?></th>
            <th><?= e(__('password')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($children as $c): ?>
            <tr>
                <td><?= (int)$c['id'] ?></td>
                <td>
                    <?= e(person_full_name($c)) ?>
                    <div class="tiny muted"><a href="<?= e(url('/imtahan/' . $c['access_token'])) ?>" target="_blank"><?= e(__('exam_link')) ?></a></div>
                </td>
                <td>
                    <a href="<?= url('/admin/valideyn/' . $c['parent_id']) ?>"><?= e(person_full_name([
                        'first_name' => $c['parent_first'] ?? '',
                        'last_name' => $c['parent_last'] ?? '',
                        'patronymic' => $c['parent_patronymic'] ?? '',
                    ])) ?></a>
                    <div class="tiny muted"><?= e($c['parent_email']) ?></div>
                    <?php if (!empty($c['parent_phone'])): ?>
                        <div class="tiny muted"><?= e($c['parent_phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= e(format_date($c['birth_date'], 'd.m.Y')) ?></td>
                <td><?= e(grade_label((int)$c['grade'])) ?></td>
                <td><?= e(sector_label($c['sector'])) ?></td>
                <td><?= e($c['gender'] === 'girl' ? __('girl') : __('boy')) ?></td>
                <td><code><?= e(child_password_display($c['password_hint'] ?? null, $c['first_name'] ?? null, $c['birth_date'] ?? null)) ?></code></td>
                <td class="actions-cell">
                    <?php if (\App\Core\Auth::canManageAccounts()): ?>
                    <a class="btn btn-sm" href="<?= url('/admin/usaq/duzelis/' . $c['id']) ?>"><?= e(__('edit')) ?></a>
                    <form method="post" action="<?= e(form_get_action()) ?>" class="inline-reset">
                        <?= route_hidden('/admin/usaq/sifre/' . (int) $c['id']) ?>
                        <?= csrf_field() ?>
                        <input type="text" name="new_password" placeholder="<?= e(__('new_or_auto')) ?>" class="input-sm">
                        <button class="btn btn-sm" type="submit"><?= e(__('reset_password_btn')) ?></button>
                    </form>
                    <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form" data-confirm="<?= e(__('confirm_delete_child')) ?>">
                        <?= route_hidden('/admin/usaq/sil/' . (int) $c['id']) ?>
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-danger" type="submit"><?= e(__('delete')) ?></button>
                    </form>
                    <?php else: ?>
                        <a class="btn btn-sm btn-ghost" href="<?= url('/admin/valideyn/' . $c['parent_id']) ?>"><?= e(__('view_details')) ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($children)): ?>
            <tr><td colspan="9" class="muted"><?= e(__('no_results_yet')) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
