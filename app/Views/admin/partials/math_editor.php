<?php
/** Shared math + geometry editor fields. Expects $q (optional array). */
$q = $q ?? [];
$fmt = (string) ($q['content_format'] ?? 'plain');

$decodeField = static function (string $val) use ($fmt): string {
    if ($fmt !== 'html') {
        return $val;
    }
    $val = str_replace(['<br>', '<br/>', '<br />'], "\n", $val);
    $val = html_entity_decode(strip_tags($val, '<img>'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $val;
};

$qt = $decodeField((string) ($q['question_text'] ?? ''));
$oa = $decodeField((string) ($q['option_a'] ?? ''));
$ob = $decodeField((string) ($q['option_b'] ?? ''));
$oc = $decodeField((string) ($q['option_c'] ?? ''));
$od = $decodeField((string) ($q['option_d'] ?? ''));
$oe = $decodeField((string) ($q['option_e'] ?? ''));
?>
<div class="math-editor"
     data-upload-url="<?= e(url('/admin/suallar/media')) ?>"
     data-csrf="<?= e(\App\Core\Session::csrfToken()) ?>"
     data-label-prompt="<?= e(__('math_label_prompt')) ?>"
     data-preview-label="<?= e(__('math_student_preview')) ?>"
     data-correct-label="<?= e(__('correct_label')) ?>"
     data-active-field="question_text">

    <p class="math-hint"><?= e(__('math_editor_hint')) ?></p>

    <div class="math-toolbar" role="toolbar" aria-label="<?= e(__('math_toolbar')) ?>">
        <button type="button" class="math-tool" data-wrap="\(\frac{||}{}\)" title="Kəsr">a/b</button>
        <button type="button" class="math-tool" data-wrap="\(\sqrt{||}\)" title="Kök">√</button>
        <button type="button" class="math-tool" data-wrap="\(^{||}\)" title="Qüvvət">xⁿ</button>
        <button type="button" class="math-tool" data-snip="\(^{2}\)" title="Kvadrat">x²</button>
        <button type="button" class="math-tool" data-snip="\(^{3}\)" title="Kub">x³</button>
        <button type="button" class="math-tool" data-wrap="\(||\)" title="Mötərizə">( )</button>
        <button type="button" class="math-tool" data-snip="\(\mathrm{sm}\)" title="sm">sm</button>
        <button type="button" class="math-tool" data-snip="\(\mathrm{sm}^{2}\)" title="sm²">sm²</button>
        <button type="button" class="math-tool" data-snip="\(\mathrm{m}^{2}\)" title="m²">m²</button>
        <button type="button" class="math-tool" data-snip="\(\mathrm{sm}^{3}\)" title="sm³">sm³</button>
        <button type="button" class="math-tool" data-snip="°" title="Dərəcə">°</button>
        <button type="button" class="math-tool" data-snip="∠" title="Bucaq">∠</button>
        <button type="button" class="math-tool" data-snip="△" title="Üçbucaq">△</button>
        <button type="button" class="math-tool" data-snip="□" title="Kvadrat">□</button>
        <button type="button" class="math-tool" data-snip="π" title="Pi">π</button>
    </div>

    <div class="math-editor-grid">
        <div>
            <label class="math-field-label"><?= e(__('question')) ?>
                <textarea name="question_text" rows="5" required><?= e($qt) ?></textarea>
            </label>

            <?php
            $opts = ['a' => $oa, 'b' => $ob, 'c' => $oc, 'd' => $od, 'e' => $oe];
            foreach ($opts as $letter => $val):
                $req = $letter !== 'e' ? ' required' : '';
            ?>
                <label class="option-row-math">
                    <span><?= strtoupper($letter) ?></span>
                    <input type="text" name="option_<?= $letter ?>" value="<?= e($val) ?>"<?= $req ?>
                           placeholder="<?= $letter === 'e' ? e(__('optional')) : '' ?>">
                </label>
            <?php endforeach; ?>

            <label class="math-field-label"><?= e(__('answer_col')) ?>
                <select name="correct_option" required>
                    <?php foreach (['A','B','C','D','E'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($q['correct_option'] ?? 'A') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div>
            <div class="math-preview">
                <h3><?= e(__('math_student_preview')) ?></h3>
                <p class="muted tiny preview-hint"><?= e(__('student_preview_hint')) ?></p>
                <div class="math-preview-body student-preview-host"></div>
            </div>
        </div>
    </div>

    <div class="geo-panel">
        <h3 style="margin:0 0 8px;font-family:var(--display);font-size:1.05rem;"><?= e(__('math_geometry')) ?></h3>
        <p class="math-hint"><?= e(__('math_geometry_hint')) ?></p>
        <div class="geo-tools">
            <button type="button" class="geo-tool is-active" data-tool="pen"><?= e(__('geo_pen')) ?></button>
            <button type="button" class="geo-tool" data-tool="select"><?= e(__('geo_select')) ?></button>
            <button type="button" class="geo-tool" data-tool="eraser"><?= e(__('geo_eraser')) ?></button>
            <button type="button" class="geo-tool" data-tool="line"><?= e(__('geo_line')) ?></button>
            <button type="button" class="geo-tool" data-tool="angle"><?= e(__('geo_angle')) ?></button>
            <button type="button" class="geo-tool" data-tool="triangle"><?= e(__('geo_triangle')) ?></button>
            <button type="button" class="geo-tool" data-tool="trapezoid"><?= e(__('geo_trapezoid')) ?></button>
            <button type="button" class="geo-tool" data-tool="square"><?= e(__('geo_square')) ?></button>
            <button type="button" class="geo-tool" data-tool="rect"><?= e(__('geo_rect')) ?></button>
            <button type="button" class="geo-tool" data-tool="rhombus"><?= e(__('geo_rhombus')) ?></button>
            <button type="button" class="geo-tool" data-tool="parallelogram"><?= e(__('geo_parallelogram')) ?></button>
            <button type="button" class="geo-tool" data-tool="circle"><?= e(__('geo_circle')) ?></button>
            <button type="button" class="geo-tool" data-tool="cone"><?= e(__('geo_cone')) ?></button>
            <button type="button" class="geo-tool" data-tool="box"><?= e(__('geo_box')) ?></button>
            <button type="button" class="geo-tool" data-tool="label"><?= e(__('geo_label')) ?></button>
        </div>
        <div class="geo-canvas-wrap">
            <canvas class="geo-canvas" width="720" height="420" aria-label="<?= e(__('math_geometry')) ?>"></canvas>
        </div>
        <div class="geo-actions">
            <button type="button" class="geo-font-down" title="<?= e(__('geo_font_down')) ?>">A−</button>
            <button type="button" class="geo-font-up" title="<?= e(__('geo_font_up')) ?>">A+</button>
            <button type="button" class="geo-delete"><?= e(__('geo_delete')) ?></button>
            <span class="geo-eraser-size" title="<?= e(__('geo_eraser_size')) ?>">
                <button type="button" class="geo-eraser-down" title="<?= e(__('geo_eraser_down')) ?>">⌫−</button>
                <label class="geo-eraser-label">
                    <?= e(__('geo_eraser')) ?>
                    <input type="range" class="geo-eraser-range" min="4" max="48" value="14" step="1">
                    <strong class="geo-eraser-size-val">14</strong>
                </label>
                <button type="button" class="geo-eraser-up" title="<?= e(__('geo_eraser_up')) ?>">⌫+</button>
            </span>
            <button type="button" class="geo-undo"><?= e(__('geo_undo')) ?></button>
            <button type="button" class="geo-clear"><?= e(__('geo_clear')) ?></button>
            <button type="button" class="geo-insert"><?= e(__('geo_insert')) ?></button>
        </div>
    </div>
</div>
