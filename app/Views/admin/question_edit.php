<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/suallar/bax/' . $q['id']) ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
    </div>

    <form method="post" action="<?= e(form_get_action()) ?>" class="form-card" id="questionForm">
        <?= route_hidden('/admin/suallar/duzelis/' . (int) $q['id']) ?>
        <?= csrf_field() ?>
        <div class="grid-3">
            <label><?= e(__('subject')) ?>
                <select name="subject_id" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (int)$q['subject_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e(subject_name($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('grade')) ?>
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= (int)$q['grade'] === $g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('sector')) ?>
                <select name="sector" required>
                    <option value="az" <?= $q['sector'] === 'az' ? 'selected' : '' ?>><?= e(__('sector_az_short')) ?></option>
                    <option value="ru" <?= $q['sector'] === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru_short')) ?></option>
                </select>
            </label>
        </div>

        <?php require __DIR__ . '/partials/math_editor.php'; ?>

        <button class="btn" type="submit"><?= e(__('save_changes')) ?></button>
    </form>
</div>
