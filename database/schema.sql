-- ============================================================================
-- TRIPLE T UNIVERSITY (TTU) ENROLLMENT & LMS SYSTEM - COMPLETE DDL SCHEMA
-- Database: sia (42 Tables & Views)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ----------------------------------------------------------------------------
-- 1. USERS & CORE SYSTEM TABLES
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ttu_email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `role` enum('superadmin','admin','admissions','scholarship','cashier','clinic','faculty','scheduler','applicant','student') NOT NULL DEFAULT 'applicant',
  `department` varchar(100) DEFAULT 'None',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `college_curriculum_id` int(10) unsigned DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(10) DEFAULT NULL,
  `verification_code_expires_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `force_password_reset` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_number` (`student_number`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_is_active` (`is_active`),
  KEY `idx_users_email_verified` (`email_verified`),
  KEY `fk_users_college_curriculum` (`college_curriculum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `affected_record` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bi-info-circle',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_activity_logs_user_id` (`user_id`),
  CONSTRAINT `fk_activity_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_attempt` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `badge_label` varchar(50) NOT NULL,
  `badge_color` varchar(20) NOT NULL DEFAULT 'primary',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. ACADEMIC CATALOG & CURRICULUM TABLES
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `college_programs`;
CREATE TABLE `college_programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_strands`;
CREATE TABLE `shs_strands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `units` int(11) NOT NULL DEFAULT 3,
  `subject_type` varchar(50) NOT NULL DEFAULT 'Lecture',
  `description` text DEFAULT NULL,
  `education_level` enum('SHS','College','Both') NOT NULL DEFAULT 'College',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `college_curricula`;
CREATE TABLE `college_curricula` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `curriculum_name` varchar(150) NOT NULL,
  `version` varchar(50) NOT NULL DEFAULT '1.0',
  `effective_academic_year` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','draft') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `college_curricula_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `college_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  KEY `curriculum_id` (`curriculum_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `college_curriculum_subs_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_curriculum_subs_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `college_sections`;
CREATE TABLE `college_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_code` varchar(50) NOT NULL,
  `program_id` int(10) unsigned NOT NULL,
  `curriculum_id` int(10) unsigned DEFAULT NULL,
  `academic_year` varchar(50) DEFAULT NULL,
  `year_level` varchar(50) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `schedule_type` varchar(50) NOT NULL DEFAULT 'Morning',
  `adviser` varchar(150) DEFAULT NULL,
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
  `capacity` int(11) NOT NULL DEFAULT 40,
  `day` varchar(20) NOT NULL DEFAULT 'TBA',
  `start_time` time NOT NULL DEFAULT '00:00:00',
  `end_time` time NOT NULL DEFAULT '00:00:00',
  `room` varchar(50) DEFAULT NULL,
  `instructor` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `college_section_id` (`college_section_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `college_section_subjects_ibfk_1` FOREIGN KEY (`college_section_id`) REFERENCES `college_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_section_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_curricula`;
CREATE TABLE `shs_curricula` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `strand_id` int(10) unsigned NOT NULL,
  `curriculum_name` varchar(150) NOT NULL,
  `version` varchar(50) NOT NULL DEFAULT '1.0',
  `effective_academic_year` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','draft') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `strand_id` (`strand_id`),
  CONSTRAINT `shs_curricula_ibfk_1` FOREIGN KEY (`strand_id`) REFERENCES `shs_strands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_curriculum_subjects`;
CREATE TABLE `shs_curriculum_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `curriculum_id` (`curriculum_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `shs_curriculum_subs_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `shs_curricula` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_curriculum_subs_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `shs_sections`;
CREATE TABLE `shs_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_code` varchar(50) NOT NULL,
  `strand_id` int(10) unsigned NOT NULL,
  `curriculum_id` int(10) unsigned DEFAULT NULL,
  `grade_level` varchar(50) NOT NULL,
  `academic_year` varchar(50) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `schedule_type` varchar(50) NOT NULL DEFAULT 'Morning',
  `adviser` varchar(150) DEFAULT NULL,
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
  `capacity` int(11) NOT NULL DEFAULT 40,
  `day` varchar(20) NOT NULL DEFAULT 'TBA',
  `start_time` time NOT NULL DEFAULT '00:00:00',
  `end_time` time NOT NULL DEFAULT '00:00:00',
  `room` varchar(50) DEFAULT NULL,
  `instructor` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shs_section_id` (`shs_section_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `shs_section_subjects_ibfk_1` FOREIGN KEY (`shs_section_id`) REFERENCES `shs_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_section_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. FINANCE & SCHOLARSHIP TABLES
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `fee_templates`;
CREATE TABLE `fee_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `academic_level` enum('Senior High School','College') DEFAULT NULL,
  `grade_level` varchar(50) NOT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `semester` enum('First','Second','Summer') DEFAULT NULL,
  `is_per_unit` tinyint(1) NOT NULL DEFAULT 0,
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

DROP TABLE IF EXISTS `scholarships`;
CREATE TABLE `scholarships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `category` enum('School-Based','Government','Private') NOT NULL DEFAULT 'School-Based',
  `provider` varchar(150) DEFAULT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `min_gwa` decimal(4,2) DEFAULT NULL,
  `income_requirement` decimal(10,2) DEFAULT NULL,
  `slots` int(11) DEFAULT NULL,
  `tuition_coverage_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `tuition_coverage_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `misc_coverage_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `misc_coverage_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stipend_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `book_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `application_start` date DEFAULT NULL,
  `application_end` date DEFAULT NULL,
  `status` enum('Active','Inactive','Draft') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `scholarships_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `college_programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. ADMISSIONS, APPLICATIONS & ENROLLMENTS
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `academic_level` enum('Senior High School','College') DEFAULT NULL,
  `grade_level` varchar(50) NOT NULL,
  `school_year` varchar(50) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `student_type` varchar(50) NOT NULL DEFAULT 'Regular',
  `strand` varchar(100) DEFAULT NULL,
  `nstp` varchar(50) DEFAULT NULL,
  `section_id` int(10) unsigned DEFAULT NULL,
  `college_curriculum_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','under_review','correction_required','approved','rejected','enrolled') NOT NULL DEFAULT 'pending',
  `document_submission_method` enum('online','on_campus') NOT NULL DEFAULT 'online',
  `admin_feedback` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `contact_number` varchar(50) NOT NULL,
  `telephone_number` varchar(50) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `civil_status` varchar(20) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `place_of_birth` varchar(100) NOT NULL,
  `address_house_number` varchar(50) DEFAULT NULL,
  `address_street` varchar(100) DEFAULT NULL,
  `address_barangay` varchar(100) DEFAULT NULL,
  `address_city` varchar(100) NOT NULL,
  `address_province` varchar(100) NOT NULL,
  `address_zip` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_relationship` varchar(50) NOT NULL,
  `guardian_contact` varchar(50) NOT NULL,
  `previous_school` varchar(150) NOT NULL,
  `previous_school_year` varchar(50) NOT NULL,
  `previous_school_type` varchar(50) NOT NULL,
  `lrn` varchar(50) DEFAULT NULL,
  `emergency_name` varchar(100) DEFAULT NULL,
  `emergency_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `user_id` (`user_id`),
  KEY `fk_app_curr` (`college_curriculum_id`),
  CONSTRAINT `fk_app_curr` FOREIGN KEY (`college_curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_applications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `application_documents`;
CREATE TABLE `application_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_application_documents_app_id` (`application_id`),
  CONSTRAINT `fk_application_documents_app_id` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `health_records`;
CREATE TABLE `health_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL,
  `height` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `has_allergies` tinyint(1) NOT NULL DEFAULT 0,
  `has_asthma` tinyint(1) NOT NULL DEFAULT 0,
  `has_diabetes` tinyint(1) NOT NULL DEFAULT 0,
  `has_hypertension` tinyint(1) NOT NULL DEFAULT 0,
  `has_heart_disease` tinyint(1) NOT NULL DEFAULT 0,
  `has_physical_disability` tinyint(1) NOT NULL DEFAULT 0,
  `has_existing_condition` tinyint(1) NOT NULL DEFAULT 0,
  `has_previous_surgery` tinyint(1) NOT NULL DEFAULT 0,
  `has_maintenance_medication` tinyint(1) NOT NULL DEFAULT 0,
  `has_hospitalized` tinyint(1) NOT NULL DEFAULT 0,
  `medical_conditions` text DEFAULT NULL,
  `allergies_details` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `other_notes` text DEFAULT NULL,
  `emergency_name` varchar(100) NOT NULL,
  `emergency_relationship` varchar(50) NOT NULL,
  `emergency_contact` varchar(50) NOT NULL,
  `status` enum('pending','under_review','correction_required','verified','rejected') NOT NULL DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_records_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `college_enrollments`;
CREATE TABLE `college_enrollments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `college_section_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `subject_id` (`subject_id`),
  KEY `shs_section_id` (`shs_section_id`),
  CONSTRAINT `shs_enrollments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_enrollments_ibfk_3` FOREIGN KEY (`shs_section_id`) REFERENCES `shs_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_assessments`;
CREATE TABLE `student_assessments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL,
  `fee_template_id` int(10) unsigned DEFAULT NULL,
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
  CONSTRAINT `student_assessments_ibfk_3` FOREIGN KEY (`fee_template_id`) REFERENCES `fee_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_assessments_ibfk_4` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payment_records`;
CREATE TABLE `payment_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `cashier_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `user_id` (`user_id`),
  KEY `cashier_id` (`cashier_id`),
  CONSTRAINT `payment_records_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `student_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_records_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_records_ibfk_3` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scholarship_applications`;
CREATE TABLE `scholarship_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned NOT NULL,
  `academic_year_id` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `admin_feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `scholarship_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scholarship_applications_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scholarship_recipients`;
CREATE TABLE `scholarship_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned NOT NULL,
  `academic_year_id` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `scholarship_recipients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scholarship_recipients_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_scholarships`;
CREATE TABLE `student_scholarships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned NOT NULL,
  `academic_year` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `student_scholarships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_scholarships_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. LEARNING MANAGEMENT SYSTEM (LMS) TABLES
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `lms_courses`;
CREATE TABLE `lms_courses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `faculty_user_id` int(10) unsigned DEFAULT NULL,
  `college_section_id` int(10) unsigned DEFAULT NULL,
  `course_code` varchar(100) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `term` varchar(50) NOT NULL DEFAULT 'First Semester',
  `academic_year` varchar(50) NOT NULL DEFAULT '2026-2027',
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `fk_lms_course_subject` (`subject_id`),
  KEY `fk_lms_course_faculty` (`faculty_user_id`),
  KEY `fk_lms_course_section` (`college_section_id`),
  CONSTRAINT `fk_lms_course_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lms_course_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_course_section` FOREIGN KEY (`college_section_id`) REFERENCES `college_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_modules`;
CREATE TABLE `lms_modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 1,
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lms_mod_course` (`lms_course_id`),
  CONSTRAINT `fk_lms_mod_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_materials`;
CREATE TABLE `lms_materials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_module_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL DEFAULT 'pdf',
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lms_mat_module` (`lms_module_id`),
  CONSTRAINT `fk_lms_mat_module` FOREIGN KEY (`lms_module_id`) REFERENCES `lms_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_assignments`;
CREATE TABLE `lms_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `lms_module_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_points` decimal(5,2) NOT NULL DEFAULT 100.00,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lms_ass_course` (`lms_course_id`),
  KEY `fk_lms_ass_module` (`lms_module_id`),
  CONSTRAINT `fk_lms_ass_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_ass_module` FOREIGN KEY (`lms_module_id`) REFERENCES `lms_modules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_submissions`;
CREATE TABLE `lms_submissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `graded_by` int(10) unsigned DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submission_text` text DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_assignment_student` (`assignment_id`,`student_id`),
  KEY `fk_lms_sub_student` (`student_id`),
  KEY `fk_lms_sub_grader` (`graded_by`),
  CONSTRAINT `fk_lms_sub_assign` FOREIGN KEY (`assignment_id`) REFERENCES `lms_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lms_sub_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_quizzes`;
CREATE TABLE `lms_quizzes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit_minutes` int(11) NOT NULL DEFAULT 30,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 50.00,
  `max_attempts` int(11) NOT NULL DEFAULT 1,
  `due_date` datetime DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_quiz_course` (`lms_course_id`),
  CONSTRAINT `fk_quiz_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_questions`;
CREATE TABLE `lms_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_id` int(10) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','essay') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `order_index` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_question_quiz` (`lms_quiz_id`),
  CONSTRAINT `fk_question_quiz` FOREIGN KEY (`lms_quiz_id`) REFERENCES `lms_quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_question_choices`;
CREATE TABLE `lms_question_choices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_question_id` int(10) unsigned NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_index` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_choice_question` (`lms_question_id`),
  CONSTRAINT `fk_choice_question` FOREIGN KEY (`lms_question_id`) REFERENCES `lms_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_quiz_attempts`;
CREATE TABLE `lms_quiz_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `score` decimal(5,2) DEFAULT NULL,
  `total_points` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT 0,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_attempt_quiz` (`lms_quiz_id`),
  KEY `fk_attempt_student` (`student_id`),
  CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`lms_quiz_id`) REFERENCES `lms_quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_quiz_answers`;
CREATE TABLE `lms_quiz_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_attempt_id` int(10) unsigned NOT NULL,
  `lms_question_id` int(10) unsigned NOT NULL,
  `lms_question_choice_id` int(10) unsigned DEFAULT NULL,
  `text_answer` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `points_awarded` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_answer_attempt` (`lms_quiz_attempt_id`),
  KEY `fk_answer_question` (`lms_question_id`),
  KEY `fk_answer_choice` (`lms_question_choice_id`),
  CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`lms_quiz_attempt_id`) REFERENCES `lms_quiz_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answer_choice` FOREIGN KEY (`lms_question_choice_id`) REFERENCES `lms_question_choices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`lms_question_id`) REFERENCES `lms_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_attendance_sessions`;
CREATE TABLE `lms_attendance_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `session_date` date NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Regular Class Session',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lms_attendance_sessions_ibfk_1` (`lms_course_id`),
  CONSTRAINT `lms_attendance_sessions_ibfk_1` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_attendance_records`;
CREATE TABLE `lms_attendance_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_attendance_session_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `status` enum('present','late','absent','excused') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_student` (`lms_attendance_session_id`,`student_id`),
  KEY `lms_attendance_records_ibfk_2` (`student_id`),
  CONSTRAINT `lms_attendance_records_ibfk_1` FOREIGN KEY (`lms_attendance_session_id`) REFERENCES `lms_attendance_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_attendance_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lms_announcements`;
CREATE TABLE `lms_announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `author_user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lms_announcements_ibfk_1` (`lms_course_id`),
  KEY `lms_announcements_ibfk_2` (`author_user_id`),
  CONSTRAINT `lms_announcements_ibfk_1` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_announcements_ibfk_2` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. VIEWS
-- ----------------------------------------------------------------------------

DROP VIEW IF EXISTS `student_academic_records_view`;
CREATE VIEW `student_academic_records_view` AS
SELECT 
    u.id AS user_id,
    u.student_number,
    u.first_name,
    u.last_name,
    u.email,
    u.ttu_email,
    a.id AS application_id,
    a.reference_number,
    a.academic_level,
    a.grade_level,
    a.strand,
    a.status AS enrollment_status,
    sec.section_code,
    (SELECT COALESCE(SUM(s.units), 0) 
     FROM college_enrollments ce 
     JOIN subjects s ON ce.subject_id = s.id 
     WHERE ce.application_id = a.id) AS total_enrolled_units
FROM users u
JOIN applications a ON u.id = a.user_id
LEFT JOIN college_sections sec ON a.section_id = sec.id;

-- ----------------------------------------------------------------------------
-- 7. FOREIGN KEY CONSTRAINTS ON USERS
-- ----------------------------------------------------------------------------
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_college_curriculum` FOREIGN KEY (`college_curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
