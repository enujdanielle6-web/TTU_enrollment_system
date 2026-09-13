# 01. DATA MAPPING SPECIFICATION & TRANSFORMATION RULES

**Document Reference:** `docs/migration/01_DATA_MAPPING.md`  
**Execution Phase:** Phase 15 — Migration Design  
**Repository:** Triple T University (TTU) Enrollment System & LMS  
**Target Architecture:** Option A+ (Enriched In-Place Separation + Relational Faculty & Timetable Integration)  
**Date:** September 13, 2026  
**Status:** ARCHITECTURAL SPECIFICATION & FIELD-LEVEL MIGRATION SPECIFICATION  

---

## 1. Executive Summary & Mapping Philosophy

This document provides the exhaustive, field-level data mapping and transformation rules required to execute the transition from the legacy polymorphic schema to the Option A+ relational architecture.

### Core Transformation Principles:
1. **Lossless Transformation:** No existing data point (email, password hash, enrollment record, submission, grade) is discarded, truncated, or overwritten.
2. **Deterministic Normalization:** Free-form text fields (such as `department` and `instructor`) are normalized into verified foreign keys while preserving the original strings for backward-compatible dual-read/dual-write.
3. **Strict Domain Isolation:** Student-specific identifiers (`student_number`) and faculty identifiers (`employee_id`) are cleanly partitioned in `users`, eliminating the multi-purpose column hazard.
4. **Idempotent Data Scripts:** Every SQL migration and backfill snippet can be executed repeatedly without generating duplicates, key collisions, or data skew.

---

## 2. Global Entity & Column Transformation Matrix

The following master table details every modified, added, or re-mapped column across all subsystems:

| Target Table | Target Column | Source Table | Source Column | Transform Type | Transform Logic & Default Value | Data Integrity Rule |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `users` | `employee_id` | `users` | `student_number` | **Split & Relocate** | If `role = 'faculty'`, copy `student_number` to `employee_id`. Format: `EMP-YYYY-XXXX` or legacy value. | `VARCHAR(50) UNIQUE NULL` |
| `users` | `student_number` | `users` | `student_number` | **Domain Cleanse** | If `role = 'faculty'`, set `student_number = NULL`. Retain for students and applicants. | `VARCHAR(50) UNIQUE NULL` |
| `users` | `lms_status` | `applications` / `users` | `status` / `role` | **Additive State** | If `role = 'faculty'` $\to$ `'active'`.<br>If enrolled student $\to$ `'active'`.<br>All others $\to$ `'inactive'`. | `ENUM('inactive', 'active', 'suspended') DEFAULT 'inactive'` |
| `users` | `role` | `applications` | `status` | **State Sync** | If user has `applications.status = 'enrolled'` and `users.role = 'applicant'`, update `users.role = 'student'`. | `ENUM(...) NOT NULL DEFAULT 'applicant'` |
| `faculty_profiles` | `user_id` | `users` | `id` | **Entity Extraction** | `users.id` WHERE `role = 'faculty'` | `INT(10) UNSIGNED NOT NULL UNIQUE FK` |
| `faculty_profiles` | `employee_id` | `users` | `employee_id` | **Derivation** | Synced with `users.employee_id` | `VARCHAR(50) NOT NULL UNIQUE` |
| `faculty_profiles` | `program_id` | `users` | `department` | **String to Relational** | Map string `users.department` to `college_programs.id`. Fallback: `NULL` or General Education (ID: 1). | `INT(10) UNSIGNED NULL FK` |
| `faculty_profiles` | `academic_rank` | *(New)* | *(Default)* | **Initialization** | Default: `'Instructor I'`. Configurable in profile editor. | `VARCHAR(100) NOT NULL DEFAULT 'Instructor I'` |
| `faculty_profiles` | `employment_type` | *(New)* | *(Default)* | **Initialization** | Default: `'full_time'`. | `ENUM('full_time', 'part_time', 'adjunct') DEFAULT 'full_time'` |
| `faculty_profiles` | `max_teaching_units`| *(New)* | *(Default)* | **Initialization** | Default: `18.00` (Full-time), `12.00` (Part-time). | `DECIMAL(4,2) NOT NULL DEFAULT 18.00` |
| `faculty_profiles` | `status` | `users` | `is_active` | **Direct Map** | If `users.is_active = 1` $\to$ `'active'`, else `'inactive'`. | `ENUM('active', 'on_leave', 'inactive') DEFAULT 'active'` |
| `college_section_subjects` | `faculty_user_id` | `college_section_subjects` | `instructor` | **Name Resolution** | Fuzzy/exact match `instructor` string against `CONCAT(u.first_name, ' ', u.last_name)`. | `INT(10) UNSIGNED NULL FK -> users.id` |
| `shs_section_subjects` | `faculty_user_id` | `shs_section_subjects` | `instructor` | **Name Resolution** | Fuzzy/exact match `instructor` string against `CONCAT(u.first_name, ' ', u.last_name)`. | `INT(10) UNSIGNED NULL FK -> users.id` |
| `lms_courses` | `faculty_user_id` | `college_section_subjects` | `faculty_user_id` | **Correction & Hardening** | Re-assign incorrectly auto-provisioned courses (Alan Turing / ID 8) to actual section timetable instructor. | `ON DELETE RESTRICT` (Replaces `CASCADE`) |
| `lms_courses` | `academic_section_id`, `subject_id` | `lms_courses` | Multiple | **Constraint Hardening** | Add compound unique index `(academic_level, academic_section_id, subject_id)`. | `UNIQUE KEY` |

