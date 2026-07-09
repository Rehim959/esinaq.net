<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/imtahanlar/yeni') ?>"><?= e(__('create_exam_plus')) ?></a>
    </div>
    <table class="table">
        <thead>
        <tr><th><?= e(__('id')) ?></th><th><?= e(__('exam_title')) ?></th><th><?= e(__('grade')) ?></th><th><?= e(__('sector')) ?></th><th><?= e(__('questions')) ?></th><th><?= e(__('status')) ?></th><th>Live</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($exams as $e): ?>
            <tr>
                <td><?= (int)$e['id'] ?></td>
                <td><?= e($e['title']) ?></td>
                <td><?= (int)$e['grade'] ?></td>
                <td><?= e(strtoupper($e['sector'])) ?></td>
                <td><?= (int)$e['question_count'] ?></td>
                <td><span class="status status-<?= e($e['status']) ?>"><?= e($e['status']) ?></span></td>
                <td><?= (int)$e['live_count'] ?></td>
                <td class="actions-cell">
                    <?php if ($e['status'] === 'running'): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/bitir/' . $e['id']) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-sm btn-danger"><?= e(__('finish')) ?></button></form>
                    <?php elseif (in_array($e['status'], ['finished', 'cancelled'], true)): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/yeniden/' . $e['id']) ?>" class="inline-form" onsubmit="return confirm(<?= json_encode(__('confirm_rerun_exam'), JSON_UNESCAPED_UNICODE) ?>)">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm"><?= e(__('rerun_exam')) ?></button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/baslat/' . $e['id']) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-sm"><?= e(__('start')) ?></button></form>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/imtahanlar/monitor/' . $e['id']) ?>"><?= e(__('monitor')) ?></a>
                    <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/sil/' . $e['id']) ?>" class="inline-form" onsubmit="return confirm(<?= json_encode(__('confirm_delete_exam'), JSON_UNESCAPED_UNICODE) ?>)">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-danger" type="submit"><?= e(__('delete_exam')) ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($exams)): ?><tr><td colspan="8" class="muted"><?= e(__('no_exams')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
