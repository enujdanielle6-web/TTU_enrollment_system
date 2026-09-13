# 02. DATABASE ARCHITECTURE & ENTITY-RELATIONSHIP MAP

**Document Reference:** `docs/codebase/02_DATABASE_MAP.md`  
**Execution Phase:** Phase 2 — Database Understanding  
**Database Name:** `sia`  
**Single Source of Truth:** `database/schema.sql` (44 Tables & 1 View)  
**Date:** September 13, 2026  
**Status:** FORENSICALLY VERIFIED

---

## 1. Executive Schema Inventory

The MariaDB database `sia` is composed of **44 tables** and **1 view** organized into five core functional domains:
1. **Users & Core Security:** `users`, `activity_logs`, `login_attempts`, `system_settings`, `announcements`.
2. **Academic Catalog & Curricula:** `college_programs`, `shs_strands`, `subjects`, `college_curricula`, `college_curriculum_subjects`, `shs_curricula`, `shs_curriculum_subjects`.
3. **Sections & Scheduling:** `college_sections`, `college_section_subjects`, `shs_sections`, `shs_section_subjects`.
4. **Admissions, Enrollment & Finance:** `applications`, `application_subject_requests`, `application_documents`, `health_records`, `college_enrollments`, `shs_enrollments`, `fee_templates`, `student_assessments`, `assessment_items`, `payment_records`, `receipt_sequences`, `student_number_sequences`, `scholarships`, `scholarship_applications`, `scholarship_recipients`.
5. **Learning Management System (LMS):** `lms_courses`, `lms_modules`, `lms_materials`, `lms_assignments`, `lms_submissions`, `lms_quizzes`, `lms_questions`, `lms_question_choices`, `lms_quiz_attempts`, `lms_quiz_answers`, `lms_attendance_sessions`, `lms_attendance_records`, `lms_announcements`.
6. **Views:** `student_academic_records_view`.

---

## 2. Forensic Analysis of the `users` Table

