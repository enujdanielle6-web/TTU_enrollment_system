# Users Table

**Table Name**: `users`  
**Storage Engine**: InnoDB (`utf8mb4_unicode_ci`)  
**Primary Key**: `id` (INT Unsigned, Auto Increment)

The `users` table is the central identity store for all system actors (Applicants, Enrolled Students, Faculty, Admissions, Registrar, Cashiers, Clinic Officers, Schedulers, and Superadmins).

---

## 1. Column Specifications

| Column | Data Type | Nullable | Default | Description & Business Meaning |
|---|---|---|---|---|
| `id` | INT(10) UNSIGNED | NO | *AUTO_INC* | Unique surrogate primary key. |
| `first_name` | VARCHAR(100) | NO | *None* | User's given name. |
| `last_name` | VARCHAR(100) | NO | *None* | User's surname. |
| `email` | VARCHAR(255) | NO | *None* | Unique primary email address used for login and notifications (**UNIQUE INDEX**). |
| `password` | VARCHAR(255) | NO | *None* | Standard Bcrypt password hash (`password_hash()`). |
| `role` | ENUM | NO | `'applicant'` | System role: `'applicant'`, `'admin'`, `'superadmin'`, `'admissions'`, `'scholarship'`, `'cashier'`, `'clinic'`, `'faculty'`, `'scheduler'`. |
| `student_number` | VARCHAR(50) | YES | `NULL` | Institutional student ID (e.g. `2026-000003`) or faculty employee ID (**UNIQUE INDEX**). |
| `department` | VARCHAR(100) | YES | `'None'` | Administrative department assignment (e.g., `'Admissions'`, `'Registrar'`, `'Finance'`). |
| `permissions` | LONGTEXT / JSON | YES | `NULL` | JSON array of granular permission strings (e.g., `["view_reports","edit_fees"]`). |
| `college_curriculum_id`| INT(10) UNSIGNED | YES | `NULL` | Optional FK linking the user to their locked curriculum version. |
| `email_verified` | TINYINT(1) | NO | `1` | `0` = Unverified (must verify OTP); `1` = Email verified. |
| `verification_code` | VARCHAR(10) | YES | `NULL` | Active 6-digit random verification code (e.g. `'582914'`). Cleared on successful verification. |
| `verification_expires_at`| DATETIME | YES | `NULL` | Expiration timestamp for the active OTP code (15 minutes from issue). |
| `is_active` | TINYINT(1) | NO | `1` | `1` = Active account; `0` = Suspended/Deactivated. |
| `last_login_at` | TIMESTAMP | YES | `NULL` | Timestamp of the most recent successful authentication. |
| `created_at` | TIMESTAMP | NO | `CURRENT_TIMESTAMP` | Account creation timestamp. |
| `updated_at` | TIMESTAMP | NO | `CURRENT_TIMESTAMP ON UPDATE` | Record update timestamp. |

---

## 2. Key Indexes & Constraints
- **Primary Key:** `PRIMARY KEY (id)`
- **Unique Indexes:**
  - `idx_users_email (email)`
  - `idx_users_student_number (student_number)`
- **Standard Indexes:**
  - `idx_users_role (role)`
  - `idx_users_is_active (is_active)`
  - `idx_users_email_verified (email_verified)`

---

## 3. Relationships
- **Has Many:** `applications` (`applications.user_id` $\rightarrow$ `users.id`)
- **Has Many:** `activity_logs` (`activity_logs.user_id` $\rightarrow$ `users.id`)
- **Has Many:** `health_records` (`health_records.user_id` $\rightarrow$ `users.id`)
- **Has Many:** `lms_courses` (as instructor: `lms_courses.teacher_id` $\rightarrow$ `users.id`)
- **Has Many:** `lms_submissions` (`lms_submissions.student_id` $\rightarrow$ `users.id`)
- **Has Many:** `lms_quiz_attempts` (`lms_quiz_attempts.student_id` $\rightarrow$ `users.id`)

---
**Related:**
- [[Database Overview]]
- [[Data Dictionary]]
- [[Authentication & Email Verification]]
- [[Applications Table]]
