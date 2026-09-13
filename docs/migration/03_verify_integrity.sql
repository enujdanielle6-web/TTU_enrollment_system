-- Stage 4: Database Integrity & Migration Assertions
-- Target Database: sia
-- Execution Protocol: Every query below MUST return 0 rows. Any row returned indicates a failure.

-- Assertion 1: Zero Faculty with student_number or missing employee_id
SELECT 'Assertion 1 FAILED: Faculty with invalid identifier separation' AS error_message, id, role, student_number, employee_id
FROM `users` 
WHERE `role` = 'faculty' 
  AND (`employee_id` IS NULL OR `employee_id` = '' OR `student_number` IS NOT NULL);

-- Assertion 2: Zero Enrolled Students with 'applicant' role
SELECT 'Assertion 2 FAILED: Enrolled applicant not synchronized to student role' AS error_message, u.id, u.email, u.role, a.status AS application_status
FROM `users` u
JOIN `applications` a ON a.user_id = u.id
WHERE a.status = 'enrolled' AND u.role = 'applicant';

-- Assertion 3: Zero Faculty without faculty_profiles
SELECT 'Assertion 3 FAILED: Faculty member lacking faculty_profiles entry' AS error_message, u.id, u.first_name, u.last_name, u.email
FROM `users` u
LEFT JOIN `faculty_profiles` fp ON fp.user_id = u.id
WHERE u.role = 'faculty' AND fp.id IS NULL;

-- Assertion 4: Zero Duplicate LMS Courses for a Section-Subject
SELECT 'Assertion 4 FAILED: Duplicate LMS courses detected' AS error_message, academic_level, academic_section_id, subject_id, COUNT(*) AS dup_count
FROM `lms_courses`
GROUP BY `academic_level`, `academic_section_id`, `subject_id`
HAVING COUNT(*) > 1;

-- Assertion 5: Zero Foreign Keys on lms_courses with CASCADE Delete Rule
SELECT 'Assertion 5 FAILED: Catastrophic CASCADE hazard still active on lms_courses' AS error_message, CONSTRAINT_NAME, DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND CONSTRAINT_NAME = 'fk_lms_course_faculty'
  AND DELETE_RULE = 'CASCADE';

-- Assertion 6: Zero Misaligned LMS Courses vs Section Subject Assigned Faculty
SELECT 'Assertion 6 FAILED: LMS Course faculty mismatched from section subject instructor' AS error_message, lc.id AS course_id, lc.faculty_user_id AS lms_faculty_id, css.faculty_user_id AS section_faculty_id
FROM `lms_courses` lc
JOIN `college_section_subjects` css 
  ON css.college_section_id = lc.academic_section_id 
  AND css.subject_id = lc.subject_id
WHERE lc.academic_level = 'College' 
  AND css.faculty_user_id IS NOT NULL 
  AND lc.faculty_user_id != css.faculty_user_id;

-- Assertion 7: Zero Active Faculty with Inactive LMS Status
SELECT 'Assertion 7 FAILED: Active faculty with inactive LMS standing' AS error_message, id, role, is_active, lms_status
FROM `users`
WHERE `role` = 'faculty' AND `is_active` = 1 AND `lms_status` != 'active';
