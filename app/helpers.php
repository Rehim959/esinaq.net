<?php

declare(strict_types=1);

/**
 * Simple .env loader — no Composer required (shared hosting friendly).
 */
function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, "\"'");
        if ($name === '') {
            continue;
        }
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => $value,
    };
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Display brand: eSınaq.net */
function brand_name(): string
{
    return 'eSınaq.net';
}

/** Reject copy-paste placeholders from .env.example */
function is_placeholder_secret(?string $value): bool
{
    $v = trim((string) $value);
    if ($v === '') {
        return true;
    }
    if (preg_match('/^CHANGE[_-]/i', $v)) {
        return true;
    }
    $blocked = [
        'Admin123!',
        'password',
        '123456',
        'your_db_password',
        'your_mail_password',
        'CHANGE_ME',
        'CHANGE_ME_STRONG_PASSWORD',
        'CHANGE_ME_TO_LONG_RANDOM_TOKEN',
        'CHANGE_THIS_TO_RANDOM_32_CHAR_STRING',
    ];
    return in_array($v, $blocked, true);
}

/** HTML brand mark for headers / hero */
function brand_html(string $variant = 'nav'): string
{
    $e = '<span class="brand-e">e</span>';
    $name = '<strong class="brand-name">Sınaq</strong>';
    $tld = '<span class="brand-tld">.net</span>';
    if ($variant === 'hero') {
        return '<span class="brand-lockup brand-lockup-hero">' . $e . $name . $tld . '</span>';
    }
    if ($variant === 'plain') {
        return $e . 'Sınaq' . $tld;
    }
    return '<span class="brand-lockup">' . $e . $name . $tld . '</span>';
}

function app_base_url(): string
{
    $configured = rtrim((string) env('APP_URL', ''), '/');
    if ($configured !== '' && !preg_match('#^https?://localhost#i', $configured) && !str_contains($configured, '127.0.0.1')) {
        // Always prefer HTTPS in production URLs
        if (str_starts_with($configured, 'http://')) {
            $configured = 'https://' . substr($configured, 7);
        }
        return $configured;
    }

    $https = \App\Core\Security::isHttps() || !\App\Core\Security::isLocalHost();
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'www.esinaq.net');
    if ($host === '' || str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
        $host = 'www.esinaq.net';
    }
    if (strcasecmp($host, 'esinaq.net') === 0) {
        $host = 'www.esinaq.net';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
}

function csp_nonce(): string
{
    return \App\Core\Security::cspNonce();
}

function redirect(string $path): void
{
    // Only allow relative app paths — block open redirects
    $safe = \App\Core\Security::safeRedirectPath($path);
    header('Location: ' . url($safe));
    exit;
}

/**
 * Build app URLs.
 * Uses /index.php?r=/path so nginx/Apache shared hosting works
 * without mod_rewrite and without PATH_INFO.
 */
function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return app_base_url() . '/';
    }

    // Keep extra query string if present (e.g. /sifre-berpa?token=...)
    $extra = '';
    if (str_contains($path, '?')) {
        [$path, $extra] = explode('?', $path, 2);
    }

    $out = app_base_url() . '/index.php?r=' . rawurlencode($path);
    if ($extra !== '') {
        $out .= '&' . $extra;
    }
    return $out;
}

function asset(string $path): string
{
    // Static files are real files under /assets — no front controller
    return app_base_url() . '/assets/' . ltrim($path, '/');
}

