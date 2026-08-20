# Database Data Dictionary

This document serves as the complete technical data dictionary for all **41 database tables** in the TTU database (`sia`).

---

## 1. Identity & System Core Tables

### `users`
Central identity store for all system accounts.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `first_name` (VARCHAR(100), NOT NULL)
* `last_name` (VARCHAR(100), NOT NULL)
* `email` (VARCHAR(255), UNIQUE, NOT NULL): Primary login email.
* `password` (VARCHAR(255), NOT NULL): Bcrypt password hash.
* `role` (ENUM('applicant','admin','superadmin','admissions','scholarship','cashier','clinic','faculty','scheduler'), NOT NULL, DEFAULT 'applicant')
* `student_number` (VARCHAR(50), UNIQUE, NULL): Institutional student ID or faculty employee ID.
* `department` (VARCHAR(100), NULL, DEFAULT 'None'): Department assignment.
* `permissions` (LONGTEXT / JSON, NULL): Granular permissions JSON.
* `college_curriculum_id` (INT UNSIGNED, NULL, FK $\rightarrow$ `college_curricula.id`): Locked curriculum version.
* `email_verified` (TINYINT(1), NOT NULL, DEFAULT 1): `0` = unverified, `1` = verified.
* `verification_code` (VARCHAR(10), NULL): Active 6-digit OTP code.
* `verification_expires_at` (DATETIME, NULL): Expiration timestamp for active OTP code.
* `is_active` (TINYINT(1), NOT NULL, DEFAULT 1): Account status.
* `last_login_at` (TIMESTAMP, NULL): Last login timestamp.
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL)

