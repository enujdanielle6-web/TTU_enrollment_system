-- LMS Phase 9 Migration: Platform-Wide LMS Announcements
-- Modifies lms_announcements to allow platform-wide (lms_course_id IS NULL) notices
-- Adds target_audience ('all', 'students', 'faculty') and severity ('info', 'warning', 'danger', 'success')

ALTER TABLE `lms_announcements` 
  MODIFY `lms_course_id` int(10) unsigned NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `target_audience` enum('all','students','faculty') NOT NULL DEFAULT 'all' AFTER `content`,
  ADD COLUMN IF NOT EXISTS `severity` enum('info','warning','danger','success') NOT NULL DEFAULT 'info' AFTER `target_audience`;
