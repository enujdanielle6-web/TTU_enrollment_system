-- Online Enrollment System
-- Database schema aligned with config/database.php and application PHP files.
--
-- Default connection settings (config/database.php):
--   DB_HOST     = 127.0.0.1
--   DB_PORT     = 3306
--   DB_DATABASE = sia
--   DB_USERNAME = root
--   DB_PASSWORD = (empty)
--   DB_CHARSET  = utf8mb4
--
-- Import:
--   mysql -u root < database/schema.sql
--   mysql -u root sia < database/seed.sql

CREATE DATABASE IF NOT EXISTS sia
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sia;

-- ---------------------------------------------------------------------------
-- users
-- Used by: auth/login_process.php, auth/register_process.php
-- Session fields: id, first_name, last_name, email, role
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('applicant', 'admin', 'superadmin', 'admissions', 'scholarship', 'cashier') NOT NULL DEFAULT 'applicant',
  student_number VARCHAR(50) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_student_number (student_number),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- applications
-- Used by: applicant/status.php, includes/functions.php
-- Fields read: id, reference_number, status, created_at, updated_at, user_id
-- Status values match PROJECT.md and includes/functions.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reference_number VARCHAR(50) NOT NULL,
  status ENUM(
    'pending',
    'under_review',
    'correction_required',
    'approved',
    'rejected',
    'enrolled'
  ) NOT NULL DEFAULT 'pending',
  document_submission_method ENUM('online', 'on_campus') NOT NULL DEFAULT 'online',
  academic_level ENUM('Senior High School', 'College') DEFAULT NULL,
  grade_level VARCHAR(50) DEFAULT NULL,
  school_year VARCHAR(50) DEFAULT NULL,
  semester ENUM('First', 'Second', 'Summer') DEFAULT NULL,
  strand VARCHAR(50) DEFAULT NULL,
  contact_number VARCHAR(50) DEFAULT NULL,
  birth_date DATE DEFAULT NULL,
  gender VARCHAR(50) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  guardian_name VARCHAR(100) DEFAULT NULL,
  guardian_contact VARCHAR(50) DEFAULT NULL,
  middle_name VARCHAR(100) DEFAULT NULL,
  suffix VARCHAR(20) DEFAULT NULL,
  place_of_birth VARCHAR(255) DEFAULT NULL,
  civil_status VARCHAR(50) DEFAULT NULL,
  nationality VARCHAR(100) DEFAULT NULL,
  religion VARCHAR(100) DEFAULT NULL,
  telephone_number VARCHAR(50) DEFAULT NULL,
  address_house_number VARCHAR(100) DEFAULT NULL,
  address_street VARCHAR(255) DEFAULT NULL,
  address_barangay VARCHAR(100) DEFAULT NULL,
  address_city VARCHAR(100) DEFAULT NULL,
  address_province VARCHAR(100) DEFAULT NULL,
  address_zip VARCHAR(20) DEFAULT NULL,
  father_name VARCHAR(100) DEFAULT NULL,
  father_occupation VARCHAR(100) DEFAULT NULL,
  father_contact VARCHAR(50) DEFAULT NULL,
  mother_name VARCHAR(100) DEFAULT NULL,
  mother_occupation VARCHAR(100) DEFAULT NULL,
  mother_contact VARCHAR(50) DEFAULT NULL,
  guardian_relationship VARCHAR(100) DEFAULT NULL,
  last_school_attended VARCHAR(255) DEFAULT NULL,
  last_school_address VARCHAR(255) DEFAULT NULL,
  last_school_year VARCHAR(50) DEFAULT NULL,
  previous_school_level ENUM('Junior High School', 'Senior High School', 'College') DEFAULT NULL,
  previous_strand_course VARCHAR(255) DEFAULT NULL,
  academic_year_from INT DEFAULT NULL,
  academic_year_to INT DEFAULT NULL,
  previous_school_status ENUM('Graduated', 'Currently Enrolled', 'Transferee', 'Undergraduate') DEFAULT NULL,
  lrn VARCHAR(50) DEFAULT NULL,
  student_type VARCHAR(50) DEFAULT NULL,
  nstp ENUM('CWTS', 'ROTC', 'LTS') DEFAULT NULL,
  emergency_contact_person VARCHAR(100) DEFAULT NULL,
  emergency_contact_relationship VARCHAR(100) DEFAULT NULL,
  emergency_contact_number VARCHAR(50) DEFAULT NULL,
  special_needs TEXT DEFAULT NULL,
  medical_conditions TEXT DEFAULT NULL,
  allergies TEXT DEFAULT NULL,
  admin_feedback TEXT DEFAULT NULL,
  internal_notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_applications_reference_number (reference_number),
  KEY idx_applications_user_id (user_id),
  KEY idx_applications_status (status),
  CONSTRAINT fk_applications_user_id
    FOREIGN KEY (user_id)
    REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- application_documents
