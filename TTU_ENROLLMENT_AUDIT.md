# TTU Enrollment System — Comprehensive Audit

**Target System:** Triple T University (TTU) Enrollment System  
**Repository Location:** `c:\xampp\htdocs\sia`  
**Audit Date:** September 2026  
**Auditor Roles:** Senior Software Architect, Full-Stack Engineer, Security Engineer, QA Engineer, Database Architect, UI/UX Auditor  
**Scope Boundary:** **ENROLLMENT SYSTEM ONLY**. The Learning Management System (LMS) is unfinished and strictly excluded from all evaluations, scoring, architecture analysis, and recommendations.

---

## 1. Executive Summary

This comprehensive audit evaluated the Triple T University (TTU) Enrollment System, a custom-built PHP/MySQL institutional management platform supporting Senior High School (SHS) and College applicant admissions, medical clearance, document verification, academic scheduling, sectioning, assessment, cashiering, scholarship processing, and student record management.

The system demonstrates commendable engineering foundations: a clean vanilla PHP MVC architecture with a custom front controller (`public/index.php`), custom HTTP request/response abstractions (`app/Core/Request.php`, `app/Core/Response.php`), robust CSRF token management (`app/Middleware/CsrfMiddleware.php`), session fixation defenses (`app/Middleware/SessionSecurityMiddleware.php`), centralized PDO database wrapping (`app/Core/Database.php`), and a modern, aesthetically polished Bootstrap 5 / SweetAlert2 UI with glassmorphism touches.

However, beneath the polished presentation, the audit identified **critical architectural disconnects, severe security bypasses, runtime SQL schema mismatches, and data-integrity risks** that prevent the platform from being production-ready:
1. **Critical Remote Database Wipe:** An unauthenticated setup endpoint is explicitly whitelisted in `.htaccess` (`database/migrations/setup_database.php`), allowing anyone on the network to execute `DROP DATABASE IF EXISTS sia` and destroy all production records.
2. **Broken Role-Based Authorization & Privilege Escalation:** While admin routes enforce role checks at the outer middleware group, inner admin controllers lack granular permission gates. Any staff member (e.g., clinic staff or cashier) can invoke `SystemController::processUser` to create a `superadmin` account or trigger `processBackup` to run arbitrary SQL scripts.
3. **Email OTP Verification Bypass:** In `AuthController::login()`, the user lookup query omits `email_verified`, causing the fallback logic to always evaluate to verified, allowing unverified applicants immediate access.
4. **Runtime SQL Column Mismatches:** Multiple active controllers query database columns that do not exist in the schema (e.g., `reset_password_code` in `AuthController.php`, `remarks` in `ScholarshipController.php`), causing fatal 500 errors during password recovery and scholarship updates.
5. **Three-Way Enrollment Finalization Conflict:** Three separate modules (`FinanceController`, `AdmissionsController`, and `RegistrarController`) attempt to manage the final transition to `enrolled`, causing empty registrar queues, broken COR issuance workflows, and inconsistent credential provisioning.
6. **Data Loss for Irregular Students:** The enrollment portal allows irregular students to select custom subjects, but the backend fails to persist these selections to the database, resulting in silent data loss during admissions processing.

Overall, the system scores **6.35 / 10** (*Functional but significant weaknesses*). While its structural foundation and UI design are strong, immediate intervention is required in security, role authorization, workflow state machines, and schema synchronization before real-world deployment.

---

## 2. Scope

### In-Scope Components (Enrollment System Only)
* **Authentication & Identity:** User registration, multi-factor OTP verification, role routing, password reset, session security.
* **Applicant Portal:** Multi-step admissions form, track/strand and course selection, document upload, medical profile, application tracking.
* **Admissions Module:** Applicant verification, document evaluation, entrance exam scoring, interview rating, status progression.
* **Clinic / Medical Module:** Health profile review, medical clearance sign-off, clinic document management.
* **Registrar & Curriculum:** College and SHS curricula, subject catalogs, prerequisite rules, section capacity management, enrollment queues, Certificate of Registration (COR) generation.
* **Scheduling Module:** Academic year/semester management, timetable conflict detection, room/faculty assignments.
* **Finance & Cashier:** Assessment generation, fee templates, unit rate calculations, payment processing, official receipts, ledger tracking.
* **Scholarship Module:** Scholarship programs, applicant grant allocation, discount assessment recalculations, recipient status tracking.
* **System Administration:** User management, activity auditing, system settings, database backup/restore utilities.
* **Core Framework:** Front controller, custom router, middleware pipeline, PDO database connection, utility helpers.

### Excluded Components (Out of Scope)
* **Learning Management System (LMS):** All controllers in `app/Controllers/Lms/`, views in `app/Views/lms/`, database tables (`lms_*`, `quizzes`, `assignments`, `submissions`), and LMS student/teacher portals are strictly excluded from all grading, architecture evaluations, and recommendations.

---

## 3. System Architecture Overview

The TTU Enrollment System implements a **Hybrid Vanilla PHP MVC Pattern** emphasizing "Fat Controllers":

