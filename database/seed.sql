-- --------------------------------------------------------
-- ENROLLMENT MANAGEMENT SYSTEM SEED DATA
-- COMPREHENSIVE SAMPLE DATA FOR TESTING
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
-- 2. USERS
-- Password: password123
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
(4, 'FIL101', 'Komunikasyon at Pananaliksik', 3, 'SHS', 1),
(5, 'PE101', 'Physical Education and Health 1', 2, 'SHS', 1),
-- SHS STEM Specialized
(6, 'STEM101', 'Pre-Calculus', 3, 'SHS', 1),
(7, 'STEM102', 'Basic Calculus', 3, 'SHS', 1),
-- SHS ABM Specialized
(8, 'ABM101', 'Fundamentals of ABM 1', 3, 'SHS', 1),
(9, 'ABM102', 'Business Math', 3, 'SHS', 1),
-- SHS HUMSS Specialized
(10, 'HUMSS101', 'Creative Writing', 3, 'SHS', 1),
(11, 'HUMSS102', 'Disciplines and Ideas in Social Sciences', 3, 'SHS', 1),
-- SHS ICT Specialized
(12, 'ICT101', 'Computer Systems Servicing', 3, 'SHS', 1),
(13, 'ICT102', 'Programming (Java/Python)', 3, 'SHS', 1),

-- College GE Subjects
(14, 'GE101', 'Understanding the Self', 3, 'College', 1),
(15, 'GE102', 'Readings in Philippine History', 3, 'College', 1),
(16, 'GE103', 'The Contemporary World', 3, 'College', 1),
(17, 'GE104', 'Mathematics in the Modern World', 3, 'College', 1),
(18, 'PE1', 'Physical Fitness', 2, 'College', 1),
(19, 'NSTP1', 'National Service Training Program 1', 3, 'College', 1),

-- College BSIT Core
(20, 'CC101', 'Introduction to Computing', 3, 'College', 1),
(21, 'CC102', 'Computer Programming 1', 3, 'College', 1),
(22, 'IT101', 'IT Fundamentals', 3, 'College', 1),
(23, 'IT102', 'Platform Technologies', 3, 'College', 1),

-- College BSCS Core
(24, 'CS101', 'Discrete Mathematics', 3, 'College', 1),
(25, 'CS102', 'Data Structures and Algorithms', 3, 'College', 1),

-- College BSIS Core
(26, 'IS101', 'Fundamentals of Information Systems', 3, 'College', 1),
(27, 'IS102', 'Enterprise Architecture', 3, 'College', 1);

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
-- STEM Grade 11 First Sem
(1, 'Grade 11', 'First', 1), (1, 'Grade 11', 'First', 2), (1, 'Grade 11', 'First', 3), (1, 'Grade 11', 'First', 4), (1, 'Grade 11', 'First', 5), (1, 'Grade 11', 'First', 6), (1, 'Grade 11', 'First', 7),
-- ABM Grade 11 First Sem
(2, 'Grade 11', 'First', 1), (2, 'Grade 11', 'First', 2), (2, 'Grade 11', 'First', 3), (2, 'Grade 11', 'First', 4), (2, 'Grade 11', 'First', 5), (2, 'Grade 11', 'First', 8), (2, 'Grade 11', 'First', 9),
-- HUMSS Grade 11 First Sem
(3, 'Grade 11', 'First', 1), (3, 'Grade 11', 'First', 2), (3, 'Grade 11', 'First', 3), (3, 'Grade 11', 'First', 4), (3, 'Grade 11', 'First', 5), (3, 'Grade 11', 'First', 10), (3, 'Grade 11', 'First', 11),
-- ICT Grade 11 First Sem
(4, 'Grade 11', 'First', 1), (4, 'Grade 11', 'First', 2), (4, 'Grade 11', 'First', 3), (4, 'Grade 11', 'First', 4), (4, 'Grade 11', 'First', 5), (4, 'Grade 11', 'First', 12), (4, 'Grade 11', 'First', 13);

TRUNCATE TABLE `shs_sections`;
INSERT INTO `shs_sections` (`id`, `section_code`, `strand_id`, `grade_level`, `academic_year`, `schedule_type`, `capacity`, `status`) VALUES
(1, 'STEM-11A', 1, 'Grade 11', '2026-2027', 'Morning', 40, 1),
(2, 'STEM-11B', 1, 'Grade 11', '2026-2027', 'Afternoon', 40, 1),
(3, 'ABM-11A', 2, 'Grade 11', '2026-2027', 'Morning', 40, 1),
(4, 'HUMSS-11A', 3, 'Grade 11', '2026-2027', 'Morning', 40, 1),
(5, 'ICT-11A', 4, 'Grade 11', '2026-2027', 'Morning', 40, 1);

