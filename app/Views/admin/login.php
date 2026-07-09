<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/admin/login') ?>">
        <?= csrf_field() ?>
        <h1>Admin girişi</h1>
        <?php if ($msg = \App\Core\Session::flash('error')): ?>
            <div class="flash flash-error"><?= e($msg) ?></div>
        <?php endif; ?>
        <label>E-poçt<input type="email" name="email" value="admin@esinaq.net" required></label>
        <label>Şifrə<input type="password" name="password" required></label>
        <button class="btn btn-block" type="submit">Daxil ol</button>
    </form>
</div>
