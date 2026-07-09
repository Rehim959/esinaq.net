<?php

declare(strict_types=1);

// Shared hosting: works when docroot is /public OR when all files sit in public_html
if (is_dir(__DIR__ . '/app')) {
    define('BASE_PATH', __DIR__);
} else {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');
if ($path === '/') {
    $path = '/';
} else {
    $path = rtrim($path, '/') ?: '/';
}

$isLangRoute = (bool) preg_match('#^/dil/(az|ru)$#', $path);
$isInstall = str_starts_with($path, '/install');

if (!\App\Core\Lang::hasLocale() && !$isLangRoute && !$isInstall) {
    \App\Core\View::render('home/language', ['title' => brand_name()], null);
    exit;
}

$router = require BASE_PATH . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
