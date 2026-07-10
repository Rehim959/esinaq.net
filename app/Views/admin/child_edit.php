<div class="page">
    <div class="page-actions">
        <?php
        $backParent = isset($_GET['back']) && $_GET['back'] === 'parent';
        $backUrl = $backParent
            ? url('/admin/valideyn/' . (int) $child['parent_id'])
            : url('/admin/usaqlar');
        $grades = grades_list();
        ?>
        <a href="<?= e($backUrl) ?>" class="btn btn-ghost btn-sm">← <?= e(__('back')) ?></a>
    </div>

    <p class="muted">
        <?= e(__('parents')) ?>:
        <a href="<?= url('/admin/valideyn/' . (int) $child['parent_id']) ?>">
            <?= e(person_full_name([
                'first_name' => $child['parent_first'] ?? '',
                'last_name' => $child['parent_last'] ?? '',
                'patronymic' => $child['parent_patronymic'] ?? '',
            ])) ?>
        </a>
    </p>

    <form method="post" action="<?= e(form_get_action()) ?>" class="form-card">
        <?= route_hidden('/admin/usaq/ad/' . (int) $child['id']) ?>
        <?= csrf_field() ?>
        <?php if ($backParent): ?>
            <input type="hidden" name="back" value="parent">
        <?php endif; ?>

        <h2><?= e(__('edit_child_name')) ?></h2>
        <div class="grid-3">
            <label><?= e(__('first_name')) ?>
                <input type="text" name="first_name" required value="<?= e((string) $child['first_name']) ?>">
            </label>
            <label><?= e(__('last_name')) ?>
                <input type="text" name="last_name" required value="<?= e((string) $child['last_name']) ?>">
            </label>
            <label><?= e(__('patronymic')) ?>
                <input type="text" name="patronymic" required value="<?= e((string) ($child['patronymic'] ?? '')) ?>">
            </label>
        </div>
        <p class="muted tiny"><?= e(__('edit_child_name_help')) ?></p>

        <h2 style="margin-top:22px"><?= e(__('edit_child_grade_sector')) ?></h2>
        <div class="grade-sector-alert" role="alert">
            <p><?= e(__('edit_child_grade_help')) ?></p>
        </div>
        <div class="grid-2">
            <label><?= e(__('current_grade')) ?>
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= (int) $child['grade'] === $g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('sector')) ?>
                <select name="sector" required>
                    <option value="az" <?= $child['sector'] === 'az' ? 'selected' : '' ?>><?= e(__('sector_az_short')) ?></option>
                    <option value="ru" <?= $child['sector'] === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru_short')) ?></option>
                </select>
            </label>
        </div>

        <button class="btn" type="submit"><?= e(__('save_changes')) ?></button>
    </form>
</div>
