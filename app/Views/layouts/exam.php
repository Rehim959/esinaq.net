<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('exam')) ?> | <?= e(brand_name()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/katex/katex.min.css') ?>">
</head>
<body class="exam-body">
<div class="lang-float"><?= lang_switcher() ?></div>
<?php if ($msg = \App\Core\Session::flash('error')): ?>
    <div class="flash flash-error container"><?= e($msg) ?></div>
<?php endif; ?>
<?= $content ?>
<script src="<?= asset('vendor/katex/katex.min.js') ?>" nonce="<?= e(csp_nonce()) ?>"></script>
<script src="<?= asset('vendor/katex/auto-render.min.js') ?>" nonce="<?= e(csp_nonce()) ?>"></script>
<script src="<?= asset('js/math-render.js') ?>" nonce="<?= e(csp_nonce()) ?>"></script>
</body>
</html>


