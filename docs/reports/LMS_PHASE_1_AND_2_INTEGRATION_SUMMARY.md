# TTU LMS INTEGRATION & REBUILD: PHASES 1 & 2 TECHNICAL HANDOFF SPECIFICATION

> **Subsystem**: Learning Management System (LMS) & Enrollment System Integration  
> **Repository Root**: `c:\xampp\htdocs\sia`  
> **Status**: Phase 1 & Phase 2 Fully Implemented, Verified, and Locked  
> **Verification Suites**:
> * Phase 1: [`scripts/tests/test_phase1_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase1_verification.php) — **14 / 14 Passed (100%)**
> * Phase 2: [`scripts/tests/test_phase2_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase2_verification.php) — **8 / 8 Passed (100%)**  
> **Next Target**: Phase 3 (Core Course Shell & Material Architecture) — *Do NOT start until directed.*

---

## 1. PURPOSE & AGENT CONTEXT FOR FUTURE SESSIONS

This document is the **authoritative single-source technical handoff** for the ongoing rebuild and stabilization of the TTU Learning Management System (LMS) and its relationship with the TTU Enrollment System.

If you are an agent resuming work in a new conversation:
1. **Enrollment is the Academic Source of Truth**: The LMS never owns student enrollment records or course catalogs. It is a downstream learning experience dynamically derived from official enrollment.
2. **Hybrid MVC Architecture**: We follow the project's Fat Controller / Lightweight Repository pattern using Vanilla PHP, raw PDO queries, prepared statements, and Bootstrap 5 presentation views. SQL queries meant for business logic remain in controllers or domain services (`EnrollmentService`, `LmsService`, `LmsGradebookService`).
3. **No Unplanned Mutations**: Read operations (`GET` requests, course listing queries) must NEVER mutate database state (e.g. creating courses on page load is strictly forbidden).
4. **Non-Destructive Academic History**: Student academic activity (submissions, quiz attempts, attendance) must never be hard-deleted when students drop subjects or transfer sections.
5. **Always Run Regression Suites Before & After Modifying Code**:
   * `php scripts/tests/test_phase1_verification.php`
   * `php scripts/tests/test_phase2_verification.php`

---

## 2. SYSTEM ARCHITECTURE & HIGH-LEVEL DOMAIN RELATIONSHIP

```text
┌────────────────────────────────────────────────────────────────────────┐
│                      ENROLLMENT SUBSYSTEM                              │
│                    (Academic Source of Truth)                          │
├────────────────────────────────────────────────────────────────────────┤
│  • Academic Hierarchy: College & SHS Curricula, Programs & Strands     │
│  • Sections: `college_sections`, `shs_sections`                        │
│  • Subject Offerings & Schedules: `college_section_subjects`           │
│  • Student Admissions & Applications: `applications`                   │
│  • Official Course Enrollment Roster: `college_enrollments`,           │
│    `shs_enrollments` (states: 'enrolled', 'dropped', 'withdrawn')      │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼ Unidirectional Derivation
┌────────────────────────────────────────────────────────────────────────┐
│                         LMS SUBSYSTEM                                  │
│                 (Derived Learning Experience)                          │
├────────────────────────────────────────────────────────────────────────┤
│  • Course Shells: `lms_courses` (tied to section + subject)            │
│  • Learning Content: `lms_modules`, `lms_materials`                    │
│  • Assessments: `lms_assignments`, `lms_quizzes`                       │
│  • Student Activity: `lms_submissions`, `lms_quiz_attempts`            │
│  • Student Access: Dynamically resolved from active enrollment rows    │
│  • Gradebooks & Attendance: Filtered strictly to active enrollments    │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. PHASE 1 SUMMARY — LMS ARCHITECTURE & DATA INTEGRITY

Phase 1 resolved critical security, authentication, and architectural defects identified during the initial audit.

### 3.1 Key Defects Resolved in Phase 1
1. **Material Storage Path Disconnect**:
   * *Old behavior*: Faculty upload controller wrote to `c:\xampp\storage\lms_materials/`, while [`DownloadController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php) read from `app/uploads/lms/`, causing 100% of material downloads to fail with `HTTP 404`.
   * *Resolution*: Standardized canonical project storage: `storage/uploads/lms/materials/` and `storage/uploads/lms/submissions/`.
