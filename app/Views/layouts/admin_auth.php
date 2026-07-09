<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('admin')) ?> | <?= e(brand_name()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="lang-float"><?= lang_switcher() ?></div>
<?= $content ?>
</body>
</html>


