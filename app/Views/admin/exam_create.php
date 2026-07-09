<div class="page">
    <form method="post" action="<?= url('/admin/imtahanlar/yeni') ?>" class="form-card">
        <?= csrf_field() ?>
        <label><?= e(__('exam_title')) ?><input type="text" name="title" required placeholder="5 · Math"></label>
        <div class="grid-3">
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
            <label><?= e(__('duration_minutes')) ?><input type="number" name="duration_minutes" value="60" min="5" required></label>
        </div>
        <div class="grid-2">
            <label><?= e(__('starts_at')) ?><input type="datetime-local" name="starts_at"></label>
            <label><?= e(__('ends_at')) ?><input type="datetime-local" name="ends_at"></label>
        </div>

        <h3><?= e(__('subjects_and_counts')) ?></h3>
        <p class="muted"><?= e(__('subjects_random_help')) ?></p>
        <div class="subject-pick">
            <?php foreach ($subjects as $s): ?>
                <label class="pick-row">
                    <input type="checkbox" name="subject_ids[]" value="<?= (int)$s['id'] ?>">
                    <span><?= e(subject_name($s)) ?></span>
                    <input type="number" name="question_counts[<?= (int)$s['id'] ?>]" value="10" min="1" max="50" title="<?= e(__('question_count')) ?>">
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn" type="submit"><?= e(__('create_exam_btn')) ?></button>
    </form>
</div>