```
                ┌────────────────────────────────────────┐
                │             Web Browser                │
                └───────────────────┬────────────────────┘
                                    │ HTTP Request
                                    ▼
                ┌────────────────────────────────────────┐
                │          public/index.php              │
                │        (Front Controller Entry)        │
                └───────────────────┬────────────────────┘
                                    │
                                    ▼
                ┌────────────────────────────────────────┐
                │          app/Core/Router.php           │
                │  - Route Resolution & Named Params     │
                │  - Middleware Pipeline Execution       │
                └─────────┬──────────────────┬───────────┘
                          │                  │
         Session & CSRF   │                  │ Role Middleware
         Middleware       ▼                  ▼
    ┌─────────────────────────┐        ┌─────────────────────────┐
    │SessionSecurityMiddleware│        │     RoleMiddleware      │
    │     CsrfMiddleware      │        │  (applicant / admin)    │
    └─────────────────────────┘        └─────────────┬───────────┘
                                                     │
                                                     ▼
                                       ┌─────────────────────────┐
                                       │   Fat Controllers       │
                                       │ (Logic + Validation     │
                                       │  + Raw PDO Queries)     │
                                       └──────┬────────────┬─────┘
                                              │            │
                         Render View Data     │            │ Raw SQL Queries
                                              ▼            ▼
                                       ┌──────────┐  ┌───────────┐
                                       │  Views   │  │ Database  │
                                       │ (PHP/HTML│  │ (MariaDB  │
                                       │   UI)    │  │ via PDO)  │
                                       └──────────┘  └───────────┘
```

### Key Architectural Characteristics
* **Routing & Middleware Pipeline:** `app/Core/Router.php` parses incoming URIs, matches patterns with named parameters (e.g., `{id}`), and executes middleware stacks sequentially before dispatching to `Controller@action`.
* **State & Session Security:** `SessionSecurityMiddleware` enforces session regeneration, fingerprint matching (IP + User Agent), and inactivity timeouts. `CsrfMiddleware` validates tokens on all mutating HTTP methods (`POST`, `PUT`, `DELETE`).
* **Fat Controllers:** In accordance with project conventions, controllers (`app/Controllers/*`) handle request decoding, business logic, validation, transaction orchestration, and direct PDO queries.
* **Anemic Models:** Models (`app/Models/*`) primarily serve as basic data access wrappers, though complex queries are frequently executed directly inside controller methods.
* **Presentation Layer:** Vanilla PHP views combined with Bootstrap 5.3, FontAwesome 6, SweetAlert2, and modular CSS sheets.

---

## 4. Reconstructed Enrollment Workflow

Based on code-level analysis of controllers, database triggers, and UI forms, the actual implemented enrollment lifecycle proceeds as follows:

```
  [1. Registration]
  Applicant submits registration at /register -> AuthController::register()
  Account created in `users` (role='applicant', email_verified=0)
         │
         ▼
  [2. Email OTP Verification]
  OTP generated and emailed -> AuthController::verifyEmail()
  *CRITICAL BUG*: AuthController::login() bypasses OTP check due to missing column in User::findByEmail()
         │
         ▼
  [3. Application Form Submission]
  Applicant completes multi-step form at /applicant/enroll -> EnrollController::processForm()
  Creates row in `applications` (status='draft' or 'submitted')
         │
         ▼
  [4. Document & Health Submission]
  Applicant uploads required credentials -> DocumentController::uploadDocument()
  Applicant completes health history -> HealthController::submitProfile()
         │
         ▼
  [5. Clinic Clearance]
  Medical staff reviews profile at /admin/clinic/records -> ClinicController::process()
  Updates `health_records.clearance_status = 'cleared'`
         │
         ▼
  [6. Admissions Verification & Exam/Interview]
  Admissions staff evaluates credentials at /admin/admissions/view?id={id}
  Status transitions: 'submitted' -> 'under_review' -> 'verified'
  Admissions enters entrance exam score & interview rating
  Status updated: 'verified' -> 'approved'
         │
         ▼
  [7. Sectioning & Subject Assignment]
  Admissions assigns Section ID -> `applications.section_id`
  *CRITICAL GAP*: Irregular students select custom subjects, but these are never persisted,
  causing Admissions to overwrite them with regular section subjects.
         │
         ▼
  [8. Assessment Generation & Scholarship Allocation]
  System generates assessment in `student_assessments` and `assessment_items`
  If applicant has scholarship -> Scholarship staff assigns program in `scholarship_recipients`
  `recalculateStudentAssessment()` deducts grant from `total_amount`
         │
         ▼
  [9. Cashier Payment & Verification]
  Applicant pays at cashier or uploads proof -> FinanceController::processPayment()
  Cashier approves payment: inserts `payments`, updates `student_assessments.payment_status`
         │
         ▼
  [10. Enrollment Finalization Conflict]
  *CONFLICT*: FinanceController calls `finalizeStudentEnrollment()`, immediately setting status='enrolled'
  Registrar's enrollment queue queries `WHERE status='approved'`, finding zero records!
  Admissions also has an `action=enroll` button that creates student numbers and TTU institutional emails.
```

---

## 5. Module-by-Module Audit

### 5.1 Authentication & User Management (`app/Controllers/AuthController.php`, `User.php`)
* **Strengths:** Strong session regeneration upon login; robust CSRF validation; password hashing using `PASSWORD_DEFAULT` (bcrypt); IP and User-Agent fingerprinting in `SessionSecurityMiddleware.php`.
* **Vulnerabilities:**
  * `AuthController::login()` checks `$user['email_verified']`, but `User::findByEmail()` (`app/Models/User.php:16`) omits `email_verified` from the `SELECT` list. As a result, `$user['email_verified']` is `null`, default fallback evaluates to `1`, and unverified applicants can log in immediately.
  * `AuthController.php:640, 777, 790, 851` queries `reset_password_code` and `reset_password_expires_at`, but `database/schema.sql:35` defines `reset_token` and `reset_token_expires_at`. Initiating a password reset triggers an unhandled `PDOException` (500 Internal Server Error).
  * `AdmissionsController.php:682-689` resets an enrolled applicant's password to their plain student number and sets `force_password_reset = 1`. However, `force_password_reset` is never checked or enforced in `AuthController.php`, leaving the student's password set to their public student number indefinitely.

