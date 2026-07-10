<?php
/** @var array<string,mixed> $q */
/** @var list<array<string,mixed>> $subjects */
/** @var list<int> $grades */
$isEdit = !empty($q['id']);
$actionPath = $isEdit
    ? '/admin/suallar/sekilli/duzelis/' . (int) $q['id']
    : '/admin/suallar/sekilli';

$slots = [
    [
        'image' => 'question_image',
        'text' => 'question_text_manual',
        'remove' => 'remove_question_image',
        'label' => __('question'),
        'src' => question_img_src($q['question_text'] ?? null),
        'textValue' => question_text_without_img($q['question_text'] ?? null),
        'multiline' => true,
        'requiredHint' => true,
    ],
    [
        'image' => 'option_a_image',
        'text' => 'option_a_manual',
        'remove' => 'remove_option_a_image',
        'label' => 'A',
        'src' => question_img_src($q['option_a'] ?? null),
        'textValue' => question_text_without_img($q['option_a'] ?? null),
        'multiline' => false,
        'requiredHint' => true,
    ],
    [
        'image' => 'option_b_image',
        'text' => 'option_b_manual',
        'remove' => 'remove_option_b_image',
        'label' => 'B',
        'src' => question_img_src($q['option_b'] ?? null),
        'textValue' => question_text_without_img($q['option_b'] ?? null),
        'multiline' => false,
        'requiredHint' => true,
    ],
    [
        'image' => 'option_c_image',
        'text' => 'option_c_manual',
        'remove' => 'remove_option_c_image',
        'label' => 'C',
        'src' => question_img_src($q['option_c'] ?? null),
        'textValue' => question_text_without_img($q['option_c'] ?? null),
        'multiline' => false,
        'requiredHint' => true,
    ],
    [
        'image' => 'option_d_image',
        'text' => 'option_d_manual',
        'remove' => 'remove_option_d_image',
        'label' => 'D',
        'src' => question_img_src($q['option_d'] ?? null),
        'textValue' => question_text_without_img($q['option_d'] ?? null),
        'multiline' => false,
        'requiredHint' => true,
    ],
    [
        'image' => 'option_e_image',
        'text' => 'option_e_manual',
        'remove' => 'remove_option_e_image',
        'label' => 'E (' . __('optional') . ')',
        'src' => question_img_src($q['option_e'] ?? null),
        'textValue' => question_text_without_img($q['option_e'] ?? null),
        'multiline' => false,
        'requiredHint' => false,
    ],
];
?>
<div class="page">
    <div class="page-actions">
        <a href="<?= url('/admin/suallar?kind=image') ?>" class="btn btn-ghost btn-sm"><?= e(__('image_questions')) ?></a>
        <a href="<?= url('/admin/suallar/yeni') ?>" class="btn btn-ghost btn-sm"><?= e(__('add_question_math')) ?></a>
    </div>

    <form method="post" action="<?= e(form_get_action()) ?>" enctype="multipart/form-data" class="form-card image-question-form" id="imageQuestionForm">
        <?= route_hidden($actionPath) ?>
        <?= csrf_field() ?>

        <div class="hint-box">
            <strong><?= e(__('image_question_help_title')) ?></strong>
            <p><?= e(__('image_question_help')) ?></p>
            <p><?= e(__('image_question_mixed_help')) ?></p>
        </div>

        <div class="grid-3">
            <label><?= e(__('subject')) ?>
                <select name="subject_id" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (int)($q['subject_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e(subject_name($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('grade')) ?>
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= (int)($q['grade'] ?? 0) === $g ? 'selected' : '' ?>><?= e(grade_label($g)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e(__('sector')) ?>
                <select name="sector" required>
                    <option value="az" <?= ($q['sector'] ?? 'az') === 'az' ? 'selected' : '' ?>><?= e(__('sector_az_short')) ?></option>
                    <option value="ru" <?= ($q['sector'] ?? '') === 'ru' ? 'selected' : '' ?>><?= e(__('sector_ru_short')) ?></option>
                </select>
            </label>
        </div>

        <div class="image-mixed-list">
            <?php foreach ($slots as $slot): ?>
                <div class="image-mixed-card">
                    <div class="image-mixed-head">
                        <strong><?= e($slot['label']) ?></strong>
                        <?php if ($slot['requiredHint']): ?>
                            <span class="muted tiny"><?= e(__('image_or_text_required')) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="image-mixed-body">
                        <label class="image-upload-card image-upload-card-compact">
                            <span class="image-upload-label"><?= e(__('upload_image_optional')) ?></span>
                            <?php if ($slot['src'] !== ''): ?>
                                <img class="image-upload-preview" src="<?= e($slot['src']) ?>" alt="">
                            <?php else: ?>
                                <img class="image-upload-preview is-empty" alt="" hidden>
                            <?php endif; ?>
                            <input type="file" name="<?= e($slot['image']) ?>" accept="image/png,image/jpeg,image/webp" class="image-upload-input">
                            <?php if ($isEdit && $slot['src'] !== ''): ?>
                                <label class="tiny remove-image-check">
                                    <input type="checkbox" name="<?= e($slot['remove']) ?>" value="1">
                                    <?= e(__('remove_image')) ?>
                                </label>
                            <?php endif; ?>
                        </label>
                        <label class="image-text-field">
                            <span><?= e(__('manual_text_optional')) ?></span>
                            <?php if ($slot['multiline']): ?>
                                <textarea name="<?= e($slot['text']) ?>" rows="4" placeholder="<?= e(__('manual_text_placeholder')) ?>"><?= e($slot['textValue']) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?= e($slot['text']) ?>" value="<?= e($slot['textValue']) ?>" placeholder="<?= e(__('manual_text_placeholder_short')) ?>">
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <label><?= e(__('answer_col')) ?>
            <select name="correct_option" required id="imageCorrectOption">
                <?php foreach (['A', 'B', 'C', 'D', 'E'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($q['correct_option'] ?? 'A') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <section class="image-student-preview" id="imageStudentPreview"
                 data-preview-label="<?= e(__('math_student_preview')) ?>"
                 data-correct-label="<?= e(__('correct_label')) ?>">
            <h3><?= e(__('math_student_preview')) ?></h3>
            <p class="muted tiny preview-hint"><?= e(__('student_preview_hint')) ?></p>
            <div class="student-preview-host" id="imagePreviewHost"></div>
        </section>

        <button class="btn" type="submit"><?= e($isEdit ? __('save_changes') : __('save_question')) ?></button>
    </form>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var form = document.getElementById('imageQuestionForm');
  var host = document.getElementById('imagePreviewHost');
  var panel = document.getElementById('imageStudentPreview');
  if (!form || !host) return;

  var blobUrls = {};

  function revoke(key) {
    if (blobUrls[key]) {
      try { URL.revokeObjectURL(blobUrls[key]); } catch (e) {}
      delete blobUrls[key];
    }
  }

  function slotImageSrc(imageName, removeName) {
    var input = form.querySelector('[name="' + imageName + '"]');
    if (input && input.files && input.files[0]) {
      revoke(imageName);
      blobUrls[imageName] = URL.createObjectURL(input.files[0]);
      return blobUrls[imageName];
    }
    var remove = form.querySelector('[name="' + removeName + '"]');
    if (remove && remove.checked) return '';
    var card = input && input.closest('.image-upload-card');
    var img = card && card.querySelector('.image-upload-preview');
    if (img && img.src && !img.classList.contains('is-empty') && !img.hidden) {
      return img.getAttribute('src') || '';
    }
    return '';
  }

  function slotHtml(imageName, textName, removeName) {
    var src = slotImageSrc(imageName, removeName);
    var textEl = form.querySelector('[name="' + textName + '"]');
    var text = textEl ? String(textEl.value || '').trim() : '';
    var parts = [];
    if (src) parts.push('<img src="' + src.replace(/"/g, '') + '" alt="" class="q-diagram">');
    if (text) parts.push(text);
    return parts.join('\n');
  }

  function subjectLabel() {
    var sel = form.querySelector('[name="subject_id"]');
    if (sel && sel.selectedOptions && sel.selectedOptions[0]) {
      return sel.selectedOptions[0].textContent || '';
    }
    return '';
  }

  function refresh() {
    var cfg = {
      question: slotHtml('question_image', 'question_text_manual', 'remove_question_image'),
      options: {
        A: slotHtml('option_a_image', 'option_a_manual', 'remove_option_a_image'),
        B: slotHtml('option_b_image', 'option_b_manual', 'remove_option_b_image'),
        C: slotHtml('option_c_image', 'option_c_manual', 'remove_option_c_image'),
        D: slotHtml('option_d_image', 'option_d_manual', 'remove_option_d_image'),
        E: slotHtml('option_e_image', 'option_e_manual', 'remove_option_e_image')
      },
      correct: (form.querySelector('[name="correct_option"]') || {}).value || 'A',
      subject: subjectLabel(),
      showCorrect: true,
      previewLabel: panel ? panel.getAttribute('data-preview-label') : '',
      correctLabel: panel ? panel.getAttribute('data-correct-label') : 'Düzgün'
    };
    if (typeof window.esinaqRenderStudentPreview === 'function') {
      window.esinaqRenderStudentPreview(host, cfg);
    }
  }

  form.querySelectorAll('.image-upload-input').forEach(function (input) {
    input.addEventListener('change', function () {
      var card = input.closest('.image-upload-card');
      var img = card && card.querySelector('.image-upload-preview');
      if (!img || !input.files || !input.files[0]) return;
      var url = URL.createObjectURL(input.files[0]);
      img.src = url;
      img.hidden = false;
      img.classList.remove('is-empty');
      refresh();
    });
  });

  form.querySelectorAll('textarea, input[type="text"], select, input[type="checkbox"]').forEach(function (el) {
    el.addEventListener('input', refresh);
    el.addEventListener('change', refresh);
  });

  refresh();
})();
</script>
