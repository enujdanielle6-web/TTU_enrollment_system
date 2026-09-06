# Known Issues & Resolution Log

This document tracks architectural debt, bugs discovered during development, and their verified resolutions.

---

## 1. Resolved Issues Log

### Issue 1: Environment Variables Not Loading in Front Controller
- **Module:** [[Architecture]] / [[Security Overview]]
- **Symptoms:** SMTP authentication failed during applicant registration and email dispatch because `getenv('SMTP_USERNAME')` returned empty strings.
- **Root Cause:** `public/index.php` was initialized without loading the `.env` file into PHP environment superglobals.
- **Resolution:** Added an automated `.env` parser directly into `public/index.php` and added fallback environment loading inside `app/Helpers/functions.php`.

### Issue 2: LMS Sign-out Incorrect Redirection
- **Module:** [[LMS]]
- **Symptoms:** Clicking "Sign out" inside the Student or Faculty LMS redirected users to the general Admissions portal login (`/sia/auth/login.php`).
- **Resolution:** Implemented dedicated endpoints (`/auth/lms_student_logout.php` and `/auth/lms_faculty_logout.php`) in `LmsAuthController` and updated `AuthController::logout` to detect LMS sessions.

### Issue 3: Senior High School (SHS) LMS Course Isolation
- **Module:** [[LMS]]
- **Symptoms:** LMS initially only queried `college_enrollments`, causing SHS students to see zero courses.
- **Resolution:** Created `ShsEnrollmentRepository` alongside `CollegeEnrollmentRepository` in `app/Repositories/`, enabling dynamic course auto-provisioning for both academic levels.

### Issue 4: Registrar Subject Edit Modal Freeze
- **Module:** [[Registrar]]
- **Symptoms:** Clicking "Edit Subject" caused the screen to black out and freeze.
- **Root Cause:** Modal HTML markup was nested inside a table `<tbody>` tag, breaking Bootstrap's z-index backdrop calculation.
- **Resolution:** Extracted modal loops outside the `<table>` element to the bottom of the view template.

### Issue 5: Missing Active Scholars Route
- **Module:** [[Scholarship]]
- **Symptoms:** Navigating to "Active Scholars" returned a 404 Not Found error.
- **Resolution:** Registered `$router->get('/admin/scholarship/scholars.php', ...)` in `app/Routes/web.php` mapping to `ScholarshipController@scholars`.

### Issue 12: Curriculum Archive Modal Unclosed Tag Causing Black Screen
- **Module:** [[Registrar]] / `college_curriculum.php`
- **Symptoms:** Attempting to delete/archive a curriculum rendered an un-dismissible dark black overlay covering the entire viewport.
- **Root Cause:** In `app/Views/admin/registrar/college_curriculum.php`, the `#archiveCurriculumModal` element was missing its closing `</div>` tag, causing subsequent modals to nest within its backdrop tree.
- **Resolution:** Added the missing `</div>` tag on `#archiveCurriculumModal`.

### Issue 13: Missing `delivery_mode` Column in Section Subjects Tables
- **Module:** [[Scheduler]] / `SchedulerController.php`
- **Symptoms:** Clicking "Manage Schedule" on section directories failed silently and redirected back to the section listing with a generic database error.
- **Root Cause:** Queries in `SchedulerController::builder()` and `process()` selected `delivery_mode`, but the column was absent from `college_section_subjects` and `shs_section_subjects`.
- **Resolution:** Executed database schema migration adding `delivery_mode VARCHAR(50) NOT NULL DEFAULT 'Face-to-Face'` to both tables.

### Issue 14: Schedule Builder SPA Router Script Re-execution Collision
- **Module:** [[Scheduler]] / `schedule_builder.php` & `spa-router.js`
- **Symptoms:** When navigating to the Schedule Builder via SPA, clicking back, and re-opening the builder, the canvas and unscheduled sidebar remained blank until manual browser refresh.
- **Root Cause:** Re-evaluating top-level `const` and `let` declarations inside `spa-router.js` dynamic script injector triggered `Uncaught SyntaxError: Identifier 'type' has already been declared`, halting script execution before `render()` could run.
- **Resolution:** Encapsulated `schedule_builder.php` JavaScript inside an IIFE `(function() { ... })();`, globally attached event handlers to `window`, and added `data-spa="false"` to full-screen canvas navigation links.

