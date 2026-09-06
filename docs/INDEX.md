# Triple T University (TTU) Documentation Master Index

Welcome to the centralized documentation index and developer orientation guide for the **Triple T University (TTU) Enrollment System & Learning Management System (LMS)**.

This index serves as the single source of truth for repository orientation, subsystem mapping, file traceability, and feature-to-code navigation.

---

## 🧭 Developer Fast Navigator: "Where Do I Go If I Want To...?"

| Developer Question / Task | Primary Documentation | Key Code Files to Inspect | Related Database Tables |
|---|---|---|---|
| **Modify applicant registration or OTP email verification** | `[[Authentication & Email Verification]]`, `[[ADR-006 Deferred Account Creation via OTP]]` | `app/Controllers/AuthController.php`<br>`app/Views/auth/register.php`<br>`app/Views/auth/verify_email.php` | `users` |
| **Change the online enrollment application form or step wizard** | `[[Applicant Portal]]`, `[[04 - Applicant Portal Relationship Map]]` | `app/Controllers/EnrollController.php`<br>`app/Views/applicant/enroll.php`<br>`app/Views/applicant/status.php` | `applications`<br>`application_subject_requests`<br>`users` |
| **Inspect document upload rules or review feedback** | `[[Document Submission Preference Workflow]]` | `app/Controllers/DocumentController.php`<br>`app/Views/applicant/documents.php` | `application_documents`<br>`applications` |
| **Update clinic medical clearance logic or gates** | `[[Clinic]]`, `[[Health Submission & Clearance Workflow]]` | `app/Controllers/Admin/Clinic/ClinicController.php`<br>`app/Controllers/HealthController.php` | `health_records`<br>`applications` |
| **Modify Admissions approval or section assignment** | `[[Admissions]]`, `[[05 - Admissions Admin Relationship Map]]` | `app/Controllers/Admin/Admissions/AdmissionsController.php`<br>`app/Views/admin/admissions/detail.php` | `applications`<br>`college_sections`<br>`student_assessments` |
| **Modify student tuition assessment math or fee breakdown** | `[[Payment & Assessment Workflow]]`, `[[ADR-009 Financial Immutability and Assessment Snapshots]]` | `app/Services/AssessmentService.php`<br>`app/Controllers/Admin/Finance/FeeController.php` | `student_assessments`<br>`assessment_items`<br>`fee_templates` |
| **Update Cashier payment recording, receipts, or proof approval** | `[[Finance]]`, `[[09 - Finance & Cashier Relationship Map]]` | `app/Controllers/Admin/Finance/FinanceController.php`<br>`app/Views/admin/finance/cashier_payments.php`<br>`app/Views/admin/finance/receipt.php` | `payment_records`<br>`student_assessments`<br>`receipt_sequences` |
| **Modify final enrollment matriculation or student number allocation** | `[[Registrar]]`, `[[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]`, `[[ADR-010 Domain Service Layer Extraction and Atomic Sequences]]` | `app/Controllers/Admin/Registrar/RegistrarController.php`<br>`app/Services/EnrollmentService.php`<br>`app/Services/StudentNumberService.php` | `applications`<br>`users`<br>`college_enrollments`<br>`student_number_sequences` |
| **Work on College or SHS Curricula & Subject Catalog** | `[[Curriculum Architecture]]`, `[[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]` | `app/Controllers/Admin/Registrar/CollegeController.php`<br>`app/Controllers/Admin/Registrar/ShsController.php`<br>`app/Controllers/Admin/Registrar/SubjectController.php` | `subjects`<br>`college_curricula`<br>`shs_curricula` |
| **Build or adjust class schedules, sections, and timetables** | `[[Scheduler]]`, `[[08 - Scheduler Admin Relationship Map]]` | `app/Controllers/Admin/Scheduler/SchedulerController.php`<br>`app/Views/admin/scheduler/schedule_builder.php` | `college_sections`<br>`shs_sections`<br>`section_subjects` |
| **Manage scholarships and tuition discount awards** | `[[Scholarship]]`, `[[10 - Scholarship Admin Relationship Map]]` | `app/Controllers/Admin/Scholarship/ScholarshipController.php` | `scholarships`<br>`scholarship_applications`<br>`scholarship_recipients` |
| **Modify system users, RBAC roles, audit logs, or settings** | `[[System Administration]]`, `[[11 - System Admin & Reports Relationship Map]]` | `app/Controllers/Admin/System/SystemController.php`<br>`app/Views/admin/system/users.php` | `users`<br>`activity_logs`<br>`system_settings` |
| **Work on the LMS (Courses, Modules, Assignments, Quizzes, Grades)** | `[[LMS]]`, `[[ADR-003 Hybrid SPA Navigation Design]]`, `[[12 - LMS Student Portal Relationship Map]]`, `[[13 - LMS Faculty Portal Relationship Map]]` | `app/Controllers/Lms/*`<br>`app/Services/Lms*`<br>`app/Views/lms/*` | `lms_courses`<br>`lms_modules`<br>`lms_assignments`<br>`lms_quizzes` |
| **Inspect database table definitions and foreign keys** | `[[Data Dictionary]]`, `[[Entity Relationship Architecture]]` | `database/schema.sql`<br>`schema_dump.sql` | All 45 tables/views |

---

## 📚 Complete Documentation Vault Organization (`docs/obsidian/`)

