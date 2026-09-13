# 00. PROJECT BASELINE & SAFETY VERIFICATION

**Phase 0 Execution Document**  
**Repository:** `c:\xampp\htdocs\sia`  
**Date:** September 13, 2026  
**Status:** VERIFIED

---

## 1. Repository Structure & Root Directory
- **Application Root:** `c:\xampp\htdocs\sia\`
- **Web Root:** `c:\xampp\htdocs\sia\public\`
- **Server Environment:** Apache 2.4+ on Windows (XAMPP environment).
- **URL Rewriting:** `c:\xampp\htdocs\sia\.htaccess` intercepts requests, prevents directory indexing (`Options -Indexes`), denies direct access to `/app/`, `/config/`, and `/database/`, and routes all other non-static file requests to `public/index.php`.

---

## 2. Environment & Runtime Detection
- **PHP Version:** `PHP 8.2.12 (cli)` (ZTS Visual C++ 2019 x64).
  - Modern syntax features utilized: Named arguments, match expressions, strict types, constructor property promotion, enums.
- **Database Engine:** MariaDB 10.4.32 / MySQL.
  - Connection: PDO with `ATTR_ERRMODE => ERRMODE_EXCEPTION`, `ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC`, `ATTR_EMULATE_PREPARES => false`.
  - Database Name: `sia`.
- **Operating System:** Windows.

---

## 3. Composer Dependencies & Libraries
Inspected `c:\xampp\htdocs\sia\composer.json`:
- **Packages:**
  - `phpmailer/phpmailer: ^7.1` (used for sending email OTPs and student credentials).
- **Notice:** No web framework (Laravel, Symfony, CodeIgniter) is installed. The system is built on a custom Vanilla PHP MVC architecture.
- **Notice:** No automated testing packages (`phpunit/phpunit`, `pestphp/pest`) are installed.

---

## 4. Configuration Infrastructure
- **`.env`:** Environment configuration located at `c:\xampp\htdocs\sia\.env` containing:
  - Database settings: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
  - Mail settings: `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- **`config/database.php`:** Loads `.env` manually, establishes PDO connection with `utf8mb4` charset and strict attributes.
