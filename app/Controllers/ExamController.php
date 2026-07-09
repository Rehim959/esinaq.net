<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\View;
use App\Services\ExamService;

final class ExamController
{
    public function entry(string $token): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM children WHERE access_token = ? AND is_active = 1');
        $stmt->execute([$token]);
        $child = $stmt->fetch();

        if (!$child) {
            View::render('exam/invalid', ['title' => __('invalid_link_title')], 'layouts/exam');
            return;
        }

        // If already logged in as this child, go to exam list
        if (Auth::childId() === (int) $child['id']) {
            redirect('/imtahan/' . $token . '/siyahi');
        }

        View::render('exam/login', [
            'title' => __('exam_entry'),
            'child' => $child,
            'token' => $token,
        ], 'layouts/exam');
    }

    public function login(string $token): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/imtahan/' . $token);
        }

        $rateKey = RateLimiter::clientKey('exam_login:' . substr(hash('sha256', $token), 0, 16));
        if (RateLimiter::tooManyAttempts($rateKey, 5, 1800)) {
            $wait = RateLimiter::availableIn($rateKey, 1800);
            Session::flash('error', __('err_rate_limit', ['n' => (string) max(1, (int) ceil($wait / 60))]));
            redirect('/imtahan/' . $token);
        }

        $password = (string) ($_POST['password'] ?? '');
        $child = Auth::attemptChild($token, $password);

        if (!$child) {
            RateLimiter::hit($rateKey, 1800);
            Session::flash('error', __('err_child_password'));
            redirect('/imtahan/' . $token);
        }

        RateLimiter::clear($rateKey);
        redirect('/imtahan/' . $token . '/siyahi');
    }

    public function listExams(string $token): void
    {
        $child = $this->requireChild($token);
        $pdo = Database::connection();

        $exams = $pdo->prepare(
            "SELECT e.*,
                (SELECT es.status FROM exam_sessions es WHERE es.exam_id = e.id AND es.child_id = ? LIMIT 1) AS my_status,
                (SELECT es.id FROM exam_sessions es WHERE es.exam_id = e.id AND es.child_id = ? LIMIT 1) AS my_session_id,
                (SELECT es.percentage FROM exam_sessions es WHERE es.exam_id = e.id AND es.child_id = ? LIMIT 1) AS my_percentage,
                (SELECT es.letter_grade FROM exam_sessions es WHERE es.exam_id = e.id AND es.child_id = ? LIMIT 1) AS my_letter
             FROM exams e
             WHERE e.status = 'running'
               AND e.grade = ?
               AND e.sector = ?
               AND (e.starts_at IS NULL OR e.starts_at <= NOW())
               AND (e.ends_at IS NULL OR e.ends_at >= NOW())
             ORDER BY e.id DESC"
        );
        $exams->execute([
            $child['id'], $child['id'], $child['id'], $child['id'],
            $child['grade'], $child['sector'],
        ]);

        View::render('exam/list', [
            'title' => __('exams'),
            'child' => $child,
            'token' => $token,
            'exams' => $exams->fetchAll(),
        ], 'layouts/exam');
    }

    public function start(string $token, string $examId): void
    {
        $child = $this->requireChild($token);
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/imtahan/' . $token . '/siyahi');
        }
        $examId = (int) $examId;
        $pdo = Database::connection();

        $exam = $pdo->prepare(
            "SELECT * FROM exams WHERE id = ? AND status = 'running' AND grade = ? AND sector = ?
             AND (starts_at IS NULL OR starts_at <= NOW())
             AND (ends_at IS NULL OR ends_at >= NOW())"
        );
        $exam->execute([$examId, $child['grade'], $child['sector']]);
        $examRow = $exam->fetch();

        if (!$examRow) {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/imtahan/' . $token . '/siyahi');
        }

        $service = new ExamService();
        $session = $service->ensureSession($examId, (int) $child['id']);

        if (in_array($session['status'], ['submitted', 'timed_out'], true)) {
            redirect('/imtahan/' . $token . '/netice/' . $session['id']);
        }

        if ($session['status'] === 'pending') {
            $service->startSession((int) $session['id']);
            $session = $service->ensureSession($examId, (int) $child['id']);
        }

        redirect('/imtahan/' . $token . '/kec/' . $session['id']);
    }

    public function take(string $token, string $sessionId): void
    {
        $child = $this->requireChild($token);
        $sessionId = (int) $sessionId;
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT es.*, e.title, e.duration_minutes, e.shuffle_questions
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             WHERE es.id = ? AND es.child_id = ?'
        );
        $stmt->execute([$sessionId, $child['id']]);
        $session = $stmt->fetch();

        if (!$session) {
            redirect('/imtahan/' . $token . '/siyahi');
        }

        if (in_array($session['status'], ['submitted', 'timed_out'], true)) {
            redirect('/imtahan/' . $token . '/netice/' . $sessionId);
        }

        // Time check
        $started = strtotime($session['started_at'] ?? 'now');
        $endsAt = $started + ((int) $session['duration_minutes'] * 60);
        if (time() >= $endsAt) {
            (new ExamService())->submit($sessionId, 'timed_out');
            redirect('/imtahan/' . $token . '/netice/' . $sessionId);
        }

        $questions = (new ExamService())->getExamQuestions((int) $session['exam_id'], false);

        $answers = $pdo->prepare('SELECT question_id, selected_option FROM student_answers WHERE session_id = ?');
        $answers->execute([$sessionId]);
        $answerMap = [];
        foreach ($answers->fetchAll() as $a) {
            $answerMap[(int) $a['question_id']] = $a['selected_option'];
        }

        $currentIndex = isset($_GET['q']) ? max(0, (int) $_GET['q']) : 0;
        if ($currentIndex >= count($questions)) {
            $currentIndex = max(0, count($questions) - 1);
        }

        View::render('exam/take', [
            'title' => $session['title'],
            'child' => $child,
            'token' => $token,
            'session' => $session,
            'questions' => $questions,
            'answerMap' => $answerMap,
            'currentIndex' => $currentIndex,
            'endsAt' => $endsAt,
            'remainingSeconds' => max(0, $endsAt - time()),
        ], 'layouts/exam');
    }

    public function answer(string $token, string $sessionId): void
    {
        $child = $this->requireChild($token);
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            View::json(['ok' => false, 'error' => 'csrf'], 403);
            return;
        }

        $sessionId = (int) $sessionId;
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT es.*, e.duration_minutes
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             WHERE es.id = ? AND es.child_id = ? AND es.status = ?'
        );
        $stmt->execute([$sessionId, $child['id'], 'in_progress']);
        $session = $stmt->fetch();

        if (!$session) {
            View::json(['ok' => false, 'error' => 'session'], 400);
            return;
        }

        if ($this->isSessionExpired($session)) {
            (new ExamService())->submit($sessionId, 'timed_out');
            View::json(['ok' => false, 'error' => 'timeout', 'redirect' => url('/imtahan/' . $token . '/netice/' . $sessionId)], 400);
            return;
        }

        $questionId = (int) ($_POST['question_id'] ?? 0);
        $option = strtoupper(trim((string) ($_POST['option'] ?? '')));
        if ($option === '') {
            $option = null;
        } elseif (!in_array($option, ['A', 'B', 'C', 'D', 'E'], true)) {
            View::json(['ok' => false, 'error' => 'option'], 400);
            return;
        }

        // Verify question belongs to exam
        $check = $pdo->prepare('SELECT id FROM exam_questions WHERE exam_id = ? AND question_id = ?');
        $check->execute([$session['exam_id'], $questionId]);
        if (!$check->fetch()) {
            View::json(['ok' => false, 'error' => 'question'], 400);
            return;
        }

        (new ExamService())->saveAnswer($sessionId, $questionId, $option);
        View::json(['ok' => true]);
    }

    public function submit(string $token, string $sessionId): void
    {
        $child = $this->requireChild($token);
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/imtahan/' . $token . '/siyahi');
        }

        $sessionId = (int) $sessionId;
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT es.*, e.duration_minutes
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             WHERE es.id = ? AND es.child_id = ?'
        );
        $stmt->execute([$sessionId, $child['id']]);
        $session = $stmt->fetch();

        if (!$session) {
            redirect('/imtahan/' . $token . '/siyahi');
        }

        if (in_array($session['status'], ['submitted', 'timed_out'], true)) {
            redirect('/imtahan/' . $token . '/netice/' . $sessionId);
        }

        if ($session['status'] !== 'in_progress') {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/imtahan/' . $token . '/siyahi');
        }

        $status = $this->isSessionExpired($session) ? 'timed_out' : 'submitted';
        (new ExamService())->submit($sessionId, $status);
        redirect('/imtahan/' . $token . '/netice/' . $sessionId);
    }

    public function result(string $token, string $sessionId): void
    {
        $child = $this->requireChild($token);
        $sessionId = (int) $sessionId;
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT es.*, e.title, e.status AS exam_status, e.duration_minutes
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             WHERE es.id = ? AND es.child_id = ?'
        );
        $stmt->execute([$sessionId, $child['id']]);
        $session = $stmt->fetch();

        if (!$session) {
            redirect('/imtahan/' . $token . '/siyahi');
        }

        if ($session['status'] === 'in_progress' && $this->isSessionExpired($session)) {
            (new ExamService())->submit($sessionId, 'timed_out');
            redirect('/imtahan/' . $token . '/netice/' . $sessionId);
        }

        if (!in_array($session['status'], ['submitted', 'timed_out'], true)) {
            redirect('/imtahan/' . $token . '/siyahi');
        }

        $details = (new ExamService())->getResultDetails($sessionId);
        $correct = 0;
        $wrong = 0;
        $blank = 0;
        foreach ($details as $d) {
            if ($d['selected_option'] === null) {
                $blank++;
            } elseif ((int) $d['is_correct'] === 1) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        $revealAnswers = ($session['exam_status'] ?? '') === 'finished';

        View::render('exam/result', [
            'title' => __('result'),
            'child' => $child,
            'token' => $token,
            'session' => $session,
            'details' => $details,
            'correct' => $correct,
            'wrong' => $wrong,
            'blank' => $blank,
            'revealAnswers' => $revealAnswers,
            'message' => grade_message((string) $session['letter_grade']),
            'band' => __('grade_band_' . strtoupper((string) $session['letter_grade'])),
        ], 'layouts/exam');
    }

    private function requireChild(string $token): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM children WHERE access_token = ? AND is_active = 1');
        $stmt->execute([$token]);
        $child = $stmt->fetch();

        if (!$child) {
            View::render('exam/invalid', ['title' => __('invalid_link_title')], 'layouts/exam');
            exit;
        }

        if (Auth::childId() !== (int) $child['id']) {
            redirect('/imtahan/' . $token);
        }

        return $child;
    }

    /** @param array<string,mixed> $session */
    private function isSessionExpired(array $session): bool
    {
        if (empty($session['started_at'])) {
            return false;
        }
        $started = strtotime((string) $session['started_at']);
        if ($started === false) {
            return false;
        }
        $endsAt = $started + ((int) ($session['duration_minutes'] ?? 0) * 60);
        return time() >= $endsAt;
    }
}
