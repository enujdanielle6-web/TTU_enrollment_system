# TRIPLE T UNIVERSITY (TTU) ENROLLMENT & LMS SYSTEM
## Exhaustive Technical Codebase Audit, Architectural Inspection, and Verification Report

**Audit Date:** September 13, 2026  
**Auditor:** Antigravity Principal Software Architecture & Security Agent  
**Target Codebase:** `C:\xampp\htdocs\sia`  
**System Scope:** TTU Enrollment System, TTU LMS, and Enrollment ↔ LMS Integration Layer  
**Target Artifact:** `system_audit.md`

---

## 1. Executive Summary

An exhaustive, verified technical audit of the entire Triple T University (TTU) Enrollment System and Learning Management System (LMS) codebase was conducted. The audit analyzed all architectural layers: Core Framework, Routing, Middleware pipeline, Repositories, Domain Services, Models, Controllers, Views, Database Schemas (`database/schema.sql`, migrations, seeds), and Obsidian documentation.

The codebase represents a **Hybrid MVC** pattern implemented in Vanilla PHP 8+ with modern routing, middleware chaining, and a Strangler Fig transition from procedural scripts to object-oriented controllers. While significant portions of the system exhibit sound engineering (such as strict MIME-type document validation in `DocumentController`, granular database indexing, and comprehensive curriculum lifecycle state machines), the audit identified **critical systemic defects** that break core production workflows:

1. **LMS Operational Deadlock:** Every state-changing form submission in the LMS portal (quizzes, assignments, module creation, materials, attendance, grading) fails with `403 Forbidden` due to the complete omission of CSRF tokens in LMS views despite the active enforcement of `CsrfMiddleware`.
2. **Universal LMS File Download Breakdown:** All students, faculty, and administrators are universally blocked with `403 Forbidden` when attempting to download course materials or assignment submissions due to a session variable naming mismatch (`$_SESSION['role']` vs. `$_SESSION['user_role']`).
3. **Storage Path Escape & Material 404s:** Course material uploads compute a directory path that escapes the project root into `c:\xampp\storage\lms_materials/`, while the download controller searches within `app/uploads/lms/`, causing all uploaded files to be inaccessible.
4. **Missing Role-Based Access Control on LMS Routes:** Both student and faculty route trees in `app/Routes/web.php` are lumped into a single middleware group that enforces only authentication, allowing students to access faculty dashboards, gradebooks, and attendance management.
5. **Scheduler ↔ LMS Integration Disconnect:** Academic section subjects store faculty as raw, unvalidated strings (`VARCHAR(150)`), while LMS courses require a foreign key to `users.id`. There is no synchronization between schedule assignments and LMS course allocations.
6. **Administrative Privilege Escalation:** Most admin controllers (`AdmissionsController`, `CollegeController`, `ShsController`, `SubjectController`, `FeeController`, `FinanceController::process`, and `ScholarshipController::process`) fail to call `requirePermission()`, allowing any administrative role (cashier, clinic, scheduler) to execute unauthorized administrative actions across departments.

---

## 2. Architecture Overview

### 2.1 Pattern & Entry Point
- **Pattern:** Hybrid MVC relying on "Fat Controllers" handling routing, validation, business rules, raw PDO SQL execution, and view rendering. Models act primarily as basic active record / data transfer structures.
- **Entry Point:** `public/index.php` initializes error handling, starts sessions, generates CSRF tokens, bootstraps the application container, loads helper functions (`app/Core/functions.php`), registers routes from `app/Routes/web.php`, and dispatches the `Request` through the `Router`.
- **Directory Structure:**
  - `app/Core/`: Router, Request, Response, Database, BaseController, HttpException.
  - `app/Middleware/`: SessionSecurityMiddleware, CsrfMiddleware, AuthMiddleware, RoleMiddleware.
  - `app/Controllers/`: Grouped into `Admin/` (Admissions, Clinic, Finance, Registrar, Scheduler, Scholarship, System, LMS Admin), `Lms/` (Student, Faculty, Assignments, Quizzes, Gradebook, Attendance, Downloads), and root controllers (`AuthController`, `ApplicantController`, `DocumentController`, `EnrollController`, `HealthController`, `HomeController`).
  - `app/Services/`: AssessmentService, EnrollmentService, StudentNumberService, LmsService, LmsQuizService, LmsGradebookService, LmsCalendarService.
  - `app/Repositories/`: EnrollmentRepositoryInterface, CollegeEnrollmentRepository, ShsEnrollmentRepository.
  - `app/Views/`: Presentation templates organized by domain.
  - `database/`: `schema.sql` (canonical 42 tables/views), `seed.sql`, and migrations.

### 2.2 Middleware Pipeline
The router supports onion-style middleware execution:
```
Request → SessionSecurityMiddleware → CsrfMiddleware → AuthMiddleware → RoleMiddleware → Controller Action → Response
```
- **SessionSecurityMiddleware:** Enforces IP consistency, User-Agent consistency, and session timeouts (1800s inactive, 14400s absolute).
- **CsrfMiddleware:** Intercepts `POST`, `PUT`, `DELETE` methods and validates `csrf_token` against `$_SESSION['csrf_token']`.
- **AuthMiddleware:** Validates `!empty($_SESSION['logged_in'])`.
- **RoleMiddleware:** Verifies allowed user roles. Configured with a special `'admin'` alias that permits `['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic', 'scheduler']`.

---

## 3. Enrollment System Audit

