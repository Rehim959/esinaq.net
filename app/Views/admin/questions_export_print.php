<?php
/** @var list<array<string,mixed>> $rows */
/** @var array{grade:int,sector:string,subjectId:int} $filters */
/** @var int $total */
?>
<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? __('export_pdf')) ?></title>
    <style>
        :root { color-scheme: light; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.45;
            color: #111;
            max-width: 820px;
            margin: 0 auto;
            padding: 24px 20px 48px;
        }
        h1 { font-size: 18pt; margin: 0 0 8px; }
        .meta { color: #555; margin-bottom: 20px; }
        .toolbar {
            display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;
            padding: 12px; background: #f1f5f9; border-radius: 8px;
        }
        .toolbar button, .toolbar a {
            font: inherit; padding: 8px 14px; border-radius: 6px; cursor: pointer;
            border: 1px solid #cbd5e1; background: #fff; text-decoration: none; color: #0f172a;
        }
        .toolbar button.primary { background: #1e3a5f; color: #fff; border-color: #1e3a5f; }
        .q { margin: 0 0 22px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0; page-break-inside: avoid; }
        .q-head { color: #64748b; font-size: 10pt; margin-bottom: 4px; }
        .options { margin: 8px 0 0 1.2em; padding: 0; }
        .options li { margin-bottom: 4px; }
        .ans { font-weight: 700; margin-top: 8px; }
        @media print {
            .toolbar { display: none !important; }
            body { padding: 0; max-width: none; }
            .q { border-bottom: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="primary" onclick="window.print()"><?= e(__('export_print_pdf')) ?></button>
        <a href="<?= e(url('/admin/suallar' . (
            ($filters['grade'] > 0 || $filters['sector'] !== '' || $filters['subjectId'] > 0)
                ? '?' . http_build_query(array_filter([
                    'grade' => $filters['grade'] > 0 ? $filters['grade'] : null,
                    'sector' => $filters['sector'] !== '' ? $filters['sector'] : null,
                    'subject_id' => $filters['subjectId'] > 0 ? $filters['subjectId'] : null,
                ]))
                : ''
        ))) ?>"><?= e(__('back')) ?></a>
    </div>

    <h1><?= e(__('question_bank')) ?> — <?= e(brand_name()) ?></h1>
    <p class="meta"><?= e(__('stats_total', ['n' => (string) $total])) ?></p>

    <?php foreach ($rows as $r): ?>
        <div class="q">
            <div class="q-head">
                #<?= (int) $r['n'] ?> · ID <?= (int) $r['id'] ?> ·
                <?= e((string) $r['subject']) ?> · <?= (int) $r['grade'] ?> · <?= e((string) $r['sector']) ?>
            </div>
            <div><strong><?= (int) $r['n'] ?>.</strong> <?= e((string) $r['question']) ?></div>
            <ol class="options" type="A">
                <li><?= e((string) $r['option_a']) ?></li>
                <li><?= e((string) $r['option_b']) ?></li>
                <li><?= e((string) $r['option_c']) ?></li>
                <li><?= e((string) $r['option_d']) ?></li>
                <?php if ((string) $r['option_e'] !== ''): ?>
                    <li><?= e((string) $r['option_e']) ?></li>
                <?php endif; ?>
            </ol>
            <div class="ans"><?= e(__('answer_col')) ?>: <?= e((string) $r['correct']) ?></div>
        </div>
    <?php endforeach; ?>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
</body>
</html>
