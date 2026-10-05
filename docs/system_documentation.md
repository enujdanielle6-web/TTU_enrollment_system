# TRIPLE T UNIVERSITY (TTU) ENROLLMENT & LMS SYSTEM
# Complete Codebase Architecture, Subsystem Mapping & Technical Reference Manual

**Author:** Antigravity Principal Architecture & Engineering Agent  
**Target Repository:** `c:\xampp\htdocs\sia`  
**Single Source of Truth:** Live PHP 8.x Codebase and MariaDB Schema  
**Target File:** `system_documentation.md`  
**Purpose:** Comprehensive, exhaustive architectural map and operational reference for human engineers and AI agents to understand, maintain, debug, and extend the TTU platform without re-scanning the entire codebase.

---

## TABLE OF CONTENTS
1. [System Mission & Executive Overview](#1-system-mission--executive-overview)
2. [Architectural Framework & Normal Request Lifecycle](#2-architectural-framework--normal-request-lifecycle)
3. [Directory Structure & Namespace Organization](#3-directory-structure--namespace-organization)
4. [Authentication, Sessions & RBAC Architecture](#4-authentication-sessions--rbac-architecture)
5. [Database Architecture & Data Relationships](#5-database-architecture--data-relationships)
6. [Enrollment Subsystems Deep-Dive](#6-enrollment-subsystems-deep-dive)
   - 6.1 Applicant Portal & Self-Registration
   - 6.2 Admissions Processing & Document Verification
   - 6.3 Health Services / Clinic Clearance
   - 6.4 Curriculum Engine (College & Senior High School)
   - 6.5 Registrar & Academic Scheduling
   - 6.6 Cashier & Tuition Assessment Engine
   - 6.7 Scholarship Grants & Beneficiary Management
   - 6.8 System Administration, Settings & Audit Logging
7. [LMS Subsystems Deep-Dive](#7-lms-subsystems-deep-dive)
   - 7.1 LMS Identity & Portal Access
   - 7.2 Course Structure & Section Binding
   - 7.3 Modules & Course Content Distribution
   - 7.4 Assignment Lifecycle & Submission Pipeline
   - 7.5 Quiz Engine, Question Bank & Grading
   - 7.6 Gradebook & Performance Computation
   - 7.7 Attendance Tracking & Sessions
   - 7.8 Announcements & Academic Calendar
8. [Enrollment ↔ LMS Integration Layer](#8-enrollment--lms-integration-layer)
   - 8.1 Shared vs. Fragmented Data Models
   - 8.2 The Faculty Scheduling & Course Generation Workflow
   - 8.3 Student Enrollment to Course Provisioning Pipeline
   - 8.4 Integration Failure Points & Technical Disconnects
9. [End-to-End Workflow Tracing (17 Institutional Processes)](#9-end-to-end-workflow-tracing-17-institutional-processes)
10. [Module & File Relationship Graphs](#10-module--file-relationship-graphs)
11. [Master Route Catalog & Middleware Mapping](#11-master-route-catalog--middleware-mapping)
12. [Configuration, Dependencies & Environment](#12-configuration-dependencies--environment)
13. [Architectural Risks, Known Gaps & Uncertainties](#13-architectural-risks-known-gaps--uncertainties)
14. [Verification Report & Ground Truth Summary](#14-verification-report--ground-truth-summary)

---

## 1. SYSTEM MISSION & EXECUTIVE OVERVIEW

Triple T University (TTU) operates a web-based educational management suite comprised of two primary systems:
1. **The Enrollment System (SIS):** Manages the student lifecycle from public marketing and self-service online application, document upload, medical evaluation, admissions committee review, section allocation, curriculum assignment, fee assessment, cashiering, and scholarship grants.
2. **The Learning Management System (LMS):** Powers day-to-day academic delivery, providing portals for students and faculty to share digital course modules, distribute lecture materials, conduct online timed quizzes, submit digital assignments, record daily attendance, log gradebook records, and view institutional schedules.

### Dual-Track Academic Structure
Both the Enrollment System and the LMS are architected to support two distinct educational tiers simultaneously:
- **Higher Education (College):** Unit-based tuition pricing, semester-based calendars (1st, 2nd, Summer), program degrees (BSIT, BSCS, BSIS, BSHM), and year-level progression (1st to 4th Year).
- **Senior High School (SHS):** Fixed-voucher tuition templates, semester-based calendars, specialized tracks/strands (STEM, ABM, HUMSS, TVL-ICT), and grade levels (Grade 11 and Grade 12).

---

## 2. ARCHITECTURAL FRAMEWORK & NORMAL REQUEST LIFECYCLE

### 2.1 The Hybrid MVC Architecture
The system is built on Vanilla PHP 8.x using a **Hybrid MVC** pattern governed by the following structural rules:
- **Front Controller Pattern:** All web requests are funneled through `.htaccess` into `public/index.php`.
- **Fat Controllers:** In accordance with project design standards, controllers handle request extraction, input validation, business logic, authorization enforcement, and raw PDO SQL query execution.
- **Thin Models:** Models (`app/Models/*`) act primarily as basic active record wrappers, data containers, or query helpers. They do not hide SQL logic from controllers.
- **Service Layer:** High-complexity cross-cutting business rules (e.g., student number generation, institutional email creation, tuition assessment calculation, LMS quiz scoring, grade computation) are encapsulated in dedicated Domain Services (`app/Services/*`).
- **Repository Layer:** Abstracted enrollment queries are encapsulated in `app/Repositories/*` implementing `EnrollmentRepositoryInterface` to decouple College and SHS database queries.
- **View Layer:** HTML templates with embedded PHP rendering (`app/Views/*`). Controller data is injected via output buffering in `BaseController::render()`.

### 2.2 Normal Request Flow Diagram

```text
HTTP Request (Browser / AJAX)
   │
   ▼
[Apache mod_rewrite / .htaccess]
   │ (Rewrites non-static files to public/index.php)
   ▼
[public/index.php] (Front Controller)
   │ 1. Bootstraps autoloader (Composer & custom PSR-4)
   │ 2. Initializes session configuration & lifetime
   │ 3. Generates CSRF token in $_SESSION['csrf_token']
   │ 4. Instantiates App\Core\Request & App\Core\Response
   │ 5. Instantiates App\Core\Router & loads app/Routes/web.php
   │ 6. Dispatches Request through Onion Middleware Pipeline
   ▼
[Middleware Pipeline]
   │ ├── SessionSecurityMiddleware (IP, User-Agent, and idle timeout check)
   │ ├── CsrfMiddleware (Validates csrf_token on POST/PUT/DELETE)
   │ ├── AuthMiddleware (Enforces $_SESSION['logged_in'] == true)
   │ └── RoleMiddleware (Enforces $_SESSION['user_role'] against route permissions)
   ▼
[Controller Action] (e.g., App\Controllers\Admin\Finance\FinanceController::process)
   │ 1. Validates input parameters via Request::getBody() or Request::input()
   │ 2. Enforces granular permission check via requirePermission('permission.name')
   │ 3. Obtains PDO instance via App\Core\Database::getConnection()
   │ 4. Executes business logic or delegates to Domain Services / Repositories
   │ 5. Writes changes to MariaDB (within PDO transactions where applicable)
   │ 6. Emits Activity Log via logActivity()
   ▼
[View Rendering / JSON Response]
   │ ├── Web: BaseController::render('view_name', $data) -> Output Buffer -> Browser
   │ └── API: Response::json($data, $statusCode) -> JSON payload -> Client Fetch
```

### 2.3 Core Infrastructure Components

- **`app/Core/Router.php`:**
  - Implements route registration (`get()`, `post()`, `put()`, `delete()`, `group()`).
  - Supports route parameter tokens (e.g., `/course/{course_id}/assignments/{id}`) converted into regex patterns (`(?P<course_id>[^/]+)`).
  - Maintains a nested middleware stack per route group.
  - Matches requested URIs and dispatches closures or `[ControllerClass, 'methodName']`.
- **`app/Core/Request.php`:**
  - Encapsulates `$_SERVER`, `$_GET`, `$_POST`, `$_FILES`, and raw `php://input`.
  - Normalizes HTTP methods (including `_method` method spoofing).
  - Sanitizes input and detects AJAX requests via `X-Requested-With: XMLHttpRequest` or `Accept: application/json`.
- **`app/Core/Response.php`:**
  - Manages HTTP status codes (`setStatusCode(int)`).
  - Manages headers (`setHeader(name, value)`).
  - Emits JSON payloads (`json(data, status)`).
  - Handles client redirects (`redirect(url)`).
- **`app/Core/Database.php`:**
  - Implements the Singleton pattern to provide a single, shared PDO connection.
  - Configures PDO attributes: `ERRMODE_EXCEPTION`, `DEFAULT_FETCH_MODE => FETCH_ASSOC`, and `EMULATE_PREPARES => false`.
  - Reads connection parameters from `config/database.php`.
- **`app/Core/BaseController.php`:**
  - Base class for all web controllers.
  - Provides `render(viewPath, data)` which extracts data into scope and requires the template from `app/Views/`.
  - Provides helper methods: `redirect(url)`, `json(data, status)`, `forbidden(response)`, and `notFound(response)`.
- **`app/Core/functions.php`:**
  - Global procedural utilities loaded on bootstrap:
    - `hasPermission(string|array $perms)`: Reads `$_SESSION['user_permissions']` and `$_SESSION['user_role']`.
    - `requirePermission(string|array $perms)`: Aborts with HTTP 403 / redirect if authorization fails.
    - `logActivity(...)`: Inserts structured records into the `activity_logs` table.
    - `csrf_token()`: Retrieves the session's active CSRF token.
    - `esc($string)`: Wraps `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` for XSS mitigation.

---

## 3. DIRECTORY STRUCTURE & NAMESPACE ORGANIZATION

```text
c:\xampp\htdocs\sia\
├── .agents/                        # AI pair programming guidelines & skill sets
├── .htaccess                       # Apache URL rewrite rules & directory access controls
├── config/
│   ├── app.php                     # Application settings, timezone, environment flags
│   └── database.php                # MariaDB PDO host, port, dbname, user, password
├── database/
│   ├── schema.sql                  # Canonical 42-table MariaDB schema (Single Source of Truth)
│   ├── seed.sql                    # Production and demo seed datasets
│   ├── lms_phase2.sql              # Legacy/intermediate migration script
│   └── migrations/                 # Phase-specific schema alterations (Phases 5, 7, 8)
├── docs/
│   ├── INDEX.md                    # Documentation index
│   ├── obsidian/                   # Legacy Obsidian knowledge vault (18 sub-directories)
│   └── codebase/                   # Dedicated codebase technical knowledge base
├── public/
│   ├── index.php                   # Front Controller & application bootstrap
│   ├── css/                        # Compiled and vanilla stylesheet assets
│   └── js/
│       ├── spa-router.js           # Client-side AJAX SPA navigation engine
│       └── app.js                  # Global front-end event bindings
├── storage/                        # Server file uploads (outside web root)
│   └── lms_materials/              # (Subject of path-mismatch finding)
├── app/
│   ├── Core/                       # MVC Engine (Router, Request, Response, Database, BaseController)
│   ├── Middleware/                 # PSR-style request interceptors (Auth, Role, CSRF, Session)
│   ├── Models/                     # Data containers & Active Record entities
│   ├── Repositories/               # Data access layer for student enrollments
│   ├── Services/                   # Domain business logic services
│   ├── Routes/
│   │   └── web.php                 # Master application routing definition (268 lines)
│   ├── uploads/                    # Local storage for documents, receipts, and LMS files
│   │   ├── documents/              # Applicant requirement uploads
│   │   ├── payments/               # Bank transfer / GCash proof of payment receipts
│   │   └── lms/                    # LMS uploaded files
│   │       └── submissions/        # Student assignment submissions
│   ├── Controllers/
│   │   ├── AuthController.php      # Main portal authentication, OTP, password recovery
│   │   ├── ApplicantController.php # Applicant self-service portal
│   │   ├── DocumentController.php  # Requirement uploads & verification
│   │   ├── EnrollController.php    # Public application intake forms
│   │   ├── HealthController.php    # Student medical history submission
│   │   ├── HomeController.php      # Public landing pages
│   │   ├── Admin/                  # Administrative & Registrar Sub-portals
│   │   │   ├── Admissions/         # AdmissionsController.php
│   │   │   ├── Clinic/             # ClinicController.php
│   │   │   ├── Finance/            # FinanceController.php, FeeController.php
│   │   │   ├── Registrar/          # RegistrarController.php, SubjectController.php, CollegeController.php, ShsController.php
│   │   │   ├── Scheduler/          # SchedulerController.php
│   │   │   ├── Scholarship/        # ScholarshipController.php
│   │   │   ├── System/             # SystemController.php, ReportController.php, DashboardController.php
│   │   │   └── LmsAdminController.php # LMS manual course mapping generator
│   │   └── Lms/                    # LMS Portals & Features
│   │       ├── LmsAuthController.php           # Student & Faculty dedicated LMS login
│   │       ├── StudentController.php           # Student dashboard & course overview
│   │       ├── FacultyController.php           # Faculty dashboard & module/material manager
│   │       ├── DownloadController.php          # Secure streaming for materials & submissions
│   │       ├── StudentAssignmentController.php # Assignment viewing & student submissions
│   │       ├── FacultyAssignmentController.php # Assignment creation & grading
│   │       ├── StudentQuizController.php       # Timed quiz taking & answer submission
│   │       ├── FacultyQuizController.php       # Quiz creation, question bank & result review
│   │       ├── StudentGradebookController.php  # Student grade views
│   │       ├── FacultyGradebookController.php  # Faculty grade computation & recording
│   │       ├── StudentAttendanceController.php # Student attendance record viewing
│   │       ├── FacultyAttendanceController.php # Attendance session creation & roll call
│   │       ├── StudentCalendarController.php   # Student calendar schedule view
│   │       ├── FacultyCalendarController.php   # Faculty timetable view
│   │       ├── StudentAnnouncementController.php# Student course announcements view
│   │       └── FacultyAnnouncementController.php# Faculty course announcement creator
│   └── Views/                      # PHP presentation templates (HTML/Bootstrap 5)
│       ├── admin/                  # Administrative module views
│       ├── applicant/              # Applicant portal views
│       ├── auth/                   # Login, register, OTP verification views
│       ├── components/             # Reusable UI partials (header, navbar, footer)
│       ├── errors/                 # HTTP 403, 404, 500 error pages
│       ├── lms/                    # LMS student and faculty views
│       └── public/                 # Public marketing & enrollment intake views
```

---

## 4. AUTHENTICATION, SESSIONS & RBAC ARCHITECTURE

### 4.1 Identity Storage: The `users` Table
The entire institutional community (administrators, staff, faculty, students, applicants) is stored within the single canonical table: `users`.
- **Primary Keys & Identifiers:** `id` (INT UNSIGNED AUTO_INCREMENT), `email` (Unique VARCHAR), `student_number` (Unique VARCHAR nullable; stores Student ID or Faculty Employee ID), `ttu_email` (Institutional email `@ttu.edu.ph`).
- **Authentication Columns:** `password` (Bcrypt hash via `password_hash`), `email_verified` (TINYINT 1/0), `verification_code` (6-digit OTP), `verification_code_expires_at` (DATETIME), `force_password_reset` (TINYINT 1/0).
- **Authorization Columns:** `role` (ENUM: `'superadmin'`, `'admin'`, `'admissions'`, `'scholarship'`, `'cashier'`, `'clinic'`, `'faculty'`, `'scheduler'`, `'applicant'`, `'student'`), `permissions` (JSON text array of granular capabilities, e.g., `["fees.manage","applications.review"]`).

### 4.2 Session State & Token Contracts
Upon successful authentication, controllers regenerate the session ID (`session_regenerate_id(true)`) and write the following state variables:

```php
$_SESSION['logged_in']        = true;
$_SESSION['user_id']          = (int) $user['id'];
$_SESSION['user_first_name']  = $user['first_name'];
$_SESSION['user_last_name']   = $user['last_name'];
$_SESSION['user_name']        = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_email']       = $user['email'];
$_SESSION['user_role']        = $user['role'];        // Canonical session role key
$_SESSION['user_department']  = $user['department'] ?? 'None';
$_SESSION['user_permissions'] = json_decode($user['permissions'], true) ?? [];
$_SESSION['user_ip']          = $_SERVER['REMOTE_ADDR'];
$_SESSION['user_agent']       = $_SERVER['HTTP_USER_AGENT'];
$_SESSION['created_time']     = time();
```

*Legacy / LMS Compatibility Keys:*  
`LmsAuthController` sets supplementary keys: `$_SESSION['lms_logged_in'] = true`, `$_SESSION['lms_user_id'] = $user['id']`, `$_SESSION['lms_role'] = 'student'|'faculty'`.

### 4.3 The Role Hierarchy & Permissions Engine

```text
                           ┌──────────────┐
                           │  superadmin  │ (Bypasses all checks via wildcard ["*"])
                           └──────┬───────┘
                                  │
    ┌──────────────┬──────────────┼──────────────┬──────────────┬──────────────┐
    │              │              │              │              │              │
┌───▼───┐      ┌───▼───┐      ┌───▼───┐      ┌───▼───┐      ┌───▼───┐      ┌───▼───┐
│ admin │      │admiss-│      │cashier│      │clinic │      │schedu-│      │schol- │
│(Regis-│      │ ions  │      │       │      │       │      │ ler   │      │arship │
│ trar) │      │       │      │       │      │       │      │       │      │       │
└───┬───┘      └───┬───┘      └───┬───┘      └───┬───┘      └───┬───┘      └───┬───┘
    │              │              │              │              │              │
    └──────────────┴──────────────┼──────────────┴──────────────┴──────────────┘
                                  │
                       ┌──────────┴──────────┐
                       │   RoleMiddleware    │  Aliases all 7 staff roles as 'admin'
                       └──────────┬──────────┘
                                  │
                 ┌────────────────┴────────────────┐
                 │                                 │
           ┌─────▼─────┐                     ┌─────▼─────┐
           │  faculty  │                     │  student  │
           └───────────┘                     └─────▲─────┘
                                                   │
                                             ┌─────┴─────┐
                                             │ applicant │
                                             └───────────┘
```

#### Granular Permissions Mapping
The system supports 25 distinct granular permissions defined in `users.permissions`:
- **Registrar / Academic Structure:** `students.view`, `students.edit`, `programs.manage`, `subjects.manage`, `curriculum.manage`, `college_curriculum.manage`, `shs_curriculum.manage`, `sections.manage`, `college_sections.manage`, `shs_sections.manage`, `schedules.manage`, `enrollment.finalize`.
- **Admissions & Medical:** `applications.view_queue`, `applications.view_details`, `applications.review`, `documents.verify`, `medical.review`.
- **Finance & Cashier:** `fees.manage`, `assessments.generate`, `payments.record`, `receipts.print`.
- **Scholarship:** `scholarships.manage`, `scholarship_applications.review`.
- **System Administration:** `users.manage`, `settings.manage`, `reports.view`.

---

## 5. DATABASE ARCHITECTURE & DATA RELATIONSHIPS

The database schema (`sia`) consists of 42 tables and views defined in `database/schema.sql`.

### 5.1 Core Entity Domains

```text
[USERS & IDENTITY]
  users ─────────────┬─────────► activity_logs
                     ├─────────► login_attempts
                     └─────────► health_records

[ACADEMIC STRUCTURE]
  college_programs ──► college_curricula ──► college_curriculum_subjects ──► subjects
  shs_strands ───────► shs_curricula ─────► shs_curriculum_subjects ───────► subjects

[TIMETABLE & SECTIONS]
  college_sections ──► college_section_subjects (references subjects)
  shs_sections ──────► shs_section_subjects (references subjects)

[ENROLLMENT & ADMISSIONS]
  users ─────────────► applications ──┬──► application_documents
                                      ├──► application_subject_requests
                                      ├──► college_enrollments (references subjects & sections)
                                      ├──► shs_enrollments (references subjects & sections)
                                      └──► student_assessments ──► payment_records

[SCHOLARSHIPS]
  scholarships ──────► scholarship_applications
  scholarships ──────► scholarship_recipients

[LEARNING MANAGEMENT SYSTEM (LMS)]
  lms_courses ───────┬──► lms_modules ──► lms_materials
  (references        ├──► lms_assignments ──► lms_submissions
   subjects & users) ├──► lms_quizzes ──► lms_questions ──► lms_question_choices
                     │                 └──► lms_quiz_attempts ──► lms_quiz_answers
                     ├──► lms_grade_items ──► lms_grades
                     ├──► lms_attendance_sessions ──► lms_attendance_records
                     └──► lms_announcements
```

### 5.2 Key Tables and Column Specifications

#### 1. `applications` (Central Enrollment State Machine)
- `id` (INT UNSIGNED PK)
- `user_id` (INT UNSIGNED FK -> `users.id` ON DELETE CASCADE)
- `reference_number` (VARCHAR(50) UNIQUE, format: `APP-YYYY-XXXXXX`)
- `academic_level` (ENUM: `'Senior High School'`, `'College'`)
- `status` (ENUM: `'pending'`, `'under_review'`, `'correction_required'`, `'approved'`, `'payment_verified'`, `'rejected'`, `'enrolled'`)
- `section_id` (INT UNSIGNED nullable -> `college_sections.id` or `shs_sections.id`)
- `college_curriculum_id` (INT UNSIGNED nullable FK -> `college_curricula.id` ON DELETE SET NULL)

#### 2. `student_assessments` (Tuition Assessment Ledger)
- `id` (INT UNSIGNED PK)
- `application_id` (INT UNSIGNED FK -> `applications.id` ON DELETE CASCADE)
- `total_units` (INT DEFAULT 0)
- `tuition_fee`, `miscellaneous_fee`, `registration_fee`, `laboratory_fee`, `other_fees` (DECIMAL(10,2))
- `total_amount` (DECIMAL(10,2))
- `discount_amount` (DECIMAL(10,2) DEFAULT 0.00)
- `net_amount` (DECIMAL(10,2))
- `total_paid` (DECIMAL(10,2) DEFAULT 0.00)
- `payment_status` (ENUM: `'unpaid'`, `'partial'`, `'paid'`)

#### 3. `payment_records` (Official Receipts Ledger)
- `id` (INT UNSIGNED PK)
- `assessment_id` (INT UNSIGNED FK -> `student_assessments.id` ON DELETE CASCADE)
- `user_id` (INT UNSIGNED FK -> `users.id`)
- `cashier_id` (INT UNSIGNED nullable FK -> `users.id`)
- `amount` (DECIMAL(10,2))
- `payment_method` (ENUM: `'Cash'`, `'GCash'`, `'Bank Transfer'`)
- `reference_number` (VARCHAR(100))
- `status` (ENUM: `'pending'`, `'verified'`, `'rejected'`)

#### 4. `lms_courses` (The Core LMS Course Entity)
- `id` (INT UNSIGNED PK)
- `academic_level` (ENUM: `'College'`, `'SHS'`)
- `academic_section_id` (INT UNSIGNED NOT NULL, logical foreign key)
- `subject_id` (INT UNSIGNED FK -> `subjects.id` ON DELETE CASCADE)
- `faculty_user_id` (INT UNSIGNED FK -> `users.id` ON DELETE CASCADE)
- `status` (ENUM: `'active'`, `'archived'` DEFAULT `'active'`)

---

## 6. ENROLLMENT SUBSYSTEMS DEEP-DIVE

### 6.1 Applicant Portal & Self-Registration
- **Primary Files:** `app/Controllers/AuthController.php`, `app/Controllers/ApplicantController.php`, `app/Controllers/EnrollController.php`.
- **Mechanics:**
  1. Prospective student fills public intake form at `/sia/auth/register.php` or `/sia/enroll/college.php` / `/sia/enroll/shs.php`.
  2. Account created with `role = 'applicant'`, `email_verified = 0`, and a 6-digit numeric OTP stored in `verification_code`.
  3. User redirected to OTP verification page. Upon successful OTP submission, `email_verified` is toggled to `1`.
  4. Applicant completes the multi-tab enrollment questionnaire (`applications` record created with status `'pending'`).
  5. The applicant dashboard (`/sia/applicant/dashboard.php`) displays real-time progress through a 5-step milestone stepper: Registration -> Document Submission -> Medical Review -> Admissions Review -> Official Enrollment.

### 6.2 Admissions Processing & Document Verification
- **Primary Files:** `app/Controllers/Admin/Admissions/AdmissionsController.php`, `app/Controllers/DocumentController.php`.
- **Mechanics:**
  - Applicants upload required documents (`birth_certificate`, `form_137`, `good_moral`, `transfer_credential`) via `DocumentController::upload()`. Files are strictly validated via MIME type and stored in `app/uploads/documents/`.
  - Admissions officers review queue at `/sia/admin/admissions/review.php`.
  - Admissions officer inspects files, selects approve/reject, and submits feedback.
  - Transitioning an application to `'approved'` marks the applicant as ready for section allocation and tuition assessment.

### 6.3 Health Services / Clinic Clearance
- **Primary Files:** `app/Controllers/Admin/Clinic/ClinicController.php`, `app/Controllers/HealthController.php`.
- **Mechanics:**
  - Applicants submit personal medical history (allergies, asthma, surgeries, maintenance medications, emergency contacts) stored in `health_records`.
  - University Medical Staff access `/sia/admin/clinic/medical_clearance.php` (guarded by `requirePermission('medical.review')`).
  - Clinic officer reviews conditions and updates health status: `'verified'`, `'correction_required'`, or `'rejected'`.
  - Medical clearance is required before official enrollment can be finalized.

### 6.4 Curriculum Engine (College & Senior High School)
- **Primary Files:** `app/Controllers/Admin/Registrar/SubjectController.php`, `CollegeController.php`, `ShsController.php`.
- **Mechanics:**
  - **Subject Master Catalog:** Maintained in `subjects` table. `SubjectController::getSubjectUsageDetails()` implements strict immutability checks across draft, active, and archived curricula, preventing accidental deletion of active subjects.
  - **Curriculum Roadmaps:** Programs contain multi-year curricula (`college_curricula` and `shs_curricula`). Subjects are mapped to specific Year Levels and Semesters in `college_curriculum_subjects` and `shs_curriculum_subjects`.
  - **State Machine:** Curricula transition through `'draft'` -> `'active'` -> `'archived'`. Active curricula cannot have core structure modified if students are officially enrolled.

### 6.5 Registrar & Academic Scheduling
- **Primary Files:** `app/Controllers/Admin/Scheduler/SchedulerController.php`, `app/Controllers/Admin/Registrar/RegistrarController.php`.
- **Mechanics:**
  - Sections created under `college_sections` or `shs_sections` linked to a specific program/strand, year level, and semester.
  - Schedulers assign days (e.g., `'Monday/Wednesday'`), times (`start_time`, `end_time`), rooms, delivery modes, and instructors to section subjects in `college_section_subjects` and `shs_section_subjects`.
  - Conflict engine checks:
    - **Room Conflict:** Queries whether the specified room is booked by any other section at overlapping times on the same day.
    - **Instructor Conflict:** Compares raw string instructor names across overlapping schedules.

### 6.6 Cashier & Tuition Assessment Engine
- **Primary Files:** `app/Controllers/Admin/Finance/FinanceController.php`, `FeeController.php`, `app/Services/AssessmentService.php`.
- **Mechanics:**
  - **Assessment Generation:** `AssessmentService::generateAssessment()` creates `student_assessments`. For College, tuition is computed as `total_units * college_cost_per_unit` plus miscellaneous fees. For SHS, fees are pulled from matching `fee_templates`.
  - **Payment Processing:** Cashier records cash, bank, or GCash payments in `/sia/admin/finance/process.php`. Payments update `payment_records` and increment `total_paid` on `student_assessments`.
  - **Status Evaluation:** If `total_paid >= net_amount`, status transitions to `'paid'`. If `total_paid > 0`, status becomes `'partial'`. If payment is verified, application status updates to `'payment_verified'`.

### 6.7 Scholarship Grants & Beneficiary Management
- **Primary Files:** `app/Controllers/Admin/Scholarship/ScholarshipController.php`.
- **Mechanics:**
  - University and third-party grants configured in `scholarships` (percentage or fixed discount on tuition and miscellaneous fees).
  - Students submit applications in `scholarship_applications`.
  - Scholarship officers review applications at `/sia/admin/scholarship/review.php`.
  - Approving a scholarship inserts a record into `scholarship_recipients`, computes discount values, and recalculates the student's `student_assessments.discount_amount` and `net_amount`.

### 6.8 System Administration, Settings & Audit Logging
- **Primary Files:** `app/Controllers/Admin/System/SystemController.php`, `ReportController.php`, `DashboardController.php`.
- **Mechanics:**
  - **System Settings:** Key-value store in `system_settings` managing global parameters: `active_school_year`, `enrollment_status` (`'open'`/`'closed'`), `college_cost_per_unit`, `system_name`.
  - **Audit Logging:** Administrative events invoke `logActivity($userId, $icon, $title, $description, $affectedRecord, $oldValue, $newValue)` writing directly to `activity_logs`.
  - **User Management:** Superadministrators create, edit, deactivate, and assign granular permissions to staff accounts at `/sia/admin/system/users.php`.

---

## 7. LMS SUBSYSTEMS DEEP-DIVE

### 7.1 LMS Identity & Portal Access
- **Primary Files:** `app/Controllers/Lms/LmsAuthController.php`.
- **Mechanics:**
  - Dedicated login routes: `/sia/auth/lms_student_login.php` (Student ID + Password) and `/sia/auth/lms_faculty_login.php` (Employee ID + Password).
  - Student authentication validates against `applications.status = 'enrolled'`.
  - Faculty authentication validates against `users.role = 'faculty'`.

### 7.2 Course Structure & Section Binding
- **Primary Files:** `app/Controllers/Admin/LmsAdminController.php`, `app/Services/LmsService.php`, `app/Repositories/CollegeEnrollmentRepository.php`, `app/Repositories/ShsEnrollmentRepository.php`.
- **Mechanics:**
  - An LMS Course (`lms_courses`) links a curriculum subject (`subjects.id`) to an enrolled section (`academic_section_id`) and an assigned instructor (`faculty_user_id`).
  - Students access courses mapped to their section via `LmsService::getStudentCourses($userId)`.

### 7.3 Modules & Course Content Distribution
- **Primary Files:** `app/Controllers/Lms/FacultyController.php`, `app/Controllers/Lms/DownloadController.php`.
- **Mechanics:**
  - Faculty organize coursework into sequential modules (`lms_modules`) having title, description, display order, and status (`'draft'`/`'published'`).
  - Digital materials (PDFs, slide decks, documents) uploaded into `lms_materials` linked to a module.
  - Students download materials via secure route `/sia/lms/download/material/{id}`, which validates course enrollment before streaming binary content.

### 7.4 Assignment Lifecycle & Submission Pipeline
- **Primary Files:** `app/Controllers/Lms/FacultyAssignmentController.php`, `app/Controllers/Lms/StudentAssignmentController.php`.
- **Mechanics:**
  - Faculty create assignments (`lms_assignments`) specifying title, description, instructions, max points, and due date.
  - Students submit assignments via `/sia/lms/student/course/{course_id}/assignments/{id}/submit`. Submissions stored in `lms_submissions` with file uploads placed in `app/uploads/lms/submissions/`.
  - Resubmission logic: If a submission already exists, updates status to `'RESUBMITTED'`.
  - Faculty grade submissions at `/sia/lms/faculty/course/{course_id}/assignments/{id}/grade`, recording numeric grade and feedback.

### 7.5 Quiz Engine, Question Bank & Grading
- **Primary Files:** `app/Controllers/Lms/FacultyQuizController.php`, `app/Controllers/Lms/StudentQuizController.php`, `app/Services/LmsQuizService.php`.
- **Mechanics:**
  - Quizzes created under `lms_quizzes` with settings: time limit (minutes), passing score, max attempts, open date, due date.
  - Faculty add questions (`lms_questions`) with types: Multiple Choice (`multiple_choice`), True/False (`true_false`), and Short Answer (`short_answer`). Choices stored in `lms_question_choices`.
  - When student starts a quiz, `LmsQuizService::startAttempt()` creates `lms_quiz_attempts` with `status = 'in_progress'`.
  - Submissions evaluated in a database transaction (`submitAttempt()`): evaluates choices against `is_correct`, tallies points, records answers in `lms_quiz_answers`, updates attempt score, and sets status to `'graded'`.

### 7.6 Gradebook & Performance Computation
- **Primary Files:** `app/Controllers/Lms/FacultyGradebookController.php`, `app/Controllers/Lms/StudentGradebookController.php`, `app/Services/LmsGradebookService.php`.
- **Mechanics:**
  - Faculty configure grade items in `lms_grade_items` categorized into: Assignment, Quiz, Exam, Project, Attendance.
  - Weighted grade computation computes student overall course percentage and automatically converts into Philippine collegiate grading scale (1.00 to 5.00, where 1.00 is Excellent, 3.00 is Passing, and 5.00 is Failed).

### 7.7 Attendance Tracking & Sessions
- **Primary Files:** `app/Controllers/Lms/FacultyAttendanceController.php`, `app/Controllers/Lms/StudentAttendanceController.php`.
- **Mechanics:**
  - Faculty initiate attendance sessions (`lms_attendance_sessions`) for a specific date and time.
  - Faculty record student attendance (`lms_attendance_records`): `'present'`, `'late'`, `'absent'`, or `'excused'`.
  - Students view personal attendance percentages and historical logs in their portal.

### 7.8 Announcements & Academic Calendar
- **Primary Files:** `app/Controllers/Lms/FacultyAnnouncementController.php`, `app/Controllers/Lms/StudentAnnouncementController.php`, `app/Services/LmsCalendarService.php`.
- **Mechanics:**
  - Course-specific notices published in `lms_announcements`.
  - Aggregated calendar service combines assignment due dates, quiz schedules, and class timetables into an integrated JSON calendar feed.

---

## 8. ENROLLMENT ↔ LMS INTEGRATION LAYER

### 8.1 Shared vs. Fragmented Data Models

| Data Concept | Enrollment Representation | LMS Representation | Integration Mechanism | Current State |
| :--- | :--- | :--- | :--- | :--- |
| **Student Identity** | `users` (`role='student'`, `student_number`) | `users` (`role='student'`) | Shared `users.id` | **Synchronized** |
| **Faculty Identity** | `users` (`role='faculty'`) | `users` (`role='faculty'`) | Shared `users.id` | **Partially Disconnected** (Creation bug in SystemController) |
| **Subjects Catalog** | `subjects` | `subjects` via `lms_courses.subject_id` | Foreign Key (`fk_lms_course_subject`) | **Synchronized** |
| **Course Section** | `college_sections`, `shs_sections` | `lms_courses.academic_section_id` | Logical reference (no FK constraint) | **Fragile** (Orphan risk on deletion) |
| **Faculty Assignment** | `*.section_subjects.instructor` | `lms_courses.faculty_user_id` | Unlinked: Raw String vs. User ID FK | **BROKEN** |
| **Student Enrollment** | `college_enrollments`, `shs_enrollments` | Dynamic query across section enrollments | Polled dynamically via `LmsService` | **Synchronized** |

### 8.2 The Faculty Scheduling & Course Generation Workflow

```text
[INTENDED WORKFLOW]
Superadmin creates Faculty Account (users table)
   ↓
Faculty user appears in Scheduler dropdown
   ↓
Scheduler assigns Faculty User ID to Section Subject
   ↓
System automatically generates/updates matching lms_courses record
   ↓
Faculty logs into LMS and immediately sees assigned Course

[ACTUAL CODEBASE REALITY]
Superadmin attempts to create Faculty in SystemController
   └── ❌ FAILS: Throws Exception "Invalid role specified." Role whitelist omits 'faculty'.
Scheduler assigns Instructor in SchedulerController
   └── ⚠️ MANUAL STRING: Types name as raw text into VARCHAR(150) instructor column.
Scheduler publishes timetable
   └── ❌ NO SYNC: Does NOT create or update lms_courses.
LMS Admin opens Course Generator (/sia/admin/lms/generator)
   └── ⚠️ MANUAL MAPPING: Admin must manually pair section subjects with faculty user IDs.
```

### 8.3 Student Enrollment to Course Provisioning Pipeline
When a student enrolls officially:
1. Application status transitions to `'enrolled'`.
2. Student assigned a `section_id` and registered in `college_enrollments` or `shs_enrollments`.
3. When student opens `/sia/lms/student/dashboard.php`, `LmsService::getStudentCourses()` queries `CollegeEnrollmentRepository` or `ShsEnrollmentRepository`.
4. Repositories locate existing `lms_courses` records matching the student's section and subjects.
5. **The JIT Fallback Hazard:** If no course was generated by the admin, repositories execute an inline `INSERT INTO lms_courses`, assigning the course to whichever faculty member has the lowest ID or hardcoded fallback ID `18`.

---

## 9. END-TO-END WORKFLOW TRACING (17 INSTITUTIONAL PROCESSES)

### Workflow 1: Applicant Self-Registration
- **Entry Point:** POST `/sia/auth/register.php` -> `AuthController::register`
- **Validation:** First name, last name, email, strong password check (`isPasswordStrong()`).
- **Database Changes:** `INSERT INTO users (first_name, last_name, email, password, role, email_verified, verification_code) VALUES (..., 'applicant', 0, '123456')`.
- **Output:** Generates OTP, stores in session, redirects to `/sia/auth/verify_otp.php`.

### Workflow 2: OTP Verification
- **Entry Point:** POST `/sia/auth/verify_otp.php` -> `AuthController::verifyOtp`
- **Validation:** Matches input OTP with `users.verification_code` and validates `verification_code_expires_at > NOW()`.
- **Database Changes:** `UPDATE users SET email_verified = 1, verification_code = NULL WHERE id = ?`.
- **Next State:** Sets `$_SESSION['logged_in'] = true`, redirects to `/sia/applicant/dashboard.php`.

### Workflow 3: Enrollment Application Submission
- **Entry Point:** POST `/sia/enroll/college.php` / `/sia/enroll/shs.php` -> `EnrollController::submit`
- **Validation:** Complete demographic data, program/strand choice, previous school information.
- **Database Changes:** Generates unique reference (`APP-YYYY-XXXXXX`), executes `INSERT INTO applications (...) VALUES (..., 'pending')`. Inserts chosen subjects into `application_subject_requests`.
- **Next State:** Application status becomes `'pending'`.

### Workflow 4: Requirement Document Upload
- **Entry Point:** POST `/sia/applicant/documents.php` -> `DocumentController::upload`
- **Validation:** File size <= 5MB, MIME inspection via `finfo` against whitelist (`pdf`, `jpg`, `png`).
- **Database Changes:** `INSERT INTO application_documents (application_id, document_name, file_path, status) VALUES (?, ?, ?, 'pending')`.
- **Next State:** Document appears in Admissions Officer's verification queue.

### Workflow 5: Medical History & Clinic Evaluation
- **Entry Point:** POST `/sia/applicant/health_record.php` -> `HealthController::submit`
- **Review:** POST `/sia/admin/clinic/process.php` -> `ClinicController::process`
- **Database Changes:** `health_records.status` updated to `'verified'`, `'correction_required'`, or `'rejected'`. Activity log recorded.
- **Next State:** Health record marked verified; prerequisite for final enrollment.

### Workflow 6: Admissions Queue Review & Approval
- **Entry Point:** POST `/sia/admin/admissions/process.php` -> `AdmissionsController::process`
- **Validation:** Reviewer checks documents, academic records, and medical clearance.
- **Database Changes:** `UPDATE applications SET status = 'approved', admin_feedback = ? WHERE id = ?`.
- **Next State:** Application status `'approved'`. Unlocks section allocation and tuition assessment.

### Workflow 7: Curriculum & Subject Assignment
- **Entry Point:** POST `/sia/admin/registrar/enrollment_process.php` -> `RegistrarController::assignCurriculum`
- **Validation:** Validates curriculum version and compatibility with program/strand.
- **Database Changes:** `UPDATE applications SET college_curriculum_id = ? WHERE id = ?`. Inserts curriculum subjects into `application_subject_requests`.
- **Next State:** Subjects mapped for billing and sectioning.

### Workflow 8: Section Allocation
- **Entry Point:** POST `/sia/admin/registrar/enrollment_process.php` -> `RegistrarController::assignSection`
- **Validation:** Checks section capacity (`capacity > enrolled_count`).
- **Database Changes:** `UPDATE applications SET section_id = ? WHERE id = ?`.
- **Next State:** Section locked in; timetable scheduled.

### Workflow 9: Tuition Assessment Generation
- **Entry Point:** GET `/sia/admin/finance/assessment.php?id={app_id}` -> `FinanceController::assessment`
- **Service:** Calls `AssessmentService::generateAssessment($pdo, $appId)`.
- **Database Changes:** Calculates units, fees, laboratory charges. Inserts record into `student_assessments` with `payment_status = 'unpaid'`.
- **Next State:** Assessment ledger created; student can pay tuition.

### Workflow 10: Cashiering & Payment Recording
- **Entry Point:** POST `/sia/admin/finance/process.php` -> `FinanceController::process`
- **Validation:** Amount > 0, valid payment method (`Cash`, `GCash`, `Bank Transfer`).
- **Database Changes:** Begins transaction (`FOR UPDATE` on assessment). Inserts `payment_records`. Increments `student_assessments.total_paid`. Updates `payment_status` (`'partial'` or `'paid'`).
- **Next State:** `applications.status` updated to `'payment_verified'`.

### Workflow 11: Official Enrollment Finalization
- **Entry Point:** POST `/sia/admin/registrar/finalize.php` -> `RegistrarController::finalize`
- **Service:** Calls `EnrollmentService::finalizeEnrollment($applicationId, $adminUserId, $pdo)`.
- **Database Changes:**
  1. Assigns Student ID via `StudentNumberService::generate()`.
  2. Generates institutional email (`first.last@ttu.edu.ph`) and temporary password.
  3. Updates `users.role = 'student'`, `users.student_number = ?`, `users.ttu_email = ?`.
  4. Populates `college_enrollments` or `shs_enrollments` with all section subjects.
  5. Updates `applications.status = 'enrolled'`.
  6. Dispatches credentials email via PHPMailer.
- **Next State:** Applicant is now officially a Student.

### Workflow 12: LMS Course Generation (Admin)
- **Entry Point:** POST `/sia/admin/lms/generate` -> `LmsAdminController::generateLmsCourse`
- **Validation:** Validates academic level, section ID, subject ID, and faculty user ID.
- **Database Changes:** `INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status) VALUES (?, ?, ?, ?, 'active')`.
- **Next State:** Course active and available in LMS.

### Workflow 13: Student LMS Access & Dashboard
- **Entry Point:** GET `/sia/lms/student/dashboard.php` -> `StudentController::dashboard`
- **Service:** Calls `LmsService::getStudentCourses($userId)`.
- **Database Changes:** Reads enrollments; runs JIT fallback if courses unmapped.
- **Next State:** Student views course cards, units, teachers, and enrolled classmate totals.

### Workflow 14: Faculty Module Creation & Material Upload
- **Entry Point:** POST `/sia/lms/faculty/module_create.php` and `/material_upload.php` -> `FacultyController`
- **Validation:** Validates course ownership via `LmsService::isFacultyAuthorizedForCourse()`.
- **Database Changes:** Inserts into `lms_modules` and `lms_materials`.
- **Next State:** Material appears in course stream.

### Workflow 15: Assignment Submission & Grading
- **Submission:** POST `/sia/lms/student/course/{c_id}/assignments/{id}/submit` -> `StudentAssignmentController::submit`. Writes file and updates `lms_submissions`.
- **Grading:** POST `/sia/lms/faculty/course/{c_id}/assignments/{id}/grade` -> `FacultyAssignmentController::grade`. Inserts/updates grade and feedback.
- **Next State:** Submission marked `'GRADED'`.

### Workflow 16: Timed Quiz Attempt & Scoring
- **Start:** POST `/sia/lms/student/.../quizzes/{id}/start` -> `StudentQuizController::start`. Inserts `lms_quiz_attempts`.
- **Submit:** POST `/sia/lms/student/.../quizzes/{id}/submit` -> `StudentQuizController::submit`. Calls `LmsQuizService::submitAttempt()`. Evaluates choices in transaction, calculates score, updates attempt status to `'graded'`.
- **Next State:** Results available to student and faculty.

### Workflow 17: Final Gradebook Compilation
- **Entry Point:** GET `/sia/lms/faculty/course/{c_id}/gradebook` -> `FacultyGradebookController::index`
- **Service:** Calls `LmsGradebookService::getCourseGradebook($courseId)`.
- **Calculations:** Aggregates assignment scores, quiz scores, exam grades, attendance. Converts weighted percentage to 1.00–5.00 GPA scale.
- **Next State:** Official course grade sheet rendered.

---

## 10. MODULE & FILE RELATIONSHIP GRAPHS

### 10.1 Enrollment Module Relationship Graph
```text
[HTTP REQUEST]
     │
     ▼
app/Routes/web.php (Route: /sia/admin/admissions/*)
     │
     ▼
app/Controllers/Admin/Admissions/AdmissionsController.php
     ├── App\Core\Database::getConnection() ──► MariaDB (applications, users)
     ├── App\Models\Application.php
     ├── App\Models\ApplicationDocument.php
     ├── App\Services\EnrollmentService.php
     │        ├── App\Services\StudentNumberService.php ──► MariaDB (users.student_number)
     │        └── PHPMailer\PHPMailer ────────────────────► SMTP Gateway
     └── app/Views/admin/admissions/review.php
```

### 10.2 LMS Quiz Subsystem Relationship Graph
```text
[HTTP REQUEST]
     │
     ▼
app/Routes/web.php (Route: /sia/lms/student/course/{c_id}/quizzes/{q_id}/attempt/{a_id}/submit)
     │
     ▼
app/Controllers/Lms/StudentQuizController.php
     │
     ├── App\Services\LmsService.php
     │        └── isStudentAuthorizedForCourse() ──► Repositories
     │
     └── App\Services\LmsQuizService.php
              ├── getQuiz() ───────────────────────► MariaDB (lms_quizzes)
              ├── getQuestions() ──────────────────► MariaDB (lms_questions, choices)
              └── submitAttempt() [TRANSACTION]
                       ├── INSERT ─────────────────► MariaDB (lms_quiz_answers)
                       └── UPDATE ─────────────────► MariaDB (lms_quiz_attempts: status='graded')
     │
     ▼
app/Views/lms/student/quizzes/result.php
```

---

## 11. MASTER ROUTE CATALOG & MIDDLEWARE MAPPING

| HTTP Method | Route URI Pattern | Target Controller & Action | Middleware Applied | Granular Permission Checked |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/sia/` | `HomeController@index` | None | Public |
| **GET** | `/sia/auth/login.php` | `AuthController@login` | None | Public |
| **POST** | `/sia/auth/login.php` | `AuthController@processLogin` | `CsrfMiddleware` | Public |
| **GET** | `/sia/auth/logout.php` | `AuthController@logout` | `AuthMiddleware` | Authenticated |
| **GET** | `/sia/applicant/dashboard.php` | `ApplicantController@dashboard` | `SessionSecurity`, `Csrf`, `Auth`, `Role:applicant` | Applicant Role |
| **POST** | `/sia/applicant/documents.php` | `DocumentController@upload` | `SessionSecurity`, `Csrf`, `Auth`, `Role:applicant` | Applicant Role |
| **GET** | `/sia/admin/dashboard.php` | `DashboardController@index` | `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | Any Admin Role |
| **GET** | `/sia/admin/admissions/review.php`| `AdmissionsController@review`| `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | None (Security Gap) |
| **POST** | `/sia/admin/admissions/process.php`| `AdmissionsController@process`| `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | None (Security Gap) |
| **GET** | `/sia/admin/clinic/medical_clearance.php`| `ClinicController@index`| `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | `medical.review` |
| **GET** | `/sia/admin/registrar/subjects.php`| `SubjectController@index` | `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | None (Security Gap) |
| **POST** | `/sia/admin/registrar/subjects.php`| `SubjectController@process` | `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | None (Security Gap) |
| **GET** | `/sia/admin/scheduler/scheduler_dashboard.php`| `SchedulerController@dashboard`| `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | None |
| **POST** | `/sia/admin/finance/process.php` | `FinanceController@process` | `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | None (Security Gap) |
| **GET** | `/sia/admin/system/users.php` | `SystemController@users` | `SessionSecurity`, `Csrf`, `Auth`, `Role:admin` | Superadmin Only |
| **GET** | `/sia/lms/student/dashboard.php` | `StudentController@dashboard` | `SessionSecurity`, `Csrf`, `Auth` | Missing Role Guard |
| **GET** | `/sia/lms/faculty/dashboard.php` | `FacultyController@dashboard` | `SessionSecurity`, `Csrf`, `Auth` | Missing Role Guard |
| **POST** | `/sia/lms/faculty/module_create.php`| `FacultyController@createModule`| `SessionSecurity`, `Csrf`, `Auth` | Missing Role Guard |
| **POST** | `/sia/lms/faculty/material_upload.php`| `FacultyController@uploadMaterial`| `SessionSecurity`, `Csrf`, `Auth` | Missing Role Guard |
| **GET** | `/sia/lms/download/material/{id}`| `DownloadController@downloadMaterial`| `SessionSecurity`, `Auth` | Checks `$_SESSION['role']` |

---

## 12. CONFIGURATION, DEPENDENCIES & ENVIRONMENT

### 12.1 Web Server & PHP Runtime Requirements
- **PHP Version:** 8.1+ required (strict typing, match expressions, constructor property promotion, enums).
- **Mandatory Extensions:** `pdo_mysql`, `fileinfo` (MIME validation), `json`, `session`, `mbstring`.
- **Web Server:** Apache 2.4+ with `mod_rewrite` enabled.
- **Directory Security:** `.htaccess` line 8 prohibits direct web requests to `/app/`, `/config/`, and `/database/`.

### 12.2 External Composer Dependencies (`composer.json`)
- `phpmailer/phpmailer`: Institutional email delivery via SMTP.
- `vlucas/phpdotenv`: Environment variable management.

---

## 13. ARCHITECTURAL RISKS, KNOWN GAPS & UNCERTAINTIES

### Critical Operational Gaps
1. **LMS CSRF Token Omission:** `CsrfMiddleware` intercepts all POST requests on `/sia/lms/*`, but none of the LMS view templates include a `csrf_token` input. Every state-changing LMS form fails with HTTP 403.
2. **Download Controller Role Key Mismatch:** `DownloadController.php` checks `$_SESSION['role']`, which is never set by authentication controllers. All course material and assignment downloads return HTTP 403 Forbidden.
3. **Faculty Material Upload Path Escape:** `FacultyController::uploadMaterial` uses `__DIR__ . '/../../../../storage/lms_materials/'`, escaping into `c:\xampp\storage\lms_materials/`. `DownloadController` looks in `app/uploads/lms/`, causing 404 errors for all uploaded materials.
4. **LMS Route Group Lacks Role Guards:** Both `/lms/student/*` and `/lms/faculty/*` are grouped together with only `AuthMiddleware`. Students can access faculty routes.
5. **Scheduler Disconnected from LMS:** Schedules store instructor names as loose strings, while LMS requires foreign keys to `users.id`. Schedules do not sync to `lms_courses`.
6. **Unsafe JIT Course Provisioning:** Enrollment repositories execute database `INSERT` statements inside read queries, arbitrarily assigning courses to lowest faculty IDs or hardcoded ID 18.
7. **Horizontal Privilege Escalation in Admin Portal:** Admin controllers lack internal `requirePermission()` calls, allowing any administrative role to execute cross-departmental actions.

---

## 14. VERIFICATION REPORT & GROUND TRUTH SUMMARY

### Confirmed Ground Truths
- The actual codebase strictly implements Vanilla PHP Hybrid MVC with Fat Controllers and Thin Models.
- `DocumentController` implements robust MIME-type verification via `finfo`.
- `AssessmentService` and `EnrollmentService` correctly encapsulate complex multi-table transactions.
- Obsolete SQL scripts (`lms_phase2.sql`) exist in the repository but `database/schema.sql` is the canonical schema.

### Disproven Assumptions & False Positives
- **False Positive:** Suspected SQL injection in `SchedulerController` dynamic table interpolation.  
  *Verification:* Variables are strictly computed internally via ternary conditions based on boolean flags and are never derived from user input.
- **False Positive:** Suspected missing permission checks in `ClinicController`.  
  *Verification:* All actions explicitly enforce `requirePermission('medical.review')`.

### Post-Migration Architecture Update (September 2026 - Option A+)
The following critical gaps identified in Section 13 have been officially remediated under **Option A+ Architecture**:
1. **Scheduler ↔ LMS Disconnection (Resolved):** Schedulers now select instructors from `faculty_profiles` joined with `users`. Schedules persist `faculty_user_id` via dual-write, and automated idempotent upsert synchronizes `lms_courses` on save.
2. **Compound-Day Collision Blindspot (Resolved):** `SchedulerController::decomposeDays()` evaluates decomposed day tokens (`'MWF'` vs `'M'`), intercepting all schedule overlaps.
3. **Unsafe JIT Lowest-ID Provisioning Bug (Resolved):** `CollegeEnrollmentRepository` and `ShsEnrollmentRepository` resolve LMS courses strictly from `college_section_subjects.faculty_user_id`, eliminating arbitrary assignment to Alan Turing (ID 8) or fallback 18.
4. **Catastrophic Cascade Deletion Hazard (Resolved):** `lms_courses.faculty_user_id` was altered to `ON DELETE RESTRICT`, permanently protecting academic course history from accidental cascade erasure.
5. **Admissions Password Overwrite (Resolved):** `EnrollmentService::finalizeEnrollment` preserves applicant registration password hashes and synchronizes `users.role = 'student'` and `users.lms_status = 'active'`.

*For complete migration specifications, see `docs/codebase/FINAL_SYSTEM_MAP.md` and `docs/migration/05_POST_MIGRATION_VERIFICATION.md`.*

---
*End of TTU Complete Codebase Technical Reference Manual.*