### 3.1 Authentication & Session Management
- **Implementation:** `app/Controllers/AuthController.php`.
- **Strengths:** Password hashing via `PASSWORD_DEFAULT` (Bcrypt), session regeneration on login (`session_regenerate_id(true)`), brute force lockout tracking in `login_attempts` table, email verification OTP with expiration timestamps, secure password strength validation via `isPasswordStrong()`.
- **Flaws:**
  - `AuthController::login` sets `$_SESSION['user_role']`, but legacy scripts and LMS controllers expect `$_SESSION['role']` or `$_SESSION['lms_role']`.

### 3.2 Role-Based Access Control (RBAC)
- **Implementation:** `app/Core/functions.php` (`hasPermission()`, `requirePermission()`) and `app/Middleware/RoleMiddleware.php`.
- **Flaw:** While granular permissions are defined in `users.permissions` (e.g., `fees.manage`, `applications.review`, `payments.record`), `app/Routes/web.php` guards the entire `/sia/admin/*` tree with `RoleMiddleware:admin`. Controllers fail to enforce granular permissions internally:
  - `AdmissionsController.php` (all methods): 0 calls to `requirePermission()`.
  - `SubjectController.php` (all methods): 0 calls to `requirePermission()`.
  - `CollegeController.php` & `ShsController.php` (all methods): 0 calls to `requirePermission()`.
  - `FeeController.php` (all methods): 0 calls to `requirePermission()`.
  - `FinanceController.php` (`assessment`, `payments`, `process`): 0 calls to `requirePermission()`.
  - `ScholarshipController.php` (`process`): 0 calls to `requirePermission()`.
  - `LmsAdminController.php` (all methods): 0 calls to `requirePermission()`.

### 3.3 Admissions & Document Processing
- **Implementation:** `app/Controllers/Admin/Admissions/AdmissionsController.php` and `app/Controllers/DocumentController.php`.
- **Strengths:** `DocumentController` utilizes strict extension and MIME-type validation via `finfo` against a strict whitelist (`pdf`, `jpg`, `jpeg`, `png`). File sizes are capped at 5MB. Upload paths are obfuscated.
- **Flaws:**
  - `AdmissionsController::bulkProcess` allows an administrator to batch-select applications and update their status to `enrolled` directly in the `applications` table. This bypasses tuition assessment, fee payment verification, medical clearance, student number generation, institutional email creation, section assignment, and LMS enrollment provisioning.
  - `AdmissionsController::process` contains legacy duplicate finalization logic (lines 719–779) that duplicates `EnrollmentService::finalizeEnrollment()` without database transactions or payment checks.
  - Email uniqueness check in `AdmissionsController.php:750` queries `SELECT COUNT(*) FROM users WHERE ttu_email = :email` without `AND id != :id`, risking false collision loops.

### 3.4 Medical & Health Clearance
- **Implementation:** `app/Controllers/Admin/Clinic/ClinicController.php` and `app/Controllers/HealthController.php`.
- **Findings:** Correctly guarded by `requirePermission('medical.review')`. Records state transitions across `pending`, `under_review`, `correction_required`, `verified`, and `rejected`.

### 3.5 Curriculum & Subjects
- **Implementation:** `app/Controllers/Admin/Registrar/SubjectController.php`, `CollegeController.php`, and `ShsController.php`.
- **Strengths:** `SubjectController::getSubjectUsageDetails()` implements strict immutability checks across draft, active, and archived curricula, preventing accidental deletion of active subjects.
- **Flaws:**
  - Complete absence of `requirePermission()` in `SubjectController`, `CollegeController`, and `ShsController`. Any administrative user can modify institutional curricula.
  - Destructive cascade in `database/schema.sql:179`: deleting a subject from `subjects` cascades to `college_curriculum_subjects`, `shs_curriculum_subjects`, `college_section_subjects`, and `lms_courses`.

### 3.6 Registrar & Scheduling
- **Implementation:** `app/Controllers/Admin/Scheduler/SchedulerController.php`.
- **Strengths:** Validates room conflicts across overlapping time ranges and days.
- **Flaws:**
  - `instructor` is handled as an unvalidated raw string in `college_section_subjects` and `shs_section_subjects` (lines 501, 525).
  - Instructor conflict detection (line 495) compares raw strings (`WHERE ss.instructor = ?`). Variations in name formatting bypass conflict detection.
  - No foreign key to `users.id`. Assigning an instructor in the Scheduler does not update `lms_courses.faculty_user_id`.

### 3.7 Cashier & Finance
- **Implementation:** `app/Controllers/Admin/Finance/FinanceController.php`, `FeeController.php`, and `app/Services/AssessmentService.php`.
- **Strengths:** Accurate fee assessments for College (unit-based) and SHS (fixed templates). Transactions and row locking (`FOR UPDATE`) are used during payment processing in `FinanceController::process`.
- **Flaws:**
  - `app/Views/admin/finance/cashier_payments.php:9-24` executes raw `$pdo->query(...)` directly within the presentation view markup.
  - `FinanceController::payments()` is an empty stub passing `$pdo` to the view without permission checks.
  - `FeeController.php` lacks permission checks on fee template creation and updates.

### 3.8 User Management
- **Implementation:** `app/Controllers/Admin/System/SystemController.php:162`.
- **Flaw:** `SystemController::processUser` rejects any role creation outside `['applicant', 'superadmin', 'admissions', 'scholarship', 'cashier']`. Attempting to create an account with role `faculty`, `scheduler`, `clinic`, or `admin` throws an exception (`"Invalid role specified."`). Additionally, `app/Views/admin/system/users.php:337-347` omits `faculty` from the `<select name="role">` dropdown.

