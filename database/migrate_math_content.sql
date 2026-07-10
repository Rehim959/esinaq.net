-- Math / geometry rich content support
-- Run in phpMyAdmin on the eSinaq database

ALTER TABLE questions
  ADD COLUMN content_format ENUM('plain','html') NOT NULL DEFAULT 'plain' AFTER question_text;

ALTER TABLE questions
  MODIFY option_a TEXT NOT NULL,
  MODIFY option_b TEXT NOT NULL,
  MODIFY option_c TEXT NOT NULL,
  MODIFY option_d TEXT NOT NULL,
  MODIFY option_e TEXT NULL;