### 5.2 Applicant Portal (`app/Controllers/ApplicantController.php`, `EnrollController.php`, `DocumentController.php`)
* **Strengths:** Clean multi-step wizard; responsive tracking dashboard; clear document status indicators (`pending`, `verified`, `rejected`).
* **Vulnerabilities:**
  * `app/Routes/web.php:41-44` defines legacy routes mapping to `ApplicantController@applicationForm`, `processApplication`, `requirements`, and `uploadDocument`. None of these methods exist in `ApplicantController.php`, resulting in fatal PHP runtime errors if accessed.
  * In `DocumentController.php:322`, `viewDocument()` computes `$baseDir = __DIR__ . '/../../../uploads/documents/'`. Because `DocumentController` is located at `app/Controllers/DocumentController.php`, this resolves to `c:/xampp/htdocs/uploads/documents/` (one directory level too high), failing to locate files stored in `c:/xampp/htdocs/sia/uploads/documents/`.
  * Irregular subject selection in `EnrollController.php:87-142` is rendered in the UI but ignored in `processForm()`, resulting in silent loss of selected subjects.

### 5.3 Admissions & Evaluation (`app/Controllers/Admin/Admissions/AdmissionsController.php`)
* **Strengths:** Comprehensive applicant review modal; entrance exam and interview scoring; batch status updates; automated student number generation.
* **Vulnerabilities:**
  * Student number generation (`AdmissionsController.php:736`) uses `SELECT student_number FROM students ORDER BY id DESC LIMIT 1` without table locks or transactions, susceptible to race conditions and duplicate key exceptions under concurrent approvals.
  * Inconsistent finalization: Admissions can trigger `action=enroll`, while Cashier can independently trigger `finalizeStudentEnrollment()`, leading to split-brain records where students lack institutional emails.

### 5.4 Registrar & Curriculum (`app/Controllers/Admin/Registrar/RegistrarController.php`, `CollegeController.php`, `ShsController.php`)
* **Strengths:** Multi-department support (Senior High School vs. College); prerequisite checking; section capacity counters; curriculum status tracking (`draft`, `active`, `archived`).
* **Vulnerabilities:**
  * Broken Enrollment Queue: `college_enrollment_queue.php` and `shs_enrollment_queue.php` query `WHERE a.status = 'approved' AND sa.payment_status IN ('partial', 'paid')`. Because Cashier marks `a.status = 'enrolled'` immediately upon payment verification, the Registrar's queue is perpetually empty.
  * Active Curriculum Mutation: Modifying subject units or course descriptions in an `active` curriculum immediately alters calculations across historical student assessments because views re-query live subject units.

### 5.5 Finance & Cashiering (`app/Controllers/Admin/Finance/FinanceController.php`, `FeeController.php`)
* **Strengths:** Structured fee templates; payment tracking by channel (Cash, GCash, Bank Transfer); reference number tracking; automated assessment recalculation upon scholarship award.
* **Vulnerabilities:**
  * Missing Authorization: `FeeController::process()` lacks granular permission checks (`requirePermission()`), allowing any staff member with access to admin routes to modify institutional fee structures.
  * Receipt Number Collision: Official Receipt (OR) numbers are generated via non-atomic `SELECT MAX(or_number)` queries without database row locks (`FOR UPDATE`).

### 5.6 Scholarship Management (`app/Controllers/Admin/Scholarship/ScholarshipController.php`)
* **Strengths:** Supports fixed-amount and percentage-based discounts; automatically triggers `recalculateStudentAssessment()` when awards are approved.
* **Vulnerabilities:**
  * Schema Mismatch: `ScholarshipController.php:314-318` executes `UPDATE scholarship_recipients SET status = :status, remarks = :remarks WHERE id = :id`. The table `scholarship_recipients` lacks a `remarks` column, causing a fatal 500 error whenever scholarship staff updates a recipient.
  * Dead Database Table: Table `student_scholarships` exists in `schema.sql:583-597` but is completely unused across the entire codebase.

### 5.7 Clinic / Health Records (`app/Controllers/Admin/Clinic/ClinicController.php`, `HealthController.php`)
* **Strengths:** Complete medical profiles; tracking of emergency contacts, blood type, and physical exam findings; clearance status gating.
* **Vulnerabilities:**
  * Complete Lack of Role Validation: `ClinicController::process()` contains zero role or permission checks. Any authenticated staff member can grant medical clearance or view confidential health data.

### 5.8 System Administration (`app/Controllers/Admin/System/SystemController.php`, `ReportController.php`)
* **Strengths:** Activity logging table (`audit_logs`); system settings key-value store; database export capability.
* **Vulnerabilities:**
  * **Critical Privilege Escalation:** `SystemController::processUser()` allows any authenticated staff role to create or modify user accounts and assign the `superadmin` role.
  * **Arbitrary SQL Execution:** `SystemController::processBackup()` (`action=import`) executes uploaded `.sql` files directly via `$pdo->exec($sqlScript)` with zero validation or sanitization.
  * **Critical Unauthenticated Database Wipe:** `.htaccess:14-16` whitelists `database/migrations/setup_database.php` from authentication and URL rewriting, allowing any unauthenticated visitor to execute `DROP DATABASE IF EXISTS sia`.

---

## 6. Database Audit

