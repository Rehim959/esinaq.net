<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'İmtahan') ?> | eSınaq</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="exam-body">
<?php if ($msg = \App\Core\Session::flash('error')): ?>
    <div class="flash flash-error container"><?= e($msg) ?></div>
<?php endif; ?>
<?= $content ?>
</body>
</html>
