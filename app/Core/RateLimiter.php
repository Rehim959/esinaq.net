<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple file-based rate limiter (shared hosting friendly, no Redis).
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $data = self::read($key);
        $now = time();

        if ($data === null || ($now - $data['start']) >= $decaySeconds) {
            return false;
        }

        return $data['hits'] >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds): int
    {
        $now = time();
        $data = self::read($key);

        if ($data === null || ($now - $data['start']) >= $decaySeconds) {
            $data = ['start' => $now, 'hits' => 0];
        }

        $data['hits']++;
        self::write($key, $data, $decaySeconds);

        return $data['hits'];
    }

    public static function clear(string $key): void
    {
        $file = self::path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function availableIn(string $key, int $decaySeconds): int
    {
        $data = self::read($key);
        if ($data === null) {
            return 0;
        }
        return max(0, $decaySeconds - (time() - $data['start']));
    }

    public static function clientKey(string $prefix): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (str_contains((string) $ip, ',')) {
            $ip = trim(explode(',', (string) $ip)[0]);
        }
        $ip = preg_replace('/[^a-fA-F0-9:.\-]/', '', (string) $ip) ?: 'unknown';
        return $prefix . ':' . $ip;
    }

    private static function dir(): string
    {
        $dir = BASE_PATH . '/storage/rate_limits';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir;
    }

    private static function path(string $key): string
    {
        return self::dir() . '/' . hash('sha256', $key) . '.json';
    }

    /** @return array{start:int,hits:int}|null */
    private static function read(string $key): ?array
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['start'], $data['hits'])) {
            return null;
        }
        return ['start' => (int) $data['start'], 'hits' => (int) $data['hits']];
    }

    /** @param array{start:int,hits:int} $data */
    private static function write(string $key, array $data, int $decaySeconds): void
    {
        $file = self::path($key);
        @file_put_contents($file, json_encode($data), LOCK_EX);
        @touch($file, time());
        // Best-effort cleanup of stale files
        if (random_int(1, 50) === 1) {
            self::gc($decaySeconds);
        }
    }

    private static function gc(int $decaySeconds): void
    {
        $dir = self::dir();
        $files = @scandir($dir);
        if ($files === false) {
            return;
        }
        $cutoff = time() - max($decaySeconds, 3600);
        foreach ($files as $f) {
            if (!str_ends_with($f, '.json')) {
                continue;
            }
            $path = $dir . '/' . $f;
            if (@filemtime($path) < $cutoff) {
                @unlink($path);
            }
        }
    }
}
