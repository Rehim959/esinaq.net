<div class="page narrow-form">
    <form method="post" action="<?= url('/valideyn/usaq-elave') ?>" class="form-card">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Ad<input type="text" name="first_name" value="<?= old('first_name') ?>" required></label>
            <label>Soyad<input type="text" name="last_name" value="<?= old('last_name') ?>" required></label>
        </div>
        <label>Doğum tarixi<input type="date" name="birth_date" value="<?= old('birth_date') ?>" required></label>
        <div class="grid-2">
            <label>Sinif
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= old('grade') == (string)$g ? 'selected' : '' ?>><?= $g ?>-ci sinif</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sektor
                <select name="sector" required>
                    <option value="az" <?= old('sector') === 'az' ? 'selected' : '' ?>>Azərbaycan sektoru</option>
                    <option value="ru" <?= old('sector') === 'ru' ? 'selected' : '' ?>>Rus sektoru</option>
                </select>
            </label>
        </div>
        <label>Cins
            <select name="gender" required>
                <option value="boy">Oğlan</option>
                <option value="girl">Qız</option>
            </select>
        </label>
        <p class="hint">Şifrə avtomatik yaradılır: <strong>Ad + doğum ili</strong> (məs: Samir2015). Unikal link e-poçtunuza göndəriləcək.</p>
        <button class="btn" type="submit">Əlavə et və e-poçt göndər</button>
    </form>
</div>