### 6.1 Schema & Referential Integrity Overview
The database schema (`database/schema.sql`) defines 42 tables and views using InnoDB and `utf8mb4_unicode_ci`. Primary keys are consistently defined as unsigned integers, and foreign keys generally enforce relational constraints.

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                  DATABASE SCHEMA AUDIT                                  │
├─────────────────────────┬──────────────────────┬─────────────┬─────────────────────────┤
│ Issue                   │ Table / View         │ Column      │ Impact                  │
├─────────────────────────┼──────────────────────┼─────────────┼─────────────────────────┤
│ Schema-Code Mismatch    │ users                │ reset_token │ Fatal 500 on pwd reset  │
│ Schema-Code Mismatch    │ scholarship_recipient│ remarks     │ Fatal 500 on status upd │
│ Dead Table              │ student_scholarships │ (All)       │ Unused legacy clutter   │
│ View Calculation Flaw   │ student_academic_view│ units/sec   │ SHS records zeroed out  │
│ Unsafe Cascade Delete   │ applications         │ user_id     │ Deleting user wipes app │
│ Missing Unique Index    │ student_assessments  │ app_id+term │ Duplicate assessments   │
└─────────────────────────┴──────────────────────┴─────────────┴─────────────────────────┘
```

### 6.2 Discrepancies Between PHP Application & Database Schema
1. **Password Reset Token:**
   * Schema: `users.reset_token` (VARCHAR 100), `users.reset_token_expires_at` (DATETIME).
   * Code: `AuthController.php` references `reset_password_code` and `reset_password_expires_at`.
   * Result: Runtime SQL error `1054 Unknown column 'reset_password_code'`.
2. **Scholarship Recipient Remarks:**
   * Schema: `scholarship_recipients` has columns `id, scholarship_id, student_id, application_id, academic_year, semester, discount_amount, status, approved_by, approved_at, created_at, updated_at`.
   * Code: `ScholarshipController.php:315` attempts to update `remarks`.
   * Result: Runtime SQL error `1054 Unknown column 'remarks'`.
3. **Redundant Tables:**
   * Table `student_scholarships` (`schema.sql:583-597`) duplicates the purpose of `scholarship_recipients` and has 0 references in the PHP codebase.
4. **Flawed Database View (`student_academic_records_view`):**
   * Defined in `schema.sql:842-870`. It forces a `LEFT JOIN college_sections sec ON a.section_id = sec.id` and computes `total_enrolled_units` strictly from `college_enrollments`.
   * When queried for Senior High School students, section names are null or mismatched, and enrolled units always evaluate to 0.

### 6.3 Concurrency & Transaction Integrity
* Multiple critical workflows perform multi-table updates without database transactions (`$pdo->beginTransaction()`):
  * `EnrollController::processForm()` inserts into `applications`, `student_family_info`, and `student_education_history` without wrapping them in a transaction. A mid-process error leaves orphaned partial records.
  * Student number generation in `AdmissionsController::process()` and `functions.php::generateStudentNumber()` relies on `SELECT ... ORDER BY id DESC LIMIT 1` without row-level locking (`FOR UPDATE`), causing race condition collisions under concurrent enrollments.

---

## 7. Security Audit

### 7.1 Vulnerability Matrix

| ID | Vulnerability | Location / Evidence | Severity | Potential Impact |
|---|---|---|---|---|
| **SEC-01** | Unauthenticated Database Wipe | `.htaccess:14-16` & `setup_database.php` | 🔴 **CRITICAL** | Remote database destruction & data wipe |
| **SEC-02** | Administrative Privilege Escalation | `SystemController.php:31` (`processUser`) | 🔴 **CRITICAL** | Any staff member can create Superadmin |
| **SEC-03** | Arbitrary SQL Script Execution | `SystemController.php:240` (`processBackup`) | 🔴 **CRITICAL** | Remote SQL command execution & data tampering |
| **SEC-04** | Email OTP Verification Bypass | `AuthController.php:82` & `User.php:16` | 🔴 **CRITICAL** | Unverified accounts access system directly |
| **SEC-05** | Permanent Plain-Text Default Password | `AdmissionsController.php:684` | 🟠 **HIGH** | Account hijacking via public student number |
| **SEC-06** | Missing Granular Authorization | `ClinicController.php`, `FeeController.php` | 🟠 **HIGH** | Unauthorized fee and medical record tampering |
| **SEC-07** | Insecure Document Access Path | `DocumentController.php:322` | 🟡 **MEDIUM** | Inability to retrieve applicant documents |
| **SEC-08** | Missing Brute-Force Rate Limiting | `AuthController.php:40-120` | 🟡 **MEDIUM** | Susceptibility to credential stuffing |

### 7.2 Detailed Findings

#### 🔴 SEC-01: Unauthenticated Database Drop and Reset
* **File:** `.htaccess:14-16` and `database/migrations/setup_database.php`
* **Evidence:**
  ```apache
  # .htaccess
  RewriteCond %{REQUEST_URI} !/database/migrations/setup_database\.php$ [NC]
  RewriteRule ^(.*)$ public/index.php [QSA,L]
  ```
  `setup_database.php` begins with:
  ```php
  $pdo->exec("DROP DATABASE IF EXISTS sia; CREATE DATABASE sia; USE sia;");
  ```
* **Impact:** Anyone visiting `http://localhost/sia/database/migrations/setup_database.php` in a web browser without authentication completely destroys the production database and all enrolled student records.
* **Fix:** Remove the `.htaccess` whitelist rule immediately and add IP restriction / CLI-only checks (`php_sapi_name() === 'cli'`) to `setup_database.php`.

#### 🔴 SEC-02: Privilege Escalation to Superadmin
* **File:** `app/Controllers/Admin/System/SystemController.php` (method `processUser`)
* **Evidence:** The route `/admin/system/user_process.php` is protected only by the generic `'role:admin'` middleware. Inside `processUser()`, there is no check verifying whether the acting user is a `superadmin`. Any user with roles `cashier`, `clinic`, `scheduler`, or `admissions` can submit:
  ```http
  POST /admin/system/user_process.php
  action=create_user&role=superadmin&email=attacker@ttu.edu.ph&password=Password123!
  ```
* **Impact:** Total administrative compromise by low-privilege staff accounts.
* **Fix:** Add `$this->requirePermission('manage_users')` or enforce `if ($_SESSION['role'] !== 'superadmin') { unauthorized(); }`.

