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

function redirect(string $path): never
{
    $url = str_starts_with($path, 'http') ? $path : rtrim((string) env('APP_URL', ''), '/') . $path;
    header('Location: ' . $url);
    exit;
}

function url(string $path = ''): string
{
    return rtrim((string) env('APP_URL', ''), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
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
        $percentage >= 90 => 'A',
        $percentage >= 80 => 'B',
        $percentage >= 70 => 'C',
        $percentage >= 60 => 'D',
        $percentage >= 50 => 'E',
        default => 'F',
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

function child_password(string $firstName, string $birthDate): string
{
    $year = date('Y', strtotime($birthDate));
    return $firstName . $year;
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
    $back = $_SERVER['REQUEST_URI'] ?? '/';
    $backPath = parse_url($back, PHP_URL_PATH) ?: '/';
    if (preg_match('#^/dil/(az|ru)$#', $backPath)) {
        $backPath = '/';
    }
    $q = '?back=' . urlencode($backPath);
    return '<div class="lang-switch" role="navigation" aria-label="' . e(__('language')) . '">'
        . '<a class="' . $azClass . '" href="' . e(url('/dil/az' . $q)) . '">AZ</a>'
        . '<a class="' . $ruClass . '" href="' . e(url('/dil/ru' . $q)) . '">RU</a>'
        . '</div>';
}
