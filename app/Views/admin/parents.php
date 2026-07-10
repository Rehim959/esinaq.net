<div class="page">
    <form class="filter-bar" method="get" action="<?= e(form_get_action()) ?>">
        <?= route_hidden('/admin/valideynler') ?>
        <input type="search" name="q" value="<?= e($search ?? '') ?>" placeholder="<?= e(__('search_parents_placeholder')) ?>" class="search-input">
        <button class="btn btn-sm" type="submit"><?= e(__('search')) ?></button>
        <?php if (!empty($search)): ?>
            <a class="btn btn-sm btn-ghost" href="<?= url('/admin/valideynler') ?>"><?= e(__('clear')) ?></a>
        <?php endif; ?>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th><?= e(__('id')) ?></th>
            <th><?= e(__('name')) ?></th>
            <th><?= e(__('email')) ?></th>
            <th><?= e(__('phone')) ?></th>
            <th><?= e(__('children')) ?></th>
            <th><?= e(__('date')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($parents as $p): ?>
            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= e(person_full_name($p)) ?></td>
                <td><?= e($p['email']) ?></td>
                <td><?= e($p['phone'] !== '' ? $p['phone'] : '—') ?></td>
                <td><?= (int)$p['child_count'] ?></td>
                <td><?= e(format_date($p['created_at'], 'd.m.Y')) ?></td>
                <td class="actions-cell">
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/valideyn/' . $p['id']) ?>"><?= e(__('view_details')) ?></a>
                    <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                    <form method="post" action="<?= url('/admin/valideyn/sil/' . $p['id']) ?>" class="inline-form" data-confirm="<?= e(__('confirm_delete_parent')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(__('delete')) ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($parents)): ?>
            <tr><td colspan="7" class="muted"><?= e(__('no_results_yet')) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
