<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/sifre-berpa') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <h1>Yeni şifrə</h1>
        <label>Yeni şifrə<input type="password" name="password" required minlength="6"></label>
        <label>Şifrə təkrar<input type="password" name="password_confirmation" required minlength="6"></label>
        <button class="btn btn-block" type="submit">Şifrəni yenilə</button>
    </form>
</div>
