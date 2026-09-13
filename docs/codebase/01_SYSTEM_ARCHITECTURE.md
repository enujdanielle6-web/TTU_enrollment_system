# 01. COMPLETE SYSTEM ARCHITECTURE & CODEBASE MAPPING

**Document Reference:** `docs/codebase/01_SYSTEM_ARCHITECTURE.md`  
**Execution Phase:** Phase 1 — Complete Codebase Understanding  
**Codebase:** Triple T University (TTU) Enrollment System & Learning Management System (LMS)  
**Location:** `c:\xampp\htdocs\sia\`  
**Date:** September 13, 2026  
**Status:** FORENSICALLY VERIFIED

---

## 1. Architectural Pattern: Vanilla PHP Hybrid MVC

The codebase implements a custom **Hybrid MVC (Model-View-Controller)** pattern written in modern Vanilla PHP 8.2 without external full-stack web frameworks.

```text
                                  ┌──────────────────────────┐
                                  │      HTTP Request        │
                                  └─────────────┬────────────┘
                                                │
                                                ▼
                                  ┌──────────────────────────┐
                                  │  public/index.php        │
                                  │  (Front Controller)      │
                                  └─────────────┬────────────┘
                                                │
                                                ▼
                                  ┌──────────────────────────┐
                                  │  App\Core\Router         │
                                  │  & Middleware Pipeline   │
                                  └─────────────┬────────────┘
                                                │
                        ┌───────────────────────┴───────────────────────┐
                        │                                               │
                        ▼                                               ▼
          ┌───────────────────────────┐                   ┌───────────────────────────┐
          │   Enrollment Controllers  │                   │      LMS Controllers      │
          │   (Fat Controllers)       │                   │   (Fat Controllers)       │
          └─────────────┬─────────────┘                   └─────────────┬─────────────┘
                        │                                               │
           ┌────────────┴────────────┐                     ┌────────────┴────────────┐
           │                         │                     │                         │
           ▼                         ▼                     ▼                         ▼
