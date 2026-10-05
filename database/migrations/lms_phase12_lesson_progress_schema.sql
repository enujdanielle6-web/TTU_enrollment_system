-- ============================================================================
-- LMS Phase 12: lesson progress tracking
-- Records which lesson files (lms_materials) each student has opened in the
-- preview window and how far they scrolled. A lesson counts as finished once
-- the student reaches the end (completed_at is set).
--
-- Additive and safe to run more than once. Run it once on every existing
-- database (local XAMPP and InfinityFree) in phpMyAdmin > SQL.
-- New installs from 01_schema.sql / schema.sql already include this table.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `lms_material_progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_material_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `max_scroll_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `first_opened_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_opened_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_matprog_material_student` (`lms_material_id`,`student_id`),
  KEY `idx_lms_matprog_student` (`student_id`),
  CONSTRAINT `fk_lms_matprog_material` FOREIGN KEY (`lms_material_id`) REFERENCES `lms_materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_matprog_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
