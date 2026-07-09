<?php
$q = $questions[$currentIndex] ?? null;
$total = count($questions);
$csrf = \App\Core\Session::csrfToken();
?>
<div class="exam-shell" data-session="<?= (int)$session['id'] ?>" data-token="<?= e($token) ?>" data-ends="<?= (int)$endsAt ?>">
    <aside class="exam-left">
        <h3>Cavablar</h3>
        <ul class="answered-list" id="answeredList">
            <?php foreach ($questions as $i => $item): ?>
                <?php $sel = $answerMap[(int)$item['id']] ?? null; ?>
                <?php if ($sel): ?>
                    <li data-qi="<?= $i ?>"><?= $i + 1 ?>. <?= e($sel) ?></li>
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

        <?php if ($q): ?>
            <div class="question-box" id="questionBox"
                 data-qid="<?= (int)$q['id'] ?>"
                 data-index="<?= (int)$currentIndex ?>">
                <div class="q-meta">
                    <span>Sual <?= $currentIndex + 1 ?> / <?= $total ?></span>
                    <span class="subject-tag"><?= e($q['subject_name']) ?></span>
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
                    <a class="btn btn-ghost" href="?q=<?= max(0, $currentIndex - 1) ?>" <?= $currentIndex === 0 ? 'style="visibility:hidden"' : '' ?>>← Əvvəlki</a>
                    <a class="btn btn-ghost" href="?q=<?= min($total - 1, $currentIndex + 1) ?>">Keç</a>
                    <?php if ($currentIndex < $total - 1): ?>
                        <a class="btn" href="?q=<?= $currentIndex + 1 ?>">Növbəti →</a>
                    <?php else: ?>
                        <form method="post" action="<?= url('/imtahan/' . $token . '/teslim/' . $session['id']) ?>" onsubmit="return confirm('İmtahanı təslim etmək istəyirsiniz?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger" type="submit">Təslim et</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <aside class="exam-right">
        <div class="timer-side" id="timerSide"><?= (int)ceil($remainingSeconds / 60) ?> dəq</div>
        <h3>Sual xəritəsi</h3>
        <div class="q-map">
            <?php foreach ($questions as $i => $item): ?>
                <?php $answered = isset($answerMap[(int)$item['id']]); ?>
                <a href="?q=<?= $i ?>" class="q-dot <?= $answered ? 'done' : '' ?> <?= $i === $currentIndex ? 'current' : '' ?>"><?= $i + 1 ?></a>
            <?php endforeach; ?>
        </div>
        <form method="post" action="<?= url('/imtahan/' . $token . '/teslim/' . $session['id']) ?>" onsubmit="return confirm('İmtahanı təslim etmək istəyirsiniz?')">
            <?= csrf_field() ?>
            <button class="btn btn-block btn-danger" type="submit">Təslim et</button>
        </form>
    </aside>
</div>

<script>
(function () {
    const shell = document.querySelector('.exam-shell');
    if (!shell) return;
    const token = shell.dataset.token;
    const sessionId = shell.dataset.session;
    const endsAt = parseInt(shell.dataset.ends, 10) * 1000;
    const csrf = <?= json_encode($csrf) ?>;
    const answerUrl = <?= json_encode(url('/imtahan/' . $token . '/cavab/' . $session['id'])) ?>;
    const resultUrl = <?= json_encode(url('/imtahan/' . $token . '/netice/' . $session['id'])) ?>;

    function pad(n) { return String(n).padStart(2, '0'); }
    function tick() {
        const left = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
        const h = Math.floor(left / 3600);
        const m = Math.floor((left % 3600) / 60);
        const s = left % 60;
        const el = document.getElementById('timer');
        if (el) el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
        const side = document.getElementById('timerSide');
        if (side) side.textContent = Math.ceil(left / 60) + ' dəq';
        if (left <= 0) {
            window.location.href = resultUrl;
        }
    }
    tick();
    setInterval(tick, 1000);

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
                await fetch(answerUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            } catch (e) {}

            const idx = parseInt(box.dataset.index, 10);
            const list = document.getElementById('answeredList');
            let li = list.querySelector('[data-qi="' + idx + '"]');
            if (!li) {
                li = document.createElement('li');
                li.dataset.qi = idx;
                list.appendChild(li);
            }
            li.textContent = (idx + 1) + '. ' + opt;

            const dots = document.querySelectorAll('.q-dot');
            if (dots[idx]) dots[idx].classList.add('done');
        });
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.warn('Tab dəyişildi');
        }
    });
})();
</script>
