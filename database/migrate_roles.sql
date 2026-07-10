-- eSınaq.net: admin roles (run once in phpMyAdmin if auto-migrate skipped)
SET NAMES utf8mb4;

-- Add role column if missing
SET @col_role := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'role'
);
SET @sql_role := IF(@col_role = 0,
  "ALTER TABLE admins ADD COLUMN role ENUM('super_admin','admin','moderator') NOT NULL DEFAULT 'moderator' AFTER full_name",
  "ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin','admin','moderator') NOT NULL DEFAULT 'moderator'");
PREPARE stmt_role FROM @sql_role;
EXECUTE stmt_role;
DEALLOCATE PREPARE stmt_role;

-- Add is_active column if missing
SET @col_active := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'is_active'
);
SET @sql_active := IF(@col_active = 0,
  "ALTER TABLE admins ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role",
  'SELECT 1');
PREPARE stmt_active FROM @sql_active;
EXECUTE stmt_active;
DEALLOCATE PREPARE stmt_active;

-- Keep primary account as super_admin
UPDATE admins
SET role = 'super_admin', is_active = 1
WHERE email = 'admin@esinaq.net'
   OR id = (SELECT id FROM (SELECT MIN(id) AS id FROM admins) AS t);
