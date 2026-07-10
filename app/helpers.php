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

/** Detect KaTeX / diagram markup in question fields. */
function question_looks_rich(string ...$parts): bool
{
    $joined = implode("\n", $parts);
    return (bool) preg_match('/\\\\\\(|\\\\\\[|<img\\s/i', $joined);
}

/**
 * Sanitize teacher-authored question HTML.
 * Allows: text, newlines→br, and <img src="/uploads/questions/..."> only.
 */
function sanitize_question_html(string $raw): string
{
    $raw = str_replace(["\r\n", "\r"], "\n", $raw);
    $imgs = [];
    $raw = (string) preg_replace_callback(
        '#<img\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>#i',
        static function (array $m) use (&$imgs): string {
            $src = $m[2];
            if (!preg_match('#^/uploads/questions/[a-zA-Z0-9._-]+\.(?:png|jpe?g|webp)$#', $src)) {
                return '';
            }
            $key = '%%IMG' . count($imgs) . '%%';
            $imgs[$key] = '<img src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '" alt="diagram" class="q-diagram">';
            return $key;
        },
        $raw
    );

    $escaped = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $escaped = nl2br($escaped, false);
    foreach ($imgs as $key => $tag) {
        $escaped = str_replace($key, $tag, $escaped);
    }
    return $escaped;
}

/** Render question/option for display (plain escaped or sanitized HTML). */
function render_question(?string $text, ?string $format = 'plain'): string
{
    $text = (string) $text;
    if ($format === 'html') {
        return $text;
    }
    return e($text);
}

/** Extract first /uploads/questions/... src from HTML, or empty. */
function question_img_src(?string $html): string
{
    if ($html === null || $html === '') {
        return '';
    }
    if (preg_match('#src\s*=\s*["\'](/uploads/questions/[a-zA-Z0-9._-]+\.(?:png|jpe?g|webp))["\']#i', $html, $m)) {
        return $m[1];
    }
    return '';
}

/** Plain text from question/option HTML with images stripped. */
function question_text_without_img(?string $html): string
{
    $html = (string) $html;
    if ($html === '') {
        return '';
    }
    $html = preg_replace('#<img\b[^>]*>#i', '', $html) ?? '';
    $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
    $html = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $html = preg_replace("/[ \t]+/u", ' ', $html) ?? $html;
    return trim($html);
}

/** True when any field contains an uploaded image. */
function question_is_image(array $q): bool
{
    foreach (['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e'] as $key) {
        if (str_contains((string) ($q[$key] ?? ''), '<img')) {
            return true;
        }
    }
    return false;
}

/** Absolute filesystem path for public uploads (public/ or flat public_html). */
function uploads_path(string $relative = ''): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
    $candidates = [
        $base . '/public/uploads',
        $base . '/uploads',
    ];
    $root = $candidates[0];
    foreach ($candidates as $c) {
        if (is_dir($c) || is_dir(dirname($c))) {
            // Prefer existing uploads dir; else public/uploads for local, uploads for flat
            if (is_dir($c)) {
                $root = $c;
                break;
            }
        }
    }
    // Flat deploy: BASE_PATH is public_html → use BASE_PATH/uploads
    if (is_dir($base . '/app') && !is_dir($base . '/public')) {
        $root = $base . '/uploads';
    } elseif (is_dir($base . '/public')) {
        $root = $base . '/public/uploads';
    } else {
        $root = $base . '/uploads';
    }
    $rel = ltrim(str_replace('\\', '/', $relative), '/');
    return $rel === '' ? $root : $root . '/' . $rel;
}

/** Normalize text for duplicate question detection. */
function question_normalize_text(string $value): string
{
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = mb_strtolower($value, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return trim($value);
}

/** Fingerprint of question + options (same subject/grade/sector compared separately). */
function question_fingerprint(
    string $questionText,
    string $a,
    string $b,
    string $c,
    string $d,
    ?string $e = null
): string {
    return hash('sha256', implode("\n", [
        question_normalize_text($questionText),
        question_normalize_text($a),
        question_normalize_text($b),
        question_normalize_text($c),
        question_normalize_text($d),
        question_normalize_text((string) $e),
    ]));
}

/** Short preview for admin reports. */
function question_preview_text(string $text, int $max = 90): string
{
    $plain = question_normalize_text($text);
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($plain, 0, $max, '…', 'UTF-8');
    }
    return strlen($plain) > $max ? substr($plain, 0, $max - 1) . '…' : $plain;
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
    $rel = ltrim($path, '/');
    $url = app_base_url() . '/assets/' . $rel;
    // Cache-bust so design updates show after deploy (public/ or flat public_html)
    $candidates = [
        (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/public/assets/' . $rel,
        (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/assets/' . $rel,
    ];
    foreach ($candidates as $file) {
        if (is_file($file)) {
            return $url . '?v=' . filemtime($file);
        }
    }
    return $url . '?v=20260710';
}

/** Resolve current request path for the router (pretty URL or ?r= / POST r). */
function request_path(): string
{
    foreach (['r'] as $key) {
        if (isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '') {
            $path = '/' . ltrim($_GET[$key], '/');
            return $path === '/' ? '/' : rtrim($path, '/');
        }
    }
    // Some hosts strip query string on POST — accept hidden r field
    if (
        strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
        && isset($_POST['r']) && is_string($_POST['r']) && $_POST['r'] !== ''
    ) {
        $path = '/' . ltrim($_POST['r'], '/');
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

/** Full name: Ad Soyad Ata adı */
function person_full_name(array $row): string
{
    $parts = array_filter([
        trim((string) ($row['first_name'] ?? '')),
        trim((string) ($row['last_name'] ?? '')),
        trim((string) ($row['patronymic'] ?? '')),
    ], static fn ($p) => $p !== '');
    return $parts === [] ? '—' : implode(' ', $parts);
}

/** AZ mobile operators for registration UI */
function phone_operators(): array
{
    return ['50', '51', '55', '60', '70', '77', '99', '10'];
}

/**
 * Build phone from +994 + operator + 7-digit local number.
 * Also accepts a full number string via normalize_phone fallback.
 */
function build_az_phone(string $operator, string $local, string $fallbackFull = ''): ?string
{
    $op = preg_replace('/\D+/', '', $operator) ?? '';
    $local = preg_replace('/\D+/', '', $local) ?? '';
    if (in_array($op, phone_operators(), true) && strlen($local) === 7) {
        return '+994' . $op . $local;
    }
    if ($fallbackFull !== '') {
        return normalize_phone($fallbackFull);
    }
    return null;
}

/** Normalize / validate AZ mobile: returns +994... or null */
function normalize_phone(string $phone): ?string
{
    $raw = trim($phone);
    if ($raw === '') {
        return null;
    }
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if (str_starts_with($digits, '994') && strlen($digits) === 12) {
        return '+' . $digits;
    }
    if (str_starts_with($digits, '0') && strlen($digits) === 10) {
        return '+994' . substr($digits, 1);
    }
    if (strlen($digits) === 9) {
        return '+994' . $digits;
    }
    if (strlen($digits) >= 10 && strlen($digits) <= 15) {
        return '+' . $digits;
    }
    return null;
}

/** Hidden `r` field so GET filter forms keep query-string routing */
function route_hidden(string $path): string
{
    $path = '/' . ltrim($path, '/');
    return '<input type="hidden" name="r" value="' . e($path) . '">';
}

function form_get_action(): string
{
    return app_base_url() . '/index.php';
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