#### 🔴 SEC-04: Email OTP Verification Bypass
* **File:** `app/Controllers/AuthController.php:82` & `app/Models/User.php:16`
* **Evidence:**
  ```php
  // User::findByEmail
  SELECT id, first_name, last_name, email, password, role, is_active, department, permissions 
  FROM users WHERE email = :email LIMIT 1
  ```
  Notice `email_verified` is omitted. Then in `AuthController.php`:
  ```php
  if ($user['role'] === 'applicant' && (int)($user['email_verified'] ?? 1) === 0) { ... }
  ```
  Because `$user['email_verified']` is `null`, `$user['email_verified'] ?? 1` evaluates to `1`. `(int)1 === 0` is `false`, bypassing verification entirely.
* **Impact:** Unverified and fake email accounts can freely access applicant workflows.
* **Fix:** Include `email_verified` in `User::findByEmail()` query.

---

## 8. Business Logic Audit

### 8.1 State Machine Discrepancies
The system allows invalid or out-of-order state transitions:
1. **Bypassing Medical Clearance:** Admissions staff can approve an application (`status = 'approved'`) even if `health_records.clearance_status` is `pending` or `rejected`. The approval logic does not validate medical clearance before advancing the status.
2. **Double Assessment Generation:** If an applicant's section or program is modified after initial assessment, calling the assessment generator creates a duplicate record in `student_assessments` because the table lacks a unique constraint on `(application_id, academic_year, semester)`.
3. **Lost Irregular Student Subject Choices:** In `app/Views/applicant/enroll.php`, irregular students select custom subject codes. However, `EnrollController::processForm()` only persists basic academic details (`strand_course`, `year_level`, `status`), completely discarding the subject array. When Admissions opens the application, standard block subjects are assigned instead.

### 8.2 Broken Registrar Finalization Queue
The system provides two conflicting enrollment completion routines:
* **Routine A (Cashier):** In `FinanceController.php:342`, approving payment calls `finalizeStudentEnrollment()`, which sets `applications.status = 'enrolled'`.
* **Routine B (Registrar):** In `RegistrarController.php:89, 181`, the registrar queues filter:
  ```sql
  WHERE a.status = 'approved' AND sa.payment_status IN ('partial', 'paid')
  ```
Because Routine A executed first and marked the student as `'enrolled'`, Routine B returns 0 records. The Registrar can never view, validate, or finalize the student from their dedicated queue.

---

## 9. Finance / Assessment Audit

### 9.1 Tuition Calculation & Fee Itemization
Tuition calculations in `FeeController.php` and `functions.php::recalculateStudentAssessment()` compute:
$$\text{Total Assessment} = (\text{Total Units} \times \text{Lecture Rate}) + (\text{Lab Units} \times \text{Lab Rate}) + \text{Miscellaneous Fees} - \text{Scholarship Discount}$$

### 9.2 Critical Finance Vulnerabilities
1. **Lack of Assessment Immutability:** Assessment breakdown views (`app/Views/applicant/assessment.php`) calculate unit totals on the fly by querying current subject units from the `college_subjects` table. If the Registrar alters the unit value of a subject in an active curriculum, existing assessments dynamically display altered total amounts, breaking financial audit trails.
2. **Race Condition in Official Receipt Generation:** `FinanceController.php:360` generates receipt numbers using `SELECT receipt_number FROM payments ORDER BY id DESC LIMIT 1`. Concurrent cashier transactions generate duplicate receipt numbers, violating accounting integrity.
3. **Scholarship Recipient Update Fatal Crash:** In `ScholarshipController.php:315`, attempting to update scholarship status fails with `1054 Unknown column 'remarks'`, preventing scholarship cancellations or renewals.

---

## 10. Curriculum / Subject Audit

### 10.1 Versioning & Lifecycle States
Curricula in `college_curricula` and `shs_curricula` support three lifecycle states: `draft`, `active`, and `archived`.
* **Draft:** Editable; subjects can be added, modified, or removed.
* **Active:** Assigned to current cohorts; read-only.
* **Archived:** Historical; cannot be assigned to new students.

### 10.2 Audit Findings
* **Missing Guardrails on Active Curricula:** The backend controllers (`CollegeController.php`, `ShsController.php`) do not enforce read-only status on `active` curricula. Staff can modify prerequisite codes, course titles, and unit counts on active curricula currently in use by enrolled students.
* **Cascade Deletion Danger:** Deleting a subject from `college_subjects` does not check for existing enrollment records in `college_enrollments`, risking orphan references or foreign key constraint crashes.

---

## 11. Performance Audit

### 11.1 Query Bottlenecks & N+1 Problems
* **Applicant List Querying:** In `AdmissionsController::index()`, applicant records are queried in bulk, followed by separate per-applicant queries inside PHP loops to retrieve document status and entrance exam ratings. For 500 applicants, this triggers over 1,500 database queries instead of a single consolidated `LEFT JOIN`.
* **Section Enrollment Counts:** In `SchedulerController.php`, section capacity is computed on every page render by running `SELECT COUNT(*) FROM applications WHERE section_id = :id` for each section row in a `foreach` loop.

### 11.2 Indexing Gaps
* Missing index on `applications(status, academic_year, semester)`.
* Missing composite index on `student_assessments(student_id, payment_status)`.
* Missing index on `audit_logs(user_id, created_at)`.

---

## 12. UI/UX Audit

