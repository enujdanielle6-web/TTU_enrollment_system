-- Emergency Rollback Script for Stages 1 to 3
-- Target Database: sia
-- Restores database to exact pre-migration state in < 120 seconds

START TRANSACTION;

-- 1. Restore users table identity state
UPDATE `users` SET `student_number` = `employee_id` WHERE `role` = 'faculty' AND `employee_id` IS NOT NULL;
UPDATE `users` SET `employee_id` = NULL, `lms_status` = 'inactive';
UPDATE `users` SET `role` = 'applicant' WHERE `id` IN (SELECT `user_id` FROM `applications` WHERE `status` = 'enrolled');

-- 2. Drop relational helper tables and views
DROP VIEW IF EXISTS `faculty_workloads_view`;
DROP TABLE IF EXISTS `faculty_availability`;
DROP TABLE IF EXISTS `faculty_specializations`;
DROP TABLE IF EXISTS `faculty_profiles`;

-- 3. Restore lms_courses foreign key to CASCADE
ALTER TABLE `lms_courses` DROP FOREIGN KEY `fk_lms_course_faculty`;
ALTER TABLE `lms_courses` ADD CONSTRAINT `fk_lms_course_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `lms_courses` DROP INDEX IF EXISTS `unique_section_subject_course`;

-- 4. Remove relational faculty columns on timetable tables
ALTER TABLE `college_section_subjects` DROP FOREIGN KEY IF EXISTS `fk_css_faculty_user`, DROP COLUMN IF EXISTS `faculty_user_id`;
ALTER TABLE `shs_section_subjects` DROP FOREIGN KEY IF EXISTS `fk_sss_faculty_user`, DROP COLUMN IF EXISTS `faculty_user_id`;

-- 5. Drop new columns on users
ALTER TABLE `users` DROP COLUMN IF EXISTS `employee_id`, DROP COLUMN IF EXISTS `lms_status`;

COMMIT;