```text
docs/obsidian/
├── 00 - Home/                      # Primary Entry Points
│   └── [[TTU Enrollment System Home]]
├── 01 - Architecture/              # System Design & Boundaries
│   ├── [[System Architecture]]
│   ├── [[System Architecture Reconnaissance]]
│   └── [[MVC Strangler Fig Migration]]
├── 02 - Modules/                   # Subsystem Functional Overviews
│   ├── [[Module Index]]
│   ├── [[Applicant Portal]]
│   ├── [[Admissions]]
│   ├── [[Clinic]]
│   ├── [[Registrar]]
│   ├── [[Scheduler]]
│   ├── [[Finance]]
│   ├── [[Scholarship]]
│   ├── [[System Administration]]
│   ├── [[LMS]]
│   ├── [[LMS_Database_Architecture]]
│   ├── [[LMS_Phase_2_Foundation]]
│   ├── [[LMS Profile and Messages UI]]
│   └── [[Landing Page & Program Card Customization]]
├── 03 - Workflows/                 # End-to-End Business State Machines
│   ├── [[Workflow Index]]
│   ├── [[Student Lifecycle Workflow]]
│   ├── [[Applicant Registration Workflow]]
│   ├── [[Document Submission Preference Workflow]]
│   ├── [[Health Submission & Clearance Workflow]]
│   └── [[Payment & Assessment Workflow]]
├── 04 - Database/                  # Database Design & Schemas
│   ├── [[Database Overview]]
│   ├── [[Data Dictionary]]
│   ├── [[Entity Relationship Architecture]]
│   ├── [[Users Table]]
│   ├── [[Applications Table]]
│   └── [[Application Documents Table]]
├── 05 - Curriculum/                # Academic Rules & Catalog
│   ├── [[Curriculum Architecture]]
│   ├── [[National Service Training Program (NSTP) Architecture]]
│   └── [[Subject Catalog Immutability Architecture]]
├── 06 - Business Rules/            # Statutory & Institutional Policies
│   └── [[Business Rules]]
├── 07 - Security/                  # Defenses & Authentication
│   ├── [[Security Overview]]
│   ├── [[Authentication & Email Verification]]
│   └── [[Email & Notification System]]
├── 08 - API & AJAX/                # Internal JSON Endpoints
│   └── [[API Documentation]]
├── 09 - Testing/                   # Quality Assurance & Manual Testing
│   └── [[Testing Strategy]]
├── 10 - Bugs & Issues/             # Resolution History & Technical Debt
│   ├── [[Known Issues]]
│   └── [[LMS Navigation and Render Bugs Fixed]]
├── 11 - Development Guide/         # Developer Onboarding & Environment
│   ├── [[Development Guide]]
│   ├── [[AI Development Context]]
│   └── [[Project Structure & Code Map]]
├── 12 - Coding Standards/          # PHP, CSS, & MVC Rules
│   └── [[Coding Standards]]
├── 13 - Reports/                   # Administrative Analytics & CSVs
│   └── [[Reports Overview]]
├── 14 - Architecture Decisions/    # Architecture Decision Records (ADRs)
│   ├── [[ADR Index]]
│   ├── [[ADR-001 The Application as Term Concept]]
│   ├── [[ADR-002 Strangler Fig Migration]]
│   ├── [[ADR-003 Hybrid SPA Navigation Design]]
│   ├── [[ADR-004 Hybrid Navigation Adversarial Audit]]
│   ├── [[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]
│   ├── [[ADR-006 Deferred Account Creation via OTP]]
│   ├── [[ADR-007 NSTP Modular Curriculum Integration]]
│   ├── [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]
│   ├── [[ADR-009 Financial Immutability and Assessment Snapshots]]
│   └── [[ADR-010 Domain Service Layer Extraction and Atomic Sequences]]
├── 15 - Operations/                # Server Setup & Runbooks
│   ├── [[Installation & Setup Guide]]
│   └── [[Troubleshooting Runbook]]
├── 16 - Page Relationships/        # Page-to-Code Traceability Matrices
│   ├── [[00 - Master Relationship Index & Matrix]]
│   ├── [[01 - Shared Dependencies & Impact Analysis]]
│   ├── [[02 - Cross-Module Data Flow & Table Sharing]]
│   ├── [[03 - Auth & Public Pages Relationship Map]]
│   ├── [[04 - Applicant Portal Relationship Map]]
│   ├── [[05 - Admissions Admin Relationship Map]]
│   ├── [[06 - Clinic Admin Relationship Map]]
│   ├── [[07 - Registrar Admin Relationship Map]]
│   ├── [[08 - Scheduler Admin Relationship Map]]
│   ├── [[09 - Finance & Cashier Relationship Map]]
│   ├── [[10 - Scholarship Admin Relationship Map]]
│   ├── [[11 - System Admin & Reports Relationship Map]]
│   ├── [[12 - LMS Student Portal Relationship Map]]
│   └── [[13 - LMS Faculty Portal Relationship Map]]
└── 17 - File Reference/            # Comprehensive File-Level Traceability Standard
    ├── [[00 - File Reference Index]]
    ├── [[01 - Controllers Reference]]
    ├── [[02 - Models Reference]]
    ├── [[03 - Services Reference]]
    ├── [[04 - Core & Middleware Reference]]
    └── [[05 - Views Catalog & Template Mapping]]
```

---

## 🔍 System Architecture Summary
- **Architecture Pattern:** Hybrid MVC with "Fat Controllers" handling request validation, routing, business logic, transaction boundaries, and direct PDO queries, with cross-cutting domain logic extracted into stateless domain services (`app/Services/`).
- **Persistence:** Relational MariaDB database (`sia`), containing **45 tables and views** centered on the *"Application as Term"* architectural concept.
- **Routing:** Centralized front-controller router (`public/index.php` $\rightarrow$ `app/Routes/web.php`) with middleware interceptors (`AuthMiddleware`, `CsrfMiddleware`, `RoleMiddleware`, `SessionSecurityMiddleware`).
- **Frontend:** Vanilla PHP server-side templates with Bootstrap 5, Vanilla JavaScript DOM debouncing, SweetAlert2 notifications, and Chart.js reporting widgets.
