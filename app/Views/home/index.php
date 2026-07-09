<section class="hero">
    <div class="hero-bg" aria-hidden="true"></div>
    <div class="container hero-content">
        <p class="brand-mark">eSınaq</p>
        <h1>Uşaqlarınızın bilik səviyyəsini onlayn ölçün</h1>
        <p class="hero-lead">1–11-ci siniflər · Azərbaycan və Rus sektorları · Fənn üzrə sınaqlar və aylıq hesabatlar</p>
        <div class="hero-actions">
            <a class="btn btn-lg" href="<?= url('/qeydiyyat') ?>">Valideyn qeydiyyatı</a>
            <a class="btn btn-lg btn-ghost" href="<?= url('/valideyn/giris') ?>">Daxil ol</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container narrow">
        <h2>Necə işləyir?</h2>
        <p class="section-lead">Üç sadə addım — qeydiyyatdan nəticəyə qədər.</p>
        <ol class="steps">
            <li><strong>Qeydiyyat</strong> — Valideyn hesabı yaradın, övladlarınızı əlavə edin.</li>
            <li><strong>İmtahan</strong> — Hər uşağa unikal link göndərilir; şifrə: Ad + doğum ili.</li>
            <li><strong>Hesabat</strong> — Nəticələri, səhv sualları və aylıq tendensiyanı izləyin.</li>
        </ol>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <h2>Fənnlər</h2>
        <p class="section-lead">Hər sinif və sektor üçün ayrı sual bankı.</p>
        <div class="subject-grid">
            <?php foreach (subjects_map() as $name): ?>
                <div class="subject-chip"><?= e($name) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