/** Resolve current request path for the router (pretty URL or ?r=). */
function request_path(): string
{
    if (isset($_GET['r']) && is_string($_GET['r']) && $_GET['r'] !== '') {
        $path = '/' . ltrim($_GET['r'], '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    // Strip /index.php if present
    if (preg_match('#/index\.php$#', $path)) {
        $path = '/';
    }

    $path = '/' . trim($path, '/');
    return $path === '/' ? '/' : rtrim($path, '/');
}

function csrf_field(): string
{
    $token = \App\Core\Session::csrfToken();
    return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}

function old(string $key, string $default = ''): string
{
    $old = \App\Core\Session::get('_old', []);
    return e((string) ($old[$key] ?? $default));
}

function flash_old(array $data): void
{
    unset($data['password'], $data['password_confirmation'], $data['password_hint'], $data['_csrf']);
    \App\Core\Session::set('_old', $data);
}

function clear_old(): void
{
    \App\Core\Session::remove('_old');
}

function __(string $key, array $replace = []): string
{
    return \App\Core\Lang::get($key, $replace);
}

function locale(): string
{
    return \App\Core\Lang::locale();
}

function grade_label(int $grade): string
{
    if (locale() === 'ru') {
        return __("grade_n", ['n' => $grade]);
    }
    $suffix = match ($grade) {
        3, 4 => '-cü',
        6 => '-cı',
        9, 10 => '-cu',
        default => '-ci',
    };
    return $grade . $suffix . ' sinif';
}

function sector_label(string $sector): string
{
    return $sector === 'ru' ? __('sector_ru') : __('sector_az');
}

function letter_grade(float $percentage): string
{
    return match (true) {
        $percentage >= 95 => 'A',
        $percentage >= 85 => 'B',
        $percentage >= 75 => 'C',
        $percentage >= 65 => 'D',
        default => 'E',
    };
}

function grade_message(string $letter): string
{
    $key = 'grade_msg_' . strtoupper($letter);
    return __($key);
}

function generate_token(int $bytes = 24): string
{
    return bin2hex(random_bytes($bytes));
}

/**
 * Child exam password: FirstName + birth year (product rule).
 * Always store via child_password_hash(); show plaintext only once (email/flash).
 */
function child_password(string $firstName, string $birthDate): string
{
    $year = date('Y', strtotime($birthDate));
    $base = preg_replace('/\s+/u', '', trim($firstName)) ?: 'Usaq';
    return $base . $year;
}

function child_password_hash(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

function child_password_display(?string $stored, ?string $firstName = null, ?string $birthDate = null): string
{
    if ($stored === null || $stored === '') {
        return '—';
    }
    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
        // Reconstruct display formula for parents (not a secret beyond name+year)
        if ($firstName && $birthDate) {
            return child_password($firstName, $birthDate);
        }
        return '••••••••';
    }
    return $stored;
}

function format_date(?string $datetime, string $format = 'd.m.Y H:i'): string
{
    if (!$datetime) {
        return '—';
    }
    return date($format, strtotime($datetime));
}

function grades_list(): array
{
    return range(1, 11);
}

function subjects_map(): array
{
    return [
        'azerbaycan_dili' => __('subj_azerbaycan_dili'),
        'edebiyyat' => __('subj_edebiyyat'),
        'riyaziyyat' => __('subj_riyaziyyat'),
        'tarix' => __('subj_tarix'),
        'cografiya' => __('subj_cografiya'),
        'rus_dili' => __('subj_rus_dili'),
        'ingilis_dili' => __('subj_ingilis_dili'),
        'mentiq' => __('subj_mentiq'),
    ];
}

function subject_name(array $subject): string
{
    return locale() === 'ru'
        ? (string) ($subject['name_ru'] ?? $subject['name_az'] ?? '')
        : (string) ($subject['name_az'] ?? '');
}

function lang_switcher(): string
{
    $current = locale();
    $azClass = $current === 'az' ? 'active' : '';
    $ruClass = $current === 'ru' ? 'active' : '';
    $back = request_path();
    if (preg_match('#^/dil/(az|ru)$#', $back)) {
        $back = '/';
    }
    $q = '?back=' . urlencode($back);
    return '<div class="lang-switch" role="navigation" aria-label="' . e(__('language')) . '">'
        . '<a class="' . $azClass . '" href="' . e(url('/dil/az' . $q)) . '">AZ</a>'
        . '<a class="' . $ruClass . '" href="' . e(url('/dil/ru' . $q)) . '">RU</a>'
        . '</div>';
}
