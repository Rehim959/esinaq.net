<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/imtahanlar') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
        <a href="<?= url('/admin/imtahanlar/monitor/' . (int) $exam['id']) ?>" class="btn btn-ghost btn-sm"><?= e(__('monitor')) ?></a>
    </div>

    <div class="info-strip">
        <strong><?= e($exam['title']) ?></strong>
        <span class="muted">
            <?= e(grade_label((int) $exam['grade'])) ?> ·
            <?= e(sector_label((string) $exam['sector'])) ?> ·
            <?= e(format_date($exam['starts_at'] ?? null)) ?>
        </span>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th><?= e(__('parent')) ?></th>
            <th><?= e(__('email')) ?></th>
            <th><?= e(__('children')) ?></th>
            <th><?= e(__('status')) ?></th>
            <th><?= e(__('invite_interested_at')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($invites as $inv): ?>
            <?php
            $st = (string) $inv['status'];
            $parentName = person_full_name($inv);
            ?>
            <tr>
                <td><?= e($parentName) ?></td>
                <td class="tiny"><?= e((string) $inv['email']) ?></td>
                <td><?= e((string) ($inv['child_names'] ?? '—')) ?></td>
                <td><span class="status status-invite-<?= e($st) ?>"><?= e(__('invite_status_' . $st)) ?></span></td>
                <td class="tiny"><?= e(format_date($inv['interested_at'] ?? null)) ?></td>
                <td class="actions-cell">
                    <?php if (in_array($st, ['interested', 'invited'], true)): ?>
                        <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form">
                            <?= route_hidden('/admin/imtahanlar/dewet/tesdiq/' . (int) $inv['id']) ?>
                            <?= csrf_field() ?>
                            <button class="btn btn-sm" type="submit"><?= e(__('invite_approve')) ?></button>
                        </form>
                        <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form">
                            <?= route_hidden('/admin/imtahanlar/dewet/ref/' . (int) $inv['id']) ?>
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-danger" type="submit"><?= e(__('invite_reject')) ?></button>
                        </form>
                    <?php elseif ($st === 'approved'): ?>
                        <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form">
                            <?= route_hidden('/admin/imtahanlar/dewet/ref/' . (int) $inv['id']) ?>
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-ghost" type="submit"><?= e(__('invite_reject')) ?></button>
                        </form>
                    <?php elseif ($st === 'rejected'): ?>
                        <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form">
                            <?= route_hidden('/admin/imtahanlar/dewet/tesdiq/' . (int) $inv['id']) ?>
                            <?= csrf_field() ?>
                            <button class="btn btn-sm" type="submit"><?= e(__('invite_approve')) ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if (in_array($st, ['invited', 'interested'], true)): ?>
                        <form method="post" action="<?= e(form_get_action()) ?>" class="inline-form">
                            <?= route_hidden('/admin/imtahanlar/dewet/yeniden/' . (int) $inv['id']) ?>
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-ghost" type="submit"><?= e(__('invite_resend')) ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($invites)): ?>
            <tr><td colspan="6" class="muted"><?= e(__('no_exam_invites')) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