┌────────────────────┐    ┌────────────────────┐┌────────────────────┐    ┌────────────────────┐
│  Domain Services   │    │  Repositories      ││  LMS Services      │    │  LMS Repositories  │
│  (Enrollment,      │    │  (College/SHS      ││  (LmsService,      │    │  (College/SHS      │
│   Assessment,      │    │   Enrollment)      ││   Quiz, Gradebook, │    │   Enrollment)      │
│   StudentNumber)   │    │                    ││   Calendar)        │    │                    │
└──────────┬─────────┘    └──────────┬─────────┘└──────────┬─────────┘    └──────────┬─────────┘
           │                         │                     │                         │
           └────────────┬────────────┘                     └────────────┬────────────┘
                        │                                               │
                        ▼                                               ▼
          ┌───────────────────────────────────────────────────────────────────────────┐
          │                       App\Core\Database (Singleton PDO)                   │
          └─────────────────────────────────────┬─────────────────────────────────────┘
                                                │
                                                ▼
          ┌───────────────────────────────────────────────────────────────────────────┐
          │                    MariaDB Database: sia (42 Tables/Views)                │
          └─────────────────────────────────────┬─────────────────────────────────────┘
                                                │
                                                ▼
          ┌───────────────────────────────────────────────────────────────────────────┐
          │               Presentation Views (app/Views/* via BaseController)         │
          └───────────────────────────────────────────────────────────────────────────┘
```

### Core Architectural Principles in the Codebase
1. **Fat Controllers:** In accordance with project conventions, controllers contain input parsing, validation logic, authorization checks, workflow coordination, and direct PDO SQL query execution.
2. **Thin Models:** Models (`app/Models/*`) subclass `App\Models\BaseModel` or act as basic data containers. They provide lightweight query helpers (e.g. `User::findById()`, `Application::findByUserId()`) and do not hide complex SQL queries from controllers.
3. **Dedicated Domain Services:** Highly complex or reusable multi-table domain operations are extracted into dedicated services:
   - `App\Services\AssessmentService`: Calculates unit and template tuition fees.
   - `App\Services\EnrollmentService`: Executes the atomic enrollment finalization transaction.
   - `App\Services\StudentNumberService`: Generates unique student ID numbers.
   - `App\Services\LmsService`: Orchestrates LMS course queries and authorization.
   - `App\Services\LmsQuizService`: Evaluates quiz attempts and scoring transactions.
   - `App\Services\LmsGradebookService`: Computes weighted grade formulas and GPA scales.
4. **Abstracted Repositories:** To decouple College from Senior High School enrollment records, the LMS consumes `App\Repositories\EnrollmentRepositoryInterface` implemented by:
   - `App\Repositories\CollegeEnrollmentRepository`
   - `App\Repositories\ShsEnrollmentRepository`

---

## 2. Request Lifecycle & Front Controller Dispatch

### Step 1: Web Server Interception
Apache intercepts all requests via `c:\xampp\htdocs\sia\.htaccess`:
```apache
RewriteEngine On
Options -Indexes
RewriteRule ^(app|config|database)/ - [F,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ public/index.php [QSA,L]
RewriteCond %{REQUEST_URI} \.php$
RewriteCond %{REQUEST_URI} !public/index\.php$
RewriteRule ^(.*)$ public/index.php [QSA,L]
```
- Direct access to sensitive application code (`/app/`, `/config/`, `/database/`) is rejected with `403 Forbidden`.
- All requests for non-existing physical files are rewritten to `public/index.php`.

### Step 2: Bootstrapping in `public/index.php`
1. **Autoloading:** Registers a PSR-4 autoloader mapping the `App\` namespace to `__DIR__ . '/../app/'`. Composer packages are autoloaded if `vendor/autoload.php` is loaded.
2. **Environment Loading:** Reads and parses `.env` if present, populating `$_ENV`, `$_SERVER`, and `putenv()`.
3. **Global Procedural Helpers:** Requires `app/Helpers/functions.php` (contains `esc()`, `hasPermission()`, `requirePermission()`, `logActivity()`, `csrf_token()`, formatting functions).
4. **Container / Core Instantiation:**
   ```php
   $request = new App\Core\Request();
   $response = new App\Core\Response();
   $router = new App\Core\Router($request, $response);
   ```
5. **Route Loading:** Requires `app/Routes/web.php`.
6. **Execution:** Calls `$router->resolve()`, echoing the output or catching `HttpException` and fatal exceptions.

### Step 3: Onion Middleware Pipeline
The router organizes routes into nested middleware groups. When a route is matched, the request passes sequentially through:
```text
Request 
  └── App\Middleware\SessionSecurityMiddleware
        └── App\Middleware\CsrfMiddleware
              └── App\Middleware\AuthMiddleware
                    └── App\Middleware\RoleMiddleware
                          └── Controller Method
```

---

## 3. Authentication & Session Infrastructure

### 3.1 Authentication Entry Points
The system maintains two distinct authentication entry points:

#### 1. Main Institutional Authentication
- **File:** `app/Controllers/AuthController.php`
- **Routes:**
  - `GET /sia/auth/login.php` -> `AuthController::login`
  - `POST /sia/auth/login.php` -> `AuthController::processLogin`
  - `GET /sia/auth/register.php` -> `AuthController::register`
  - `POST /sia/auth/register.php` -> `AuthController::processRegister`
  - `GET /sia/auth/verify_otp.php` -> `AuthController::verifyOtpView`
  - `POST /sia/auth/verify_otp.php` -> `AuthController::verifyOtp`
  - `GET /sia/auth/logout.php` -> `AuthController::logout`
- **Mechanism:**
  - Login queries: `SELECT * FROM users WHERE email = :email LIMIT 1`.
  - Checks brute-force lockouts against the `login_attempts` table.
  - Verifies passwords using `password_verify($password, $user['password'])`.
  - Regenerates session ID: `session_regenerate_id(true)`.
  - Writes core session variables:
    ```php
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_first_name'] = $user['first_name'];
    $_SESSION['user_last_name'] = $user['last_name'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_department'] = $user['department'] ?? 'None';
    $_SESSION['user_permissions'] = $user['permissions'] ? json_decode($user['permissions'], true) : [];
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['created_time'] = time();
    ```

#### 2. Dedicated LMS Portal Authentication
- **File:** `app/Controllers/Lms/LmsAuthController.php`
- **Routes:**
  - `GET /sia/auth/lms_student_login.php` -> Form for Student ID (`student_number`) + Password.
  - `GET /sia/auth/lms_faculty_login.php` -> Form for Employee ID (`student_number`) + Password.
  - `POST /sia/auth/lms_login.php` -> `LmsAuthController::login`
- **Mechanism:**
  - **Student Login:** Queries `SELECT * FROM users WHERE student_number = :sid AND is_active = 1`. Then checks whether the student has an officially enrolled application: `SELECT COUNT(*) FROM applications WHERE user_id = :uid AND status = 'enrolled'`. If not enrolled, rejects login. Sets `$_SESSION['user_role'] = 'student'` and `$_SESSION['lms_role'] = 'student'`.
  - **Faculty Login:** Queries `SELECT * FROM users WHERE student_number = :eid AND role = 'faculty' AND is_active = 1`. Sets `$_SESSION['user_role'] = 'faculty'` and `$_SESSION['lms_role'] = 'faculty'`.

### 3.2 Session Security Guard
- **File:** `app/Middleware/SessionSecurityMiddleware.php`
- **Enforcements:**
  1. Starts session securely if not already started.
  2. Generates `$_SESSION['csrf_token']` via `bin2hex(random_bytes(32))` if absent.
  3. Validates client IP consistency: `$_SESSION['user_ip'] === $_SERVER['REMOTE_ADDR']`.
  4. Validates browser User-Agent consistency: `$_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT']`.
  5. Inactivity timeout: Expires session if idle for > 1800 seconds (30 minutes).
  6. Absolute timeout: Forces re-authentication if session lifetime exceeds 14400 seconds (4 hours).

---

## 4. Authorization & RBAC Infrastructure

### 4.1 Route Middleware Level (`App\Middleware\RoleMiddleware`)
Located in `app/Middleware/RoleMiddleware.php`.
- Parameterized in `app/Routes/web.php` (e.g. `'App\Middleware\RoleMiddleware:admin'`).
- Checks `$_SESSION['user_role']`.
- **The `'admin'` Alias:** If `'admin'` is specified as the allowed role, the middleware permits ANY of the 7 staff/administrative roles:
  ```php
  $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic', 'scheduler'];
  ```
- **Redirection:**
  - Unauthorized applicants are redirected to `/sia/applicant/dashboard.php`.
  - Unauthorized staff are redirected to their respective sub-portal dashboard.
  - Unknown/unrecognized roles trigger session destruction (`session_destroy()`) and redirect to `/sia/auth/login.php`.

### 4.2 Action & Method Level (`requirePermission()`)
Defined in `app/Helpers/functions.php`:
```php
function hasPermission(string|array $permissions, ?array $userPerms = null, ?string $userRole = null): bool
function requirePermission(string|array $permissions): void
```
- Reads `$_SESSION['user_permissions']` and `$_SESSION['user_role']`.
- `superadmin` role automatically bypasses all permission checks (wildcard `["*"]`).
- Evaluates whether the user's assigned permissions contain the required permission string.
- If unauthorized, sets flash error message `$_SESSION['admin_error']` and redirects to the user's role-appropriate dashboard.

---

## 5. Database Access Flow & Patterns

### 5.1 Singleton PDO Connection
Defined in `app/Core/Database.php`:
- Connects using credentials from `.env` via `config/database.php`.
- Settings:
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (all database errors throw `PDOException`).
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (returns associative arrays).
  - `PDO::ATTR_EMULATE_PREPARES => false` (native prepared statements).

### 5.2 Transaction & Concurrency Patterns
- Read-heavy queries execute prepared statements without transactions.
- Critical financial and grading operations enforce database transactions:
  - `App\Controllers\Admin\Finance\FinanceController::process()` locks student assessment records using `SELECT * FROM student_assessments WHERE id = :id FOR UPDATE`.
  - `App\Services\EnrollmentService::finalizeEnrollment()` wraps student number generation, email assignment, credential encryption, and section subject enrollment inside a single atomic transaction.
  - `App\Services\LmsQuizService::submitAttempt()` wraps answer evaluation, point allocation, and attempt grading in a single transaction.

---

## 6. Enrollment System Architecture

The Enrollment System is organized into seven operational domains:

```text
[Applicant Registration] ──► [Requirement Upload] ──► [Clinic Clearance]
          │                           │                       │
          ▼                           ▼                       ▼
[EnrollController]           [DocumentController]    [ClinicController]
          │                           │                       │
          └───────────────────────────┼───────────────────────┘
                                      ▼
                        [Admissions Review Queue]
                         (AdmissionsController)
                                      │
                                      ▼
                        [Curriculum & Sectioning]
                   (RegistrarController / Scheduler)
                                      │
                                      ▼
                           [Tuition Assessment]
                    (FeeController / FinanceController)
                                      │
                                      ▼
                           [Cashiering / Payment]
                            (FinanceController)
                                      │
                                      ▼
                         [Enrollment Finalization]
                           (EnrollmentService)
```

### Module Breakdown
1. **Applicant Portal (`app/Controllers/ApplicantController.php`):**
   - Renders 5-stage milestone stepper: Registration -> Requirement Submission -> Medical Review -> Admissions Review -> Official Enrollment.
   - Manages personal profile and emergency contact details.
2. **Document Management (`app/Controllers/DocumentController.php`):**
   - Uploads student credentials into `app/uploads/documents/`.
   - Validates file sizes (<= 5MB) and inspects binary content using `finfo` against strict MIME types (`pdf`, `jpg`, `png`).
3. **Admissions Management (`app/Controllers/Admin/Admissions/AdmissionsController.php`):**
   - Manages queue at `/sia/admin/admissions/review.php`.
   - Performs application state updates (`pending` -> `under_review` -> `approved` / `rejected`).
4. **Health Services (`app/Controllers/Admin/Clinic/ClinicController.php`):**
   - Evaluates health questionnaires in `health_records`.
   - Grants medical clearance (`verified`, `correction_required`, `rejected`). Guarded by `requirePermission('medical.review')`.
5. **Curriculum Management (`app/Controllers/Admin/Registrar/`):**
   - `SubjectController.php`: Master course catalog with unit definitions and deletion lock safeguards.
   - `CollegeController.php`: College curriculum roadmaps (Year Levels, Semesters).
   - `ShsController.php`: Senior High School strands (STEM, ABM, HUMSS, TVL-ICT).
6. **Section & Schedule Management (`app/Controllers/Admin/Scheduler/SchedulerController.php`):**
   - Defines class sections (`college_sections`, `shs_sections`).
   - Assigns schedules (`day`, `start_time`, `end_time`, `room`, `instructor`, `delivery_mode`).
   - Enforces room conflict detection.
7. **Cashier & Finance (`app/Controllers/Admin/Finance/`):**
   - `FeeController.php`: Configures fixed fee templates.
   - `FinanceController.php`: Calculates tuition balances, processes payments, issues official receipts.
8. **Scholarships (`app/Controllers/Admin/Scholarship/ScholarshipController.php`):**
   - Configures academic/athletic grants.
   - Evaluates applicant eligibility and updates assessment discounts.

---

## 7. LMS System Architecture

The LMS manages digital academic delivery once a student is officially enrolled:

```text
[Enrolled Student] ──► [/sia/lms/student/dashboard.php] ──► [LmsService::getStudentCourses()]
                                                                    │
                             ┌──────────────────────────────────────┴──────────────────────────────────────┐
                             │                                                                             │
                             ▼                                                                             ▼
                [CollegeEnrollmentRepository]                                                  [ShsEnrollmentRepository]
                             │                                                                             │
                             └──────────────────────────────────────┬──────────────────────────────────────┘
                                                                    ▼
                                                     [lms_courses Entity Binding]
                                                                    │
                    ┌───────────────────┬───────────────────────────┼───────────────────────────┬───────────────────┐
                    │                   │                           │                           │                   │
                    ▼                   ▼                           ▼                           ▼                   ▼
              [lms_modules]     [lms_materials]             [lms_assignments]             [lms_quizzes]      [lms_grade_items]
                    │                   │                           │                           │                   │
                    ▼                   ▼                           ▼                           ▼                   ▼
             Module Overview     Secure Download             Student Submissions         Timed Attempts       1.00–5.00 GPA
```

### Module Breakdown
1. **Course Discovery & Authorization (`app/Services/LmsService.php`):**
   - Reads student enrollment dynamically across `college_enrollments` and `shs_enrollments`.
   - Validates student access via `isStudentAuthorizedForCourse($userId, $lmsCourseId)`.
   - Validates faculty course access via `isFacultyAuthorizedForCourse($facultyUserId, $lmsCourseId)`.
2. **Modules & Course Materials (`FacultyController.php`, `DownloadController.php`):**
   - Organizes resources into course units (`lms_modules`).
   - Streams files via `DownloadController` preventing path traversal.
3. **Assignments Subsystem (`StudentAssignmentController.php`, `FacultyAssignmentController.php`):**
   - File uploads placed in `app/uploads/lms/submissions/`.
   - Faculty grading interface with point inputs and written feedback.
4. **Quiz Subsystem (`StudentQuizController.php`, `FacultyQuizController.php`, `LmsQuizService.php`):**
   - Supports Multiple Choice, True/False, and Short Answer formats.
   - Evaluates timed attempts and computes scores automatically.
5. **Gradebook Subsystem (`StudentGradebookController.php`, `FacultyGradebookController.php`, `LmsGradebookService.php`):**
   - Aggregates assignments, quizzes, exams, and attendance.
   - Calculates weighted grades and maps them to the Philippine collegiate grading scale.
6. **Attendance Subsystem (`StudentAttendanceController.php`, `FacultyAttendanceController.php`, `LmsAttendanceService.php`):**
   - Faculty initiate date-stamped sessions and record roll calls.
   - Students track personal attendance records.
7. **Calendar & Announcements (`StudentCalendarController.php`, `LmsCalendarService.php`, `LmsAnnouncementService.php`):**
   - Aggregates timetables, assignment deadlines, and quiz schedules into a unified JSON calendar feed.

---

## 8. Shared Infrastructure & Dependencies

### 8.1 Shared Components Between Enrollment and LMS
1. **User Identity:** The `users` table serves as the single source of identity for all actors.
2. **Course Catalog:** The `subjects` table defines subject codes, descriptive titles, and unit counts used by both the Registrar curricula and LMS course headers.
3. **Sections:** `college_sections` and `shs_sections` define student cohorts referenced by both Enrollment and LMS.
4. **Database Connection:** All controllers share `App\Core\Database::getConnection()`.
5. **Session & Security Infrastructure:** All routes share `SessionSecurityMiddleware` and `CsrfMiddleware`.

### 8.2 External Vendor Dependencies (`composer.json`)
- `phpmailer/phpmailer: ^7.1`: Used by `App\Services\EnrollmentService` and `App\Controllers\AuthController` for SMTP dispatch of OTP codes and student credentials.

---

### Phase 1 Architectural Summary
The TTU platform possesses a fully realized, cohesive Hybrid MVC foundation. Requests follow a predictable flow from front-controller routing through middleware into domain-specific fat controllers and services. All major files, methods, database tables, and route groups have been verified against the physical filesystem and codebase.