TRUNCATE TABLE `shs_section_subjects`;
INSERT INTO `shs_section_subjects` (`shs_section_id`, `subject_id`, `instructor`, `room`, `day`, `start_time`, `end_time`) VALUES
-- STEM-11A
(1, 1, 'Mr. Smith', 'Room 101', 'MWF', '07:30:00', '08:30:00'),
(1, 2, 'Ms. Johnson', 'Room 102', 'TTH', '08:30:00', '10:00:00'),
(1, 3, 'Dr. Brown', 'Lab 1', 'MWF', '09:00:00', '10:00:00'),
(1, 4, 'Mr. Cruz', 'Room 103', 'TTH', '10:30:00', '12:00:00'),
(1, 5, 'Coach Leo', 'Gym', 'MWF', '10:30:00', '11:30:00'),
(1, 6, 'Engr. Dalisay', 'Room 104', 'TTH', '13:00:00', '14:30:00'),
(1, 7, 'Engr. Dalisay', 'Room 104', 'MWF', '13:00:00', '14:00:00'),

-- ABM-11A
(3, 1, 'Mr. Smith', 'Room 201', 'TTH', '07:30:00', '09:00:00'),
(3, 2, 'Ms. Johnson', 'Room 202', 'MWF', '08:30:00', '09:30:00'),
(3, 3, 'Dr. Brown', 'Lab 2', 'TTH', '09:30:00', '11:00:00'),
(3, 4, 'Mr. Cruz', 'Room 203', 'MWF', '10:30:00', '11:30:00'),
(3, 5, 'Coach Leo', 'Gym', 'TTH', '11:00:00', '12:00:00'),
(3, 8, 'Ms. Santos', 'Room 204', 'MWF', '13:00:00', '14:00:00'),
(3, 9, 'Ms. Santos', 'Room 204', 'TTH', '13:00:00', '14:30:00'),

-- HUMSS-11A
(4, 1, 'Mr. Smith', 'Room 301', 'MWF', '07:30:00', '08:30:00'),
(4, 2, 'Ms. Johnson', 'Room 302', 'TTH', '08:30:00', '10:00:00'),
(4, 3, 'Dr. Brown', 'Lab 1', 'MWF', '09:00:00', '10:00:00'),
(4, 4, 'Mr. Cruz', 'Room 303', 'TTH', '10:30:00', '12:00:00'),
(4, 5, 'Coach Leo', 'Gym', 'MWF', '10:30:00', '11:30:00'),
(4, 10, 'Ms. Poe', 'Room 304', 'MWF', '13:00:00', '14:00:00'),
(4, 11, 'Ms. Poe', 'Room 304', 'TTH', '13:00:00', '14:30:00'),

-- ICT-11A
(5, 1, 'Mr. Smith', 'Room 401', 'MWF', '07:30:00', '08:30:00'),
(5, 2, 'Ms. Johnson', 'Room 402', 'TTH', '08:30:00', '10:00:00'),
(5, 3, 'Dr. Brown', 'Lab 1', 'MWF', '09:00:00', '10:00:00'),
(5, 4, 'Mr. Cruz', 'Room 403', 'TTH', '10:30:00', '12:00:00'),
(5, 5, 'Coach Leo', 'Gym', 'MWF', '10:30:00', '11:30:00'),
(5, 12, 'Mr. Gates', 'ComLab 1', 'MWF', '13:00:00', '14:30:00'),
(5, 13, 'Ms. Lovelace', 'ComLab 2', 'TTH', '13:00:00', '14:30:00');


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
(2, 2, 'BSCS Default Curriculum', '2026', '2026-2027', 'active'),
(3, 3, 'BSIS Default Curriculum', '2026', '2026-2027', 'active');

TRUNCATE TABLE `college_curriculum_subjects`;
INSERT INTO `college_curriculum_subjects` (`curriculum_id`, `subject_id`, `year_level`, `semester`, `display_order`) VALUES
-- BSIT 1st Year First Sem
(1, 14, '1st Year', 'First', 1), (1, 15, '1st Year', 'First', 2), (1, 18, '1st Year', 'First', 3), (1, 19, '1st Year', 'First', 4), (1, 20, '1st Year', 'First', 5), (1, 21, '1st Year', 'First', 6), (1, 22, '1st Year', 'First', 7),
-- BSCS 1st Year First Sem
(2, 14, '1st Year', 'First', 1), (2, 15, '1st Year', 'First', 2), (2, 18, '1st Year', 'First', 3), (2, 19, '1st Year', 'First', 4), (2, 20, '1st Year', 'First', 5), (2, 21, '1st Year', 'First', 6), (2, 24, '1st Year', 'First', 7),
-- BSIS 1st Year First Sem
(3, 14, '1st Year', 'First', 1), (3, 15, '1st Year', 'First', 2), (3, 18, '1st Year', 'First', 3), (3, 19, '1st Year', 'First', 4), (3, 20, '1st Year', 'First', 5), (3, 21, '1st Year', 'First', 6), (3, 26, '1st Year', 'First', 7);

