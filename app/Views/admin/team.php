<div class="page">
    <?php if (\App\Core\Auth::canManageTeam()): ?>
    <div class="form-card team-add-card">
        <h2 class="team-add-title"><?= e(__('add_staff')) ?></h2>
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
                        <?php if (\App\Core\Auth::canAddRole('admin')): ?>
                            <option value="admin"><?= e(__('role_admin')) ?></option>
                        <?php endif; ?>
                    </select>
                </label>
            </div>
            <button class="btn" type="submit"><?= e(__('add_staff')) ?></button>
        </form>
    </div>
    <?php endif; ?>

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
            <?php
            $assignable = \App\Core\Auth::assignableRolesFor($a);
            $canPass = \App\Core\Auth::canChangeStaffPassword($a);
            $canToggle = \App\Core\Auth::canToggleStaff($a);
            $hasAction = $assignable !== [] || $canPass || $canToggle;
            ?>
            <tr>
                <td><?= (int)$a['id'] ?></td>
                <td><?= e($a['full_name']) ?></td>
                <td><?= e($a['email']) ?></td>
                <td><?= e(\App\Core\Auth::roleLabel((string) $a['role'])) ?></td>
                <td><?= e((int)$a['is_active'] ? __('admin_active') : __('admin_inactive')) ?></td>
                <td><?= e(format_date($a['created_at'], 'd.m.Y')) ?></td>
                <td class="actions-cell team-actions">
                    <?php if ($assignable !== []): ?>
                        <form method="post" action="<?= url('/admin/komanda/rol/' . $a['id']) ?>" class="inline-form team-role-form"
                              data-confirm="<?= e(__('confirm_staff_role_change')) ?>">
                            <?= csrf_field() ?>
                            <select name="role" class="input-sm" required>
                                <?php foreach ($assignable as $roleOpt): ?>
                                    <option value="<?= e($roleOpt) ?>" <?= (string) $a['role'] === $roleOpt ? 'selected' : '' ?>>
                                        <?= e(\App\Core\Auth::roleLabel($roleOpt)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm" type="submit"><?= e(__('change_role')) ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canPass): ?>
                        <form method="post" action="<?= url('/admin/komanda/sifre/' . $a['id']) ?>" class="inline-reset team-pass-form"
                              data-confirm="<?= e(__('confirm_staff_password_reset')) ?>">
                            <?= csrf_field() ?>
                            <input type="text" name="new_password" minlength="8" required
                                   placeholder="<?= e(__('new_password')) ?>" class="input-sm" autocomplete="new-password">
                            <button class="btn btn-sm" type="submit"><?= e(__('reset_password_btn')) ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canToggle): ?>
                        <form method="post" action="<?= url('/admin/komanda/toggle/' . $a['id']) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm" type="submit">
                                <?= e((int)$a['is_active'] ? __('deactivate') : __('activate')) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (!$hasAction): ?>
                        <span class="muted tiny">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
