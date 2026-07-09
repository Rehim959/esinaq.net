<?php
/**
 * One-time installer for shared hosting.
 * Open https://esinaq.net/install.php once, then DELETE this file.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/helpers.php';
loadEnv(BASE_PATH . '/.env');

header('Content-Type: text/html; charset=utf-8');

$done = false;
$error = null;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $host = (string) env('DB_HOST', 'localhost');
        $port = (string) env('DB_PORT', '3306');
        $name = (string) env('DB_NAME');
        $user = (string) env('DB_USER');
        $pass = (string) env('DB_PASS');

        if ($name === '' || $user === '') {
            throw new RuntimeException('.env faylında DB_NAME və DB_USER yazılmalıdır.');
        }

        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . str_replace('`', '``', $name) . '`');

        foreach (['schema.sql', 'seed.sql', 'sample_questions.sql'] as $file) {
            $sql = file_get_contents(BASE_PATH . '/database/' . $file);
            if ($sql === false) {
                continue;
            }
            $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
            $pdo->exec($sql);
            while ($pdo->nextRowset()) {
            }
            $messages[] = $file . ' icra olundu';
        }

        $adminEmail = (string) env('ADMIN_EMAIL', 'admin@esinaq.net');
        $adminPass = (string) env('ADMIN_PASSWORD', 'Admin123!');
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
        $stmt->execute([$adminEmail]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE email = ?')->execute([$hash, $adminEmail]);
            $messages[] = 'Admin şifrəsi yeniləndi';
        } else {
            $pdo->prepare('INSERT INTO admins (email, password_hash, full_name) VALUES (?, ?, ?)')
                ->execute([$adminEmail, $hash, 'Sistem Administratoru']);
            $messages[] = 'Admin yaradıldı: ' . $adminEmail;
        }

        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="az">
<head><meta charset="UTF-8"><title>eSınaq Install</title>
<style>body{font-family:system-ui;max-width:560px;margin:40px auto;padding:0 16px;line-height:1.5}button{padding:12px 20px;background:#0b6e4f;color:#fff;border:0;border-radius:8px;font-weight:700;cursor:pointer}.ok{color:#0b6e4f}.err{color:#c0392b;background:#fdecea;padding:12px;border-radius:8px}</style>
</head>
<body>
<h1>eSınaq quraşdırma</h1>
<p><code>.env</code> faylındakı DB məlumatları ilə cədvəlləri yaradır.</p>
<?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($done): ?>
    <p class="ok"><strong>Uğurlu!</strong></p>
    <ul><?php foreach ($messages as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul>
    <p><strong>İndi bu faylı (public/install.php) silin!</strong></p>
    <p><a href="/admin/login">Admin panelə keç</a></p>
<?php else: ?>
    <form method="post"><button type="submit">Quraşdırmanı başlat</button></form>
<?php endif; ?>
</body>
</html>
