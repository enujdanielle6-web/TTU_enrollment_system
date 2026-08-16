# TTU Enrollment System — Architectural Reconnaissance Report

## 1. Executive Summary
The Triple T University (TTU) Enrollment System is a monolithic, highly integrated web application managing the entire academic lifecycle of a student. It governs everything from initial admission applications, cashier assessments, registrar curriculum management, and section assignments, down to a bare-bones Student/Faculty Learning Management System (LMS). The system operates on a hybrid Vanilla PHP architecture—slowly migrating from legacy procedural patterns to a unified Model-View-Controller (MVC) paradigm. The database acts as the strict source of truth, heavily normalizing academic entities while sharing core user profiles across administrative, applicant, and LMS boundaries.

## 2. Repository Structure
The repository is structured to separate public assets from secure application logic.

```text
TTU Enrollment System (sia/)
├── app/                      # Secure Application Core
│   ├── Config/               # Database and environment configurations
│   ├── Controllers/          # MVC Controllers (Admin, Lms, Auth, Applicant)
│   ├── Core/                 # Framework engine (Router, Request, Response, BaseModel)
│   ├── Middleware/           # Route interceptors (Auth, Role checks)
│   ├── Models/               # Data access layer
│   ├── Routes/               # Centralized routing definitions (web.php)
│   ├── Views/                # Presentation layer (PHP/HTML)
│   └── uploads/              # Secure file storage (payments, documents)
├── css/ & js/                # Legacy or global frontend assets
├── docs/                     # Project documentation (Obsidian markdown)
├── public/                   # Web Root
│   ├── index.php             # Front Controller entry point
│   └── vendor/               # Frontend libraries (Bootstrap, ChartJS, SweetAlert)
└── scripts/                  # Backend utility scripts (migrations, schema tools)
```

## 3. Current Architecture
The system is currently a **Hybrid MVC Monolith**.
*   **The MVC Core**: New features (Admin modules, LMS, Applicant dashboard) follow a strict MVC pattern orchestrated by a custom built `App\Core\Router`.
*   **Legacy Procedural**: Older scripts and endpoints still exist but are being actively strangled out.
*   **Shared Infrastructure**: The database is fully shared. There are no microservices or API barriers between modules. A change in the Registrar module immediately affects the LMS module.

## 4. Request / Routing Flow
1.  **Entry**: All traffic is directed to `public/index.php`.
2.  **Routing**: The `App\Core\Router` evaluates the URI against definitions in `app/Routes/web.php`.
3.  **Middleware**: Pre-flight checks are executed. For example, `RoleMiddleware` ensures a user accessing `/admin/*` has the required enum role (`admin`, `superadmin`, `cashier`, etc.).
4.  **Controller**: The resolved controller (e.g., `AdmissionsController`) executes business logic, fetches data via Models (or raw PDO), and calls `$this->render()`.
5.  **View**: The View engine extracts variables and injects the specific template into shared layout files (`layout_header.php`, `layout_footer.php`).

## 5. Authentication Architecture
Authentication is session-based and centralized.
*   **Users Table**: All human actors exist in the `users` table.
*   **Roles**: Defined by an `enum`: `'applicant','admin','superadmin','admissions','scholarship','cashier','clinic','faculty','scheduler'`.
*   **The "Student" Concept**: Crucially, there is NO `student` role in the database. A student is simply an `applicant` whose application `status` has transitioned to `'enrolled'`.
*   **Session State**: Upon successful login, the `id`, `role`, and `student_number` are written to `$_SESSION`.
*   **Authorization**: Handled by Middleware at the route level, and by Controller logic at the data level.

## 6. Database Architecture
The database (`sia`) is highly normalized and relational.

*   `users`: The identity root.
*   `applications`: The lifecycle root of a student. Tracks their transition from pending → approved → enrolled.
*   `subjects`: The catalog of all teachable classes.
*   `college_programs` / `college_curricula` / `college_curriculum_subjects`: The blueprint for what a student must take.
*   `college_sections` / `shs_sections`: The physical/virtual grouping of students for a specific subject, tied to a faculty `adviser`.
*   `college_enrollments` / `shs_enrollments`: The definitive bridge table that links an `application` to a `subject`.
*   `student_assessments` / `payment_records`: The financial ledger.
*   `lms_modules` / `lms_assignments`: The academic content bound to a `subject_id`.

## 7. Academic Domain Model

```text
User (Identity) 
  ↳ Application (Lifecycle)
      ↳ College Enrollment (The Act of Taking a Class)
          ↳ Subject (The Course Content)
          ↳ College Section (The Class Schedule/Instructor)
```
*   **Student Identity**: The `student_number` in the `users` table.
*   **Enrollment Status**: The `status` enum in the `applications` table.
*   **Official Subjects**: The rows in `college_enrollments` matching the `application_id`.

## 8. Module Architecture

*   **Admissions**: Processes new `applications` and documents.
*   **Registrar**: Manages `curricula`, `subjects`, and transitions applications to `enrolled`.
*   **Scheduler**: Groups subjects into `sections` and assigns faculty and timeslots.
*   **Cashier**: Manages `student_assessments` and verifies `payment_records`.
*   **Scholarship**: Applies discounts via `student_scholarships`.
*   **Faculty**: Logs in via LMS to upload modules to their assigned `subject_id`s.
*   **LMS**: The student-facing portal to view enrolled subjects and materials.

## 9. LMS Architecture
**IMPLEMENTED**:
*   Secure Login (Faculty and Student).
*   Student Dashboard (Displays enrolled courses).
*   Faculty Dashboard (Displays assigned courses).
*   Course Viewer (Displays `lms_modules` uploaded by faculty).

