<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Services\MailService;

final class ParentController
{
    public function dashboard(): void
    {
        Auth::requireParent();
        $pdo = Database::connection();
        $parentId = Auth::parentId();

        $children = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY created_at DESC');
        $children->execute([$parentId]);
        $kids = $children->fetchAll();

        $stats = [];
        foreach ($kids as $kid) {
            $s = $pdo->prepare(
                'SELECT es.*, e.title, e.grade, e.sector
                 FROM exam_sessions es
                 JOIN exams e ON e.id = es.exam_id
                 WHERE es.child_id = ? AND es.status IN ("submitted","timed_out")
                 ORDER BY es.finished_at DESC LIMIT 5'
            );
            $s->execute([$kid['id']]);
            $stats[$kid['id']] = $s->fetchAll();
        }

        View::render('parent/dashboard', [
            'title' => __('parent_panel'),
            'children' => $kids,
            'stats' => $stats,
        ], 'layouts/parent');
    }

    public function showAddChild(): void
    {
        Auth::requireParent();
        View::render('parent/add_child', [
            'title' => __('add_child'),
            'grades' => grades_list(),
        ], 'layouts/parent');
    }

    public function addChild(): void
    {
        Auth::requireParent();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/valideyn/usaq-elave');
        }

        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $patronymic = trim((string) ($_POST['patronymic'] ?? ''));
        $day = (int) ($_POST['birth_day'] ?? 0);
        $month = (int) ($_POST['birth_month'] ?? 0);
        $year = (int) ($_POST['birth_year'] ?? 0);
        $grade = (int) ($_POST['grade'] ?? 0);
        $sector = (string) ($_POST['sector'] ?? '');
        $gender = (string) ($_POST['gender'] ?? '');

        $birth = '';
        if ($day > 0 && $month > 0 && $year > 0 && checkdate($month, $day, $year)) {
            $birth = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        if ($first === '' || $last === '' || $patronymic === '' || $birth === '' || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true) || !in_array($gender, ['boy', 'girl'], true)) {
            Session::flash('error', __('err_child_fields'));
            flash_old($_POST);
            redirect('/valideyn/usaq-elave');
        }

        $token = generate_token(16);
        $plainPassword = child_password($first, $birth);
        $hint = child_password_hash($plainPassword);
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO children (parent_id, first_name, last_name, patronymic, birth_date, grade, sector, gender, access_token, password_hint)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([Auth::parentId(), $first, $last, $patronymic, $birth, $grade, $sector, $gender, $token, $hint]);

        $childId = (int) $pdo->lastInsertId();
        $parent = $pdo->prepare('SELECT * FROM parents WHERE id = ?');
        $parent->execute([Auth::parentId()]);
        $p = $parent->fetch();

        $child = [
            'first_name' => $first,
            'last_name' => $last,
            'patronymic' => $patronymic,
            'grade' => $grade,
            'sector' => $sector,
            'password_hint' => $plainPassword,
        ];

        $link = url('/imtahan/' . $token);
        (new MailService())->childRegistered($p['email'], $p['first_name'], $child, $link);

        clear_old();
        Session::flash('success', __('ok_child_added', ['name' => $first]));
        redirect('/valideyn');
    }

    public function childResults(string $id): void
    {
        Auth::requireParent();
        $childId = (int) $id;
        $pdo = Database::connection();

        $child = $pdo->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ?');
        $child->execute([$childId, Auth::parentId()]);
        $kid = $child->fetch();
        if (!$kid) {
            Session::flash('error', __('err_child_not_found'));
            redirect('/valideyn');
        }

        $sessions = $pdo->prepare(
            'SELECT es.*, e.title, e.grade, e.sector
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             WHERE es.child_id = ? AND es.status IN ("submitted","timed_out")
             ORDER BY es.finished_at DESC'
        );
        $sessions->execute([$childId]);
        $list = $sessions->fetchAll();

        // Monthly averages for chart
        $monthly = $pdo->prepare(
            'SELECT DATE_FORMAT(finished_at, "%Y-%m") AS ym, AVG(percentage) AS avg_pct, COUNT(*) AS cnt
             FROM exam_sessions
             WHERE child_id = ? AND status IN ("submitted","timed_out") AND finished_at IS NOT NULL
             GROUP BY ym ORDER BY ym'
        );
        $monthly->execute([$childId]);

        // Subject weakness
        $weak = $pdo->prepare(
            'SELECT s.name_az, s.name_ru, AVG(CASE WHEN sa.is_correct = 1 THEN 100 ELSE 0 END) AS avg_pct, COUNT(*) AS total
             FROM student_answers sa
             JOIN exam_sessions es ON es.id = sa.session_id
             JOIN questions q ON q.id = sa.question_id
             JOIN subjects s ON s.id = q.subject_id
             WHERE es.child_id = ? AND es.status IN ("submitted","timed_out")
             GROUP BY s.id, s.name_az, s.name_ru
             ORDER BY avg_pct ASC'
        );
        $weak->execute([$childId]);

        View::render('parent/child_results', [
            'title' => __('child_results', ['name' => $kid['first_name']]),
            'child' => $kid,
            'sessions' => $list,
            'monthly' => $monthly->fetchAll(),
            'weakSubjects' => $weak->fetchAll(),
            'examLink' => url('/imtahan/' . $kid['access_token']),
        ], 'layouts/parent');
    }

    public function sessionDetail(string $id): void
    {
        Auth::requireParent();
        $sessionId = (int) $id;
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT es.*, e.title, e.status AS exam_status, c.first_name, c.last_name, c.patronymic, c.parent_id, c.id AS child_id
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             JOIN children c ON c.id = es.child_id
             WHERE es.id = ?'
        );
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session || (int) $session['parent_id'] !== Auth::parentId()) {
            Session::flash('error', __('err_result_not_found'));
            redirect('/valideyn');
        }

        if (!in_array($session['status'], ['submitted', 'timed_out'], true)) {
            Session::flash('error', __('err_exam_not_finished'));
            redirect('/valideyn/usaq/' . $session['child_id']);
        }

        $revealAnswers = ($session['exam_status'] ?? '') === 'finished';
        $details = (new \App\Services\ExamService())->getResultDetails($sessionId);
        $wrongOnly = [];
        if ($revealAnswers) {
            $wrongOnly = array_values(array_filter($details, fn ($d) => $d['is_correct'] !== null && (int) $d['is_correct'] === 0));
        }

        View::render('parent/session_detail', [
            'title' => $revealAnswers ? __('wrong_questions') : __('result'),
            'session' => $session,
            'wrong' => $wrongOnly,
            'revealAnswers' => $revealAnswers,
            'message' => grade_message((string) ($session['letter_grade'] ?? 'E')),
            'band' => __('grade_band_' . strtoupper((string) ($session['letter_grade'] ?? 'E'))),
        ], 'layouts/parent');
    }
}
