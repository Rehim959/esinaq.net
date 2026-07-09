-- Admin password is set automatically by bootstrap on first run (ADMIN_PASSWORD from .env)
-- Subjects are also ensured by bootstrap; this seed is a backup for Docker init.

INSERT IGNORE INTO subjects (code, name_az, name_ru, sort_order) VALUES
('azerbaycan_dili', 'Azərbaycan dili', 'Азербайджанский язык', 1),
('edebiyyat', 'Ədəbiyyat', 'Литература', 2),
('riyaziyyat', 'Riyaziyyat', 'Математика', 3),
('tarix', 'Tarix', 'История', 4),
('cografiya', 'Coğrafiya', 'География', 5),
('rus_dili', 'Rus dili', 'Русский язык', 6),
('ingilis_dili', 'İngilis dili', 'Английский язык', 7),
('mentiq', 'Məntiq', 'Логика', 8);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('site_name', 'eSınaq'),
('site_email', 'esinaq@esinaq.net'),
('grades', '1,2,3,4,5,6,7,8,9,10,11');
