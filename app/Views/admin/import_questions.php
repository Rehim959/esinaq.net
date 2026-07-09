<div class="page">
    <form method="post" action="<?= url('/admin/suallar/elave') ?>" class="form-card">
        <?= csrf_field() ?>
        <div class="grid-3">
            <label>Fənn
                <select name="subject_id" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name_az']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
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
        </div>

        <label>Sualları bura yapışdırın
            <textarea name="raw_text" rows="18" required placeholder="1. 2+2 neçədir?
A) 3
B) 4
C) 5
D) 6
E) 7
+B

2. Azərbaycanın paytaxtı?
A) Gəncə
B) Bakı
C) Sumqayıt
D) Şəki
+B"></textarea>
        </label>

        <div class="hint-box">
            <strong>Format:</strong>
            <ul>
                <li>Hər sual nömrələnir: <code>1. Sual mətni?</code></li>
                <li>Variantlar: <code>A) ...</code> <code>B) ...</code> <code>C) ...</code> <code>D) ...</code> (E istəyə görə)</li>
                <li>Düzgün cavab: sətirin sonunda <code>+B</code> və ya variantın qarşısında <code>+B) 4</code></li>
                <li>Şagird imtahanda <code>+</code> işarəsini görmür — yalnız sistem bilir</li>
            </ul>
        </div>

        <button class="btn" type="submit">Sualları yüklə</button>
    </form>
</div>
