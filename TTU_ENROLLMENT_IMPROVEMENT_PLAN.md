# TTU Enrollment System — Improvement & Optimization Plan

**Target System:** Triple T University (TTU) Enrollment System  
**Repository Location:** `c:\xampp\htdocs\sia`  
**Date:** September 2026  
**Auditor / Architecture Team:** Senior Software Architect, Full-Stack Engineer, Security Engineer, QA Engineer, Database Architect, UI/UX Auditor  
**Scope Boundary:** **ENROLLMENT SYSTEM ONLY**. The Learning Management System (LMS) is unfinished and strictly excluded from all analyses, recommendations, and roadmaps.

---

## 1. Executive Summary

Following the baseline repository audit ([TTU_ENROLLMENT_AUDIT.md](file:///c:/xampp/htdocs/sia/TTU_ENROLLMENT_AUDIT.md)), this document establishes the **authoritative engineering blueprint and improvement plan** for the Triple T University (TTU) Enrollment System.

The TTU Enrollment System is an ambitious, functionally rich institutional portal built on a custom Vanilla PHP Hybrid MVC architecture. It features a custom front controller (`public/index.php`), dynamic HTTP routing with named regex parameters (`app/Core/Router.php`), middleware pipeline execution, centralized PDO database connectivity, and an aesthetically exceptional Bootstrap 5 / SweetAlert2 presentation layer.

However, the platform currently suffers from:
1. **Critical Security Exposures:** An unauthenticated database reset endpoint in [.htaccess](file:///c:/xampp/htdocs/sia/.htaccess), missing role authorization in admin sub-controllers, arbitrary SQL execution in backups, and email OTP verification bypass.
2. **Runtime Fatal Schema Mismatches:** Queries referencing non-existent columns (`reset_password_code` in [AuthController.php](file:///c:/xampp/htdocs/sia/app/Controllers/AuthController.php), `remarks` in [ScholarshipController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scholarship/ScholarshipController.php)).
3. **Broken Multi-Role Enrollment State Machine:** Competing finalization routines between Cashier and Registrar leave Registrar queues empty and bypass institutional onboarding.
4. **Data Loss & Concurrency Risks:** Silent loss of irregular applicant subject selections, lack of assessment snapshot immutability, and race conditions in student number and receipt number generation.

This improvement plan details **where, why, and how** each component must be optimized, providing exact file-by-file targets, service extraction blueprints, database migrations, state machine definitions, and an incremental execution roadmap that preserves working functionality throughout the refactoring lifecycle.

---

## 2. Audit Findings Revalidation

Before formulating improvements, all findings in `TTU_ENROLLMENT_AUDIT.md` were independently cross-referenced against the live source code and database schema.

| Previous Audit Finding | Status | Technical Revalidation & Correction |
|---|:---:|---|
| **Remote DB Wipe via `setup_database.php`** | **CONFIRMED** | `.htaccess:14-16` whitelists `database/migrations/setup_database.php` from authentication and rewriting. Calling it over HTTP executes `DROP DATABASE IF EXISTS sia` and reinstalls default seeds. |
| **Privilege Escalation in `SystemController`** | **CONFIRMED** | `SystemController::processUser` accepts `role=superadmin` without validating that `$_SESSION['user_role'] === 'superadmin'`. Outer route middleware only checks generic `:admin`. |
| **Email OTP Bypass in `AuthController::login`** | **CONFIRMED** | `User::findByEmail()` (`app/Models/User.php:16`) omits `email_verified` from the `SELECT` list. In `AuthController.php:82`, `$user['email_verified'] ?? 1` evaluates to `1`, bypassing OTP verification for unverified accounts. |
| **Runtime 500 on Password Reset** | **CONFIRMED** | `AuthController.php:640, 777, 790, 851` queries `reset_password_code` and `reset_password_expires_at`. The schema (`users` table) defines `reset_token` and `reset_token_expires_at`. Password reset throws an unhandled `PDOException: 1054 Unknown column`. |
| **Runtime 500 on Scholarship Recipient Update** | **CONFIRMED** | `ScholarshipController.php:315` executes `UPDATE scholarship_recipients SET status = :status, remarks = :remarks WHERE id = :id`. MariaDB table `scholarship_recipients` lacks a `remarks` column. |
| **Cashier vs. Registrar Finalization Conflict** | **CONFIRMED** | `FinanceController.php:342` calls `finalizeStudentEnrollment()`, immediately setting `applications.status = 'enrolled'`. `RegistrarController.php:89, 181` filters `WHERE a.status = 'approved'`, making the Registrar queue permanently empty. |
| **Irregular Subject Selection Data Loss** | **CONFIRMED** | `EnrollController.php:247-257` parses `$selectedSubjects` into `$oldData['selected_subjects']` for form re-population, but lines 286–304 never insert or persist these subjects into any database table when the form succeeds. |
| **Document View 404 Path Error** | **CONFIRMED** | `DocumentController.php:322` computes `__DIR__ . '/../../../uploads/documents/'`. From `app/Controllers/DocumentController.php`, three directories up is `c:/xampp/htdocs/uploads/documents/` (missing the `/sia/` directory level), causing 404s. |
| **Student Number Generation Query Target** | **PARTIALLY CORRECT (CORRECTED)** | Audit noted `SELECT student_number FROM students`. Correction: No `students` table exists in TTU. The query targets `users` (`SELECT student_number FROM users WHERE student_number LIKE :prefix ORDER BY student_number DESC LIMIT 1`). The non-atomic concurrency race condition is confirmed, but the target table is `users`. |
| **Assessment Line Items Storage** | **PARTIALLY CORRECT (CORRECTED)** | Audit noted `assessment_items` table. Correction: No `assessment_items` table exists in MariaDB schema. `student_assessments` stores only lump sums. Subject line items are dynamically queried on every view via a three-tier fallback query across `college_enrollments`, `college_section_subjects`, and `college_curriculum_subjects`. |
| **Dead Table `student_scholarships`** | **CONFIRMED** | `database/schema.sql:583-597` defines `student_scholarships`. Grep across the entire `app/` codebase returned 0 references. The application uses `scholarship_recipients`. |

---

## 3. System-Wide Improvement Map

Analysis of all 23 functional modules and architectural components:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                 TTU SYSTEM-WIDE IMPROVEMENT MAP                                  │
├──────────────────────┬───────────────────────────────┬───────────────────────────────────────────┤
│ Module               │ Current State                 │ Target Improved State                     │
├──────────────────────┼───────────────────────────────┼───────────────────────────────────────────┤
│ 1. Authentication    │ Bcrypt + sessions; OTP broken │ Enforced OTP; rate limiting; reset token  │
│ 2. Applicant Reg     │ Multi-step wizard; no draft   │ Auto-saving draft; phone regex validation │
│ 3. Applicant Portal  │ Clean dashboard; bad 404 paths│ Corrected file paths; real rejection notes│
│ 4. Documents         │ Basic upload; 404 viewer bug  │ Sanitized filenames; secure viewer stream │
│ 5. Clinic / Medical  │ Health profile; zero authz    │ Granular `medical.review` check; clearance│
│ 6. Admissions        │ Solid review UI; split logic  │ Academic evaluation only; no auto-enroll  │
│ 7. Registrar         │ Empty queue bug; redirect link│ Authoritative finalizer; COR generator    │
│ 8. Curriculum        │ Active curricula editable     │ Immutability locks on `active` curricula  │
│ 9. Subjects          │ Catalog exists; loose deletes │ Prerequisite graph; soft delete safeguards│
│ 10. Sections         │ N+1 capacity queries          │ Atomic enrollment counters; conflict checks│
│ 11. Scheduling       │ Timetable UI; overlap bugs    │ Room/faculty double-booking validation    │
│ 12. Finance          │ Fee templates; mutable math   │ Dedicated `AssessmentService`; snapshots  │
│ 13. Assessment       │ Lump-sum only; live re-query  │ Immutable `assessment_items` table        │
│ 14. Cashier          │ Non-atomic receipt numbers    │ Sequence generator table; ledger audit    │
│ 15. Scholarship      │ Schema crash; dead table      │ Add `remarks`; drop dead table; auto-calc │
│ 16. Student Records  │ Broken SHS view calculation   │ Unified view for SHS & College records    │
│ 17. Reports          │ CSV export; unpaginated       │ Streaming CSV; date range filters         │
│ 18. Administration   │ Privilege escalation; raw SQL │ Strict `superadmin` check; backup sandboxing│
│ 19. Database         │ Missing composite indexes     │ Added indexes, transactions, row locks    │
│ 20. Routing          │ Broad `:admin` group check    │ Sub-group role parameters; removed dead   │
│ 21. Middleware       │ Solid session/CSRF checks     │ Route-level permission middleware         │
│ 22. Core Framework   │ Vanilla MVC; fat controllers  │ Clean Service Layer for core workflows    │
│ 23. UI/UX            │ Beautiful; form resubmit risk │ Submit debouncing; jump-to-error handling │
└──────────────────────┴───────────────────────────────┴───────────────────────────────────────────┘
```

---

## 4. Architecture Improvements

### 4.1 What Should Remain As-Is
* **Front Controller Architecture (`public/index.php`):** Centralized bootstrapping, strict error handling, session start, and environment initialization work reliably.
* **HTTP Router (`app/Core/Router.php`):** The regex route matcher with named parameters and middleware pipeline is lightweight, flexible, and fast.
* **Core Abstractions (`Request.php`, `Response.php`, `Database.php`):** Clean separation of HTTP inputs and outputs; centralized PDO singleton with `ERRMODE_EXCEPTION`.
* **Session & CSRF Middleware:** `SessionSecurityMiddleware.php` and `CsrfMiddleware.php` provide enterprise-grade session fixation defense, IP/User-Agent verification, and anti-CSRF token lifecycle management.
* **Vanilla PHP / Bootstrap 5 Presentation Layer:** Fast, zero-compile asset pipeline using standard Bootstrap 5.3, FontAwesome 6, and custom modular CSS.

### 4.2 Candidate Refactors & Service Layer Extraction

To resolve fat controllers and eliminate massive code duplication across the system, business logic must be extracted into dedicated domain services in `app/Services/`.

```
               ┌────────────────────────────────────────────────────────┐
               │                  HTTP Controllers                      │
               │  - AuthController          - AdmissionsController      │
               │  - EnrollController        - RegistrarController       │
               │  - FinanceController       - ScholarshipController     │
               └───────────┬────────────────────────────┬───────────────┘
                           │                            │
             Delegates API │              Delegates Web │
             & HTTP tasks  ▼                            ▼
               ┌────────────────────────────────────────────────────────┐
               │                     SERVICE LAYER                      │
               │              (Domain Business Logic Only)              │
               ├────────────────────────────┬───────────────────────────┤
               │ EnrollmentService          │ AssessmentService         │
               │ StudentNumberService       │ PaymentService            │
               │ ScholarshipService         │ CurriculumService         │
               └───────────┬────────────────────────────┬───────────────┘
                           │                            │
            Queries via    │             Persists via   │
            Data Container ▼                            ▼
               ┌────────────────────────────┐  ┌────────────────────────┐
               │       Anemic Models        │  │     Database (PDO)     │
               │ (User, Application, etc.)  │  │  (Atomic Transactions) │
               └────────────────────────────┘  └────────────────────────┘
```

#### Proposed Dedicated Services

#### 1. `App\Services\AssessmentService`
* **Current Location:** Logic duplicated across `AdmissionsController.php:750-910`, `FinanceController.php:70-170`, `ApplicantController.php:370-465`, and `functions.php:750-840`.
* **Responsibilities:**
  * Resolve applicable fee template for an applicant's program, grade level, and semester.
  * Calculate lecture, lab, miscellaneous, registration, and other fees.
  * Snapshot line items into `student_assessments` and `assessment_items`.
  * Recalculate net balances when scholarships or payments are recorded.
* **Key Methods:**
  * `generateAssessment(int $applicationId): int`
  * `recalculateAssessment(int $userId): void`
  * `getAssessmentBreakdown(int $assessmentId): array`
* **Controllers Benefiting:** `AdmissionsController`, `FinanceController`, `ApplicantController`, `ScholarshipController`.

#### 2. `App\Services\EnrollmentService`
* **Current Location:** Fragmented across `EnrollController.php`, `AdmissionsController.php`, `FinanceController.php`, and `functions.php::finalizeStudentEnrollment()`.
* **Responsibilities:**
  * Coordinate enrollment state machine transitions (`draft` $\rightarrow$ `enrolled`).
  * Validate prerequisites and section capacity before assignment.
  * Persist regular and irregular subject selections.
  * Execute final enrollment provisioning (student number, institutional email, welcome credentials).
* **Key Methods:**
  * `submitApplication(int $userId, array $data, array $subjects = []): int`
  * `approveApplication(int $applicationId, int $evaluatorId, array $scores): void`
  * `finalizeEnrollment(int $applicationId, int $registrarId): void`
* **Controllers Benefiting:** `EnrollController`, `AdmissionsController`, `RegistrarController`, `FinanceController`.

#### 3. `App\Services\StudentNumberService`
* **Current Location:** Duplicated in `functions.php:569` and `AdmissionsController.php:648`.
* **Responsibilities:**
  * Atomic generation of formatted student IDs (`YYYY-XXXXXX`).
  * Concurrency locking using a dedicated sequence table or `SELECT ... FOR UPDATE`.
* **Key Methods:**
  * `generateNextNumber(int $year): string`
* **Controllers Benefiting:** `AdmissionsController`, `RegistrarController`, `EnrollmentService`.

#### 4. `App\Services\PaymentService`
* **Current Location:** Embedded in `FinanceController.php:300-370`.
* **Responsibilities:**
  * Atomic official receipt number generation (`OR-YYYY-XXXXX`).
  * Ledger transaction insertion wrapped in database transactions.
  * Triggering assessment balance updates without prematurely finalizing enrollment.
* **Key Methods:**
  * `recordPayment(int $assessmentId, float $amount, string $method, ?string $refNo, int $cashierId): array`
  * `generateReceiptNumber(): string`
* **Controllers Benefiting:** `FinanceController`, `ApplicantApiController`.

#### 5. `App\Services\ScholarshipService`
* **Current Location:** Fragmented in `ScholarshipController.php` and `functions.php`.
* **Responsibilities:**
  * Validating grant eligibility.
  * Applying fixed or percentage discounts.
  * Invoking `AssessmentService::recalculateAssessment` within a database transaction.
* **Key Methods:**
  * `grantScholarship(int $scholarshipId, int $applicationId, int $approverId): void`
  * `revokeScholarship(int $recipientId, string $reason, int $actorId): void`
* **Controllers Benefiting:** `ScholarshipController`.

---

## 5. Enrollment Workflow Redesign

### 5.1 The Root Problem
Currently, three separate actors attempt to manage enrollment finalization:
* **Cashier:** [FinanceController.php:342](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php#L342) automatically calls `finalizeStudentEnrollment()`, changing status to `enrolled`.
* **Registrar:** [RegistrarController.php:89](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php#L89) expects status to be `approved` to display students in the enrollment queue, but finds 0 records because Cashier already marked them `enrolled`.
* **Admissions:** [AdmissionsController.php:642](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Admissions/AdmissionsController.php#L642) contains the only code that generates `ttu_email` and sends credential emails, but this is bypassed if Cashier finalizes first.

### 5.2 Authoritative State Machine & Ownership Matrix

```
  [1. draft] ──────────► (Applicant completes form)
        │
        ▼
  [2. submitted] ──────► (Applicant uploads docs & health profile)
        │
        ▼
  [3. under_review] ───► (Admissions & Clinic review submissions)
        ├────────────────────────────────┬───────────────────────────────┐
        ▼                                ▼                               ▼
  (Admissions checks)            (Clinic checks)              (Correction needed)
  - Doc verification             - Medical clearance                     │
  - Entrance Exam score          - Clinic signoff                        ▼
  - Interview rating                     │                    [correction_required]
        │                                │                               │
        └────────────────┬───────────────┘                               │
                         ▼                                               ▼
               [4. approved] ◄───────────────────────────────── (Applicant resubmits)
                         │
                         ▼ (System automatically generates assessment)
               [5. assessed]
                         │
                         ▼ (Cashier records initial/full payment)
               [6. payment_verified]
                         │
                         ▼ (Registrar verifies section & subjects)
               [7. registrar_review]
                         │
                         ▼ (Registrar clicks "Finalize Enrollment")
               [8. enrolled]
                 - Official Student Number assigned
                 - Institutional TTU Email created (@ttu.edu.ph)
                 - Welcome credentials emailed via PHPMailer
                 - Certificate of Registration (COR) unlocked
```

### 5.3 Clear Ownership Boundaries

| State Transition | Permitted Role | Mutated Fields | Business Logic & Side Effects |
|---|:---:|---|---|
| `draft` $\rightarrow$ `submitted` | `applicant` | `status = 'submitted'`, `submitted_at = NOW()` | Validates required fields; notifies admissions queue. |
| `submitted` $\rightarrow$ `correction_required` | `admissions` | `status = 'correction_required'`, `admin_feedback` | Sends email to applicant detailing corrections needed. |
| `submitted` $\rightarrow$ `under_review` | `admissions` | `status = 'under_review'` | Locks application from applicant edits during review. |
| Clearance sign-off | `clinic` | `health_records.clearance_status = 'cleared'` | Requires `medical.review` permission; logs audit. |
| `under_review` $\rightarrow$ `approved` | `admissions` | `status = 'approved'`, `exam_score`, `interview_rating` | **Requires clinic clearance.** Automatically triggers `AssessmentService::generateAssessment()`. |
| Payment confirmation | `cashier` | `sa.payment_status = 'paid'`, `sa.total_paid` | **DOES NOT mark student as enrolled.** Transitions application to `payment_verified`. Inserts into `payments`. |
| `payment_verified` $\rightarrow$ `enrolled` | `admin` / `registrar` | `status = 'enrolled'`, `student_number`, `ttu_email` | **Owned exclusively by Registrar.** Generates student number, creates `ttu_email`, sends login credentials, generates official COR. |

---

## 6. Authentication & Security Improvements

### 6.1 Mitigation Blueprint

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                              SECURITY MITIGATION BLUEPRINT                             │
├─────────┬──────────────────────────────┬──────────────────────────────┬────────────────┤
│ Ref ID  │ Vulnerability                │ Exact Target File            │ Required Fix   │
├─────────┼──────────────────────────────┼──────────────────────────────┼────────────────┤
│ SEC-01  │ Remote DB Wipe via setup     │ `.htaccess` & `setup_db.php` │ Remove exempt; │
│         │                              │                              │ add CLI guard  │
│ SEC-02  │ Superadmin Escalation        │ `SystemController.php:31`    │ Guard with     │
│         │                              │                              │ superadmin check│
│ SEC-03  │ Arbitrary SQL Restore        │ `SystemController.php:240`   │ Strict whitelist│
│         │                              │                              │ & parser test  │
│ SEC-04  │ OTP Login Bypass             │ `User.php:16`                │ Add `email_    │
│         │                              │                              │ verified` col  │
│ SEC-05  │ Public Plaintext Password    │ `AuthController.php:110`     │ Intercept login│
│         │                              │                              │ for reset      │
│ SEC-06  │ Missing Admin Sub-Route RBAC │ `web.php:69-152`             │ Add granular   │
│         │                              │                              │ sub-group roles│
│ SEC-07  │ Document 404 Resolution      │ `DocumentController.php:322` │ Use dirname()  │
│         │                              │                              │ path resolution│
│ SEC-08  │ Brute-Force Vulnerability    │ `AuthController.php:40`      │ Enforce lockout│
│         │                              │                              │ after 5 fails  │
└─────────┴──────────────────────────────┴──────────────────────────────┴────────────────┘
```

### 6.2 Code-Level Implementation Details

#### SEC-01: Remove Migration Exemption & Add CLI Guard
In `.htaccess`, delete:
```apache
RewriteCond %{REQUEST_URI} !/database/migrations/setup_database\.php$ [NC]
```
In `database/migrations/setup_database.php`, add at line 2:
```php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Access Denied: Migration scripts can only be executed via CLI.');
}
```

#### SEC-02: Prevent Privilege Escalation in `SystemController`
In `app/Controllers/Admin/System/SystemController.php`, add to `processUser()`:
```php
if (($_POST['role'] ?? '') === 'superadmin' && ($_SESSION['user_role'] ?? '') !== 'superadmin') {
    $_SESSION['admin_error'] = 'Only Superadministrators can create or promote Superadmin accounts.';
    $response->redirect('/sia/admin/system/users.php');
    return;
}
```

#### SEC-04: Fix Email OTP Verification Bypass
In `app/Models/User.php:16`, update `findByEmail()` query:
```sql
SELECT id, first_name, last_name, email, password, role, is_active, 
       department, permissions, email_verified 
FROM users WHERE email = :email LIMIT 1
```

#### SEC-05: Enforce First-Time Forced Password Reset
In `app/Controllers/AuthController.php:125`, add:
```php
if (!empty($user['force_password_reset'])) {
    $_SESSION['force_password_reset_required'] = true;
    $response->redirect('/sia/auth/change_password.php');
    return;
}
```
In `SessionSecurityMiddleware.php`, if `$_SESSION['force_password_reset_required']` is true and the current route is not `/auth/change_password.php` or `/auth/logout.php`, redirect immediately to `/auth/change_password.php`.

#### SEC-06: Route Group RBAC in `app/Routes/web.php`
Split the monolithic `:admin` route group into specific role groups supported natively by `Router.php`:
```php
// System Administrator Only
$router->group(['prefix' => '/sia/admin/system', 'middleware' => ['App\Middleware\RoleMiddleware:superadmin']], function($router) {
    $router->get('/users.php', ['App\Controllers\Admin\System\SystemController', 'users']);
    $router->post('/user_process.php', ['App\Controllers\Admin\System\SystemController', 'processUser']);
    $router->get('/backup.php', ['App\Controllers\Admin\System\SystemController', 'backup']);
    $router->post('/backup_process.php', ['App\Controllers\Admin\System\SystemController', 'processBackup']);
});

// Cashier & Finance Only
$router->group(['prefix' => '/sia/admin/finance', 'middleware' => ['App\Middleware\RoleMiddleware:cashier,superadmin']], function($router) {
    $router->get('/cashier_dashboard.php', ['App\Controllers\Admin\Finance\FinanceController', 'dashboard']);
    $router->post('/cashier_process.php', ['App\Controllers\Admin\Finance\FinanceController', 'process']);
    $router->get('/fees.php', ['App\Controllers\Admin\Finance\FeeController', 'index']);
});

// Clinic Only
$router->group(['prefix' => '/sia/admin/clinic', 'middleware' => ['App\Middleware\RoleMiddleware:clinic,superadmin']], function($router) {
    $router->get('/medical_clearance.php', ['App\Controllers\Admin\Clinic\ClinicController', 'index']);
    $router->post('/medical_process.php', ['App\Controllers\Admin\Clinic\ClinicController', 'process']);
});
```

---

## 7. Applicant Portal Improvements

1. **Auto-Save Draft Functionality:**
   * *Problem:* Applicants filling out the extensive multi-step form lose entered information if their session times out or the browser closes.
   * *Fix:* Implement an AJAX draft endpoint (`POST /applicant/save_draft.php`) triggered on step navigation that writes partial data to `applications` with `status = 'draft'`.
2. **Persist Irregular Applicant Subject Selections:**
   * *Problem:* Custom subjects chosen on `enroll.php` are dropped on submission.
   * *Fix:* Create table `application_subject_requests` and persist selected subject IDs and section IDs during `EnrollController::processForm()`.
3. **Transparent Document Rejection Reasons:**
   * *Problem:* Applicants only see `Needs Reupload` badge without knowing why a document was rejected.
   * *Fix:* In `app/Views/applicant/status.php` and `requirements.php`, display `application_documents.feedback` alongside the rejected document badge.
4. **Document Viewer Path Correction:**
   * *Problem:* `DocumentController.php:322` resolves to `htdocs/uploads/` instead of `htdocs/sia/uploads/`.
   * *Fix:* Replace `__DIR__ . '/../../../uploads/documents/'` with `dirname(__DIR__, 2) . '/uploads/documents/'`.

---

## 8. Admissions Improvements

1. **Prerequisite Medical Clearance Gate:**
   * *Problem:* Admissions staff can approve applications even if clinic clearance is pending or rejected.
   * *Fix:* In `AdmissionsController::process()`, when `$status === 'approved'`, query `health_records.clearance_status`. If not `'cleared'`, reject the approval with an alert: `"Cannot approve application: Applicant has not received Medical Clearance from the Clinic."`
2. **Remove Competing Finalization Logic:**
   * *Problem:* Admissions has an `action=enroll` routine that duplicates finalization.
   * *Fix:* Admissions approves the application (`status = 'approved'`), which generates the assessment. Final enrollment is reserved for the Registrar after payment verification.
3. **Database Transaction Wrapping:**
   * *Problem:* Applicant detail updates, document status changes, and activity logging execute in separate uncoordinated queries.
   * *Fix:* Wrap all operations in `AdmissionsController::process()` inside `$pdo->beginTransaction()` and `$pdo->commit()`.

---

## 9. Clinic Improvements

1. **Enforce Role Authorization:**
   * *Problem:* `ClinicController::process()` contains no role checks.
   * *Fix:* Add `requirePermission('medical.review')` at the entry of `index()`, `detail()`, and `process()`.
2. **Medical Hold Status:**
   * *Problem:* Health records only support `pending`, `cleared`, or `rejected`.
   * *Fix:* Add `conditional_clearance` with specific notes (e.g., pending submission of chest X-ray within 30 days) to prevent halting enrollment for minor medical requirements.

---

## 10. Registrar Improvements

1. **Repair Enrollment Queues:**
   * *Problem:* `collegeQueue()` and `shsQueue()` filter `WHERE a.status = 'approved' AND sa.payment_status IN ('partial', 'paid')`. Because Cashier sets `a.status = 'enrolled'`, queue is empty.
   * *Fix:* Update query to:
     ```sql
     WHERE a.status = 'payment_verified' OR (a.status = 'approved' AND sa.payment_status IN ('partial', 'paid'))
     ```
2. **Dedicated Registrar Finalization Handler:**
   * *Problem:* Queue "Finalize" button redirects back to Admissions detail view.
   * *Fix:* Create a dedicated Registrar action `RegistrarController::finalizeEnrollment()` that:
     * Validates section capacity and subject assignments.
     * Invokes `EnrollmentService::finalizeEnrollment()`.
     * Redirects to the Certificate of Registration (COR) generation view.
3. **Official Certificate of Registration (COR) Generator:**
   * *Problem:* COR printing is ad-hoc inside `ApplicantController::printSlip`.
   * *Fix:* Move COR template generation to `RegistrarController::printCor()` with security verification (QR code containing student number, semester, and cryptographic hash).

---

## 11. Curriculum & Subject Improvements

1. **Curriculum Immutability Lock:**
   * *Problem:* Staff can edit subjects and unit counts in `active` curricula currently assigned to students.
   * *Fix:* In `CollegeController::processCurriculum()` and `ShsController::processCurriculum()`, check if `status === 'active'`. If active, disallow adding/deleting subjects or editing units. Require creating a new curriculum version (`v2.0`) in `draft` status.
2. **Subject Deletion Integrity Check:**
   * *Problem:* Deleting a subject from `subjects` does not verify existing enrollments.
   * *Fix:* Add foreign key validation: check `college_enrollments`, `shs_enrollments`, and `curriculum_subjects`. If referenced, disallow hard delete and set `is_active = 0`.

---

## 12. Scheduling Improvements

1. **Section Capacity Concurrency Guards:**
   * *Problem:* Over-enrollment occurs when two students select the same section simultaneously.
   * *Fix:* In `SectionService`, wrap section assignment in a transaction with `SELECT current_enrolled, max_capacity FROM college_sections WHERE id = :id FOR UPDATE`. If `current_enrolled >= max_capacity`, throw `SectionFullException`.
2. **Room and Faculty Conflict Detection:**
   * *Problem:* Schedules allow assigning the same room or teacher to overlapping time slots.
   * *Fix:* Add conflict validation query checking `(day_of_week = :day AND room_id = :room AND start_time < :end_time AND end_time > :start_time)` before schedule insertion.

---

## 13. Finance & Assessment Improvements

### 13.1 Assessment Snapshot Architecture
* **The Root Issue:** Assessments dynamically recalculate tuition fees by joining live subject units from `subjects`. If a curriculum is altered later, historical assessments change retroactively.
* **The Solution:** Create table `assessment_items` to store an immutable snapshot of all billed subjects and fees at the exact moment of assessment generation:

```sql
CREATE TABLE IF NOT EXISTS assessment_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    item_type ENUM('tuition', 'miscellaneous', 'laboratory', 'registration', 'other', 'discount') NOT NULL,
    item_code VARCHAR(50) NULL,
    item_name VARCHAR(150) NOT NULL,
    units DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    rate_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES student_assessments(id) ON DELETE CASCADE,
    INDEX idx_assessment_id (assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

When displaying the assessment, the system reads directly from `assessment_items` rather than joining live curriculum tables, guaranteeing 100% financial immutability.

---

## 14. Cashier Improvements

1. **Atomic Receipt Number Generation:**
   * *Problem:* `FinanceController.php:360` uses `SELECT MAX(receipt_number)` without row locks, risking receipt number collision.
   * *Fix:* Create a sequence table `receipt_sequences` updated atomically:
     ```sql
     UPDATE receipt_sequences SET current_value = current_value + 1 WHERE sequence_year = :year;
     SELECT current_value FROM receipt_sequences WHERE sequence_year = :year;
     ```
2. **Decouple Cashier from Enrollment Completion:**
   * *Problem:* Cashier verifies payment and prematurely marks the student as `enrolled`.
   * *Fix:* Cashier updates payment record and changes `student_assessments.payment_status = 'paid'`. Application status moves to `payment_verified`. Final enrollment validation is handed over to the Registrar.

---

## 15. Scholarship Improvements

1. **Fix Schema Mismatch:**
   * *Problem:* `ScholarshipController.php:315` crashes with `1054 Unknown column 'remarks'`.
   * *Fix:* Add column to MariaDB:
     ```sql
     ALTER TABLE scholarship_recipients ADD COLUMN remarks TEXT NULL AFTER status;
     ```
2. **Drop Dead Table:**
   * *Problem:* Table `student_scholarships` is obsolete clutter.
   * *Fix:* Execute `DROP TABLE IF EXISTS student_scholarships;`.
3. **Automated Recalculation Hook:**
   * *Problem:* Revoking or approving a scholarship does not always refresh the student's net balance.
   * *Fix:* Trigger `AssessmentService::recalculateAssessment($userId)` in all scholarship status update handlers.

---

## 16. Student Records Improvements

1. **Fix `student_academic_records_view` for SHS:**
   * *Problem:* View hardcodes a `LEFT JOIN college_sections` and sums only `college_enrollments`. SHS student section names and unit totals are corrupted or zeroed out.
   * *Fix:* Redefine view using a `UNION ALL` or `COALESCE` between College and SHS tables:
     ```sql
     CREATE OR REPLACE VIEW student_academic_records_view AS
     SELECT 
         a.id AS application_id,
         a.user_id,
         u.student_number,
         u.first_name,
         u.last_name,
         a.academic_level,
         a.grade_level,
         a.strand,
         a.semester,
         a.school_year,
         COALESCE(csec.name, ssec.name, 'Unassigned') AS section_name,
         COALESCE(c_units.total_units, s_units.total_units, 0) AS total_enrolled_units,
         sa.payment_status,
         a.status AS enrollment_status
     FROM applications a
     INNER JOIN users u ON u.id = a.user_id
     LEFT JOIN college_sections csec ON a.section_id = csec.id AND a.academic_level = 'College'
     LEFT JOIN shs_sections ssec ON a.section_id = ssec.id AND a.academic_level = 'Senior High School'
     LEFT JOIN (
         SELECT ce.application_id, SUM(s.units) AS total_units 
         FROM college_enrollments ce 
         JOIN subjects s ON ce.subject_id = s.id 
         GROUP BY ce.application_id
     ) c_units ON a.id = c_units.application_id
     LEFT JOIN (
         SELECT se.application_id, SUM(s.units) AS total_units 
         FROM shs_enrollments se 
         JOIN subjects s ON se.subject_id = s.id 
         GROUP BY se.application_id
     ) s_units ON a.id = s_units.application_id
     LEFT JOIN student_assessments sa ON a.id = sa.application_id;
     ```

---

## 17. System Administration Improvements

1. **Isolate Administrative Modules:**
   * Add middleware `RoleMiddleware:superadmin` to all routes in `/admin/system/*`.
2. **Sanitize Database Backup and Restore:**
   * In `SystemController::processBackup()`, disallow arbitrary SQL upload execution. Replace `$pdo->exec($sqlScript)` with a controlled, sandboxed import utility that only accepts backups signed with a cryptographic HMAC token.
3. **Audit Log Indexing and Search:**
   * Add composite index on `audit_logs(created_at, user_id)` and implement date-range filtering.

---

## 18. Database Improvements

| Table | Current Problem | Proposed Change | Why | Risk | Migration Difficulty |
|---|---|---|---|:---:|:---:|
| `users` | Missing `email_verified` in find query; column mismatch on reset | Add `email_verified` to queries; align code to `reset_token` | Fixes OTP bypass & 500 error | Low | Easy |
| `scholarship_recipients` | Missing `remarks` column | `ALTER TABLE scholarship_recipients ADD COLUMN remarks TEXT NULL;` | Eliminates fatal 500 error | Low | Easy |
| `student_scholarships` | Dead table | `DROP TABLE student_scholarships;` | Cleans up schema | None | Easy |
| `applications` | Non-atomic reference number loop | Add unique index on `reference_number`; use sequence table | Eliminates collisions | Low | Moderate |
| `student_assessments` | Duplicate assessment risk | `ALTER TABLE student_assessments ADD UNIQUE KEY uq_app_assessment (application_id);` | Prevents double billing | Low | Easy |
| `assessment_items` | Missing snapshot table | Create table `assessment_items` | Ensures financial immutability | Low | Moderate |
| `application_subject_requests` | Missing table for irregular students | Create table to store custom subject requests | Prevents subject data loss | Low | Moderate |
| `student_academic_records_view` | Hardcoded college join zeros SHS | Redefine view with `COALESCE` for SHS and College | Fixes student masterlist | Low | Easy |

---

## 19. Performance Improvements

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   PERFORMANCE OPTIMIZATION MAP                                   │
├──────────────────────────┬─────────────────────────────┬───────────────────────────┬─────────────┤
│ File                     │ Function                    │ Current Bottleneck        │ Benefit     │
├──────────────────────────┼─────────────────────────────┼───────────────────────────┼─────────────┤
│ AdmissionsController.php │ index() / review()          │ N+1 loop querying docs &  │ 85% query   │
│                          │                             │ exam scores per applicant │ reduction   │
│ SchedulerController.php  │ collegeSections()           │ Loop executing COUNT(*) on│ 90% faster  │
│                          │                             │ applications per section  │ render      │
│ ApplicantController.php  │ assessment() / printSlip()  │ 80-line 3-tier fallback   │ Instant     │
│                          │                             │ query on live tables      │ read        │
│ RegistrarController.php  │ students()                  │ Unpaginated table loading │ Prevents    │
│                          │                             │ all active records at once│ browser lag │
└──────────────────────────┴─────────────────────────────┴───────────────────────────┴─────────────┘
```

### Specific Optimizations
1. **Admissions Queue Consolidated Query:** Replace per-row document loops with a single query using `GROUP_CONCAT(ad.document_name, ':', ad.status) AS doc_summary`.
2. **Section Capacity Denormalization:** Maintain `current_enrolled INT UNSIGNED DEFAULT 0` on `college_sections` and `shs_sections`, incremented atomically on enrollment.
3. **Database Index Additions:**
   ```sql
   ALTER TABLE applications ADD INDEX idx_status_level (status, academic_level);
   ALTER TABLE student_assessments ADD INDEX idx_payment_status (payment_status);
   ALTER TABLE activity_logs ADD INDEX idx_user_created (user_id, created_at);
   ```

---

## 20. UI/UX Improvements

### 20.1 Applicant Experience
* **Debounce & Loading States:** Attach a lightweight JavaScript handler to all submit buttons (`enroll-form`, `payment-form`) that disables the button and displays a spinner upon submission to prevent accidental double-submissions.
* **Auto-Scroll to Form Validation Errors:** When `$_SESSION['enroll_errors']` is populated, render a script that smoothly scrolls the applicant's browser to the first invalid field and applies Bootstrap's `.is-invalid` CSS class.
* **Clear Timeline Status Descriptions:** Provide contextual descriptions for every timeline step so applicants understand that `approved` means academically cleared and awaiting cashier payment.

### 20.2 Staff Experience
* **Unified Admissions Evaluation Modal:** Combine document review, entrance exam input, and interview rating into a single tabbed interface, eliminating the need to navigate between multiple pages.
* **Server-Side Pagination on Student Masterlist:** Add standard 25/50/100 row server-side pagination with query filters on `admin/registrar/students.php`.
* **Standardized Status Badges:** Centralize status badge styling in a single helper `renderStatusBadge($status)` to eliminate inconsistent styling across views.

---

## 21. Data Integrity Improvements

### Transaction Boundaries Blueprint
Every multi-table mutation must be wrapped in a strict database transaction boundary:

```
[EnrollController::processForm]
  $pdo->beginTransaction();
  1. INSERT INTO applications
  2. INSERT INTO application_subject_requests (if irregular)
  3. INSERT INTO health_records
  4. INSERT INTO activity_logs
  $pdo->commit();  (rollBack on PDOException)

[AdmissionsController::approve]
  $pdo->beginTransaction();
  1. UPDATE applications SET status = 'approved'
  2. INSERT INTO student_assessments
  3. INSERT INTO assessment_items (snapshot line items)
  4. INSERT INTO activity_logs
  $pdo->commit();  (rollBack on PDOException)

[FinanceController::processPayment]
  $pdo->beginTransaction();
  1. Atomic increment receipt sequence
  2. INSERT INTO payments
  3. UPDATE student_assessments SET total_paid, payment_status
  4. UPDATE applications SET status = 'payment_verified'
  5. INSERT INTO activity_logs
  $pdo->commit();  (rollBack on PDOException)

[RegistrarController::finalizeEnrollment]
  $pdo->beginTransaction();
  1. Atomic generate student number
  2. Generate ttu_email
  3. UPDATE users SET student_number, ttu_email, force_password_reset
  4. UPDATE applications SET status = 'enrolled'
  5. INSERT INTO college_enrollments / shs_enrollments
  6. INSERT INTO activity_logs
  $pdo->commit();  (rollBack on PDOException)
```

---

## 22. Testing Strategy

### 22.1 Test Hierarchy & Priority

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                 TESTING SUITE HIERARCHY                                │
├─────────────────────────┬──────────────────────────────────────────────────────────────┤
│ Test Suite              │ Scope & Focus Workflows                                      │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 1. Security Tests       │ Unauthenticated migration block, RBAC middleware parameter   │
│                         │ enforcement, OTP bypass prevention, forced password reset    │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 2. Financial Math Tests │ Tuition rate calculations, lecture vs. lab units, fee        │
│                         │ templates, scholarship percentage discounts, net balances    │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 3. State Machine Tests  │ Valid/invalid application transitions: draft -> submitted -> │
│                         │ under_review -> approved -> payment_verified -> enrolled     │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 4. Concurrency Tests    │ Simultaneous student number generation, simultaneous receipt │
│                         │ generation, section capacity overflow protection             │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 5. Integration Tests    │ End-to-end applicant enrollment flow from registration to    │
│                         │ COR issuance via PHPUnit HTTP test cases                     │
└─────────────────────────┴──────────────────────────────────────────────────────────────┘
```

### 22.2 PHPUnit Test Setup
Create a test harness in `tests/`:
* `tests/TestCase.php`: Bootstraps in-memory SQLite or test MariaDB connection, loads fixtures, provides session mocking.
* `tests/Unit/AssessmentTest.php`: Tests all unit calculation rules.
* `tests/Unit/StateTransitionTest.php`: Tests invalid state rejections.
* `tests/Integration/EnrollmentWorkflowTest.php`: Simulates end-to-end multi-role lifecycle.

---

## 23. Codebase Cleanup

| Item / Path | Classification | Rationale | Action Required |
|---|:---:|---|---|
| `database/migrations/setup_database.php` | **REFACTOR** | Dangerous remote drop capability | Add CLI-only guard; remove `.htaccess` exemption |
| `student_scholarships` table | **REMOVE** | Unused legacy table duplicating `scholarship_recipients` | Execute `DROP TABLE student_scholarships;` |
| `app/Routes/web.php:41-44` | **REMOVE** | Routes map to non-existent `ApplicantController` methods | Delete unused route declarations |
| `functions.php::generateStudentNumber` | **REFACTOR** | Duplicate of controller logic; non-atomic | Extract to `StudentNumberService` with row lock |
| `functions.php::recalculateStudentAssessment`| **REFACTOR** | Global procedural logic with raw queries | Move into `AssessmentService` |
| Monolithic `:admin` route group | **REFACTOR** | Overly permissive role checking | Split into granular sub-groups per module role |
| Multi-tier subject fallback SQL | **REFACTOR** | Copied in 5 different controllers | Centralize in `AssessmentService` |

---

## 24. Exact File-by-File Improvement Map

### Authentication & Core Security
* **[`.htaccess`](file:///c:/xampp/htdocs/sia/.htaccess):**
  * Remove line 14–16 whitelist for `setup_database.php`.
* **[`database/migrations/setup_database.php`](file:///c:/xampp/htdocs/sia/database/migrations/setup_database.php):**
  * Add `if (php_sapi_name() !== 'cli') exit('CLI only');`.
* **[`app/Models/User.php`](file:///c:/xampp/htdocs/sia/app/Models/User.php):**
  * Line 16: Add `email_verified` to `SELECT` query in `findByEmail()`.
* **[`app/Controllers/AuthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/AuthController.php):**
  * Lines 640, 777, 790, 851: Replace `reset_password_code` and `reset_password_expires_at` with `reset_token` and `reset_token_expires_at`.
  * Line 125: Add check for `force_password_reset` and redirect to change password view.
  * Add rate-limiting check against `login_attempts` table.
* **[`app/Routes/web.php`](file:///c:/xampp/htdocs/sia/app/Routes/web.php):**
  * Lines 41–44: Remove dead applicant routes.
  * Lines 69–152: Split monolithic `:admin` group into dedicated role groups (`:superadmin`, `:cashier,superadmin`, `:clinic,superadmin`, `:admissions,superadmin`).

### Applicant & Enrollment Workflow
* **[`app/Controllers/EnrollController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/EnrollController.php):**
  * Line 286: Persist `$selectedSubjects` into `application_subject_requests`.
  * Wrap application creation in `$pdo->beginTransaction()`.
* **[`app/Controllers/DocumentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/DocumentController.php):**
  * Line 322: Fix relative path to `dirname(__DIR__, 2) . '/uploads/documents/'`.
* **[`app/Controllers/Admin/Clinic/ClinicController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Clinic/ClinicController.php):**
  * Add `requirePermission('medical.review')` to all handler methods.
* **[`app/Controllers/Admin/Admissions/AdmissionsController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Admissions/AdmissionsController.php):**
  * Line 642: Remove `action=enroll` duplicate finalization.
  * Validate clinic clearance before allowing transition to `approved`.
  * Wrap applicant approvals and assessment creation in `$pdo->beginTransaction()`.

### Registrar, Finance & Scholarships
* **[`app/Controllers/Admin/Registrar/RegistrarController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php):**
  * Lines 89, 181: Update queue queries to match `a.status = 'payment_verified'`.
  * Add `finalizeEnrollment()` method that generates credentials and completes registration.
* **[`app/Controllers/Admin/Finance/FinanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php):**
  * Line 342: Remove `finalizeStudentEnrollment()`. Transition application to `payment_verified` instead of `enrolled`.
  * Line 360: Make receipt number generation atomic via sequence table.
* **[`app/Controllers/Admin/Scholarship/ScholarshipController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scholarship/ScholarshipController.php):**
  * Line 315: Add `remarks` column to `scholarship_recipients` table in schema and update handler.

---

## 25. Priority Matrix

| ID | Improvement Task | Module | Severity | Impact | Difficulty | Priority |
|---|---|---|:---:|:---:|:---:|:---:|
| **IMP-01** | Remove `.htaccess` setup exemption & add CLI check | Security | 🔴 Critical | High | Easy | **P0** |
| **IMP-02** | Align `AuthController` password reset SQL columns | Auth | 🔴 Critical | High | Easy | **P0** |
| **IMP-03** | Fix `User::findByEmail` to include `email_verified` | Auth | 🔴 Critical | High | Easy | **P0** |
| **IMP-04** | Add `remarks` column to `scholarship_recipients` | Database | 🔴 Critical | High | Easy | **P0** |
| **IMP-05** | Add Superadmin guard in `SystemController::processUser` | Security | 🔴 Critical | High | Easy | **P0** |
| **IMP-06** | Reconcile Cashier vs. Registrar finalization workflow | Workflow | 🔴 Critical | High | Moderate | **P0** |
| **IMP-07** | Fix Document view 404 path resolution | Documents | 🟠 High | Medium | Easy | **P1** |
| **IMP-08** | Persist irregular student subject choices | Enrollment | 🟠 High | High | Moderate | **P1** |
| **IMP-09** | Enforce forced password reset on login | Auth | 🟠 High | Medium | Easy | **P1** |
| **IMP-10** | Add granular route group RBAC in `web.php` | Security | 🟠 High | High | Moderate | **P1** |
| **IMP-11** | Fix `student_academic_records_view` for SHS | Database | 🟠 High | Medium | Easy | **P1** |
| **IMP-12** | Create `assessment_items` snapshot table | Finance | 🟡 Medium | High | Moderate | **P2** |
| **IMP-13** | Extract `AssessmentService` and `EnrollmentService` | Architecture| 🟡 Medium | High | Hard | **P2** |
| **IMP-14** | Add database transactions across multi-table writes | Integrity | 🟡 Medium | High | Moderate | **P2** |
| **IMP-15** | Add atomic sequence table for receipt numbers | Finance | 🟡 Medium | Medium | Moderate | **P2** |
| **IMP-16** | Add button debounce and scroll-to-error in UI | UI/UX | 🔵 Low | Low | Easy | **P3** |
| **IMP-17** | Drop unused `student_scholarships` table | Cleanup | 🔵 Low | Low | Easy | **P3** |

---

## 26. Quick Wins (Can Be Implemented in < 4 Hours)

1. **Lock Down Database Setup:** Delete lines 14–16 in `.htaccess` and add CLI check to `setup_database.php`.
2. **Fix Password Reset Columns:** Update 4 lines in `AuthController.php` (`reset_password_code` $\rightarrow$ `reset_token`).
3. **Fix OTP Bypass:** Add `email_verified` to `User::findByEmail()`.
4. **Fix Scholarship Recipient SQL Error:** Add `remarks TEXT NULL` to `scholarship_recipients` in MariaDB.
5. **Fix Document Viewer Path:** Update directory resolution in `DocumentController.php:322`.
6. **Guard System User Creation:** Add `superadmin` role validation check to `SystemController::processUser()`.

---

## 27. Medium-Term Improvements (1–2 Sprints)

1. **Authoritative Enrollment State Machine:** Refactor `FinanceController`, `AdmissionsController`, and `RegistrarController` to follow the redesigned state machine.
2. **Irregular Student Subject Persistence:** Create `application_subject_requests` table and integrate into enrollment submission and admissions review.
3. **Assessment Snapshot Architecture:** Deploy `assessment_items` table and update assessment generation to store immutable line items.
4. **Granular RBAC Pipeline:** Configure route sub-groups in `web.php` with specific role parameters and enforce `requirePermission()` across all admin controllers.
5. **Database Transaction Boundaries:** Wrap all multi-table mutations in PDO transaction blocks.

---

## 28. Long-Term Architecture

### Strangler Fig Migration to Domain Services
To modernize without risking a costly or disruptive system rewrite, adopt an incremental **Strangler Fig approach**:

```
[Phase A: Baseline] ──► [Phase B: Service Layer] ──► [Phase C: Repository Layer]
Fat Controllers         Fat Controllers              Slim Controllers
Direct PDO in actions   Call Services                Call Services
                        Services use direct PDO      Services call Repositories
                        (Business logic isolated)    (Data access abstracted)
```

1. **Step 1:** Leave routing, middleware, and views completely untouched.
2. **Step 2:** Extract core calculation and state logic from controllers into `app/Services/` (`AssessmentService`, `EnrollmentService`, `StudentNumberService`).
3. **Step 3:** Replace controller SQL blocks with single-line service invocations:
   ```php
   // Old Fat Controller: 150 lines of SQL and logic
   // New Slim Controller:
   $this->assessmentService->generateAssessment($applicationId);
   ```
4. **Step 4:** As services mature, introduce typed Repositories (`ApplicationRepository`, `AssessmentRepository`) to encapsulate database queries.

---

## 29. Recommended Implementation Order

### Sprint 1: Security & Quick Wins (Immediate)
* Fix `.htaccess` and CLI guard for `setup_database.php`.
* Align column names in `AuthController.php` and `ScholarshipController.php`.
* Include `email_verified` in `User::findByEmail()`.
* Add `superadmin` check in `SystemController::processUser()`.
* Correct document path in `DocumentController.php`.

### Sprint 2: Workflow Reconciliation & State Machine
* Implement decoupled Cashier payment verification (`payment_verified` status).
* Implement authoritative Registrar enrollment queue and finalization routine.
* Connect admissions clinic clearance check prior to application approval.
* Enforce `force_password_reset` redirect upon student first login.

### Sprint 3: Data Integrity & Irregular Students
* Create `application_subject_requests` table and update `EnrollController`.
* Wrap multi-table inserts in database transactions (`beginTransaction`).
* Deploy updated `student_academic_records_view` supporting SHS and College.
* Drop dead `student_scholarships` table.

### Sprint 4: Financial Immutability & Concurrency
* Create `assessment_items` table and migrate assessment generation.
* Create atomic `receipt_sequences` table for official receipts.
* Add composite database indexes on `applications`, `student_assessments`, and `activity_logs`.

### Sprint 5: Service Layer Extraction
* Extract `AssessmentService` and eliminate duplicated 3-tier fallback SQL.
* Extract `EnrollmentService` and `StudentNumberService`.
* Add PHPUnit automated test harness for financial math and state transitions.

### Sprint 6: UI/UX & Polish
* Add button debouncing / loading spinners to prevent double submission.
* Add auto-scroll to form validation errors.
* Display document rejection reasons on applicant dashboard.

---

## 30. Final Recommendation

The Triple T University (TTU) Enrollment System possesses a **strong foundation, an exceptionally polished user interface, and comprehensive functional scope**. It does **NOT** require a ground-up rewrite into a modern framework like Laravel.

By following this improvement plan and executing **Sprint 1 (Quick Wins)** and **Sprint 2 (Workflow Reconciliation)**, the engineering team can eliminate all critical security exposures, resolve runtime fatal crashes, and establish a reliable, audit-compliant enrollment engine within days.
