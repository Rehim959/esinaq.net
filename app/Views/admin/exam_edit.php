<?php
$starts = !empty($exam['starts_at']) ? date('Y-m-d\TH:i', strtotime($exam['starts_at'])) : '';
$ends = !empty($exam['ends_at']) ? date('Y-m-d\TH:i', strtotime($exam['ends_at'])) : '';
?>
<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/imtahanlar') ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
    </div>
    <form method="post" action="<?= url('/admin/imtahanlar/duzelis/' . $exam['id']) ?>" class="form-card">
        <?= csrf_field() ?>
        <label><?= e(__('exam_title')) ?>
            <input type="text" name="title" value="<?= e($exam['title']) ?>" required>
        </label>
        <label><?= e(__('duration_minutes')) ?>
            <input type="number" name="duration_minutes" value="<?= (int)$exam['duration_minutes'] ?>" min="5" max="300" required>
        </label>
        <p class="hint"><?= e(__('exam_schedule_help')) ?></p>
        <div class="grid-2">
            <label><?= e(__('starts_at')) ?>
                <input type="datetime-local" name="starts_at" value="<?= e($starts) ?>" required>
            </label>
            <label><?= e(__('ends_at')) ?>
                <input type="datetime-local" name="ends_at" value="<?= e($ends) ?>" required>
            </label>
        </div>
        <p class="tiny muted"><?= e(__('auto_schedule_note')) ?></p>
        <button class="btn" type="submit"><?= e(__('save_changes')) ?></button>
    </form>
</div>