---

## 4. LMS Audit

### 4.1 LMS Authentication & Portals
- **Implementation:** `app/Controllers/Lms/LmsAuthController.php`.
- **Findings:**
  - Student login validates against `applications.status = 'enrolled'`.
  - Faculty login validates against `users.role = 'faculty'`.
  - Both authentication routines set `$_SESSION['user_role']` and `$_SESSION['lms_role']`, but never `$_SESSION['role']`.
  - Error responses utilize inline JavaScript alerts (`echo "<script>alert(...); ...</script>"`) rather than standard HTTP responses or flash messages.

### 4.2 Route Guards & RBAC Breakdown
- **Implementation:** `app/Routes/web.php:178-248`.
- **Flaw:** The entire LMS route group combines Student and Faculty portals under:
  ```php
  $router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\CsrfMiddleware', 'App\Middleware\AuthMiddleware']], function (Router $router) { ... });
  ```
  Neither `RoleMiddleware` nor sub-group role separation is applied. Any logged-in student or applicant can access faculty routes (e.g., `/sia/lms/faculty/dashboard.php`, `/sia/lms/faculty/calendar`). Furthermore, `RoleMiddleware.php` only supports admin roles and destroys the session if passed `faculty` or `student`.

### 4.3 CSRF Deadlock on All LMS Forms
- **Implementation:** `app/Middleware/CsrfMiddleware.php` vs. `app/Views/lms/*`.
- **Flaw:** `CsrfMiddleware` intercepts all POST requests and throws `HttpException(403)` if `csrf_token` is missing or invalid. An exhaustive search of `app/Views/lms/` confirms that **not a single LMS view template renders a CSRF token input field**. Every POST action (creating modules, uploading materials, creating assignments, grading submissions, starting quizzes, submitting quiz attempts, recording attendance, posting announcements) results in an immediate `403 Forbidden` error.

### 4.4 Course Material & Submission Download Breakdown
- **Implementation:** `app/Controllers/Lms/DownloadController.php:21, 89`.
- **Flaw:** Both `downloadMaterial()` and `downloadSubmission()` evaluate user permissions via `$role = $_SESSION['role'] ?? '';`. Because login controllers set `$_SESSION['user_role']`, `$role` is always empty string `''`. Lines 48 and 126 trigger `$this->forbidden($response);`, preventing all users from downloading any files.

### 4.5 Material Upload Storage Path Mismatch
- **Implementation:** `app/Controllers/Lms/FacultyController.php:90` vs. `app/Controllers/Lms/DownloadController.php:56`.
- **Flaw:**
  - In `FacultyController::uploadMaterial`, `$uploadDir = __DIR__ . '/../../../../storage/lms_materials/';`. From `app/Controllers/Lms/`, navigating up four levels reaches `c:\xampp\storage\lms_materials/` (outside the web root).
  - In `DownloadController::downloadMaterial`, `$baseDir = realpath(__DIR__ . '/../../../app/uploads/lms');`.
  - Uploaded materials are stored in an unmanaged external directory and can never be retrieved by the download controller.

### 4.6 Quizzes & Assessments
- **Implementation:** `app/Controllers/Lms/StudentQuizController.php`, `FacultyQuizController.php`, and `app/Services/LmsQuizService.php`.
- **Strengths:** Supports Multiple Choice, True/False, and Short Answer question types. Correctly tracks attempts and scores choices using database transactions.
- **Flaws:**
  - `LmsQuizService::submitAttempt` lines 196–203 calculates `$maxTime` to validate quiz time limits, but leaves the expiration block completely empty (`if (time() > $maxTime) { /* empty */ }`). Late submissions are accepted with zero penalty.

### 4.7 Assignments & Submissions
- **Implementation:** `app/Controllers/Lms/StudentAssignmentController.php` and `FacultyAssignmentController.php`.
- **Flaw:** `StudentAssignmentController::submit` uses a file extension blacklist (`['php', 'exe', 'sh', 'bat', 'js', 'html', 'phtml']`) instead of a strict whitelist. Dangerous extensions such as `.phar`, `.pht`, `.php7`, `.shtml`, or `.svg` (stored XSS) are not blocked.

---

## 5. Enrollment ↔ LMS Integration Audit

### 5.1 The Broken Faculty Workflow
The documented institutional workflow requires:
```
LMS Admin creates/activates Faculty account
↓
Faculty becomes selectable in Enrollment Scheduler
↓
Scheduler assigns Faculty to Section Subject
↓
LMS recognizes Faculty assignment and provisions course
↓
Faculty accesses assigned LMS course
```
**Codebase Reality:** This workflow is completely broken at multiple points:
1. **Creation Blocked:** `SystemController::processUser` line 162 throws an exception when attempting to create role `faculty`. The UI modal lacks a faculty option.
2. **Disconnected Scheduling:** In `SchedulerController.php:501, 525`, the scheduler assigns faculty by typing a raw string into `college_section_subjects.instructor` or `shs_section_subjects.instructor`. There is no dropdown referencing `users`, no validation against active faculty, and no storage of `faculty_user_id`.
3. **No Course Synchronization:** Creating or updating schedules does not update `lms_courses`. `lms_courses` requires `faculty_user_id` (an integer foreign key to `users.id`).
4. **Manual Course Generator Disconnect:** `LmsAdminController::courseGenerator` displays section subjects with `old_instructor_string` and asks the admin to manually select a faculty user to generate the `lms_courses` entry.

