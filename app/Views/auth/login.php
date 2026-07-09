<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/valideyn/giris') ?>">
        <?= csrf_field() ?>
        <h1>Valideyn girişi</h1>
        <label>E-poçt<input type="email" name="email" value="<?= old('email') ?>" required></label>
        <label>Şifrə<input type="password" name="password" required></label>
        <button class="btn btn-block" type="submit">Daxil ol</button>
        <p class="center muted">
            <a href="<?= url('/sifremi-unutdum') ?>">Şifrəmi unutdum</a> ·
            <a href="<?= url('/qeydiyyat') ?>">Qeydiyyat</a>
        </p>
    </form>
</div>
