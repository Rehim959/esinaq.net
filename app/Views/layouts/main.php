<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'eSınaq') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url('/') ?>">e<strong>Sınaq</strong></a>
        <nav class="nav">
            <?php if (\App\Core\Auth::parentId()): ?>
                <a href="<?= url('/valideyn') ?>">Panel</a>
                <form method="post" action="<?= url('/cixis') ?>" class="inline-form"><?= csrf_field() ?><button type="submit" class="link-btn">Çıxış</button></form>
            <?php elseif (\App\Core\Auth::adminId()): ?>
                <a href="<?= url('/admin') ?>">Admin</a>
            <?php else: ?>
                <a href="<?= url('/valideyn/giris') ?>">Giriş</a>
                <a class="btn btn-sm" href="<?= url('/qeydiyyat') ?>">Qeydiyyat</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if ($msg = \App\Core\Session::flash('success')): ?>
    <div class="flash flash-success container"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = \App\Core\Session::flash('error')): ?>
    <div class="flash flash-error container"><?= e($msg) ?></div>
<?php endif; ?>

<main>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div><strong>eSınaq</strong> — onlayn sınaq və yoxlama platforması</div>
        <div><a href="mailto:esinaq@esinaq.net">esinaq@esinaq.net</a></div>
    </div>
</footer>
</body>
</html>