### Issue 15: LMS Course Schema Mismatch (`lc.academic_level` Unknown Column)
- **Module:** [[LMS]] / `CollegeEnrollmentRepository.php`, `ShsEnrollmentRepository.php`, `schema.sql`
- **Symptoms:** Accessing the LMS Student Dashboard (`/sia/lms/student/dashboard.php`) threw `500 Internal Server Error` with `PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'lc.academic_level' in 'where clause'`.
- **Root Cause:** Live database and baseline DDL in `database/schema.sql` retained legacy static LMS table structures lacking the unified `academic_level` and `academic_section_id` proxy architecture.
- **Resolution:** Synchronized all 13 LMS table DDLs in `database/schema.sql` and seed definitions in `database/seed.sql`. Executed live schema migration and validated test suites.

### Issue 16: Cashier Payment Verification Nested Modal Infinite Recursion Crash
- **Module:** [[Finance]] / `cashier_payments.php`
- **Symptoms:** Clicking "Reject Payment" with empty remarks opened a warning modal; clicking "OK, Got It" froze the JavaScript main thread and crashed the browser tab.
- **Root Cause:** Stacking `#customWarningModal` on top of `#verifyModal` triggered a Bootstrap 5 `_enforceFocus` / focus-trap collision (`RangeError: Maximum call stack size exceeded`) during modal dismissal and focus restoration.
- **Resolution:** Replaced stacked warning and confirmation modals with inline form validation (`is-invalid` indicator + helper alert) and an inline confirmation box directly inside `#verifyModal`.

---

### Issue 6: Health Information Form Undefined Variable Block
- **Module:** [[Applicant Portal]] / `HealthController.php`
- **Symptoms:** Applicant health form submissions always failed with "Please fill out all required physical and emergency contact fields."
- **Root Cause:** `$heightVal = (float)$heightRaw;` and `$weightVal = (float)$weightRaw;` were invoked without extracting `$heightRaw = $request->input('height')` and `$weightRaw = $request->input('weight')`.
- **Resolution:** Restored `$heightRaw` and `$weightRaw` extraction with fallback handling for emergency/guardian contact values in `HealthController.php`. Verified submissions properly persist to `health_records`.

### Issue 7: Non-Existent `shs_curriculum` Table in SHS Controllers & API
- **Module:** [[Registrar]] / `ShsController.php` & `ApplicantApiController.php`
- **Symptoms:** Adding/removing subjects to SHS curriculum or querying SHS curriculum preview returns fatal `PDOException: Table 'sia.shs_curriculum' doesn't exist`.
- **Root Cause:** Queries referenced obsolete table `shs_curriculum` instead of `shs_curricula` and `shs_curriculum_subjects`.
- **Resolution:** Replaced all obsolete `shs_curriculum` table queries in `ApplicantApiController.php` (`getCurriculum` and `getFullCurriculum`) with joined queries across `shs_curricula sc` and `shs_curriculum_subjects c` filtered by `sc.status = 'active'`.

### Issue 8: Undefined Function `showErrorPage()`
- **Module:** `ScholarshipController.php`, `RegistrarController.php`, `ReportController.php`, `functions.php`
- **Symptoms:** Triggering database exception blocks caused fatal error `Call to undefined function showErrorPage()`.
- **Root Cause:** The helper function `showErrorPage()` was never implemented in `app/Helpers/functions.php`.
- **Resolution:** Implemented `showErrorPage(string $title, string $message, int $statusCode = 500): void` in `app/Helpers/functions.php` rendering a responsive, branded error UI with HTTP response code setting and navigation buttons.

### Issue 9: Erroneous Capacity Foreign Key Comparison in `Schedule.php`
- **Module:** [[Scheduler]] / `Schedule.php`
- **Symptoms:** Irregular subject schedule capacity checks reported inaccurate student counts.
- **Root Cause:** `$capStmt->execute([$off['id'], $off['subject_id']])` compared `shs_enrollments.shs_section_id` against `shs_section_subjects.id` (offering ID) instead of the actual `shs_section_id`.
- **Resolution:** Updated offerings queries to select `shs_section_id AS section_id` and `college_section_id AS section_id` and passed `$off['section_id']` into `$capStmt->execute()`.

