<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        if (!Lang::hasLocale()) {
            View::render('home/language', [
                'title' => 'eSınaq',
            ], null);
            return;
        }

        View::render('home/index', [
            'title' => __('home_title'),
        ]);
    }

    public function setLanguage(string $locale): void
    {
        if (!in_array($locale, ['az', 'ru'], true)) {
            $locale = 'az';
        }
        Lang::setLocale($locale);

        $back = $_GET['back'] ?? '/';
        if (!is_string($back)) {
            $back = '/';
        }
        redirect(\App\Core\Security::safeRedirectPath($back));
    }
}
