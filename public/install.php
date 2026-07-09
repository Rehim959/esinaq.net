<?php
/**
 * One-time hosting installer for eSınaq.net.
 * Creates .env, database tables, admin — then deletes itself.
 */
declare(strict_types=1);

if (is_dir(__DIR__ . '/app')) {
    define('BASE_PATH', __DIR__);
} else {
    define('BASE_PATH', dirname(__DIR__));
}
require BASE_PATH . '/app/helpers.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$lockFile = BASE_PATH . '/storage/install.lock';
$self = __FILE__;
$done = false;
$error = null;
$messages = [];
$adminEmailOut = '';
$adminPassOut = '';

if (is_file($lockFile)) {
    @unlink($self);
    http_response_code(403);
    echo page('Quraşdırma bağlıdır', '<p>Sayt artıq quraşdırılıb. <a href="/">Ana səhifəyə keç</a></p>');
    exit;
}

$defaults = [
    'app_url' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'esinaq.net'),
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',
    'mail_host' => 'mail.esinaq.net',
    'mail_port' => '587',
    'mail_user' => 'esinaq@esinaq.net',
    'mail_pass' => '',
    'mail_encryption' => 'tls',
    'admin_email' => 'admin@esinaq.net',
    'admin_password' => '',
    'admin_password2' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach ($defaults as $k => $v) {
        $data[$k] = trim((string) ($_POST[$k] ?? $v));
    }

    try {
        if ($data['db_name'] === '' || $data['db_user'] === '') {
            throw new RuntimeException('DB adı və istifadəçi mütləqdir.');
        }
        if ($data['admin_password'] === '' || strlen($data['admin_password']) < 10) {
            throw new RuntimeException('Admin şifrəsi ən azı 10 simvol olmalıdır.');
        }
        if ($data['admin_password'] !== $data['admin_password2']) {
            throw new RuntimeException('Admin şifrələri uyğun gəlmir.');
        }
        if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Admin e-poçtu düzgün deyil.');
        }
        if (is_placeholder_secret($data['admin_password'])) {
            throw new RuntimeException('Admin şifrəsi çox sadədir / placeholder-dır.');
        }

        $appSecret = bin2hex(random_bytes(24));
        $installToken = bin2hex(random_bytes(16));

        $env = <<<ENV
APP_ENV=production
APP_DEBUG=false
APP_URL={$data['app_url']}
APP_SECRET={$appSecret}
APP_NAME=eSınaq.net
INSTALL_TOKEN={$installToken}
SESSION_SECURE=true

DB_HOST={$data['db_host']}
DB_PORT={$data['db_port']}
DB_NAME={$data['db_name']}
DB_USER={$data['db_user']}
DB_PASS={$data['db_pass']}

MAIL_HOST={$data['mail_host']}
MAIL_PORT={$data['mail_port']}
MAIL_USER={$data['mail_user']}
MAIL_PASS={$data['mail_pass']}
MAIL_ENCRYPTION={$data['mail_encryption']}
MAIL_FROM={$data['mail_user']}
MAIL_FROM_NAME=eSınaq.net