### 5.2 The Unsafe JIT Auto-Provisioning Fallback
- **Implementation:** `app/Repositories/CollegeEnrollmentRepository.php:65-96` and `ShsEnrollmentRepository.php:62-93`.
- **Behavior:** When an enrolled student accesses their LMS portal (`getStudentCourses`), the repository executes a read query. If an `lms_courses` record does not exist for an enrolled subject:
  ```php
  $facStmt = $this->pdo->query("SELECT id FROM users WHERE role = 'faculty' ORDER BY id ASC LIMIT 1");
  $defaultFacultyId = (int)$facStmt->fetchColumn() ?: 18;
  // Automatically executes INSERT INTO lms_courses with $defaultFacultyId
  ```
- **Consequences:**
  - Violates Command-Query Separation (side-effecting write in a read operation).
  - Race condition when multiple students access courses concurrently.
  - Courses are arbitrarily assigned to whichever faculty account has the lowest ID, or to hardcoded ID `18`.
  - If no faculty accounts exist or user ID 18 does not exist, the `INSERT` statement violates foreign key constraint `fk_lms_course_faculty` and crashes the student dashboard with an uncaught `PDOException`.

### 5.3 Database Schema Discrepancies
- **Polymorphic Foreign Key Omission:** `lms_courses.academic_section_id` has no foreign key constraint to `college_sections` or `shs_sections` (`database/schema.sql:661`). Deleting a section leaves orphaned courses in the LMS.
- **Destructive Faculty Cascading:** `lms_courses.faculty_user_id` has `ON DELETE CASCADE`. Deleting a faculty account permanently deletes the course, its modules, assignments, student submissions, quiz questions, and student grade records.
- **Mismatched Academic Level Enums:**
  - `applications.academic_level`: `'Senior High School'`, `'College'`.
  - `lms_courses.academic_level`: `'SHS'`, `'College'`.
  - `subjects.education_level`: `'SHS'`, `'College'`, `'Both'`.

---

## 6. Security Findings

| Vulnerability ID | Title | Severity | Location | Description |
| :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | Total CSRF Rejection Deadlock | **CRITICAL** | `app/Views/lms/*`, `CsrfMiddleware.php` | All LMS POST forms lack CSRF tokens, causing all state changes to be rejected. |
| **SEC-02** | Missing LMS Role Guards / IDOR Risk | **CRITICAL** | `app/Routes/web.php:178-248` | LMS faculty and student routes lack role middleware; students can access faculty portals. |
| **SEC-03** | Horizontal Admin Privilege Escalation | **HIGH** | `AdmissionsController`, `FeeController`, etc. | Admin controllers do not verify granular permissions; cashiers can approve applications and edit curricula. |
| **SEC-04** | Blacklist File Upload in LMS | **MEDIUM** | `StudentAssignmentController.php:99` | Blacklist approach allows upload of alternative executable extensions (`.phar`, `.shtml`, `.svg`). |
| **SEC-05** | Directory Traversal Risk via Path Escape | **HIGH** | `FacultyController.php:90` | Material upload path resolves outside project root (`c:\xampp\storage\lms_materials/`). |
| **SEC-06** | Session Fixation & Naming Inconsistency | **HIGH** | `DownloadController.php:21, 89` | Inconsistent session keys (`role` vs `user_role`) lock out file access. |
| **SEC-07** | Raw SQL in Presentation Layer | **HIGH** | `cashier_payments.php:9-24` | Direct PDO query execution inside HTML view bypasses controller layers. |

---

## 7. Database Findings

1. **Orphaned LMS Courses:** `lms_courses.academic_section_id` lacks foreign key constraints. Deleting sections leaves dangling records.
2. **Catastrophic Faculty Cascade:** `CONSTRAINT fk_lms_course_faculty FOREIGN KEY (faculty_user_id) REFERENCES users (id) ON DELETE CASCADE` destroys student academic history if a faculty member is deleted. Must be changed to `ON DELETE RESTRICT` or soft-delete.
3. **Unlinked Instructor Strings:** `college_section_subjects.instructor` and `shs_section_subjects.instructor` are raw `VARCHAR(150)` columns instead of foreign keys to `users.id`.
4. **Outdated Seed and Migration Files:** `database/lms_phase2.sql` uses obsolete schema (`teacher_id`, `college_section_id`). `schema_dump.sql` is a seed dump lacking LMS table structures. Only `database/schema.sql` contains the canonical DDL.

---

## 8. Code Quality & Architectural Findings

1. **Direct SQL in Views:** `app/Views/admin/finance/cashier_payments.php` executes raw database queries directly in the view.
2. **Duplicate Finalization Logic:** `AdmissionsController::process()` re-implements enrollment finalization rather than delegating to `EnrollmentService::finalizeEnrollment()`.
3. **Hardcoded Magic Numbers:** Repositories hardcode fallback faculty ID `18` (`CollegeEnrollmentRepository.php:66`, `ShsEnrollmentRepository.php:63`).
4. **Side-Effecting Read Queries:** Repositories execute database `INSERT` operations inside `getActiveStudentCourses()`.
5. **Procedural Alert Injection:** `LmsAuthController.php` emits raw `<script>alert(...)</script>` strings instead of returning standard responses.

---

