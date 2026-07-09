<div class="page narrow-form">
    <form method="post" action="<?= url('/valideyn/usaq-elave') ?>" class="form-card">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label><?= e(__('first_name')) ?><input type="text" name="first_name" value="<?= old('first_name') ?>" required></label>
            <label><?= e(__('last_name')) ?><input type="text" name="last_name" value="<?= old('last_name') ?>" required></label>
        </div>

        <fieldset class="birth-fieldset">
            <legend><?= e(__('birth_date')) ?></legend>
            <div class="grid-3">
                <label><?= e(__('day')) ?>
                    <select name="birth_day" required>
                        <option value=""><?= e(__('day')) ?></option>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>" <?= old('birth_day') == (string)$d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label><?= e(__('month')) ?>
                    <select name="birth_month" required>
                        <option value=""><?= e(__('month')) ?></option>
                        <?php
                        $months = locale() === 'ru'
                            ? [1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь']
                            : [1=>'Yanvar',2=>'Fevral',3=>'Mart',4=>'Aprel',5=>'May',6=>'İyun',7=>'İyul',8=>'Avqust',9=>'Sentyabr',10=>'Oktyabr',11=>'Noyabr',12=>'Dekabr'];
                        foreach ($months as $num => $label):
                        ?>
                            <option value="<?= $num ?>" <?= old('birth_month') == (string)$num ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e(__('year')) ?>
                    <select name="birth_year" required>
                        <option value=""><?= e(__('year')) ?></option>
                        <?php for ($y = (int)date('Y') - 5; $y >= (int)date('Y') - 20; $y--): ?>
                            <option value="<?= $y ?>" <?= old('birth_year') == (string)$y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
        </fieldset>

        <div class="grid-2">
            <label><?= e(__('current_grade')) ?>
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= old('grade') == (string)$g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('sector')) ?>
                <select name="sector" required>
                    <option value="az" <?= old('sector') === 'az' ? 'selected' : '' ?>><?= e(__('sector_az')) ?></option>
                    <option value="ru" <?= old('sector') === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru')) ?></option>
                </select>
            </label>
        </div>
        <label><?= e(__('gender')) ?>
            <select name="gender" required>
                <option value="boy" <?= old('gender') === 'boy' ? 'selected' : '' ?>><?= e(__('boy')) ?></option>
                <option value="girl" <?= old('gender') === 'girl' ? 'selected' : '' ?>><?= e(__('girl')) ?></option>
            </select>
        </label>
        <p class="hint"><?= e(__('password_hint_help')) ?></p>
        <button class="btn" type="submit"><?= e(__('add_and_email')) ?></button>
    </form>
</div>