2. **Direct Webroot Asset Protection**:
   * Added `.htaccess` rule blocking direct HTTP requests to `storage/`:
     ```apache
     RewriteRule ^(app|config|database|storage)/ - [F,L]
     ```
   * All file access must stream through [`DownloadController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php) with course authorization checks.
3. **Session Role Authentication Bug**:
   * *Old behavior*: [`DownloadController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php) checked `$_SESSION['role']`, which was never set because the system sets `$_SESSION['user_role']`.
   * *Resolution*: Standardized role checks to `$_SESSION['user_role']` with fallback to `$_SESSION['role']`.
4. **Premature LMS Login by Unenrolled Applicants**:
   * *Old behavior*: Applicants with `applications.status = 'approved'` were allowed to log into the student LMS portal before paying tuition or being finalized by the Registrar.
   * *Resolution*: [`LmsAuthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php) strictly enforces `applications.status = 'enrolled'`.
5. **Missing Role Route Authorization Guards**:
   * *Old behavior*: Student and faculty LMS routes lacked middleware, allowing cross-role or unauthenticated access.
   * *Resolution*: Wrapped all student routes in `RoleMiddleware::allowOnly(['student'])` and all faculty routes in `RoleMiddleware::allowOnly(['faculty', 'admin', 'superadmin'])` in [`web.php`](file:///c:/xampp/htdocs/sia/app/Routes/web.php).
6. **Mutating Repositories (JIT Side-Effects on Read)**:
   * *Old behavior*: [`CollegeEnrollmentRepository::getActiveStudentCourses()`](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php) and [`ShsEnrollmentRepository`](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php) executed `INSERT INTO lms_courses` on read queries.
   * *Resolution*: Stripped all insert logic from repositories; read queries are now pure `SELECT` operations.
7. **Unassigned Course Shell Crash (TBA Instructor)**:
   * *Old behavior*: `lms_courses.faculty_user_id` was `NOT NULL`, causing course creation to fail when no instructor was assigned.
   * *Resolution*: Modified database column:
     ```sql
     ALTER TABLE lms_courses MODIFY faculty_user_id INT(10) UNSIGNED DEFAULT NULL;
     ```
   * Unassigned courses now cleanly render with instructor `"TBA"` on student dashboards, while preventing unauthorized instructors from claiming the course.

---

## 4. PHASE 2 SUMMARY — ENROLLMENT ↔ LMS LIFECYCLE INTEGRATION

Phase 2 established reliable, bi-directional lifecycle synchronization between the Enrollment System and LMS course access.

### 4.1 Key Defects Resolved in Phase 2
1. **Section Transfer Desynchronization (Task 2)**:
   * *Old behavior*: Updating `applications.section_id` in Admissions or Registrar did **not** update `college_enrollments.college_section_id` (or `shs_enrollments.shs_section_id`). Students remained tied to their previous section's LMS courses.
   * *Resolution*: Created [`EnrollmentService::transferSection()`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php). Inside a database transaction, it updates `applications.section_id`, updates all active rows in `college_enrollments` / `shs_enrollments`, and provisions LMS course shells for the new section.
2. **Destructive Subject Dropping & Orphaned Work (Task 3)**:
   * *Old behavior*: Dropping a subject either hard-deleted enrollment rows (destroying audit history) or left student submissions/attempts orphaned.
   * *Resolution*: Added lifecycle columns to `college_enrollments` and `shs_enrollments`:
     ```sql
     ADD COLUMN status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled',
     ADD COLUMN dropped_at TIMESTAMP NULL DEFAULT NULL;
     ```
   * Created [`EnrollmentService::dropSubject()`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php) and `withdrawSubject()`. Status is transitioned to `'dropped'`, `dropped_at` is timestamped, active LMS course access is revoked immediately, and the student is excluded from active gradebooks and attendance rosters, while all historical submissions and quiz attempts remain intact in the database.
3. **Faculty Reassignment & TBA Isolation (Task 4)**:
   * *Old behavior*: Reassigning a faculty member in the timetable left stale faculty IDs on `lms_courses`.
   * *Resolution*: Enhanced `LmsService::provisionCourseShell()` to update `faculty_user_id = VALUES(faculty_user_id)`. Reassigning Faculty A to Faculty B immediately revokes Faculty A's access and grants Faculty B access. Setting faculty to `NULL` converts the course to TBA.
4. **Irregular Student Disappearance from Course Counts (Task 5)**:
   * *Old behavior*: Faculty course counters queried `applications.section_id = lms_courses.academic_section_id`. Irregular students often have `applications.section_id = NULL` or cross-enroll across sections, causing them to vanish from faculty student counts.
   * *Resolution*: Re-engineered [`LmsService::getFacultyCourses()`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) to count active students directly from `college_enrollments` / `shs_enrollments` matching section and subject where `ce.status = 'enrolled' AND a.status = 'enrolled'`.
5. **Deterministic, Idempotent Provisioning (Task 1 & Task 6)**:
   * LMS course shells are created only at official milestones:
     * Section subject creation in Scheduler (`EnrollmentService::assignSectionSubjects`).
     * Student enrollment finalization in Registrar (`EnrollmentService::finalizeEnrollment`).
     * Section transfer execution (`EnrollmentService::transferSection`).
   * Backed by `UNIQUE KEY (academic_level, academic_section_id, subject_id)`. Repeated provisioning attempts return the same shell without creating duplicate records.
6. **Transaction Boundaries & Atomicity (Task 7)**:
   * Multi-table operations (`transferSection`, `dropSubject`, `finalizeEnrollment`) execute inside explicit PDO transactions (`$pdo->beginTransaction()`, `$pdo->commit()`, `$pdo->rollBack()`). Any failure reverts all tables to avoid partial or desynchronized state.

---

## 5. DATABASE SCHEMA INVENTORY & MIGRATIONS

### 5.1 Migrations Executed

#### 1. LMS Courses Faculty Nullability (Phase 1)
```sql
ALTER TABLE lms_courses MODIFY faculty_user_id INT(10) UNSIGNED DEFAULT NULL;
```

#### 2. Enrollment Lifecycle Columns (Phase 2)
* Script: [`database/migrations/add_enrollment_lifecycle_columns.php`](file:///c:/xampp/htdocs/sia/database/migrations/add_enrollment_lifecycle_columns.php)
```sql
-- college_enrollments
ALTER TABLE college_enrollments 
    ADD COLUMN status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled' AFTER college_section_id,
    ADD COLUMN dropped_at TIMESTAMP NULL DEFAULT NULL AFTER status,
    ADD INDEX idx_ce_status (status),
    ADD INDEX idx_ce_sec_status (college_section_id, status);

-- shs_enrollments
ALTER TABLE shs_enrollments 
    ADD COLUMN status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled' AFTER shs_section_id,
    ADD COLUMN dropped_at TIMESTAMP NULL DEFAULT NULL AFTER status,
    ADD INDEX idx_se_status (status),
    ADD INDEX idx_se_sec_status (shs_section_id, status);
```

### 5.2 Key Relational Tables Summary

| Table | Primary Role | Key Columns |
| :--- | :--- | :--- |
| `applications` | Student admission & enrollment lifecycle | `id`, `user_id`, `academic_level`, `section_id`, `status` (`'draft'`, `'submitted'`, `'approved'`, `'payment_verified'`, `'enrolled'`, `'rejected'`) |
| `college_enrollments` | Official subject enrollment (College) | `id`, `application_id`, `subject_id`, `college_section_id`, `status` (`'enrolled'`, `'dropped'`, `'withdrawn'`), `dropped_at` |
| `shs_enrollments` | Official subject enrollment (SHS) | `id`, `application_id`, `subject_id`, `shs_section_id`, `status` (`'enrolled'`, `'dropped'`, `'withdrawn'`), `dropped_at` |
| `college_sections` / `shs_sections` | Cohort sections | `id`, `program_id`, `section_name`, `year_level`, `semester` |
| `college_section_subjects` | Section timetable & faculty binding | `id`, `college_section_id`, `subject_id`, `faculty_user_id`, `day`, `start_time`, `end_time`, `room` |
| `lms_courses` | LMS Course Shell | `id`, `academic_level`, `academic_section_id`, `subject_id`, `faculty_user_id` (nullable), `status` |
| `lms_modules` | Course topic modules | `id`, `lms_course_id`, `title`, `display_order`, `is_published` |
| `lms_materials` | Learning files & documents | `id`, `lms_module_id`, `title`, `file_path`, `file_type`, `file_size`, `is_published` |
| `lms_assignments` | Graded assignments | `id`, `lms_course_id`, `title`, `max_score`, `due_date`, `is_published` |
| `lms_submissions` | Student assignment submissions | `id`, `lms_assignment_id`, `student_id` (references `users.id`), `file_path`, `grade`, `status` |
| `lms_quizzes` | Graded quizzes | `id`, `lms_course_id`, `title`, `time_limit_minutes`, `is_published` |
| `lms_quiz_attempts` | Student quiz attempts | `id`, `lms_quiz_id`, `student_id` (references `users.id`), `score`, `status` |

---

## 6. INVENTORY OF MODIFIED CODE FILES

### Application Layer
1. [`app/Services/EnrollmentService.php`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php)
   * `finalizeEnrollment()`: Deterministically provisions LMS course shells upon enrollment.
   * `assignSectionSubjects()`: Provisions LMS course shells upon subject assignment.
   * `transferSection()`: Atomic section transfer with PDO transaction synchronization.
   * `dropSubject()`: Non-destructive subject drop updating `status = 'dropped'` and `dropped_at = NOW()`.
   * `withdrawSubject()` / `restoreSubject()`: Lifecycle state transitions.
2. [`app/Services/LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php)
   * `provisionCourseShell()`: Idempotent UPSERT maintaining faculty assignment and status.
   * `getFacultyCourses()`: Enrolled student counting directly from official enrollment records.
   * `isFacultyAuthorizedForCourse()`: Multi-tenant ownership and admin oversight check.
   * `isStudentAuthorizedForCourse()`: Enrollment verification delegated to repositories.
