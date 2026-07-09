<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/imtahanlar/yeni') ?>">+ Yeni imtahan</a>
    </div>
    <table class="table">
        <thead>
        <tr><th>ID</th><th>Başlıq</th><th>Sinif</th><th>Sektor</th><th>Suallar</th><th>Status</th><th>Canlı</th><th></th></tr>
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
                    <?php if ($e['status'] !== 'running'): ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/baslat/' . $e['id']) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-sm">Başlat</button></form>
                    <?php else: ?>
                        <form method="post" action="<?= url('/admin/imtahanlar/bitir/' . $e['id']) ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-sm btn-danger">Bitir</button></form>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/imtahanlar/monitor/' . $e['id']) ?>">Monitor</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($exams)): ?><tr><td colspan="8" class="muted">İmtahan yoxdur</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
