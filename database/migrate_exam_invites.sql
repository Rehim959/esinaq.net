-- Exam invitation / parent interest / admin approval
-- Run in phpMyAdmin if auto-migrate does not run

CREATE TABLE IF NOT EXISTS exam_invites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    status ENUM('invited','interested','approved','rejected') NOT NULL DEFAULT 'invited',
    interested_at TIMESTAMP NULL DEFAULT NULL,
    decided_at TIMESTAMP NULL DEFAULT NULL,
    decided_by INT UNSIGNED NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_exam_parent (exam_id, parent_id),
    UNIQUE KEY uq_invite_token (token),
    INDEX idx_exam_status (exam_id, status),
    CONSTRAINT fk_invite_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    CONSTRAINT fk_invite_parent FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
    CONSTRAINT fk_invite_admin FOREIGN KEY (decided_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
