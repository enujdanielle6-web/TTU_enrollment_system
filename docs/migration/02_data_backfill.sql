-- Stage 2: Data Backfill & Baseline Synchronization Script
-- Database: sia
-- Safe, idempotent transformations executed within an explicit transaction

START TRANSACTION;

-- 1. Faculty ID Disentanglement:
-- Copy student_number to employee_id for all faculty rows, then nullify student_number
UPDATE `users` 
SET `employee_id` = `student_number`,
    `student_number` = NULL
WHERE `role` = 'faculty' 
  AND `student_number` IS NOT NULL;

-- Ensure every faculty member has an employee_id
UPDATE `users` 
SET `employee_id` = CONCAT('EMP-', DATE_FORMAT(created_at, '%Y'), '-', LPAD(id, 4, '0'))
WHERE `role` = 'faculty' 
  AND (`employee_id` IS NULL OR `employee_id` = '');

-- 2. Synchronize Enrolled Students to Official Role
UPDATE `users` u
JOIN `applications` a ON a.user_id = u.id
SET u.role = 'student'
WHERE a.status = 'enrolled' 
  AND u.role = 'applicant';

-- 3. Initialize LMS Status
-- Faculty members set to active
UPDATE `users` 
SET `lms_status` = 'active' 
WHERE `role` = 'faculty' AND `is_active` = 1;

-- Officially enrolled students set to active
UPDATE `users` u
JOIN `applications` a ON a.user_id = u.id
SET u.lms_status = 'active'
WHERE a.status = 'enrolled' AND u.is_active = 1;

-- 4. Baseline Population of faculty_profiles
INSERT INTO `faculty_profiles` (
    `user_id`, 
    `employee_id`, 
    `program_id`, 
    `academic_rank`, 
    `employment_type`, 
    `max_teaching_units`, 
    `status`
)
SELECT 
    u.id AS user_id,
    u.employee_id,
    cp.id AS program_id,
    'Instructor I' AS academic_rank,
    'full_time' AS employment_type,
    18.00 AS max_teaching_units,
    IF(u.is_active = 1, 'active', 'inactive') AS status
FROM `users` u
LEFT JOIN `college_programs` cp ON (
    (u.department LIKE '%Computer Science%' AND cp.code = 'BSCS') OR
    (u.department LIKE '%Information Technology%' AND cp.code = 'BSIT') OR
    (u.department LIKE '%Nursing%' AND cp.code = 'BSN') OR
    (u.department LIKE '%Business%' AND cp.code = 'BSBA') OR
    (u.department = cp.code)
)
WHERE u.role = 'faculty'
ON DUPLICATE KEY UPDATE
    `employee_id` = VALUES(`employee_id`),
    `program_id` = VALUES(`program_id`),
    `status` = VALUES(`status`);

-- 5. Timetable Relational Instructor Resolution
-- Match legacy college_section_subjects.instructor string to users.id
UPDATE `college_section_subjects` css
JOIN `users` u ON TRIM(css.instructor) = CONCAT(u.first_name, ' ', u.last_name)
SET css.faculty_user_id = u.id
WHERE u.role = 'faculty' 
  AND css.instructor IS NOT NULL 
  AND css.faculty_user_id IS NULL;

-- Inverted name match (Last, First)
UPDATE `college_section_subjects` css
JOIN `users` u ON TRIM(css.instructor) = CONCAT(u.last_name, ', ', u.first_name)
SET css.faculty_user_id = u.id
WHERE u.role = 'faculty' 
  AND css.instructor IS NOT NULL 
  AND css.faculty_user_id IS NULL;

-- Repeat for SHS
UPDATE `shs_section_subjects` sss
JOIN `users` u ON TRIM(sss.instructor) = CONCAT(u.first_name, ' ', u.last_name)
SET sss.faculty_user_id = u.id
WHERE u.role = 'faculty' 
  AND sss.instructor IS NOT NULL 
  AND sss.faculty_user_id IS NULL;

-- 6. De-duplicate LMS Courses before applying unique constraint
DELETE c1 FROM `lms_courses` c1
JOIN `lms_courses` c2 
  ON c1.academic_level = c2.academic_level 
  AND c1.academic_section_id = c2.academic_section_id 
  AND c1.subject_id = c2.subject_id 
  AND c1.id < c2.id;

-- 7. Add Compound Unique Key to lms_courses
SET @idx_lms_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'lms_courses' 
      AND INDEX_NAME = 'unique_section_subject_course'
);
SET @add_lms_idx = IF(@idx_lms_exists = 0, 'ALTER TABLE `lms_courses` ADD UNIQUE KEY `unique_section_subject_course` (`academic_level`, `academic_section_id`, `subject_id`)', 'SELECT 1');
PREPARE stmt FROM @add_lms_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. Reconcile Lowest-ID Auto-Provisioned Courses with Section Instructor
UPDATE `lms_courses` lc
JOIN `college_section_subjects` css 
  ON css.college_section_id = lc.academic_section_id 
  AND css.subject_id = lc.subject_id
SET lc.faculty_user_id = css.faculty_user_id
WHERE lc.academic_level = 'College' 
  AND css.faculty_user_id IS NOT NULL 
  AND lc.faculty_user_id != css.faculty_user_id;

UPDATE `lms_courses` lc
JOIN `shs_section_subjects` sss 
  ON sss.shs_section_id = lc.academic_section_id 
  AND sss.subject_id = lc.subject_id
SET lc.faculty_user_id = sss.faculty_user_id
WHERE lc.academic_level = 'SHS' 
  AND sss.faculty_user_id IS NOT NULL 
  AND lc.faculty_user_id != sss.faculty_user_id;

-- 9. Seed Default Faculty Availability (Monday - Saturday, 07:00 - 19:00)
INSERT IGNORE INTO `faculty_availability` (`faculty_user_id`, `day_of_week`, `start_time`, `end_time`, `is_available`)
SELECT 
    u.id, 
    d.day_name, 
    '07:00:00', 
    '19:00:00', 
    1
FROM `users` u
CROSS JOIN (
    SELECT 'Monday' AS day_name UNION ALL
    SELECT 'Tuesday' UNION ALL
    SELECT 'Wednesday' UNION ALL
    SELECT 'Thursday' UNION ALL
    SELECT 'Friday' UNION ALL
    SELECT 'Saturday'
) d
WHERE u.role = 'faculty' AND u.is_active = 1;

COMMIT;
