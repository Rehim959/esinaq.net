<?php
$q = $questions[$currentIndex] ?? null;
$total = count($questions);
$csrf = \App\Core\Session::csrfToken();
$basePath = '/imtahan/' . $token . '/kec/' . $session['id'];
$submitUrl = url('/imtahan/' . $token . '/teslim/' . $session['id']);
$qUrl = static function (int $i) use ($basePath): string {
    return url($basePath . '?q=' . $i);
};

// Group map by subject for labels + unfinished check
$subjectGroups = [];
foreach ($questions as $i => $item) {
    $sid = (int) ($item['subject_id'] ?? 0);
    $sname = locale() === 'ru'
        ? (string) ($item['subject_name_ru'] ?? $item['subject_name'] ?? '')
        : (string) ($item['subject_name'] ?? '');
    if (!isset($subjectGroups[$sid])) {
        $subjectGroups[$sid] = ['name' => $sname, 'indexes' => []];
    }
    $subjectGroups[$sid]['indexes'][] = $i;
}

$unfinishedSubjects = [];
foreach ($subjectGroups as $g) {
    foreach ($g['indexes'] as $qi) {
        $qid = (int) $questions[$qi]['id'];
        if (!isset($answerMap[$qid]) || $answerMap[$qid] === null || $answerMap[$qid] === '') {
            $unfinishedSubjects[$g['name']] = true;
            break;
        }
    }
}
$unfinishedList = array_keys($unfinishedSubjects);
?>
<div class="exam-shell"
     data-session="<?= (int)$session['id'] ?>"
     data-token="<?= e($token) ?>"
     data-ends="<?= (int)$endsAt ?>"
     data-submit-url="<?= e($submitUrl) ?>">
    <aside class="exam-left">
        <h3><?= e(__('answers')) ?></h3>
        <ul class="answered-list" id="answeredList">
            <?php foreach ($questions as $i => $item): ?>
                <?php $sel = $answerMap[(int)$item['id']] ?? null; ?>
                <?php if ($sel): ?>
                    <li data-qi="<?= $i ?>"><a href="<?= e($qUrl($i)) ?>"><?= $i + 1 ?>. <?= e($sel) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </aside>

    <section class="exam-center">
        <div class="exam-topbar">
            <div>
                <strong><?= e($session['title']) ?></strong>
                <span class="muted"> · <?= e($child['first_name']) ?></span>
            </div>
            <div class="timer" id="timer"><?= gmdate('H:i:s', $remainingSeconds) ?></div>
        </div>
        <div class="time-alert" id="timeAlert" hidden>
            <strong><?= e(__('time_warning')) ?></strong>
            <span id="timeAlertText"><?= e(__('time_warning_min', ['n' => '15'])) ?></span>
        </div>

        <?php if ($q): ?>
            <?php $subjName = locale() === 'ru' ? ($q['subject_name_ru'] ?? $q['subject_name']) : $q['subject_name']; ?>
            <div class="question-box" id="questionBox"
                 data-qid="<?= (int)$q['id'] ?>"
                 data-index="<?= (int)$currentIndex ?>"
                 data-subject="<?= e((string)$subjName) ?>">
                <div class="q-meta">
                    <span><?= e(__('question_n_of', ['n' => $currentIndex + 1, 'total' => $total])) ?></span>
                    <span class="subject-tag"><?= e($subjName) ?></span>
                </div>
                <h2 class="q-title"><?= e($q['question_text']) ?></h2>
                <div class="options" id="options">
                    <?php
                    $opts = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
                    if (!empty($q['option_e'])) $opts['E'] = $q['option_e'];
                    $currentSel = $answerMap[(int)$q['id']] ?? null;
                    foreach ($opts as $letter => $text):
                    ?>
                        <button type="button" class="option <?= $currentSel === $letter ? 'selected' : '' ?>" data-opt="<?= $letter ?>">
                            <span class="opt-letter"><?= $letter ?></span>
                            <span><?= e($text) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="exam-nav">
                    <a class="btn btn-ghost<?= $currentIndex === 0 ? ' is-invisible' : '' ?>" href="<?= e($qUrl(max(0, $currentIndex - 1))) ?>">← <?= e(__('previous')) ?></a>
                    <a class="btn btn-ghost" href="<?= e($qUrl(min($total - 1, $currentIndex + 1))) ?>"><?= e(__('skip')) ?></a>
                    <?php if ($currentIndex < $total - 1): ?>
                        <a class="btn" href="<?= e($qUrl($currentIndex + 1)) ?>"><?= e(__('next')) ?> →</a>
                    <?php else: ?>
                        <form method="post" action="<?= e($submitUrl) ?>" class="js-submit-exam">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger" type="submit"><?= e(__('submit_exam')) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <aside class="exam-right">
        <div class="timer-side" id="timerSide"><?= (int)ceil($remainingSeconds / 60) ?> <?= e(__('min_short')) ?></div>
        <h3><?= e(__('question_map')) ?></h3>
        <div class="q-map" id="qMap">
            <?php foreach ($subjectGroups as $sid => $group): ?>
                <div class="q-map-subject" data-subject="<?= e($group['name']) ?>">
                    <span class="q-map-label"><?= e($group['name']) ?></span>
                    <div class="q-map-dots">
                        <?php foreach ($group['indexes'] as $i): ?>
                            <?php $answered = isset($answerMap[(int)$questions[$i]['id']]); ?>
                            <a href="<?= e($qUrl($i)) ?>"
                               class="q-dot <?= $answered ? 'done' : '' ?> <?= $i === $currentIndex ? 'current' : '' ?>"
                               data-qi="<?= $i ?>"
                               data-subject="<?= e($group['name']) ?>"><?= $i + 1 ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="q-map-hint" id="unfinishedHint" <?= $unfinishedList === [] ? 'hidden' : '' ?>>
            <span class="q-map-hint-title"><?= e(__('subjects_left_title')) ?></span>
            <span id="unfinishedNames"><?= e(implode(', ', $unfinishedList)) ?></span>
        </div>
        <form method="post" id="autoSubmitForm" action="<?= e($submitUrl) ?>" class="js-submit-exam">
            <?= csrf_field() ?>
            <button class="btn btn-block btn-danger" type="submit"><?= e(__('submit_exam')) ?></button>
        </form>
    </aside>
</div>

<?php
$examTakeConfig = [
    'csrf' => $csrf,
    'answerUrl' => url('/imtahan/' . $token . '/cavab/' . $session['id']),
    'qUrls' => array_map(static fn ($i) => $qUrl($i), array_keys($questions)),
    'subjectByIndex' => array_map(static function ($item) {
        return locale() === 'ru'
            ? (string) ($item['subject_name_ru'] ?? $item['subject_name'] ?? '')
            : (string) ($item['subject_name'] ?? '');
    }, $questions),
    'minLabel' => __('min_short'),
    'warnTpl' => __('time_warning_min', ['n' => '__N__']),
    'submitConfirm' => __('submit_confirm'),
    'subjectsLeftConfirm' => __('subjects_left_confirm', ['list' => '__LIST__']),
    'answered' => $answerMap,
];
?>
<script type="application/json" id="examTakeConfig" nonce="<?= e(csp_nonce()) ?>"><?= json_encode($examTakeConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></script>
<script src="<?= asset('js/exam-take.js') ?>" nonce="<?= e(csp_nonce()) ?>"></script>
