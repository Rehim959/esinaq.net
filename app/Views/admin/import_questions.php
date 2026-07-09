<div class="page">
    <form method="post" action="<?= url('/admin/suallar/elave') ?>" class="form-card">
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

        <label><?= e(__('paste_questions')) ?>
            <textarea name="raw_text" rows="18" required placeholder="1. 2+2 = ?
A) 3
B) 4
C) 5
D) 6
+B"></textarea>
        </label>

        <div class="hint-box">
            <strong><?= e(__('format_help')) ?></strong>
            <ul>
                <li><?= e(__('format_1')) ?></li>
                <li><?= e(__('format_2')) ?></li>
                <li><?= e(__('format_3')) ?></li>
                <li><?= e(__('format_4')) ?></li>
            </ul>
        </div>

        <button class="btn" type="submit"><?= e(__('upload_questions')) ?></button>
    </form>
</div>
