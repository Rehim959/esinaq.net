<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    private static ?string $cspNonce = null;

    public static function cspNonce(): string
    {
        if (self::$cspNonce === null) {
            self::$cspNonce = base64_encode(random_bytes(16));
        }
        return self::$cspNonce;
    }

    public static function forceHttps(): void
    {
        if (headers_sent() || self::isLocalHost()) {
            return;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $requestHttps = self::requestIsHttps();
        $appUrlHttps = str_starts_with((string) env('APP_URL', ''), 'https://');

        // Same-host HTTP → HTTPS. Skip when APP_URL is already https
        // (TLS often terminated at nginx without X-Forwarded-Proto).
        if (!$requestHttps && !$appUrlHttps) {
            header('Location: https://' . $host . $uri, true, 301);
            exit;
        }

        // Apex → www always on HTTPS
        if ($host === 'esinaq.net') {
            header('Location: https://www.esinaq.net' . $uri, true, 301);
            exit;
        }
    }

    /** Actual request over TLS (ignore APP_URL). */
    public static function requestIsHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($proto === 'https') {
            return true;
        }
        return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }

    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        $nonce = self::cspNonce();

        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Cross-Origin-Opener-Policy: same-origin');
        // No unsafe-inline in script-src — nonce only
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'self'; "
            . "img-src 'self' data:; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "script-src 'self' 'nonce-{$nonce}'; "
            . "connect-src 'self'"
        );

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
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if (str_starts_with((string) env('APP_URL', ''), 'https://')) {
            return true;
        }
        return (bool) env('SESSION_SECURE', false);
    }

    public static function isLocalHost(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return str_starts_with($host, 'localhost')
            || str_starts_with($host, '127.0.0.1')
            || str_ends_with($host, '.local');
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