## 9. Workflow Findings

| Workflow | Entry Point | Expected State Transition | Actual Behavior | Result |
| :--- | :--- | :--- | :--- | :--- |
| **1. Faculty Account Creation** | `/sia/admin/system/users.php` | Superadmin creates faculty user | Throws "Invalid role specified." | **BLOCKED** |
| **2. Schedule Assignment** | `/sia/admin/scheduler/college_sections.php` | Assign faculty user to section subject | Stores raw string in `instructor` column | **DISCONNECTED** |
| **3. LMS Course Generation** | `/sia/admin/lms/generator` | Sync section schedule to LMS course | Requires manual mapping; no auto-sync | **FRAGILE** |
| **4. Student Course Access** | `/sia/lms/student/dashboard.php` | Read assigned courses | JIT inserts course with arbitrary faculty (ID 18) | **UNSAFE** |
| **5. Course Material Upload** | `/sia/lms/faculty/course.php` | Faculty uploads lecture slides | CSRF 403 Forbidden; path escapes to `c:\xampp\storage` | **BROKEN** |
| **6. Material / Submission Download** | `/sia/lms/download/material/{id}` | User downloads file | 403 Forbidden due to `$_SESSION['role']` mismatch | **BLOCKED** |
| **7. Quiz Submission** | `/sia/lms/student/.../quizzes/.../submit` | Student submits quiz answers | CSRF 403 Forbidden; time limit unenforced | **BROKEN** |
| **8. Admissions Bulk Process** | `/sia/admin/admissions/review.php` | Batch process applications | Direct update to `enrolled` without fees/clearance | **CORRUPTIVE** |

---

## 10. Testing Findings

- **Existing Tests:** The repository contains no automated test suite (PHPUnit or Pest).
- **Critical Untested Areas:**
  1. Complete Enrollment finalization state machine.
  2. Fee assessment and payment balance calculations.
  3. Scheduling room and instructor conflict detection.
  4. LMS course auto-provisioning and student authorization.
  5. Quiz time limit validation and scoring algorithms.

---

## 11. Documentation Findings

- **Discrepancy 1:** Documentation claims a unified identity and shared course assignment model. The source code reveals disconnected raw instructor strings and manual course generation.
- **Discrepancy 2:** Documentation indicates that `RoleMiddleware` protects all sub-portals. In reality, `RoleMiddleware:admin` permits any admin role, and LMS routes have no role middleware at all.
- **Discrepancy 3:** Database schema docs describe `lms_courses` as having foreign keys to sections, but `academic_section_id` is a loose integer with no constraint.

---

## 12. Detailed Issues Catalog

### 12.1 Confirmed Critical Issues (P0)

#### [CRIT-01] Universal CSRF Rejection Across All LMS Forms
- **Severity:** Critical
- **Evidence:** `app/Routes/web.php:178`, `app/Middleware/CsrfMiddleware.php:17-21`, `app/Views/lms/*`.
- **Current Behavior:** `web.php` applies `CsrfMiddleware` to the entire LMS route group. However, no view in `app/Views/lms/` includes `<input type="hidden" name="csrf_token" ...>`. Every POST submission fails with HTTP 403.
- **Recommended Fix:** Add `csrf_token()` hidden inputs to all LMS view forms, or include a global CSRF token in `lms_header.php` and inject it automatically via JavaScript into AJAX and form submissions.
- **Status:** CONFIRMED.

#### [CRIT-02] LMS Material and Submission Downloads Universally Forbidden
- **Severity:** Critical
- **Evidence:** `app/Controllers/Lms/DownloadController.php:21, 89` vs. `app/Controllers/AuthController.php:118` and `app/Controllers/Lms/LmsAuthController.php:99, 138`.
- **Current Behavior:** `DownloadController` checks `$role = $_SESSION['role'] ?? '';`. The authentication controllers only set `$_SESSION['user_role']` and `$_SESSION['lms_role']`. `$role` evaluates to `''`, causing lines 48 and 126 to reject every download with `403 Forbidden`.
- **Recommended Fix:** Update `DownloadController` to check `$_SESSION['user_role'] ?? $_SESSION['lms_role'] ?? ''`.
- **Status:** CONFIRMED.

#### [CRIT-03] Material Upload Directory Path Escape and Download 404 Mismatch
- **Severity:** Critical
- **Evidence:** `app/Controllers/Lms/FacultyController.php:90` vs. `app/Controllers/Lms/DownloadController.php:56`.
- **Current Behavior:** `FacultyController::uploadMaterial` writes files to `__DIR__ . '/../../../../storage/lms_materials/'` (resolves to `c:\xampp\storage\lms_materials/`). `DownloadController::downloadMaterial` attempts to read from `app/uploads/lms/`. All uploaded materials produce 404 errors.
- **Recommended Fix:** Unify storage path to `c:\xampp\htdocs\sia\app\uploads\lms\materials/` across both controllers.
- **Status:** CONFIRMED.

#### [CRIT-04] Complete Absence of Role Middleware on LMS Routes
- **Severity:** Critical
- **Evidence:** `app/Routes/web.php:178-248` and `app/Middleware/RoleMiddleware.php:72-118`.
- **Current Behavior:** Both `/lms/student/*` and `/lms/faculty/*` are grouped under `AuthMiddleware` only. Students can directly access faculty endpoints. `RoleMiddleware` destroys sessions if passed role `student` or `faculty`.
- **Recommended Fix:** Update `RoleMiddleware.php` to handle `faculty` and `student` roles, and split the LMS routes in `web.php` into two separate route groups with dedicated role guards.
- **Status:** CONFIRMED.

