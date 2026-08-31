-- ============================================================================
-- TRIPLE T UNIVERSITY (TTU) ENROLLMENT & LMS SYSTEM - COMPREHENSIVE SEED DATA
-- Database: sia
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ----------------------------------------------------------------------------
-- 1. SYSTEM SETTINGS
-- ----------------------------------------------------------------------------
DELETE FROM `system_settings`;
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'active_school_year', '2026-2027', NOW(), NOW()),
(2, 'enrollment_status', 'open', NOW(), NOW()),
(3, 'college_cost_per_unit', '500.00', NOW(), NOW()),
(4, 'system_name', 'Triple T University Enrollment & LMS', NOW(), NOW()),
(5, 'contact_email', 'admissions@ttu.edu.ph', NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 2. PUBLIC & INSTITUTIONAL ANNOUNCEMENTS
-- ----------------------------------------------------------------------------
DELETE FROM `announcements`;
INSERT INTO `announcements` (`id`, `badge_label`, `badge_color`, `title`, `content`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admissions', 'primary', 'Academic Year 2026-2027 Enrollment is Now Open', 'Triple T University is officially accepting freshman, transferee, and senior high school applications for the upcoming academic year. Complete your online registration and submit digital requirements today.', 1, NOW(), NOW()),
(2, 'Scholarship', 'success', 'Academic & Athletic Scholarship Grants Open', 'Qualified valedictorians, salutatorians, and varsity athletes are invited to submit their scholarship applications through the student portal before September 30, 2026.', 1, NOW(), NOW()),
(3, 'Registrar', 'info', 'Curriculum Updates & Section Schedules Available', 'New curriculum roadmaps and timetable offerings for College of Computer Studies and Senior High School strands have been published.', 1, NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 3. CORE USERS & INSTITUTIONAL ACCOUNTS
-- Default Password for Staff: admin123 ($2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK)
-- Default Password for Students/Faculty: password123 ($2y$10$WUOWe6NmQUF.xkOM3Bn52eVnrS55voa4/InCsvjZgqbENbSRRD9Eu)
-- ----------------------------------------------------------------------------
DELETE FROM `users`;
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `ttu_email`, `password`, `student_number`, `role`, `department`, `permissions`, `college_curriculum_id`, `email_verified`, `verification_code`, `verification_code_expires_at`, `reset_token`, `reset_token_expires_at`, `force_password_reset`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'System', 'Superadmin', 'admin@ttu.edu.ph', 'admin@ttu.edu.ph', '$2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK', 'ADM-0001', 'superadmin', 'System Administration', '[\"*\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(2, 'Eleanor', 'Vance', 'admissions@ttu.edu.ph', 'admissions@ttu.edu.ph', '$2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK', 'ADM-0002', 'admissions', 'Admissions Office', '[\"manage_admissions\",\"view_applications\",\"process_applications\",\"manage_documents\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(3, 'Marcus', 'Aurelius', 'registrar@ttu.edu.ph', 'registrar@ttu.edu.ph', '$2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK', 'ADM-0003', 'admin', 'Registrar Office', '[\"manage_registrar\",\"manage_curriculum\",\"manage_subjects\",\"manage_students\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(4, 'Clara', 'Oswald', 'cashier@ttu.edu.ph', 'cashier@ttu.edu.ph', '$2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK', 'ADM-0004', 'cashier', 'Finance & Accounting', '[\"manage_finance\",\"process_payments\",\"verify_payments\",\"issue_receipts\",\"manage_fees\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(5, 'Dr. Sarah', 'Palmer', 'clinic@ttu.edu.ph', 'clinic@ttu.edu.ph', '$2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK', 'ADM-0005', 'clinic', 'University Health Services', '[\"manage_clinic\",\"verify_medical\",\"update_health_records\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(6, 'Theodore', 'Nott', 'scheduler@ttu.edu.ph', 'scheduler@ttu.edu.ph', '$2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK', 'ADM-0006', 'scheduler', 'Academic Scheduling', '[\"manage_sections\",\"manage_schedules\",\"assign_rooms\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(7, 'Gwendolyn', 'Stacy', 'scholarship@ttu.edu.ph', 'scholarship@ttu.edu.ph', '$2y$10$2TckHB27daVTUY2s77cGLOP/D85YVJEUACV6npajBU6fLmRFdAArK', 'ADM-0007', 'scholarship', 'Student Affairs & Grants', '[\"manage_scholarships\",\"review_scholarships\",\"award_grants\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(8, 'Alan', 'Turing', 'alan.turing@ttu.edu.ph', 'alan.turing@ttu.edu.ph', '$2y$10$WUOWe6NmQUF.xkOM3Bn52eVnrS55voa4/InCsvjZgqbENbSRRD9Eu', 'FAC-2026-001', 'faculty', 'Computer Science Dept', '[\"lms_faculty\",\"manage_courses\",\"grade_assignments\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(9, 'Ada', 'Lovelace', 'ada.lovelace@ttu.edu.ph', 'ada.lovelace@ttu.edu.ph', '$2y$10$WUOWe6NmQUF.xkOM3Bn52eVnrS55voa4/InCsvjZgqbENbSRRD9Eu', 'FAC-2026-002', 'faculty', 'Information Technology Dept', '[\"lms_faculty\",\"manage_courses\",\"grade_assignments\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(10, 'Dr. Grace', 'Hopper', 'grace.hopper@ttu.edu.ph', 'grace.hopper@ttu.edu.ph', '$2y$10$WUOWe6NmQUF.xkOM3Bn52eVnrS55voa4/InCsvjZgqbENbSRRD9Eu', 'FAC-2026-003', 'faculty', 'Computer Science Dept', '[\"lms_faculty\",\"manage_courses\",\"grade_assignments\"]', NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(11, 'John', 'Doe', 'john.doe@example.com', 'john.doe@ttu.edu.ph', '$2y$10$WUOWe6NmQUF.xkOM3Bn52eVnrS55voa4/InCsvjZgqbENbSRRD9Eu', '2026-000001', 'applicant', 'None', NULL, 1, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(12, 'Mary', 'Smith', 'mary.smith@example.com', 'mary.smith@ttu.edu.ph', '$2y$10$WUOWe6NmQUF.xkOM3Bn52eVnrS55voa4/InCsvjZgqbENbSRRD9Eu', '2026-000002', 'applicant', 'None', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW()),
(13, 'Jane', 'Applicant', 'jane.applicant@example.com', NULL, '$2y$10$WUOWe6NmQUF.xkOM3Bn52eVnrS55voa4/InCsvjZgqbENbSRRD9Eu', NULL, 'applicant', 'None', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, 1, NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 4. ACADEMIC PROGRAMS & SHS STRANDS
-- ----------------------------------------------------------------------------
DELETE FROM `college_programs`;
INSERT INTO `college_programs` (`id`, `code`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 'Prepares students in network administration, web systems, database design, and cybersecurity.', 1, NOW(), NOW()),
(2, 'BSCS', 'Bachelor of Science in Computer Science', 'Focuses on computing theory, algorithm design, software engineering, and artificial intelligence.', 1, NOW(), NOW()),
(3, 'BSIS', 'Bachelor of Science in Information Systems', 'Integrates business management with enterprise computing architectures and business analytics.', 1, NOW(), NOW()),
(4, 'BSHM', 'Bachelor of Science in Hospitality Management', 'Professional training in hotel operations, culinary arts, and tourism management.', 1, NOW(), NOW());

DELETE FROM `shs_strands`;
INSERT INTO `shs_strands` (`id`, `code`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'STEM', 'Science, Technology, Engineering, and Mathematics', 'Academic track for engineering, pure sciences, and computing specializations.', 1, NOW(), NOW()),
(2, 'ABM', 'Accountancy, Business, and Management', 'Academic track for entrepreneurship, financial management, and business administration.', 1, NOW(), NOW()),
(3, 'HUMSS', 'Humanities and Social Sciences', 'Academic track for communication arts, law, journalism, and social sciences.', 1, NOW(), NOW()),
(4, 'TVL-ICT', 'Technical-Vocational-Livelihood (ICT Strand)', 'Vocational track in computer programming, animation, and technical drafting.', 1, NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 5. MASTER SUBJECTS CATALOG
-- ----------------------------------------------------------------------------
DELETE FROM `subjects`;
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `units`, `subject_type`, `description`, `education_level`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CC101', 'Introduction to Computing', 3, 'Lecture', 'Fundamental computing principles, binary logic, and digital literacy.', 'College', 1, NOW(), NOW()),
(2, 'CC102', 'Fundamentals of Programming', 3, 'Lecture', 'Procedural problem-solving and structured programming algorithms.', 'College', 1, NOW(), NOW()),
(3, 'MATH101', 'Calculus 1', 3, 'Lecture', 'Limits, derivatives, integrals, and analytical geometry.', 'College', 1, NOW(), NOW()),
(4, 'ENG101', 'Purposive Communication', 3, 'Lecture', 'Writing, speaking, and presenting to different audiences in multimodal contexts.', 'College', 1, NOW(), NOW()),
(5, 'IT101', 'Web Systems and Technologies', 3, 'Lecture', 'Client-side web architecture, HTML5, CSS3 tokens, and JavaScript engines.', 'College', 1, NOW(), NOW()),
(6, 'CS101', 'Data Structures and Algorithms', 3, 'Lecture', 'Stacks, queues, linked lists, trees, graphs, and Big-O efficiency analysis.', 'College', 1, NOW(), NOW()),
(7, 'DB101', 'Database Management Systems', 3, 'Lecture', 'Relational database modeling, ER diagrams, normalization, and SQL optimization.', 'College', 1, NOW(), NOW()),
(8, 'SHS-STEM101', 'Pre-Calculus (STEM)', 4, 'Lecture', 'Trigonometric functions, conic sections, and mathematical induction for Grade 11.', 'SHS', 1, NOW(), NOW()),
(9, 'SHS-GENBIO1', 'General Biology 1', 4, 'Lecture', 'Cellular biology, genetics, and molecular biochemistry for STEM students.', 'SHS', 1, NOW(), NOW()),
(10, 'SHS-ENG01', 'Oral Communication in Context', 3, 'Lecture', 'Development of listening and speaking skills in varied communication situations.', 'SHS', 1, NOW(), NOW()),
(11, 'SHS-PR101', 'Empowerment Technologies (ICT)', 3, 'Lecture', 'ICT fundamentals, digital content creation, and multimedia design for TVL.', 'SHS', 1, NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 6. VERSIONED CURRICULA & SUBJECT MAPPINGS
-- ----------------------------------------------------------------------------
DELETE FROM `college_curricula`;
INSERT INTO `college_curricula` (`id`, `program_id`, `curriculum_name`, `version`, `effective_academic_year`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'BSIT 2026 Standard Curriculum', '1.0', '2026-2027', 'Official curriculum blueprint for BS Information Technology.', 'active', NOW(), NOW()),
(2, 2, 'BSCS 2026 Standard Curriculum', '1.0', '2026-2027', 'Official curriculum blueprint for BS Computer Science.', 'active', NOW(), NOW());

DELETE FROM `college_curriculum_subjects`;
INSERT INTO `college_curriculum_subjects` (`id`, `curriculum_id`, `subject_id`, `year_level`, `semester`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '1st Year', 'First', 1, NOW(), NOW()),
(2, 1, 2, '1st Year', 'First', 2, NOW(), NOW()),
(3, 1, 4, '1st Year', 'First', 3, NOW(), NOW()),
(4, 1, 5, '1st Year', 'Second', 1, NOW(), NOW()),
(5, 1, 7, '1st Year', 'Second', 2, NOW(), NOW()),
(6, 2, 1, '1st Year', 'First', 1, NOW(), NOW()),
(7, 2, 2, '1st Year', 'First', 2, NOW(), NOW()),
(8, 2, 3, '1st Year', 'First', 3, NOW(), NOW()),
(9, 2, 6, '1st Year', 'Second', 1, NOW(), NOW());

DELETE FROM `shs_curricula`;
INSERT INTO `shs_curricula` (`id`, `strand_id`, `curriculum_name`, `version`, `effective_academic_year`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'STEM Grade 11-12 Curriculum', '1.0', '2026-2027', 'DepEd aligned STEM curriculum matrix.', 'active', NOW(), NOW()),
(2, 4, 'TVL-ICT Grade 11-12 Curriculum', '1.0', '2026-2027', 'Vocational ICT track curriculum matrix.', 'active', NOW(), NOW());

DELETE FROM `shs_curriculum_subjects`;
INSERT INTO `shs_curriculum_subjects` (`id`, `curriculum_id`, `subject_id`, `grade_level`, `semester`, `created_at`, `updated_at`) VALUES
(1, 1, 8, 'Grade 11', 'First', NOW(), NOW()),
(2, 1, 9, 'Grade 11', 'First', NOW(), NOW()),
(3, 1, 10, 'Grade 11', 'First', NOW(), NOW()),
(4, 2, 10, 'Grade 11', 'First', NOW(), NOW()),
(5, 2, 11, 'Grade 11', 'First', NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 7. CLASS SECTIONS & SCHEDULED SUBJECT OFFERINGS
-- ----------------------------------------------------------------------------
DELETE FROM `college_sections`;
INSERT INTO `college_sections` (`id`, `section_code`, `program_id`, `curriculum_id`, `academic_year`, `year_level`, `semester`, `capacity`, `schedule_type`, `adviser`, `status`, `created_at`, `updated_at`) VALUES
(1, 'BSIT 1-A', 1, 1, '2026-2027', '1st Year', 'First', 40, 'Morning', 'Ada Lovelace', 1, NOW(), NOW()),
(2, 'BSCS 1-A', 2, 2, '2026-2027', '1st Year', 'First', 40, 'Morning', 'Alan Turing', 1, NOW(), NOW());

DELETE FROM `college_section_subjects`;
INSERT INTO `college_section_subjects` (`id`, `college_section_id`, `subject_id`, `capacity`, `day`, `start_time`, `end_time`, `room`, `instructor`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 40, 'MWF', '08:00:00', '09:00:00', 'Lab 101', 'Ada Lovelace', NOW(), NOW()),
(2, 1, 2, 40, 'MWF', '09:00:00', '10:00:00', 'Lab 102', 'Alan Turing', NOW(), NOW()),
(3, 1, 4, 40, 'TTH', '10:30:00', '12:00:00', 'Room 305', 'Dr. Grace Hopper', NOW(), NOW()),
(4, 2, 1, 40, 'MWF', '08:00:00', '09:00:00', 'Lab 101', 'Alan Turing', NOW(), NOW()),
(5, 2, 2, 40, 'MWF', '09:00:00', '10:00:00', 'Lab 102', 'Alan Turing', NOW(), NOW()),
(6, 2, 3, 40, 'TTH', '13:00:00', '14:30:00', 'Room 401', 'Dr. Grace Hopper', NOW(), NOW());

DELETE FROM `shs_sections`;
INSERT INTO `shs_sections` (`id`, `section_code`, `strand_id`, `curriculum_id`, `grade_level`, `academic_year`, `capacity`, `schedule_type`, `adviser`, `status`, `created_at`, `updated_at`) VALUES
(1, 'STEM 11-A', 1, 1, 'Grade 11', '2026-2027', 40, 'Morning', 'Dr. Grace Hopper', 1, NOW(), NOW()),
(2, 'TVL 11-A', 4, 2, 'Grade 11', '2026-2027', 40, 'Morning', 'Ada Lovelace', 1, NOW(), NOW());

DELETE FROM `shs_section_subjects`;
INSERT INTO `shs_section_subjects` (`id`, `shs_section_id`, `subject_id`, `capacity`, `day`, `start_time`, `end_time`, `room`, `instructor`, `created_at`, `updated_at`) VALUES
(1, 1, 8, 40, 'MWF', '08:00:00', '09:30:00', 'SHS Room 1', 'Dr. Grace Hopper', NOW(), NOW()),
(2, 1, 9, 40, 'MWF', '09:30:00', '11:00:00', 'Bio Lab', 'Dr. Grace Hopper', NOW(), NOW()),
(3, 1, 10, 40, 'TTH', '08:00:00', '09:30:00', 'SHS Room 1', 'Ada Lovelace', NOW(), NOW()),
(4, 2, 10, 40, 'TTH', '08:00:00', '09:30:00', 'SHS Room 2', 'Ada Lovelace', NOW(), NOW()),
(5, 2, 11, 40, 'MWF', '10:00:00', '11:30:00', 'Comp Lab 3', 'Ada Lovelace', NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 8. FEE TEMPLATES & SCHOLARSHIPS
-- ----------------------------------------------------------------------------
DELETE FROM `fee_templates`;
INSERT INTO `fee_templates` (`id`, `name`, `academic_level`, `grade_level`, `strand`, `semester`, `is_per_unit`, `tuition_fee`, `miscellaneous_fee`, `registration_fee`, `laboratory_fee`, `other_fees`, `total_amount`, `created_at`, `updated_at`) VALUES
(1, 'College BSIT 1st Year (Rate per Unit)', 'College', '1st Year', 'BSIT', 'First', 1, 500.00, 2500.00, 500.00, 2000.00, 1000.00, 6000.00, NOW(), NOW()),
(2, 'College BSCS 1st Year (Rate per Unit)', 'College', '1st Year', 'BSCS', 'First', 1, 500.00, 2500.00, 500.00, 2000.00, 1000.00, 6000.00, NOW(), NOW()),
(3, 'SHS STEM Grade 11 Standard', 'Senior High School', 'Grade 11', 'STEM', 'First', 0, 12000.00, 2000.00, 500.00, 1500.00, 500.00, 16500.00, NOW(), NOW()),
(4, 'SHS TVL Grade 11 Standard', 'Senior High School', 'Grade 11', 'TVL-ICT', 'First', 0, 10000.00, 2000.00, 500.00, 2000.00, 500.00, 15000.00, NOW(), NOW());

DELETE FROM `scholarships`;
INSERT INTO `scholarships` (`id`, `name`, `code`, `category`, `provider`, `program_id`, `year_level`, `min_gwa`, `income_requirement`, `slots`, `tuition_coverage_type`, `tuition_coverage_value`, `misc_coverage_type`, `misc_coverage_value`, `stipend_amount`, `book_allowance`, `description`, `requirements`, `application_start`, `application_end`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Academic Excellence Full Scholarship', 'ACAD-FULL', 'School-Based', 'Triple T University Board', 1, '1st Year', 1.25, NULL, 50, 'percentage', 100.00, 'percentage', 100.00, 5000.00, 3000.00, '100% Tuition and Miscellaneous fee waiver for high-ranking honor graduates.', '1. Certified True Copy of Grades\n2. Certificate of Valedictorian/Salutatorian Rank\n3. Certificate of Good Moral Character', '2026-06-01', '2026-09-30', 'Active', NOW(), NOW()),
(2, 'Athletic Varsity Grant', 'ATH-50', 'School-Based', 'TTU Athletics Council', NULL, 'All', 2.50, NULL, 30, 'percentage', 50.00, 'percentage', 0.00, 2000.00, 0.00, '50% Tuition discount for university varsity players.', '1. Coach Recommendation Letter\n2. Medical Fitness Clearance\n3. Proof of Enrollment', '2026-06-01', '2026-09-30', 'Active', NOW(), NOW()),
(3, 'CHED Tulong Dunong Program', 'CHED-TDP', 'Government', 'Commission on Higher Education (CHED)', NULL, 'All', 2.75, 300000.00, 100, 'fixed', 7500.00, 'fixed', 0.00, 0.00, 0.00, 'Government financial grant providing PHP 7,500 assistance per semester.', '1. Certificate of Indigency\n2. Parents ITR or Certificate of Low Income\n3. Grade 12 Report Card', '2026-06-01', '2026-10-31', 'Active', NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 9. SAMPLE APPLICATIONS & STUDENT RECORDS
-- ----------------------------------------------------------------------------
DELETE FROM `applications`;
INSERT INTO `applications` (`id`, `user_id`, `reference_number`, `academic_level`, `grade_level`, `school_year`, `semester`, `student_type`, `strand`, `nstp`, `section_id`, `college_curriculum_id`, `status`, `document_submission_method`, `admin_feedback`, `internal_notes`, `contact_number`, `telephone_number`, `birth_date`, `gender`, `civil_status`, `nationality`, `religion`, `place_of_birth`, `address_house_number`, `address_street`, `address_barangay`, `address_city`, `address_province`, `address_zip`, `address`, `guardian_name`, `guardian_relationship`, `guardian_contact`, `previous_school`, `previous_school_year`, `previous_school_type`, `lrn`, `emergency_name`, `emergency_relationship`, `emergency_contact`, `created_at`, `updated_at`) VALUES
(1, 11, 'APP-2026-000001', 'College', '1st Year', '2026-2027', 'First', 'Regular', 'BSIT', 'CWTS', 1, 1, 'enrolled', 'online', 'Application verified and enrollment confirmed.', 'Honors graduate from Manila Science HS.', '09171234567', '0281234567', '2007-05-15', 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Manila', '123', 'Mabini Street', 'Barangay 659', 'Manila', 'Metro Manila', '1000', '123 Mabini Street, Barangay 659, Manila, Metro Manila', 'Robert Doe', 'Father', '09179876543', 'Manila Science High School', '2025-2026', 'Public', '123456789012', 'Robert Doe', 'Father', '09179876543', NOW(), NOW()),
(2, 12, 'APP-2026-000002', 'Senior High School', 'Grade 11', '2026-2027', 'First', 'Regular', 'STEM', NULL, 1, NULL, 'enrolled', 'online', 'Enrolled in STEM 11-A section block.', 'Transferee with complete Form 137.', '09181234567', '0289876543', '2009-08-20', 'Female', 'Single', 'Filipino', 'Christian', 'Quezon City', '456', 'Katipunan Avenue', 'Loyola Heights', 'Quezon City', 'Metro Manila', '1108', '456 Katipunan Avenue, Loyola Heights, Quezon City', 'Elizabeth Smith', 'Mother', '09187654321', 'Quezon City High School', '2025-2026', 'Public', '987654321098', 'Elizabeth Smith', 'Mother', '09187654321', NOW(), NOW()),
(3, 13, 'APP-2026-000003', 'College', '1st Year', '2026-2027', 'First', 'Regular', 'BSCS', 'ROTC', NULL, 2, 'pending', 'online', NULL, NULL, '09191234567', NULL, '2008-01-10', 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Pasig City', '789', 'Ortigas Avenue', 'San Antonio', 'Pasig City', 'Metro Manila', '1600', '789 Ortigas Avenue, San Antonio, Pasig City', 'James Applicant', 'Father', '09198765432', 'Pasig City Science High School', '2025-2026', 'Public', '555666777888', 'James Applicant', 'Father', '09198765432', NOW(), NOW());

DELETE FROM `health_records`;
INSERT INTO `health_records` (`id`, `user_id`, `application_id`, `height`, `weight`, `blood_type`, `has_allergies`, `has_asthma`, `has_diabetes`, `has_hypertension`, `has_heart_disease`, `has_physical_disability`, `has_existing_condition`, `has_previous_surgery`, `has_maintenance_medication`, `has_hospitalized`, `medical_conditions`, `allergies_details`, `current_medications`, `other_notes`, `emergency_name`, `emergency_relationship`, `emergency_contact`, `status`, `admin_remarks`, `created_at`, `updated_at`) VALUES
(1, 11, 1, '175 cm', '68 kg', 'O+', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'None', 'None', 'None', 'Fit for all university and physical education activities.', 'Robert Doe', 'Father', '09179876543', 'verified', 'Medically cleared for academic term.', NOW(), NOW()),
(2, 12, 2, '162 cm', '52 kg', 'A+', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'Mild allergic rhinitis', 'Dust and pollen', 'Antihistamine as needed', 'Carries personal inhaler.', 'Elizabeth Smith', 'Mother', '09187654321', 'verified', 'Cleared with allergy note on file.', NOW(), NOW());

DELETE FROM `application_documents`;
INSERT INTO `application_documents` (`id`, `application_id`, `document_name`, `file_path`, `status`, `feedback`, `created_at`, `updated_at`) VALUES
(1, 1, 'PSA Birth Certificate', 'sample_psa_11.pdf', 'verified', 'Original PSA verified.', NOW(), NOW()),
(2, 1, 'Form 138 (Report Card)', 'sample_f138_11.pdf', 'verified', 'GWA 94.5% verified.', NOW(), NOW()),
(3, 1, 'Certificate of Good Moral Character', 'sample_moral_11.pdf', 'verified', 'Verified.', NOW(), NOW()),
(4, 1, '2x2 ID Picture', 'sample_photo_11.jpg', 'verified', 'Compliant university ID photo.', NOW(), NOW()),
(5, 2, 'PSA Birth Certificate', 'sample_psa_12.pdf', 'verified', 'Verified.', NOW(), NOW()),
(6, 2, 'Form 138 (Report Card)', 'sample_f138_12.pdf', 'verified', 'Verified.', NOW(), NOW());

DELETE FROM `college_enrollments`;
INSERT INTO `college_enrollments` (`id`, `application_id`, `subject_id`, `college_section_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NOW(), NOW()),
(2, 1, 2, 1, NOW(), NOW()),
(3, 1, 4, 1, NOW(), NOW());

DELETE FROM `shs_enrollments`;
INSERT INTO `shs_enrollments` (`id`, `application_id`, `subject_id`, `shs_section_id`, `created_at`, `updated_at`) VALUES
(1, 2, 8, 1, NOW(), NOW()),
(2, 2, 9, 1, NOW(), NOW()),
(3, 2, 10, 1, NOW(), NOW());

DELETE FROM `student_assessments`;
INSERT INTO `student_assessments` (`id`, `user_id`, `application_id`, `fee_template_id`, `scholarship_id`, `tuition_fee`, `miscellaneous_fee`, `registration_fee`, `laboratory_fee`, `other_fees`, `total_amount`, `discount_amount`, `net_amount`, `total_paid`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 11, 1, 1, 1, 4500.00, 2500.00, 500.00, 2000.00, 1000.00, 10500.00, 10500.00, 0.00, 0.00, 'paid', NOW(), NOW()),
(2, 12, 2, 3, NULL, 12000.00, 2000.00, 500.00, 1500.00, 500.00, 16500.00, 0.00, 16500.00, 5000.00, 'partial', NOW(), NOW());

DELETE FROM `payment_records`;
INSERT INTO `payment_records` (`id`, `assessment_id`, `user_id`, `cashier_id`, `amount`, `payment_date`, `payment_method`, `receipt_number`, `reference_number`, `proof_image`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 2, 12, 4, 5000.00, CURDATE(), 'GCash', 'REC-20260825-0001', 'GCASH-987123654', 'proof_mary_downpayment.jpg', 'verified', 'Initial downpayment verified for enrollment finalization.', NOW(), NOW());

DELETE FROM `scholarship_recipients`;
INSERT INTO `scholarship_recipients` (`id`, `user_id`, `scholarship_id`, `academic_year_id`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(1, 11, 1, '2026-2027', 'First', 'Active', NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 10. SAMPLE LMS COURSES, MODULES, ASSIGNMENTS & QUIZZES
-- ----------------------------------------------------------------------------
DELETE FROM `lms_announcements`;
DELETE FROM `lms_attendance_records`;
DELETE FROM `lms_attendance_sessions`;
DELETE FROM `lms_quiz_answers`;
DELETE FROM `lms_quiz_attempts`;
DELETE FROM `lms_question_choices`;
DELETE FROM `lms_questions`;
DELETE FROM `lms_quizzes`;
DELETE FROM `lms_submissions`;
DELETE FROM `lms_assignments`;
DELETE FROM `lms_materials`;
DELETE FROM `lms_modules`;
DELETE FROM `lms_courses`;

INSERT INTO `lms_courses` (`id`, `academic_level`, `academic_section_id`, `subject_id`, `faculty_user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'College', 1, 1, 9, 'active', NOW(), NOW()),
(2, 'College', 1, 2, 8, 'active', NOW(), NOW()),
(3, 'College', 1, 4, 10, 'active', NOW(), NOW()),
(4, 'SHS', 1, 8, 10, 'active', NOW(), NOW());

INSERT INTO `lms_modules` (`id`, `lms_course_id`, `title`, `description`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Module 1: Architecture of Modern Computers', 'Binary representations, CPU microarchitecture, and memory hierarchies.', 1, 'published', NOW(), NOW()),
(2, 1, 'Module 2: Operating Systems & Virtualization', 'Process scheduling, file systems, and hypervisor concepts.', 2, 'published', NOW(), NOW()),
(3, 2, 'Module 1: Structured Control Flow & Logic', 'Conditionals, nested iterations, and functional decomposition.', 1, 'published', NOW(), NOW());

INSERT INTO `lms_materials` (`id`, `lms_module_id`, `file_name`, `file_path`, `mime_type`, `file_size`, `created_at`) VALUES
(1, 1, 'Lecture 1: Digital Logic & Binary Systems (PDF)', 'materials/cc101_lec1.pdf', 'application/pdf', 1048576, NOW()),
(2, 1, 'Syllabus & Course Outline 2026-2027', 'materials/cc101_syllabus.pdf', 'application/pdf', 524288, NOW()),
(3, 3, 'Lecture 1: Algorithmic Thinking in C / Python', 'materials/cc102_lec1.pdf', 'application/pdf', 2097152, NOW());

INSERT INTO `lms_assignments` (`id`, `lms_course_id`, `lms_module_id`, `title`, `description`, `due_date`, `max_score`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Assignment 1: Number Systems Conversion Exercise', 'Convert the provided decimal numbers to binary, octal, and hexadecimal representation. Submit your PDF solution sheet.', DATE_ADD(NOW(), INTERVAL 7 DAY), 100, 'published', NOW(), NOW()),
(2, 2, 3, 'Lab Exercise 1: Conditional Flow Implementation', 'Implement the quadratic formula solver with input edge-case handling in standard C or PHP.', DATE_ADD(NOW(), INTERVAL 5 DAY), 50, 'published', NOW(), NOW());

INSERT INTO `lms_quizzes` (`id`, `lms_course_id`, `title`, `description`, `time_limit`, `max_attempts`, `passing_score`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Quiz 1: Computer Hardware & Logic Fundamentals', 'Evaluates knowledge in CPU architecture and logic gates.', 20, 1, 75.00, NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), 'published', NOW(), NOW());

INSERT INTO `lms_questions` (`id`, `lms_quiz_id`, `question_text`, `question_type`, `points`, `display_order`, `created_at`) VALUES
(1, 1, 'What is the primary function of the Arithmetic Logic Unit (ALU) in a CPU?', 'multiple_choice', 5.00, 1, NOW()),
(2, 1, 'Which memory type is volatile and loses its contents when power is turned off?', 'multiple_choice', 5.00, 2, NOW()),
(3, 1, 'A gigabyte is equivalent to exactly 1,024 megabytes in binary prefix notation.', 'true_false', 5.00, 3, NOW());

INSERT INTO `lms_question_choices` (`id`, `lms_question_id`, `choice_text`, `is_correct`, `display_order`) VALUES
(1, 1, 'Performs arithmetic calculations and logical decisions', 1, 1),
(2, 1, 'Stores persistent files on solid-state media', 0, 2),
(3, 1, 'Manages cooling fan speeds across thermal zones', 0, 3),
(4, 2, 'Random Access Memory (RAM)', 1, 1),
(5, 2, 'Read-Only Memory (ROM)', 0, 2),
(6, 2, 'Solid State Flash Storage', 0, 3),
(7, 3, 'True', 1, 1),
(8, 3, 'False', 0, 2);

INSERT INTO `lms_attendance_sessions` (`id`, `lms_course_id`, `session_date`, `start_time`, `end_time`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, CURDATE(), '08:00:00', '10:00:00', 'Class Orientation & Syllabus Review', NOW(), NOW());

INSERT INTO `lms_attendance_records` (`id`, `lms_attendance_session_id`, `student_id`, `status`, `remarks`, `recorded_at`) VALUES
(1, 1, 11, 'present', 'Attended on time.', NOW());

INSERT INTO `lms_announcements` (`id`, `lms_course_id`, `author_user_id`, `title`, `content`, `status`, `published_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 'Welcome to CC101 Introduction to Computing!', 'Please download the course syllabus from Module 1 and review the lecture slides before our next lab meeting on Wednesday.', 'published', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

