# Database Data Dictionary

This document serves as the authoritative, verified technical data dictionary for all **42 database tables and views** in the TTU database (`sia`).

---

## 1. Identity, Security & System Core Tables

### `users`
Central identity repository for all institutional accounts.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `first_name` (VARCHAR(100), NOT NULL)
* `last_name` (VARCHAR(100), NOT NULL)
* `email` (VARCHAR(255), UNIQUE, NOT NULL): Primary login and verification email.
* `ttu_email` (VARCHAR(255), NULL): Institutional university email (`first.last@ttu.edu.ph`).
* `password` (VARCHAR(255), NOT NULL): Bcrypt password hash.
* `student_number` (VARCHAR(50), UNIQUE, NULL): Official Student ID (`YYYY-XXXXXX`) or Faculty Employee ID.
* `role` (ENUM('superadmin','admin','admissions','scholarship','cashier','clinic','faculty','scheduler','applicant','student'), NOT NULL, DEFAULT 'applicant')
* `department` (VARCHAR(100), NULL, DEFAULT 'None'): Department assignment.
* `permissions` (LONGTEXT / JSON, NULL): Granular permissions array.
* `college_curriculum_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `college_curricula.id`): Permanently assigned curriculum version.
* `email_verified` (TINYINT(1), NOT NULL, DEFAULT 0): `0` = unverified OTP, `1` = verified.
* `verification_code` (VARCHAR(10), NULL): 6-digit email OTP verification code.
* `verification_code_expires_at` (DATETIME, NULL): 15-minute OTP expiry timestamp.
* `reset_token` (VARCHAR(255), NULL): 6-digit password reset OTP.
* `reset_token_expires_at` (DATETIME, NULL): Password reset token expiry.
* `force_password_reset` (TINYINT(1), NOT NULL, DEFAULT 0): Forces student password change on initial login.
* `is_active` (TINYINT(1), NOT NULL, DEFAULT 1): Account activation state.
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `activity_logs`
Immutable institutional audit trail capturing critical mutations.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `user_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `users.id`, ON DELETE SET NULL)
* `ip_address` (VARCHAR(45), NULL): Client IPv4/IPv6 address.
* `affected_record` (VARCHAR(255), NULL): E.g., `Application #12`, `Payment Record #4`.
* `icon` (VARCHAR(50), NULL, DEFAULT 'bi-info-circle'): Bootstrap Icon class.
* `title` (VARCHAR(255), NOT NULL): Action title (e.g., 'Application Approved', 'Payment Recorded').
* `description` (TEXT, NULL): Detailed description or comment.
* `old_value` (LONGTEXT / JSON, NULL): Snapshot before change.
* `new_value` (LONGTEXT / JSON, NULL): Snapshot after change.
* `created_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `login_attempts`
Brute-force authentication throttling log.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `ip_address` (VARCHAR(45), NOT NULL)
* `email` (VARCHAR(255), NOT NULL)
* `attempted_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `system_settings`
Global key-value configuration dictionary.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `setting_key` (VARCHAR(100), UNIQUE, NOT NULL): E.g., `active_school_year`, `enrollment_status`, `college_cost_per_unit`.
* `setting_value` (TEXT, NULL)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `announcements`
Institutional broadcast notices.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `badge_label` (VARCHAR(50), NOT NULL)
* `badge_color` (VARCHAR(20), NOT NULL, DEFAULT 'primary')
* `title` (VARCHAR(255), NOT NULL)
* `content` (TEXT, NOT NULL)
* `is_active` (TINYINT(1), NOT NULL, DEFAULT 1)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

---

## 2. Admissions, Applications & Medical Tables

