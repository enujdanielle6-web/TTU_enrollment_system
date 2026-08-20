-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sia
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=366 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `application_documents`
--

DROP TABLE IF EXISTS `application_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_curricula`
--

DROP TABLE IF EXISTS `college_curricula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_curriculum_subjects`
--

DROP TABLE IF EXISTS `college_curriculum_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_enrollments`
--

DROP TABLE IF EXISTS `college_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_programs`
--

DROP TABLE IF EXISTS `college_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_section_subjects`
--

DROP TABLE IF EXISTS `college_section_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_sections`
--

DROP TABLE IF EXISTS `college_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fee_templates`
--

DROP TABLE IF EXISTS `fee_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `health_records`
--

DROP TABLE IF EXISTS `health_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_announcements`
--

DROP TABLE IF EXISTS `lms_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `author_user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lms_course_id` (`lms_course_id`),
  KEY `author_user_id` (`author_user_id`),
  CONSTRAINT `lms_announcements_ibfk_1` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_announcements_ibfk_2` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_assignments`
--

DROP TABLE IF EXISTS `lms_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `lms_module_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_score` int(11) NOT NULL DEFAULT 100,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lms_ass_course` (`lms_course_id`),
  KEY `fk_lms_ass_module` (`lms_module_id`),
  CONSTRAINT `fk_lms_ass_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_ass_module` FOREIGN KEY (`lms_module_id`) REFERENCES `lms_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_attendance_records`
--

DROP TABLE IF EXISTS `lms_attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_attendance_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_attendance_session_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_student` (`lms_attendance_session_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `lms_attendance_records_ibfk_1` FOREIGN KEY (`lms_attendance_session_id`) REFERENCES `lms_attendance_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_attendance_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_attendance_sessions`
--

DROP TABLE IF EXISTS `lms_attendance_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_attendance_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lms_course_id` (`lms_course_id`),
  CONSTRAINT `lms_attendance_sessions_ibfk_1` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_courses`
--

DROP TABLE IF EXISTS `lms_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_courses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `academic_level` enum('College','SHS') NOT NULL,
  `academic_section_id` int(10) unsigned NOT NULL COMMENT 'Maps to college_sections.id or shs_sections.id logically',
  `subject_id` int(10) unsigned NOT NULL,
  `faculty_user_id` int(10) unsigned NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lms_course_subject` (`subject_id`),
  KEY `idx_lms_course_faculty` (`faculty_user_id`),
  CONSTRAINT `fk_lms_course_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_course_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_materials`
--

DROP TABLE IF EXISTS `lms_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_materials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_module_id` int(10) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lms_mat_module` (`lms_module_id`),
  CONSTRAINT `fk_lms_mat_module` FOREIGN KEY (`lms_module_id`) REFERENCES `lms_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_modules`
--

DROP TABLE IF EXISTS `lms_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lms_mod_course` (`lms_course_id`),
  CONSTRAINT `fk_lms_mod_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_question_choices`
--

DROP TABLE IF EXISTS `lms_question_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_question_choices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_question_id` int(10) unsigned NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_choice_question` (`lms_question_id`),
  CONSTRAINT `fk_choice_question` FOREIGN KEY (`lms_question_id`) REFERENCES `lms_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_questions`
--

DROP TABLE IF EXISTS `lms_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_id` int(10) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_question_quiz` (`lms_quiz_id`),
  CONSTRAINT `fk_question_quiz` FOREIGN KEY (`lms_quiz_id`) REFERENCES `lms_quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_quiz_answers`
--

DROP TABLE IF EXISTS `lms_quiz_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_quiz_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_attempt_id` int(10) unsigned NOT NULL,
  `lms_question_id` int(10) unsigned NOT NULL,
  `lms_question_choice_id` int(10) unsigned DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `points_awarded` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt_question` (`lms_quiz_attempt_id`,`lms_question_id`),
  KEY `fk_answer_question` (`lms_question_id`),
  KEY `fk_answer_choice` (`lms_question_choice_id`),
  CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`lms_quiz_attempt_id`) REFERENCES `lms_quiz_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answer_choice` FOREIGN KEY (`lms_question_choice_id`) REFERENCES `lms_question_choices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`lms_question_id`) REFERENCES `lms_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_quiz_attempts`
--

DROP TABLE IF EXISTS `lms_quiz_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_quiz_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `started_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quiz_student_attempt` (`lms_quiz_id`,`student_id`,`attempt_number`),
  KEY `fk_attempt_student` (`student_id`),
  CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`lms_quiz_id`) REFERENCES `lms_quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_quizzes`
--

DROP TABLE IF EXISTS `lms_quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_quizzes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL COMMENT 'In minutes. NULL means unlimited.',
  `max_attempts` int(11) DEFAULT 1,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_quiz_course` (`lms_course_id`),
  CONSTRAINT `fk_quiz_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_submissions`
--

DROP TABLE IF EXISTS `lms_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_submissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `status` enum('SUBMITTED','RESUBMITTED','GRADED') NOT NULL DEFAULT 'SUBMITTED',
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `graded_by` int(10) unsigned DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lms_sub_assign` (`assignment_id`),
  KEY `fk_lms_sub_student` (`student_id`),
  KEY `fk_lms_sub_grader` (`graded_by`),
  CONSTRAINT `fk_lms_sub_assign` FOREIGN KEY (`assignment_id`) REFERENCES `lms_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lms_sub_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_records`
--

DROP TABLE IF EXISTS `payment_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scholarship_applications`
--

DROP TABLE IF EXISTS `scholarship_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scholarship_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_feedback` text DEFAULT NULL,
  `submitted_documents` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `scholarship_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scholarship_applications_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scholarship_recipients`
