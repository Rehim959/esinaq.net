<div class="page exam-invite-page">
    <div class="form-card" style="max-width:560px;margin:40px auto">
        <?php if (!empty($ok)): ?>
            <h1><?= e($title ?? __('invite_interest_saved_title')) ?></h1>
            <p><?= e($message ?? __('invite_interest_saved')) ?></p>
            <?php if (!empty($exam['title'])): ?>
                <div class="hint-box" style="margin-top:16px">
                    <strong><?= e((string) $exam['title']) ?></strong>
                    <?php if (!empty($exam['grade'])): ?>
                        <p class="muted" style="margin:6px 0 0">
                            <?= e(grade_label((int) $exam['grade'])) ?> ·
                            <?= e(sector_label((string) ($exam['sector'] ?? ''))) ?>
                            <?php if (!empty($exam['starts_at'])): ?>
                                · <?= e(format_date((string) $exam['starts_at'])) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h1><?= e($title ?? __('invite_invalid_title')) ?></h1>
            <p><?= e($message ?? __('invite_invalid')) ?></p>
        <?php endif; ?>
        <p style="margin-top:24px">
            <a class="btn" href="<?= url('/') ?>"><?= e(brand_name()) ?></a>
        </p>
    </div>
</div>
