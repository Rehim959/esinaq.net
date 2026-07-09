<div class="page">
    <form method="post" action="<?= url('/admin/imtahanlar/yeni') ?>" class="form-card">
        <?= csrf_field() ?>
        <label>Başlıq<input type="text" name="title" required placeholder="5-ci sinif Riyaziyyat sınağı"></label>
        <div class="grid-3">
            <label>Sinif
                <select name="grade" required>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>"><?= $g ?>-ci sinif</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Sektor
                <select name="sector" required>
                    <option value="az">Azərbaycan</option>
                    <option value="ru">Rus</option>
                </select>
            </label>
            <label>Müddət (dəqiqə)<input type="number" name="duration_minutes" value="60" min="5" required></label>
        </div>
        <div class="grid-2">
            <label>Başlama (istəyə görə)<input type="datetime-local" name="starts_at"></label>
            <label>Bitmə (istəyə görə)<input type="datetime-local" name="ends_at"></label>
        </div>

        <h3>Fənnlər və sual sayı</h3>
        <p class="muted">Seçilmiş fənnlərdən bazada olan suallar təsadüfi seçiləcək.</p>
        <div class="subject-pick">
            <?php foreach ($subjects as $s): ?>
                <label class="pick-row">
                    <input type="checkbox" name="subject_ids[]" value="<?= (int)$s['id'] ?>">
                    <span><?= e($s['name_az']) ?></span>
                    <input type="number" name="question_counts[<?= (int)$s['id'] ?>]" value="10" min="1" max="50" title="Sual sayı">
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn" type="submit">İmtahanı yarat</button>
    </form>
</div>
