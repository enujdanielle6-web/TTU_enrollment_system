# Project Structure & Code Map

This document provides an exhaustive code map of the TTU Enrollment System and LMS repository, detailing the purpose of every key directory, architectural constraints, dependencies, and file placement rules.

---

## 1. Directory Tree Overview

```text
TTU Enrollment System (c:\xampp\htdocs\sia\)
├── .agents/                        # AI agent configurations and development skills
├── .env                            # Environment variables (Database & Google SMTP credentials)
├── .env.example                    # Template environment file for setup
├── .htaccess                       # Apache front firewall & mod_rewrite rules
├── DEVELOPER_HANDOFF.md            # Recent feature releases & architecture changes
├── README.md                       # Master orientation and quickstart guide
├── app/                            # SECURE APPLICATION LOGIC (PHP)
│   ├── Config/                     # Framework configuration classes
│   ├── Controllers/                # Hybrid MVC Fat Controllers
│   │   ├── Admin/                  # Administrative controllers
│   │   │   ├── Admissions/         # AdmissionsController.php
│   │   │   ├── Clinic/             # ClinicController.php
│   │   │   ├── Finance/            # FinanceController.php, FeeController.php
│   │   │   ├── Registrar/          # RegistrarController, CollegeController, ShsController, SubjectController
│   │   │   ├── Scheduler/          # SchedulerController.php
│   │   │   ├── Scholarship/        # ScholarshipController.php
│   │   │   └── System/             # SystemController, ReportController, DashboardController
│   │   │   └── LmsAdminController.php
│   │   ├── Api/                    # JSON AJAX Endpoints (AdminApiController, ApplicantApiController)
│   │   ├── Lms/                    # LMS Controllers (Student, Faculty, Quiz, Assignment, Gradebook, Download)
│   │   ├── ApplicantController.php # Core applicant portal controller
│   │   ├── AuthController.php      # Main auth, registration & 6-digit OTP verification
│   │   ├── DocumentController.php  # Requirement uploads & workflow
│   │   ├── EnrollController.php    # Self-enrollment and schedule selection
│   │   ├── HealthController.php    # Applicant health info submission
│   │   └── HomeController.php      # Public landing pages
│   ├── Core/                       # Custom framework engine (Router, Request, Response, BaseModel)
│   ├── Helpers/                    # Global helper functions (functions.php)
│   ├── Middleware/                 # Interceptors (SessionSecurity, Csrf, Auth, Role, Test)
│   ├── Models/                     # Simple data containers (User, Application, HealthRecord)
│   ├── Repositories/               # Cross-tier enrollment queries (CollegeEnrollmentRepository, ShsEnrollmentRepository)
│   ├── Routes/                     # Central route definitions (web.php)
│   ├── Services/                   # Domain service layer (LmsService.php)
│   ├── Views/                      # PHP presentation templates
│   │   ├── admin/                  # Administrative blade-style views
│   │   ├── applicant/              # Applicant self-service portal views
│   │   ├── auth/                   # Login, register, verify_email views
│   │   ├── emails/                 # Branded HTML email templates
│   │   ├── lms/                    # Student & Faculty LMS portal views
│   │   └── components/             # Reusable UI partials (navbars, sidebars, alerts)
│   └── uploads/                    # Server-side uploads directory (documents, payments)
├── config/                         # Global database configuration (database.php)
├── css/                            # Global stylesheets and custom design tokens
├── database/                       # SQL schema and seed files
├── docs/                           # Master Obsidian Technical Documentation
├── images/                         # Static image assets (TTU_LOGO.png, ttu_campus.jpg)
├── js/                             # Global client-side scripts and utilities
├── public/                         # WEB ROOT DIRECTORY (HTTP Entry)
│   ├── index.php                   # Master Front Controller
│   └── images/                     # Public image symlinks
├── schema_dump.sql                 # Active, complete 41-table database dump
├── scripts/                        # Maintenance, test scripts, and CLI utilities
└── vendor/                         # Composer packages (PHPMailer)
```

---

## 2. Directory Placement Rules & Responsibilities

| Directory | What Belongs Here | Dependencies | What Should NOT Be Placed Here |
|---|---|---|---|
| `app/Controllers/` | Fat Controllers handling HTTP requests, input validation, business logic, PDO queries, and view rendering. | Depends on `Core`, `Repositories`, `Services`, `Database`. | Do not place raw HTML presentation markup or global script utilities. |
| `app/Repositories/`| Specialized query logic spanning multiple parallel schemas (e.g. `college_enrollments` vs `shs_enrollments`). | Depends on `Core\Database`. | Do not handle HTTP redirects or request validation. |
| `app/Services/` | Domain logic that aggregates data across multiple entities (e.g. cross-course LMS student deadlines and study streaks). | Depends on `Database`, `Repositories`. | Do not render views or access session superglobals directly. |
| `app/Views/` | Presentation templates using pure HTML and minimal PHP (`if`, `foreach`, `echo`). | Consumes data arrays passed by controllers. | Never execute direct database queries (`$pdo->query()`) inside views. |
| `app/Routes/` | Route declarations (`web.php`) defining URI path mappings and middleware assignments. | Depends on `Core\Router`. | Do not write business logic inside route closures. |
| `public/` | Public web root. Contains `index.php` front controller and static assets. | Direct entry point for Apache. | Never place secret configuration files, credentials, or controllers here. |
| `scripts/` | Standalone CLI utility scripts and test scripts. | Can load `Helpers/functions.php`. | Do not expose utility scripts to public HTTP requests. |

---
**Related:**
- [[System Architecture]]
- [[AI Development Context]]
- [[Coding Standards]]
