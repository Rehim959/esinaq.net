<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\ExamInviteService;

final class InviteController
{
    public function confirm(string $token): void
    {
        $token = preg_replace('/[^a-f0-9]/i', '', $token) ?? '';
        if (strlen($token) !== 64) {
            View::render('invite/confirm', [
                'title' => __('invite_invalid_title'),
                'ok' => false,
                'message' => __('invite_invalid'),
            ], 'layouts/exam');
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT ei.*, p.id AS parent_pk, p.email, p.first_name, p.last_name, p.patronymic,
                    e.id AS exam_pk, e.title, e.grade, e.sector, e.starts_at, e.ends_at, e.status AS exam_status
             FROM exam_invites ei
             JOIN parents p ON p.id = ei.parent_id
             JOIN exams e ON e.id = ei.exam_id
             WHERE ei.token = ?'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            View::render('invite/confirm', [
                'title' => __('invite_invalid_title'),
                'ok' => false,
                'message' => __('invite_invalid'),
            ], 'layouts/exam');
            return;
        }

        $status = (string) $row['status'];

        if ($status === 'approved') {
            View::render('invite/confirm', [
                'title' => __('invite_already_approved_title'),
                'ok' => true,
                'message' => __('invite_already_approved'),
                'exam' => $row,
            ], 'layouts/exam');
            return;
        }

        if ($status === 'rejected') {
            View::render('invite/confirm', [
                'title' => __('invite_rejected_title'),
                'ok' => false,
                'message' => __('invite_rejected_parent'),
                'exam' => $row,
            ], 'layouts/exam');
            return;
        }

        if ($status === 'interested') {
            View::render('invite/confirm', [
                'title' => __('invite_interest_saved_title'),
                'ok' => true,
                'message' => __('invite_interest_already'),
                'exam' => $row,
            ], 'layouts/exam');
            return;
        }

        // invited → interested
        $pdo->prepare(
            "UPDATE exam_invites SET status = 'interested', interested_at = NOW() WHERE id = ? AND status = 'invited'"
        )->execute([(int) $row['id']]);

        $parent = [
            'id' => (int) $row['parent_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'patronymic' => $row['patronymic'],
            'email' => $row['email'],
        ];
        $exam = [
            'id' => (int) $row['exam_id'],
            'title' => $row['title'],
            'grade' => $row['grade'],
            'sector' => $row['sector'],
            'starts_at' => $row['starts_at'],
            'ends_at' => $row['ends_at'],
        ];

        try {
            (new ExamInviteService())->notifyAdminsOfInterest($row, $parent, $exam);
        } catch (\Throwable) {
            // Parent confirmation still counts
        }

        View::render('invite/confirm', [
            'title' => __('invite_interest_saved_title'),
            'ok' => true,
            'message' => __('invite_interest_saved'),
            'exam' => $exam,
        ], 'layouts/exam');
    }
}
