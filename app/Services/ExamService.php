<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ExamService
{
    public function pickQuestions(int $examId): int
    {
        $pdo = Database::connection();
        $exam = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
        $exam->execute([$examId]);
        $examRow = $exam->fetch();
        if (!$examRow) {
            return 0;
        }

        $pdo->prepare('DELETE FROM exam_questions WHERE exam_id = ?')->execute([$examId]);

        $subjects = $pdo->prepare('SELECT * FROM exam_subjects WHERE exam_id = ?');
        $subjects->execute([$examId]);
        $subjectRows = $subjects->fetchAll();

        $order = 0;
        $total = 0;

        foreach ($subjectRows as $es) {
            $count = (int) $es['question_count'];
            $q = $pdo->prepare(
                'SELECT id, subject_id FROM questions
                 WHERE subject_id = ? AND grade = ? AND sector = ? AND is_active = 1
                 ORDER BY RAND() LIMIT ' . (int) $count
            );
            $q->execute([(int) $es['subject_id'], (int) $examRow['grade'], $examRow['sector']]);
            $picked = $q->fetchAll();

            $ins = $pdo->prepare('INSERT INTO exam_questions (exam_id, question_id, subject_id, sort_order) VALUES (?, ?, ?, ?)');
            foreach ($picked as $row) {
                $ins->execute([$examId, $row['id'], $row['subject_id'], $order++]);
                $total++;
            }
        }

        return $total;
    }

    public function ensureSession(int $examId, int $childId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM exam_sessions WHERE exam_id = ? AND child_id = ?');
        $stmt->execute([$examId, $childId]);
        $session = $stmt->fetch();

        if ($session) {
            return $session;
        }

        $pdo->prepare('INSERT INTO exam_sessions (exam_id, child_id, status) VALUES (?, ?, ?)')
            ->execute([$examId, $childId, 'pending']);

        $stmt->execute([$examId, $childId]);
        return $stmt->fetch();
    }

    public function startSession(int $sessionId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE exam_sessions SET status = ?, started_at = NOW() WHERE id = ? AND status = ?')
            ->execute(['in_progress', $sessionId, 'pending']);
    }

    public function saveAnswer(int $sessionId, int $questionId, ?string $option): void
    {
        $pdo = Database::connection();
        $q = $pdo->prepare('SELECT correct_option FROM questions WHERE id = ?');
        $q->execute([$questionId]);
        $question = $q->fetch();
        if (!$question) {
            return;
        }

        $isCorrect = null;
        if ($option !== null && $option !== '') {
            $isCorrect = strtoupper($option) === $question['correct_option'] ? 1 : 0;
        }

        $pdo->prepare(
            'INSERT INTO student_answers (session_id, question_id, selected_option, is_correct, answered_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option), is_correct = VALUES(is_correct), answered_at = NOW()'
        )->execute([$sessionId, $questionId, $option ?: null, $isCorrect]);
    }

    public function submit(int $sessionId, string $status = 'submitted'): array
    {
        $pdo = Database::connection();

        $session = $pdo->prepare('SELECT * FROM exam_sessions WHERE id = ?');
        $session->execute([$sessionId]);
        $sess = $session->fetch();
        if (!$sess) {
            throw new \RuntimeException('Sessiya tapılmadı');
        }

        if (in_array($sess['status'], ['submitted', 'timed_out'], true) && $sess['score'] !== null) {
            return $sess;
        }

        $maxStmt = $pdo->prepare('SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?');
        $maxStmt->execute([(int) $sess['exam_id']]);
        $max = (int) $maxStmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM student_answers WHERE session_id = ? AND is_correct = 1');
        $stmt->execute([$sessionId]);
        $correct = (int) $stmt->fetchColumn();

        $percentage = $max > 0 ? round(($correct / $max) * 100, 2) : 0.0;
        $letter = letter_grade((float) $percentage);

        $pdo->prepare(
            'UPDATE exam_sessions SET status = ?, finished_at = NOW(), score = ?, max_score = ?, percentage = ?, letter_grade = ? WHERE id = ?'
        )->execute([$status, $correct, $max, $percentage, $letter, $sessionId]);

        $session->execute([$sessionId]);
        return $session->fetch();
    }

    public function getExamQuestions(int $examId, bool $shuffle = false): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT eq.sort_order, eq.subject_id, q.*, s.name_az AS subject_name
                FROM exam_questions eq
                JOIN questions q ON q.id = eq.question_id
                JOIN subjects s ON s.id = eq.subject_id
                WHERE eq.exam_id = ?
                ORDER BY eq.sort_order';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$examId]);
        $rows = $stmt->fetchAll();

        if ($shuffle) {
            shuffle($rows);
        }

        return $rows;
    }

    public function getResultDetails(int $sessionId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT q.*, sa.selected_option, sa.is_correct, s.name_az AS subject_name
             FROM exam_sessions es
             JOIN exam_questions eq ON eq.exam_id = es.exam_id
             JOIN questions q ON q.id = eq.question_id
             JOIN subjects s ON s.id = q.subject_id
             LEFT JOIN student_answers sa ON sa.session_id = es.id AND sa.question_id = q.id
             WHERE es.id = ?
             ORDER BY eq.sort_order'
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }
}
