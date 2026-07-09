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
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        // Observatory needs: http://HOST → https://HOST (same host), then optional www hop.
        if (self::shouldRedirectHttpToHttps()) {
            header('Location: https://' . $host . $uri, true, 301);
            exit;
        }

        if ($host === 'esinaq.net' && self::requestIsHttps()) {
            header('Location: https://www.esinaq.net' . $uri, true, 301);
            exit;
        }
    }

    /**
     * Redirect plain HTTP → HTTPS on the same host.
     * Uses clear HTTP signals so HTTPS behind a TLS-terminating proxy does not loop.
     */
    public static function shouldRedirectHttpToHttps(): bool
    {
        if (self::requestIsHttps()) {
            return false;
        }

        $mode = strtolower((string) env('HTTPS_REDIRECT', 'auto'));
        if ($mode === 'off' || $mode === '0' || $mode === 'false') {
            return false;
        }
        if ($mode === 'always' || $mode === '1' || $mode === 'true') {
            return true;
        }

        // auto: only when we positively know the request is HTTP
        $port = (string) ($_SERVER['SERVER_PORT'] ?? '');
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $scheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));

        if ($proto === 'http') {
            return true;
        }
        if ($scheme === 'http') {
            return true;
        }
        if ($port === '80') {
            return true;
        }
        // HTTPS explicitly off and no https forward header
        if (($https === 'off' || $https === '') && $proto !== 'https' && $port !== '443') {
            // Last resort for Observatory: many shared hosts leave these empty on HTTP
            // Enable with HTTPS_REDIRECT=always in .env if auto is not enough.
            return (bool) env('FORCE_HTTPS_REDIRECT', false);
        }

        return false;
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
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
            return true;
        }
        $visitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
        if ($visitor !== '' && str_contains($visitor, '"scheme":"https"')) {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        return strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https';
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
        if (self::requestIsHttps()) {
            return true;
        }
        // Cookies / HSTS: trust APP_URL when proxy hides TLS
        if (str_starts_with((string) env('APP_URL', ''), 'https://')) {
            return true;
        }
        return (bool) env('SESSION_SECURE', false);
    }

    public static function isLocalHost(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        return $host === 'localhost'
            || $host === '127.0.0.1'
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
