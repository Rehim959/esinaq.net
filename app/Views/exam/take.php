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
                    <a class="btn btn-ghost" href="<?= e($qUrl(max(0, $currentIndex - 1))) ?>" <?= $currentIndex === 0 ? 'style="visibility:hidden"' : '' ?>>← <?= e(__('previous')) ?></a>
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

<script>
(function () {
    const shell = document.querySelector('.exam-shell');
    if (!shell) return;
    const endsAt = parseInt(shell.dataset.ends, 10) * 1000;
    const csrf = <?= json_encode($csrf) ?>;
    const answerUrl = <?= json_encode(url('/imtahan/' . $token . '/cavab/' . $session['id'])) ?>;
    const qUrls = <?= json_encode(array_map(static fn ($i) => $qUrl($i), array_keys($questions)), JSON_UNESCAPED_UNICODE) ?>;
    const subjectByIndex = <?= json_encode(array_map(static function ($item) {
        return locale() === 'ru'
            ? (string) ($item['subject_name_ru'] ?? $item['subject_name'] ?? '')
            : (string) ($item['subject_name'] ?? '');
    }, $questions), JSON_UNESCAPED_UNICODE) ?>;
    const minLabel = <?= json_encode(__('min_short'), JSON_UNESCAPED_UNICODE) ?>;
    const warnTpl = <?= json_encode(__('time_warning_min', ['n' => '__N__']), JSON_UNESCAPED_UNICODE) ?>;
    const submitConfirm = <?= json_encode(__('submit_confirm'), JSON_UNESCAPED_UNICODE) ?>;
    const subjectsLeftConfirm = <?= json_encode(__('subjects_left_confirm', ['list' => '__LIST__']), JSON_UNESCAPED_UNICODE) ?>;
    let submitted = false;
    const answered = {};
    <?php foreach ($answerMap as $qid => $sel): ?>
    answered[<?= (int)$qid ?>] = <?= json_encode((string)$sel) ?>;
    <?php endforeach; ?>

    function pad(n) { return String(n).padStart(2, '0'); }

    function unfinishedSubjects() {
        const left = {};
        Object.keys(subjectByIndex).forEach(function (i) {
            const idx = parseInt(i, 10);
            const dot = document.querySelector('.q-dot[data-qi="' + idx + '"]');
            if (!dot || !dot.classList.contains('done')) {
                left[subjectByIndex[idx]] = true;
            }
        });
        return Object.keys(left).filter(Boolean);
    }

    function refreshUnfinishedHint() {
        const list = unfinishedSubjects();
        const hint = document.getElementById('unfinishedHint');
        const names = document.getElementById('unfinishedNames');
        if (!hint || !names) return;
        if (list.length === 0) {
            hint.hidden = true;
            names.textContent = '';
        } else {
            hint.hidden = false;
            names.textContent = list.join(', ');
        }
    }

    function confirmSubmit() {
        const left = unfinishedSubjects();
        if (left.length > 0) {
            const msg = subjectsLeftConfirm.replace('__LIST__', left.join(', '));
            return window.confirm(msg);
        }
        return window.confirm(submitConfirm);
    }

    function autoSubmit() {
        if (submitted) return;
        submitted = true;
        const form = document.getElementById('autoSubmitForm');
        if (form) form.submit();
    }

    function tick() {
        const left = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
        const h = Math.floor(left / 3600);
        const m = Math.floor((left % 3600) / 60);
        const s = left % 60;
        const el = document.getElementById('timer');
        const side = document.getElementById('timerSide');
        const alertBox = document.getElementById('timeAlert');
        const alertText = document.getElementById('timeAlertText');

        if (el) {
            el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
            el.classList.toggle('timer-danger', left > 0 && left <= 900);
        }
        if (side) {
            side.textContent = Math.ceil(left / 60) + ' ' + minLabel;
            side.classList.toggle('timer-danger', left > 0 && left <= 900);
        }
        if (alertBox) {
            if (left > 0 && left <= 900) {
                alertBox.hidden = false;
                if (alertText) {
                    alertText.textContent = warnTpl.replace('__N__', String(Math.max(1, Math.ceil(left / 60))));
                }
            } else {
                alertBox.hidden = true;
            }
        }
        if (left <= 0) {
            autoSubmit();
        }
    }
    tick();
    setInterval(tick, 1000);

    document.querySelectorAll('.js-submit-exam').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (submitted) return;
            if (!confirmSubmit()) {
                e.preventDefault();
                return;
            }
            submitted = true;
        });
    });

    document.querySelectorAll('.option').forEach(btn => {
        btn.addEventListener('click', async () => {
            const box = document.getElementById('questionBox');
            const qid = box.dataset.qid;
            const opt = btn.dataset.opt;
            document.querySelectorAll('.option').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');

            const fd = new FormData();
            fd.append('_csrf', csrf);
            fd.append('question_id', qid);
            fd.append('option', opt);
            try {
                const res = await fetch(answerUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json().catch(() => ({}));
                if (data && data.error === 'timeout' && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
            } catch (e) {}

            const idx = parseInt(box.dataset.index, 10);
            answered[qid] = opt;
            const list = document.getElementById('answeredList');
            let li = list.querySelector('[data-qi="' + idx + '"]');
            if (!li) {
                li = document.createElement('li');
                li.dataset.qi = idx;
                list.appendChild(li);
            }
            const href = qUrls[idx] || ('?q=' + idx);
            li.innerHTML = '<a href="' + href + '">' + (idx + 1) + '. ' + opt + '</a>';

            const dot = document.querySelector('.q-dot[data-qi="' + idx + '"]');
            if (dot) dot.classList.add('done');
            refreshUnfinishedHint();
        });
    });
})();
</script>
