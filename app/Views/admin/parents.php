<div class="page">
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
                <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
                <td><?= e($p['email']) ?></td>
                <td><?= e($p['phone'] ?? '—') ?></td>
                <td><?= (int)$p['child_count'] ?></td>
                <td><?= e(format_date($p['created_at'], 'd.m.Y')) ?></td>
                <td class="actions-cell">
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/valideyn/' . $p['id']) ?>"><?= e(__('view_details')) ?></a>
                    <form method="post" action="<?= url('/admin/valideyn/sil/' . $p['id']) ?>" class="inline-form" onsubmit="return confirm(<?= json_encode(__('confirm_delete_parent'), JSON_UNESCAPED_UNICODE) ?>)">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(__('delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($parents)): ?>
            <tr><td colspan="7" class="muted"><?= e(__('no_results_yet')) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