### `applications`
Primary application and enrollment term record.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `user_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `reference_number` (VARCHAR(50), UNIQUE, NOT NULL): E.g., `APP-2026-0001`.
* `academic_level` (ENUM('Senior High School','College'), NULL)
* `grade_level` (VARCHAR(50), NOT NULL)
* `school_year` (VARCHAR(50), NOT NULL)
* `semester` (VARCHAR(50), NULL)
* `student_type` (VARCHAR(50), NOT NULL, DEFAULT 'Regular'): Freshman, Transferee, Regular, Irregular.
* `strand` (VARCHAR(100), NULL): Selected academic program code or strand code.
* `nstp` (VARCHAR(50), NULL): CWTS, ROTC, LTS.
* `section_id` (INT(10) UNSIGNED, NULL): Assigned section ID.
* `college_curriculum_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `college_curricula.id`)
* `status` (ENUM('pending','under_review','correction_required','approved','rejected','enrolled'), NOT NULL, DEFAULT 'pending')
* `document_submission_method` (ENUM('online','on_campus'), NOT NULL, DEFAULT 'online')
* `admin_feedback` (TEXT, NULL): Feedback sent to applicant for corrections.
* `internal_notes` (TEXT, NULL): Confidential notes for admissions staff.
* Demographic & Contact Fields: `contact_number`, `telephone_number`, `birth_date`, `gender`, `civil_status`, `nationality`, `religion`, `place_of_birth`, address fields (`address_house_number`, `address_street`, `address_barangay`, `address_city`, `address_province`, `address_zip`, `address`), parent/guardian fields, previous education fields, `lrn` (12-digit LRN), emergency contact fields.
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `application_documents`
Uploaded requirement files for applicant admission review.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `application_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `document_name` (VARCHAR(100), NOT NULL): E.g., `Form 138 (Report Card)`, `PSA Birth Certificate`, `Certificate of Good Moral Character`, `2x2 ID Picture`, `Transcript of Records (TOR)`.
* `file_path` (VARCHAR(255), NOT NULL): Stored filename under `uploads/documents/`.
* `status` (ENUM('pending','verified','rejected'), NOT NULL, DEFAULT 'pending')
* `feedback` (TEXT, NULL): Admissions audit comment.
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `health_records`
Clinic medical profile and health clearance records.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `user_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `application_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* Physical Measurements: `height`, `weight`, `blood_type` (A+, A-, B+, B-, AB+, AB-, O+, O-, Unknown).
* Medical Boolean Flags: `has_allergies`, `has_asthma`, `has_diabetes`, `has_hypertension`, `has_heart_disease`, `has_physical_disability`, `has_existing_condition`, `has_previous_surgery`, `has_maintenance_medication`, `has_hospitalized`.
* Medical Detail Text: `medical_conditions`, `allergies_details`, `current_medications`, `other_notes`.
* Emergency Contact: `emergency_name`, `emergency_relationship`, `emergency_contact`.
* `status` (ENUM('pending','under_review','correction_required','verified','rejected'), NOT NULL, DEFAULT 'pending')
* `admin_remarks` (TEXT, NULL): Clinic physician/nurse remarks.
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

---

## 3. Academic Structure, Curricula & Class Sections

### `subjects`
Master catalog of all teachable courses.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `subject_code` (VARCHAR(50), UNIQUE, NOT NULL): E.g., `CS101`, `MATH1`, `ENG1`.
* `subject_name` (VARCHAR(150), NOT NULL)
* `units` (INT(11), NOT NULL, DEFAULT 3)
* `subject_type` (VARCHAR(50), NOT NULL, DEFAULT 'Lecture'): Lecture, Laboratory.
* `description` (TEXT, NULL)
* `education_level` (ENUM('SHS','College','Both'), NOT NULL, DEFAULT 'College')
* `status` (TINYINT(1), NOT NULL, DEFAULT 1)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `college_programs`
Undergraduate degree programs.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `code` (VARCHAR(50), UNIQUE, NOT NULL): E.g., `bscs`, `bsit`, `bshm`.
* `name` (VARCHAR(255), NOT NULL)
* `description` (TEXT, NULL)
* `is_active` (TINYINT(1), NOT NULL, DEFAULT 1)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `college_curricula`
Versioned blueprints for college degree programs.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `program_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `college_programs.id`, ON DELETE CASCADE)
* `curriculum_name` (VARCHAR(150), NOT NULL)
* `version` (VARCHAR(50), NOT NULL, DEFAULT '1.0')
* `effective_academic_year` (VARCHAR(50), NOT NULL): E.g., `2026-2027`.
* `description` (TEXT, NULL)
* `status` (ENUM('active','inactive','draft'), NOT NULL, DEFAULT 'active')
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `college_curriculum_subjects`
Maps subjects to year level and semester in college curricula.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `curriculum_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `college_curricula.id`, ON DELETE CASCADE)
* `subject_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `year_level` (VARCHAR(50), NOT NULL): `1st Year`, `2nd Year`, `3rd Year`, `4th Year`.
* `semester` (VARCHAR(50), NOT NULL): `First`, `Second`, `Summer`.
* `display_order` (INT(11), NOT NULL, DEFAULT 0)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `college_sections`
Timetabled class section blocks for college students.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `section_code` (VARCHAR(50), UNIQUE, NOT NULL): E.g., `BSCS 1-A`.
* `program_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `college_programs.id`, ON DELETE CASCADE)
* `curriculum_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `college_curricula.id`, ON DELETE SET NULL)
* `academic_year` (VARCHAR(50), NULL)
* `year_level` (VARCHAR(50), NOT NULL)
* `semester` (VARCHAR(50), NULL)
* `capacity` (INT(11), NOT NULL, DEFAULT 40)
* `schedule_type` (VARCHAR(50), NOT NULL, DEFAULT 'Morning')
* `adviser` (VARCHAR(150), NULL)
* `status` (TINYINT(1), NOT NULL, DEFAULT 1)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `college_section_subjects`
Scheduled subject offerings per college section.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `college_section_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `college_sections.id`, ON DELETE CASCADE)
* `subject_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `capacity` (INT(11), NOT NULL, DEFAULT 40)
* `day` (VARCHAR(20), NOT NULL, DEFAULT 'TBA')
* `start_time` (TIME, NOT NULL, DEFAULT '00:00:00')
* `end_time` (TIME, NOT NULL, DEFAULT '00:00:00')
* `room` (VARCHAR(50), NULL)
* `instructor` (VARCHAR(150), NULL)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `college_enrollments`
Official subject enrollment bridge for College students.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `application_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `subject_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `college_section_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `college_sections.id`, ON DELETE SET NULL)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### Parallel Senior High School (SHS) Tables
- **`shs_strands`**: `id`, `code` (UNIQUE), `name`, `description`, `is_active`, `created_at`, `updated_at`.
- **`shs_curricula`**: `id`, `strand_id` (FK $\rightarrow$ `shs_strands.id`), `curriculum_name`, `version`, `effective_academic_year`, `description`, `status` (`active`,`inactive`,`draft`), `created_at`, `updated_at`.
- **`shs_curriculum_subjects`**: `id`, `curriculum_id` (FK $\rightarrow$ `shs_curricula.id`), `subject_id` (FK $\rightarrow$ `subjects.id`), `grade_level` (`Grade 11`,`Grade 12`), `semester` (`First`,`Second`), `created_at`, `updated_at`.
- **`shs_sections`**: `id`, `section_code` (UNIQUE), `strand_id` (FK $\rightarrow$ `shs_strands.id`), `curriculum_id` (FK $\rightarrow$ `shs_curricula.id`), `grade_level`, `academic_year`, `capacity`, `schedule_type`, `adviser`, `status`, `created_at`, `updated_at`.
- **`shs_section_subjects`**: `id`, `shs_section_id` (FK $\rightarrow$ `shs_sections.id`), `subject_id` (FK $\rightarrow$ `subjects.id`), `capacity`, `day`, `start_time`, `end_time`, `room`, `instructor`, `created_at`, `updated_at`.
- **`shs_enrollments`**: `id`, `application_id` (FK $\rightarrow$ `applications.id`), `subject_id` (FK $\rightarrow$ `subjects.id`), `shs_section_id` (FK $\rightarrow$ `shs_sections.id`), `created_at`, `updated_at`.
- **`student_academic_records_view`**: Database View synthesizing student enrollment details, total units, section codes, and academic levels for reporting.

---

## 4. Finance & Scholarship Tables

### `fee_templates`
Tuition and institutional fee templates.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `name` (VARCHAR(100), NOT NULL)
* `academic_level` (ENUM('Senior High School','College'), NULL)
* `grade_level` (VARCHAR(50), NOT NULL)
* `strand` (VARCHAR(50), NULL)
* `semester` (ENUM('First','Second','Summer'), NULL): Scopes fee template to specific terms for College.
* `is_per_unit` (TINYINT(1), NOT NULL, DEFAULT 0): `1` = `tuition_fee` is rate-per-unit; `0` = flat fee.
* `tuition_fee` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `miscellaneous_fee` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `registration_fee` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `laboratory_fee` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `other_fees` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `total_amount` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `student_assessments`
Finalized financial billing ledger per application term.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `user_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `application_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `applications.id`, ON DELETE CASCADE)
* `fee_template_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `fee_templates.id`, ON DELETE SET NULL)
* `scholarship_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `scholarships.id`, ON DELETE SET NULL)
* `tuition_fee` / `miscellaneous_fee` / `registration_fee` / `laboratory_fee` / `other_fees` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `total_amount` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `discount_amount` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `net_amount` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `total_paid` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `payment_status` (ENUM('unpaid','partial','paid'), NOT NULL, DEFAULT 'unpaid')
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `payment_records`
Transaction records for over-the-counter payments and online bank proofs.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `assessment_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `student_assessments.id`, ON DELETE CASCADE)
* `user_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `cashier_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `users.id`, ON DELETE SET NULL)
* `amount` (DECIMAL(10,2), NOT NULL)
* `payment_date` (DATE, NOT NULL)
* `payment_method` (VARCHAR(50), NOT NULL): `Cash`, `GCash`, `Bank Transfer`.
* `receipt_number` (VARCHAR(50), NULL): Generated receipt ID (`REC-YYYYMMDD-XXXX`).
* `reference_number` (VARCHAR(100), NULL): Bank reference number.
* `proof_image` (VARCHAR(255), NULL): Uploaded receipt proof image.
* `status` (ENUM('pending','verified','rejected'), NOT NULL, DEFAULT 'pending')
* `remarks` (TEXT, NULL): Cashier remarks or rejection reasons.
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `scholarships`
Institutional and external financial grant definitions.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `name` (VARCHAR(150), NOT NULL)
* `code` (VARCHAR(50), UNIQUE, NOT NULL)
* `category` (ENUM('School-Based','Government','Private'), NOT NULL, DEFAULT 'School-Based')
* `provider` (VARCHAR(150), NULL)
* `program_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `college_programs.id`, ON DELETE SET NULL)
* `year_level` (VARCHAR(50), NULL)
* `min_gwa` (DECIMAL(4,2), NULL)
* `income_requirement` (DECIMAL(10,2), NULL)
* `slots` (INT(11), NULL)
* `tuition_coverage_type` (ENUM('percentage','fixed'), NOT NULL, DEFAULT 'percentage')
* `tuition_coverage_value` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `misc_coverage_type` (ENUM('percentage','fixed'), NOT NULL, DEFAULT 'percentage')
* `misc_coverage_value` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `stipend_amount` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `book_allowance` (DECIMAL(10,2), NOT NULL, DEFAULT 0.00)
* `description` / `requirements` (TEXT, NULL)
* `application_start` / `application_end` (DATE, NULL)
* `status` (ENUM('Active','Inactive','Draft'), NOT NULL, DEFAULT 'Active')
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `scholarship_applications`
Applicant submissions requesting financial aid.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `user_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `scholarship_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `scholarships.id`, ON DELETE CASCADE)
* `academic_year_id` (VARCHAR(50), NULL)
* `semester` (VARCHAR(50), NULL)
* `status` (ENUM('pending','under_review','approved','rejected'), NOT NULL, DEFAULT 'pending')
* `submitted_documents` (LONGTEXT / JSON, NULL)
* `admin_feedback` (TEXT, NULL)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `scholarship_recipients`
Active awardees receiving scholarship deductions per term.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `user_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `scholarship_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `scholarships.id`, ON DELETE CASCADE)
* `academic_year_id` (VARCHAR(50), NOT NULL)
* `semester` (VARCHAR(50), NOT NULL)
* `status` (VARCHAR(50), NOT NULL, DEFAULT 'Active')
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `student_scholarships`
Legacy student-to-scholarship association table.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `user_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `users.id`, ON DELETE CASCADE)
* `scholarship_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `scholarships.id`, ON DELETE CASCADE)
* `academic_year` (VARCHAR(50), NOT NULL)
* `semester` (VARCHAR(50), NOT NULL)
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

---

## 5. Learning Management System (LMS) Tables

### `lms_courses`
Active course instances mapped to enrolled subjects.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `subject_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `subjects.id`, ON DELETE CASCADE)
* `section_id` (INT(10) UNSIGNED, NULL)
* `academic_level` (ENUM('College','Senior High School'), NOT NULL, DEFAULT 'College')
* `instructor_id` (INT(10) UNSIGNED, NULL, FK $\rightarrow$ `users.id`, ON DELETE SET NULL)
* `course_code` (VARCHAR(100), UNIQUE, NOT NULL): E.g., `CS101-BSCS1A`.
* `course_name` (VARCHAR(255), NOT NULL)
* `term` (VARCHAR(50), NOT NULL, DEFAULT '1st Semester')
* `academic_year` (VARCHAR(50), NOT NULL, DEFAULT '2026-2027')
* `status` (ENUM('active','archived'), NOT NULL, DEFAULT 'active')
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `lms_modules`
Course syllabus units and chapters.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `lms_course_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `lms_courses.id`, ON DELETE CASCADE)
* `title` (VARCHAR(255), NOT NULL)
* `description` (TEXT, NULL)
* `order_index` (INT(11), NOT NULL, DEFAULT 1)
* `status` (ENUM('draft','published'), NOT NULL, DEFAULT 'published')
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `lms_materials`
Downloadable lecture files and learning assets.
* `id` (INT(10) UNSIGNED, PK, AUTO_INC)
* `lms_module_id` (INT(10) UNSIGNED, NOT NULL, FK $\rightarrow$ `lms_modules.id`, ON DELETE CASCADE)
* `title` (VARCHAR(255), NOT NULL)
* `description` (TEXT, NULL)
* `file_path` (VARCHAR(255), NOT NULL)
* `file_type` (VARCHAR(50), NOT NULL, DEFAULT 'pdf')
* `status` (ENUM('draft','published'), NOT NULL, DEFAULT 'published')
* `created_at` / `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)

