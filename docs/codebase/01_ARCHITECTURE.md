# 01. ARCHITECTURE

## Architectural Pattern: Hybrid MVC
The system follows a **Hybrid MVC** pattern governed by:
- **Fat Controllers:** Handle routing inputs, form validation, business logic rules, raw PDO SQL execution, and view orchestration.
- **Thin Models:** Act as data containers and basic Active Record wrappers (`User`, `Application`, `ApplicationDocument`, `HealthRecord`, `StudentAssessment`). Models do not abstract or hide SQL logic from controllers.
- **Domain Services:** Multi-table transactions and business calculations (`AssessmentService`, `EnrollmentService`, `StudentNumberService`, `LmsService`, `LmsQuizService`, `LmsGradebookService`, `LmsCalendarService`).
- **Repositories:** Abstract enrollment data queries (`EnrollmentRepositoryInterface`, `CollegeEnrollmentRepository`, `ShsEnrollmentRepository`).

## Request Lifecycle
```text
Client Request
   │
   ▼
[Apache .htaccess] (Rewrites non-static files to public/index.php)
   │
   ▼
[public/index.php] (Front Controller)
   │ 1. Bootstraps autoloader & session
   │ 2. Generates CSRF token in $_SESSION['csrf_token']
   │ 3. Instantiates App\Core\Request & App\Core\Response
   │ 4. Instantiates App\Core\Router & loads app/Routes/web.php
   │ 5. Dispatches Request through Middleware
   ▼
[Middleware Pipeline]
   │ ├── SessionSecurityMiddleware (Session hijacking, IP & timeout checks)
   │ ├── CsrfMiddleware (Validates csrf_token on POST/PUT/DELETE)
   │ ├── AuthMiddleware (Enforces $_SESSION['logged_in'])
   │ └── RoleMiddleware (Enforces $_SESSION['user_role'])
   ▼
[Controller Action] (e.g. App\Controllers\Admin\Admissions\AdmissionsController)
   │ 1. Validates input
   │ 2. Enforces requirePermission('permission.name')
   │ 3. Interacts with PDO via App\Core\Database::getConnection()
   │ 4. Calls Domain Services where necessary
   │ 5. Logs audit entry via logActivity()
   ▼
[BaseController::render() / Response::json()]
```