3. [`app/Services/LmsGradebookService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php)
   * `getEnrolledStudents()`: Excludes dropped/withdrawn students using `status = 'enrolled'`.
   * `getCourseGradebook()`: Assembles assignments, quizzes, and grid roster.
4. [`app/Repositories/CollegeEnrollmentRepository.php`](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php)
   * `getActiveStudentCourses()`: Pure SELECT query filtering by `ce.status = 'enrolled' AND a.status = 'enrolled'`.
   * `isStudentAuthorizedForCourse()`: Checks active status in `college_enrollments`.
5. [`app/Repositories/ShsEnrollmentRepository.php`](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php)
   * `getActiveStudentCourses()`: Pure SELECT query filtering by `se.status = 'enrolled' AND a.status = 'enrolled'`.
   * `isStudentAuthorizedForCourse()`: Checks active status in `shs_enrollments`.
6. [`app/Controllers/Lms/DownloadController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php)
   * Resolves canonical storage `storage/uploads/lms/materials/` and `storage/uploads/lms/submissions/`.
   * Verifies `$_SESSION['user_role']` and calls authorization checks before streaming.
7. [`app/Controllers/Lms/FacultyController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php)
   * `uploadMaterial()` writes to canonical `storage/uploads/lms/materials/` with sanitized, randomized filenames.
8. [`app/Controllers/Lms/FacultyAttendanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAttendanceController.php)
   * Restricts attendance roster loading to `status = 'enrolled'`.
