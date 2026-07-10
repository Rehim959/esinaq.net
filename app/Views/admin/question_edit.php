<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/suallar/bax/' . $q['id']) ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
    </div>

    <form method="post" action="<?= url('/admin/suallar/duzelis/' . $q['id']) ?>" class="form-card">
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

        <label><?= e(__('question')) ?>
            <textarea name="question_text" rows="4" required><?= e($q['question_text']) ?></textarea>
        </label>

        <div class="grid-2">
            <label>A <input type="text" name="option_a" value="<?= e($q['option_a']) ?>" required></label>
            <label>B <input type="text" name="option_b" value="<?= e($q['option_b']) ?>" required></label>
            <label>C <input type="text" name="option_c" value="<?= e($q['option_c']) ?>" required></label>
            <label>D <input type="text" name="option_d" value="<?= e($q['option_d']) ?>" required></label>
            <label>E <input type="text" name="option_e" value="<?= e((string)($q['option_e'] ?? '')) ?>" placeholder="<?= e(__('optional')) ?>"></label>
            <label><?= e(__('answer_col')) ?>
                <select name="correct_option" required>
                    <?php foreach (['A','B','C','D','E'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $q['correct_option'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <button class="btn" type="submit"><?= e(__('save_changes')) ?></button>
    </form>
</div>
