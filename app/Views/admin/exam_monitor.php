<div class="page">
    <a href="<?= url('/admin/imtahanlar') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
    <div class="info-strip">
        <strong><?= e($exam['title']) ?></strong>
        <span class="status status-<?= e($exam['status']) ?>"><?= e($exam['status']) ?></span>
        <span><?= e(grade_label((int)$exam['grade'])) ?> · <?= e(strtoupper($exam['sector'])) ?></span>
    </div>
    <table class="table">
        <thead><tr><th><?= e(__('child')) ?></th><th><?= e(__('status')) ?></th><th><?= e(__('started')) ?></th><th><?= e(__('finished')) ?></th><th><?= e(__('score')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
            <tr>
                <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <td><?= e($s['status']) ?></td>
                <td><?= e(format_date($s['started_at'])) ?></td>
                <td><?= e(format_date($s['finished_at'])) ?></td>
                <td><?= $s['percentage'] !== null ? e((string)$s['percentage']) . '% (' . e($s['letter_grade']) . ')' : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?><tr><td colspan="5" class="muted"><?= e(__('no_sessions')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
