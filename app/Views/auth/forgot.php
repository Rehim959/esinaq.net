<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/sifremi-unutdum') ?>">
        <?= csrf_field() ?>
        <h1>Şifrəni unutdum</h1>
        <p class="muted">E-poçtunuza <strong>esinaq@esinaq.net</strong> ünvanından bərpa linki göndəriləcək.</p>
        <label>E-poçt<input type="email" name="email" required></label>
        <button class="btn btn-block" type="submit">Bərpa linki göndər</button>
        <p class="center muted"><a href="<?= url('/valideyn/giris') ?>">Girişə qayıt</a></p>
    </form>
</div>