#### [CRIT-05] Admissions Bulk Process Accounting and Validation Bypass
- **Severity:** Critical
- **Evidence:** `app/Controllers/Admin/Admissions/AdmissionsController.php:904-960`.
- **Current Behavior:** Selecting `enrolled` in bulk process directly executes `UPDATE applications SET status = 'enrolled'`, skipping payment verification, medical clearance, credential generation, section enrollment, and LMS course sync.
- **Recommended Fix:** Remove `'enrolled'` from `$validStatuses` in `bulkProcess()`, or invoke `EnrollmentService::finalizeEnrollment()` for each record inside a transaction.
- **Status:** CONFIRMED.

#### [CRIT-06] Inability to Create Faculty Accounts via Administrative Management
- **Severity:** Critical
- **Evidence:** `app/Controllers/Admin/System/SystemController.php:162` and `app/Views/admin/system/users.php:337-347`.
- **Current Behavior:** `SystemController::processUser` rejects any role creation outside `['applicant', 'superadmin', 'admissions', 'scholarship', 'cashier']`. Attempting to create `faculty`, `scheduler`, `clinic`, or `admin` accounts throws `"Invalid role specified."`. The UI modal also omits `faculty`.
- **Recommended Fix:** Expand the role whitelist in `SystemController.php` to include `faculty`, `scheduler`, `clinic`, and `admin`, and add `<option value="faculty">Faculty</option>` to `users.php`.
- **Status:** CONFIRMED.

---

### 12.2 Confirmed High Priority Issues (P1)

#### [HIGH-01] Scheduler ↔ LMS Faculty Assignment Disconnect
- **Severity:** High
- **Evidence:** `app/Controllers/Admin/Scheduler/SchedulerController.php:501, 525` vs. `app/Controllers/Admin/LmsAdminController.php:18-38` vs. `database/schema.sql:661-671`.
- **Current Behavior:** Section subjects store instructor names as raw strings. The LMS requires a foreign key `faculty_user_id`. Scheduler changes do not synchronize with `lms_courses`.
- **Recommended Fix:** Add `faculty_user_id` foreign key columns to `college_section_subjects` and `shs_section_subjects`. Update the Scheduler UI to select from active faculty users. Automatically synchronize schedule assignments with `lms_courses`.
- **Status:** CONFIRMED.

#### [HIGH-02] Unsafe JIT Course Auto-Provisioning on Read Operations
- **Severity:** High
- **Evidence:** `app/Repositories/CollegeEnrollmentRepository.php:65-96` and `app/Repositories/ShsEnrollmentRepository.php:62-93`.
- **Current Behavior:** Student portal read queries execute side-effecting `INSERT` statements when courses are missing, assigning the course to an arbitrary faculty member or hardcoded ID `18`.
- **Recommended Fix:** Remove write operations from repository read methods. Require explicit course generation upon section schedule finalization.
- **Status:** CONFIRMED.

#### [HIGH-03] Horizontal Administrative Privilege Escalation
- **Severity:** High
- **Evidence:** `app/Routes/web.php:65` vs. `AdmissionsController`, `CollegeController`, `ShsController`, `SubjectController`, `FeeController`, `FinanceController`.
- **Current Behavior:** `RoleMiddleware:admin` allows any admin role. Controllers fail to call `requirePermission()`, allowing staff to perform unauthorized actions across different departments.
- **Recommended Fix:** Add explicit `requirePermission()` calls to all administrative controller methods.
- **Status:** CONFIRMED.

#### [HIGH-04] Direct Database Query Execution in Presentation View
- **Severity:** High
- **Evidence:** `app/Views/admin/finance/cashier_payments.php:9-24` and `app/Controllers/Admin/Finance/FinanceController.php:70-78`.
- **Current Behavior:** `cashier_payments.php` executes `$pdo->query(...)` directly in the view markup. The controller is an empty stub.
- **Recommended Fix:** Move database query logic into `FinanceController::payments()` and pass the resulting dataset to the view.
- **Status:** CONFIRMED.

#### [HIGH-05] Redundant Finalization Logic in AdmissionsController
- **Severity:** High
- **Evidence:** `app/Controllers/Admin/Admissions/AdmissionsController.php:719-779` vs. `app/Services/EnrollmentService.php:25-185`.
- **Current Behavior:** `AdmissionsController::process()` contains duplicate inline enrollment logic that bypasses `EnrollmentService::finalizeEnrollment()`, lacks payment checks, and has an email collision bug.
- **Recommended Fix:** Refactor `AdmissionsController::process()` to delegate enrollment finalization exclusively to `EnrollmentService::finalizeEnrollment()`.
- **Status:** CONFIRMED.

#### [HIGH-06] Unenforced Server-Side Quiz Time Limits
- **Severity:** High
- **Evidence:** `app/Services/LmsQuizService.php:196-203`.
- **Current Behavior:** The time limit check has an empty `if (time() > $maxTime) { }` block. Late quiz submissions are accepted and graded without penalty.
- **Recommended Fix:** Enforce time limit check: reject late submissions or flag them and apply automated penalties.
- **Status:** CONFIRMED.

---

### 12.3 Confirmed Medium Priority Issues (P2)