### 12.1 Visual Design & Aesthetics
* **Strengths:** Modern dark/light visual identity; clean typography; consistent use of TTU burgundy (`#800000`) and gold accent tones; responsive tables with Bootstrap 5; interactive alerts via SweetAlert2.
* **Friction Points:**
  1. **Silent Rejection / Error Handling:** Form validation failures in `EnrollController::processForm()` redirect back with generic session flash messages that do not scroll to or highlight the invalid input field.
  2. **Applicant Tracking Disconnect:** The application tracker does not display the specific reason when a document is marked `rejected`, requiring applicants to contact support manually.
  3. **Table Pagination Absence:** Admin applicant and student queues render all database records in a single table without server-side pagination, causing browser lag when viewing large student rosters.

---

## 13. Edge Cases & Failure Scenarios

1. **Form Double-Submission:** Form submit buttons on `enroll.php` and `finance/payment.php` lack JavaScript debounce/disable states on submit, allowing users to double-click and generate duplicate payments or duplicate enrollment applications.
2. **Mid-Workflow Curriculum Modification:** If an administrator modifies subject lecture/lab units while an applicant is between application approval and cashier assessment, the student is assessed on mismatched unit figures.
3. **Session Expiry During Multi-Step Application:** If an applicant's session expires while completing Step 3 of the enrollment form, submitting the form triggers a CSRF error that dumps the user to the login screen, losing all entered form data.

---

## 14. Testing Audit

* **Automated Tests:** **0% Coverage**. There is no `tests/` directory, no `phpunit.xml` configuration, and zero unit or integration tests for core logic (tuition calculation, scholarship deductions, grade prerequisites).
* **Manual Testing Reliance:** The system relies entirely on manual end-to-end browser verification.
* **Regression Risk:** High. Changes to `functions.php` or `AuthController.php` frequently introduce subtle bugs (such as the password reset column mismatch) that go unnoticed until triggered in manual operation.

---

## 15. Code Quality & Maintainability

* **Separation of Concerns:** Moderate. The system follows an MVC structure, but controllers average 700–1,200 lines and combine routing, validation, raw SQL queries, HTML rendering, and external integrations.
* **Code Duplication:** High. Student number generation logic is duplicated across `AdmissionsController.php` and `functions.php`. Currency formatting and payment status badges are duplicated across 8 separate view files.
* **Dead Code:** `app/Routes/web.php` references non-existent methods in `ApplicantController`. The `student_scholarships` table remains in the database schema without any application consumers.

---

## 16. Critical Findings (🔴 CRITICAL)

1. **Remote Database Wipe via Unauthenticated Migration Endpoint:** `.htaccess` allows unauthenticated access to `database/migrations/setup_database.php`, executing `DROP DATABASE IF EXISTS sia`.
2. **System Administration Privilege Escalation:** `SystemController::processUser()` allows any authenticated staff role to create or elevate accounts to `superadmin`.
3. **Arbitrary SQL Execution via Backup Restore:** `SystemController::processBackup()` executes arbitrary uploaded SQL files directly into MariaDB via `$pdo->exec()`.
4. **Email OTP Verification Bypass on Login:** `User::findByEmail()` omits `email_verified`, causing `AuthController::login()` to treat all applicants as verified.
5. **Runtime Fatal Error on Password Reset:** `AuthController.php` queries non-existent columns `reset_password_code` and `reset_password_expires_at` instead of `reset_token`.
6. **Three-Way Enrollment Finalization Conflict:** Cashier auto-finalization sets `status = 'enrolled'`, rendering Registrar queues permanently empty.

---

## 17. High Priority Findings (🟠 HIGH)

1. **Runtime Fatal Error on Scholarship Recipient Update:** `ScholarshipController.php:315` queries non-existent column `remarks` in `scholarship_recipients`.
2. **Permanent Default Student Password:** `AdmissionsController.php` sets initial student passwords to plain student numbers, and `force_password_reset` is never enforced.
3. **Missing Authorization in Clinic and Fee Controllers:** `ClinicController.php` and `FeeController.php` lack permission checks.
4. **Irregular Student Enrollment Data Loss:** Custom subject selections made by irregular students are discarded during form processing.
5. **Broken View for SHS Academic Records:** `student_academic_records_view` joins college sections and calculates zero units for SHS students.

---

## 18. Medium Priority Findings (🟡 MEDIUM)

1. **Document Storage Path Resolution Bug:** `DocumentController.php:322` uses incorrect relative path (`/../../../`), failing to view uploaded documents.
2. **Non-Atomic ID & Receipt Number Generation:** Student numbers and OR numbers generated via `SELECT ... ORDER BY id DESC LIMIT 1` risk collisions under load.
3. **Assessment Dynamic Recalculation Risk:** Assessment items dynamically reflect live subject unit changes instead of stored snapshots.
4. **Dead Routes in `web.php`:** Unimplemented routes in `ApplicantController` cause fatal errors if hit.
5. **Lack of Rate Limiting on Login:** No lockout or delay mechanism against brute-force password guessing.

---

## 19. Low Priority Findings (🔵 LOW)

1. **Dead Schema Table:** Table `student_scholarships` is obsolete and unused.
2. **Duplicated Utility Logic:** Currency formatting and badge HTML duplicated across multiple view templates.
3. **Missing Server-Side Table Pagination:** Admin queues load full tables without pagination.
4. **Lack of Automated Test Suite:** No PHPUnit setup or automated CI verification.

---

## 20. Complete Grading Table

Scores are evaluated strictly on the **Enrollment System** (LMS excluded) on a 0–10 scale:

