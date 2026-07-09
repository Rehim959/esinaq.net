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

date_default_timezone_set('Asia/Baku');

\App\Core\Security::sendHeaders();
\App\Core\Session::start();
\App\Core\Lang::boot();

// Ensure admin exists on first boot (create only — never auto-reset password in production)
try {
    $pdo = \App\Core\Database::connection();
    $adminEmail = (string) env('ADMIN_EMAIL', 'admin@esinaq.net');
    $adminPass = (string) env('ADMIN_PASSWORD', '');
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch();
    if (!$admin && $adminPass !== '' && $adminPass !== 'CHANGE_ME') {
        $pdo->prepare('INSERT INTO admins (email, password_hash, full_name) VALUES (?, ?, ?)')
            ->execute([$adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), 'Sistem Administratoru']);
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

    // Widen password_hint for bcrypt hashes (safe if already applied)
    try {
        $pdo->exec('ALTER TABLE children MODIFY password_hint VARCHAR(255) NOT NULL');
    } catch (\Throwable) {
        // ignore if no permission / already correct
    }
} catch (\Throwable) {
    // DB may not be ready yet during first docker boot
}
