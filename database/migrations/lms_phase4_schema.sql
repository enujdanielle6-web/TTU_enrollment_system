ALTER TABLE lms_assignments ADD COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'draft' AFTER max_score;

ALTER TABLE lms_submissions ADD COLUMN status ENUM('SUBMITTED', 'RESUBMITTED', 'GRADED') NOT NULL DEFAULT 'SUBMITTED' AFTER student_id;
ALTER TABLE lms_submissions ADD COLUMN file_name VARCHAR(255) AFTER file_path;
ALTER TABLE lms_submissions ADD COLUMN mime_type VARCHAR(100) AFTER file_name;
ALTER TABLE lms_submissions ADD COLUMN file_size INT(11) AFTER mime_type;
ALTER TABLE lms_submissions ADD COLUMN graded_at DATETIME NULL AFTER feedback;
ALTER TABLE lms_submissions ADD COLUMN graded_by INT(10) UNSIGNED NULL AFTER graded_at;
ALTER TABLE lms_submissions ADD CONSTRAINT fk_lms_sub_grader FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL;
