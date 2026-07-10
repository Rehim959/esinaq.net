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
            "SELECT es.*, e.title, c.first_name, c.last_name, c.patronymic
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

    public function showQuestion(string $id): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT q.*, s.name_az AS subject_name, s.name_ru AS subject_name_ru
             FROM questions q JOIN subjects s ON s.id = q.subject_id WHERE q.id = ?'
        );
        $stmt->execute([(int) $id]);
        $q = $stmt->fetch();
        if (!$q) {
            Session::flash('error', __('err_question_not_found'));
            redirect('/admin/suallar');
        }

        View::render('admin/question_show', [
            'title' => __('question_detail') . ' #' . (int) $q['id'],
            'q' => $q,
        ], 'layouts/admin');
    }

    public function showEditQuestion(string $id): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
        $stmt->execute([(int) $id]);
        $q = $stmt->fetch();
        if (!$q) {
            Session::flash('error', __('err_question_not_found'));
            redirect('/admin/suallar');
        }
        $subjects = $pdo->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

        View::render('admin/question_edit', [
            'title' => __('edit_question') . ' #' . (int) $q['id'],
            'q' => $q,
            'subjects' => $subjects,
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function updateQuestion(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar');
        }

        $qid = (int) $id;
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $grade = (int) ($_POST['grade'] ?? 0);
        $sector = (string) ($_POST['sector'] ?? '');
        $text = trim((string) ($_POST['question_text'] ?? ''));
        $a = trim((string) ($_POST['option_a'] ?? ''));
        $b = trim((string) ($_POST['option_b'] ?? ''));
        $c = trim((string) ($_POST['option_c'] ?? ''));
        $d = trim((string) ($_POST['option_d'] ?? ''));
        $eOpt = trim((string) ($_POST['option_e'] ?? ''));
        $correct = strtoupper(trim((string) ($_POST['correct_option'] ?? '')));

        if (
            $subjectId < 1 || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true)
            || $text === '' || $a === '' || $b === '' || $c === '' || $d === ''
            || !in_array($correct, ['A', 'B', 'C', 'D', 'E'], true)
            || ($correct === 'E' && $eOpt === '')
        ) {
            Session::flash('error', __('err_question_fields'));
            redirect('/admin/suallar/duzelis/' . $qid);
        }

        Database::connection()->prepare(
            'UPDATE questions SET subject_id=?, grade=?, sector=?, question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=?, correct_option=?, updated_at=NOW()
             WHERE id=?'
        )->execute([
            $subjectId, $grade, $sector, $text, $a, $b, $c, $d,
            $eOpt !== '' ? $eOpt : null,
            $correct, $qid,
        ]);

        Session::flash('success', __('ok_question_updated'));
        redirect('/admin/suallar/bax/' . $qid);
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
        $duration = max(5, min(300, (int) ($_POST['duration_minutes'] ?? 60)));
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
        $subjectIds = $_POST['subject_ids'] ?? [];
        $counts = $_POST['question_counts'] ?? [];

        $startsAtSql = $startsAt !== '' ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $startsAt))) : null;
        $endsAtSql = $endsAt !== '' ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $endsAt))) : null;

        if (
            $title === '' || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true)
            || !is_array($subjectIds) || $subjectIds === []
            || !$startsAtSql || !$endsAtSql || strtotime($endsAtSql) <= strtotime($startsAtSql)
        ) {
            Session::flash('error', __('err_exam_create'));
            redirect('/admin/imtahanlar/yeni');
        }

        $status = (strtotime($startsAtSql) <= time()) ? 'running' : 'scheduled';
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO exams (title, grade, sector, duration_minutes, starts_at, ends_at, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$title, $grade, $sector, $duration, $startsAtSql, $endsAtSql, $status, Auth::adminId()]);

        $examId = (int) $pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO exam_subjects (exam_id, subject_id, question_count) VALUES (?, ?, ?)');

        foreach ($subjectIds as $sid) {
            $sid = (int) $sid;
            $cnt = max(1, min(50, (int) ($counts[$sid] ?? 10)));
            $ins->execute([$examId, $sid, $cnt]);
        }

        $picked = (new ExamService())->pickQuestions($examId);
        if ($status === 'running' && $picked === 0) {
            Session::flash('error', __('err_exam_create'));
            redirect('/admin/imtahanlar');
        }
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

        // Make exam visible immediately: open window from now (fix future starts_at)
        $pdo->prepare(
            "UPDATE exams SET
                status = 'running',
                starts_at = NOW(),
                ends_at = CASE
                    WHEN ends_at IS NULL OR ends_at <= NOW()
                        THEN DATE_ADD(NOW(), INTERVAL GREATEST(duration_minutes, 60) MINUTE)
                    ELSE ends_at
                END
             WHERE id = ?"
        )->execute([$examId]);
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
        $examId = (int) $id;
        $pdo = Database::connection();
        $pdo->prepare("UPDATE exams SET status = 'finished' WHERE id = ?")->execute([$examId]);

        // Auto-finalize any still in-progress sessions with their current answers
        $open = $pdo->prepare("SELECT id FROM exam_sessions WHERE exam_id = ? AND status IN ('pending','in_progress')");
        $open->execute([$examId]);
        $service = new ExamService();
        foreach ($open->fetchAll() as $row) {
            $service->submit((int) $row['id'], 'timed_out');
        }

        Session::flash('success', __('ok_exam_finished'));
        redirect('/admin/imtahanlar');
    }

    public function showEditExam(string $id): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
        $stmt->execute([(int) $id]);
        $exam = $stmt->fetch();
        if (!$exam) {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/admin/imtahanlar');
        }

        View::render('admin/exam_edit', [
            'title' => __('edit_exam_schedule'),
            'exam' => $exam,
        ], 'layouts/admin');
    }

    public function updateExamSchedule(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar');
        }

        $examId = (int) $id;
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $duration = max(5, min(300, (int) ($_POST['duration_minutes'] ?? 60)));

        $startsAtSql = $startsAt !== '' ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $startsAt))) : null;
        $endsAtSql = $endsAt !== '' ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $endsAt))) : null;

        if ($title === '' || !$startsAtSql || !$endsAtSql || strtotime($endsAtSql) <= strtotime($startsAtSql)) {
            Session::flash('error', __('err_exam_create'));
            redirect('/admin/imtahanlar/duzelis/' . $examId);
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT status FROM exams WHERE id = ?');
        $stmt->execute([$examId]);
        $row = $stmt->fetch();
        if (!$row) {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/admin/imtahanlar');
        }

        // Recalculate status from new window (unless already finished — keep finished unless window reopened)
        $status = $row['status'];
        if ($status !== 'finished' && $status !== 'cancelled') {
            if (strtotime($endsAtSql) <= time()) {
                $status = 'finished';
            } elseif (strtotime($startsAtSql) <= time()) {
                $status = 'running';
            } else {
                $status = 'scheduled';
            }
        }

        $pdo->prepare(
            'UPDATE exams SET title = ?, duration_minutes = ?, starts_at = ?, ends_at = ?, status = ? WHERE id = ?'
        )->execute([$title, $duration, $startsAtSql, $endsAtSql, $status, $examId]);

        \App\Services\ExamScheduler::sync();
        Session::flash('success', __('ok_exam_schedule_updated'));
        redirect('/admin/imtahanlar');
    }

    public function cloneExam(string $id): void
    {
        Auth::requireSuperAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar');
        }

        $examId = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
        $stmt->execute([$examId]);
        $src = $stmt->fetch();
        if (!$src) {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/admin/imtahanlar');
        }

        $title = (string) $src['title'];
        if (!str_contains($title, '(yenidən)')) {
            $title .= ' (yenidən)';
        }

        $duration = max(5, (int) $src['duration_minutes']);
        $windowMin = max($duration, 60);
        $pdo->prepare(
            "INSERT INTO exams (title, grade, sector, duration_minutes, questions_per_subject, starts_at, ends_at, status, shuffle_questions, created_by)
             VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL {$windowMin} MINUTE), ?, ?, ?)"
        )->execute([
            $title,
            (int) $src['grade'],
            $src['sector'],
            $duration,
            (int) $src['questions_per_subject'],
            'running',
            (int) $src['shuffle_questions'],
            Auth::adminId(),
        ]);

        $newId = (int) $pdo->lastInsertId();
        $subjects = $pdo->prepare('SELECT subject_id, question_count FROM exam_subjects WHERE exam_id = ?');
        $subjects->execute([$examId]);
        $ins = $pdo->prepare('INSERT INTO exam_subjects (exam_id, subject_id, question_count) VALUES (?, ?, ?)');
        foreach ($subjects->fetchAll() as $row) {
            $ins->execute([$newId, (int) $row['subject_id'], (int) $row['question_count']]);
        }

        $count = (new ExamService())->pickQuestions($newId);
        Session::flash('success', __('ok_exam_cloned', ['n' => (string) $count]));
        redirect('/admin/imtahanlar');
    }

    public function deleteExam(string $id): void
    {
        Auth::requireSuperAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar');
        }

        $examId = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM exams WHERE id = ?');
        $stmt->execute([$examId]);
        if (!$stmt->fetch()) {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/admin/imtahanlar');
        }

        $pdo->prepare('DELETE FROM exams WHERE id = ?')->execute([$examId]);
        Session::flash('success', __('ok_exam_deleted'));
        redirect('/admin/imtahanlar');
    }

    public function team(): void
    {
        Auth::requireSuperAdmin();
        $admins = Database::connection()->query(
            'SELECT id, email, full_name, role, is_active, created_at FROM admins ORDER BY id ASC'
        )->fetchAll();

        View::render('admin/team', [
            'title' => __('team_title'),
            'admins' => $admins,
        ], 'layouts/admin');
    }

    public function addModerator(): void
    {
        Auth::requireSuperAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/komanda');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'moderator');
        if (!in_array($role, ['super_admin', 'moderator'], true)) {
            $role = 'moderator';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $fullName === '' || strlen($password) < 8) {
            Session::flash('error', __('err_admin_fields'));
            redirect('/admin/komanda');
        }

        $pdo = Database::connection();
        $exists = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            Session::flash('error', __('err_moderator_exists'));
            redirect('/admin/komanda');
        }

        $pdo->prepare(
            'INSERT INTO admins (email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 1)'
        )->execute([$email, password_hash($password, PASSWORD_DEFAULT), $fullName, $role]);

        Session::flash('success', __('ok_moderator_added'));
        redirect('/admin/komanda');
    }

    public function toggleAdmin(string $id): void
    {
        Auth::requireSuperAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/komanda');
        }

        $adminId = (int) $id;
        if ($adminId === Auth::adminId()) {
            Session::flash('error', __('err_cannot_demote_self'));
            redirect('/admin/komanda');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, is_active FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        if (!$row) {
            Session::flash('error', __('err_forbidden'));
            redirect('/admin/komanda');
        }

        $newActive = (int) $row['is_active'] ? 0 : 1;
        $pdo->prepare('UPDATE admins SET is_active = ? WHERE id = ?')->execute([$newActive, $adminId]);
        Session::flash('success', __('ok_moderator_updated'));
        redirect('/admin/komanda');
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
            'SELECT es.*, c.first_name, c.last_name, c.patronymic, c.grade, c.sector
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
        $q = trim((string) ($_GET['q'] ?? ''));
        $pdo = Database::connection();

        if ($q !== '') {
            $like = '%' . $q . '%';
            $stmt = $pdo->prepare(
                'SELECT p.*, (SELECT COUNT(*) FROM children c WHERE c.parent_id = p.id) AS child_count
                 FROM parents p
                 WHERE p.first_name LIKE ? OR p.last_name LIKE ? OR p.patronymic LIKE ?
                    OR p.email LIKE ? OR p.phone LIKE ?
                    OR CONCAT(p.first_name, " ", p.last_name, " ", p.patronymic) LIKE ?
                 ORDER BY p.id DESC LIMIT 200'
            );
            $stmt->execute([$like, $like, $like, $like, $like, $like]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $pdo->query(
                'SELECT p.*, (SELECT COUNT(*) FROM children c WHERE c.parent_id = p.id) AS child_count
                 FROM parents p ORDER BY p.id DESC LIMIT 200'
            )->fetchAll();
        }

        View::render('admin/parents', [
            'title' => __('parents'),
            'parents' => $rows,
            'search' => $q,
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
            'SELECT es.*, e.title, c.first_name, c.last_name, c.patronymic
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             JOIN children c ON c.id = es.child_id
             WHERE c.parent_id = ?
             ORDER BY es.finished_at DESC, es.id DESC
             LIMIT 50'
        );
        $sessions->execute([$parentId]);

        View::render('admin/parent_show', [
            'title' => person_full_name($parent),
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
        Auth::requireSuperAdmin();
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
        $q = trim((string) ($_GET['q'] ?? ''));
        $pdo = Database::connection();

        $base = 'SELECT c.*, p.first_name AS parent_first, p.last_name AS parent_last, p.patronymic AS parent_patronymic,
                    p.email AS parent_email, p.phone AS parent_phone
             FROM children c
             JOIN parents p ON p.id = c.parent_id';

        if ($q !== '') {
            $like = '%' . $q . '%';
            $stmt = $pdo->prepare(
                $base . ' WHERE c.first_name LIKE ? OR c.last_name LIKE ? OR c.patronymic LIKE ?
                    OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?
                    OR CONCAT(c.first_name, " ", c.last_name, " ", c.patronymic) LIKE ?
                 ORDER BY c.id DESC LIMIT 300'
            );
            $stmt->execute([$like, $like, $like, $like, $like, $like, $like, $like]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $pdo->query($base . ' ORDER BY c.id DESC LIMIT 300')->fetchAll();
        }

        View::render('admin/children', [
            'title' => __('children_list'),
            'children' => $rows,
            'search' => $q,
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
        Auth::requireSuperAdmin();
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
