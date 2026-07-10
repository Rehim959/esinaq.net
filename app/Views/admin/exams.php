<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/imtahanlar/yeni') ?>"><?= e(__('create_exam_plus')) ?></a>
    </div>
    <table class="table">
        <thead>
        <tr>
            <th><?= e(__('id')) ?></th>
            <th><?= e(__('exam_title')) ?></th>
            <th><?= e(__('grade')) ?></th>
            <th><?= e(__('sector')) ?></th>
            <th><?= e(__('questions')) ?></th>
            <th><?= e(__('status')) ?></th>
            <th><?= e(__('exam_stat_parents')) ?></th>
            <th><?= e(__('exam_stat_children')) ?></th>
            <th>Live</th>
            <th><?= e(__('starts_at')) ?></th>
            <th><?= e(__('ends_at')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($exams as $e): ?>
            <?php
            $hasInvites = (int) ($e['invite_total'] ?? 0) > 0;
            $parentsApproved = (int) ($e['parents_approved'] ?? 0);
            $childrenPart = (int) ($e['children_participated'] ?? 0);
            ?>
            <tr>
                <td><?= (int)$e['id'] ?></td>
                <td><?= e($e['title']) ?></td>
                <td><?= (int)$e['grade'] ?></td>
                <td><?= e(strtoupper($e['sector'])) ?></td>
                <td><?= (int)$e['question_count'] ?></td>
                <td><span class="status status-<?= e($e['status']) ?>"><?= e($e['status']) ?></span></td>
                <td>
                    <?php if ($hasInvites): ?>
                        <strong><?= $parentsApproved ?></strong>
                        <span class="muted tiny"> / <?= (int) $e['invite_total'] ?></span>
                    <?php else: ?>
                        <span class="muted">—</span>
                    <?php endif; ?>
                </td>
                <td><strong><?= $childrenPart ?></strong></td>
                <td><?= (int)$e['live_count'] ?></td>
                <td class="tiny"><?= e(format_date($e['starts_at'] ?? null)) ?></td>
                <td class="tiny"><?= e(format_date($e['ends_at'] ?? null)) ?></td>
                <td class="actions-cell">
                    <?php if ($e['status'] === 'running'): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/bitir/' . $e['id']) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-sm btn-danger"><?= e(__('finish')) ?></button></form>
                    <?php elseif (in_array($e['status'], ['draft', 'scheduled'], true)): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/baslat/' . $e['id']) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-sm"><?= e(__('start')) ?></button></form>
                    <?php elseif (in_array($e['status'], ['finished', 'cancelled'], true) && \App\Core\Auth::canManageExams()): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/yeniden/' . $e['id']) ?>" class="inline-form" data-confirm="<?= e(__('confirm_rerun_exam')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm"><?= e(__('rerun_exam')) ?></button>
                        </form>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/imtahanlar/dewetler/' . $e['id']) ?>">
                        <?= e(__('exam_invites')) ?>
                        <?php if ((int) ($e['invite_pending'] ?? 0) > 0): ?>
                            <span class="badge-pending"><?= (int) $e['invite_pending'] ?></span>
                        <?php elseif ($hasInvites): ?>
                            <span class="badge-muted"><?= (int) $e['invite_total'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/imtahanlar/duzelis/' . $e['id']) ?>"><?= e(__('edit_schedule')) ?></a>
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/imtahanlar/monitor/' . $e['id']) ?>"><?= e(__('monitor')) ?></a>
                    <?php if (\App\Core\Auth::canManageExams()): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/sil/' . $e['id']) ?>" class="inline-form" data-confirm="<?= e(__('confirm_delete_exam')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-danger" type="submit"><?= e(__('delete_exam')) ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($exams)): ?><tr><td colspan="12" class="muted"><?= e(__('no_exams')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