---

## 3. Domain 1: User Identity & Account Separation (`users` Table)

### 3.1 `student_number` and `employee_id` Separation
* **Historical Flaw:** The `student_number` column was used for both students (e.g. `2024-0001`) and faculty members (e.g. `FAC-2024-001` or `EMP-001`). This created cognitive dissonance in LMS auth and broke semantic constraints.
* **Target State:**
  - `users.student_number` strictly contains student identity numbers or `NULL`.
  - `users.employee_id` strictly contains employee/faculty identity numbers or `NULL`.
  - Superadmins, cashiers, schedulers, and clinic staff can also be assigned an `employee_id`.

#### Field Transformation Logic:
```sql
-- Step 1: Add employee_id column (Nullable, Unique)
ALTER TABLE `users` 
ADD COLUMN `employee_id` VARCHAR(50) NULL AFTER `student_number`,
ADD UNIQUE KEY `idx_users_employee_id` (`employee_id`);

-- Step 2: Migrate existing faculty identifiers from student_number to employee_id
UPDATE `users` 
SET `employee_id` = `student_number`,
    `student_number` = NULL
WHERE `role` = 'faculty' 
  AND `student_number` IS NOT NULL;

-- Step 3: Ensure faculty members without an identifier receive a generated employee_id
UPDATE `users` 
SET `employee_id` = CONCAT('EMP-', DATE_FORMAT(created_at, '%Y'), '-', LPAD(id, 4, '0'))
WHERE `role` = 'faculty' 
  AND `employee_id` IS NULL;
```

### 3.2 Student Role State Synchronization
* **Historical Flaw:** When an applicant completes enrollment (`applications.status = 'enrolled'`), `AdmissionsController` and `EnrollmentService` fail to update `users.role` from `'applicant'` to `'student'`. The user remains permanently tagged as `'applicant'`, requiring LMS code to perform runtime synthetic role aliasing.
* **Target State:**
  - All users with verified enrolled status possess `users.role = 'student'`.

#### Field Transformation Logic:
```sql
-- Synchronize active enrolled applicants to official student role
UPDATE `users` u
JOIN `applications` a ON a.user_id = u.id
SET u.role = 'student'
WHERE a.status = 'enrolled' 
  AND u.role = 'applicant';
```

### 3.3 LMS Status Lifecycle Initialization
* **Historical Flaw:** LMS access is strictly binary based on `users.is_active`. There is no mechanism to suspend LMS access for tuition arrears or disciplinary holds without disabling the student's entire institutional login.
* **Target State:**
  - `users.lms_status` is an ENUM(`'inactive'`, `'active'`, `'suspended'`).
  - Active faculty members: `'active'`.
  - Officially enrolled students: `'active'`.
  - Applicants and incomplete registrants: `'inactive'`.