TRUNCATE TABLE `college_sections`;
INSERT INTO `college_sections` (`id`, `section_code`, `program_id`, `curriculum_id`, `academic_year`, `year_level`, `semester`, `schedule_type`, `capacity`, `status`) VALUES
(1, 'BSIT-1A', 1, 1, '2026-2027', '1st Year', 'First', 'Morning', 40, 1),
(2, 'BSIT-1B', 1, 1, '2026-2027', '1st Year', 'First', 'Afternoon', 40, 1),
(3, 'BSCS-1A', 2, 2, '2026-2027', '1st Year', 'First', 'Morning', 40, 1),
(4, 'BSIS-1A', 3, 3, '2026-2027', '1st Year', 'First', 'Morning', 40, 1);

TRUNCATE TABLE `college_section_subjects`;
INSERT INTO `college_section_subjects` (`college_section_id`, `subject_id`, `instructor`, `room`, `day`, `start_time`, `end_time`) VALUES
-- BSIT-1A
(1, 14, 'Prof. A', 'Room C1', 'MWF', '08:00:00', '09:00:00'),
(1, 15, 'Prof. B', 'Room C2', 'TTH', '08:30:00', '10:00:00'),
(1, 18, 'Coach C', 'Gym', 'MWF', '09:30:00', '10:30:00'),
(1, 19, 'Sir D', 'Field', 'SAT', '08:00:00', '11:00:00'),
(1, 20, 'Prof. E', 'Lab 1', 'MWF', '11:00:00', '12:00:00'),
(1, 21, 'Prof. F', 'Lab 2', 'TTH', '10:30:00', '12:00:00'),
(1, 22, 'Prof. G', 'Lab 3', 'MWF', '13:00:00', '14:00:00'),

-- BSCS-1A
(3, 14, 'Prof. A', 'Room C1', 'TTH', '07:00:00', '08:30:00'),
(3, 15, 'Prof. B', 'Room C2', 'MWF', '09:00:00', '10:00:00'),
(3, 18, 'Coach C', 'Gym', 'TTH', '09:30:00', '11:00:00'),
(3, 19, 'Sir D', 'Field', 'SAT', '13:00:00', '16:00:00'),
(3, 20, 'Prof. E', 'Lab 1', 'TTH', '11:30:00', '13:00:00'),
(3, 21, 'Prof. F', 'Lab 2', 'MWF', '10:30:00', '12:30:00'),
(3, 24, 'Prof. H', 'Room C3', 'MWF', '13:00:00', '14:00:00'),

-- BSIS-1A
(4, 14, 'Prof. A', 'Room C1', 'MWF', '10:00:00', '11:00:00'),
(4, 15, 'Prof. B', 'Room C2', 'TTH', '10:30:00', '12:00:00'),
(4, 18, 'Coach C', 'Gym', 'MWF', '08:00:00', '09:00:00'),
(4, 19, 'Sir D', 'Field', 'SUN', '08:00:00', '11:00:00'),
(4, 20, 'Prof. E', 'Lab 1', 'TTH', '13:30:00', '15:00:00'),
(4, 21, 'Prof. F', 'Lab 2', 'MWF', '13:00:00', '14:30:00'),
(4, 26, 'Prof. I', 'Room C4', 'MWF', '15:00:00', '16:00:00');


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
(1, 'SHS STEM', 'Senior High School', 'Grade 11', 'STEM', 15000.00, 2000.00, 500.00, 1500.00, 1000.00, 20000.00),
(2, 'SHS ABM', 'Senior High School', 'Grade 11', 'ABM', 14000.00, 2000.00, 500.00, 1000.00, 1000.00, 18500.00),
(3, 'SHS HUMSS', 'Senior High School', 'Grade 11', 'HUMSS', 14000.00, 2000.00, 500.00, 500.00, 1000.00, 18000.00),
(4, 'SHS ICT', 'Senior High School', 'Grade 11', 'ICT', 15000.00, 2000.00, 500.00, 2500.00, 1000.00, 21000.00),
(5, 'College BSIT', 'College', '1st Year', NULL, 20000.00, 3000.00, 500.00, 3500.00, 1000.00, 28000.00),
(6, 'College BSCS', 'College', '1st Year', NULL, 20000.00, 3000.00, 500.00, 3500.00, 1000.00, 28000.00),
(7, 'College BSIS', 'College', '1st Year', NULL, 19000.00, 3000.00, 500.00, 2500.00, 1000.00, 26000.00);

SET FOREIGN_KEY_CHECKS = 1;
