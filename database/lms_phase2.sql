-- LMS Phase 2 Foundation Database Schema
-- Run this script to generate the required LMS tables in 3NF

CREATE TABLE IF NOT EXISTS `lms_courses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT UNSIGNED NOT NULL,
  `college_section_id` INT UNSIGNED DEFAULT NULL,
  `teacher_id` INT UNSIGNED DEFAULT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `welcome_message` TEXT DEFAULT NULL,
  `is_published` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_subject_section_teacher` (`subject_id`, `college_section_id`, `teacher_id`),
  CONSTRAINT `fk_lms_courses_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_courses_section` FOREIGN KEY (`college_section_id`) REFERENCES `college_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_courses_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_course_resources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lms_course_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_resources_course` (`lms_course_id`),
  CONSTRAINT `fk_lms_resources_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lms_course_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `sequence_order` INT NOT NULL DEFAULT 0,
  `is_published` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_modules_course` (`lms_course_id`),
  CONSTRAINT `fk_lms_modules_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_lessons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lms_module_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `overview` TEXT DEFAULT NULL,
  `sequence_order` INT NOT NULL DEFAULT 0,
  `is_published` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_lessons_module` (`lms_module_id`),
  CONSTRAINT `fk_lms_lessons_module` FOREIGN KEY (`lms_module_id`) REFERENCES `lms_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_materials` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lms_lesson_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content_body` MEDIUMTEXT DEFAULT NULL,
  `sequence_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_materials_lesson` (`lms_lesson_id`),
  CONSTRAINT `fk_lms_materials_lesson` FOREIGN KEY (`lms_lesson_id`) REFERENCES `lms_lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_material_files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lms_material_id` INT UNSIGNED NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `storage_path` VARCHAR(255) NOT NULL,
  `file_size_bytes` INT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_files_material` (`lms_material_id`),
  CONSTRAINT `fk_lms_files_material` FOREIGN KEY (`lms_material_id`) REFERENCES `lms_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_student_progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `lms_lesson_id` INT UNSIGNED NOT NULL,
  `status` ENUM('in_progress', 'completed') NOT NULL DEFAULT 'in_progress',
  `last_accessed_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_lms_progress_student_lesson` (`student_id`, `lms_lesson_id`),
  CONSTRAINT `fk_lms_progress_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_progress_lesson` FOREIGN KEY (`lms_lesson_id`) REFERENCES `lms_lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
