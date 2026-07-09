<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/imtahan/' . $token) ?>">
        <?= csrf_field() ?>
        <div class="exam-brand">eSınaq</div>
        <h1>Salam, <?= e($child['first_name']) ?>!</h1>
        <p class="muted">İmtahana daxil olmaq üçün şifrəni yazın.<br>Şifrə: <strong>Adınız + doğum iliniz</strong></p>
        <label>Şifrə<input type="text" name="password" placeholder="məs: Samir2015" required autocomplete="off"></label>
        <button class="btn btn-block" type="submit">Daxil ol</button>
    </form>
</div>
