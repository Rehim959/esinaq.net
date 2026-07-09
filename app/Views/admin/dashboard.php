<div class="page">
    <div class="stat-grid">
        <div class="stat"><div class="stat-num"><?= (int)$stats['parents'] ?></div><div class="stat-label"><?= e(__('parents')) ?></div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['children'] ?></div><div class="stat-label"><?= e(__('children')) ?></div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['questions'] ?></div><div class="stat-label"><?= e(__('question')) ?></div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['exams'] ?></div><div class="stat-label"><?= e(__('exam')) ?></div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['running'] ?></div><div class="stat-label"><?= e(__('active_exams')) ?></div></div>
        <div class="stat"><div class="stat-num"><?= (int)$stats['sessions_today'] ?></div><div class="stat-label"><?= e(__('sessions_today')) ?></div></div>
    </div>

    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/suallar/elave') ?>"><?= e(__('add_question')) ?></a>
        <a class="btn btn-ghost" href="<?= url('/admin/imtahanlar/yeni') ?>"><?= e(__('create_exam')) ?></a>
    </div>

    <h2><?= e(__('recent_results')) ?></h2>
    <table class="table">
        <thead><tr><th><?= e(__('child')) ?></th><th><?= e(__('exam')) ?></th><th><?= e(__('score')) ?></th><th><?= e(__('letter')) ?></th><th><?= e(__('date')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?= e(person_full_name($r)) ?></td>
                <td><?= e($r['title']) ?></td>
                <td><?= e((string)$r['percentage']) ?>%</td>
                <td><?= e($r['letter_grade']) ?></td>
                <td><?= e(format_date($r['finished_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recent)): ?><tr><td colspan="5" class="muted"><?= e(__('no_results_yet')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