#### [MED-01] Blacklist File Upload Validation in LMS Submissions
- **Severity:** Medium
- **Evidence:** `app/Controllers/Lms/StudentAssignmentController.php:99-102`.
- **Current Behavior:** Checks against a blacklist of extensions (`php`, `exe`, etc.) instead of a strict whitelist, risking upload of alternative executable formats.
- **Recommended Fix:** Replace with a strict whitelist (`pdf`, `docx`, `pptx`, `zip`, `jpg`, `png`) and MIME-type verification via `finfo`.
- **Status:** CONFIRMED.

#### [MED-02] Polymorphic Section Column in `lms_courses` Lacks Foreign Key
- **Severity:** Medium
- **Evidence:** `database/schema.sql:661`.
- **Current Behavior:** `academic_section_id` is an unconstrained integer. Section deletion leaves orphaned records.
- **Recommended Fix:** Implement database triggers or application-level foreign key validation on section deletion.
- **Status:** CONFIRMED.

#### [MED-03] Inconsistent Academic Level Enum Values
- **Severity:** Medium
- **Evidence:** `database/schema.sql:363` (`'Senior High School'`) vs. line 660 (`'SHS'`).
- **Current Behavior:** Requires redundant string translation mapping across repositories and services.
- **Recommended Fix:** Standardize enum values across all tables to `'College'` and `'SHS'`.
- **Status:** CONFIRMED.

#### [MED-04] Raw JavaScript Alerts in Authentication Responses
- **Severity:** Medium
- **Evidence:** `app/Controllers/Lms/LmsAuthController.php:111, 115, 121`.
- **Current Behavior:** Outputs raw `<script>alert(...)</script>` strings instead of utilizing proper HTTP redirects and flash session messages.
- **Recommended Fix:** Refactor to use `$response->redirect()` with flash session messages.
- **Status:** CONFIRMED.

#### [MED-05] Absence of Automated Test Infrastructure
- **Severity:** Medium
- **Evidence:** No test directories or PHPUnit configurations exist.
- **Current Behavior:** Regression testing and refactoring cannot be validated automatically.
- **Recommended Fix:** Introduce PHPUnit with SQLite in-memory database testing for core domain services.
- **Status:** CONFIRMED.

---

### 12.4 Confirmed Low Priority Issues (P3)

#### [LOW-01] Obsolete SQL Migration Scripts
- **Severity:** Low
- **Evidence:** `database/lms_phase2.sql` and `database/schema_dump.sql`.
- **Current Behavior:** Obsolete column structures (`teacher_id`, `college_section_id`) cause confusion for developers.
- **Recommended Fix:** Archive or remove obsolete SQL files and document `database/schema.sql` as the single source of truth.
- **Status:** CONFIRMED.

#### [LOW-02] Hardcoded Fallback Faculty ID 18
- **Severity:** Low
- **Evidence:** `app/Repositories/CollegeEnrollmentRepository.php:66` and `ShsEnrollmentRepository.php:63`.
- **Current Behavior:** Magic number `18` used as fallback faculty ID.
- **Recommended Fix:** Eliminate fallback and require explicit faculty assignment.
- **Status:** CONFIRMED.

---

## 13. Verification Pass & Consistency Check

### 13.1 Confirmed Bugs (Verified via Source Code)
- `SystemController.php:162`: Role creation whitelist rejects `faculty`, `scheduler`, `clinic`, `admin`.
- `users.php:337-347`: UI dropdown lacks `faculty` option.
- `DownloadController.php:21, 89`: Checks `$_SESSION['role']` which is never set, breaking all downloads.
- `FacultyController.php:90`: Uploads escape to `c:\xampp\storage\lms_materials/`.
- `web.php:178`: LMS routes lack role middleware; all forms lack CSRF tokens.
- `AdmissionsController.php:947`: `bulkProcess` sets `status = 'enrolled'` without payment or clearance checks.
- `cashier_payments.php:9-24`: Direct raw SQL execution in presentation view.
- `LmsQuizService.php:196-203`: Server-side quiz time limit check is empty.
- `CollegeEnrollmentRepository.php:89` & `ShsEnrollmentRepository.php:85`: JIT write operations in read queries with hardcoded faculty ID 18.

### 13.2 Confirmed Security Issues (Verified via Source Code)
- CSRF deadlock on all LMS POST endpoints.
- Broken LMS route authorization (students can access faculty routes).
- Horizontal privilege escalation in admin controllers due to missing `requirePermission()` calls.
- Blacklist file upload validation in `StudentAssignmentController`.

### 13.3 False Positives Investigated & Removed
1. **Suspected SQL Injection in SchedulerController:** Investigated `$table` and `$secIdCol` interpolation (lines 492–497). Verified that these variables are strictly set via internal ternary expressions (`$isCollege ? ... : ...`) and are not derived from user input. **Result: FALSE POSITIVE.**
2. **Suspected Document Directory Traversal in DocumentController:** Investigated file upload handling in `DocumentController.php:81-140`. Verified that the controller uses strict extension whitelists, MIME-type verification via `finfo`, and server-generated unique filenames. **Result: FALSE POSITIVE.**
3. **Suspected Missing Permission Checks in ClinicController:** Investigated `ClinicController.php`. Verified that `dashboard()`, `index()`, and `detail()` explicitly call `requirePermission('medical.review')`. **Result: FALSE POSITIVE.**

### 13.4 Remaining Unknowns
- **Production Web Server Configuration:** Whether Apache `.htaccess` or Nginx blocks direct HTTP access to `app/uploads/` cannot be verified without inspecting the live server vhost configuration.
- **External SMTP / PHPMailer Credentials:** Runtime delivery of institutional email credentials requires active credentials in environment settings.

