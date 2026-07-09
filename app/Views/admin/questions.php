<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/suallar/elave') ?>">+ Copy-Paste ilə əlavə et</a>
    </div>

    <form class="filter-bar" method="get">
        <select name="grade">
            <option value="0">Bütün siniflər</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= $g ?>" <?= (int)$filters['grade'] === $g ? 'selected' : '' ?>><?= $g ?>-ci</option>
            <?php endforeach; ?>
        </select>
        <select name="sector">
            <option value="">Bütün sektorlar</option>
            <option value="az" <?= $filters['sector'] === 'az' ? 'selected' : '' ?>>Azərbaycan</option>
            <option value="ru" <?= $filters['sector'] === 'ru' ? 'selected' : '' ?>>Rus</option>
        </select>
        <select name="subject_id">
            <option value="0">Bütün fənnlər</option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$filters['subjectId'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name_az']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm" type="submit">Filter</button>
    </form>

    <table class="table">
        <thead><tr><th>ID</th><th>Sual</th><th>Fənn</th><th>Sinif</th><th>Sektor</th><th>Cavab</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($questions as $q): ?>
            <tr>
                <td><?= (int)$q['id'] ?></td>
                <td class="q-preview"><?= e(mb_strimwidth($q['question_text'], 0, 80, '…')) ?></td>
                <td><?= e($q['subject_name']) ?></td>
                <td><?= (int)$q['grade'] ?></td>
                <td><?= e(strtoupper($q['sector'])) ?></td>
                <td><strong><?= e($q['correct_option']) ?></strong></td>
                <td>
                    <form method="post" action="<?= url('/admin/suallar/sil/' . $q['id']) ?>" onsubmit="return confirm('Silinsin?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-btn danger">Sil</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($questions)): ?><tr><td colspan="7" class="muted">Sual yoxdur</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
