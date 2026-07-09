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
            'title' => 'Valideyn paneli',
            'children' => $kids,
            'stats' => $stats,
        ], 'layouts/parent');
    }

    public function showAddChild(): void
    {
        Auth::requireParent();
        View::render('parent/add_child', [
            'title' => 'Uşaq əlavə et',
            'grades' => grades_list(),
        ], 'layouts/parent');
    }

    public function addChild(): void
    {
        Auth::requireParent();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Təhlükəsizlik xətası.');
            redirect('/valideyn/usaq-elave');
        }

        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $birth = (string) ($_POST['birth_date'] ?? '');
        $grade = (int) ($_POST['grade'] ?? 0);
        $sector = (string) ($_POST['sector'] ?? '');
        $gender = (string) ($_POST['gender'] ?? '');

        if ($first === '' || $last === '' || $birth === '' || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true) || !in_array($gender, ['boy', 'girl'], true)) {
            Session::flash('error', 'Bütün sahələri düzgün doldurun.');
            flash_old($_POST);
            redirect('/valideyn/usaq-elave');
        }

        $token = generate_token(16);
        $hint = child_password($first, $birth);
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO children (parent_id, first_name, last_name, birth_date, grade, sector, gender, access_token, password_hint)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([Auth::parentId(), $first, $last, $birth, $grade, $sector, $gender, $token, $hint]);

        $childId = (int) $pdo->lastInsertId();
        $parent = $pdo->prepare('SELECT * FROM parents WHERE id = ?');
        $parent->execute([Auth::parentId()]);
        $p = $parent->fetch();

        $child = [
            'first_name' => $first,
            'last_name' => $last,
            'grade' => $grade,
            'sector' => $sector,
            'password_hint' => $hint,
        ];

        $link = url('/imtahan/' . $token);
        (new MailService())->childRegistered($p['email'], $p['first_name'], $child, $link);

        clear_old();
        Session::flash('success', $first . ' əlavə olundu. Giriş məlumatları e-poçtunuza göndərildi.');
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
            Session::flash('error', 'Uşaq tapılmadı.');
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
            'SELECT s.name_az, AVG(CASE WHEN sa.is_correct = 1 THEN 100 ELSE 0 END) AS avg_pct, COUNT(*) AS total
             FROM student_answers sa
             JOIN exam_sessions es ON es.id = sa.session_id
             JOIN questions q ON q.id = sa.question_id
             JOIN subjects s ON s.id = q.subject_id
             WHERE es.child_id = ? AND es.status IN ("submitted","timed_out")
             GROUP BY s.id, s.name_az
             ORDER BY avg_pct ASC'
        );
        $weak->execute([$childId]);

        View::render('parent/child_results', [
            'title' => $kid['first_name'] . ' — nəticələr',
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
            'SELECT es.*, e.title, c.first_name, c.last_name, c.parent_id
             FROM exam_sessions es
             JOIN exams e ON e.id = es.exam_id
             JOIN children c ON c.id = es.child_id
             WHERE es.id = ?'
        );
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session || (int) $session['parent_id'] !== Auth::parentId()) {
            Session::flash('error', 'Nəticə tapılmadı.');
            redirect('/valideyn');
        }

        if (!in_array($session['status'], ['submitted', 'timed_out'], true)) {
            Session::flash('error', 'İmtahan hələ bitməyib.');
            redirect('/valideyn/usaq/' . $session['child_id']);
        }

        $details = (new \App\Services\ExamService())->getResultDetails($sessionId);
        $wrongOnly = array_values(array_filter($details, fn ($d) => $d['is_correct'] !== null && (int) $d['is_correct'] === 0));

        View::render('parent/session_detail', [
            'title' => 'Səhv suallar',
            'session' => $session,
            'wrong' => $wrongOnly,
        ], 'layouts/parent');
    }
}
