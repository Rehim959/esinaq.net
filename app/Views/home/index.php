<section class="hero">
    <div class="hero-bg" aria-hidden="true">
        <div class="hero-wash"></div>
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-grain"></div>
    </div>
    <div class="container hero-content">
        <p class="brand-mark"><span class="brand-e">e</span>Sınaq</p>
        <h1 class="hero-headline"><?= e(__('home_headline')) ?></h1>
        <p class="hero-lead"><?= e(__('home_lead')) ?></p>
        <div class="hero-actions">
            <a class="btn btn-lg" href="<?= url('/qeydiyyat') ?>">
                <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0 2.25c-3.6 0-6.75 1.8-6.75 4v1.5h13.5v-1.5c0-2.2-3.15-4-6.75-4Z"/></svg>
                <?= e(__('parent_register')) ?>
            </a>
            <a class="btn btn-lg btn-ghost" href="<?= url('/valideyn/giris') ?>"><?= e(__('sign_in_btn')) ?></a>
        </div>
        <a class="hero-scroll" href="#niye-biz">
            <svg class="ico ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v12.2l4.1-4.1 1.4 1.4L12 20l-5.5-6.5 1.4-1.4L11 16.2V4h1Z"/></svg>
            <?= e(__('why_us')) ?>
        </a>
    </div>
</section>