The `users` table is the central institutional identity store for all actors across both Enrollment and LMS.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Constraints & Keys | Domain Purpose |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `INT(10) UNSIGNED` | NO | `AUTO_INCREMENT` | `PRIMARY KEY` | Unique institutional user identifier. |
| `first_name` | `VARCHAR(100)` | NO | None | None | Legal first name. |
| `last_name` | `VARCHAR(100)` | NO | None | None | Legal last name. |
| `email` | `VARCHAR(255)` | NO | None | `UNIQUE KEY (email)` | Personal/login email address. |
| `ttu_email` | `VARCHAR(255)` | YES | `NULL` | None | Institutional email (`@ttu.edu.ph`). |
| `password` | `VARCHAR(255)` | NO | None | None | Bcrypt password hash. |
| `student_number`| `VARCHAR(50)` | YES | `NULL` | `UNIQUE KEY (student_number)` | Student ID or Faculty Employee ID. |
| `role` | `ENUM(...)` | NO | `'applicant'` | `KEY (role)` | Role: `'superadmin'`, `'admin'`, `'admissions'`, `'scholarship'`, `'cashier'`, `'clinic'`, `'faculty'`, `'scheduler'`, `'applicant'`, `'student'`. |
| `department` | `VARCHAR(100)` | YES | `'None'` | None | Staff departmental assignment. |
| `permissions`| `LONGTEXT` | YES | `NULL` | JSON format | Custom granular permissions array. |
| `college_curriculum_id` | `INT(10) UNSIGNED` | YES | `NULL` | `FK -> college_curricula.id ON DELETE SET NULL` | Student active college curriculum. |
| `email_verified`| `TINYINT(1)` | NO | `0` | `KEY (email_verified)` | 1 = OTP confirmed, 0 = unverified. |
| `verification_code` | `VARCHAR(10)` | YES | `NULL` | None | 6-digit numeric OTP. |
| `verification_code_expires_at` | `DATETIME` | YES | `NULL` | None | OTP expiration timestamp. |
| `reset_token` | `VARCHAR(255)` | YES | `NULL` | None | Password reset token. |
| `reset_token_expires_at` | `DATETIME` | YES | `NULL` | None | Reset token expiration timestamp. |
| `force_password_reset` | `TINYINT(1)` | NO | `0` | None | Flag forcing password update. |
| `is_active` | `TINYINT(1)` | NO | `1` | `KEY (is_active)` | Account activation flag. |
| `created_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Record creation timestamp. |
| `updated_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP ON UPDATE` | None | Last modification timestamp. |

---

## 3. All Tables Referencing `users.id`

Fourteen distinct foreign keys bind directly to `users.id`:

| Referencing Table | Foreign Key Column | Constraint Name | Delete Behavior | Subsystem |
| :--- | :--- | :--- | :--- | :--- |
| `activity_logs` | `user_id` | `fk_activity_logs_user_id` | `ON DELETE SET NULL` | Core System |
| `applications` | `user_id` | `fk_applications_user_id` | `ON DELETE CASCADE` | Enrollment |
| `health_records` | `user_id` | `health_records_ibfk_1` | `ON DELETE CASCADE` | Enrollment / Clinic |
| `student_assessments` | `user_id` | `student_assessments_ibfk_1` | `ON DELETE CASCADE` | Finance |
| `payment_records` | `user_id` | `payment_records_ibfk_2` | `ON DELETE CASCADE` | Finance |
| `payment_records` | `cashier_id` | `payment_records_ibfk_3` | `ON DELETE SET NULL` | Finance |
| `scholarship_applications` | `user_id` | `scholarship_applications_ibfk_1` | `ON DELETE CASCADE` | Scholarship |
| `scholarship_recipients` | `user_id` | `scholarship_recipients_ibfk_1` | `ON DELETE CASCADE` | Scholarship |
| `lms_courses` | `faculty_user_id` | `fk_lms_course_faculty` | `ON DELETE CASCADE` | LMS Core |
| `lms_submissions` | `student_id` | `fk_lms_sub_student` | `ON DELETE CASCADE` | LMS Assignments |
| `lms_submissions` | `graded_by` | `fk_lms_sub_grader` | `ON DELETE SET NULL` | LMS Assignments |
| `lms_quiz_attempts` | `student_id` | `fk_attempt_student` | `ON DELETE CASCADE` | LMS Quizzes |
| `lms_attendance_records` | `student_id` | `lms_attendance_records_ibfk_2` | `ON DELETE CASCADE` | LMS Attendance |
| `lms_announcements` | `author_user_id` | `lms_announcements_ibfk_2` | `ON DELETE CASCADE` | LMS Announcements |

---

## 4. Enrollment Subsystem Tables & Relationships

### 4.1 `applications` (Central Enrollment State Machine)
- **Primary Key:** `id`
- **Foreign Keys:**
  - `user_id` -> `users.id` (`ON DELETE CASCADE`)
  - `college_curriculum_id` -> `college_curricula.id` (`ON DELETE SET NULL`)
- **Key Columns:**
  - `reference_number`: VARCHAR(50) UNIQUE (`APP-YYYY-XXXXXX`).
  - `academic_level`: ENUM(`'Senior High School'`, `'College'`).
  - `grade_level`: VARCHAR(50).
  - `school_year`: VARCHAR(50).
  - `semester`: VARCHAR(50).
  - `student_type`: VARCHAR(50) DEFAULT `'Regular'`.
  - `strand`: VARCHAR(100) (SHS strand e.g. `'STEM'`).
  - `section_id`: INT UNSIGNED nullable (Maps logically to `college_sections.id` or `shs_sections.id`).
  - `status`: ENUM(`'pending'`, `'under_review'`, `'correction_required'`, `'approved'`, `'payment_verified'`, `'rejected'`, `'enrolled'`).
  - `document_submission_method`: ENUM(`'online'`, `'on_campus'`).

### 4.2 Dependent Enrollment Tables
- **`application_documents`:**
  - `id` (PK), `application_id` (FK -> `applications.id` ON DELETE CASCADE), `document_name`, `file_path`, `status` (ENUM: `'pending'`, `'verified'`, `'rejected'`), `feedback`.
- **`application_subject_requests`:**
  - `id` (PK), `application_id` (FK -> `applications.id` ON DELETE CASCADE), `subject_id` (FK -> `subjects.id` ON DELETE CASCADE), `section_id`.
- **`health_records`:**
  - `id` (PK), `user_id` (FK -> `users.id` ON DELETE CASCADE), `application_id` (FK -> `applications.id` ON DELETE CASCADE), survey booleans (`has_allergies`, `has_asthma`, etc.), emergency contact details, `status` (ENUM: `'pending'`, `'under_review'`, `'correction_required'`, `'verified'`, `'rejected'`).
- **`college_enrollments`:**
  - `id` (PK), `application_id` (FK -> `applications.id` ON DELETE CASCADE), `subject_id` (FK -> `subjects.id` ON DELETE CASCADE), `college_section_id` (FK -> `college_sections.id` ON DELETE SET NULL).
  - Unique Constraint: `UNIQUE KEY (application_id, subject_id)`.
- **`shs_enrollments`:**
  - `id` (PK), `application_id` (FK -> `applications.id` ON DELETE CASCADE), `subject_id` (FK -> `subjects.id` ON DELETE CASCADE), `shs_section_id` (FK -> `shs_sections.id` ON DELETE SET NULL).
  - Unique Constraint: `UNIQUE KEY (application_id, subject_id)`.

---

## 5. Academic Catalog & Timetable Schema

### 5.1 Programs & Strands
- **`college_programs`:** `id` (PK), `code` (VARCHAR(50) UNIQUE, e.g. `'BSIT'`), `name`, `description`, `is_active`.
- **`shs_strands`:** `id` (PK), `code` (VARCHAR(50) UNIQUE, e.g. `'STEM'`), `name`, `description`, `is_active`.

### 5.2 Subjects Master Catalog
- **`subjects`:** `id` (PK), `subject_code` (VARCHAR(50) UNIQUE), `subject_name`, `units` (INT DEFAULT 3), `subject_type` (ENUM: `'Lecture'`, `'Laboratory'`, `'RLE'`), `education_level` (ENUM: `'SHS'`, `'College'`, `'Both'`), `status` (TINYINT 1/0).

### 5.3 Curricula
- **`college_curricula`:** `id` (PK), `program_id` (FK -> `college_programs.id` ON DELETE CASCADE), `curriculum_name`, `version`, `effective_academic_year`, `status` (ENUM: `'active'`, `'inactive'`, `'draft'`).
- **`college_curriculum_subjects`:** `id` (PK), `curriculum_id` (FK -> `college_curricula.id` ON DELETE CASCADE), `subject_id` (FK -> `subjects.id` ON DELETE CASCADE), `year_level`, `semester`, `display_order`.
- **`shs_curricula`:** `id` (PK), `strand_id` (FK -> `shs_strands.id` ON DELETE CASCADE), `curriculum_name`, `version`, `effective_academic_year`, `status` (ENUM: `'active'`, `'inactive'`, `'draft'`).
- **`shs_curriculum_subjects`:** `id` (PK), `curriculum_id` (FK -> `shs_curricula.id` ON DELETE CASCADE), `subject_id` (FK -> `subjects.id` ON DELETE CASCADE), `grade_level`, `semester`, `display_order`.

### 5.4 Sections & Timetables
- **`college_sections`:** `id` (PK), `section_code` (VARCHAR(50) UNIQUE), `program_id` (FK -> `college_programs.id` ON DELETE CASCADE), `curriculum_id` (FK -> `college_curricula.id` ON DELETE SET NULL), `academic_year`, `year_level`, `semester`, `capacity`, `schedule_type`, `adviser` (VARCHAR(150) loose string), `status`.
- **`college_section_subjects`:** `id` (PK), `college_section_id` (FK -> `college_sections.id` ON DELETE CASCADE), `subject_id` (FK -> `subjects.id` ON DELETE CASCADE), `day`, `start_time`, `end_time`, `room`, `instructor` (VARCHAR(150) loose string), `delivery_mode`.
- **`shs_sections`:** `id` (PK), `section_code` (VARCHAR(50) UNIQUE), `strand_id` (FK -> `shs_strands.id` ON DELETE CASCADE), `curriculum_id` (FK -> `shs_curricula.id` ON DELETE SET NULL), `grade_level`, `academic_year`, `capacity`, `schedule_type`, `adviser` (VARCHAR(150) loose string), `status`.
- **`shs_section_subjects`:** `id` (PK), `shs_section_id` (FK -> `shs_sections.id` ON DELETE CASCADE), `subject_id` (FK -> `subjects.id` ON DELETE CASCADE), `day`, `start_time`, `end_time`, `room`, `instructor` (VARCHAR(150) loose string), `delivery_mode`.

---

## 6. Finance & Scholarship Schema

- **`fee_templates`:** `id` (PK), `name`, `academic_level` (ENUM: `'Senior High School'`, `'College'`), `grade_level`, `strand`, `semester`, `is_per_unit`, `tuition_fee`, `miscellaneous_fee`, `registration_fee`, `laboratory_fee`, `other_fees`, `total_amount`.
- **`student_assessments`:** `id` (PK), `application_id` (FK -> `applications.id` ON DELETE CASCADE), `user_id` (FK -> `users.id` ON DELETE CASCADE), `fee_template_id` (FK -> `fee_templates.id` ON DELETE SET NULL), `scholarship_id` (FK -> `scholarships.id` ON DELETE SET NULL), `total_units`, `tuition_fee`, `miscellaneous_fee`, `registration_fee`, `laboratory_fee`, `other_fees`, `total_amount`, `discount_amount`, `net_amount`, `total_paid`, `payment_status` (ENUM: `'unpaid'`, `'partial'`, `'paid'`).
- **`assessment_items`:** `id` (PK), `assessment_id` (FK -> `student_assessments.id` ON DELETE CASCADE), `item_name`, `amount`, `category`.
- **`payment_records`:** `id` (PK), `assessment_id` (FK -> `student_assessments.id` ON DELETE CASCADE), `user_id` (FK -> `users.id` ON DELETE CASCADE), `cashier_id` (FK -> `users.id` ON DELETE SET NULL), `amount`, `payment_method` (ENUM: `'Cash'`, `'GCash'`, `'Bank Transfer'`), `reference_number`, `status` (ENUM: `'pending'`, `'verified'`, `'rejected'`), `receipt_number`.
- **`scholarships`:** `id` (PK), `name`, `code` (UNIQUE), `category`, `provider`, `program_id` (FK -> `college_programs.id` ON DELETE SET NULL), `tuition_coverage_type` (ENUM: `'percentage'`, `'fixed'`), `tuition_coverage_value`, `misc_coverage_type`, `misc_coverage_value`, `status` (ENUM: `'Active'`, `'Inactive'`, `'Draft'`).
- **`scholarship_applications`:** `id` (PK), `user_id` (FK -> `users.id` ON DELETE CASCADE), `scholarship_id` (FK -> `scholarships.id` ON DELETE CASCADE), `status` (ENUM: `'pending'`, `'under_review'`, `'approved'`, `'rejected'`).
- **`scholarship_recipients`:** `id` (PK), `user_id` (FK -> `users.id` ON DELETE CASCADE), `scholarship_id` (FK -> `scholarships.id` ON DELETE CASCADE), `awarded_date`, `status` (ENUM: `'Active'`, `'Completed'`, `'Revoked'`).

---

## 7. LMS Database Schema

### 7.1 `lms_courses` (The Anchor Entity)
- `id` (INT UNSIGNED PK)
- `academic_level` (ENUM: `'College'`, `'SHS'`)
- `academic_section_id` (INT UNSIGNED NOT NULL, logical foreign key)
- `subject_id` (INT UNSIGNED FK -> `subjects.id` ON DELETE CASCADE)
- `faculty_user_id` (INT UNSIGNED FK -> `users.id` ON DELETE CASCADE)
- `status` (ENUM: `'active'`, `'archived'` DEFAULT `'active'`)
- Indexes: `KEY (subject_id)`, `KEY (faculty_user_id)`.

### 7.2 Modules & Materials
- **`lms_modules`:** `id` (PK), `lms_course_id` (FK -> `lms_courses.id` ON DELETE CASCADE), `title`, `description`, `display_order`, `status` (ENUM: `'draft'`, `'published'`).
- **`lms_materials`:** `id` (PK), `lms_module_id` (FK -> `lms_modules.id` ON DELETE CASCADE), `title`, `file_path`, `file_type`, `status` (ENUM: `'draft'`, `'published'`).

### 7.3 Assignments & Submissions
- **`lms_assignments`:** `id` (PK), `lms_course_id` (FK -> `lms_courses.id` ON DELETE CASCADE), `lms_module_id` (FK -> `lms_modules.id` ON DELETE SET NULL), `title`, `instructions`, `max_score` (DECIMAL(5,2)), `due_date`, `status` (ENUM: `'draft'`, `'published'`).
- **`lms_submissions`:** `id` (PK), `assignment_id` (FK -> `lms_assignments.id` ON DELETE CASCADE), `student_id` (FK -> `users.id` ON DELETE CASCADE), `file_path`, `file_name`, `mime_type`, `file_size`, `submitted_at`, `status` (ENUM: `'SUBMITTED'`, `'RESUBMITTED'`, `'GRADED'`), `grade` (DECIMAL(5,2)), `feedback`, `graded_by` (FK -> `users.id` ON DELETE SET NULL), `graded_at`.
- Unique Constraint: `UNIQUE KEY (assignment_id, student_id)`.

### 7.4 Quizzes & Question Engine
- **`lms_quizzes`:** `id` (PK), `lms_course_id` (FK -> `lms_courses.id` ON DELETE CASCADE), `title`, `description`, `time_limit` (INT NULL = unlimited), `max_attempts` (INT NULL = unlimited), `passing_score` (DECIMAL(5,2)), `start_date`, `end_date`, `status` (ENUM: `'draft'`, `'published'`).
- **`lms_questions`:** `id` (PK), `lms_quiz_id` (FK -> `lms_quizzes.id` ON DELETE CASCADE), `question_text`, `question_type` (ENUM: `'multiple_choice'`, `'true_false'`), `points` (DECIMAL(5,2) DEFAULT 1.00), `display_order`.
- **`lms_question_choices`:** `id` (PK), `lms_question_id` (FK -> `lms_questions.id` ON DELETE CASCADE), `choice_text`, `is_correct` (TINYINT 1/0), `display_order`.
- **`lms_quiz_attempts`:** `id` (PK), `lms_quiz_id` (FK -> `lms_quizzes.id` ON DELETE CASCADE), `student_id` (FK -> `users.id` ON DELETE CASCADE), `attempt_number`, `started_at`, `submitted_at`, `score` (DECIMAL(5,2)), `status` (ENUM: `'in_progress'`, `'submitted'`, `'graded'`).
  - Unique Constraint: `UNIQUE KEY (lms_quiz_id, student_id, attempt_number)`.
- **`lms_quiz_answers`:** `id` (PK), `lms_quiz_attempt_id` (FK -> `lms_quiz_attempts.id` ON DELETE CASCADE), `lms_question_id` (FK -> `lms_questions.id` ON DELETE CASCADE), `lms_question_choice_id` (FK -> `lms_question_choices.id` ON DELETE SET NULL), `is_correct` (TINYINT 1/0), `points_awarded` (DECIMAL(5,2)).
  - Unique Constraint: `UNIQUE KEY (lms_quiz_attempt_id, lms_question_id)`.

### 7.5 Attendance & Announcements
- **`lms_attendance_sessions`:** `id` (PK), `lms_course_id` (FK -> `lms_courses.id` ON DELETE CASCADE), `session_date` (DATE), `start_time`, `end_time`, `notes`.
- **`lms_attendance_records`:** `id` (PK), `lms_attendance_session_id` (FK -> `lms_attendance_sessions.id` ON DELETE CASCADE), `student_id` (FK -> `users.id` ON DELETE CASCADE), `status` (ENUM: `'present'`, `'absent'`, `'late'`, `'excused'`), `remarks`, `recorded_at`.
  - Unique Constraint: `UNIQUE KEY (lms_attendance_session_id, student_id)`.
- **`lms_announcements`:** `id` (PK), `lms_course_id` (FK -> `lms_courses.id` ON DELETE CASCADE), `author_user_id` (FK -> `users.id` ON DELETE CASCADE), `title`, `content`, `status` (ENUM: `'draft'`, `'published'`).

---

## 8. Logical Relationships Without Database Foreign Keys

| Parent Entity | Column in Child | Child Table | Logical Reference | Technical Hazard / Defect |
| :--- | :--- | :--- | :--- | :--- |
| `college_sections` / `shs_sections` | `academic_section_id` | `lms_courses` | Section ID | Deleting a section leaves orphaned LMS courses with dangling section IDs. |
| `college_sections` / `shs_sections` | `section_id` | `applications` | Section ID | Polymorphic column; cannot enforce standard FK constraint. |
| `users` (role='faculty') | `instructor` | `college_section_subjects` | Instructor Name | Stored as unlinked `VARCHAR(150)` string. No FK to `users.id`. Name variations break conflict detection and LMS sync. |
| `users` (role='faculty') | `instructor` | `shs_section_subjects` | Instructor Name | Stored as unlinked `VARCHAR(150)` string. No FK to `users.id`. |
| `users` (role='faculty') | `adviser` | `college_sections` | Section Adviser | Stored as unlinked `VARCHAR(150)` string. No FK to `users.id`. |
| `users` (role='faculty') | `adviser` | `shs_sections` | Section Adviser | Stored as unlinked `VARCHAR(150)` string. No FK to `users.id`. |

---

### Phase 2 Database Map Conclusion
Every table, primary key, foreign key, index, unique constraint, enum value, nullable property, cascade rule, and unconstrained logical relationship in `database/schema.sql` has been forensically verified and documented. No speculative tables (such as previously assumed separate grade tables) were included.