### `lms_assignments` & `lms_submissions`
- **`lms_assignments`**: `id`, `lms_course_id` (FK $\rightarrow$ `lms_courses.id`), `title`, `description`, `due_date`, `max_points`, `file_path`, `status`, `created_at`, `updated_at`.
- **`lms_submissions`**: `id`, `lms_assignment_id` (FK $\rightarrow$ `lms_assignments.id`), `user_id` (FK $\rightarrow$ `users.id`), `file_path`, `submission_text`, `grade` (DECIMAL(5,2)), `feedback`, `submitted_at`, `graded_at`.

### `lms_quizzes`, `lms_questions`, `lms_question_choices`, `lms_quiz_attempts`, `lms_quiz_answers`
- **`lms_quizzes`**: `id`, `lms_course_id` (FK $\rightarrow$ `lms_courses.id`), `title`, `description`, `time_limit_minutes`, `passing_score`, `max_attempts`, `due_date`, `status`, `created_at`, `updated_at`.
- **`lms_questions`**: `id`, `lms_quiz_id` (FK $\rightarrow$ `lms_quizzes.id`), `question_text`, `question_type` (`multiple_choice`,`true_false`,`essay`), `points`, `order_index`, `created_at`.
- **`lms_question_choices`**: `id`, `lms_question_id` (FK $\rightarrow$ `lms_questions.id`), `choice_text`, `is_correct` (TINYINT(1)), `order_index`.
- **`lms_quiz_attempts`**: `id`, `lms_quiz_id` (FK $\rightarrow$ `lms_quizzes.id`), `user_id` (FK $\rightarrow$ `users.id`), `attempt_number`, `score`, `total_points`, `passed` (TINYINT(1)), `started_at`, `completed_at`.
- **`lms_quiz_answers`**: `id`, `lms_quiz_attempt_id` (FK $\rightarrow$ `lms_quiz_attempts.id`), `lms_question_id` (FK $\rightarrow$ `lms_questions.id`), `selected_choice_id`, `text_answer`, `is_correct`, `points_awarded`.

### `lms_attendance_sessions` & `lms_attendance_records`
- **`lms_attendance_sessions`**: `id`, `lms_course_id` (FK $\rightarrow$ `lms_courses.id`), `session_date`, `title`, `created_at`.
- **`lms_attendance_records`**: `id`, `lms_attendance_session_id` (FK $\rightarrow$ `lms_attendance_sessions.id`), `user_id` (FK $\rightarrow$ `users.id`), `status` (`present`,`late`,`absent`,`excused`), `remarks`, `created_at`, `updated_at`.

### `lms_announcements`
- **`lms_announcements`**: `id`, `lms_course_id` (FK $\rightarrow$ `lms_courses.id`), `author_id` (FK $\rightarrow$ `users.id`), `title`, `content`, `created_at`, `updated_at`.

---
**Related:**
- [[Database Overview]]
- [[Users Table]]
- [[Applications Table]]
- [[Curriculum Architecture]]