### `activity_logs`
System-wide audit trail for administrative actions.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `user_id` (INT UNSIGNED, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `icon` (VARCHAR(50), DEFAULT 'bi-circle'): Bootstrap icon class.
* `title` (VARCHAR(255), NOT NULL): Action title (e.g. 'Logged In', 'Fee Updated').
* `description` (TEXT, NULL): Detailed description.
* `ip_address` (VARCHAR(45), NULL): Client IPv4/IPv6 address.
* `affected_record` (VARCHAR(255), NULL): Identifier of affected entity.
* `old_value` / `new_value` (JSON, NULL): Snapshot of changed data.
* `reason` (TEXT, NULL): Reason provided for mutation.
* `created_at` (TIMESTAMP)

### `login_attempts`
Brute force defense log.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `ip_address` (VARCHAR(45), NOT NULL)
* `email` (VARCHAR(255), NOT NULL)
* `attempted_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `system_settings`
Global system configuration key-value pairs.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `setting_key` (VARCHAR(100), UNIQUE, NOT NULL)
* `setting_value` (TEXT, NULL)
* `created_at` / `updated_at` (TIMESTAMP)

### `announcements`
University-wide public announcements.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `badge_label` (VARCHAR(100), NOT NULL)
* `badge_color` (VARCHAR(50), DEFAULT 'primary')
* `title` (VARCHAR(255), NOT NULL)
* `content` (TEXT, NOT NULL)
* `is_active` (TINYINT(1), DEFAULT 1)
* `created_at` / `updated_at` (TIMESTAMP)

---

## 2. Admission, Applicant & Health Tables

### `applications`
Master application and term enrollment anchor.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `user_id` (INT UNSIGNED, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `reference_number` (VARCHAR(50), UNIQUE, NOT NULL): e.g. `APP-2026-0001`.
* `status` (ENUM('pending','under_review','correction_required','approved','rejected','enrolled'), DEFAULT 'pending')
* `document_submission_method` (ENUM('online','on_campus'), DEFAULT 'online')
* `academic_level` (ENUM('Senior High School','College'))
* `grade_level` / `school_year` / `semester` (VARCHAR/ENUM)
* `strand` / `section_id` / `college_curriculum_id` (Program choices)
* Personal, demographic, guardian, and previous school background fields.
* `created_at` / `updated_at` (TIMESTAMP)

### `application_documents`
Digital scans uploaded by applicants.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `application_id` (INT UNSIGNED, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `document_name` (VARCHAR(255), NOT NULL): e.g. 'PSA Birth Certificate', 'Form 137'.
* `file_path` (VARCHAR(255), NULL): Path on disk.
* `status` (ENUM('pending','verified','rejected'), DEFAULT 'pending')
* `feedback` (TEXT, NULL): Remarks from admissions reviewer.
* `created_at` / `updated_at` (TIMESTAMP)

### `health_records`
Medical background and clinic clearance records.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `user_id` (INT UNSIGNED, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `application_id` (INT UNSIGNED, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `blood_type` (VARCHAR(10), NULL)
* `allergies` / `medical_conditions` / `current_medications` (TEXT, NULL)
* `emergency_contact_person` / `emergency_contact_relationship` / `emergency_contact_number` (VARCHAR)
* `status` (ENUM('pending','under_review','correction_required','verified'), DEFAULT 'pending')
* `admin_remarks` (TEXT, NULL)
* `created_at` / `updated_at` (TIMESTAMP)

---

## 3. Academic & Curriculum Tables

### `subjects`
Universal catalog of all teachable subjects.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `subject_code` (VARCHAR(50), UNIQUE, NOT NULL): e.g. 'IT101', 'ENG101'.
* `subject_name` (VARCHAR(255), NOT NULL)
* `units` (INT, DEFAULT 3): Academic credit units.
* `lecture_hours` / `lab_hours` (INT, DEFAULT 0)
* `description` (TEXT, NULL)
* `created_at` / `updated_at` (TIMESTAMP)

### `college_programs`
College degree programs.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `program_code` (VARCHAR(50), UNIQUE, NOT NULL): e.g. 'BSIT', 'BSCS'.
* `program_name` (VARCHAR(255), NOT NULL)
* `description` (TEXT, NULL)
* `is_active` (TINYINT(1), DEFAULT 1)

### `college_curricula`
Versioned blueprints for college programs.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `program_id` (INT UNSIGNED, FK $\rightarrow$ `college_programs.id`, ON DELETE CASCADE)
* `curriculum_name` (VARCHAR(255), NOT NULL): e.g. '2024 Revised Curriculum'.
* `year_level` (VARCHAR(50))
* `is_active` (TINYINT(1), DEFAULT 1)

### `college_curriculum_subjects`
Maps subjects to specific year levels and semesters within a college curriculum version.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `curriculum_id` (INT UNSIGNED, FK $\rightarrow$ `college_curricula.id`, ON DELETE CASCADE)
* `subject_id` (INT UNSIGNED, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `year_level` (INT)
* `semester` (ENUM('First','Second','Summer'))

### `college_sections`
Timetabled section blocks for college students.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `curriculum_id` (INT UNSIGNED, FK $\rightarrow$ `college_curricula.id`, ON DELETE CASCADE)
* `section_code` (VARCHAR(50), NOT NULL): e.g. 'BSIT 1-A'.
* `year_level` (INT)
* `semester` (VARCHAR(50))
* `max_capacity` (INT, DEFAULT 40)
* `adviser` (VARCHAR(255), NULL): Faculty instructor assigned to section.

### `college_section_subjects`
Links subjects to sections with specific schedules and room numbers.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `section_id` (INT UNSIGNED, FK $\rightarrow$ `college_sections.id`, ON DELETE CASCADE)
* `subject_id` (INT UNSIGNED, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `day` / `start_time` / `end_time` / `room` (VARCHAR/TIME)

### `college_enrollments`
Official bridge table linking enrolled college students to their active subjects.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `application_id` (INT UNSIGNED, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `subject_id` (INT UNSIGNED, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `section_id` (INT UNSIGNED, FK $\rightarrow$ `college_sections.id`, ON DELETE CASCADE)
* `created_at` (TIMESTAMP)

### Parallel SHS Tables:
- `shs_strands`: Senior High School tracks/strands (STEM, ABM, HUMSS, TVL).
- `shs_curricula`: Versioned blueprints for SHS strands.
- `shs_curriculum_subjects`: Required subjects mapped by Grade 11/12 and semester.
- `shs_sections`: Timetabled sections for SHS (e.g. 'STEM 11-A').
- `shs_section_subjects`: Subject schedule and room mappings for SHS sections.
- `shs_enrollments`: Official bridge table linking SHS students to enrolled subjects.

---

## 4. Finance & Scholarship Tables

### `fee_templates`
Standard tuition and fee pricing blueprints.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `academic_level` (ENUM('College','Senior High School'))
* `program_or_strand` (VARCHAR(100), NOT NULL)
* `year_level` (VARCHAR(50))
* `tuition_fee` (DECIMAL(10,2), NOT NULL): Flat fee or Rate per Unit.
* `is_per_unit` (TINYINT(1), NOT NULL, DEFAULT 0): `1` = multiply by enrolled units, `0` = static flat total.
* `registration_fee` / `misc_fee` / `lab_fee` / `other_fees` (DECIMAL(10,2), DEFAULT 0.00)
* `created_at` / `updated_at` (TIMESTAMP)

### `student_assessments`
Finalized financial assessment statement per student term.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `application_id` (INT UNSIGNED, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `fee_template_id` (INT UNSIGNED, FK $\rightarrow$ `fee_templates.id`, ON DELETE SET NULL)
* `total_units` (INT, DEFAULT 0)
* `total_tuition` / `total_misc` / `total_discount` / `net_amount` (DECIMAL(10,2))
* `status` (ENUM('unpaid','partial','paid'), DEFAULT 'unpaid')
* `created_at` / `updated_at` (TIMESTAMP)

### `payment_records`
Transaction ledger and proof of payment records.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `application_id` (INT UNSIGNED, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `assessment_id` (INT UNSIGNED, FK $\rightarrow$ `student_assessments.id`, ON DELETE CASCADE)
* `or_number` (VARCHAR(50), UNIQUE, NULL): Official receipt number.
* `amount_paid` (DECIMAL(10,2), NOT NULL)
* `payment_method` (ENUM('cash','bank_transfer','gcash','online'), DEFAULT 'cash')
* `reference_number` (VARCHAR(100), NULL)
* `proof_image` (VARCHAR(255), NULL): Uploaded bank transfer receipt slip.
* `status` (ENUM('pending','verified','rejected'), DEFAULT 'verified')
* `verified_by` (INT UNSIGNED, FK $\rightarrow$ `users.id`, ON DELETE SET NULL)
* `created_at` (TIMESTAMP)

### `scholarships` & `scholarship_applications` & `student_scholarships` & `scholarship_recipients`
Manage scholarship programs, grant applications, recipient lists, and discount percentages/fixed deductions.

---

## 5. Learning Management System (LMS) Tables

### `lms_courses`
Active course instances mapped to enrolled subjects.
* `id` (INT UNSIGNED, PK, AUTO_INC)
* `subject_id` (INT UNSIGNED, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `college_section_id` (INT UNSIGNED, NULL, FK $\rightarrow$ `college_sections.id`)
* `teacher_id` (INT UNSIGNED, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `thumbnail_path` / `welcome_message` (VARCHAR/TEXT, NULL)
* `is_published` (TINYINT(1), DEFAULT 1)
* `created_at` / `updated_at` (TIMESTAMP)

### `lms_modules` & `lms_materials`
Weekly learning modules and downloadable file attachments.

### `lms_assignments` & `lms_submissions`
Assignment instructions, due dates, max points, student file uploads, submission timestamps, faculty scores, and feedback comments.

### `lms_quizzes`, `lms_questions`, `lms_question_choices`, `lms_quiz_attempts`, `lms_quiz_answers`
Timed quizzes, question authoring, multiple-choice options, student timed attempts, selected answers, and automated scoring results.

### `lms_attendance_sessions` & `lms_attendance_records`
Faculty attendance session logs and individual student present/absent statuses.

### `lms_announcements`
Course-level announcement broadcasts.

---
**Related:**
- [[Database Overview]]
- [[Users Table]]
- [[Applications Table]]
