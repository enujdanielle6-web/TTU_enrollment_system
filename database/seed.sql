-- --------------------------------------------------------
-- ENROLLMENT MANAGEMENT SYSTEM SEED DATA
-- GENERATED FROM CURRENT IMPLEMENTATION
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. SYSTEM SETTINGS
-- --------------------------------------------------------
TRUNCATE TABLE `system_settings`;
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('school_name', 'Tech Trends University', 'Official Name of the Institution'),
('school_year', '2026-2027', 'Current Academic Year'),
('semester', 'First', 'Current Semester (First, Second, Summer)'),
('system_status', 'active', 'System Operational Status');

-- --------------------------------------------------------
-- 2. USERS (Admin & Staff Accounts)
-- Password for all accounts: password123
-- Hash: $2y$10$6S0PPYgyk.V.8z46rpQwLevBWrklaAUhDzSzZAJU56dwA9Q2MMvni
-- --------------------------------------------------------
TRUNCATE TABLE `users`;
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `department`, `is_active`) VALUES
(1, 'System', 'Admin', 'admin@ttu.edu.ph', '$2y$10$6S0PPYgyk.V.8z46rpQwLevBWrklaAUhDzSzZAJU56dwA9Q2MMvni', 'superadmin', 'IT', 1),
(2, 'Admissions', 'Officer', 'admissions@ttu.edu.ph', '$2y$10$6S0PPYgyk.V.8z46rpQwLevBWrklaAUhDzSzZAJU56dwA9Q2MMvni', 'admissions', 'Admissions', 1),
(3, 'Registrar', 'Head', 'registrar@ttu.edu.ph', '$2y$10$6S0PPYgyk.V.8z46rpQwLevBWrklaAUhDzSzZAJU56dwA9Q2MMvni', 'admin', 'Registrar', 1),
(4, 'Finance', 'Cashier', 'cashier@ttu.edu.ph', '$2y$10$6S0PPYgyk.V.8z46rpQwLevBWrklaAUhDzSzZAJU56dwA9Q2MMvni', 'cashier', 'Finance', 1),
(5, 'Scholarship', 'Coordinator', 'scholarship@ttu.edu.ph', '$2y$10$6S0PPYgyk.V.8z46rpQwLevBWrklaAUhDzSzZAJU56dwA9Q2MMvni', 'scholarship', 'Student Affairs', 1),
(6, 'Jane', 'Doe', 'jane.doe@example.com', '$2y$10$6S0PPYgyk.V.8z46rpQwLevBWrklaAUhDzSzZAJU56dwA9Q2MMvni', 'applicant', NULL, 1);

-- --------------------------------------------------------
-- 3. SUBJECTS
-- --------------------------------------------------------
TRUNCATE TABLE `subjects`;
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `units`, `education_level`, `status`) VALUES
-- SHS Core Subjects
(1, 'ENG101', 'Oral Communication', 3, 'SHS', 1),
(2, 'MATH101', 'General Mathematics', 3, 'SHS', 1),
(3, 'SCI101', 'Earth and Life Science', 3, 'SHS', 1),
-- College IT Subjects
(4, 'CC101', 'Introduction to Computing', 3, 'College', 1),
(5, 'CC102', 'Computer Programming 1', 3, 'College', 1),
(6, 'IT101', 'IT Fundamentals', 3, 'College', 1),
(7, 'GE101', 'Understanding the Self', 3, 'Both', 1);

-- --------------------------------------------------------
-- 4. SHS STRANDS & CURRICULUM
-- --------------------------------------------------------
TRUNCATE TABLE `shs_strands`;
INSERT INTO `shs_strands` (`id`, `code`, `name`, `is_active`) VALUES
(1, 'STEM', 'Science, Technology, Engineering, and Mathematics', 1),
(2, 'ABM', 'Accountancy, Business, and Management', 1),
(3, 'HUMSS', 'Humanities and Social Sciences', 1),
(4, 'ICT', 'Information and Communications Technology (TVL)', 1);

TRUNCATE TABLE `shs_curriculum`;
INSERT INTO `shs_curriculum` (`strand_id`, `grade_level`, `semester`, `subject_id`) VALUES
(1, 'Grade 11', 'First', 1),
(1, 'Grade 11', 'First', 2),
(1, 'Grade 11', 'First', 3),
(4, 'Grade 11', 'First', 1),
(4, 'Grade 11', 'First', 2);

TRUNCATE TABLE `shs_sections`;
INSERT INTO `shs_sections` (`id`, `section_code`, `strand_id`, `grade_level`, `academic_year`, `schedule_type`, `capacity`, `status`) VALUES
(1, 'STEM-11A', 1, 'Grade 11', '2026-2027', 'Morning', 40, 1),
(2, 'ICT-11A', 4, 'Grade 11', '2026-2027', 'Afternoon', 40, 1);

