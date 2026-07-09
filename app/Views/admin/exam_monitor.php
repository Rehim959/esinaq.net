<div class="page">
    <a href="<?= url('/admin/imtahanlar') ?>" class="btn btn-ghost btn-sm">← Geri</a>
    <div class="info-strip">
        <strong><?= e($exam['title']) ?></strong>
        <span class="status status-<?= e($exam['status']) ?>"><?= e($exam['status']) ?></span>
        <span><?= (int)$exam['grade'] ?>-ci · <?= e(strtoupper($exam['sector'])) ?></span>
    </div>
    <table class="table">
        <thead><tr><th>Uşaq</th><th>Status</th><th>Başladı</th><th>Bitdi</th><th>Bal</th></tr></thead>
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
        <?php if (empty($sessions)): ?><tr><td colspan="5" class="muted">Hələ sessiya yoxdur</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
