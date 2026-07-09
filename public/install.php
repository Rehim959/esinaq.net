<?php
/**
 * One-time installer for shared hosting.
 * Protect with INSTALL_TOKEN in .env, then DELETE this file after use.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/helpers.php';
loadEnv(BASE_PATH . '/.env');

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$lockFile = BASE_PATH . '/storage/install.lock';
$installToken = (string) env('INSTALL_TOKEN', '');
$done = false;
$error = null;
$messages = [];

if (is_file($lockFile)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="az"><head><meta charset="UTF-8"><title>Locked</title></head><body style="font-family:system-ui;max-width:560px;margin:40px auto;padding:0 16px">';
    echo '<h1>Quraşdırma bağlıdır</h1><p>install.lock mövcuddur. Təhlükəsizlik üçün <code>public/install.php</code> faylını silin.</p></body></html>';
    exit;
}

if ($installToken === '' || $installToken === 'CHANGE_ME') {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="az"><head><meta charset="UTF-8"><title>Install</title></head><body style="font-family:system-ui;max-width:560px;margin:40px auto;padding:0 16px">';
    echo '<h1>Install deaktivdir</h1><p><code>.env</code> faylında güclü <code>INSTALL_TOKEN</code> təyin edin, sonra bu səhifəni yenidən açın.</p></body></html>';
    exit;
}

$provided = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
if (!hash_equals($installToken, $provided)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="az"><head><meta charset="UTF-8"><title>Install</title></head><body style="font-family:system-ui;max-width:560px;margin:40px auto;padding:0 16px">';
    echo '<h1>Token tələb olunur</h1><p>URL: <code>/install.php?token=YOUR_INSTALL_TOKEN</code></p></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $host = (string) env('DB_HOST', 'localhost');
        $port = (string) env('DB_PORT', '3306');
        $name = (string) env('DB_NAME');
        $user = (string) env('DB_USER');
        $pass = (string) env('DB_PASS');
        $adminPass = (string) env('ADMIN_PASSWORD', '');

        if ($name === '' || $user === '') {
            throw new RuntimeException('.env faylında DB_NAME və DB_USER yazılmalıdır.');
        }
        if ($adminPass === '' || $adminPass === 'CHANGE_ME' || $adminPass === 'Admin123!') {
            throw new RuntimeException('ADMIN_PASSWORD güclü və unikal olmalıdır (Admin123! qadağandır).');
        }

        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . str_replace('`', '``', $name) . '`');

        foreach (['schema.sql', 'seed.sql', 'sample_questions.sql'] as $file) {
            $sql = file_get_contents(BASE_PATH . '/database/' . $file);
            if ($sql === false) {
                continue;
            }
            $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
            // Split on semicolons carefully for multi-statement files without MULTI_STATEMENTS attr
            $parts = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($parts as $part) {
                if ($part !== '') {
                    $pdo->exec($part);
                }
            }
            $messages[] = $file . ' icra olundu';
        }

        try {
            $pdo->exec('ALTER TABLE children MODIFY password_hint VARCHAR(255) NOT NULL');
        } catch (Throwable) {
        }

        $adminEmail = (string) env('ADMIN_EMAIL', 'admin@esinaq.net');
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

        @mkdir(BASE_PATH . '/storage', 0750, true);
        file_put_contents($lockFile, date('c') . " install completed\n");
        $done = true;
    } catch (Throwable $e) {
        $error = env('APP_DEBUG') ? $e->getMessage() : 'Quraşdırma uğursuz oldu. .env və DB hüquqlarını yoxlayın.';
    }
}
?>
<!DOCTYPE html>
<html lang="az">
<head><meta charset="UTF-8"><title>eSınaq.net Install</title>
<style>body{font-family:system-ui;max-width:560px;margin:40px auto;padding:0 16px;line-height:1.5}button{padding:12px 20px;background:#1565c0;color:#fff;border:0;border-radius:8px;font-weight:700;cursor:pointer}.ok{color:#1565c0}.err{color:#c0392b;background:#fdecea;padding:12px;border-radius:8px}</style>
</head>
<body>
<h1>eSınaq.net quraşdırma</h1>
<p><code>.env</code> faylındakı DB məlumatları ilə cədvəlləri yaradır.</p>
<?php if ($error): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if ($done): ?>
    <p class="ok"><strong>Uğurlu!</strong></p>
    <ul><?php foreach ($messages as $m): ?><li><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
    <p><strong>İndi <code>public/install.php</code> faylını silin.</strong></p>
    <p><a href="/">Sayta keç</a></p>
<?php else: ?>
    <form method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($provided, ENT_QUOTES, 'UTF-8') ?>">
        <p>Bu əməliyyat cədvəlləri yaradır / yeniləyir və admin hesabını qurur.</p>
        <button type="submit">Quraşdırmanı başlat</button>
    </form>
<?php endif; ?>
</body>
</html>
