<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/qeydiyyat') ?>">
        <?= csrf_field() ?>
        <h1>Valideyn qeydiyyatı</h1>
        <p class="muted">Hesab yaradın — övladlarınızı əlavə edib nəticələri izləyin.</p>
        <div class="grid-2">
            <label>Ad<input type="text" name="first_name" value="<?= old('first_name') ?>" required></label>
            <label>Soyad<input type="text" name="last_name" value="<?= old('last_name') ?>" required></label>
        </div>
        <label>E-poçt<input type="email" name="email" value="<?= old('email') ?>" required></label>
        <label>Telefon (istəyə görə)<input type="text" name="phone" value="<?= old('phone') ?>"></label>
        <div class="grid-2">
            <label>Şifrə<input type="password" name="password" required minlength="6"></label>
            <label>Şifrə təkrar<input type="password" name="password_confirmation" required minlength="6"></label>
        </div>
        <button class="btn btn-block" type="submit">Qeydiyyatdan keç</button>
        <p class="center muted">Artıq hesabınız var? <a href="<?= url('/valideyn/giris') ?>">Daxil olun</a></p>
    </form>
</div>