| Category | Score | Rating | Summary Justification |
|---|:---:|---|---|
| **1. Architecture** | **7.0** | Good | Clean Hybrid MVC front-controller & router; Fat Controllers contain too much SQL. |
| **2. Code Quality** | **6.5** | Functional | Good readability; notable duplication and fat controller methods. |
| **3. Database Design** | **7.0** | Good | Proper 3NF structure; foreign keys defined; clean schema naming. |
| **4. Database Integrity** | **5.5** | Needs Improvement | Missing unique constraints on assessments; race conditions on sequence numbers. |
| **5. Security** | **4.0** | Poor / High Risk | Exposed unauthenticated database wipe endpoint; arbitrary SQL execution. |
| **6. Auth & Authorization** | **5.0** | Needs Improvement | Strong password hashing & sessions, but broken OTP gating & privilege escalation. |
| **7. Enrollment Workflow** | **6.0** | Functional | Comprehensive applicant lifecycle, but conflict between Cashier and Registrar. |
| **8. Business Logic** | **6.0** | Functional | Medical clearance not enforced before approval; irregular subject data lost. |
| **9. Curriculum Management** | **7.0** | Good | Multi-department support, but active curricula lack edit protections. |
| **10. Finance / Assessment** | **6.5** | Functional | Solid fee template structure; lack of assessment immutability; OR collision risk. |
| **11. Student Records** | **7.0** | Good | Comprehensive personal and academic tables; view broken for SHS students. |
| **12. Applicant Registration** | **7.5** | Good | Well-structured multi-step wizard, but OTP verification bypassable on login. |
| **13. Administrative Functions**| **6.5** | Functional | Comprehensive modules, but missing granular permission gates. |
| **14. Performance** | **6.5** | Functional | Responsive on small datasets; N+1 query patterns in applicant and section lists. |
| **15. UI / UX** | **8.5** | Very Good | Outstanding design aesthetics; responsive Bootstrap 5; great SweetAlert2 polish. |
| **16. Accessibility** | **7.0** | Good | Good semantic markup and contrast, but lacks full ARIA attributes. |
| **17. Error Handling** | **5.5** | Needs Improvement | Unhandled PDO column exceptions result in 500 error screens. |
| **18. Validation** | **7.0** | Good | Server-side validation present on most inputs; missing debounce on client. |
| **19. Testing** | **2.0** | Critical Problems | Zero automated tests; no PHPUnit configuration. |
| **20. Maintainability** | **6.5** | Functional | Easy to understand, but fat controllers make modifications regression-prone. |
| **21. Data Integrity** | **5.5** | Needs Improvement | Missing transactions on multi-table inserts; historical assessments mutable. |
| **22. System Reliability** | **5.5** | Needs Improvement | Runtime fatal errors on password reset and scholarship updates. |

---

## 21. Overall Score

### Calculation Methodology
The overall score is computed as a weighted average across core architectural disciplines:
* **Security & Access Control (20%):** Security (4.0), Auth & Authorization (5.0) $\rightarrow$ Avg: **4.50**
* **Database & Data Integrity (20%):** Database Design (7.0), Database Integrity (5.5), Data Integrity (5.5) $\rightarrow$ Avg: **6.00**
* **Business Logic & Workflows (20%):** Enrollment Workflow (6.0), Business Logic (6.0), Finance (6.5), Curriculum (7.0) $\rightarrow$ Avg: **6.38**
* **Architecture & Code Quality (15%):** Architecture (7.0), Code Quality (6.5), Maintainability (6.5), Error Handling (5.5) $\rightarrow$ Avg: **6.38**
* **Frontend & User Experience (15%):** UI/UX (8.5), Accessibility (7.0), Applicant Registration (7.5), Admin Functions (6.5) $\rightarrow$ Avg: **7.38**
* **Quality Assurance & Performance (10%):** Testing (2.0), Performance (6.5), Reliability (5.5) $\rightarrow$ Avg: **4.67**

$$\text{Overall Score} = (4.50 \times 0.20) + (6.00 \times 0.20) + (6.38 \times 0.20) + (6.38 \times 0.15) + (7.38 \times 0.15) + (4.67 \times 0.10) = \mathbf{6.35} / \mathbf{10}$$

### Rating
**6.35 / 10 — Functional but Significant Weaknesses**

---

## 22. Recommended Improvements

### Recommendation 1: Secure Migration Script and Restrict Database Resets
* **Problem:** `setup_database.php` can be called unauthenticated via HTTP to wipe the database.
* **Evidence:** `.htaccess:14-16` and `database/migrations/setup_database.php:1-20`.
* **Impact:** Instant remote database destruction.
* **Severity:** 🔴 Critical | **Priority:** P0 | **Difficulty:** Easy
* **Recommendation:** Remove the `.htaccess` exemption. In `setup_database.php`, add `if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }`.

### Recommendation 2: Implement Granular Permission Checks in Admin Controllers
* **Problem:** Staff roles can access `SystemController` to create superadmins or execute arbitrary SQL.
* **Evidence:** `SystemController.php:31` (`processUser`) and `web.php:69-152`.
* **Impact:** Privilege escalation and unauthorized system takeover.
* **Severity:** 🔴 Critical | **Priority:** P0 | **Difficulty:** Moderate
* **Recommendation:** Add `$this->requirePermission('manage_users')` and `$this->requirePermission('manage_system')` to all mutating methods in `SystemController`, `ClinicController`, and `FeeController`.

### Recommendation 3: Fix Password Reset and Scholarship SQL Column Mismatches
* **Problem:** Active controllers query columns that do not exist in MariaDB (`reset_password_code` vs. `reset_token`, missing `remarks` column).
* **Evidence:** `AuthController.php:640, 777` and `ScholarshipController.php:315`.
* **Impact:** 500 Internal Server Error when recovering passwords or updating scholarships.
* **Severity:** 🔴 Critical | **Priority:** P0 | **Difficulty:** Easy
* **Recommendation:** Update `AuthController.php` to query `reset_token` and `reset_token_expires_at`. Add `remarks TEXT NULL` to `scholarship_recipients` or update the query to match schema.