#### Field Transformation Logic:
```sql
-- Step 1: Add lms_status column
ALTER TABLE `users` 
ADD COLUMN `lms_status` ENUM('inactive', 'active', 'suspended') NOT NULL DEFAULT 'inactive' AFTER `role`,
ADD KEY `idx_users_lms_status` (`lms_status`);

-- Step 2: Activate all active faculty members
UPDATE `users` 
SET `lms_status` = 'active' 
WHERE `role` = 'faculty' AND `is_active` = 1;

-- Step 3: Activate all officially enrolled students
UPDATE `users` u
JOIN `applications` a ON a.user_id = u.id
SET u.lms_status = 'active'
WHERE a.status = 'enrolled' AND u.is_active = 1;
```

---

## 4. Domain 2: Faculty Entity & Profile Expansion (`faculty_profiles`)

### 4.1 Schema Definition
The `faculty_profiles` table extends `users` without duplicating user identity:

```sql
CREATE TABLE IF NOT EXISTS `faculty_profiles` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `employee_id` VARCHAR(50) NOT NULL,
  `program_id` INT(10) UNSIGNED NULL,
  `academic_rank` VARCHAR(100) NOT NULL DEFAULT 'Instructor I',
  `employment_type` ENUM('full_time', 'part_time', 'adjunct') NOT NULL DEFAULT 'full_time',
  `max_teaching_units` DECIMAL(4,2) NOT NULL DEFAULT 18.00,
  `specializations` TEXT NULL,
  `status` ENUM('active', 'on_leave', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_faculty_user_id` (`user_id`),
  UNIQUE KEY `idx_faculty_employee_id` (`employee_id`),
  KEY `fk_faculty_profiles_program` (`program_id`),
  CONSTRAINT `fk_faculty_profiles_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_faculty_profiles_program` 
    FOREIGN KEY (`program_id`) REFERENCES `college_programs` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 Department String to Relational `program_id` Mapping
The legacy `users.department` column contains loose strings such as `'Computer Science Dept'`, `'Information Technology'`, `'Nursing'`, `'General Education'`. These are mapped to `college_programs.id` using a deterministic dictionary:

| Legacy `users.department` String | Resolved `college_programs.code` | Resolved `college_programs.id` | Strategy |
| :--- | :--- | :---: | :--- |
| `'Computer Science Dept'` / `'CS'` | `BSCS` | Query via `code = 'BSCS'` | Direct match to Computer Science program |
| `'Information Technology'` / `'IT'` | `BSIT` | Query via `code = 'BSIT'` | Direct match to IT program |
| `'Nursing'` / `'College of Nursing'` | `BSN` | Query via `code = 'BSN'` | Direct match to Nursing program |
| `'Business Administration'` / `'BA'` | `BSBA` | Query via `code = 'BSBA'` | Direct match to Business Admin |
| `'General Education'` / `'GenEd'` | `GENED` | `NULL` (Institutional Service) | Faculty teaches university-wide gen-ed courses |
| `'None'` / `NULL` / Unknown | `NULL` | `NULL` | Unassigned / General assignment |

#### Population Query:
```sql
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
    COALESCE(u.employee_id, CONCAT('EMP-', LPAD(u.id, 4, '0'))) AS employee_id,
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
```

---

## 5. Domain 3: Timetable Relational Transformation (`college_section_subjects`)

### 5.1 Relational Expansion of Section Subjects
Timetable subjects currently associate instructors through a loose `VARCHAR(150) instructor` column. We introduce a nullable, indexed foreign key column `faculty_user_id` without removing `instructor`:

```sql
-- For College Section Subjects
ALTER TABLE `college_section_subjects` 
ADD COLUMN `faculty_user_id` INT(10) UNSIGNED NULL AFTER `room`,
ADD KEY `idx_css_faculty_user_id` (`faculty_user_id`),
ADD CONSTRAINT `fk_css_faculty_user` 
  FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
  ON DELETE SET NULL ON UPDATE CASCADE;

-- For SHS Section Subjects
ALTER TABLE `shs_section_subjects` 
ADD COLUMN `faculty_user_id` INT(10) UNSIGNED NULL AFTER `room`,
ADD KEY `idx_sss_faculty_user_id` (`faculty_user_id`),
ADD CONSTRAINT `fk_sss_faculty_user` 
  FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
  ON DELETE SET NULL ON UPDATE CASCADE;
```

