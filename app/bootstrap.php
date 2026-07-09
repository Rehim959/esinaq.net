<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once BASE_PATH . '/app/helpers.php';

loadEnv(BASE_PATH . '/.env');

// Allow Docker env vars to override .env
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'MAIL_HOST', 'MAIL_PORT', 'APP_URL', 'APP_SECRET'] as $key) {
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        $_ENV[$key] = $v;
    }
}

\date_default_timezone_set('Asia/Baku');

\App\Core\Session::start();

// Ensure admin exists with correct password on first boot
try {
    $pdo = \App\Core\Database::connection();
    $adminEmail = (string) env('ADMIN_EMAIL', 'admin@esinaq.net');
    $adminPass = (string) env('ADMIN_PASSWORD', 'Admin123!');
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE email = ?');
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch();
    if (!$admin) {
        $pdo->prepare('INSERT INTO admins (email, password_hash, full_name) VALUES (?, ?, ?)')
            ->execute([$adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), 'Sistem Administratoru']);
    } elseif (!password_verify($adminPass, $admin['password_hash'])) {
        // Only auto-fix in local when hash is placeholder/broken
        if (env('APP_ENV') === 'local') {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($adminPass, PASSWORD_DEFAULT), $admin['id']]);
        }
    }

    // Ensure subjects exist
    $count = (int) $pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
    if ($count === 0) {
        $subjects = [
            ['azerbaycan_dili', 'Azərbaycan dili', 'Азербайджанский язык', 1],
            ['edebiyyat', 'Ədəbiyyat', 'Литература', 2],
            ['riyaziyyat', 'Riyaziyyat', 'Математика', 3],
            ['tarix', 'Tarix', 'История', 4],
            ['cografiya', 'Coğrafiya', 'География', 5],
            ['rus_dili', 'Rus dili', 'Русский язык', 6],
            ['ingilis_dili', 'İngilis dili', 'Английский язык', 7],
            ['mentiq', 'Məntiq', 'Логика', 8],
        ];
        $ins = $pdo->prepare('INSERT INTO subjects (code, name_az, name_ru, sort_order) VALUES (?, ?, ?, ?)');
        foreach ($subjects as $s) {
            $ins->execute($s);
        }
    }
} catch (\Throwable) {
    // DB may not be ready yet during first docker boot
}