9. [`app/Controllers/Lms/LmsAuthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php)
   * Strictly enforces `applications.status = 'enrolled'`.
10. [`app/Controllers/Admin/Admissions/AdmissionsController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Admissions/AdmissionsController.php)
    * Directs section reassignments for enrolled students through `EnrollmentService::transferSection()`.
11. [`app/Controllers/Admin/Registrar/RegistrarController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php)
    * Added `transferSection()` and `dropSubject()` endpoints with CSRF protection.
12. [`app/Routes/web.php`](file:///c:/xampp/htdocs/sia/app/Routes/web.php)
    * Gated student LMS routes behind `RoleMiddleware::allowOnly(['student'])`.
    * Gated faculty LMS routes behind `RoleMiddleware::allowOnly(['faculty', 'admin', 'superadmin'])`.
    * Registered `/admin/registrar/transfer_section.php` and `/admin/registrar/drop_subject.php`.
13. [`.htaccess`](file:///c:/xampp/htdocs/sia/.htaccess)
    * Direct HTTP access to `storage/` directory denied (`403 Forbidden`).

---

## 7. AUTOMATED VERIFICATION TEST SUITES

Two automated test suites exist in the `scripts/` directory. Run them from PowerShell or Command Prompt:

### 7.1 Phase 1 Verification Suite
* **Command**: `php scripts/tests/test_phase1_verification.php`
* **Coverage**: 14 Scenarios (14 / 14 PASS)
  1. Enrolled student can log into LMS
  2. Approved-but-not-enrolled applicant rejected from LMS
  3. Student cannot access faculty routes (`RoleMiddleware`)
  4. Faculty cannot access student routes (`RoleMiddleware`)
  5. Cashier role rejected from both student and faculty LMS
  6. Faculty material written to canonical storage (`storage/uploads/lms/materials/`)
  7. Authorized student verified for course material download
  8. Unauthorized student rejected from downloading material
  9. Material storage path consistent within project
  10. Reading student courses does not INSERT into `lms_courses` (read immutability)
  11. Existing course shells remain functional
  12. Course with NULL `faculty_user_id` loads cleanly as TBA
  13. Section isolation prevents cross-section course access
  14. Faculty course isolation prevents unauthorized faculty cross-access

### 7.2 Phase 2 Verification Suite
* **Command**: `php scripts/tests/test_phase2_verification.php`
* **Coverage**: 8 Scenarios (8 / 8 PASS)
  1. **Scenario A (Normal Enrollment)**: Student enrolls $\rightarrow$ active LMS course shell resolved.
  2. **Scenario B (Section Transfer)**: Section 1 $\rightarrow$ Section 3: Old course revoked, new course granted, `college_enrollments` synchronized.
  3. **Scenario C (Subject Drop)**: Subject marked `'dropped'`, `dropped_at` set, LMS access revoked, student omitted from gradebook, historical work preserved.
  4. **Scenario D (Faculty Reassignment)**: Reassigned from Faculty 9 to Faculty 8: Faculty 8 gains access, Faculty 9 loses access, cleanly reverted.
  5. **Scenario E (Unassigned Faculty)**: Course shell with `faculty_user_id = NULL` renders as `"TBA"` for students; faculty access denied.
  6. **Scenario F (Irregular Student)**: Irregular student with `applications.section_id = NULL` accurately counted in faculty enrolled count and authorized.
  7. **Scenario G (Duplicate Prevention)**: 3 repeated calls to `provisionCourseShell()` return identical ID 1; row count stays 1.
  8. **Scenario H (Transaction Rollback)**: Invalid target section rejected; transaction rolls back leaving `applications` and `college_enrollments` untouched.

---

## 8. OBSIDIAN DOCUMENTATION SYNCHRONIZATION

Detailed architectural documentation was placed under [`docs/obsidian/`](file:///c:/xampp/htdocs/sia/docs/obsidian/):
1. [`docs/obsidian/LMS_PHASE_1_ARCHITECTURE_AND_INTEGRITY.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_PHASE_1_ARCHITECTURE_AND_INTEGRITY.md)
   * Deep dive into Phase 1 fixes: storage contracts, session keys, route guards, repository purity, and TBA course loading.
