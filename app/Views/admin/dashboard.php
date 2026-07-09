<div class="page">
    <div class="stat-grid">
        <div class="stat"><div class="stat-num"><?= (int)$stats['parents'] ?></div><div class="stat-label">Valideyn</div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['children'] ?></div><div class="stat-label">Uşaq</div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['questions'] ?></div><div class="stat-label">Sual</div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['exams'] ?></div><div class="stat-label">İmtahan</div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['running'] ?></div><div class="stat-label">Aktiv imtahan</div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['sessions_today'] ?></div><div class="stat-label">Bu gün sessiya</div></div>
    </div>

    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/suallar/elave') ?>">Sual əlavə et</a>
        <a class="btn btn-ghost" href="<?= url('/admin/imtahanlar/yeni') ?>">İmtahan yarat</a>
    </div>

    <h2>Son nəticələr</h2>
    <table class="table">
        <thead><tr><th>Uşaq</th><th>İmtahan</th><th>Bal</th><th>Hərf</th><th>Tarix</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                <td><?= e($r['title']) ?></td>
                <td><?= e((string)$r['percentage']) ?>%</td>
                <td><?= e($r['letter_grade']) ?></td>
                <td><?= e(format_date($r['finished_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recent)): ?><tr><td colspan="5" class="muted">Hələ nəticə yoxdur</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