-- Used by: applicant/dashboard.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS application_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNSIGNED NOT NULL,
  document_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  feedback TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_application_documents_app_id (application_id),
  CONSTRAINT fk_application_documents_app_id
    FOREIGN KEY (application_id)
    REFERENCES applications (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- activity_logs
-- Used by: applicant/dashboard.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  icon VARCHAR(50) NOT NULL DEFAULT 'bi-circle',
  title VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  affected_record VARCHAR(255) DEFAULT NULL,
  old_value JSON DEFAULT NULL,
  new_value JSON DEFAULT NULL,
  reason TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_activity_logs_user_id (user_id),
  CONSTRAINT fk_activity_logs_user_id
    FOREIGN KEY (user_id)
    REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- announcements
-- Used by: applicant/dashboard.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  badge_label VARCHAR(100) NOT NULL,
  badge_color VARCHAR(50) NOT NULL DEFAULT 'primary',
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_announcements_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- system_settings
-- Used by: admin/settings.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(50) NOT NULL UNIQUE,
  setting_value VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- academic_programs
-- Used by: admin/programs.php, applicant/enroll.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS academic_programs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  category ENUM('Senior High School', 'College') NOT NULL DEFAULT 'Senior High School',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- login_attempts
-- Used by: auth/login_process.php
-- Rate limiting tracking to protect against brute-force attacks.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  email VARCHAR(255) NOT NULL,
  attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- fee_templates
-- Used by: admin/fees.php, admin/fee_process.php
-- Phase 1 Financial Foundation
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fee_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  academic_level ENUM('Senior High School', 'College') DEFAULT NULL,
  grade_level VARCHAR(50) NOT NULL,
  strand VARCHAR(50) DEFAULT NULL,
  tuition_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  miscellaneous_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  registration_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  laboratory_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  other_fees DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- scholarships
-- Phase 1 Financial Foundation
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS scholarships (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  discount_type ENUM('percentage', 'fixed') NOT NULL,
  discount_value DECIMAL(10, 2) NOT NULL,
  description TEXT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- student_assessments
-- Phase 1 Financial Foundation
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_assessments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  application_id INT UNSIGNED NOT NULL,
  fee_template_id INT UNSIGNED NOT NULL,
  scholarship_id INT UNSIGNED DEFAULT NULL,
  tuition_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  miscellaneous_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  registration_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  laboratory_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  other_fees DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  net_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  total_paid DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  payment_status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (fee_template_id) REFERENCES fee_templates(id) ON DELETE CASCADE,
  FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- payment_records
-- Phase 1 Financial Foundation
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payment_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assessment_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  receipt_number VARCHAR(50) UNIQUE DEFAULT NULL,
  reference_number VARCHAR(100) DEFAULT NULL,
  cashier_id INT UNSIGNED DEFAULT NULL,
  status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (assessment_id) REFERENCES student_assessments(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- scholarship_applications
-- Phase 2 Scholarship Module
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS scholarship_applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  scholarship_id INT UNSIGNED NOT NULL,
  status ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  admin_feedback TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- student_scholarships
-- Maps a student assessment to a scholarship
-- Phase 1 Financial Foundation
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_scholarships (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assessment_id INT UNSIGNED NOT NULL,
  scholarship_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (assessment_id) REFERENCES student_assessments(id) ON DELETE CASCADE,
  FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---------------------------------------------------------------------------
-- health_records
-- Phase 5 Health Module
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS health_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    application_id INT UNSIGNED NOT NULL,
    height VARCHAR(50) DEFAULT NULL,
    weight VARCHAR(50) DEFAULT NULL,
    blood_type VARCHAR(20) DEFAULT NULL,
    has_allergies TINYINT(1) DEFAULT 0,
    has_asthma TINYINT(1) DEFAULT 0,
    has_diabetes TINYINT(1) DEFAULT 0,
    has_hypertension TINYINT(1) DEFAULT 0,
    has_heart_disease TINYINT(1) DEFAULT 0,
    has_physical_disability TINYINT(1) DEFAULT 0,
    has_existing_condition TINYINT(1) DEFAULT 0,
    has_previous_surgery TINYINT(1) DEFAULT 0,
    has_maintenance_medication TINYINT(1) DEFAULT 0,
    has_hospitalized TINYINT(1) DEFAULT 0,
    medical_conditions TEXT DEFAULT NULL,
    allergies_details TEXT DEFAULT NULL,
    current_medications TEXT DEFAULT NULL,
    other_notes TEXT DEFAULT NULL,
    emergency_name VARCHAR(100) DEFAULT NULL,
    emergency_relationship VARCHAR(100) DEFAULT NULL,
    emergency_contact VARCHAR(50) DEFAULT NULL,
    status ENUM('pending', 'under_review', 'verified', 'correction_required', 'rejected') NOT NULL DEFAULT 'pending',
    admin_remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- subjects
-- Phase 3 Curriculum Module
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_code VARCHAR(50) NOT NULL UNIQUE,
  subject_name VARCHAR(255) NOT NULL,
  units INT NOT NULL DEFAULT 3,
  subject_type VARCHAR(100) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- curriculum
-- Phase 3 Curriculum Module
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS curriculum (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  year_level VARCHAR(50) NOT NULL,
  semester VARCHAR(50) DEFAULT NULL,
  subject_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  UNIQUE KEY uq_curriculum_assignment (program_id, year_level, semester, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- enrollment_subjects
-- Phase 3 Curriculum Module
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enrollment_subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  section_id INT UNSIGNED NULL,
  section_subject_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
  UNIQUE KEY uq_enrollment_subject (application_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- sections
-- Phase 3 Sections Module
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_code VARCHAR(100) NOT NULL UNIQUE,
    program_id INT UNSIGNED NOT NULL,
    year_level VARCHAR(50) NOT NULL,
    semester VARCHAR(50) DEFAULT NULL,
    schedule_type ENUM('Morning', 'Afternoon') NOT NULL,
    capacity INT NOT NULL DEFAULT 40,
    adviser VARCHAR(255) DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- section_schedules
-- Phase 3 Sections Module
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS section_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    day VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(100) NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- section_subjects
-- Phase 7 Unified Scheduling
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS section_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    instructor VARCHAR(255) DEFAULT NULL,
    room VARCHAR(100) DEFAULT NULL,
    building VARCHAR(100) DEFAULT NULL,
    day VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    delivery_mode ENUM('Face-to-Face', 'Online', 'Hybrid') NOT NULL DEFAULT 'Face-to-Face',
    capacity INT NOT NULL DEFAULT 40,
    status TINYINT(1) NOT NULL DEFAULT 1,
    legacy_schedule_id INT UNSIGNED NULL,
    legacy_offering_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `college_curricula` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `curriculum_name` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL DEFAULT '1.0',
  `effective_academic_year` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active', 'inactive', 'draft') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `college_curricula_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `college_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `college_curriculum_subjects` (
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
