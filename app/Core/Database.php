<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $name = (string) env('DB_NAME', 'esinaq');
        $user = (string) env('DB_USER', 'root');
        $pass = (string) env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Keep MySQL NOW() aligned with app timezone (Asia/Baku)
            self::$pdo->exec("SET time_zone = '+04:00'");
            self::ensureQuestionsSchema();
            self::ensureExamInvitesSchema();
            self::ensureAdminsSchema();
        } catch (PDOException $e) {
            http_response_code(500);
            if (env('APP_DEBUG')) {
                exit('DB connection failed: ' . $e->getMessage());
            }
            exit('Verilənlər bazasına qoşulmaq mümkün olmadı.');
        }

        return self::$pdo;
    }

    /** Auto-apply math-content migration if missing (shared hosting often skips SQL files). */
    private static function ensureQuestionsSchema(): void
    {
        if (!(self::$pdo instanceof PDO)) {
            return;
        }
        try {
            $hasFormat = self::$pdo->query("SHOW COLUMNS FROM questions LIKE 'content_format'")->fetch();
            if (!$hasFormat) {
                self::$pdo->exec(
                    "ALTER TABLE questions
                     ADD COLUMN content_format ENUM('plain','html') NOT NULL DEFAULT 'plain' AFTER question_text"
                );
            }
            $opt = self::$pdo->query("SHOW COLUMNS FROM questions LIKE 'option_a'")->fetch();
            $type = strtolower((string) ($opt['Type'] ?? ''));
            if ($type !== '' && str_contains($type, 'varchar')) {
                self::$pdo->exec(
                    'ALTER TABLE questions
                     MODIFY option_a TEXT NOT NULL,
                     MODIFY option_b TEXT NOT NULL,
                     MODIFY option_c TEXT NOT NULL,
                     MODIFY option_d TEXT NOT NULL,
                     MODIFY option_e TEXT NULL'
                );
            }
        } catch (\Throwable) {
            // Leave as-is; write paths will surface a friendly error
        }
    }

    /** Auto-create exam_invites table for parent invite / admin approval flow. */
    private static function ensureExamInvitesSchema(): void
    {
        if (!(self::$pdo instanceof PDO)) {
            return;
        }
        try {
            $exists = self::$pdo->query("SHOW TABLES LIKE 'exam_invites'")->fetch();
            if ($exists) {
                return;
            }
            self::$pdo->exec(
                "CREATE TABLE exam_invites (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    exam_id INT UNSIGNED NOT NULL,
                    parent_id INT UNSIGNED NOT NULL,
                    token CHAR(64) NOT NULL,
                    status ENUM('invited','interested','approved','rejected') NOT NULL DEFAULT 'invited',
                    interested_at TIMESTAMP NULL DEFAULT NULL,
                    decided_at TIMESTAMP NULL DEFAULT NULL,
                    decided_by INT UNSIGNED NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_exam_parent (exam_id, parent_id),
                    UNIQUE KEY uq_invite_token (token),
                    INDEX idx_exam_status (exam_id, status),
                    CONSTRAINT fk_invite_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
                    CONSTRAINT fk_invite_parent FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
                    CONSTRAINT fk_invite_admin FOREIGN KEY (decided_by) REFERENCES admins(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (\Throwable) {
            // Leave as-is
        }
    }

    /** Ensure admins.role includes admin (between super_admin and moderator). */
    private static function ensureAdminsSchema(): void
    {
        if (!(self::$pdo instanceof PDO)) {
            return;
        }
        try {
            $col = self::$pdo->query("SHOW COLUMNS FROM admins LIKE 'role'")->fetch();
            if (!$col) {
                self::$pdo->exec(
                    "ALTER TABLE admins
                     ADD COLUMN role ENUM('super_admin','admin','moderator') NOT NULL DEFAULT 'moderator' AFTER full_name"
                );
            } else {
                $type = strtolower((string) ($col['Type'] ?? ''));
                if (!str_contains($type, "'admin'")) {
                    self::$pdo->exec(
                        "ALTER TABLE admins
                         MODIFY COLUMN role ENUM('super_admin','admin','moderator') NOT NULL DEFAULT 'moderator'"
                    );
                }
            }
            $active = self::$pdo->query("SHOW COLUMNS FROM admins LIKE 'is_active'")->fetch();
            if (!$active) {
                self::$pdo->exec(
                    'ALTER TABLE admins ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role'
                );
            }
            // Keep primary bootstrap account as super admin
            $email = strtolower(trim((string) env('ADMIN_EMAIL', 'admin@esinaq.net')));
            if ($email !== '') {
                self::$pdo->prepare(
                    "UPDATE admins SET role = 'super_admin', is_active = 1 WHERE email = ?"
                )->execute([$email]);
            }
        } catch (\Throwable) {
            // Leave as-is
        }
    }
}
