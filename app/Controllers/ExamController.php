<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
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
            View::render('exam/invalid', ['title' => 'Link etibarsızdır'], 'layouts/exam');
            return;
        }

        // If already logged in as this child, go to exam list
        if (Auth::childId() === (int) $child['id']) {
            redirect('/imtahan/' . $token . '/siyahi');
        }

        View::render('exam/login', [
            'title' => 'İmtahana giriş',
            'child' => $child,
            'token' => $token,
        ], 'layouts/exam');
    }

    public function login(string $token): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Təhlükəsizlik xətası.');
            redirect('/imtahan/' . $token);
        }

        $password = (string) ($_POST['password'] ?? '');
        $child = Auth::attemptChild($token, $password);

        if (!$child) {
            Session::flash('error', 'Şifrə yanlışdır. (Ad + doğum ili, məs: Samir2015)');
            redirect('/imtahan/' . $token);
        }

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
             ORDER BY e.id DESC"
        );
        $exams->execute([
            $child['id'], $child['id'], $child['id'], $child['id'],
            $child['grade'], $child['sector'],
        ]);

        View::render('exam/list', [
            'title' => 'İmtahanlar',
            'child' => $child,
            'token' => $token,
            'exams' => $exams->fetchAll(),
        ], 'layouts/exam');
    }

    public function start(string $token, string $examId): void
    {
        $child = $this->requireChild($token);
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Təhlükəsizlik xətası.');
            redirect('/imtahan/' . $token . '/siyahi');
        }
        $examId = (int) $examId;
        $pdo = Database::connection();

        $exam = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND status = 'running' AND grade = ? AND sector = ?");
        $exam->execute([$examId, $child['grade'], $child['sector']]);
        $examRow = $exam->fetch();

        if (!$examRow) {
            Session::flash('error', 'İmtahan tapılmadı və ya aktiv deyil.');
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
        $stmt = $pdo->prepare('SELECT * FROM exam_sessions WHERE id = ? AND child_id = ? AND status = ?');
        $stmt->execute([$sessionId, $child['id'], 'in_progress']);
        $session = $stmt->fetch();

        if (!$session) {
            View::json(['ok' => false, 'error' => 'session'], 400);
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
            Session::flash('error', 'Təhlükəsizlik xətası.');
            redirect('/imtahan/' . $token . '/siyahi');
        }

        $sessionId = (int) $sessionId;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM exam_sessions WHERE id = ? AND child_id = ?');
        $stmt->execute([$sessionId, $child['id']]);
        $session = $stmt->fetch();

        if (!$session) {
            redirect('/imtahan/' . $token . '/siyahi');
        }

        (new ExamService())->submit($sessionId, 'submitted');
        redirect('/imtahan/' . $token . '/netice/' . $sessionId);
    }

    public function result(string $token, string $sessionId): void
    {
        $child = $this->requireChild($token);
        $sessionId = (int) $sessionId;
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT es.*, e.title FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             WHERE es.id = ? AND es.child_id = ?'
        );
        $stmt->execute([$sessionId, $child['id']]);
        $session = $stmt->fetch();

        if (!$session || !in_array($session['status'], ['submitted', 'timed_out'], true)) {
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

        View::render('exam/result', [
            'title' => 'Nəticə',
            'child' => $child,
            'token' => $token,
            'session' => $session,
            'details' => $details,
            'correct' => $correct,
            'wrong' => $wrong,
            'blank' => $blank,
            'message' => grade_message((string) $session['letter_grade']),
        ], 'layouts/exam');
    }

    private function requireChild(string $token): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM children WHERE access_token = ? AND is_active = 1');
        $stmt->execute([$token]);
        $child = $stmt->fetch();

        if (!$child) {
            View::render('exam/invalid', ['title' => 'Link etibarsızdır'], 'layouts/exam');
            exit;
        }

        if (Auth::childId() !== (int) $child['id']) {
            redirect('/imtahan/' . $token);
        }

        return $child;
    }
}