**NOT PRESENT / PENDING**:
*   Assignments submission (`lms_submissions` table exists, UI does not).
*   Quizzes / Exams (No tables exist).
*   Gradebook (No tables exist).
*   Forums / Messages (No tables exist).

## 10. LMS ↔ Enrollment Integration
The LMS is completely parasitic on the Core Enrollment System. It does not duplicate enrollment records.
*   **Eligibility**: A student can only access the LMS if `applications.status = 'enrolled'`.
*   **Course Visibility**: The LMS strictly queries the `college_enrollments` table. If the Registrar drops a student from a subject, the course immediately vanishes from the LMS.
*   **Instructor Mapping**: The LMS fetches the instructor by looking at the `college_sections.adviser` assigned to the student's enrollment.

## 11. College vs SHS Architecture
The system employs parallel database tables for College and Senior High School.
*   **College**: `college_programs`, `college_curricula`, `college_sections`, `college_enrollments`.
*   **SHS**: `shs_strands`, `shs_curricula`, `shs_sections`, `shs_enrollments`.
*   **Risk**: The LMS currently ONLY queries `college_enrollments`. An SHS student logging into the LMS will see zero courses.

## 12. Security Audit
*   **Severity: HIGH | Location: LMS Routing | Problem:** IDOR (Insecure Direct Object Reference). While the LMS checks if a user is enrolled in a subject, faculty file uploads (`lms_modules.file_path`) are stored on the public disk. If a student guesses the file URL, they can bypass the LMS entirely and download files for courses they aren't enrolled in.
*   **Severity: MEDIUM | Location: Legacy Scripts | Problem:** Procedural scripts in `/scripts` (like `test_subject_add.php`) bypass authentication middleware entirely and can be executed via CLI or directly if exposed.
*   **Severity: LOW | Location: Database Passwords | Problem:** Password hashes are secure (Bcrypt), but PowerShell execution bugs have historically corrupted database hashes when modified directly.

## 13. Architecture Problems
*   **God Controllers**: Some admin controllers are handling routing, validation, business logic, and view rendering simultaneously without delegating to service classes.
*   **Hardcoded Roles**: The system relies heavily on string enums (`'applicant'`, `'enrolled'`). Any change to a status string requires sweeping code updates.

## 14. Database Problems
*   **Missing FK Constraints**: In some legacy tables, foreign keys are implicit rather than enforced at the InnoDB level.
*   **Duplicated State**: A student's enrollment status is tied to `applications.status`. If a student graduates and returns for a second degree, the schema struggles to differentiate their past application from their new application.

## 15. Technical Debt
*   **Hybrid Navigation**: The transition to a SPA-like router (`spa-router.js`) while maintaining standard PHP forms creates disjointed browser history and caching bugs.
*   **Raw PDO Queries**: Controllers frequently write raw `SELECT * FROM... JOIN...` instead of utilizing the `BaseModel` ORM-like structure, leading to duplicated SQL strings across the app.

## 16. Sources of Truth
*   **Student Identity**: `users.student_number`
*   **Enrollment Status**: `applications.status`
*   **Official Schedule**: `college_sections` / `shs_sections`
*   **Official Finances**: `student_assessments`
*   **LMS Content**: `lms_modules`

## 17. Dependency Map
```text
Users Module
  ↳ Admissions
      ↳ Cashier (Assessment generation)
      ↳ Registrar (Evaluation & Subject Assignment)
          ↳ Scheduler (Section grouping)
              ↳ LMS (Consumes resulting enrollments & sections)
```

## 18. Recommended Target Architecture
**CURRENT**: Hybrid MVC Monolith with heavy Controller logic.
**RECOMMENDED**: Service-Oriented MVC Monolith.
*   Keep Vanilla PHP. Do not rewrite in Laravel yet.
*   Extract business logic from Controllers into Domain Services (e.g., `EnrollmentService`, `LmsService`).
*   Standardize the `BaseModel` to prevent raw SQL injection in controllers.
*   Abstract the "Academic Target" so that College and SHS can share LMS logic via Interfaces, rather than hardcoding `JOIN college_enrollments`.

## 19. LMS Expansion Recommendations
Before expanding the LMS, the following architectural assumptions must be resolved:
*   **SHS Integration**: The `StudentController` must be rewritten to check `applications.academic_level`. If SHS, query `shs_enrollments`. If College, query `college_enrollments`.
*   **Assignments & Quizzes**: Must be tied to `subject_id` rather than a specific `college_section_id`, allowing materials to be reused across different class sections.
*   **Gradebook**: The LMS Gradebook must NEVER be the source of truth for final grades. Final grades must be pushed to a dedicated Registrar table (`student_academic_records`).

## 20. Implementation Priority
1.  **CRITICAL**: Implement SHS routing in the LMS. Currently, SHS students are completely locked out of LMS functionality.
2.  **HIGH**: Extract raw PDO queries in LMS Controllers into reusable Models/Services to prevent code duplication when adding Assignments and Quizzes.
3.  **MEDIUM**: Build out the `lms_assignments` and `lms_submissions` UI. The tables exist; the views do not.
4.  **LOW**: Forums and Announcements. Rely on email or external tools until the core academic LMS is stable.

## 21. Questions / Unknowns
*   **UNKNOWN**: How are multi-semester applications handled? Does a student create a new row in `applications` every semester, or do they just get new rows in `college_enrollments`? The schema implies `applications` holds the `semester` state, which breaks if an application represents a 4-year lifecycle.
*   **UNKNOWN**: How do Faculty members get assigned to subjects? `college_sections.adviser` is a `varchar(255)` string, NOT a foreign key to `users.id`. This makes faculty-to-section mapping in the LMS highly fragile (relies on string matching).
