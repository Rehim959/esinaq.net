<?php
$oldBirth = trim((string) old('birth_date'));
if ($oldBirth === '' && old('birth_year') && old('birth_month') && old('birth_day')) {
    $oldBirth = sprintf(
        '%04d-%02d-%02d',
        (int) old('birth_year'),
        (int) old('birth_month'),
        (int) old('birth_day')
    );
}
$minBirth = sprintf('%04d-01-01', (int) date('Y') - 20);
$maxBirth = sprintf('%04d-12-31', (int) date('Y') - 5);
$oldGender = (string) (old('gender') ?: 'boy');
$oldSector = (string) (old('sector') ?: 'az');
$oldGrade = (string) (old('grade') ?: '');
?>
<div class="page narrow-form child-add-page">
    <form method="post" action="<?= url('/valideyn/usaq-elave') ?>" class="form-card child-add-form" id="childAddForm">
        <?= csrf_field() ?>

        <section class="child-add-section">
            <h2 class="child-add-section-title"><?= e(__('child_add_section_name')) ?></h2>
            <label class="child-add-field"><?= e(__('first_name')) ?>
                <input type="text" name="first_name" value="<?= e(old('first_name')) ?>" required autocomplete="given-name" enterkeyhint="next">
            </label>
            <label class="child-add-field"><?= e(__('last_name')) ?>
                <input type="text" name="last_name" value="<?= e(old('last_name')) ?>" required autocomplete="family-name" enterkeyhint="next">
            </label>
            <label class="child-add-field"><?= e(__('patronymic')) ?>
                <input type="text" name="patronymic" value="<?= e(old('patronymic')) ?>" required enterkeyhint="next">
            </label>
        </section>

        <section class="child-add-section">
            <h2 class="child-add-section-title"><?= e(__('birth_date')) ?></h2>
            <p class="child-add-hint"><?= e(__('child_add_birth_hint')) ?></p>
            <label class="child-add-field child-add-date">
                <span class="sr-only"><?= e(__('birth_date')) ?></span>
                <input type="date" name="birth_date" value="<?= e($oldBirth) ?>"
                       min="<?= e($minBirth) ?>" max="<?= e($maxBirth) ?>"
                       required>
            </label>
        </section>

        <section class="child-add-section">
            <h2 class="child-add-section-title"><?= e(__('current_grade')) ?></h2>
            <p class="child-add-hint"><?= e(__('child_add_grade_hint')) ?></p>
            <div class="choice-chips grade-chips" role="group" aria-label="<?= e(__('current_grade')) ?>">
                <?php foreach ($grades as $g): ?>
                    <label class="choice-chip">
                        <input type="radio" name="grade" value="<?= $g ?>" required
                            <?= $oldGrade === (string) $g ? 'checked' : '' ?>>
                        <span><?= (int) $g ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="child-add-section">
            <h2 class="child-add-section-title"><?= e(__('sector')) ?></h2>
            <div class="choice-chips choice-chips-2" role="group" aria-label="<?= e(__('sector')) ?>">
                <label class="choice-chip choice-chip-wide">
                    <input type="radio" name="sector" value="az" required <?= $oldSector === 'az' ? 'checked' : '' ?>>
                    <span><?= e(__('sector_az')) ?></span>
                </label>
                <label class="choice-chip choice-chip-wide">
                    <input type="radio" name="sector" value="ru" required <?= $oldSector === 'ru' ? 'checked' : '' ?>>
                    <span><?= e(__('sector_ru')) ?></span>
                </label>
            </div>
        </section>

        <section class="child-add-section">
            <h2 class="child-add-section-title"><?= e(__('gender')) ?></h2>
            <div class="choice-chips choice-chips-2" role="group" aria-label="<?= e(__('gender')) ?>">
                <label class="choice-chip choice-chip-wide">
                    <input type="radio" name="gender" value="boy" required <?= $oldGender === 'boy' ? 'checked' : '' ?>>
                    <span><?= e(__('boy')) ?></span>
                </label>
                <label class="choice-chip choice-chip-wide">
                    <input type="radio" name="gender" value="girl" required <?= $oldGender === 'girl' ? 'checked' : '' ?>>
                    <span><?= e(__('girl')) ?></span>
                </label>
            </div>
        </section>

        <p class="hint child-add-password-hint"><?= e(__('password_hint_help')) ?></p>

        <div class="child-add-submit">
            <button class="btn btn-block" type="submit"><?= e(__('add_and_email')) ?></button>
        </div>
    </form>
</div>
