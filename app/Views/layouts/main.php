<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('site_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="site-body">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url('/') ?>"><?= brand_html('nav') ?></a>
        <button class="nav-toggle" type="button" aria-label="Menu" id="navToggle" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav" id="siteNav">
            <?= lang_switcher() ?>
            <?php if (\App\Core\Auth::parentId()): ?>
                <a href="<?= url('/valideyn') ?>"><?= e(__('panel')) ?></a>
                <form method="post" action="<?= url('/cixis') ?>" class="inline-form"><?= csrf_field() ?><button type="submit" class="link-btn"><?= e(__('logout')) ?></button></form>
            <?php elseif (\App\Core\Auth::adminId()): ?>
                <a href="<?= url('/admin') ?>"><?= e(__('admin')) ?></a>
            <?php else: ?>
                <a href="#niye-biz" class="nav-quiet"><?= e(__('why_us')) ?></a>
                <a href="<?= url('/valideyn/giris') ?>" class="nav-link"><?= e(__('login')) ?></a>
                <a class="btn btn-sm" href="<?= url('/qeydiyyat') ?>"><?= e(__('register')) ?></a>
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
    <div class="container footer-grid">
        <div>
            <div class="brand footer-brand"><?= brand_html('nav') ?></div>
            <p class="muted"><?= e(__('site_tagline')) ?></p>
        </div>
        <div>
            <a href="mailto:esinaq@esinaq.net">esinaq@esinaq.net</a>
        </div>
        <div class="footer-legal">
            <div><?= e(__('created_by')) ?></div>
            <div><?= e(__('rights_reserved')) ?></div>
        </div>
    </div>
</footer>
<script>
(function () {
    var btn = document.getElementById('navToggle');
    var nav = document.getElementById('siteNav');
    if (!btn || !nav) return;
    btn.addEventListener('click', function () {
        var open = nav.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('open', open);
    });
})();
</script>
</body>
</html>