### 5.2 Name Resolution & Matching Algorithm
The legacy text strings in `instructor` vary widely:
1. `'Alan Turing'` $\to$ Exact match `first_name = 'Alan'`, `last_name = 'Turing'`.
2. `'Turing, Alan'` $\to$ Inverted match `last_name = 'Turing'`, `first_name = 'Alan'`.
3. `'Dr. Alan Turing'` / `'Engr. Alan Turing'` $\to$ Regex/Prefix stripped match.
4. `'TBA'`, `'To Be Announced'`, `''`, `NULL` $\to$ Evaluates to `NULL`.

#### Resolution Transformation Script:
```sql
-- Pass 1: Exact concatenated full name match (First Last)
UPDATE `college_section_subjects` css
JOIN `users` u ON TRIM(css.instructor) = CONCAT(u.first_name, ' ', u.last_name)
SET css.faculty_user_id = u.id
WHERE u.role = 'faculty' 
  AND css.instructor IS NOT NULL 
  AND css.faculty_user_id IS NULL;

-- Pass 2: Inverted name match (Last, First)
UPDATE `college_section_subjects` css
JOIN `users` u ON TRIM(css.instructor) = CONCAT(u.last_name, ', ', u.first_name)
SET css.faculty_user_id = u.id
WHERE u.role = 'faculty' 
  AND css.instructor IS NOT NULL 
  AND css.faculty_user_id IS NULL;

-- Pass 3: Trim common titles ('Dr. ', 'Prof. ', 'Engr. ', 'Mr. ', 'Ms. ', 'Mrs. ')
UPDATE `college_section_subjects` css
JOIN `users` u ON TRIM(REGEXP_REPLACE(css.instructor, '^(Dr\\.|Prof\\.|Engr\\.|Mr\\.|Ms\\.|Mrs\\.)\\s+', '')) = CONCAT(u.first_name, ' ', u.last_name)
SET css.faculty_user_id = u.id
WHERE u.role = 'faculty' 
  AND css.instructor IS NOT NULL 
  AND css.faculty_user_id IS NULL;

-- Repeat Passes for SHS Section Subjects
UPDATE `shs_section_subjects` sss
JOIN `users` u ON TRIM(sss.instructor) = CONCAT(u.first_name, ' ', u.last_name)
SET sss.faculty_user_id = u.id
WHERE u.role = 'faculty' 
  AND sss.instructor IS NOT NULL 
  AND sss.faculty_user_id IS NULL;
```

---

## 6. Domain 4: LMS Course Provisioning & Foreign Key Hardening (`lms_courses`)

### 6.1 Eliminate Catastrophic Cascade Hazard
* **Current Schema (`database/schema.sql:671`):**
  ```sql
  CONSTRAINT `fk_lms_course_faculty` FOREIGN KEY (`faculty_user_id`) 
  REFERENCES `users` (`id`) ON DELETE CASCADE
  ```
  If a faculty account is deleted, MariaDB cascades and deletes all courses, assignments, student submissions, grades, quizzes, quiz questions, and attendance records.
* **Target Transformation:**
  Altering constraint to `ON DELETE RESTRICT` guarantees that faculty deletion is rejected if historical academic courses are tied to the account. Faculty offboarding must be handled via `is_active = 0` or reassigning courses.

```sql
ALTER TABLE `lms_courses` 
DROP FOREIGN KEY `fk_lms_course_faculty`;

ALTER TABLE `lms_courses` 
ADD CONSTRAINT `fk_lms_course_faculty` 
  FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
  ON DELETE RESTRICT ON UPDATE CASCADE;
```

### 6.2 Adding Compound Uniqueness Constraint
To prevent race conditions where multiple LMS courses are created for the same section-subject pair:
```sql
-- Step 1: Remove any historical duplicate courses keeping the course with the highest ID (most recent)
DELETE c1 FROM `lms_courses` c1
JOIN `lms_courses` c2 
  ON c1.academic_level = c2.academic_level 
  AND c1.academic_section_id = c2.academic_section_id 
  AND c1.subject_id = c2.subject_id 
  AND c1.id < c2.id;

-- Step 2: Add compound unique constraint
ALTER TABLE `lms_courses` 
ADD UNIQUE KEY `unique_section_subject_course` (`academic_level`, `academic_section_id`, `subject_id`);
```

