<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('parent_panel')) ?> | eSınaq</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="panel-body">
<aside class="sidebar">
    <a class="brand" href="<?= url('/valideyn') ?>"><span class="brand-e">e</span><strong>Sınaq</strong></a>
    <nav>
        <a href="<?= url('/valideyn') ?>"><?= e(__('overview')) ?></a>
        <a href="<?= url('/valideyn/usaq-elave') ?>"><?= e(__('add_child')) ?></a>
        <a href="<?= url('/') ?>"><?= e(__('home')) ?></a>
    </nav>
    <div class="sidebar-lang"><?= lang_switcher() ?></div>
    <form method="post" action="<?= url('/cixis') ?>" class="sidebar-logout"><?= csrf_field() ?><button type="submit"><?= e(__('logout')) ?></button></form>
</aside>
<div class="panel-main">
    <header class="panel-top">
        <h1><?= e($title ?? '') ?></h1>
        <div class="muted"><?= e(\App\Core\Session::get('parent_name', '')) ?></div>
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


