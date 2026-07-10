<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Auto-start / auto-finish exams based on starts_at / ends_at.
 * Safe to call on every request (cheap UPDATEs).
 */
final class ExamScheduler
{
    public static function sync(): void
    {
        try {
            $pdo = Database::connection();
        } catch (\Throwable) {
            return;
        }

        // Start: draft/scheduled whose window has begun and not yet ended
        $pdo->exec(
            "UPDATE exams SET status = 'running'
             WHERE status IN ('draft', 'scheduled')
               AND starts_at IS NOT NULL
               AND starts_at <= NOW()
               AND (ends_at IS NULL OR ends_at > NOW())"
        );

        // Finish: running past ends_at
        $toFinish = $pdo->query(
            "SELECT id FROM exams
             WHERE status = 'running'
               AND ends_at IS NOT NULL
               AND ends_at <= NOW()"
        )->fetchAll(PDO::FETCH_COLUMN);

        if ($toFinish === []) {
            return;
        }

        $service = new ExamService();
        foreach ($toFinish as $examId) {
            $examId = (int) $examId;
            $pdo->prepare("UPDATE exams SET status = 'finished' WHERE id = ? AND status = 'running'")
                ->execute([$examId]);

            $open = $pdo->prepare("SELECT id FROM exam_sessions WHERE exam_id = ? AND status IN ('pending','in_progress')");
            $open->execute([$examId]);
            foreach ($open->fetchAll() as $row) {
                $service->submit((int) $row['id'], 'timed_out');
            }
        }
    }
}
