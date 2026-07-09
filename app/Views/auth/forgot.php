<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/sifremi-unutdum') ?>">
        <?= csrf_field() ?>
        <h1><?= e(__('forgot_password')) ?></h1>
        <p class="muted"><?= e(__('forgot_lead_plain')) ?> <strong>esinaq@esinaq.net</strong></p>
        <label><?= e(__('email')) ?><input type="email" name="email" required></label>
        <button class="btn btn-block" type="submit"><?= e(__('send_reset_link')) ?></button>
        <p class="center muted"><a href="<?= url('/valideyn/giris') ?>"><?= e(__('back_to_login')) ?></a></p>
    </form>
</div>
