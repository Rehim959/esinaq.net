<div class="page">
    <div class="page-actions">
        <a class="btn" href="<?= url('/admin/suallar/elave') ?>"><?= e(__('add_question_paste')) ?></a>
    </div>

    <form class="filter-bar" method="get" action="<?= e(form_get_action()) ?>">
        <?= route_hidden('/admin/suallar') ?>
        <select name="grade">
            <option value="0"><?= e(__('all_grades')) ?></option>
            <?php foreach ($grades as $g): ?>
                <option value="<?= $g ?>" <?= (int)$filters['grade'] === $g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sector">
            <option value=""><?= e(__('all_sectors')) ?></option>
            <option value="az" <?= $filters['sector'] === 'az' ? 'selected' : '' ?>><?= e(__('sector_az_short')) ?></option>
            <option value="ru" <?= $filters['sector'] === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru_short')) ?></option>
        </select>
        <select name="subject_id">
            <option value="0"><?= e(__('all_subjects')) ?></option>
            <?php foreach ($subjects as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$filters['subjectId'] === (int)$s['id'] ? 'selected' : '' ?>><?= e(subject_name($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm" type="submit"><?= e(__('filter')) ?></button>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th><?= e(__('id')) ?></th>
            <th><?= e(__('question')) ?></th>
            <th><?= e(__('subject')) ?></th>
            <th><?= e(__('grade')) ?></th>
            <th><?= e(__('sector')) ?></th>
            <th><?= e(__('answer_col')) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($questions as $q): ?>
            <tr>
                <td><?= (int)$q['id'] ?></td>
                <td class="q-preview"><?= e(mb_strimwidth($q['question_text'], 0, 80, '…')) ?></td>
                <td><?= e(locale() === 'ru' ? ($q['subject_name_ru'] ?? $q['subject_name']) : $q['subject_name']) ?></td>
                <td><?= (int)$q['grade'] ?></td>
                <td><?= e(strtoupper($q['sector'])) ?></td>
                <td><strong><?= e($q['correct_option']) ?></strong></td>
                <td class="actions-cell">
                    <a class="btn btn-sm btn-ghost" href="<?= url('/admin/suallar/bax/' . $q['id']) ?>"><?= e(__('view_details')) ?></a>
                    <a class="btn btn-sm" href="<?= url('/admin/suallar/duzelis/' . $q['id']) ?>"><?= e(__('edit')) ?></a>
                    <form method="post" action="<?= url('/admin/suallar/sil/' . $q['id']) ?>" class="inline-form" data-confirm="<?= e(__('confirm_delete')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(__('delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($questions)): ?><tr><td colspan="7" class="muted"><?= e(__('no_questions')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
