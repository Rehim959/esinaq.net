<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Session;
use App\Core\View;
use App\Services\ExamService;
use App\Services\QuestionParser;

final class AdminController
{
    public function showLogin(): void
    {
        if (Auth::adminId()) {
            redirect('/admin');
        }
        View::render('admin/login', ['title' => __('admin_login')], 'layouts/admin_auth');
    }

    public function login(): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/login');
        }

        $rateKey = RateLimiter::clientKey('admin_login');
        if (RateLimiter::tooManyAttempts($rateKey, 6, 900)) {
            $wait = RateLimiter::availableIn($rateKey, 900);
            Session::flash('error', __('err_rate_limit', ['n' => (string) max(1, (int) ceil($wait / 60))]));
            redirect('/admin/login');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::attemptAdmin($email, $password)) {
            RateLimiter::hit($rateKey, 900);
            Session::flash('error', __('err_login'));
            redirect('/admin/login');
        }

        RateLimiter::clear($rateKey);
        redirect('/admin');
    }

    public function logout(): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/login');
        }
        Auth::logout();
        redirect('/admin/login');
    }

    public function dashboard(): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();

        $stats = [
            'parents' => (int) $pdo->query('SELECT COUNT(*) FROM parents')->fetchColumn(),
            'children' => (int) $pdo->query('SELECT COUNT(*) FROM children')->fetchColumn(),
            'questions' => (int) $pdo->query('SELECT COUNT(*) FROM questions WHERE is_active = 1')->fetchColumn(),
            'exams' => (int) $pdo->query('SELECT COUNT(*) FROM exams')->fetchColumn(),
            'running' => (int) $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'running'")->fetchColumn(),
            'sessions_today' => (int) $pdo->query("SELECT COUNT(*) FROM exam_sessions WHERE DATE(started_at) = CURDATE()")->fetchColumn(),
        ];

        $recent = $pdo->query(
            "SELECT es.*, e.title, c.first_name, c.last_name
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             JOIN children c ON c.id = es.child_id
             WHERE es.status IN ('submitted','timed_out')
             ORDER BY es.finished_at DESC LIMIT 10"
        )->fetchAll();

        View::render('admin/dashboard', [
            'title' => __('admin_panel'),
            'stats' => $stats,
            'recent' => $recent,
        ], 'layouts/admin');
    }

    public function questions(): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();
        $subjects = $pdo->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

        $grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
        $sector = (string) ($_GET['sector'] ?? '');
        $subjectId = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;

        $sql = 'SELECT q.*, s.name_az AS subject_name, s.name_ru AS subject_name_ru FROM questions q JOIN subjects s ON s.id = q.subject_id WHERE 1=1';
        $params = [];
        if ($grade > 0) {
            $sql .= ' AND q.grade = ?';
            $params[] = $grade;
        }
        if (in_array($sector, ['az', 'ru'], true)) {
            $sql .= ' AND q.sector = ?';
            $params[] = $sector;
        }
        if ($subjectId > 0) {
            $sql .= ' AND q.subject_id = ?';
            $params[] = $subjectId;
        }
        $sql .= ' ORDER BY q.id DESC LIMIT 200';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        View::render('admin/questions', [
            'title' => __('question_bank'),
            'questions' => $stmt->fetchAll(),
            'subjects' => $subjects,
            'filters' => compact('grade', 'sector', 'subjectId'),
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function showImport(): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();
        $subjects = $pdo->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

        View::render('admin/import_questions', [
            'title' => __('add_question'),
            'subjects' => $subjects,
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function import(): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar/elave');
        }

        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $grade = (int) ($_POST['grade'] ?? 0);
        $sector = (string) ($_POST['sector'] ?? '');
        $raw = (string) ($_POST['raw_text'] ?? '');

        if ($subjectId < 1 || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true) || trim($raw) === '') {
            Session::flash('error', __('err_import_fields'));
            redirect('/admin/suallar/elave');
        }

        $parser = new QuestionParser();
        $parsed = $parser->parse($raw);

        if ($parsed === []) {
            Session::flash('error', __('err_import_parse'));
            redirect('/admin/suallar/elave');
        }

        $pdo = Database::connection();
        $ins = $pdo->prepare(
            'INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $count = 0;
        foreach ($parsed as $q) {
            $ins->execute([
                $subjectId,
                $grade,
                $sector,
                $q['question_text'],
                $q['option_a'],
                $q['option_b'],
                $q['option_c'],
                $q['option_d'],
                $q['option_e'],
                $q['correct_option'],
                Auth::adminId(),
            ]);
            $count++;
        }

        Session::flash('success', __('ok_questions_imported', ['n' => (string) $count]));
        redirect('/admin/suallar?grade=' . $grade . '&sector=' . $sector . '&subject_id=' . $subjectId);
    }

    public function deleteQuestion(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar');
        }
        Database::connection()->prepare('DELETE FROM questions WHERE id = ?')->execute([(int) $id]);
        Session::flash('success', __('ok_question_deleted'));
        redirect('/admin/suallar');
    }

    public function exams(): void
    {
        Auth::requireAdmin();
        $exams = Database::connection()->query(
            'SELECT e.*,
                (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) AS question_count,
                (SELECT COUNT(*) FROM exam_sessions es WHERE es.exam_id = e.id AND es.status = "in_progress") AS live_count
             FROM exams e ORDER BY e.id DESC'
        )->fetchAll();

        View::render('admin/exams', [
            'title' => __('exams'),
            'exams' => $exams,
        ], 'layouts/admin');
    }

    public function showCreateExam(): void
    {
        Auth::requireAdmin();
        $subjects = Database::connection()->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        View::render('admin/exam_create', [
            'title' => __('create_exam'),
            'subjects' => $subjects,
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function createExam(): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar/yeni');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $grade = (int) ($_POST['grade'] ?? 0);
        $sector = (string) ($_POST['sector'] ?? '');
        $duration = max(5, (int) ($_POST['duration_minutes'] ?? 60));
        $startsAt = trim((string) ($_POST['starts_at'] ?? '')) ?: null;
        $endsAt = trim((string) ($_POST['ends_at'] ?? '')) ?: null;
        $subjectIds = $_POST['subject_ids'] ?? [];
        $counts = $_POST['question_counts'] ?? [];

        if ($title === '' || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true) || !is_array($subjectIds) || $subjectIds === []) {
            Session::flash('error', __('err_exam_create'));
            redirect('/admin/imtahanlar/yeni');
        }

        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO exams (title, grade, sector, duration_minutes, starts_at, ends_at, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$title, $grade, $sector, $duration, $startsAt, $endsAt, 'draft', Auth::adminId()]);

        $examId = (int) $pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO exam_subjects (exam_id, subject_id, question_count) VALUES (?, ?, ?)');

        foreach ($subjectIds as $sid) {
            $sid = (int) $sid;
            $cnt = max(1, (int) ($counts[$sid] ?? 10));
            $ins->execute([$examId, $sid, $cnt]);
        }

        $picked = (new ExamService())->pickQuestions($examId);
        Session::flash('success', __('ok_exam_created', ['n' => (string) $picked]));
        redirect('/admin/imtahanlar');
    }

    public function startExam(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar');
        }
        $examId = (int) $id;
        $pdo = Database::connection();
        $exam = $pdo->prepare('SELECT id, status FROM exams WHERE id = ?');
        $exam->execute([$examId]);
        $row = $exam->fetch();
        if (!$row) {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/admin/imtahanlar');
        }

        // Do not repick questions if exam already running or has student sessions
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ?');
        $countStmt->execute([$examId]);
        $sessionCount = (int) $countStmt->fetchColumn();

        $qCountStmt = $pdo->prepare('SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?');
        $qCountStmt->execute([$examId]);
        $existingQuestions = (int) $qCountStmt->fetchColumn();

        $service = new ExamService();
        if ($row['status'] === 'running' || $sessionCount > 0) {
            if ($existingQuestions === 0) {
                $count = $service->pickQuestions($examId);
            } else {
                $count = $existingQuestions;
            }
        } else {
            $count = $service->pickQuestions($examId);
        }

        $pdo->prepare("UPDATE exams SET status = 'running' WHERE id = ?")->execute([$examId]);
        Session::flash('success', __('ok_exam_started', ['n' => (string) $count]));
        redirect('/admin/imtahanlar');
    }

    public function stopExam(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar');
        }
        Database::connection()->prepare("UPDATE exams SET status = 'finished' WHERE id = ?")->execute([(int) $id]);
        Session::flash('success', __('ok_exam_finished'));
        redirect('/admin/imtahanlar');
    }

    public function examMonitor(string $id): void
    {
        Auth::requireAdmin();
        $examId = (int) $id;
        $pdo = Database::connection();
        $exam = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
        $exam->execute([$examId]);
        $examRow = $exam->fetch();
        if (!$examRow) {
            redirect('/admin/imtahanlar');
        }

        $sessions = $pdo->prepare(
            'SELECT es.*, c.first_name, c.last_name, c.grade, c.sector
             FROM exam_sessions es
             JOIN children c ON c.id = es.child_id
             WHERE es.exam_id = ?
             ORDER BY es.started_at DESC'
        );
        $sessions->execute([$examId]);

        View::render('admin/exam_monitor', [
            'title' => __('live_monitor'),
            'exam' => $examRow,
            'sessions' => $sessions->fetchAll(),
        ], 'layouts/admin');
    }

    public function parents(): void
    {
        Auth::requireAdmin();
        $rows = Database::connection()->query(
            'SELECT p.*, (SELECT COUNT(*) FROM children c WHERE c.parent_id = p.id) AS child_count
             FROM parents p ORDER BY p.id DESC LIMIT 200'
        )->fetchAll();

        View::render('admin/parents', [
            'title' => __('parents'),
            'parents' => $rows,
        ], 'layouts/admin');
    }

    public function parentShow(string $id): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();
        $parentId = (int) $id;

        $stmt = $pdo->prepare('SELECT * FROM parents WHERE id = ?');
        $stmt->execute([$parentId]);
        $parent = $stmt->fetch();
        if (!$parent) {
            Session::flash('error', __('err_parent_not_found'));
            redirect('/admin/valideynler');
        }

        $children = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY id DESC');
        $children->execute([$parentId]);
        $kids = $children->fetchAll();

        $sessions = $pdo->prepare(
            'SELECT es.*, e.title, c.first_name, c.last_name
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             JOIN children c ON c.id = es.child_id
             WHERE c.parent_id = ?
             ORDER BY es.finished_at DESC, es.id DESC
             LIMIT 50'
        );
        $sessions->execute([$parentId]);

        View::render('admin/parent_show', [
            'title' => $parent['first_name'] . ' ' . $parent['last_name'],
            'parent' => $parent,
            'children' => $kids,
            'sessions' => $sessions->fetchAll(),
        ], 'layouts/admin');
    }

    public function resetParentPassword(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/valideynler');
        }

        $parentId = (int) $id;
        $newPass = trim((string) ($_POST['new_password'] ?? ''));
        if (strlen($newPass) < 6) {
            Session::flash('error', __('err_password_len'));
            redirect('/admin/valideyn/' . $parentId);
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, email FROM parents WHERE id = ?');
        $stmt->execute([$parentId]);
        $parent = $stmt->fetch();
        if (!$parent) {
            Session::flash('error', __('err_parent_not_found'));
            redirect('/admin/valideynler');
        }

        $pdo->prepare('UPDATE parents SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($newPass, PASSWORD_DEFAULT), $parentId]);

        Session::flash('success', __('ok_parent_password_reset', ['email' => $parent['email'], 'password' => $newPass]));
        redirect('/admin/valideyn/' . $parentId);
    }

    public function deleteParent(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/valideynler');
        }

        $parentId = (int) $id;
        Database::connection()->prepare('DELETE FROM parents WHERE id = ?')->execute([$parentId]);
        Session::flash('success', __('ok_parent_deleted'));
        redirect('/admin/valideynler');
    }

    public function children(): void
    {
        Auth::requireAdmin();
        $rows = Database::connection()->query(
            'SELECT c.*, p.first_name AS parent_first, p.last_name AS parent_last, p.email AS parent_email
             FROM children c
             JOIN parents p ON p.id = c.parent_id
             ORDER BY c.id DESC LIMIT 300'
        )->fetchAll();

        View::render('admin/children', [
            'title' => __('children_list'),
            'children' => $rows,
        ], 'layouts/admin');
    }

    public function resetChildPassword(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/usaqlar');
        }

        $childId = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM children WHERE id = ?');
        $stmt->execute([$childId]);
        $child = $stmt->fetch();
        if (!$child) {
            Session::flash('error', __('err_child_not_found'));
            redirect('/admin/usaqlar');
        }

        $custom = trim((string) ($_POST['new_password'] ?? ''));
        $plain = $custom !== '' ? $custom : child_password($child['first_name'], $child['birth_date']);
        if (strlen($plain) < 4) {
            Session::flash('error', __('err_password_len'));
            redirect('/admin/usaqlar');
        }
        $hint = child_password_hash($plain);
        $newToken = generate_token(16);

        $pdo->prepare('UPDATE children SET password_hint = ?, access_token = ? WHERE id = ?')
            ->execute([$hint, $newToken, $childId]);

        Session::flash('success', __('ok_child_password_reset', [
            'name' => $child['first_name'],
            'password' => $plain,
            'link' => url('/imtahan/' . $newToken),
        ]));

        $back = Security::safeRedirectPath((string) ($_POST['back'] ?? ''));
        if (($back === '/admin/valideyn/' . $child['parent_id']) || ((string) ($_POST['back'] ?? '') === 'parent')) {
            redirect('/admin/valideyn/' . $child['parent_id']);
        }
        redirect('/admin/usaqlar');
    }

    public function deleteChild(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/usaqlar');
        }

        $childId = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT parent_id FROM children WHERE id = ?');
        $stmt->execute([$childId]);
        $child = $stmt->fetch();
        if (!$child) {
            Session::flash('error', __('err_child_not_found'));
            redirect('/admin/usaqlar');
        }

        $pdo->prepare('DELETE FROM children WHERE id = ?')->execute([$childId]);
        Session::flash('success', __('ok_child_deleted'));

        $back = (string) ($_POST['back'] ?? '');
        if ($back === 'parent') {
            redirect('/admin/valideyn/' . $child['parent_id']);
        }
        redirect('/admin/usaqlar');
    }
}