ADMIN_EMAIL={$data['admin_email']}
ADMIN_PASSWORD={$data['admin_password']}
ENV;

        if (file_put_contents(BASE_PATH . '/.env', $env) === false) {
            throw new RuntimeException('.env yazıla bilmədi. Qovluq hüquqlarını yoxlayın.');
        }
        loadEnv(BASE_PATH . '/.env');

        $pdo = new PDO(
            "mysql:host={$data['db_host']};port={$data['db_port']};charset=utf8mb4",
            $data['db_user'],
            $data['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $safeDb = str_replace('`', '``', $data['db_name']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$safeDb}`");

        foreach (['schema.sql', 'seed.sql', 'sample_questions.sql'] as $file) {
            $path = BASE_PATH . '/database/' . $file;
            if (!is_file($path)) {
                continue;
            }
            $sql = file_get_contents($path);
            if ($sql === false) {
                continue;
            }
            $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $part) {
                if ($part !== '') {
                    $pdo->exec($part);
                }
            }
            $messages[] = $file . ' hazırdır';
        }

        try {
            $pdo->exec('ALTER TABLE children MODIFY password_hint VARCHAR(255) NOT NULL');
        } catch (Throwable) {
        }

        $hash = password_hash($data['admin_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
        $stmt->execute([$data['admin_email']]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE email = ?')->execute([$hash, $data['admin_email']]);
            $messages[] = 'Admin şifrəsi yeniləndi';
        } else {
            $pdo->prepare('INSERT INTO admins (email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 1)')
                ->execute([$data['admin_email'], $hash, 'Sistem Administratoru', 'super_admin']);
            $messages[] = 'Admin yaradıldı';
        }

        @mkdir(BASE_PATH . '/storage', 0750, true);
        @mkdir(BASE_PATH . '/storage/rate_limits', 0750, true);
        @mkdir(BASE_PATH . '/storage/logs', 0750, true);
        file_put_contents($lockFile, date('c') . " install completed\n");

        $adminEmailOut = $data['admin_email'];
        $adminPassOut = $data['admin_password'];
        $done = true;

        // Self-delete installer
        @unlink($self);
        if (is_file($self)) {
            $messages[] = 'Qeyd: install.php avtomatik silinmədi — hosting panelindən silin.';
        } else {
            $messages[] = 'install.php avtomatik silindi';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $defaults = array_merge($defaults, $data ?? []);
    }
}

function page(string $title, string $body): string
{
    return '<!DOCTYPE html><html lang="az"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
        . '<style>
body{font-family:system-ui,Segoe UI,sans-serif;max-width:640px;margin:32px auto;padding:0 16px;line-height:1.5;background:#f0f7ff;color:#0f2744}
.card{background:#fff;border:1px solid #c5daf0;border-radius:16px;padding:28px;box-shadow:0 16px 40px rgba(13,71,161,.1)}
h1{margin:0 0 8px;font-size:1.6rem}label{display:block;margin:12px 0 4px;font-weight:600;font-size:14px}
input,select{width:100%;padding:11px 12px;border:1.5px solid #c5daf0;border-radius:10px;font:inherit;box-sizing:border-box}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
button{margin-top:18px;width:100%;padding:14px;background:linear-gradient(135deg,#0d47a1,#1565c0,#0288d1);color:#fff;border:0;border-radius:12px;font-weight:700;font-size:1rem;cursor:pointer}
.ok{color:#00897b}.err{color:#c0392b;background:#fdecea;padding:12px;border-radius:10px}
.muted{color:#4a6280;font-size:14px}code{background:#e3f2fd;padding:2px 6px;border-radius:4px}
@media(max-width:600px){.grid{grid-template-columns:1fr}}
</style></head><body><div class="card">' . $body . '</div></body></html>';
}

if ($done) {
    $list = '';
    foreach ($messages as $m) {
        $list .= '<li>' . htmlspecialchars($m, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo page('Uğurlu', '
        <h1>eSınaq.net hazırdır</h1>
        <p class="ok"><strong>Quraşdırma tamamlandı.</strong></p>
        <ul>' . $list . '</ul>
        <p>Admin: <code>' . htmlspecialchars($adminEmailOut, ENT_QUOTES, 'UTF-8') . '</code></p>
        <p>Şifrə: <code>' . htmlspecialchars($adminPassOut, ENT_QUOTES, 'UTF-8') . '</code> <span class="muted">(indi yadda saxlayın)</span></p>
        <p><a href="/admin/login">Admin panelə keç →</a></p>
        <p><a href="/">Ana səhifə →</a></p>
    ');
    exit;
}

$d = $defaults;
$errHtml = $error ? '<p class="err">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>' : '';
echo page('Quraşdırma', '
    <h1>eSınaq.net quraşdırma</h1>
    <p class="muted">Bir dəfəlik forma. Bitəndən sonra install avtomatik silinir.</p>
    ' . $errHtml . '
    <form method="post" autocomplete="off">
        <label>Sayt URL</label>
        <input name="app_url" value="' . htmlspecialchars($d['app_url'], ENT_QUOTES, 'UTF-8') . '" required>
        <h3>MySQL</h3>
        <div class="grid">
            <div><label>Host</label><input name="db_host" value="' . htmlspecialchars($d['db_host'], ENT_QUOTES, 'UTF-8') . '" required></div>
            <div><label>Port</label><input name="db_port" value="' . htmlspecialchars($d['db_port'], ENT_QUOTES, 'UTF-8') . '" required></div>
        </div>
        <label>DB adı</label><input name="db_name" value="' . htmlspecialchars($d['db_name'], ENT_QUOTES, 'UTF-8') . '" required>
        <div class="grid">
            <div><label>DB user</label><input name="db_user" value="' . htmlspecialchars($d['db_user'], ENT_QUOTES, 'UTF-8') . '" required></div>
            <div><label>DB şifrə</label><input type="password" name="db_pass" value="' . htmlspecialchars($d['db_pass'], ENT_QUOTES, 'UTF-8') . '"></div>
        </div>
        <h3>E-poçt (SMTP)</h3>
        <div class="grid">
            <div><label>Mail host</label><input name="mail_host" value="' . htmlspecialchars($d['mail_host'], ENT_QUOTES, 'UTF-8') . '"></div>
            <div><label>Port</label><input name="mail_port" value="' . htmlspecialchars($d['mail_port'], ENT_QUOTES, 'UTF-8') . '"></div>
        </div>
        <div class="grid">
            <div><label>Mail user</label><input name="mail_user" value="' . htmlspecialchars($d['mail_user'], ENT_QUOTES, 'UTF-8') . '"></div>
            <div><label>Mail şifrə</label><input type="password" name="mail_pass" value="' . htmlspecialchars($d['mail_pass'], ENT_QUOTES, 'UTF-8') . '"></div>
        </div>
        <label>Encryption</label>
        <select name="mail_encryption">
            <option value="tls"' . ($d['mail_encryption'] === 'tls' ? ' selected' : '') . '>tls</option>
            <option value="ssl"' . ($d['mail_encryption'] === 'ssl' ? ' selected' : '') . '>ssl</option>
            <option value=""' . ($d['mail_encryption'] === '' ? ' selected' : '') . '>yox</option>
        </select>
        <h3>Admin</h3>
        <label>Admin e-poçt</label><input type="email" name="admin_email" value="' . htmlspecialchars($d['admin_email'], ENT_QUOTES, 'UTF-8') . '" required>
        <div class="grid">
            <div><label>Admin şifrə</label><input type="password" name="admin_password" required minlength="10"></div>
            <div><label>Şifrə təkrar</label><input type="password" name="admin_password2" required minlength="10"></div>
        </div>
        <button type="submit">Quraşdırmanı tamamla</button>
    </form>
');
