<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('admin')) ?> | <?= e(brand_name()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="panel-body">
<aside class="sidebar sidebar-admin">
    <a class="brand" href="<?= url('/admin') ?>"><?= brand_html('nav') ?> <span class="brand-admin">Admin</span></a>
    <nav>
        <a href="<?= url('/admin') ?>"><?= e(__('dashboard')) ?></a>
        <a href="<?= url('/admin/suallar') ?>"><?= e(__('question_bank')) ?></a>
        <a href="<?= url('/admin/suallar/elave') ?>"><?= e(__('add_question')) ?></a>
        <a href="<?= url('/admin/imtahanlar') ?>"><?= e(__('exams')) ?></a>
        <a href="<?= url('/admin/valideynler') ?>"><?= e(__('parents')) ?></a>
        <a href="<?= url('/admin/usaqlar') ?>"><?= e(__('children_list')) ?></a>
    </nav>
    <div class="sidebar-lang"><?= lang_switcher() ?></div>
    <form method="post" action="<?= url('/admin/logout') ?>" class="sidebar-logout"><?= csrf_field() ?><button type="submit"><?= e(__('logout')) ?></button></form>
</aside>
<div class="panel-main">
    <header class="panel-top">
        <h1><?= e($title ?? '') ?></h1>
        <div class="muted"><?= e(\App\Core\Session::get('admin_name', '')) ?></div>
    </header>
    <?php if ($msg = \App\Core\Session::flash('success')): ?>
        <div class="flash flash-success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = \App\Core\Session::flash('error')): ?>
        <div class="flash flash-error"><?= e($msg) ?></div>
    <?php endif; ?>
    <?= $content ?>
</div>
</body>
</html>


