<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin') ?> | eSınaq</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="panel-body">
<aside class="sidebar sidebar-admin">
    <a class="brand" href="<?= url('/admin') ?>">e<strong>Sınaq</strong> Admin</a>
    <nav>
        <a href="<?= url('/admin') ?>">Dashboard</a>
        <a href="<?= url('/admin/suallar') ?>">Sual bankı</a>
        <a href="<?= url('/admin/suallar/elave') ?>">Sual əlavə et</a>
        <a href="<?= url('/admin/imtahanlar') ?>">İmtahanlar</a>
        <a href="<?= url('/admin/valideynler') ?>">Valideynlər</a>
    </nav>
    <form method="post" action="<?= url('/admin/logout') ?>" class="sidebar-logout"><?= csrf_field() ?><button type="submit">Çıxış</button></form>
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