TRUNCATE TABLE `shs_section_subjects`;
INSERT INTO `shs_section_subjects` (`shs_section_id`, `subject_id`, `instructor`, `room`, `day`, `start_time`, `end_time`) VALUES
(1, 1, 'Mr. Smith', 'Room 101', 'MWF', '07:30:00', '08:30:00'),
(1, 2, 'Ms. Johnson', 'Room 102', 'TTH', '08:30:00', '10:00:00'),
(1, 3, 'Dr. Brown', 'Lab 1', 'MWF', '10:00:00', '11:00:00');

-- --------------------------------------------------------
-- 5. COLLEGE PROGRAMS & CURRICULA
-- --------------------------------------------------------
TRUNCATE TABLE `college_programs`;
INSERT INTO `college_programs` (`id`, `code`, `name`, `is_active`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 1),
(2, 'BSCS', 'Bachelor of Science in Computer Science', 1),
(3, 'BSIS', 'Bachelor of Science in Information Systems', 1);

TRUNCATE TABLE `college_curricula`;
INSERT INTO `college_curricula` (`id`, `program_id`, `curriculum_name`, `version`, `effective_academic_year`, `status`) VALUES
(1, 1, 'BSIT Default Curriculum', '2026', '2026-2027', 'active'),
(2, 2, 'BSCS Default Curriculum', '2026', '2026-2027', 'active');

TRUNCATE TABLE `college_curriculum_subjects`;
INSERT INTO `college_curriculum_subjects` (`curriculum_id`, `subject_id`, `year_level`, `semester`, `display_order`) VALUES
(1, 4, '1st Year', 'First', 1),
(1, 5, '1st Year', 'First', 2),
(1, 6, '1st Year', 'First', 3),
(1, 7, '1st Year', 'First', 4);

TRUNCATE TABLE `college_sections`;
INSERT INTO `college_sections` (`id`, `section_code`, `program_id`, `curriculum_id`, `academic_year`, `year_level`, `semester`, `schedule_type`, `capacity`, `status`) VALUES
(1, 'BSIT-1A', 1, 1, '2026-2027', '1st Year', 'First', 'Morning', 40, 1),
(2, 'BSIT-1B', 1, 1, '2026-2027', '1st Year', 'First', 'Afternoon', 40, 1);

TRUNCATE TABLE `college_section_subjects`;
INSERT INTO `college_section_subjects` (`college_section_id`, `subject_id`, `instructor`, `room`, `day`, `start_time`, `end_time`) VALUES
(1, 4, 'Prof. Alan Turing', 'Lab A', 'MWF', '08:00:00', '09:00:00'),
(1, 5, 'Prof. Ada Lovelace', 'Lab B', 'MWF', '09:00:00', '10:00:00'),
(1, 6, 'Dr. Grace Hopper', 'Room 305', 'TTH', '10:30:00', '12:00:00'),
(1, 7, 'Dr. Carl Jung', 'Room 202', 'TTH', '13:00:00', '14:30:00');

-- --------------------------------------------------------
-- 6. SCHOLARSHIPS
-- --------------------------------------------------------
TRUNCATE TABLE `scholarships`;
INSERT INTO `scholarships` (`id`, `name`, `discount_type`, `discount_value`, `description`, `requirements`, `is_active`) VALUES
(1, 'Academic Excellence', 'percentage', 100.00, 'Full tuition discount for valedictorians.', '1. Certificate of Rank\n2. Good Moral', 1),
(2, 'Athletic Scholar', 'percentage', 50.00, 'Half tuition discount for varsity players.', '1. Coach Endorsement\n2. Tryout Results', 1),
(3, 'Financial Aid', 'fixed', 5000.00, 'Fixed PHP 5,000 discount per semester.', '1. ITR of Parents\n2. Barangay Indigency', 1);

-- --------------------------------------------------------
-- 7. FEE TEMPLATES
-- --------------------------------------------------------
TRUNCATE TABLE `fee_templates`;
INSERT INTO `fee_templates` (`id`, `name`, `academic_level`, `grade_level`, `strand`, `tuition_fee`, `miscellaneous_fee`, `registration_fee`, `laboratory_fee`, `other_fees`, `total_amount`) VALUES
(1, 'SHS Default (STEM)', 'Senior High School', 'Grade 11', 'STEM', 15000.00, 2000.00, 500.00, 1500.00, 1000.00, 20000.00),
(2, 'College BSIT 1st Year', 'College', '1st Year', NULL, 20000.00, 3000.00, 500.00, 3500.00, 1000.00, 28000.00);

SET FOREIGN_KEY_CHECKS = 1;
