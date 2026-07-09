<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/admin/login') ?>">
        <?= csrf_field() ?>
        <h1><?= e(__('admin_login')) ?></h1>
        <?php if ($msg = \App\Core\Session::flash('error')): ?>
            <div class="flash flash-error"><?= e($msg) ?></div>
        <?php endif; ?>
        <label><?= e(__('email')) ?><input type="email" name="email" value="admin@esinaq.net" required></label>
        <label><?= e(__('password')) ?><input type="password" name="password" required></label>
        <button class="btn btn-block" type="submit"><?= e(__('sign_in_btn')) ?></button>
    </form>
</div>