2. [`docs/obsidian/LMS_PHASE_2_LIFECYCLE_INTEGRATION.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_PHASE_2_LIFECYCLE_INTEGRATION.md)
   * Deep dive into Phase 2: academic source of truth, lifecycle state machine, atomic section transfer, drop/withdraw preservation, faculty reassignment, and irregular student resolution.
3. [`docs/obsidian/LMS_MASTER_DOCUMENTATION.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_MASTER_DOCUMENTATION.md)
   * Updated Section 8 audit table (Scenarios E and F resolved to PASS) and updated implementation roadmap status.
4. [`docs/obsidian/LMS_ENROLLMENT_INTEGRATION.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_ENROLLMENT_INTEGRATION.md)
   * Annotated Questions 3, 5, 7, and 8 with Phase 1 and Phase 2 resolution details.

---

## 9. TRANSITION TO PHASE 3 (WHAT TO DO NEXT)

When the user requests **Phase 3**, the system is in an optimal state to begin.

### Expected Scope of Phase 3 (Core Course Shell & Material Architecture):
1. **Course Shell Management**:
   * Administrative view of active course shells (`/admin/lms/courses.php`).
   * Manual override controls for Academic Deans/Registrars to reassign faculty or audit shells.
2. **Material Architecture & Streaming**:
   * Complete folder/module hierarchies within `lms_modules`.
   * Display order reordering and batch publishing.
   * Multi-file uploads and preview handlers (PDF in-browser streaming vs. forced downloads).
3. **Student LMS Experience Optimization**:
   * Student personal gradebook optimization ($O(1)$ query complexity instead of scanning full course gradebooks).
   * Course syllabus and announcement stream integration.

### Rules for Phase 3:
* **Preserve all Phase 1 & Phase 2 invariants**: Do not re-introduce read mutations, do not break section isolation, and do not bypass the `status = 'enrolled'` gate.
* **Keep running the test matrix**: Run `php scripts/tests/test_phase1_verification.php` and `php scripts/tests/test_phase2_verification.php` to guarantee zero regressions.
