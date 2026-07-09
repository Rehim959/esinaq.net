<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/valideyn/giris') ?>">
        <?= csrf_field() ?>
        <h1><?= e(__('parent_login')) ?></h1>
        <label><?= e(__('email')) ?><input type="email" name="email" value="<?= old('email') ?>" required></label>
        <label><?= e(__('password')) ?><input type="password" name="password" required></label>
        <button class="btn btn-block" type="submit"><?= e(__('sign_in_btn')) ?></button>
        <p class="center muted">
            <a href="<?= url('/sifremi-unutdum') ?>"><?= e(__('forgot_password')) ?></a> ·
            <a href="<?= url('/qeydiyyat') ?>"><?= e(__('register')) ?></a>
        </p>
    </form>
</div>
