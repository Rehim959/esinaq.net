-- eSınaq.net: ata adı + məcburi telefon (phpMyAdmin-də bir dəfə işlədin)
SET NAMES utf8mb4;

-- parents.patronymic
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'parents' AND COLUMN_NAME = 'patronymic');
SET @s := IF(@c = 0, "ALTER TABLE parents ADD COLUMN patronymic VARCHAR(100) NOT NULL DEFAULT '' AFTER last_name", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- children.patronymic
SET @c2 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'children' AND COLUMN_NAME = 'patronymic');
SET @s2 := IF(@c2 = 0, "ALTER TABLE children ADD COLUMN patronymic VARCHAR(100) NOT NULL DEFAULT '' AFTER last_name", 'SELECT 1');
PREPARE st2 FROM @s2; EXECUTE st2; DEALLOCATE PREPARE st2;

-- parents.phone NOT NULL (mövcud boşları placeholder et)
UPDATE parents SET phone = '—' WHERE phone IS NULL OR phone = '';
ALTER TABLE parents MODIFY phone VARCHAR(30) NOT NULL;
