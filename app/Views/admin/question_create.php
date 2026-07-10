<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/suallar') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
        <a href="<?= url('/admin/suallar/elave') ?>" class="btn btn-ghost btn-sm"><?= e(__('add_question_paste')) ?></a>
    </div>

    <form method="post" action="<?= e(form_get_action()) ?>" class="form-card" id="questionForm">
        <?= route_hidden('/admin/suallar/yeni') ?>
        <?= csrf_field() ?>
        <div class="grid-3">
            <label><?= e(__('subject')) ?>
                <select name="subject_id" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e(subject_name($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('grade')) ?>
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>"><?= e(grade_label($g)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('sector')) ?>
                <select name="sector" required>
                    <option value="az"><?= e(__('sector_az_short')) ?></option>
                    <option value="ru"><?= e(__('sector_ru_short')) ?></option>
                </select>
            </label>
        </div>

        <?php require __DIR__ . '/partials/math_editor.php'; ?>

        <button class="btn" type="submit"><?= e(__('save_question')) ?></button>
    </form>
</div>
