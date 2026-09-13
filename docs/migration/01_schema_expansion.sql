-- Stage 1: Additive Schema Expansion DDL (Zero-Downtime)
-- Database: sia
-- All operations are additive and non-blocking

-- 1. Disentangle User Identity: Add employee_id and lms_status to users
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `employee_id` VARCHAR(50) NULL AFTER `student_number`,
  ADD COLUMN IF NOT EXISTS `lms_status` ENUM('inactive', 'active', 'suspended') NOT NULL DEFAULT 'inactive' AFTER `role`,
  ADD UNIQUE KEY IF NOT EXISTS `idx_users_employee_id` (`employee_id`),
  ADD KEY IF NOT EXISTS `idx_users_lms_status` (`lms_status`);

-- 2. Create faculty_profiles Table
CREATE TABLE IF NOT EXISTS `faculty_profiles` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `employee_id` VARCHAR(50) NOT NULL,
  `program_id` INT(10) UNSIGNED NULL,
  `academic_rank` VARCHAR(100) NOT NULL DEFAULT 'Instructor I',
  `employment_type` ENUM('full_time', 'part_time', 'adjunct') NOT NULL DEFAULT 'full_time',
  `max_teaching_units` DECIMAL(4,2) NOT NULL DEFAULT 18.00,
  `specializations` TEXT NULL,
  `status` ENUM('active', 'on_leave', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_faculty_user_id` (`user_id`),
  UNIQUE KEY `idx_faculty_employee_id` (`employee_id`),
  KEY `fk_faculty_profiles_program` (`program_id`),
  CONSTRAINT `fk_faculty_profiles_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_faculty_profiles_program` 
    FOREIGN KEY (`program_id`) REFERENCES `college_programs` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create faculty_availability Table
CREATE TABLE IF NOT EXISTS `faculty_availability` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `faculty_user_id` INT(10) UNSIGNED NOT NULL,
  `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` TIME NOT NULL DEFAULT '07:00:00',
  `end_time` TIME NOT NULL DEFAULT '19:00:00',
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_faculty_day_window` (`faculty_user_id`, `day_of_week`, `start_time`, `end_time`),
  CONSTRAINT `fk_fa_faculty_user` 
    FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create faculty_specializations Table
CREATE TABLE IF NOT EXISTS `faculty_specializations` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `faculty_user_id` INT(10) UNSIGNED NOT NULL,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `competency_level` ENUM('Primary', 'Secondary', 'Qualified') NOT NULL DEFAULT 'Primary',
  `years_experience` INT UNSIGNED DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_faculty_subject_spec` (`faculty_user_id`, `subject_id`),
  CONSTRAINT `fk_fspec_faculty_user` 
    FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fspec_subject` 
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Add Relational Faculty Foreign Key to Section Subjects
ALTER TABLE `college_section_subjects` 
  ADD COLUMN IF NOT EXISTS `faculty_user_id` INT(10) UNSIGNED NULL AFTER `room`,
  ADD KEY IF NOT EXISTS `idx_css_faculty_user_id` (`faculty_user_id`);

ALTER TABLE `shs_section_subjects` 
  ADD COLUMN IF NOT EXISTS `faculty_user_id` INT(10) UNSIGNED NULL AFTER `room`,
  ADD KEY IF NOT EXISTS `idx_sss_faculty_user_id` (`faculty_user_id`);

-- 6. Replace Dangerous ON DELETE CASCADE on lms_courses.faculty_user_id with ON DELETE RESTRICT
-- Note: MariaDB requires dropping and re-adding foreign key constraint
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'lms_courses' 
      AND CONSTRAINT_NAME = 'fk_lms_course_faculty'
);

SET @drop_fk = IF(@fk_exists > 0, 'ALTER TABLE `lms_courses` DROP FOREIGN KEY `fk_lms_course_faculty`', 'SELECT 1');
PREPARE stmt FROM @drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `lms_courses` 
  ADD CONSTRAINT `fk_lms_course_faculty` 
  FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
  ON DELETE RESTRICT ON UPDATE CASCADE;

-- 7. Add Relational Foreign Key Constraints to Section Subjects
SET @fk_css_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'college_section_subjects' 
      AND CONSTRAINT_NAME = 'fk_css_faculty_user'
);
SET @add_css_fk = IF(@fk_css_exists = 0, 'ALTER TABLE `college_section_subjects` ADD CONSTRAINT `fk_css_faculty_user` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE stmt FROM @add_css_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sss_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'shs_section_subjects' 
      AND CONSTRAINT_NAME = 'fk_sss_faculty_user'
);
SET @add_sss_fk = IF(@fk_sss_exists = 0, 'ALTER TABLE `shs_section_subjects` ADD CONSTRAINT `fk_sss_faculty_user` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE stmt FROM @add_sss_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. Create Canonical SQL View: faculty_workloads_view
CREATE OR REPLACE VIEW `faculty_workloads_view` AS
SELECT 
    u.id AS faculty_user_id,
    COALESCE(u.employee_id, u.student_number) AS employee_id,
    CONCAT(u.first_name, ' ', u.last_name) AS faculty_name,
    fp.academic_rank,
    fp.employment_type,
    fp.max_teaching_units,
    COUNT(DISTINCT css.id) AS total_assigned_classes,
    COALESCE(SUM(s.units), 0) AS total_teaching_units,
    COALESCE(SUM(
        ROUND(TIME_TO_SEC(TIMEDIFF(css.end_time, css.start_time)) / 3600.0, 2)
    ), 0) AS total_weekly_contact_hours,
    CASE 
        WHEN COALESCE(SUM(s.units), 0) > fp.max_teaching_units THEN 'Overload'
        WHEN COALESCE(SUM(s.units), 0) = fp.max_teaching_units THEN 'Full'
        ELSE 'Underload'
    END AS workload_status
FROM `users` u
LEFT JOIN `faculty_profiles` fp ON fp.user_id = u.id
LEFT JOIN `college_section_subjects` css ON css.faculty_user_id = u.id
LEFT JOIN `subjects` s ON s.id = css.subject_id
WHERE u.role = 'faculty' AND u.is_active = 1
GROUP BY u.id, u.employee_id, u.student_number, u.first_name, u.last_name, fp.academic_rank, fp.employment_type, fp.max_teaching_units;
