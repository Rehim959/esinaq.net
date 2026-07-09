<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/sifre-berpa') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <h1><?= e(__('new_password')) ?></h1>
        <label><?= e(__('new_password')) ?><input type="password" name="password" required minlength="6"></label>
        <label><?= e(__('password_confirm')) ?><input type="password" name="password_confirmation" required minlength="6"></label>
        <button class="btn btn-block" type="submit"><?= e(__('update_password')) ?></button>
    </form>
</div>
