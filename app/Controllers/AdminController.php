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
use App\Services\ExamInviteService;
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

        $filters = $this->parseQuestionListFilters();
        [$where, $params] = $this->buildQuestionFilter($filters);

        $sql = 'SELECT q.*, s.name_az AS subject_name, s.name_ru AS subject_name_ru
                FROM questions q JOIN subjects s ON s.id = q.subject_id
                WHERE 1=1' . $where . ' ORDER BY q.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $questions = $stmt->fetchAll();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM questions q WHERE 1=1' . $where);
        $countStmt->execute($params);
        $statsTotal = (int) $countStmt->fetchColumn();

        $stats = $this->questionFilterStats($pdo, $filters, $where, $params);

        View::render('admin/questions', [
            'title' => __('question_bank'),
            'questions' => $questions,
            'subjects' => $subjects,
            'filters' => $filters,
            'grades' => grades_list(),
            'statsTotal' => $statsTotal,
            'statsMode' => $stats['mode'],
            'statsRows' => $stats['rows'],
            'listLimit' => 200,
        ], 'layouts/admin');
    }

    public function exportQuestions(): void
    {
        Auth::requireAdmin();
        $format = strtolower((string) ($_GET['format'] ?? ''));
        if (!in_array($format, ['excel', 'word', 'pdf'], true)) {
            Session::flash('error', __('err_export_format'));
            redirect('/admin/suallar');
        }

        $filters = $this->parseQuestionListFilters();
        [$where, $params] = $this->buildQuestionFilter($filters);
        $pdo = Database::connection();

        $cap = 5000;
        $sql = 'SELECT q.*, s.name_az AS subject_name, s.name_ru AS subject_name_ru
                FROM questions q JOIN subjects s ON s.id = q.subject_id
                WHERE 1=1' . $where . ' ORDER BY s.sort_order, q.grade, q.sector, q.id ASC LIMIT ' . ($cap + 1);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if ($rows === []) {
            Session::flash('error', __('export_empty'));
            $qs = $this->questionFilterQueryString($filters);
            redirect('/admin/suallar' . ($qs !== '' ? '?' . $qs : ''));
        }

        if (count($rows) > $cap) {
            Session::flash('error', __('err_export_too_many', ['n' => (string) $cap]));
            $qs = $this->questionFilterQueryString($filters);
            redirect('/admin/suallar' . ($qs !== '' ? '?' . $qs : ''));
        }

        $exportRows = [];
        foreach ($rows as $i => $q) {
            $exportRows[] = [
                'n' => $i + 1,
                'id' => (int) $q['id'],
                'subject' => locale() === 'ru'
                    ? (string) ($q['subject_name_ru'] ?? $q['subject_name'])
                    : (string) $q['subject_name'],
                'grade' => (int) $q['grade'],
                'sector' => strtoupper((string) $q['sector']),
                'question' => $this->exportPlainText((string) $q['question_text']),
                'option_a' => $this->exportPlainText((string) $q['option_a']),
                'option_b' => $this->exportPlainText((string) $q['option_b']),
                'option_c' => $this->exportPlainText((string) $q['option_c']),
                'option_d' => $this->exportPlainText((string) $q['option_d']),
                'option_e' => $q['option_e'] !== null && $q['option_e'] !== ''
                    ? $this->exportPlainText((string) $q['option_e']) : '',
                'correct' => (string) $q['correct_option'],
            ];
        }

        $stamp = date('Y-m-d_H-i');
        if ($format === 'excel') {
            $this->exportQuestionsExcel($exportRows, $stamp);
            return;
        }
        if ($format === 'word') {
            $this->exportQuestionsWord($exportRows, $stamp);
            return;
        }

        View::render('admin/questions_export_print', [
            'title' => __('export_pdf') . ' — ' . brand_name(),
            'rows' => $exportRows,
            'filters' => $filters,
            'total' => count($exportRows),
        ], null);
    }

    /**
     * @return array{grade:int,sector:string,subjectId:int,kind:string}
     */
    private function parseQuestionListFilters(): array
    {
        $grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
        if ($grade < 1 || $grade > 11) {
            $grade = 0;
        }
        $sector = (string) ($_GET['sector'] ?? '');
        if (!in_array($sector, ['az', 'ru'], true)) {
            $sector = '';
        }
        $subjectId = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
        if ($subjectId < 0) {
            $subjectId = 0;
        }
        $kind = (string) ($_GET['kind'] ?? '');
        if (!in_array($kind, ['image', 'text'], true)) {
            $kind = '';
        }
        return ['grade' => $grade, 'sector' => $sector, 'subjectId' => $subjectId, 'kind' => $kind];
    }

    /**
     * @param array{grade:int,sector:string,subjectId:int,kind?:string} $filters
     * @return array{0:string,1:list<mixed>}
     */
    private function buildQuestionFilter(array $filters, string $alias = 'q'): array
    {
        $sql = '';
        $params = [];
        if ($filters['grade'] > 0) {
            $sql .= " AND {$alias}.grade = ?";
            $params[] = $filters['grade'];
        }
        if ($filters['sector'] !== '') {
            $sql .= " AND {$alias}.sector = ?";
            $params[] = $filters['sector'];
        }
        if ($filters['subjectId'] > 0) {
            $sql .= " AND {$alias}.subject_id = ?";
            $params[] = $filters['subjectId'];
        }
        $kind = (string) ($filters['kind'] ?? '');
        if ($kind === 'image') {
            $sql .= " AND (
                {$alias}.question_text LIKE ? OR {$alias}.option_a LIKE ? OR {$alias}.option_b LIKE ?
                OR {$alias}.option_c LIKE ? OR {$alias}.option_d LIKE ? OR IFNULL({$alias}.option_e,'') LIKE ?
            )";
            $params = array_merge($params, ['%<img%', '%<img%', '%<img%', '%<img%', '%<img%', '%<img%']);
        } elseif ($kind === 'text') {
            $sql .= " AND {$alias}.question_text NOT LIKE ?
                AND {$alias}.option_a NOT LIKE ? AND {$alias}.option_b NOT LIKE ?
                AND {$alias}.option_c NOT LIKE ? AND {$alias}.option_d NOT LIKE ?
                AND IFNULL({$alias}.option_e,'') NOT LIKE ?";
            $params = array_merge($params, ['%<img%', '%<img%', '%<img%', '%<img%', '%<img%', '%<img%']);
        }
        return [$sql, $params];
    }

    /**
     * @param array{grade:int,sector:string,subjectId:int,kind?:string} $filters
     */
    private function questionFilterQueryString(array $filters): string
    {
        $parts = [];
        if ($filters['grade'] > 0) {
            $parts[] = 'grade=' . $filters['grade'];
        }
        if ($filters['sector'] !== '') {
            $parts[] = 'sector=' . rawurlencode($filters['sector']);
        }
        if ($filters['subjectId'] > 0) {
            $parts[] = 'subject_id=' . $filters['subjectId'];
        }
        if (($filters['kind'] ?? '') !== '') {
            $parts[] = 'kind=' . rawurlencode((string) $filters['kind']);
        }
        return implode('&', $parts);
    }

    /**
     * @param array{grade:int,sector:string,subjectId:int} $filters
     * @param list<mixed> $params
     * @return array{mode:string,rows:list<array<string,mixed>>}
     */
    private function questionFilterStats(\PDO $pdo, array $filters, string $where, array $params): array
    {
        if ($filters['subjectId'] > 0) {
            $sql = 'SELECT q.grade, q.sector, COUNT(*) AS cnt
                    FROM questions q WHERE 1=1' . $where . '
                    GROUP BY q.grade, q.sector
                    ORDER BY q.grade ASC, q.sector ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return ['mode' => 'by_grade', 'rows' => $stmt->fetchAll()];
        }

        if ($filters['grade'] > 0) {
            $sql = 'SELECT q.subject_id, s.name_az AS subject_name, s.name_ru AS subject_name_ru, q.sector, COUNT(*) AS cnt
                    FROM questions q JOIN subjects s ON s.id = q.subject_id
                    WHERE 1=1' . $where . '
                    GROUP BY q.subject_id, s.name_az, s.name_ru, s.sort_order, q.sector
                    ORDER BY s.sort_order ASC, q.sector ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return ['mode' => 'by_subject', 'rows' => $stmt->fetchAll()];
        }

        $sql = 'SELECT q.subject_id, s.name_az AS subject_name, s.name_ru AS subject_name_ru,
                       q.grade, q.sector, COUNT(*) AS cnt
                FROM questions q JOIN subjects s ON s.id = q.subject_id
                WHERE 1=1' . $where . '
                GROUP BY q.subject_id, s.name_az, s.name_ru, s.sort_order, q.grade, q.sector
                ORDER BY s.sort_order ASC, q.grade ASC, q.sector ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return ['mode' => 'by_subject_grade', 'rows' => $stmt->fetchAll()];
    }

    private function exportPlainText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        return trim($value);
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function exportQuestionsExcel(array $rows, string $stamp): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="suallar_' . $stamp . '.csv"');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, [
            '№', 'ID', __('subject'), __('grade'), __('sector'), __('question'),
            'A', 'B', 'C', 'D', 'E', __('answer_col'),
        ], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['n'], $r['id'], $r['subject'], $r['grade'], $r['sector'],
                $r['question'], $r['option_a'], $r['option_b'], $r['option_c'],
                $r['option_d'], $r['option_e'], $r['correct'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function exportQuestionsWord(array $rows, string $stamp): void
    {
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="suallar_' . $stamp . '.doc"');
        header('Pragma: no-cache');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="utf-8"><title>' . e(__('question_bank')) . '</title>';
        echo '<style>body{font-family:Arial,sans-serif;font-size:12pt}h1{font-size:16pt}';
        echo '.q{margin:0 0 18pt;page-break-inside:avoid}.meta{color:#555;font-size:10pt;margin-bottom:4pt}';
        echo 'ol.options{margin:6pt 0 0 18pt} .ans{font-weight:bold;margin-top:6pt}</style></head><body>';
        echo '<h1>' . e(__('question_bank')) . ' — ' . e(brand_name()) . '</h1>';
        echo '<p>' . e(__('stats_total', ['n' => (string) count($rows)])) . '</p>';

        foreach ($rows as $r) {
            echo '<div class="q">';
            echo '<div class="meta">#' . (int) $r['n'] . ' · ID ' . (int) $r['id'] . ' · '
                . e((string) $r['subject']) . ' · ' . (int) $r['grade'] . ' · ' . e((string) $r['sector']) . '</div>';
            echo '<div><strong>' . (int) $r['n'] . '.</strong> ' . e((string) $r['question']) . '</div>';
            echo '<ol class="options" type="A">';
            echo '<li>' . e((string) $r['option_a']) . '</li>';
            echo '<li>' . e((string) $r['option_b']) . '</li>';
            echo '<li>' . e((string) $r['option_c']) . '</li>';
            echo '<li>' . e((string) $r['option_d']) . '</li>';
            if ((string) $r['option_e'] !== '') {
                echo '<li>' . e((string) $r['option_e']) . '</li>';
            }
            echo '</ol>';
            echo '<div class="ans">' . e(__('answer_col')) . ': ' . e((string) $r['correct']) . '</div>';
            echo '</div>';
        }
        echo '</body></html>';
        exit;
    }

    public function showImport(): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();
        $subjects = $pdo->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        $draft = Session::get('import_draft');
        $old = is_array($draft) ? [
            'subject_id' => (int) ($draft['subject_id'] ?? 0),
            'grade' => (int) ($draft['grade'] ?? 0),
            'sector' => (string) ($draft['sector'] ?? 'az'),
            'raw_text' => (string) ($draft['raw_text'] ?? ''),
        ] : [
            'subject_id' => 0,
            'grade' => 0,
            'sector' => 'az',
            'raw_text' => '',
        ];

        View::render('admin/import_questions', [
            'title' => __('add_question'),
            'subjects' => $subjects,
            'grades' => grades_list(),
            'old' => $old,
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
        $subjStmt = $pdo->prepare('SELECT name_az, name_ru FROM subjects WHERE id = ?');
        $subjStmt->execute([$subjectId]);
        $subj = $subjStmt->fetch() ?: [];
        $subjectName = locale() === 'ru'
            ? (string) ($subj['name_ru'] ?? $subj['name_az'] ?? '')
            : (string) ($subj['name_az'] ?? '');

        $existing = $pdo->prepare(
            'SELECT question_text, option_a, option_b, option_c, option_d, option_e
             FROM questions WHERE subject_id = ? AND grade = ? AND sector = ?'
        );
        $existing->execute([$subjectId, $grade, $sector]);
        $known = [];
        foreach ($existing->fetchAll() as $row) {
            $fp = question_fingerprint(
                (string) $row['question_text'],
                (string) $row['option_a'],
                (string) $row['option_b'],
                (string) $row['option_c'],
                (string) $row['option_d'],
                $row['option_e'] !== null ? (string) $row['option_e'] : null
            );
            $known[$fp] = true;
        }

        $items = [];
        $batchSeen = [];
        $willSave = 0;
        $n = 0;
        foreach ($parsed as $q) {
            $n++;
            $fp = question_fingerprint(
                (string) $q['question_text'],
                (string) $q['option_a'],
                (string) $q['option_b'],
                (string) $q['option_c'],
                (string) $q['option_d'],
                isset($q['option_e']) && $q['option_e'] !== null && $q['option_e'] !== ''
                    ? (string) $q['option_e'] : null
            );
            $status = 'ok';
            $reason = '';
            if (isset($batchSeen[$fp])) {
                $status = 'duplicate_batch';
                $reason = __('import_skip_batch_dup');
            } elseif (isset($known[$fp])) {
                $status = 'duplicate_db';
                $reason = __('import_skip_db_dup');
            } else {
                $batchSeen[$fp] = true;
                $known[$fp] = true;
                $willSave++;
            }

            $items[] = [
                'n' => $n,
                'status' => $status,
                'reason' => $reason,
                'content_format' => 'plain',
                'question_text' => (string) $q['question_text'],
                'option_a' => (string) $q['option_a'],
                'option_b' => (string) $q['option_b'],
                'option_c' => (string) $q['option_c'],
                'option_d' => (string) $q['option_d'],
                'option_e' => $q['option_e'] !== null && $q['option_e'] !== '' ? (string) $q['option_e'] : null,
                'correct_option' => (string) $q['correct_option'],
            ];
        }

        Session::set('import_draft', [
            'subject_id' => $subjectId,
            'grade' => $grade,
            'sector' => $sector,
            'subject_name' => $subjectName,
            'raw_text' => $raw,
            'items' => $items,
            'will_save' => $willSave,
            'token' => bin2hex(random_bytes(16)),
        ]);

        redirect('/admin/suallar/elave/preview');
    }

    public function importPreview(): void
    {
        Auth::requireAdmin();
        $draft = Session::get('import_draft');
        if (!is_array($draft) || empty($draft['items']) || !is_array($draft['items'])) {
            Session::flash('error', __('err_import_preview_expired'));
            redirect('/admin/suallar/elave');
        }

        View::render('admin/import_preview', [
            'title' => __('import_preview_title'),
            'draft' => $draft,
        ], 'layouts/admin');
    }

    public function confirmImport(): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar/elave');
        }

        $draft = Session::get('import_draft');
        if (!is_array($draft) || empty($draft['items']) || !is_array($draft['items'])) {
            Session::flash('error', __('err_import_preview_expired'));
            redirect('/admin/suallar/elave');
        }

        $token = (string) ($_POST['draft_token'] ?? '');
        if ($token === '' || !hash_equals((string) ($draft['token'] ?? ''), $token)) {
            Session::flash('error', __('err_import_preview_expired'));
            redirect('/admin/suallar/elave');
        }

        $subjectId = (int) ($draft['subject_id'] ?? 0);
        $grade = (int) ($draft['grade'] ?? 0);
        $sector = (string) ($draft['sector'] ?? '');
        $items = $draft['items'];

        $pdo = Database::connection();
        $existing = $pdo->prepare(
            'SELECT question_text, option_a, option_b, option_c, option_d, option_e
             FROM questions WHERE subject_id = ? AND grade = ? AND sector = ?'
        );
        $existing->execute([$subjectId, $grade, $sector]);
        $known = [];
        foreach ($existing->fetchAll() as $row) {
            $fp = question_fingerprint(
                (string) $row['question_text'],
                (string) $row['option_a'],
                (string) $row['option_b'],
                (string) $row['option_c'],
                (string) $row['option_d'],
                $row['option_e'] !== null ? (string) $row['option_e'] : null
            );
            $known[$fp] = true;
        }

        $ins = $pdo->prepare(
            'INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $saved = 0;
        $skipped = [];
        $batchSeen = [];
        foreach ($items as $q) {
            if (($q['status'] ?? '') !== 'ok') {
                $skipped[] = [
                    'n' => (int) ($q['n'] ?? 0),
                    'preview' => question_preview_text((string) ($q['question_text'] ?? '')),
                    'reason' => (string) ($q['reason'] ?? ''),
                ];
                continue;
            }
            $fp = question_fingerprint(
                (string) $q['question_text'],
                (string) $q['option_a'],
                (string) $q['option_b'],
                (string) $q['option_c'],
                (string) $q['option_d'],
                isset($q['option_e']) && $q['option_e'] !== null && $q['option_e'] !== ''
                    ? (string) $q['option_e'] : null
            );
            if (isset($known[$fp]) || isset($batchSeen[$fp])) {
                $skipped[] = [
                    'n' => (int) ($q['n'] ?? 0),
                    'preview' => question_preview_text((string) $q['question_text']),
                    'reason' => isset($batchSeen[$fp])
                        ? __('import_skip_batch_dup')
                        : __('import_skip_db_dup'),
                ];
                continue;
            }
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
            $known[$fp] = true;
            $batchSeen[$fp] = true;
            $saved++;
        }

        Session::remove('import_draft');
        Session::flash('import_report', [
            'saved' => $saved,
            'skipped' => $skipped,
            'total' => count($items),
        ]);
        if ($saved > 0) {
            Session::flash('success', __('ok_questions_saved', ['saved' => (string) $saved, 'total' => (string) count($items)]));
        } elseif ($skipped !== []) {
            Session::flash('error', __('err_questions_all_duplicates'));
        } else {
            Session::flash('error', __('err_import_parse'));
        }
        redirect('/admin/suallar/elave');
    }

    public function cancelImportPreview(): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar/elave');
        }
        // Keep draft so form can restore raw_text; just go back
        redirect('/admin/suallar/elave');
    }

    public function deleteQuestion(string $id): void
    {
        Auth::requireQuestionDelete();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar');
        }
        Database::connection()->prepare('DELETE FROM questions WHERE id = ?')->execute([(int) $id]);
        Session::flash('success', __('ok_question_deleted'));
        redirect('/admin/suallar');
    }

    public function deleteQuestionsBulk(): void
    {
        Auth::requireQuestionDelete();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar');
        }

        $raw = $_POST['ids'] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }
        $ids = [];
        foreach ($raw as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $ids[$n] = $n;
            }
        }
        $ids = array_values($ids);

        if ($ids === []) {
            Session::flash('error', __('err_no_questions_selected'));
            redirect('/admin/suallar');
        }

        // Soft cap to avoid accidental huge deletes
        if (count($ids) > 200) {
            $ids = array_slice($ids, 0, 200);
        }

        $pdo = Database::connection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        $deleted = $stmt->rowCount();

        Session::flash('success', __('ok_questions_deleted', ['n' => (string) $deleted]));
        redirect('/admin/suallar');
    }

    public function requestQuestionDelete(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar');
        }

        if (Auth::canDeleteQuestions()) {
            redirect('/admin/suallar');
        }

        $questionId = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT q.*, s.name_az AS subject_name, s.name_ru AS subject_name_ru
             FROM questions q
             LEFT JOIN subjects s ON s.id = q.subject_id
             WHERE q.id = ?'
        );
        $stmt->execute([$questionId]);
        $q = $stmt->fetch();
        if (!$q) {
            Session::flash('error', __('err_question_not_found'));
            redirect('/admin/suallar');
        }

        $managers = $pdo->query(
            "SELECT email, full_name FROM admins
             WHERE is_active = 1 AND role IN ('super_admin','admin')
               AND email IS NOT NULL AND email <> ''"
        )->fetchAll();

        $mail = new \App\Services\MailService();
        $moderatorName = (string) Session::get('admin_name', '');
        $moderatorEmail = (string) Session::get('admin_email', '');
        $reviewUrl = url('/admin/suallar/bax/' . $questionId);
        $sent = 0;
        foreach ($managers as $m) {
            if ($mail->questionDeleteRequest(
                (string) $m['email'],
                (string) $m['full_name'],
                $moderatorName,
                $moderatorEmail,
                $q,
                $reviewUrl
            )) {
                $sent++;
            }
        }

        if ($sent > 0) {
            Session::flash('success', __('ok_question_delete_requested'));
        } else {
            Session::flash('error', __('err_question_delete_notify'));
        }
        redirect('/admin/suallar');
    }

    public function showCreateQuestion(): void
    {
        Auth::requireAdmin();
        $subjects = Database::connection()->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        View::render('admin/question_create', [
            'title' => __('add_question_math'),
            'q' => [],
            'subjects' => $subjects,
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function storeQuestion(): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar/yeni');
        }

        $parsed = $this->parseQuestionForm($_POST);
        if ($parsed === null) {
            Session::flash('error', __('err_question_fields'));
            redirect('/admin/suallar/yeni');
        }

        if ($this->isDuplicateQuestion($parsed)) {
            Session::flash('error', __('err_question_duplicate'));
            redirect('/admin/suallar/yeni');
        }

        try {
            Database::connection()->prepare(
                'INSERT INTO questions (subject_id, grade, sector, question_text, content_format, option_a, option_b, option_c, option_d, option_e, correct_option, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $parsed['subject_id'], $parsed['grade'], $parsed['sector'],
                $parsed['question_text'], $parsed['content_format'],
                $parsed['option_a'], $parsed['option_b'], $parsed['option_c'], $parsed['option_d'],
                $parsed['option_e'], $parsed['correct_option'], Auth::adminId(),
            ]);
        } catch (\Throwable $e) {
            Session::flash('error', __('err_question_save'));
            redirect('/admin/suallar/yeni');
        }

        $id = (int) Database::connection()->lastInsertId();
        Session::flash('success', __('ok_question_created'));
        redirect('/admin/suallar/bax/' . $id);
    }

    public function uploadQuestionMedia(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => __('err_csrf_short')]);
            return;
        }

        $result = $this->storeQuestionImageFile($_FILES['diagram'] ?? null, 3 * 1024 * 1024);
        if (!$result['ok']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['ok' => true, 'url' => $result['url']]);
    }

    public function showCreateImageQuestion(): void
    {
        Auth::requireAdmin();
        $subjects = Database::connection()->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        View::render('admin/question_image_create', [
            'title' => __('add_question_image'),
            'q' => [],
            'subjects' => $subjects,
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function storeImageQuestion(): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar/sekilli');
        }

        $parsed = $this->parseImageQuestionForm($_POST, $_FILES, null);
        if ($parsed === null) {
            Session::flash('error', __('err_image_question_fields'));
            redirect('/admin/suallar/sekilli');
        }

        if ($this->isDuplicateQuestion($parsed)) {
            Session::flash('error', __('err_question_duplicate'));
            redirect('/admin/suallar/sekilli');
        }

        try {
            Database::connection()->prepare(
                'INSERT INTO questions (subject_id, grade, sector, question_text, content_format, option_a, option_b, option_c, option_d, option_e, correct_option, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $parsed['subject_id'], $parsed['grade'], $parsed['sector'],
                $parsed['question_text'], $parsed['content_format'],
                $parsed['option_a'], $parsed['option_b'], $parsed['option_c'], $parsed['option_d'],
                $parsed['option_e'], $parsed['correct_option'], Auth::adminId(),
            ]);
        } catch (\Throwable $e) {
            Session::flash('error', __('err_question_save'));
            redirect('/admin/suallar/sekilli');
        }

        $id = (int) Database::connection()->lastInsertId();
        Session::flash('success', __('ok_question_created'));
        redirect('/admin/suallar/bax/' . $id);
    }

    public function showEditImageQuestion(string $id): void
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

        View::render('admin/question_image_create', [
            'title' => __('edit_image_question') . ' #' . (int) $q['id'],
            'q' => $q,
            'subjects' => $subjects,
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function updateImageQuestion(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/suallar');
        }

        $qid = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
        $stmt->execute([$qid]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Session::flash('error', __('err_question_not_found'));
            redirect('/admin/suallar');
        }

        $parsed = $this->parseImageQuestionForm($_POST, $_FILES, $existing);
        if ($parsed === null) {
            Session::flash('error', __('err_image_question_fields'));
            redirect('/admin/suallar/sekilli/duzelis/' . $qid);
        }

        if ($this->isDuplicateQuestion($parsed, $qid)) {
            Session::flash('error', __('err_question_duplicate'));
            redirect('/admin/suallar/sekilli/duzelis/' . $qid);
        }

        try {
            $pdo->prepare(
                'UPDATE questions SET subject_id=?, grade=?, sector=?, question_text=?, content_format=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=?, correct_option=?, updated_at=NOW()
                 WHERE id=?'
            )->execute([
                $parsed['subject_id'], $parsed['grade'], $parsed['sector'],
                $parsed['question_text'], $parsed['content_format'],
                $parsed['option_a'], $parsed['option_b'], $parsed['option_c'], $parsed['option_d'],
                $parsed['option_e'], $parsed['correct_option'], $qid,
            ]);
        } catch (\Throwable $e) {
            Session::flash('error', __('err_question_save'));
            redirect('/admin/suallar/sekilli/duzelis/' . $qid);
        }

        Session::flash('success', __('ok_question_updated'));
        redirect('/admin/suallar/bax/' . $qid);
    }

    /**
     * @param array<string,mixed>|null $file
     * @return array{ok:bool,url?:string,error?:string}
     */
    private function storeQuestionImageFile(?array $file, int $maxBytes = 3145728): array
    {
        if (!$file || !is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => __('err_upload')];
        }
        if ((int) $file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => __('err_upload_size')];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        $map = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        ];
        if (!isset($map[$mime])) {
            return ['ok' => false, 'error' => __('err_upload_type')];
        }

        $dir = uploads_path('questions');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => __('err_upload')];
        }

        $name = bin2hex(random_bytes(12)) . '.' . $map[$mime];
        $dest = $dir . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'error' => __('err_upload')];
        }

        return ['ok' => true, 'url' => '/uploads/questions/' . $name];
    }

    private function questionImageHtml(string $url): string
    {
        $safe = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<img src="' . $safe . '" alt="diagram" class="q-diagram">';
    }

    /** @param array<string,mixed>|null $file */
    private function hasUploadedFile(?array $file): bool
    {
        return is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @param array<string,mixed>|null $existing
     * @return array<string,mixed>|null
     */
    private function parseImageQuestionForm(array $post, array $files, ?array $existing): ?array
    {
        $subjectId = (int) ($post['subject_id'] ?? 0);
        $grade = (int) ($post['grade'] ?? 0);
        $sector = (string) ($post['sector'] ?? '');
        $correct = strtoupper(trim((string) ($post['correct_option'] ?? '')));

        if (
            $subjectId < 1 || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true)
            || !in_array($correct, ['A', 'B', 'C', 'D', 'E'], true)
        ) {
            return null;
        }

        $resolve = function (
            string $imageField,
            string $textField,
            string $removeField,
            string $existingKey,
            bool $required
        ) use ($post, $files, $existing): ?array {
            $text = trim((string) ($post[$textField] ?? ''));
            $remove = !empty($post[$removeField]);
            $imgHtml = null;

            $file = $files[$imageField] ?? null;
            if ($this->hasUploadedFile(is_array($file) ? $file : null)) {
                $uploaded = $this->storeQuestionImageFile(is_array($file) ? $file : null);
                if (!$uploaded['ok']) {
                    return null;
                }
                $imgHtml = $this->questionImageHtml((string) $uploaded['url']);
            } elseif ($existing !== null && !$remove) {
                $src = question_img_src((string) ($existing[$existingKey] ?? ''));
                if ($src !== '') {
                    $imgHtml = $this->questionImageHtml($src);
                }
            }

            if ($imgHtml === null && $text === '') {
                return $required ? null : ['value' => '', 'has_img' => false];
            }

            if ($imgHtml !== null && $text !== '') {
                $escaped = nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
                return ['value' => $imgHtml . "\n" . $escaped, 'has_img' => true];
            }
            if ($imgHtml !== null) {
                return ['value' => $imgHtml, 'has_img' => true];
            }

            return ['value' => $text, 'has_img' => false];
        };

        $question = $resolve('question_image', 'question_text_manual', 'remove_question_image', 'question_text', true);
        $a = $resolve('option_a_image', 'option_a_manual', 'remove_option_a_image', 'option_a', true);
        $b = $resolve('option_b_image', 'option_b_manual', 'remove_option_b_image', 'option_b', true);
        $c = $resolve('option_c_image', 'option_c_manual', 'remove_option_c_image', 'option_c', true);
        $d = $resolve('option_d_image', 'option_d_manual', 'remove_option_d_image', 'option_d', true);
        $e = $resolve('option_e_image', 'option_e_manual', 'remove_option_e_image', 'option_e', false);

        if ($question === null || $a === null || $b === null || $c === null || $d === null || $e === null) {
            return null;
        }
        if ($correct === 'E' && ($e['value'] ?? '') === '') {
            return null;
        }

        $hasImg = $question['has_img'] || $a['has_img'] || $b['has_img'] || $c['has_img'] || $d['has_img'] || $e['has_img'];

        return [
            'subject_id' => $subjectId,
            'grade' => $grade,
            'sector' => $sector,
            'question_text' => $question['value'],
            'content_format' => $hasImg ? 'html' : 'plain',
            'option_a' => $a['value'],
            'option_b' => $b['value'],
            'option_c' => $c['value'],
            'option_d' => $d['value'],
            'option_e' => ($e['value'] !== '') ? $e['value'] : null,
            'correct_option' => $correct,
        ];
    }

    /**
     * @param array<string,mixed> $parsed
     */
    private function isDuplicateQuestion(array $parsed, ?int $excludeId = null): bool
    {
        $pdo = Database::connection();
        $sql = 'SELECT id, question_text, option_a, option_b, option_c, option_d, option_e
                FROM questions WHERE subject_id = ? AND grade = ? AND sector = ?';
        $params = [$parsed['subject_id'], $parsed['grade'], $parsed['sector']];
        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $target = question_fingerprint(
            (string) $parsed['question_text'],
            (string) $parsed['option_a'],
            (string) $parsed['option_b'],
            (string) $parsed['option_c'],
            (string) $parsed['option_d'],
            $parsed['option_e'] !== null ? (string) $parsed['option_e'] : null
        );
        foreach ($stmt->fetchAll() as $row) {
            $fp = question_fingerprint(
                (string) $row['question_text'],
                (string) $row['option_a'],
                (string) $row['option_b'],
                (string) $row['option_c'],
                (string) $row['option_d'],
                $row['option_e'] !== null ? (string) $row['option_e'] : null
            );
            if (hash_equals($target, $fp)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string,mixed> $post
     * @return array<string,mixed>|null
     */
    private function parseQuestionForm(array $post): ?array
    {
        $subjectId = (int) ($post['subject_id'] ?? 0);
        $grade = (int) ($post['grade'] ?? 0);
        $sector = (string) ($post['sector'] ?? '');
        $text = trim((string) ($post['question_text'] ?? ''));
        $a = trim((string) ($post['option_a'] ?? ''));
        $b = trim((string) ($post['option_b'] ?? ''));
        $c = trim((string) ($post['option_c'] ?? ''));
        $d = trim((string) ($post['option_d'] ?? ''));
        $eOpt = trim((string) ($post['option_e'] ?? ''));
        $correct = strtoupper(trim((string) ($post['correct_option'] ?? '')));

        if (
            $subjectId < 1 || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true)
            || $text === '' || $a === '' || $b === '' || $c === '' || $d === ''
            || !in_array($correct, ['A', 'B', 'C', 'D', 'E'], true)
            || ($correct === 'E' && $eOpt === '')
        ) {
            return null;
        }

        $rich = question_looks_rich($text, $a, $b, $c, $d, $eOpt);
        if ($rich) {
            $text = sanitize_question_html($text);
            $a = sanitize_question_html($a);
            $b = sanitize_question_html($b);
            $c = sanitize_question_html($c);
            $d = sanitize_question_html($d);
            $eOpt = $eOpt !== '' ? sanitize_question_html($eOpt) : '';
        }

        return [
            'subject_id' => $subjectId,
            'grade' => $grade,
            'sector' => $sector,
            'question_text' => $text,
            'content_format' => $rich ? 'html' : 'plain',
            'option_a' => $a,
            'option_b' => $b,
            'option_c' => $c,
            'option_d' => $d,
            'option_e' => $eOpt !== '' ? $eOpt : null,
            'correct_option' => $correct,
        ];
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

        $subjects = $pdo->query('SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

        View::render('admin/question_show', [
            'title' => __('question_detail') . ' #' . (int) $q['id'],
            'q' => $q,
            'subjects' => $subjects,
            'grades' => grades_list(),
        ], 'layouts/admin');
    }

    public function moveQuestion(string $id): void
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

        if ($subjectId < 1 || $grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true)) {
            Session::flash('error', __('err_question_move_fields'));
            redirect('/admin/suallar/bax/' . $qid);
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
        $stmt->execute([$qid]);
        $q = $stmt->fetch();
        if (!$q) {
            Session::flash('error', __('err_question_not_found'));
            redirect('/admin/suallar');
        }

        $subjOk = $pdo->prepare('SELECT id FROM subjects WHERE id = ? AND is_active = 1');
        $subjOk->execute([$subjectId]);
        if (!$subjOk->fetch()) {
            Session::flash('error', __('err_question_move_fields'));
            redirect('/admin/suallar/bax/' . $qid);
        }

        if (
            (int) $q['subject_id'] === $subjectId
            && (int) $q['grade'] === $grade
            && (string) $q['sector'] === $sector
        ) {
            Session::flash('success', __('ok_question_move_unchanged'));
            redirect('/admin/suallar/bax/' . $qid);
        }

        $parsed = [
            'subject_id' => $subjectId,
            'grade' => $grade,
            'sector' => $sector,
            'question_text' => (string) $q['question_text'],
            'option_a' => (string) $q['option_a'],
            'option_b' => (string) $q['option_b'],
            'option_c' => (string) $q['option_c'],
            'option_d' => (string) $q['option_d'],
            'option_e' => $q['option_e'] !== null && $q['option_e'] !== '' ? (string) $q['option_e'] : null,
        ];

        if ($this->isDuplicateQuestion($parsed, $qid)) {
            Session::flash('error', __('err_question_move_duplicate'));
            redirect('/admin/suallar/bax/' . $qid);
        }

        $pdo->prepare(
            'UPDATE questions SET subject_id = ?, grade = ?, sector = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$subjectId, $grade, $sector, $qid]);

        Session::flash('success', __('ok_question_moved'));
        redirect('/admin/suallar/bax/' . $qid);
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
        if (question_is_image($q)) {
            redirect('/admin/suallar/sekilli/duzelis/' . (int) $q['id']);
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
        $parsed = $this->parseQuestionForm($_POST);
        if ($parsed === null) {
            Session::flash('error', __('err_question_fields'));
            redirect('/admin/suallar/duzelis/' . $qid);
        }

        if ($this->isDuplicateQuestion($parsed, $qid)) {
            Session::flash('error', __('err_question_duplicate'));
            redirect('/admin/suallar/duzelis/' . $qid);
        }

        try {
            Database::connection()->prepare(
                'UPDATE questions SET subject_id=?, grade=?, sector=?, question_text=?, content_format=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=?, correct_option=?, updated_at=NOW()
                 WHERE id=?'
            )->execute([
                $parsed['subject_id'], $parsed['grade'], $parsed['sector'],
                $parsed['question_text'], $parsed['content_format'],
                $parsed['option_a'], $parsed['option_b'], $parsed['option_c'], $parsed['option_d'],
                $parsed['option_e'], $parsed['correct_option'], $qid,
            ]);
        } catch (\Throwable $e) {
            Session::flash('error', __('err_question_save'));
            redirect('/admin/suallar/duzelis/' . $qid);
        }

        Session::flash('success', __('ok_question_updated'));
        redirect('/admin/suallar/bax/' . $qid);
    }

    public function exams(): void
    {
        Auth::requireAdmin();
        $exams = Database::connection()->query(
            'SELECT e.*,
                (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) AS question_count,
                (SELECT COUNT(*) FROM exam_sessions es WHERE es.exam_id = e.id AND es.status = "in_progress") AS live_count,
                (SELECT COUNT(*) FROM exam_invites ei WHERE ei.exam_id = e.id AND ei.status = "interested") AS invite_pending,
                (SELECT COUNT(*) FROM exam_invites ei WHERE ei.exam_id = e.id) AS invite_total,
                (SELECT COUNT(*) FROM exam_invites ei WHERE ei.exam_id = e.id AND ei.status = "approved") AS parents_approved,
                (SELECT COUNT(*) FROM exam_sessions es
                    INNER JOIN children c ON c.id = es.child_id
                    WHERE es.exam_id = e.id
                      AND es.status IN ("in_progress", "submitted", "timed_out")
                      AND (
                        NOT EXISTS (SELECT 1 FROM exam_invites ei0 WHERE ei0.exam_id = e.id)
                        OR EXISTS (
                            SELECT 1 FROM exam_invites ei
                            WHERE ei.exam_id = e.id
                              AND ei.parent_id = c.parent_id
                              AND ei.status = "approved"
                        )
                      )
                ) AS children_participated
             FROM exams e ORDER BY e.id DESC'
        )->fetchAll();

        View::render('admin/exams', [
            'title' => __('exams'),
            'exams' => $exams,
        ], 'layouts/admin');
    }

    public function examInvites(string $id): void
    {
        Auth::requireAdmin();
        $examId = (int) $id;
        $pdo = Database::connection();
        $examStmt = $pdo->prepare('SELECT * FROM exams WHERE id = ?');
        $examStmt->execute([$examId]);
        $exam = $examStmt->fetch();
        if (!$exam) {
            Session::flash('error', __('err_exam_not_found'));
            redirect('/admin/imtahanlar');
        }

        $invites = $pdo->prepare(
            'SELECT ei.*, p.email, p.first_name, p.last_name, p.patronymic,
                    (SELECT GROUP_CONCAT(CONCAT(c.first_name, \' \', c.last_name) ORDER BY c.first_name SEPARATOR \', \')
                     FROM children c
                     WHERE c.parent_id = ei.parent_id AND c.is_active = 1
                       AND c.grade = ? AND c.sector = ?) AS child_names
             FROM exam_invites ei
             JOIN parents p ON p.id = ei.parent_id
             WHERE ei.exam_id = ?
             ORDER BY
                FIELD(ei.status, \'interested\', \'invited\', \'approved\', \'rejected\'),
                ei.interested_at DESC,
                ei.id DESC'
        );
        $invites->execute([(int) $exam['grade'], (string) $exam['sector'], $examId]);

        View::render('admin/exam_invites', [
            'title' => __('exam_invites') . ' — ' . $exam['title'],
            'exam' => $exam,
            'invites' => $invites->fetchAll(),
        ], 'layouts/admin');
    }

    public function approveInvite(string $id): void
    {
        $this->decideInvite($id, 'approved');
    }

    public function rejectInvite(string $id): void
    {
        $this->decideInvite($id, 'rejected');
    }

    private function decideInvite(string $id, string $decision): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar');
        }

        $inviteId = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM exam_invites WHERE id = ?');
        $stmt->execute([$inviteId]);
        $invite = $stmt->fetch();
        if (!$invite) {
            Session::flash('error', __('err_invite_not_found'));
            redirect('/admin/imtahanlar');
        }

        $pdo->prepare(
            'UPDATE exam_invites SET status = ?, decided_at = NOW(), decided_by = ? WHERE id = ?'
        )->execute([$decision, Auth::adminId(), $inviteId]);

        Session::flash(
            'success',
            $decision === 'approved' ? __('ok_invite_approved') : __('ok_invite_rejected')
        );
        redirect('/admin/imtahanlar/dewetler/' . (int) $invite['exam_id']);
    }

    public function resendInvite(string $id): void
    {
        Auth::requireAdmin();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/imtahanlar');
        }

        $inviteId = (int) $id;
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT exam_id FROM exam_invites WHERE id = ?');
        $stmt->execute([$inviteId]);
        $invite = $stmt->fetch();
        if (!$invite) {
            Session::flash('error', __('err_invite_not_found'));
            redirect('/admin/imtahanlar');
        }

        $ok = (new ExamInviteService())->resendInvite($inviteId);
        Session::flash($ok ? 'success' : 'error', $ok ? __('ok_invite_resent') : __('err_invite_resend'));
        redirect('/admin/imtahanlar/dewetler/' . (int) $invite['exam_id']);
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

        $inviteStats = ['invited' => 0, 'mailed' => 0];
        try {
            $inviteStats = (new ExamInviteService())->inviteMatchingParents($examId);
        } catch (\Throwable) {
            // Exam is created; invites can be resent from admin panel
        }

        Session::flash('success', __('ok_exam_created_invites', [
            'n' => (string) $picked,
            'invited' => (string) $inviteStats['invited'],
            'mailed' => (string) $inviteStats['mailed'],
        ]));
        redirect('/admin/imtahanlar/dewetler/' . $examId);
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
        Auth::requireAccountManager();
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
        Auth::requireAccountManager();
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
        Auth::requireTeamManager();
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
        Auth::requireTeamManager();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/komanda');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? Auth::ROLE_MODERATOR);
        if (!Auth::canAddRole($role)) {
            Session::flash('error', __('err_forbidden'));
            redirect('/admin/komanda');
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
        Auth::requireTeamManager();
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
        $stmt = $pdo->prepare('SELECT id, role, is_active FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        if (!$row || !Auth::canToggleStaff($row)) {
            Session::flash('error', __('err_forbidden'));
            redirect('/admin/komanda');
        }

        $newActive = (int) $row['is_active'] ? 0 : 1;
        $pdo->prepare('UPDATE admins SET is_active = ? WHERE id = ?')->execute([$newActive, $adminId]);
        Session::flash('success', __('ok_moderator_updated'));
        redirect('/admin/komanda');
    }

    public function resetStaffPassword(string $id): void
    {
        Auth::requireTeamManager();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/komanda');
        }

        $adminId = (int) $id;
        $newPass = trim((string) ($_POST['new_password'] ?? ''));
        if (strlen($newPass) < 8) {
            Session::flash('error', __('err_staff_password_len'));
            redirect('/admin/komanda');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, email, full_name, role FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        if (!$row || !Auth::canChangeStaffPassword($row)) {
            Session::flash('error', __('err_forbidden'));
            redirect('/admin/komanda');
        }

        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($newPass, PASSWORD_DEFAULT), $adminId]);

        Session::flash('success', __('ok_staff_password_reset', [
            'name' => (string) $row['full_name'],
            'email' => (string) $row['email'],
            'password' => $newPass,
        ]));
        redirect('/admin/komanda');
    }

    public function changeStaffRole(string $id): void
    {
        Auth::requireTeamManager();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/komanda');
        }

        $adminId = (int) $id;
        $newRole = (string) ($_POST['role'] ?? '');

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, email, full_name, role FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        if (!$row || !Auth::canAssignStaffRole($row, $newRole)) {
            Session::flash('error', __('err_forbidden'));
            redirect('/admin/komanda');
        }

        if ((string) $row['role'] === $newRole) {
            Session::flash('success', __('ok_staff_role_unchanged'));
            redirect('/admin/komanda');
        }

        $pdo->prepare('UPDATE admins SET role = ? WHERE id = ?')->execute([$newRole, $adminId]);
        Session::flash('success', __('ok_staff_role_updated', [
            'name' => (string) $row['full_name'],
            'role' => Auth::roleLabel($newRole),
        ]));
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
        Auth::requireAccountManager();
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

    public function updateParentName(string $id): void
    {
        Auth::requireAccountManager();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/valideynler');
        }

        $parentId = (int) $id;
        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $patronymic = trim((string) ($_POST['patronymic'] ?? ''));

        if ($first === '' || $last === '' || $patronymic === '') {
            Session::flash('error', __('err_name_fields'));
            redirect('/admin/valideyn/' . $parentId);
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM parents WHERE id = ?');
        $stmt->execute([$parentId]);
        if (!$stmt->fetch()) {
            Session::flash('error', __('err_parent_not_found'));
            redirect('/admin/valideynler');
        }

        $pdo->prepare(
            'UPDATE parents SET first_name = ?, last_name = ?, patronymic = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$first, $last, $patronymic, $parentId]);

        Session::flash('success', __('ok_parent_name_updated'));
        redirect('/admin/valideyn/' . $parentId);
    }

    public function showEditChild(string $id): void
    {
        Auth::requireAccountManager();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT c.*, p.first_name AS parent_first, p.last_name AS parent_last, p.patronymic AS parent_patronymic
             FROM children c JOIN parents p ON p.id = c.parent_id WHERE c.id = ?'
        );
        $stmt->execute([(int) $id]);
        $child = $stmt->fetch();
        if (!$child) {
            Session::flash('error', __('err_child_not_found'));
            redirect('/admin/usaqlar');
        }

        View::render('admin/child_edit', [
            'title' => __('edit_child_name') . ' — ' . person_full_name($child),
            'child' => $child,
        ], 'layouts/admin');
    }

    public function updateChildName(string $id): void
    {
        Auth::requireAccountManager();
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/admin/usaqlar');
        }

        $childId = (int) $id;
        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $patronymic = trim((string) ($_POST['patronymic'] ?? ''));
        $grade = (int) ($_POST['grade'] ?? 0);
        $sector = (string) ($_POST['sector'] ?? '');

        if ($first === '' || $last === '' || $patronymic === '') {
            Session::flash('error', __('err_name_fields'));
            redirect('/admin/usaq/duzelis/' . $childId);
        }
        if ($grade < 1 || $grade > 11 || !in_array($sector, ['az', 'ru'], true)) {
            Session::flash('error', __('err_child_fields'));
            redirect('/admin/usaq/duzelis/' . $childId);
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM children WHERE id = ?');
        $stmt->execute([$childId]);
        $child = $stmt->fetch();
        if (!$child) {
            Session::flash('error', __('err_child_not_found'));
            redirect('/admin/usaqlar');
        }

        $oldFirst = (string) $child['first_name'];
        $plainPassword = null;
        $hint = $child['password_hint'];

        // If first name changes, keep Ad+İl login formula in sync
        if (mb_strtolower($oldFirst, 'UTF-8') !== mb_strtolower($first, 'UTF-8')) {
            $plainPassword = child_password($first, (string) $child['birth_date']);
            $hint = child_password_hash($plainPassword);
        }

        $pdo->prepare(
            'UPDATE children SET first_name = ?, last_name = ?, patronymic = ?, grade = ?, sector = ?, password_hint = ? WHERE id = ?'
        )->execute([$first, $last, $patronymic, $grade, $sector, $hint, $childId]);

        if ($plainPassword !== null) {
            Session::flash('success', __('ok_child_profile_updated_password', [
                'name' => $first,
                'password' => $plainPassword,
            ]));
        } else {
            Session::flash('success', __('ok_child_profile_updated'));
        }

        $back = (string) ($_POST['back'] ?? '');
        if ($back === 'parent') {
            redirect('/admin/valideyn/' . (int) $child['parent_id']);
        }
        redirect('/admin/usaq/duzelis/' . $childId);
    }

    public function deleteParent(string $id): void
    {
        Auth::requireAccountManager();
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
        Auth::requireAccountManager();
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
        Auth::requireAccountManager();
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
