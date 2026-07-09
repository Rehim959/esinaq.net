<div class="page">
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
                    <form method="post" action="<?= url('/admin/usaq/sifre/' . $c['id']) ?>" class="inline-reset">
                        <?= csrf_field() ?>
                        <input type="text" name="new_password" placeholder="<?= e(__('new_or_auto')) ?>" class="input-sm">
                        <button class="btn btn-sm" type="submit"><?= e(__('reset_password_btn')) ?></button>
                    </form>
                    <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                    <form method="post" action="<?= url('/admin/usaq/sil/' . $c['id']) ?>" class="inline-form" data-confirm="<?= e(__('confirm_delete_child')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-danger" type="submit"><?= e(__('delete')) ?></button>
                    </form>
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
