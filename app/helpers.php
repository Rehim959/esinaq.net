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

function grade_label(int $grade): string
{
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
    return $sector === 'ru' ? 'Rus sektoru' : 'Azərbaycan sektoru';
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
    return match ($letter) {
        'A' => 'Əla! Nəticəniz möhtəşəmdir.',
        'B' => 'Yaxşı! Belə davam edin.',
        'C' => 'Orta. Bir az daha çalışsanız daha yaxşı olacaq.',
        'D' => 'Qane bəxş. Zəif mövzular üzrə təkrar edin.',
        'E' => 'Zəif. İmtahandan keçmədiniz; daha diqqətli oxumağınız tövsiyə olunur.',
        default => 'Kəskin zəif — müəllimlə əlaqə saxlayın.',
    };
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
        'azerbaycan_dili' => 'Azərbaycan dili',
        'edebiyyat' => 'Ədəbiyyat',
        'riyaziyyat' => 'Riyaziyyat',
        'tarix' => 'Tarix',
        'cografiya' => 'Coğrafiya',
        'rus_dili' => 'Rus dili',
        'ingilis_dili' => 'İngilis dili',
        'mentiq' => 'Məntiq',
    ];
}