--

DROP TABLE IF EXISTS `scholarship_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scholarship_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `scholarship_id` int(10) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `semester` varchar(50) NOT NULL,
  `status` enum('Active','Suspended','Terminated','Renewed') NOT NULL DEFAULT 'Active',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `scholarship_id` (`scholarship_id`),
  CONSTRAINT `scholarship_recipients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scholarship_recipients_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scholarships`
--

DROP TABLE IF EXISTS `scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scholarships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `category` enum('School-Based','Government','Department-Based','Private','Special') NOT NULL DEFAULT 'School-Based',
  `provider` varchar(255) DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `min_gwa` decimal(4,2) DEFAULT NULL,
  `income_requirement` decimal(12,2) DEFAULT NULL,
  `slots` int(11) DEFAULT NULL,
  `tuition_coverage_type` enum('full','percentage','fixed') NOT NULL DEFAULT 'fixed',
  `tuition_coverage_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `misc_coverage_type` enum('full','percentage','fixed') NOT NULL DEFAULT 'fixed',
  `misc_coverage_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stipend_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `book_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `required_documents` text DEFAULT NULL,
  `application_start` date DEFAULT NULL,
  `application_end` date DEFAULT NULL,
  `status` enum('Draft','Active','Closed','Suspended') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shs_curricula`
--

DROP TABLE IF EXISTS `shs_curricula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shs_curricula` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `strand_id` int(10) unsigned NOT NULL,
  `curriculum_name` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL DEFAULT '1.0',
  `effective_academic_year` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','draft') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `strand_id` (`strand_id`),
  CONSTRAINT `shs_curricula_ibfk_1` FOREIGN KEY (`strand_id`) REFERENCES `shs_strands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shs_curriculum_subjects`
--

DROP TABLE IF EXISTS `shs_curriculum_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shs_curriculum_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_id` int(10) unsigned NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `semester` enum('First','Second') DEFAULT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shs_curriculum_sub` (`curriculum_id`,`grade_level`,`semester`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_curriculum_id` (`curriculum_id`),
  CONSTRAINT `shs_curriculum_subs_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `shs_curricula` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shs_curriculum_subs_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shs_enrollments`
--

DROP TABLE IF EXISTS `shs_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shs_section_subjects`
--

DROP TABLE IF EXISTS `shs_section_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shs_sections`
--

DROP TABLE IF EXISTS `shs_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shs_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_code` varchar(100) NOT NULL,
  `strand_id` int(10) unsigned NOT NULL,
  `curriculum_id` int(10) unsigned DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shs_strands`
--

DROP TABLE IF EXISTS `shs_strands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shs_strands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `student_academic_records_view`
--

DROP TABLE IF EXISTS `student_academic_records_view`;
/*!50001 DROP VIEW IF EXISTS `student_academic_records_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `student_academic_records_view` AS SELECT
 1 AS `user_id`,
  1 AS `student_number`,
  1 AS `first_name`,
  1 AS `last_name`,
  1 AS `application_id`,
  1 AS `academic_level`,
  1 AS `grade_level`,
  1 AS `school_year`,
  1 AS `semester`,
  1 AS `strand`,
  1 AS `subject_id`,
  1 AS `subject_code`,
  1 AS `subject_name`,
  1 AS `units`,
  1 AS `enrollment_type`,
  1 AS `enrolled_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `student_assessments`
--

DROP TABLE IF EXISTS `student_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_scholarships`
--

DROP TABLE IF EXISTS `student_scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('applicant','admin','superadmin','admissions','scholarship','cashier','clinic','faculty','scheduler') NOT NULL DEFAULT 'applicant',
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
  KEY `fk_users_college_curriculum` (`college_curriculum_id`),
  CONSTRAINT `fk_users_college_curriculum` FOREIGN KEY (`college_curriculum_id`) REFERENCES `college_curricula` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `student_academic_records_view`
--

/*!50001 DROP VIEW IF EXISTS `student_academic_records_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `student_academic_records_view` AS select `u`.`id` AS `user_id`,`u`.`student_number` AS `student_number`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`a`.`id` AS `application_id`,`a`.`academic_level` AS `academic_level`,`a`.`grade_level` AS `grade_level`,`a`.`school_year` AS `school_year`,`a`.`semester` AS `semester`,`a`.`strand` AS `strand`,`s`.`id` AS `subject_id`,`s`.`subject_code` AS `subject_code`,`s`.`subject_name` AS `subject_name`,`s`.`units` AS `units`,'College' AS `enrollment_type`,`ce`.`created_at` AS `enrolled_at` from (((`college_enrollments` `ce` join `applications` `a` on(`ce`.`application_id` = `a`.`id`)) join `users` `u` on(`a`.`user_id` = `u`.`id`)) join `subjects` `s` on(`ce`.`subject_id` = `s`.`id`)) union all select `u`.`id` AS `user_id`,`u`.`student_number` AS `student_number`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`a`.`id` AS `application_id`,`a`.`academic_level` AS `academic_level`,`a`.`grade_level` AS `grade_level`,`a`.`school_year` AS `school_year`,`a`.`semester` AS `semester`,`a`.`strand` AS `strand`,`s`.`id` AS `subject_id`,`s`.`subject_code` AS `subject_code`,`s`.`subject_name` AS `subject_name`,`s`.`units` AS `units`,'SHS' AS `enrollment_type`,`se`.`created_at` AS `enrolled_at` from (((`shs_enrollments` `se` join `applications` `a` on(`se`.`application_id` = `a`.`id`)) join `users` `u` on(`a`.`user_id` = `u`.`id`)) join `subjects` `s` on(`se`.`subject_id` = `s`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-19 13:36:46
