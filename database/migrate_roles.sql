-- eSınaq.net: admin roles (run once in phpMyAdmin)
SET NAMES utf8mb4;

-- Add role column if missing
SET @col_role := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins' AND COLUMN_NAME = 'role'
);
SET @sql_role := IF(@col_role = 0,
  "ALTER TABLE admins ADD COLUMN role ENUM('super_admin','moderator') NOT NULL DEFAULT 'moderator' AFTER full_name",
  'SELECT 1');
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

-- Promote the oldest / first admin to super_admin (safe for existing installs)
UPDATE admins
SET role = 'super_admin', is_active = 1
WHERE id = (SELECT id FROM (SELECT MIN(id) AS id FROM admins) AS t);
