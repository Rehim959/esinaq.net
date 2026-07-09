<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/qeydiyyat') ?>">
        <?= csrf_field() ?>
        <h1><?= e(__('parent_register_title')) ?></h1>
        <div class="register-notice" role="note">
            <strong><?= e(__('parent_register_notice_title')) ?></strong>
            <p><?= e(__('parent_register_notice')) ?></p>
        </div>
        <div class="grid-3">
            <label><?= e(__('last_name')) ?><input type="text" name="last_name" value="<?= old('last_name') ?>" required></label>
            <label><?= e(__('first_name')) ?><input type="text" name="first_name" value="<?= old('first_name') ?>" required></label>
            <label><?= e(__('patronymic')) ?><input type="text" name="patronymic" value="<?= old('patronymic') ?>" required></label>
        </div>
        <label><?= e(__('email')) ?><input type="email" name="email" value="<?= old('email') ?>" required></label>
        <label><?= e(__('phone_required')) ?>
            <input type="tel" name="phone" value="<?= old('phone') ?>" required placeholder="+994501234567" autocomplete="tel">
        </label>
        <div class="grid-2">
            <label><?= e(__('password')) ?><input type="password" name="password" required minlength="6"></label>
            <label><?= e(__('password_confirm')) ?><input type="password" name="password_confirmation" required minlength="6"></label>
        </div>
        <button class="btn btn-block" type="submit"><?= e(__('register_btn')) ?></button>
        <p class="center muted"><?= e(__('already_have_account')) ?> <a href="<?= url('/valideyn/giris') ?>"><?= e(__('sign_in')) ?></a></p>
    </form>
</div>