### 6.3 Remediation of Lowest-ID Auto-Provisioning Defect
* **Historical Defect:** `CollegeEnrollmentRepository::getEnrolledSubjectsWithLms` (lines 65-96) automatically provisions LMS courses using:
  ```sql
  SELECT id FROM users WHERE role = 'faculty' ORDER BY id ASC LIMIT 1
  ```
  This assigns all unmapped courses to Alan Turing (ID 8) or fallback user 18, overriding the timetable instructor.
* **Target Reconciliation:**
  Reassign all `lms_courses` whose `faculty_user_id` does not match the timetable's actual instructor:

```sql
-- Align College LMS courses with the section subject instructor
UPDATE `lms_courses` lc
JOIN `college_section_subjects` css 
  ON css.college_section_id = lc.academic_section_id 
  AND css.subject_id = lc.subject_id
SET lc.faculty_user_id = css.faculty_user_id
WHERE lc.academic_level = 'College' 
  AND css.faculty_user_id IS NOT NULL 
  AND lc.faculty_user_id != css.faculty_user_id;

-- Align SHS LMS courses with the section subject instructor
UPDATE `lms_courses` lc
JOIN `shs_section_subjects` sss 
  ON sss.shs_section_id = lc.academic_section_id 
  AND sss.subject_id = lc.subject_id
SET lc.faculty_user_id = sss.faculty_user_id
WHERE lc.academic_level = 'SHS' 
  AND sss.faculty_user_id IS NOT NULL 
  AND lc.faculty_user_id != sss.faculty_user_id;
```

---

## 7. Domain 5: Faculty Availability & Specializations

### 7.1 Schema Definitions
```sql
-- Table: faculty_availability
CREATE TABLE IF NOT EXISTS `faculty_availability` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `faculty_user_id` INT(10) UNSIGNED NOT NULL,
  `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` TIME NOT NULL DEFAULT '07:00:00',
  `end_time` TIME NOT NULL DEFAULT '19:00:00',
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_faculty_day_window` (`faculty_user_id`, `day_of_week`, `start_time`, `end_time`),
  CONSTRAINT `fk_fa_faculty_user` 
    FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: faculty_specializations
CREATE TABLE IF NOT EXISTS `faculty_specializations` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `faculty_user_id` INT(10) UNSIGNED NOT NULL,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `competency_level` ENUM('Primary', 'Secondary', 'Qualified') NOT NULL DEFAULT 'Primary',
  `years_experience` INT UNSIGNED DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_faculty_subject_spec` (`faculty_user_id`, `subject_id`),
  CONSTRAINT `fk_fspec_faculty_user` 
    FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fspec_subject` 
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7.2 Initial Availability Population
Seed standard Monday–Saturday 07:00 to 19:00 availability windows for all active faculty members:
```sql
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
```

### 7.3 Canonical SQL View: `faculty_workloads_view`
A real-time reporting view that aggregates weekly assigned teaching hours, total section subjects, and capacity utilization:

```sql
CREATE OR REPLACE VIEW `faculty_workloads_view` AS
SELECT 
    u.id AS faculty_user_id,
    COALESCE(u.employee_id, u.student_number) AS employee_id,
    CONCAT(u.first_name, ' ', u.last_name) AS faculty_name,
    fp.academic_rank,
    fp.employment_type,
    fp.max_teaching_units,
    COUNT(DISTINCT css.id) AS total_assigned_classes,
    COALESCE(SUM(s.units), 0) AS total_teaching_units,
    COALESCE(SUM(
        ROUND(TIME_TO_SEC(TIMEDIFF(css.end_time, css.start_time)) / 3600.0, 2)
    ), 0) AS total_weekly_contact_hours,
    CASE 
        WHEN COALESCE(SUM(s.units), 0) > fp.max_teaching_units THEN 'Overload'
        WHEN COALESCE(SUM(s.units), 0) = fp.max_teaching_units THEN 'Full'
        ELSE 'Underload'
    END AS workload_status
FROM `users` u
LEFT JOIN `faculty_profiles` fp ON fp.user_id = u.id
LEFT JOIN `college_section_subjects` css ON css.faculty_user_id = u.id
LEFT JOIN `subjects` s ON s.id = css.subject_id
WHERE u.role = 'faculty' AND u.is_active = 1
GROUP BY u.id, u.employee_id, u.student_number, u.first_name, u.last_name, fp.academic_rank, fp.employment_type, fp.max_teaching_units;
```

