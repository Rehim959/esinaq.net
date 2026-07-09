<?php

declare(strict_types=1);

// Shared hosting: works when docroot is /public OR when all files sit in public_html
if (is_dir(__DIR__ . '/app')) {
    define('BASE_PATH', __DIR__);
} else {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/app/bootstrap.php';

$path = request_path();

$isLangRoute = (bool) preg_match('#^/dil/(az|ru)$#', $path);
$isInstall = str_starts_with($path, '/install');

if (!\App\Core\Lang::hasLocale() && !$isLangRoute && !$isInstall) {
    \App\Core\View::render('home/language', ['title' => brand_name()], null);
    exit;
}

$router = require BASE_PATH . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