---

## 14. Recommended Implementation Roadmap

```
Phase 1: Critical Fixes (Operational Restoration)
├── 1.1 Add CSRF token inputs to all LMS view forms
├── 1.2 Fix DownloadController session key check ($_SESSION['user_role'])
├── 1.3 Correct FacultyController material upload directory path
├── 1.4 Expand SystemController role whitelist to allow 'faculty'
└── 1.5 Add 'faculty' role option to admin user management modal

Phase 2: Security & Authorization Hardening
├── 2.1 Update RoleMiddleware to handle 'faculty' and 'student'
├── 2.2 Split web.php LMS route groups into dedicated /student and /faculty groups
├── 2.3 Add requirePermission() calls across all admin controllers
└── 2.4 Replace blacklist with strict whitelist in StudentAssignmentController

Phase 3: Integration & Workflow Realignment
├── 3.1 Link Scheduler to users table via faculty_user_id foreign key
├── 3.2 Automate lms_courses generation upon schedule publishing
├── 3.3 Remove unsafe JIT database writes from Enrollment Repositories
├── 3.4 Unify AdmissionsController enrollment logic into EnrollmentService
└── 3.5 Enforce quiz time limits in LmsQuizService

Phase 4: Code Quality & Database Normalization
├── 4.1 Refactor cashier_payments.php to remove raw SQL from view
├── 4.2 Standardize academic level enums across tables
├── 4.3 Replace legacy JS alert redirects in LmsAuthController
└── 4.4 Set up PHPUnit test suite for core workflows
```

---

## 15. Comprehensive Files Affected Catalog

| File Path | Nature of Modification Needed |
| :--- | :--- |
| `app/Controllers/Admin/System/SystemController.php` | Expand role whitelist to include `faculty`, `scheduler`, `clinic`, `admin`. |
| `app/Views/admin/system/users.php` | Add `faculty` option to the role creation modal dropdown. |
| `app/Controllers/Lms/DownloadController.php` | Change `$_SESSION['role']` to `$_SESSION['user_role'] ?? $_SESSION['lms_role']`. |
| `app/Controllers/Lms/FacultyController.php` | Fix upload directory path to remain within project `app/uploads/lms/materials`. |
| `app/Routes/web.php` | Separate student and faculty LMS route groups and attach dedicated role middleware. |
| `app/Middleware/RoleMiddleware.php` | Add support and redirection handling for `student` and `faculty` roles. |
| `app/Views/lms/faculty/course.php` | Add CSRF token hidden inputs to material upload and module creation modals. |
| `app/Views/lms/faculty/assignments/form.php` | Add CSRF token hidden input. |
| `app/Views/lms/faculty/assignments/submissions.php` | Add CSRF token hidden input to grading form. |
| `app/Views/lms/faculty/quizzes/form.php` | Add CSRF token hidden input. |
| `app/Views/lms/faculty/quizzes/questions.php` | Add CSRF token hidden input to question form. |
| `app/Views/lms/faculty/attendance/form.php` | Add CSRF token hidden input. |
| `app/Views/lms/faculty/attendance/edit.php` | Add CSRF token hidden input. |
| `app/Views/lms/faculty/announcements/form.php` | Add CSRF token hidden input. |
| `app/Views/lms/student/assignments/show.php` | Add CSRF token hidden input to submission form. |
| `app/Views/lms/student/quizzes/show.php` | Add CSRF token hidden input to quiz start form. |
| `app/Views/lms/student/quizzes/attempt.php` | Add CSRF token hidden input to quiz submit form. |
| `app/Controllers/Admin/Admissions/AdmissionsController.php` | Remove `enrolled` from `bulkProcess()`; delegate finalization to `EnrollmentService`. |
| `app/Controllers/Admin/Scheduler/SchedulerController.php` | Migrate instructor handling to `faculty_user_id` foreign key. |
| `app/Repositories/CollegeEnrollmentRepository.php` | Remove JIT database `INSERT` from `getActiveStudentCourses()`. |
| `app/Repositories/ShsEnrollmentRepository.php` | Remove JIT database `INSERT` from `getActiveStudentCourses()`. |
| `app/Services/LmsQuizService.php` | Implement server-side time limit enforcement in `submitAttempt()`. |
| `app/Controllers/Lms/StudentAssignmentController.php` | Implement strict whitelist and MIME validation for submissions. |
| `app/Views/admin/finance/cashier_payments.php` | Remove `$pdo->query(...)` markup; receive `$payments` from controller. |
| `app/Controllers/Admin/Finance/FinanceController.php` | Move payment query into `payments()` method; add permission check. |
| `app/Controllers/Admin/Finance/FeeController.php` | Add `requirePermission('fees.manage')`. |
| `app/Controllers/Admin/Registrar/SubjectController.php` | Add `requirePermission('subjects.manage')`. |
| `app/Controllers/Admin/Registrar/CollegeController.php` | Add `requirePermission('college_curriculum.manage')`. |
| `app/Controllers/Admin/Registrar/ShsController.php` | Add `requirePermission('shs_curriculum.manage')`. |
| `app/Controllers/Admin/Scholarship/ScholarshipController.php` | Add `requirePermission('scholarships.manage')` to `process()`. |
| `database/schema.sql` | Add `faculty_user_id` FK to section subject tables; standardize enums. |