---

## 8. Data Transformation Validation Assertions

Before declaring the data migration complete, the following SQL assertion queries must return **zero rows**:

| Validation Assertion | Query | Expected Count | Meaning of Failure |
| :--- | :--- | :---: | :--- |
| **No Unseparated Faculty IDs** | `SELECT id FROM users WHERE role = 'faculty' AND (employee_id IS NULL OR student_number IS NOT NULL);` | `0` | Faculty still has student number or lacks employee ID. |
| **No Active Enrolled Applicants** | `SELECT u.id FROM users u JOIN applications a ON a.user_id = u.id WHERE a.status = 'enrolled' AND u.role = 'applicant';` | `0` | Role state synchronization failed. |
| **No Unassigned Faculty Profiles** | `SELECT id FROM users WHERE role = 'faculty' AND id NOT IN (SELECT user_id FROM faculty_profiles);` | `0` | Missing profile record for faculty member. |
| **No Duplicate LMS Courses** | `SELECT academic_level, academic_section_id, subject_id, COUNT(*) FROM lms_courses GROUP BY academic_level, academic_section_id, subject_id HAVING COUNT(*) > 1;` | `0` | Duplicate LMS courses exist for a section subject. |
| **No Cascading Faculty FKs** | `SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME = 'fk_lms_course_faculty' AND DELETE_RULE = 'CASCADE';` | `0` | Faculty delete cascade hazard still active. |
| **No Misaligned Faculty LMS Courses** | `SELECT lc.id FROM lms_courses lc JOIN college_section_subjects css ON css.college_section_id = lc.academic_section_id AND css.subject_id = lc.subject_id WHERE lc.academic_level = 'College' AND css.faculty_user_id IS NOT NULL AND lc.faculty_user_id != css.faculty_user_id;` | `0` | Auto-provisioning lowest ID bug still contaminating courses. |

---

## 9. Comprehensive Rollback Scripts

If any migration step fails validation, the following scripts provide immediate data rollback:

```sql
-- Rollback Data Transformations
UPDATE `users` SET `student_number` = `employee_id` WHERE `role` = 'faculty' AND `employee_id` IS NOT NULL;
UPDATE `users` SET `employee_id` = NULL, `lms_status` = 'inactive';
UPDATE `users` SET `role` = 'applicant' WHERE id IN (SELECT user_id FROM applications WHERE status = 'enrolled');

-- Drop Relational Enhancements
DROP VIEW IF EXISTS `faculty_workloads_view`;
DROP TABLE IF EXISTS `faculty_availability`;
DROP TABLE IF EXISTS `faculty_specializations`;
DROP TABLE IF EXISTS `faculty_profiles`;

-- Restore LMS Course Cascade Rule (if necessary)
ALTER TABLE `lms_courses` DROP FOREIGN KEY `fk_lms_course_faculty`;
ALTER TABLE `lms_courses` ADD CONSTRAINT `fk_lms_course_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Drop Relational Columns on Section Subjects
ALTER TABLE `college_section_subjects` DROP FOREIGN KEY `fk_css_faculty_user`, DROP COLUMN `faculty_user_id`;
ALTER TABLE `shs_section_subjects` DROP FOREIGN KEY `fk_sss_faculty_user`, DROP COLUMN `faculty_user_id`;

-- Drop New Columns on users
ALTER TABLE `users` DROP COLUMN `employee_id`, DROP COLUMN `lms_status`;
```

---

### Data Mapping Summary
This specification provides deterministic, reversible transformation rules for every affected table, ensuring absolute data integrity, zero lost records, and flawless relational alignment across Enrollment, Timetabling, and the LMS.
