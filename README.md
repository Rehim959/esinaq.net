# eSınaq — Onlayn sınaq və yoxlama platforması

PHP + MySQL imtahan sistemi · Domain: **esinaq.net** · Hosting: azhosting.az

## Xüsusiyyətlər

- Valideyn qeydiyyatı + xoş gəldin e-poçtu (`esinaq@esinaq.net`)
- Şifrəmi unutdum → e-poçt ilə bərpa linki
- Uşaq əlavə etmə → unikal imtahan linki + şifrə (Ad + doğum ili)
- Admin: copy-paste ilə sual yükləmə (A–E, düzgün cavab `+B`)
- 1–11 sinif, AZ/RU sektor, 8 fənn
- İmtahan: random suallar, 3 panelli interfeys, taymer
- Uşaq: hər sual üzrə düzgün/səhv nəticə
- Valideyn: yalnız səhv suallar + aylıq/fənn statistikası

## Lokal işə salma (Docker)

Docker Desktop açıq olmalıdır:

```bash
docker compose up -d --build
```

| Xidmət | URL |
|--------|-----|
| Sayt | http://localhost:8080 |
| MailHog (e-poçt test) | http://localhost:8025 |
| MySQL | localhost:3307 |

**Admin:** `admin@esinaq.net` / `Admin123!`  
**Admin panel:** http://localhost:8080/admin/login

## GitHub-a yükləmə

```bash
git add .
git commit -m "Initial eSınaq PHP exam platform"
# GitHub-da yeni repo yaradın, sonra:
git remote add origin https://github.com/USERNAME/esinaq.net.git
git branch -M main
git push -u origin main
```

## azhosting.az-a deploy

1. GitHub-dan clone edin və ya FTP ilə faylları yükləyin.
2. Mümkünsə domain **Document Root** = `public/` qovluğu.
3. `.env.example` → `.env` kopyalayın, DB və SMTP yazın.
4. Brauzerdə `https://esinaq.net/install.php` açın → quraşdırın → **install.php silin**.
5. E-poçt: `esinaq@esinaq.net` SMTP məlumatlarını `.env`-ə yazın.

```env
APP_URL=https://esinaq.net
DB_HOST=localhost
DB_NAME=...
DB_USER=...
DB_PASS=...
MAIL_HOST=mail.esinaq.net
MAIL_PORT=587
MAIL_USER=esinaq@esinaq.net
MAIL_PASS=...
MAIL_ENCRYPTION=tls
MAIL_FROM=esinaq@esinaq.net
ADMIN_PASSWORD=GüclüŞifrə123!
```

## Sual formatı (admin copy-paste)

```
1. 2+2 neçədir?
A) 3
B) 4
C) 5
D) 6
+B

2. Paytaxt hansıdır?
A) Gəncə
B) Bakı
C) Sumqayıt
D) Şəki
+B
```

`+` yalnız sistemdə saxlanır — şagird görmür.

## Test axını

1. Admin → Sual əlavə et (copy-paste) → İmtahan yarat → Başlat  
2. Valideyn qeydiyyat → Uşaq əlavə et → e-poçtdan link  
3. Uşaq link + şifrə ilə imtahan → nəticə  
4. Valideyn panelində səhv suallar və statistika  

## Struktur

```
app/           Controllers, Services, Views, Core
public/        Web root (index.php, assets)
database/      schema.sql, seed.sql, sample_questions.sql
routes/        web.php
docker/        Dockerfile, apache.conf
install.php    Bir dəfəlik hosting quraşdırıcı (sonra silin)
```

## Texnologiya

PHP 8.2 · PDO MySQL · Composer yoxdur (shared hosting üçün) · SMTP e-poçt
