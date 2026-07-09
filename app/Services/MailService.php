<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class MailService
{
    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $from = (string) env('MAIL_FROM', 'esinaq@esinaq.net');
        $fromName = (string) env('MAIL_FROM_NAME', 'eSınaq.net');
        $host = (string) env('MAIL_HOST', 'localhost');
        $port = (int) env('MAIL_PORT', 587);
        $user = (string) env('MAIL_USER', '');
        $pass = (string) env('MAIL_PASS', '');
        $encryption = (string) env('MAIL_ENCRYPTION', '');
        $subject = \App\Core\Security::sanitizeHeaderValue($subject);
        $to = \App\Core\Security::sanitizeHeaderValue($to);

        $ok = false;
        $error = null;

        try {
            if ($host === 'mailhog' || (int) env('MAIL_PORT') === 1025) {
                $ok = $this->sendSmtp($to, $subject, $htmlBody, $from, $fromName, $host, $port, $user, $pass, false);
            } else {
                $ok = $this->sendSmtp($to, $subject, $htmlBody, $from, $fromName, $host, $port, $user, $pass, $encryption === 'tls' || $encryption === 'ssl');
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $ok = false;
        }

        $this->log($to, $subject, $htmlBody, $ok, $error);
        return $ok;
    }

    private function sendSmtp(
        string $to,
        string $subject,
        string $htmlBody,
        string $from,
        string $fromName,
        string $host,
        int $port,
        string $user,
        string $pass,
        bool $useTls
    ): bool {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 15);
        if (!$socket) {
            // Fallback to PHP mail()
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $fromName . ' <' . $from . '>',
                'Reply-To: ' . $from,
            ];
            return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));
        }

        stream_set_timeout($socket, 15);
        $this->expect($socket, 220);

        $this->cmd($socket, 'EHLO esinaq.net');
        $this->read($socket);

        if ($useTls && $port !== 465) {
            $this->cmd($socket, 'STARTTLS');
            $this->expect($socket, 220);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd($socket, 'EHLO esinaq.net');
            $this->read($socket);
        }

        if ($user !== '') {
            $this->cmd($socket, 'AUTH LOGIN');
            $this->expect($socket, 334);
            $this->cmd($socket, base64_encode($user));
            $this->expect($socket, 334);
            $this->cmd($socket, base64_encode($pass));
            $this->expect($socket, 235);
        }

        $this->cmd($socket, 'MAIL FROM:<' . $from . '>');
        $this->expect($socket, 250);
        $this->cmd($socket, 'RCPT TO:<' . $to . '>');
        $this->expect($socket, 250);
        $this->cmd($socket, 'DATA');
        $this->expect($socket, 354);

        $message = "From: {$fromName} <{$from}>\r\n";
        $message .= "To: <{$to}>\r\n";
        $message .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody));
        $message .= "\r\n.";

        $this->cmd($socket, $message);
        $this->expect($socket, 250);
        $this->cmd($socket, 'QUIT');
        fclose($socket);
        return true;
    }

    /** @param resource $socket */
    private function cmd($socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    /** @param resource $socket */
    private function read($socket): string
    {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    }

    /** @param resource $socket */
    private function expect($socket, int $code): void
    {
        $response = $this->read($socket);
        if (!str_starts_with($response, (string) $code)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
    }

    private function log(string $to, string $subject, string $body, bool $ok, ?string $error): void
    {
        try {
            $pdo = Database::connection();
            $preview = mb_substr(strip_tags($body), 0, 400);
            // Never store credentials in email_logs
            $preview = preg_replace('/Şifrə\s*:\s*\S+/iu', 'Şifrə: [REDACTED]', $preview) ?? $preview;
            $preview = preg_replace('/Password\s*:\s*\S+/iu', 'Password: [REDACTED]', $preview) ?? $preview;
            $preview = preg_replace('/Пароль\s*:\s*\S+/iu', 'Пароль: [REDACTED]', $preview) ?? $preview;
            $stmt = $pdo->prepare('INSERT INTO email_logs (recipient, subject, body_preview, status, error_message) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $to,
                $subject,
                $preview,
                $ok ? 'sent' : 'failed',
                $error,
            ]);
        } catch (\Throwable) {
            // ignore logging failures
        }
    }

    public function welcomeParent(string $email, string $firstName): bool
    {
        $loginUrl = url('/valideyn/giris');
        $html = $this->wrap(
            'Xoş gəlmisiniz, ' . e($firstName) . '!',
            '<p>Sistemə uğurla qeydiyyatdan keçdiniz. Bizi seçdiyiniz üçün təşəkkür edirik!</p>
            <p>İndi övladlarınızı sistemə əlavə edə və onların imtahan nəticələrini izləyə bilərsiniz.</p>
            <p style="text-align:center;margin:28px 0">
                <a href="' . e($loginUrl) . '" style="background:#0B6E4F;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">Valideyn panelinə daxil ol</a>
            </p>
            <p>Suallarınız olarsa, <strong>esinaq@esinaq.net</strong> ünvanına yazın.</p>'
        );
        return $this->send($email, 'eSınaq.net — Qeydiyyatınız uğurludur!', $html);
    }

    public function childRegistered(string $email, string $parentName, array $child, string $examLink): bool
    {
        $full = person_full_name($child);
        $html = $this->wrap(
            e($child['first_name']) . ' sistemə əlavə olundu',
            '<p>Hörmətli ' . e($parentName) . ',</p>
            <p><strong>' . e($full) . '</strong> uğurla sistemə qeydiyyatdan keçdi.</p>
            <div style="background:#f4f7f5;border-radius:10px;padding:16px 20px;margin:20px 0">
                <p style="margin:0 0 8px"><strong>Giriş məlumatları:</strong></p>
                <p style="margin:4px 0">Sinif: ' . e(grade_label((int) $child['grade'])) . '</p>
                <p style="margin:4px 0">Sektor: ' . e(sector_label($child['sector'])) . '</p>
                <p style="margin:4px 0">Şifrə: <code style="background:#fff;padding:2px 8px;border-radius:4px">' . e($child['password_hint']) . '</code></p>
            </div>
            <p style="text-align:center;margin:28px 0">
                <a href="' . e($examLink) . '" style="background:#0B6E4F;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">Uşağın imtahan linki</a>
            </p>
            <p style="font-size:13px;color:#666">Link: ' . e($examLink) . '</p>'
        );
        return $this->send($email, e($full) . ' — giriş məlumatları | eSınaq.net', $html);
    }

    public function passwordReset(string $email, string $token): bool
    {
        $resetUrl = url('/sifre-berpa?token=' . urlencode($token));
        $html = $this->wrap(
            'Şifrə bərpası',
            '<p>Şifrənizi unutmusunuzsa, aşağıdakı düyməyə klikləyin. Link 1 saat ərzində etibarlıdır.</p>
            <p style="text-align:center;margin:28px 0">
                <a href="' . e($resetUrl) . '" style="background:#0B6E4F;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">Şifrəni bərpa et</a>
            </p>
            <p style="font-size:13px;color:#666">Əgər bu sorğunu siz göndərməmisinizsə, bu məktubu nəzərə almayın.</p>'
        );
        return $this->send($email, 'eSınaq.net — Şifrə bərpası', $html);
    }

    public function examReminder(string $email, string $childName, string $examTitle, string $startsAt, string $link): bool
    {
        $html = $this->wrap(
            'İmtahan xatırlatması',
            '<p><strong>' . e($childName) . '</strong> üçün <strong>' . e($examTitle) . '</strong> imtahanı <strong>' . e($startsAt) . '</strong> tarixində başlayacaq.</p>
            <p style="text-align:center;margin:28px 0">
                <a href="' . e($link) . '" style="background:#0B6E4F;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">İmtahana keç</a>
            </p>'
        );
        return $this->send($email, \App\Core\Security::sanitizeHeaderValue('İmtahan xatırlatması: ' . $childName . ' | eSınaq.net'), $html);
    }

    private function wrap(string $title, string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
        <body style="margin:0;padding:0;background:#eef2f0;font-family:Segoe UI,Arial,sans-serif">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f0;padding:32px 12px">
        <tr><td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <tr><td style="background:linear-gradient(135deg,#0B6E4F,#08A045);padding:28px 32px;color:#fff">
            <div style="font-size:22px;font-weight:700;letter-spacing:-0.02em">eSınaq.net</div>
            <div style="opacity:.9;margin-top:4px;font-size:14px">Onlayn sınaq və yoxlama platforması</div>
        </td></tr>
        <tr><td style="padding:32px">
            <h1 style="margin:0 0 16px;font-size:22px;color:#12352a">' . $title . '</h1>
            <div style="color:#334;line-height:1.6;font-size:15px">' . $body . '</div>
        </td></tr>
        <tr><td style="padding:16px 32px 28px;color:#889;font-size:12px;border-top:1px solid #eef2f0">
            © ' . date('Y') . ' eSınaq.net · esinaq@esinaq.net · <a href="' . e(url('/')) . '" style="color:#0B6E4F">esinaq.net</a>
        </td></tr>
        </table>
        </td></tr></table></body></html>';
    }
}
