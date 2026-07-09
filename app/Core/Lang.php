<?php

declare(strict_types=1);

namespace App\Core;

final class Lang
{
    private static string $locale = 'az';
    /** @var array<string, string> */
    private static array $lines = [];
    private static bool $loaded = false;

    public static function boot(): void
    {
        $locale = Session::get('locale');
        if (!is_string($locale) || !in_array($locale, ['az', 'ru'], true)) {
            $locale = null;
        }
        if ($locale === null) {
            self::$locale = 'az';
            self::$loaded = false;
            return;
        }
        self::setLocale($locale);
    }

    public static function hasLocale(): bool
    {
        $locale = Session::get('locale');
        return is_string($locale) && in_array($locale, ['az', 'ru'], true);
    }

    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, ['az', 'ru'], true)) {
            $locale = 'az';
        }
        self::$locale = $locale;
        Session::set('locale', $locale);
        self::$loaded = false;
        self::load();
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function get(string $key, array $replace = []): string
    {
        self::load();
        $text = self::$lines[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }
        return $text;
    }

    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        $file = BASE_PATH . '/lang/' . self::$locale . '.php';
        if (!is_file($file)) {
            $file = BASE_PATH . '/lang/az.php';
        }
        /** @var array<string, string> $lines */
        $lines = require $file;
        self::$lines = $lines;
        self::$loaded = true;
    }
}
