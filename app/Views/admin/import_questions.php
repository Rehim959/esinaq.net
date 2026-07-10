<?php
/** @var array{subject_id:int,grade:int,sector:string,raw_text:string} $old */
$old = $old ?? ['subject_id' => 0, 'grade' => 0, 'sector' => 'az', 'raw_text' => ''];
?>
<div class="page">
    <?php if ($report = \App\Core\Session::flash('import_report')): ?>
        <?php
        $saved = (int) ($report['saved'] ?? 0);
        $skipped = $report['skipped'] ?? [];
        $total = (int) ($report['total'] ?? 0);
        if (!is_array($skipped)) {
            $skipped = [];
        }
        ?>
        <div class="import-report <?= $saved > 0 ? 'import-report-ok' : 'import-report-warn' ?>">
            <strong><?= e(__('import_report_title')) ?></strong>
            <p><?= e(__('import_report_summary', [
                'saved' => (string) $saved,
                'skipped' => (string) count($skipped),
                'total' => (string) $total,
            ])) ?></p>
            <?php if ($skipped !== []): ?>
                <p class="import-report-skipped-title"><?= e(__('import_report_skipped_list')) ?></p>
                <ul class="import-report-list">
                    <?php foreach ($skipped as $row): ?>
                        <li>
                            <span class="muted">#<?= (int) ($row['n'] ?? 0) ?></span>
                            <?= e((string) ($row['preview'] ?? '')) ?>
                            — <em><?= e((string) ($row['reason'] ?? '')) ?></em>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/admin/suallar/elave') ?>" class="form-card">
        <?= csrf_field() ?>
        <div class="hint-box">
            <strong><?= e(__('import_preview_step_title')) ?></strong>
            <p><?= e(__('import_preview_step_help')) ?></p>
        </div>
        <div class="grid-3">
            <label><?= e(__('subject')) ?>
                <select name="subject_id" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (int)$old['subject_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e(subject_name($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('grade')) ?>
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= (int)$old['grade'] === $g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('sector')) ?>
                <select name="sector" required>
                    <option value="az" <?= $old['sector'] === 'az' ? 'selected' : '' ?>><?= e(__('sector_az_short')) ?></option>
                    <option value="ru" <?= $old['sector'] === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru_short')) ?></option>
                </select>
            </label>
        </div>

        <label><?= e(__('paste_questions')) ?>
            <textarea name="raw_text" rows="18" required placeholder="1. 2+2 = ?
A) 3
B) 4
C) 5
D) 6
+B"><?= e($old['raw_text']) ?></textarea>
        </label>

        <div class="hint-box">
            <strong><?= e(__('format_help')) ?></strong>
            <ul>
                <li><?= e(__('format_1')) ?></li>
                <li><?= e(__('format_2')) ?></li>
                <li><?= e(__('format_3')) ?></li>
                <li><?= e(__('format_4')) ?></li>
                <li><?= e(__('format_5_dup')) ?></li>
            </ul>
        </div>

        <button class="btn" type="submit"><?= e(__('import_preview_btn')) ?></button>
    </form>
</div>
