-- --------------------------------------------------------
-- ENROLLMENT MANAGEMENT SYSTEM SCHEMA
-- GENERATED FROM CURRENT IMPLEMENTATION
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. CORE SYSTEM & USERS
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('applicant','admin','superadmin','admissions','scholarship','cashier') NOT NULL DEFAULT 'applicant',
  `department` varchar(100) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `student_number` varchar(50) DEFAULT NULL,
  `college_curriculum_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_student_number` (`student_number`),
  KEY `idx_users_role` (`role`),
  KEY `fk_users_college_curriculum` (`college_curriculum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'bi-circle',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `affected_record` varchar(255) DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_id` (`user_id`),
  CONSTRAINT `fk_activity_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `badge_label` varchar(100) NOT NULL,
  `badge_color` varchar(50) NOT NULL DEFAULT 'primary',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_announcements_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. ACADEMIC STRUCTURE (COLLEGE & SHS SHARED)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `units` int(11) NOT NULL DEFAULT 3,
  `subject_type` varchar(100) DEFAULT NULL,
  `education_level` enum('College','SHS','Both') NOT NULL DEFAULT 'College',
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. SENIOR HIGH SCHOOL (SHS) SPECIFIC
-- --------------------------------------------------------

DROP TABLE IF EXISTS `shs_strands`;
CREATE TABLE `shs_strands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_curriculum`;
CREATE TABLE `shs_curriculum` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `strand_id` int(10) unsigned NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `semester` enum('First','Second') DEFAULT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shs_curriculum` (`strand_id`,`grade_level`,`semester`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_strand_id` (`strand_id`),
  CONSTRAINT `shs_curriculum_ibfk_1` FOREIGN KEY (`strand_id`) REFERENCES `shs_strands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_curriculum_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_sections`;
CREATE TABLE `shs_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_code` varchar(100) NOT NULL,
  `strand_id` int(10) unsigned NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `schedule_type` enum('Morning','Afternoon') NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `adviser` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_code` (`section_code`),
  KEY `strand_id` (`strand_id`),
  CONSTRAINT `shs_sections_ibfk_1` FOREIGN KEY (`strand_id`) REFERENCES `shs_strands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_section_subjects`;
CREATE TABLE `shs_section_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shs_section_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `day` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `delivery_mode` enum('Face-to-Face','Online','Hybrid') NOT NULL DEFAULT 'Face-to-Face',
  `capacity` int(11) NOT NULL DEFAULT 40,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shs_section_id` (`shs_section_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `shs_section_subjects_ibfk_1` FOREIGN KEY (`shs_section_id`) REFERENCES `shs_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_section_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 4. COLLEGE SPECIFIC
-- --------------------------------------------------------

DROP TABLE IF EXISTS `college_programs`;
CREATE TABLE `college_programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `college_curricula`;
CREATE TABLE `college_curricula` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `curriculum_name` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL DEFAULT '1.0',
  `effective_academic_year` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','draft') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `college_curricula_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `college_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fix the cyclic dependency warning between users and college_curricula
ALTER TABLE `users` ADD CONSTRAINT `fk_users_college_curriculum` FOREIGN KEY (`college_curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE SET NULL;

DROP TABLE IF EXISTS `college_curriculum_subjects`;
CREATE TABLE `college_curriculum_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `year_level` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curriculum_subject` (`curriculum_id`,`year_level`,`semester`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `college_curriculum_subs_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_curriculum_subs_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `college_sections`;
CREATE TABLE `college_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_code` varchar(100) NOT NULL,
  `program_id` int(10) unsigned NOT NULL,
  `curriculum_id` int(10) unsigned DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `year_level` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `schedule_type` enum('Morning','Afternoon') NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `adviser` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_code` (`section_code`),
  KEY `program_id` (`program_id`),
  KEY `fk_cs_curr` (`curriculum_id`),
  CONSTRAINT `college_sections_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `college_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_curr` FOREIGN KEY (`curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `college_section_subjects`;
CREATE TABLE `college_section_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `college_section_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `day` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `delivery_mode` enum('Face-to-Face','Online','Hybrid') NOT NULL DEFAULT 'Face-to-Face',
  `capacity` int(11) NOT NULL DEFAULT 40,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `college_section_id` (`college_section_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `college_section_subjects_ibfk_1` FOREIGN KEY (`college_section_id`) REFERENCES `college_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_section_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. ADMISSIONS (APPLICATIONS)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `status` enum('pending','under_review','correction_required','approved','rejected','enrolled') NOT NULL DEFAULT 'pending',
  `document_submission_method` enum('online','on_campus') NOT NULL DEFAULT 'online',
  `academic_level` enum('Senior High School','College') DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL,
  `semester` enum('First','Second','Summer') DEFAULT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `section_id` int(10) unsigned DEFAULT NULL,
  `college_curriculum_id` int(10) unsigned DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(50) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `telephone_number` varchar(50) DEFAULT NULL,
  `address_house_number` varchar(100) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_barangay` varchar(100) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `address_province` varchar(100) DEFAULT NULL,
  `address_zip` varchar(20) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `father_contact` varchar(50) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `mother_contact` varchar(50) DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `last_school_attended` varchar(255) DEFAULT NULL,
  `last_school_address` varchar(255) DEFAULT NULL,
  `last_school_year` varchar(50) DEFAULT NULL,
  `previous_school_level` enum('Junior High School','Senior High School','College') DEFAULT NULL,
  `previous_strand_course` varchar(255) DEFAULT NULL,
  `academic_year_from` int(11) DEFAULT NULL,
  `academic_year_to` int(11) DEFAULT NULL,
  `previous_school_status` enum('Graduated','Currently Enrolled','Transferee','Undergraduate') DEFAULT NULL,
  `lrn` varchar(50) DEFAULT NULL,
  `student_type` varchar(50) DEFAULT NULL,
  `nstp` enum('CWTS','ROTC','LTS') DEFAULT NULL,
  `emergency_contact_person` varchar(100) DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(50) DEFAULT NULL,
  `special_needs` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `admin_feedback` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_applications_reference_number` (`reference_number`),
  KEY `idx_applications_user_id` (`user_id`),
  KEY `idx_applications_status` (`status`),
  KEY `fk_app_curr` (`college_curriculum_id`),
  CONSTRAINT `fk_app_curr` FOREIGN KEY (`college_curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_applications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `application_documents`;
CREATE TABLE `application_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_application_documents_app_id` (`application_id`),
  CONSTRAINT `fk_application_documents_app_id` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `health_records`;
CREATE TABLE `health_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL,
  `height` varchar(50) DEFAULT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `blood_type` varchar(20) DEFAULT NULL,
  `has_allergies` tinyint(1) DEFAULT 0,
  `has_asthma` tinyint(1) DEFAULT 0,
  `has_diabetes` tinyint(1) DEFAULT 0,
  `has_hypertension` tinyint(1) DEFAULT 0,
  `has_heart_disease` tinyint(1) DEFAULT 0,
  `has_physical_disability` tinyint(1) DEFAULT 0,
  `has_existing_condition` tinyint(1) DEFAULT 0,
  `has_previous_surgery` tinyint(1) DEFAULT 0,
  `has_maintenance_medication` tinyint(1) DEFAULT 0,
  `has_hospitalized` tinyint(1) DEFAULT 0,
  `medical_conditions` text DEFAULT NULL,
  `allergies_details` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `other_notes` text DEFAULT NULL,
  `emergency_name` varchar(100) DEFAULT NULL,
  `emergency_relationship` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(50) DEFAULT NULL,
  `status` enum('pending','under_review','verified','correction_required','rejected') NOT NULL DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_records_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. ENROLLMENT & SCHEDULING
-- --------------------------------------------------------

DROP TABLE IF EXISTS `college_enrollments`;
CREATE TABLE `college_enrollments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `college_section_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_college_enrollment` (`application_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `college_section_id` (`college_section_id`),
  CONSTRAINT `college_enrollments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_enrollments_ibfk_3` FOREIGN KEY (`college_section_id`) REFERENCES `college_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_enrollments`;
CREATE TABLE `shs_enrollments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `shs_section_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shs_enrollment` (`application_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `shs_section_id` (`shs_section_id`),
  CONSTRAINT `shs_enrollments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_enrollments_ibfk_3` FOREIGN KEY (`shs_section_id`) REFERENCES `shs_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. SCHOLARSHIPS
-- --------------------------------------------------------

DROP TABLE IF EXISTS `scholarships`;
CREATE TABLE `scholarships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scholarship_applications`;
CREATE TABLE `scholarship_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned NOT NULL,
  `status` enum('pending','under_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `scholarship_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scholarship_applications_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 8. FINANCIAL / ASSESSMENT
-- --------------------------------------------------------

DROP TABLE IF EXISTS `fee_templates`;
CREATE TABLE `fee_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `academic_level` enum('Senior High School','College') DEFAULT NULL,
  `grade_level` varchar(50) NOT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `tuition_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `miscellaneous_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `registration_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `laboratory_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_assessments`;
CREATE TABLE `student_assessments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL,
  `fee_template_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned DEFAULT NULL,
  `tuition_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `miscellaneous_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `registration_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `laboratory_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `application_id` (`application_id`),
  KEY `fee_template_id` (`fee_template_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `student_assessments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_assessments_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_assessments_ibfk_3` FOREIGN KEY (`fee_template_id`) REFERENCES `fee_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_assessments_ibfk_4` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_scholarships`;
CREATE TABLE `student_scholarships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `student_scholarships_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `student_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_scholarships_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `payment_records`;
CREATE TABLE `payment_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `cashier_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `assessment_id` (`assessment_id`),
  KEY `user_id` (`user_id`),
  KEY `cashier_id` (`cashier_id`),
  CONSTRAINT `payment_records_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `student_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_records_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_records_ibfk_3` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
