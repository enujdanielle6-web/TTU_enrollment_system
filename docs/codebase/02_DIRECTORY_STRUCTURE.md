# 02. DIRECTORY STRUCTURE

```text
c:\xampp\htdocs\sia\
├── .htaccess                       # Apache URL rewrite rules & directory protection
├── config/
│   ├── app.php                     # App settings & timezone
│   └── database.php                # MariaDB PDO host, port, dbname, credentials
├── database/
│   ├── schema.sql                  # Canonical 42-table schema (Single Source of Truth)
│   ├── seed.sql                    # Initial seed data
│   └── migrations/                 # Feature-specific SQL migrations
├── docs/
│   ├── INDEX.md                    # Documentation index
│   ├── obsidian/                   # Obsidian vault documentation
│   └── codebase/                   # This technical knowledge base
├── public/
│   ├── index.php                   # Front Controller
│   ├── css/                        # CSS stylesheets
│   └── js/
│       ├── spa-router.js           # AJAX SPA client router
│       └── app.js                  # Frontend scripts
├── app/
│   ├── Core/                       # Router, Request, Response, Database, BaseController, functions.php
│   ├── Middleware/                 # SessionSecurity, Csrf, Auth, Role middleware
│   ├── Models/                     # User, Application, StudentAssessment, etc.
│   ├── Repositories/               # College & SHS enrollment repositories
│   ├── Services/                   # Assessment, Enrollment, StudentNumber, LMS domain services
│   ├── Routes/
│   │   └── web.php                 # Master routing definition
│   ├── Controllers/
│   │   ├── AuthController.php      # Main auth portal
│   │   ├── ApplicantController.php # Applicant dashboard
│   │   ├── DocumentController.php  # Document upload/management
│   │   ├── EnrollController.php    # Public enrollment forms
│   │   ├── HealthController.php    # Medical history intake
│   │   ├── HomeController.php      # Public landing pages
│   │   ├── Admin/                  # Admissions, Clinic, Finance, Registrar, Scheduler, Scholarship, System
│   │   └── Lms/                    # Student, Faculty, Quizzes, Assignments, Gradebook, Attendance
│   └── Views/                      # PHP presentation templates (HTML/Bootstrap 5)
```
