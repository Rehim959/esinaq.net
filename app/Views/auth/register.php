<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/qeydiyyat') ?>">
        <?= csrf_field() ?>
        <h1><?= e(__('parent_register_title')) ?></h1>
        <div class="register-notice" role="note">
            <strong><?= e(__('parent_register_notice_title')) ?></strong>
            <p><?= e(__('parent_register_notice')) ?></p>
        </div>
        <div class="grid-3">
            <label><?= e(__('first_name')) ?><input type="text" name="first_name" value="<?= old('first_name') ?>" required></label>
            <label><?= e(__('last_name')) ?><input type="text" name="last_name" value="<?= old('last_name') ?>" required></label>
            <label><?= e(__('patronymic')) ?><input type="text" name="patronymic" value="<?= old('patronymic') ?>" required></label>
        </div>
        <label><?= e(__('email')) ?><input type="email" name="email" value="<?= old('email') ?>" required></label>
        <label><?= e(__('phone_required')) ?>
            <div class="phone-row">
                <span class="phone-prefix">+994</span>
                <select name="phone_op" required aria-label="<?= e(__('phone_operator')) ?>">
                    <option value=""><?= e(__('phone_operator')) ?></option>
                    <?php foreach (phone_operators() as $op): ?>
                        <option value="<?= e($op) ?>" <?= old('phone_op') === $op ? 'selected' : '' ?>><?= e($op) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="tel" name="phone_num" value="<?= old('phone_num') ?>" required
                       maxlength="7" pattern="[0-9]{7}" inputmode="numeric"
                       placeholder="<?= e(__('phone_local_placeholder')) ?>" autocomplete="tel-national">
            </div>
            <span class="tiny muted"><?= e(__('phone_format_help')) ?></span>
        </label>
        <div class="grid-2">
            <label><?= e(__('password')) ?><input type="password" name="password" required minlength="8"></label>
            <label><?= e(__('password_confirm')) ?><input type="password" name="password_confirmation" required minlength="8"></label>
        </div>
        <button class="btn btn-block" type="submit"><?= e(__('register_btn')) ?></button>
        <p class="center muted"><?= e(__('already_have_account')) ?> <a href="<?= url('/valideyn/giris') ?>"><?= e(__('sign_in')) ?></a></p>
    </form>
</div>