- **`config/app.php`:** *DOES NOT EXIST.* (Previously assumed; confirmed absent during forensic scan).
- **`public/index.php`:** Boots custom PSR-4 autoloader (`App\` -> `app/`), parses `.env`, requires `app/Helpers/functions.php`, instantiates `Request`, `Response`, `Router`, loads `app/Routes/web.php`, and calls `$router->resolve()`.

---

## 5. Major System Modules

### 5.1 Enrollment System Code
- **Controllers:**
  - `app/Controllers/ApplicantController.php` (Applicant portal, milestones, profile).
  - `app/Controllers/DocumentController.php` (Requirement upload, validation, review).
  - `app/Controllers/EnrollController.php` (Public College & SHS application intake forms).
  - `app/Controllers/HealthController.php` (Applicant medical history submission).
  - `app/Controllers/Admin/Admissions/AdmissionsController.php` (Admissions review queue, application processing).
  - `app/Controllers/Admin/Clinic/ClinicController.php` (Medical clearance, health records).
  - `app/Controllers/Admin/Finance/FinanceController.php` (Tuition cashiering, payments).
  - `app/Controllers/Admin/Finance/FeeController.php` (Institutional fee templates).
  - `app/Controllers/Admin/Registrar/RegistrarController.php` (Student records, curriculum assignment, sectioning).
  - `app/Controllers/Admin/Registrar/SubjectController.php` (Master subject catalog & usage tracking).
  - `app/Controllers/Admin/Registrar/CollegeController.php` (College curricula & roadmaps).
  - `app/Controllers/Admin/Registrar/ShsController.php` (SHS strands & curricula).
  - `app/Controllers/Admin/Scheduler/SchedulerController.php` (Sections & schedule timetables).
  - `app/Controllers/Admin/Scholarship/ScholarshipController.php` (Grants & recipients).
  - `app/Controllers/Admin/System/SystemController.php` (Users, settings, activity logs).
  - `app/Controllers/Admin/System/ReportController.php` (Enrollment analytics).
  - `app/Controllers/Admin/System/DashboardController.php` (Admin overview).
- **Models:**
  - `app/Models/User.php`
  - `app/Models/Application.php`
  - `app/Models/ApplicationDocument.php`
  - `app/Models/HealthRecord.php`
  - `app/Models/StudentAssessment.php`
  - `app/Models/ScholarshipApplication.php`
  - `app/Models/ActivityLog.php`
  - `app/Models/Announcement.php`
  - `app/Models/Schedule.php`
  - `app/Models/BaseModel.php`
- **Repositories:**
  - `app/Repositories/EnrollmentRepositoryInterface.php`
  - `app/Repositories/CollegeEnrollmentRepository.php`
  - `app/Repositories/ShsEnrollmentRepository.php`
- **Domain Services:**
  - `app/Services/AssessmentService.php` (Tuition fee computation engine).
  - `app/Services/EnrollmentService.php` (Official enrollment finalization engine).
  - `app/Services/StudentNumberService.php` (Student ID formatting & allocation).
- **Views:**
  - `app/Views/applicant/` (Applicant portal).
  - `app/Views/admin/` (Sub-portals for admissions, registrar, clinic, finance, scheduler, scholarship, system).
  - `app/Views/public/` (Public intake forms).

### 5.2 LMS (Learning Management System) Code
- **Controllers (`app/Controllers/Lms/`):**
  - `LmsAuthController.php` (Student & Faculty login portal).
  - `StudentController.php` (Student dashboard, enrolled courses overview).
  - `FacultyController.php` (Faculty dashboard, module manager, material uploader).
  - `DownloadController.php` (Binary file streaming for materials & submissions).
  - `StudentAssignmentController.php` (Assignment detail & file submission).
  - `FacultyAssignmentController.php` (Assignment creation & submission grading).
  - `StudentQuizController.php` (Timed quiz taking & answer submission).
  - `FacultyQuizController.php` (Quiz creation, question management, results).
  - `StudentGradebookController.php` (Student grade view).
  - `FacultyGradebookController.php` (Faculty grade calculation & entry).
  - `StudentAttendanceController.php` (Student attendance tracking).
  - `FacultyAttendanceController.php` (Session roll call creation).
  - `StudentCalendarController.php` / `FacultyCalendarController.php` (Class timetable).
  - `StudentAnnouncementController.php` / `FacultyAnnouncementController.php` (Course notices).
  - `app/Controllers/Admin/LmsAdminController.php` (LMS course manual mapping generator).
- **LMS Domain Services (`app/Services/`):**
  - `LmsService.php` (Core LMS course discovery, authorizations, materials, submissions).
  - `LmsQuizService.php` (Quiz questions, options, attempts, automated scoring).
  - `LmsGradebookService.php` (Gradebook weighted calculation & 1.00–5.00 GPA conversion).
  - `LmsCalendarService.php` (Timetable & assignment calendar aggregation).
  - `LmsAnnouncementService.php` (Course announcement management).
  - `LmsAttendanceService.php` (Session & roll call management).
- **LMS Views:**
  - `app/Views/lms/student/`
  - `app/Views/lms/faculty/`

---

## 6. Authentication & Session Infrastructure
- **Authentication Handlers:**
  - `app/Controllers/AuthController.php`: Handles main login, registration, OTP verification, password reset.
  - `app/Controllers/Lms/LmsAuthController.php`: Handles student and faculty dedicated LMS authentication.
- **Session Management:**
  - Standard PHP sessions (`session_start()`).
  - Session Regeneration: `session_regenerate_id(true)` invoked upon successful login.
  - Security Guard: `app/Middleware/SessionSecurityMiddleware.php` validates IP address (`$_SESSION['user_ip']`), browser User-Agent (`$_SESSION['user_agent']`), and enforces inactivity timeouts (1800 seconds) and absolute expiration (14400 seconds).
- **Session Keys Contract:**
  - User identity: `$_SESSION['user_id']`, `$_SESSION['user_email']`, `$_SESSION['user_name']`.
  - Role: `$_SESSION['user_role']`.
  - Permissions: `$_SESSION['user_permissions']` (JSON-decoded array).
  - CSRF Token: `$_SESSION['csrf_token']`.
  - Supplementary LMS keys: `$_SESSION['lms_logged_in']`, `$_SESSION['lms_user_id']`, `$_SESSION['lms_role']`.

---

## 7. Middleware & RBAC Infrastructure
- **Middleware Pipeline:** `app/Core/Router.php` dispatches through:
  1. `App\Middleware\SessionSecurityMiddleware`
  2. `App\Middleware\CsrfMiddleware` (Checks token on POST, PUT, DELETE)
  3. `App\Middleware\AuthMiddleware` (Checks `!empty($_SESSION['logged_in'])`)
  4. `App\Middleware\RoleMiddleware` (Checks `$_SESSION['user_role']`)
- **Role Map in Database (`users.role` ENUM):**
  - `'superadmin'`, `'admin'`, `'admissions'`, `'scholarship'`, `'cashier'`, `'clinic'`, `'faculty'`, `'scheduler'`, `'applicant'`, `'student'`.
- **Granular Permissions Engine:** Defined in `app/Helpers/functions.php`:
  - `hasPermission(string|array $perms)`
  - `requirePermission(string|array $perms)`
  - Permissions stored in `users.permissions` as JSON text array.

---

## 8. Database & Migration Infrastructure
- **Canonical Schema:** `database/schema.sql` defines 42 tables and views.
- **Automated Database Setup Engine:** `database/migrations/setup_database.php` (CLI executable, proxy in `scripts/setup_database.php`).
- **Seed Script:** `database/seed.sql`.
- **Standalone Migrations & Updates:**
  - `database/migrations/001_create_academic_records_view.sql`
  - `database/migrations/lms_phase4_schema.sql`
  - `database/migrations/lms_phase5_schema.sql`
  - `database/migrations/lms_phase7_schema.sql`
  - `database/migrations/lms_phase8_schema.sql`
  - `database/migrations/migrate_shs_curriculum.php`
  - `database/migrations/scholarship_update.php`
  - `scripts/migrations/01_lms_courses_foundation.php`
- **Notice on Obsolete Files:** `database/lms_phase2.sql` uses obsolete column names (`teacher_id`, `college_section_id`) superseded by `database/schema.sql`.

---

## 9. Existing Documentation Inventory
- `system_documentation.md`: Exhaustive architectural reference manual (55 KB).
- `system_audit.md`: Full codebase audit report with confirmed bugs & security findings (42 KB).
- `DOCUMENTATION_COVERAGE_AUDIT.md`: Coverage audit of previous technical documentation (33 KB).
- `DEVELOPER_HANDOFF.md`: System operational overview and handoff notes (4.5 KB).
- `README.md`: Setup and installation guide (8 KB).
- `docs/INDEX.md`: Index of documentation vault.
- `docs/obsidian/`: 18 folders of subsystem markdown files.
- `docs/codebase/`: Technical reference files 00 through 13.

---

## 10. Testing Infrastructure
- **Automated Tests:** **NONE.** No PHPUnit test runner or test directories exist.
- **Validation:** Testing is currently performed via manual browser testing or execution of CLI database migration scripts.

---

### Phase 0 Baseline Conclusion
All core baseline parameters, file locations, runtime versions, packages, and modules have been forensically verified against the actual filesystem and source code. No application code was altered.
