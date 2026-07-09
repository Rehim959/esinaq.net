<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(brand_name()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="lang-pick-body">
<div class="lang-pick">
    <div class="lang-pick-brand"><?= brand_html('hero') ?></div>
    <h1>Dili seçin / Выберите язык</h1>
    <p>Saytın dilini seçin. Seçiminiz bütün səhifələrdə saxlanılacaq.<br>
       Выберите язык сайта. Ваш выбор сохранится на всех страницах.</p>
    <div class="lang-pick-actions">
        <a class="btn btn-lg" href="<?= url('/dil/az') ?>">Azərbaycan dili</a>
        <a class="btn btn-lg btn-ghost" href="<?= url('/dil/ru') ?>">Русский язык</a>
    </div>
</div>
</body>
</html>