### Issue 10: Setting Key Mismatch in Scholarship Recipient Enrollment
- **Module:** [[Scholarship]] / `ScholarshipController.php` & `functions.php`
- **Symptoms:** Approved scholarships were not saved to `scholarship_recipients` or recalculated in `student_assessments`.
- **Root Cause:** Controller and helper queried `system_settings` for `active_academic_year_id`, but `SystemController.php` saved it as `active_school_year`.
- **Resolution:** Updated queries in `ScholarshipController.php` and `recalculateStudentAssessment()` in `functions.php` to look for both `active_school_year` and `active_academic_year_id`, and added a fallback to the student's active application record (`school_year` and `semester`).

### Issue 11: Master Password Backdoor in LMS Student Login
- **Module:** [[LMS]] / `LmsAuthController.php`
- **Symptoms:** Student accounts could be logged into using plaintext string `'password123'` or the student's plain student number without password verification.
- **Root Cause:** Insecure plaintext comparison conditions `|| $password === $user['student_number'] || $password === 'password123'` in `LmsAuthController::loginProcess()`.
- **Resolution:** Removed the backdoor conditions and enforced standard Bcrypt `password_verify($password, $user['password'])` authentication for all student logins.

### Issue 17: Remote Unauthenticated Database Wipe via `setup_database.php`
- **Module:** [[Security Overview]] / `.htaccess` & `setup_database.php`
- **Symptoms:** An external HTTP GET request to `/sia/database/migrations/setup_database.php` executed a full database drop (`DROP DATABASE IF EXISTS sia`) and reseeded default records without authentication.
- **Root Cause:** Apache rewrite rules in `.htaccess` contained an explicit exemption `RewriteCond %{REQUEST_URI} !^/sia/database/migrations/setup_database\.php$` bypassing front-controller authentication and routing.
- **Resolution:** Removed the exemption rule from `.htaccess` and inserted a strict CLI environment assertion `if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Forbidden: CLI execution only.'); }` at the entry point of `setup_database.php`.

### Issue 18: Password Reset 500 Fatal Error (`reset_password_code` Column Mismatch)
- **Module:** [[Authentication & Email Verification]] / `AuthController.php`
- **Symptoms:** When users attempted to request or complete a password reset, the system crashed with `500 Internal Server Error` (`PDOException: Unknown column 'reset_password_code' in 'where clause'`).
- **Root Cause:** `AuthController.php` queried non-existent columns `reset_password_code` and `reset_password_expires_at`, while the database schema defined `reset_token` and `reset_token_expires_at`.
- **Resolution:** Aligned all SQL statements in `AuthController.php` (lines 640, 777, 790, 851) to query `reset_token` and `reset_token_expires_at`.

### Issue 19: Email OTP Verification Bypass in Authentication
- **Module:** [[Authentication & Email Verification]] / `User.php` & `AuthController.php`
- **Symptoms:** Unverified applicant accounts could log into the portal directly, completely bypassing mandatory 6-digit email OTP verification.
- **Root Cause:** `User::findByEmail()` omitted `email_verified` from its `SELECT` column list. In `AuthController::login()`, the expression `$user['email_verified'] ?? 1` defaulted to `1` (verified).
- **Resolution:** Updated `User::findByEmail()` to explicitly select `email_verified`, restoring the mandatory verification redirect to `verify_otp.php`.

### Issue 20: Scholarship Recipient Update 500 Fatal Error (Missing `remarks` Column)
- **Module:** [[Scholarship]] / `ScholarshipController.php`
- **Symptoms:** Approving or rejecting a scholarship application failed with `500 Internal Server Error` (`PDOException: Unknown column 'remarks' in 'field list'`).
- **Root Cause:** `ScholarshipController::updateRecipientStatus()` executed `UPDATE scholarship_recipients SET status = :status, remarks = :remarks WHERE id = :id`, but the table lacked the `remarks` column.
- **Resolution:** Added `remarks TEXT NULL` to `scholarship_recipients` in MariaDB and `database/schema.sql`.

### Issue 21: Document Viewer 404 Path Error
- **Module:** [[Applicant Portal]] / `DocumentController.php`
- **Symptoms:** Admissions and applicants viewing uploaded document attachments encountered 404 file not found errors.
- **Root Cause:** `DocumentController::viewDocument()` resolved the upload root using `__DIR__ . '/../../../uploads/documents/'`, resolving outside the project root (`c:/xampp/htdocs/uploads/documents/` instead of `/sia/uploads/documents/`).
- **Resolution:** Corrected path resolution to `dirname(__DIR__, 2) . '/uploads/documents/'`.