<section class="section why-section" id="niye-biz">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow"><?= e(__('why_eyebrow')) ?></p>
            <h2><?= e(__('why_title')) ?></h2>
            <p class="section-lead"><?= e(__('why_lead')) ?></p>
        </div>
        <div class="why-grid">
            <article class="why-card">
                <div class="icon-badge" aria-hidden="true">
                    <svg class="ico" viewBox="0 0 24 24"><path d="M4 19.5V6.8A2.8 2.8 0 0 1 6.8 4H19v14.2a1.8 1.8 0 0 1-1.8 1.8H6.2A2.2 2.2 0 0 1 4 17.8V19.5Zm2.5-1.7h10.7V5.8H6.8a1 1 0 0 0-1 1v11.2c0 .2.1.4.3.5.1 0 .2.1.4.1Zm3.2-9.3h6v1.5h-6V8.5Zm0 3.2h6v1.5h-6v-1.5Zm0 3.2h4.2v1.5H9.7v-1.5Z"/></svg>
                </div>
                <h3><?= e(__('why_parent_title')) ?></h3>
                <p><?= e(__('why_parent_text')) ?></p>
                <ul class="why-list">
                    <li><?= e(__('why_parent_1')) ?></li>
                    <li><?= e(__('why_parent_2')) ?></li>
                    <li><?= e(__('why_parent_3')) ?></li>
                    <li><?= e(__('why_parent_4')) ?></li>
                </ul>
            </article>
            <article class="why-card">
                <div class="icon-badge icon-badge-alt" aria-hidden="true">
                    <svg class="ico" viewBox="0 0 24 24"><path d="M12 3.2 3.5 7.2v1.6L12 4.9l8.5 3.9V7.2L12 3.2Zm0 4.3L5.2 10.6 12 13.8l6.8-3.2L12 7.5Zm-6.5 5.4v2.1l6.5 3.1 6.5-3.1v-2.1L12 15.9 5.5 12.9Zm0 4.2v2.1L12 22.3l6.5-3.1v-2.1L12 19.9 5.5 17.1Z"/></svg>
                </div>
                <h3><?= e(__('why_child_title')) ?></h3>
                <p><?= e(__('why_child_text')) ?></p>
                <ul class="why-list">
                    <li><?= e(__('why_child_1')) ?></li>
                    <li><?= e(__('why_child_2')) ?></li>
                    <li><?= e(__('why_child_3')) ?></li>
                    <li><?= e(__('why_child_4')) ?></li>
                </ul>
            </article>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2><?= e(__('how_it_works')) ?></h2>
            <p class="section-lead"><?= e(__('how_it_works_lead')) ?></p>
        </div>
        <div class="steps-modern">
            <div class="step-card">
                <div class="step-icon" aria-hidden="true">
                    <svg class="ico" viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 1.8c-3.3 0-6 1.6-6 3.6V19h12v-1.6c0-2-2.7-3.6-6-3.6Z"/></svg>
                </div>
                <span class="step-num">I</span>
                <h3><?= e(__('step1_title')) ?></h3>
                <p><?= e(__('step1_text')) ?></p>
            </div>
            <div class="step-card">
                <div class="step-icon" aria-hidden="true">
                    <svg class="ico" viewBox="0 0 24 24"><path d="M7 3.5h10a1.5 1.5 0 0 1 1.5 1.5v14a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 19V5A1.5 1.5 0 0 1 7 3.5Zm1.5 3h7v1.5h-7V6.5Zm0 3.5h7V11h-7V10Zm0 3.5h5V15h-5v-1.5Z"/></svg>
                </div>
                <span class="step-num">II</span>
                <h3><?= e(__('step2_title')) ?></h3>
                <p><?= e(__('step2_text')) ?></p>
            </div>
            <div class="step-card">
                <div class="step-icon" aria-hidden="true">
                    <svg class="ico" viewBox="0 0 24 24"><path d="M4.5 18.5v-1.6l3.2-3.2 2.4 2.4 5.2-5.2 1.1 1.1-6.3 6.3-2.4-2.4-1.7 1.7H4.5Zm2.8-9.2L5.2 7.2l1.1-1.1 2.1 2.1 4.4-4.4L14 5l-5.5 5.5-1.2-1.2Z"/></svg>
                </div>
                <span class="step-num">III</span>
                <h3><?= e(__('step3_title')) ?></h3>
                <p><?= e(__('step3_text')) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2><?= e(__('subjects')) ?></h2>
            <p class="section-lead"><?= e(__('subjects_lead')) ?></p>
        </div>
        <div class="subject-grid">
            <?php
            $iconPaths = [
                'azerbaycan_dili' => 'M6 4.5h9.5A2.5 2.5 0 0 1 18 7v12.2a.8.8 0 0 1-1.2.7L12 17.2l-4.8 2.7A.8.8 0 0 1 6 19.2V4.5Zm1.5 1.5v11.2l4.5-2.5 4.5 2.5V7a1 1 0 0 0-1-1H7.5Z',
                'edebiyyat' => 'M5 4.5h6.2c.9 0 1.7.4 2.3 1 .6-.6 1.4-1 2.3-1H22v14.2c0 .9-.7 1.6-1.6 1.6h-5.6c-.7 0-1.3.2-1.8.6-.5-.4-1.1-.6-1.8-.6H5.6A1.6 1.6 0 0 1 4 18.7V4.5h1Zm1.5 1.5v11.8c0 .2.1.4.4.4h5.2c.5 0 1 .1 1.4.4V7.2c0-.7-.6-1.2-1.2-1.2H6.5Zm11 0h-4.8c-.7 0-1.2.5-1.2 1.2v11.4c.4-.3.9-.4 1.4-.4h5.2c.2 0 .4-.2.4-.4V6Z',
                'riyaziyyat' => 'M7 3.5h10A1.5 1.5 0 0 1 18.5 5v14a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 19V5A1.5 1.5 0 0 1 7 3.5Zm1.5 2.5v3h7v-3h-7Zm0 5h2.2v2.2H8.5V11Zm3.4 0h2.2v2.2h-2.2V11Zm3.4 0H17v2.2h-1.7V11ZM8.5 14.8h2.2V17H8.5v-2.2Zm3.4 0h2.2V17h-2.2v-2.2Zm3.4 0H17V17h-1.7v-2.2Z',
                'tarix' => 'M12 3.5a8.5 8.5 0 1 1 0 17 8.5 8.5 0 0 1 0-17Zm0 1.5a7 7 0 1 0 0 14 7 7 0 0 0 0-14Zm-.8 2.2h1.5v5.1l3.4 2 .7-1.3-2.8-1.6V7.2Z',
                'cografiya' => 'M4.5 5.8 9 4.2l6 2.1 4.5-1.5v12.4L15 18.8l-6-2.1-4.5 1.5V5.8Zm1.5 1.7v9.2l3-1v-9.2l-3 1Zm4.5-.7v9.2l4.5 1.6V8.4L10.5 6.8Zm6 1.9v9.2l3-1V7.7l-3 1Z',
                'rus_dili' => 'M12 3.5a8.5 8.5 0 1 1 0 17 8.5 8.5 0 0 1 0-17Zm0 1.5a7 7 0 0 0-1.1 13.9c.7-1.5 1.1-3.5 1.1-5.9 0-2.4-.4-4.4-1.1-5.9A7 7 0 0 0 12 5Zm0 0c.7 1.5 1.1 3.5 1.1 5.9s-.4 4.4-1.1 5.9A7 7 0 0 0 12 5Zm-6.2 6.2h12.4a7 7 0 0 1 0 1.6H5.8a7 7 0 0 1 0-1.6Z',
                'ingilis_dili' => 'M12 3.5a8.5 8.5 0 1 1 0 17 8.5 8.5 0 0 1 0-17Zm0 1.5a7 7 0 0 0-1.1 13.9c.7-1.5 1.1-3.5 1.1-5.9 0-2.4-.4-4.4-1.1-5.9A7 7 0 0 0 12 5Zm0 0c.7 1.5 1.1 3.5 1.1 5.9s-.4 4.4-1.1 5.9A7 7 0 0 0 12 5Zm-6.2 6.2h12.4a7 7 0 0 1 0 1.6H5.8a7 7 0 0 1 0-1.6Z',
                'mentiq' => 'M9.5 3.5h5v2.2h2.8v3.4h-1.6l1.8 5.2H15l-1.2-3.4h-3.6L9 14.3H6.5l1.8-5.2H6.7V5.7h2.8V3.5Zm1.5 2.2v0h2v0h-2Zm-2.8 2.2v.4h7.6v-.4H8.2Zm1.4 2.2 1 2.8h2.8l1-2.8H9.6Z',
            ];
            foreach (subjects_map() as $key => $name):
                $path = $iconPaths[$key] ?? $iconPaths['azerbaycan_dili'];
            ?>
                <div class="subject-chip">
                    <svg class="ico ico-sm" viewBox="0 0 24 24" aria-hidden="true"><path d="<?= $path ?>"/></svg>
                    <span><?= e($name) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container">
        <div class="cta-panel">
            <div class="cta-copy">
                <div class="cta-icon" aria-hidden="true">
                    <svg class="ico" viewBox="0 0 24 24"><path d="M12 3.2 3.5 7.2v1.6L12 4.9l8.5 3.9V7.2L12 3.2Zm0 4.3L5.2 10.6 12 13.8l6.8-3.2L12 7.5Zm-6.5 5.4v2.1l6.5 3.1 6.5-3.1v-2.1L12 15.9 5.5 12.9Zm0 4.2v2.1L12 22.3l6.5-3.1v-2.1L12 19.9 5.5 17.1Z"/></svg>
                </div>
                <div>
                    <h2><?= e(__('cta_title')) ?></h2>
                    <p><?= e(__('cta_text')) ?></p>
                </div>
            </div>
            <a class="btn btn-lg" href="<?= url('/qeydiyyat') ?>"><?= e(__('parent_register')) ?></a>
        </div>
    </div>
</section>
