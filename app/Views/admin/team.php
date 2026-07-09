<div class="page">
    <div class="form-card team-add-card">
        <h2 class="team-add-title"><?= e(__('add_moderator')) ?></h2>
        <form method="post" action="<?= url('/admin/komanda') ?>">
            <?= csrf_field() ?>
            <div class="grid-2">
                <label><?= e(__('full_name')) ?>
                    <input type="text" name="full_name" required>
                </label>
                <label><?= e(__('email')) ?>
                    <input type="email" name="email" required>
                </label>
            </div>
            <div class="grid-2">
                <label><?= e(__('password')) ?>
                    <input type="text" name="password" minlength="8" required>
                </label>
                <label><?= e(__('role')) ?>
                    <select name="role">
                        <option value="moderator"><?= e(__('role_moderator')) ?></option>
                        <option value="super_admin"><?= e(__('role_super_admin')) ?></option>
                    </select>
                </label>
            </div>
            <button class="btn" type="submit"><?= e(__('add_moderator')) ?></button>
        </form>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th><?= e(__('id')) ?></th>
            <th><?= e(__('full_name')) ?></th>
            <th><?= e(__('email')) ?></th>
            <th><?= e(__('role')) ?></th>
            <th><?= e(__('status')) ?></th>
            <th><?= e(__('date')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $a): ?>
            <tr>
                <td><?= (int)$a['id'] ?></td>
                <td><?= e($a['full_name']) ?></td>
                <td><?= e($a['email']) ?></td>
                <td><?= e($a['role'] === 'super_admin' ? __('role_super_admin') : __('role_moderator')) ?></td>
                <td><?= e((int)$a['is_active'] ? __('admin_active') : __('admin_inactive')) ?></td>
                <td><?= e(format_date($a['created_at'], 'd.m.Y')) ?></td>
                <td class="actions-cell">
                    <?php if ((int)$a['id'] !== (int)\App\Core\Auth::adminId()): ?>
                        <form method="post" action="<?= url('/admin/komanda/toggle/' . $a['id']) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm" type="submit">
                                <?= e((int)$a['is_active'] ? __('deactivate') : __('activate')) ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="muted tiny">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
