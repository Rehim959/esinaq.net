<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header_remove('X-Powered-By');
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($proto === 'https') {
            return true;
        }
        if (str_starts_with((string) env('APP_URL', ''), 'https://')) {
            return true;
        }
        return (bool) env('SESSION_SECURE', false);
    }

    public static function sanitizeHeaderValue(string $value): string
    {
        return str_replace(["\r", "\n", "\0"], '', $value);
    }

    public static function safeRedirectPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || str_starts_with($path, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) {
            return '/';
        }
        if (!str_starts_with($path, '/')) {
            return '/';
        }
        return $path;
    }
}