### Issue 22: Three-Way Finalization Collision Between Cashier, Admissions & Registrar
- **Module:** [[Finance]], [[Admissions]], [[Registrar]]
- **Symptoms:** Registrar enrollment queues (`college_enrollment_queue.php` and `shs_enrollment_queue.php`) were permanently empty; students skipped institutional onboarding and welcome credential dispatch.
- **Root Cause:** The Cashier module (`FinanceController::verifyPayment()`) prematurely called `finalizeStudentEnrollment()`, transitioning applications directly to `enrolled`. The Registrar's queue expected status `approved`, so paid students never appeared.
- **Resolution:** Decoupled Cashier payment verification by introducing the intermediate state `payment_verified`. Transferred final enrollment ownership exclusively to the Registrar via `RegistrarController::finalizeEnrollment()`, which generates the student number, institutional `@ttu.edu.ph` email, hashes credentials, sets `force_password_reset = 1`, and automatically enrolls the student into section subjects.

### Issue 23: Silent Data Loss of Irregular Student Custom Subject Selections
- **Module:** [[Admissions]], [[Applicant Portal]] / `EnrollController.php` & `detail.php`
- **Symptoms:** Irregular applicants customized their subject selections during multi-step registration, but the subjects were never saved to the database. Admissions evaluated them without knowing what subjects they requested.
- **Root Cause:** `EnrollController::processForm()` parsed `$selectedSubjects` into session state for form repopulation, but had no SQL statement inserting them upon successful form submission.
- **Resolution:** Created `application_subject_requests` table in MariaDB. Wrapped `EnrollController::processForm()` in PDO transactions to persist custom requested subjects, and updated `detail.php` to display these subject requests during admissions review.

### Issue 24: Senior High School Units Miscalculation in Academic Records View
- **Module:** [[Database]] / `student_academic_records_view`
- **Symptoms:** Querying academic records for SHS students returned 0 units or null section values.
- **Root Cause:** `student_academic_records_view` performed an `INNER JOIN college_enrollments`, excluding all Senior High School students.
- **Resolution:** Recreated `student_academic_records_view` as a `UNION ALL` across both `college_enrollments` and `shs_enrollments`, unifying unit and section calculations across all departments.

### Issue 25: Mutable Assessment Total Re-queries & Financial Concurrency Race Conditions
- **Module:** [[Finance]] / `AssessmentService.php`, `FinanceController.php`
- **Symptoms:** Assessment totals dynamically re-calculated whenever curriculum subjects or unit rates were edited, altering historic financial balances. Furthermore, receipt numbers were generated using non-atomic `MAX(receipt_number)` queries prone to collisions under concurrent cashier operations.
- **Root Cause:** Assessments stored only lump-sum totals; itemized breakdowns were re-queried on every page load. Receipt generation lacked row locking or sequence tables.
- **Resolution:** Created `assessment_items` table and deployed `AssessmentService::snapshotAssessmentItems()`, freezing tuition, lab, misc, and discount line items at the moment of assessment. Created `receipt_sequences` table with atomic `generateAtomicReceiptNumber()` strictly guaranteeing monotonic unique sequences (`REC-YYYYMMDD-XXXX`).

### Issue 26: Registrar Student Masterlist Browser Lag & Memory Exhaustion
- **Module:** [[Registrar]] / `RegistrarController.php` & `students.php`
- **Symptoms:** Accessing the student masterlist loaded all institutional records into the DOM at once, resulting in heavy memory consumption, slow response times, and rendering freezes.
- **Root Cause:** `RegistrarController::students()` executed an unpaginated query, delegating filtering to client-side DOM manipulation.
- **Resolution:** Implemented server-side pagination (25, 50, 100 records per page) with parameterized dynamic filtering, global KPI aggregate counts, records-per-page selection, pagination navigation with parameter preservation, and synchronized CSV export.

---

## 2. Active Technical Debt & Discovered Codebase Defects

*(All documented active codebase defects have been remediated and verified. All 26 issues are 100% resolved.)*

---
**Related:**
- [[System Architecture]]
- [[LMS Navigation and Render Bugs Fixed]]
- [[Development Guide]]
- [[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]
