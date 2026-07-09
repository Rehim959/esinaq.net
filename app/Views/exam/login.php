<div class="auth-wrap">
    <form class="auth-card" method="post" action="<?= url('/imtahan/' . $token) ?>">
        <?= csrf_field() ?>
        <div class="exam-brand"><span class="brand-e">e</span>Sınaq</div>
        <h1><?= e(__('hello_name', ['name' => $child['first_name']])) ?></h1>
        <p class="muted"><?= e(__('exam_password_help')) ?><br><?= e(__('exam_password_format')) ?></p>
        <label><?= e(__('password')) ?><input type="text" name="password" placeholder="<?= e(__('exam_password_example')) ?>" required autocomplete="off"></label>
        <button class="btn btn-block" type="submit"><?= e(__('sign_in_btn')) ?></button>
    </form>
</div>
