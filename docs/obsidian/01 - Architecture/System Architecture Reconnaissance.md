# TTU Enrollment System — Architectural Reconnaissance Report

## 1. Executive Summary
The Triple T University (TTU) Enrollment System is a monolithic, highly integrated web application managing the entire academic lifecycle of a student. It governs initial admission applications, cashier assessments, registrar curriculum management, section assignments, and a fully featured Student/Faculty Learning Management System (LMS). The system operates on a Hybrid Vanilla PHP architecture migrating from legacy procedural scripts to a unified Model-View-Controller (MVC) paradigm. The database acts as the single source of truth, heavily normalizing academic entities while sharing core user profiles across administrative, applicant, and LMS boundaries.

## 2. Repository Structure
The repository is structured to separate public assets from secure application logic:

```text
TTU Enrollment System (sia/)
├── .agents/                  # Agent skills and guidelines
├── .env                      # Environment configuration (Database, SMTP)
├── .htaccess                 # Apache rewrite engine and security firewall
├── DEVELOPER_HANDOFF.md      # Technical changelog and recent releases
├── README.md                 # Master orientation and quickstart guide
├── app/                      # Secure Application Core
│   ├── Config/               # Database and environment loader
│   ├── Controllers/          # MVC Controllers (Admin, Lms, Auth, Applicant, Api)
│   ├── Core/                 # Framework engine (Router, Request, Response, BaseModel)
│   ├── Helpers/              # Global helper functions (functions.php)
│   ├── Middleware/           # Route interceptors (SessionSecurity, Csrf, Auth, Role)
│   ├── Models/               # Data access models (User, Application, HealthRecord)
│   ├── Repositories/         # Cross-tier enrollment lookups (College & SHS)
│   ├── Routes/               # Centralized routing definitions (web.php)
│   ├── Services/             # Domain service layer (LmsService)
│   ├── Views/                # Presentation layer (admin, applicant, auth, lms, emails)
│   └── uploads/              # Storage for documents and payment proofs
├── config/                   # Global configuration (database.php)
├── css/ & js/                # Global styling and script assets
├── database/                 # SQL schemas and seeders
├── docs/                     # Comprehensive Obsidian technical documentation
├── images/                   # University logos, banners, campus hero photos
├── public/                   # Web Root Front Controller entry point
│   ├── index.php             # Master front controller
│   └── images/               # Web accessible image symlinks/assets
├── schema_dump.sql           # Active, complete 41-table database dump
├── scripts/                  # CLI testing and maintenance utilities
└── vendor/                   # Composer packages (PHPMailer)
```

## 3. Current Architecture: Hybrid MVC Monolith
* **The MVC Core**: Administrative modules, the Applicant portal, Authentication, and LMS follow an MVC pattern orchestrated by `App\Core\Router`.
* **Fat Controller Pattern**: Controllers encapsulate request routing, parameter validation, business rules, raw PDO SQL queries, and view rendering.
* **Shared Infrastructure**: The database is fully shared. A status change in Admissions or Registrar instantly propagates to Cashier and LMS.

## 4. Request / Routing Flow
1. **Entry**: All web traffic is routed through `public/index.php`.
2. **Environment**: `public/index.php` loads `.env` variables (`putenv`, `$_ENV`, `$_SERVER`).
3. **Routing**: `App\Core\Router` matches the URI against definitions in `app/Routes/web.php`.
4. **Middleware Pipeline**: Pre-flight security checks execute:
   - `SessionSecurityMiddleware`: Session hijacking/fixation defense.
   - `CsrfMiddleware`: CSRF token validation.
   - `AuthMiddleware`: Session authentication check.
   - `RoleMiddleware`: Role-based route permission gating (`applicant`, `admin`, `scheduler`, etc.).
5. **Controller Execution**: The controller executes business logic, queries MariaDB via PDO, and calls `$this->render()`.
6. **View Rendering**: View files extract data arrays and render the responsive HTML markup.

## 5. Authentication & Identity Architecture
* **Users Table**: All human actors exist in the `users` table.
* **Roles**: `'applicant'`, `'admin'`, `'superadmin'`, `'admissions'`, `'scholarship'`, `'cashier'`, `'clinic'`, `'faculty'`, `'scheduler'`.
* **The "Student" Concept**: There is no separate `students` table. A student is an `applicant` whose `applications.status` is `'enrolled'` and who has an assigned `users.student_number`.
* **Registration OTP Gating**: Newly registered applicants are assigned `email_verified = 0` and issued a 6-digit random code with 15-minute expiry. They cannot log in until verified.
* **LMS Authentication**: Students log in with their Student Number (`2026-000003`) and unified account password; Faculty log in with their assigned Employee ID.

## 6. Academic Domain & Database Architecture
* `users`: Identity root.
* `applications`: Lifecycle root of a student (pending → approved → enrolled).
* `subjects`: Universal catalog of teachable courses.
* `college_curricula` / `shs_curricula`: Versioned academic blueprints.
* `college_sections` / `shs_sections`: Scheduled classes assigned to faculty.
* `college_enrollments` / `shs_enrollments`: Official bridge linking an application to enrolled subjects.
* `health_records`: Clinic health information and clearance tracking.
* `fee_templates`, `student_assessments`, `payment_records`: Financial ledger.
* `lms_courses`, `lms_modules`, `lms_assignments`, `lms_quizzes`, `lms_submissions`: Academic learning system.

## 7. Dual Enrollment Engine: College + SHS
* **College Tier**: Uses `college_programs`, `college_curricula`, `college_sections`, `college_enrollments`.
* **SHS Tier**: Uses `shs_strands`, `shs_curricula`, `shs_sections`, `shs_enrollments`.
* **LMS Dual Support**: `CollegeEnrollmentRepository` and `ShsEnrollmentRepository` dynamically fetch enrolled courses for both College and SHS students, ensuring SHS students have full LMS course access.

## 8. Financial Engine: Tuition Rate per Unit
* Dynamic calculation replaces static totals: `Total Tuition = Total Enrolled Units × tuition_fee (rate)`.
* `fee_templates.is_per_unit = 1` triggers per-unit math across both College and SHS.
* Supports online bank proof of payment upload with administrative cashier verification.

## 9. Admissions & Automated Credential Issuance
* When Admissions finalizes enrollment (`applications.status = 'enrolled'`), the system automatically:
  1. Assigns an official Student Number (`YYYY-XXXXXX`).
  2. Generates institutional TTU email (`first.last@ttu.edu.ph`).
  3. Dispatches credentials email via `sendStudentCredentialsEmail()` with temporary LMS password.

## 10. Security & Quality Review
* **SQL Injection**: Prevented by parameterized PDO statements.
* **CSRF Defense**: Automatic CSRF token generation and validation on form POSTs.
* **Email Verification**: Enforces real email ownership prior to application creation.
* **LMS File Protection**: Downloads routed through secure controller endpoints (`/lms/download/material/{id}`).
