<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ExamInviteService
{
    /**
     * Create invites for parents who have active children matching exam grade/sector,
     * and send invitation emails.
     *
     * @return array{invited:int, mailed:int}
     */
    public function inviteMatchingParents(int $examId): array
    {
        $pdo = Database::connection();
        $examStmt = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
        $examStmt->execute([$examId]);
        $exam = $examStmt->fetch();
        if (!$exam) {
            return ['invited' => 0, 'mailed' => 0];
        }

        $parents = $pdo->prepare(
            'SELECT DISTINCT p.id, p.email, p.first_name, p.last_name, p.patronymic
             FROM parents p
             INNER JOIN children c ON c.parent_id = p.id
             WHERE c.is_active = 1 AND c.grade = ? AND c.sector = ?
             ORDER BY p.id ASC'
        );
        $parents->execute([(int) $exam['grade'], (string) $exam['sector']]);
        $parentRows = $parents->fetchAll();

        $ins = $pdo->prepare(
            'INSERT INTO exam_invites (exam_id, parent_id, token, status)
             VALUES (?, ?, ?, \'invited\')
             ON DUPLICATE KEY UPDATE id = id'
        );

        $mail = new MailService();
        $invited = 0;
        $mailed = 0;

        foreach ($parentRows as $parent) {
            $parentId = (int) $parent['id'];
            $existing = $pdo->prepare('SELECT id, token, status FROM exam_invites WHERE exam_id = ? AND parent_id = ?');
            $existing->execute([$examId, $parentId]);
            $row = $existing->fetch();

            if ($row) {
                $token = (string) $row['token'];
            } else {
                $token = bin2hex(random_bytes(32));
                $ins->execute([$examId, $parentId, $token]);
            }
            $invited++;

            $children = $this->matchingChildren($pdo, $parentId, (int) $exam['grade'], (string) $exam['sector']);
            $childNames = array_map(static fn (array $c): string => person_full_name($c), $children);
            $confirmUrl = url('/imtahan-dewet/' . $token);

            if ($mail->examInvite(
                (string) $parent['email'],
                person_full_name($parent),
                $exam,
                $childNames,
                $confirmUrl
            )) {
                $mailed++;
            }
        }

        return ['invited' => $invited, 'mailed' => $mailed];
    }

    public function resendInvite(int $inviteId): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT ei.*, p.email, p.first_name, p.last_name, p.patronymic,
                    e.title, e.grade, e.sector, e.starts_at, e.ends_at, e.duration_minutes
             FROM exam_invites ei
             JOIN parents p ON p.id = ei.parent_id
             JOIN exams e ON e.id = ei.exam_id
             WHERE ei.id = ?'
        );
        $stmt->execute([$inviteId]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        $children = $this->matchingChildren(
            $pdo,
            (int) $row['parent_id'],
            (int) $row['grade'],
            (string) $row['sector']
        );
        $childNames = array_map(static fn (array $c): string => person_full_name($c), $children);
        $confirmUrl = url('/imtahan-dewet/' . $row['token']);

        return (new MailService())->examInvite(
            (string) $row['email'],
            person_full_name($row),
            $row,
            $childNames,
            $confirmUrl
        );
    }

    /** Whether this child may see/start the exam (approved invite or legacy exam without invites). */
    public function childMayAccessExam(array $child, int $examId): bool
    {
        $pdo = Database::connection();
        $count = $pdo->prepare('SELECT COUNT(*) FROM exam_invites WHERE exam_id = ?');
        $count->execute([$examId]);
        if ((int) $count->fetchColumn() === 0) {
            return true;
        }

        $ok = $pdo->prepare(
            "SELECT COUNT(*) FROM exam_invites
             WHERE exam_id = ? AND parent_id = ? AND status = 'approved'"
        );
        $ok->execute([$examId, (int) $child['parent_id']]);
        return (int) $ok->fetchColumn() > 0;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function matchingChildren(PDO $pdo, int $parentId, int $grade, string $sector): array
    {
        $stmt = $pdo->prepare(
            'SELECT * FROM children
             WHERE parent_id = ? AND is_active = 1 AND grade = ? AND sector = ?
             ORDER BY first_name, last_name'
        );
        $stmt->execute([$parentId, $grade, $sector]);
        return $stmt->fetchAll();
    }

    /** Notify all active admins that a parent expressed interest. */
    public function notifyAdminsOfInterest(array $invite, array $parent, array $exam): void
    {
        $pdo = Database::connection();
        $admins = $pdo->query(
            "SELECT email, full_name FROM admins WHERE is_active = 1 AND email IS NOT NULL AND email <> ''"
        )->fetchAll();
        if ($admins === []) {
            return;
        }

        $children = $this->matchingChildren(
            $pdo,
            (int) $parent['id'],
            (int) $exam['grade'],
            (string) $exam['sector']
        );
        $childNames = array_map(static fn (array $c): string => person_full_name($c), $children);
        $reviewUrl = url('/admin/imtahanlar/dewetler/' . (int) $exam['id']);
        $mail = new MailService();

        foreach ($admins as $admin) {
            $mail->examInterestNotify(
                (string) $admin['email'],
                (string) ($admin['full_name'] ?? 'Admin'),
                person_full_name($parent),
                $exam,
                $childNames,
                $reviewUrl
            );
        }
    }
}