### Recommendation 4: Reconcile Enrollment Finalization Workflow
* **Problem:** Cashier payment verification sets `status = 'enrolled'`, leaving Registrar enrollment queues empty.
* **Evidence:** `FinanceController.php:342` and `RegistrarController.php:89`.
* **Impact:** Registrar cannot process students or issue official CORs.
* **Severity:** 🔴 Critical | **Priority:** P0 | **Difficulty:** Moderate
* **Recommendation:** Separate payment verification from enrollment completion. Cashier verification updates `sa.payment_status = 'paid'`. The student remains `status = 'approved'` until the Registrar completes section validation and clicks "Finalize Enrollment", transitioning status to `'enrolled'`.

### Recommendation 5: Enforce Email OTP and Mandatory First-Time Password Reset
* **Problem:** Login bypasses OTP check; students retain public student number as password.
* **Evidence:** `User.php:16`, `AuthController.php:82`, `AdmissionsController.php:684`.
* **Impact:** Account hijacking and unverified account proliferation.
* **Severity:** 🟠 High | **Priority:** P1 | **Difficulty:** Easy
* **Recommendation:** Include `email_verified` in `User::findByEmail()`. In `AuthController::login()`, check `if ($user['force_password_reset'])` and redirect immediately to a mandatory password change form.

### Recommendation 6: Persist Irregular Student Subject Selections
* **Problem:** Custom subjects selected by irregular applicants are discarded during form processing.
* **Evidence:** `EnrollController.php:87-142` and `EnrollController::processForm()`.
* **Impact:** Irregular students are enrolled in wrong subjects.
* **Severity:** 🟠 High | **Priority:** P1 | **Difficulty:** Moderate
* **Recommendation:** Create an intermediate table `application_subject_requests` to store custom subject selections submitted during enrollment. Load these selections into the Admissions evaluation screen.

---

## 23. Prioritized Improvement Roadmap

### Phase 1 — Critical Fixes (P0)
* [ ] Remove `setup_database.php` from `.htaccess` and enforce CLI-only execution.
* [ ] Align SQL column names in `AuthController.php` (`reset_token`, `reset_token_expires_at`).
* [ ] Add `remarks` column to `scholarship_recipients` in `schema.sql`.
* [ ] Fix `User::findByEmail()` to include `email_verified` in the SELECT clause.
* [ ] Add `requirePermission()` guards to `SystemController`, `ClinicController`, and `FeeController`.

### Phase 2 — Workflow & Data Integrity (P1)
* [ ] Reconcile Cashier vs. Registrar enrollment finalization workflow.
* [ ] Persist irregular applicant subject selections to `application_subject_requests`.
* [ ] Wrap multi-table inserts in `EnrollController` and `AdmissionsController` in database transactions (`beginTransaction`).
* [ ] Enforce `force_password_reset` redirect in `AuthController::login()`.
* [ ] Fix path resolution in `DocumentController::viewDocument()`.

### Phase 3 — Database & Architecture Refactoring (P2)
* [ ] Fix `student_academic_records_view` to support Senior High School subjects and sections.
* [ ] Drop unused `student_scholarships` table.
* [ ] Add unique constraint `(application_id, academic_year, semester)` to `student_assessments`.
* [ ] Enforce read-only locks on `active` curricula in `CollegeController` and `ShsController`.

### Phase 4 — Performance Optimization (P2)
* [ ] Refactor N+1 queries in `AdmissionsController::index()` into consolidated JOIN queries.
* [ ] Add missing database indexes on `applications`, `student_assessments`, and `audit_logs`.
* [ ] Add server-side pagination to applicant and student queues.

### Phase 5 — UI/UX & Quality Assurance (P3)
* [ ] Add button debounce / loading state spinners on all submission forms.
* [ ] Display specific rejection remarks on applicant document tracking portal.
* [ ] Set up PHPUnit testing framework and write integration tests for fee calculations.

---

## 24. Quick Wins

1. **Fix Password Reset Column Names:** Change 4 lines in `AuthController.php` (`reset_password_code` $\rightarrow$ `reset_token`) to restore password recovery functionality.
2. **Lock Down Setup Script:** Remove 1 line in `.htaccess` to eliminate the remote database wipe vulnerability.
3. **Include `email_verified` in User Query:** Add `email_verified` to `User::findByEmail()` to re-enable OTP gating.
4. **Fix Document Upload View Path:** Change `__DIR__ . '/../../../uploads/'` to `dirname(__DIR__, 2) . '/uploads/'` in `DocumentController.php` to resolve document 404s.

---

## 25. Long-Term Improvements

1. **Service Layer Extraction:** Extract assessment calculation, student number generation, and enrollment state transitions out of controllers into dedicated domain service classes (`AssessmentService`, `EnrollmentService`).
2. **Atomic Sequence Generation:** Replace `SELECT ... ORDER BY id DESC LIMIT 1` sequence generation with dedicated atomic counter tables (`counters` table with `SELECT ... FOR UPDATE`).
3. **Assessment Snapshot Architecture:** Store static copies of subject codes, names, and unit rates inside `assessment_items` to ensure historical financial records remain immutable forever.

---

## 26. Final Assessment

The TTU Enrollment System has a **solid architectural framework and exceptionally well-designed user interfaces**. The front controller, custom routing engine, CSRF/session middleware, and Bootstrap 5 visual design demonstrate high engineering competence.

However, the application is currently **unfit for production deployment** due to critical vulnerabilities: an exposed database wipe endpoint, administrative privilege escalation, broken OTP verification, runtime SQL crashes during password recovery and scholarship updates, and a broken finalization workflow between Cashier and Registrar.

Once the **Phase 1 Critical Fixes** and **Phase 2 Workflow Adjustments** are executed (estimated effort: 2–3 developer days), the TTU Enrollment System will become a highly secure, reliable, and production-ready institutional platform.
