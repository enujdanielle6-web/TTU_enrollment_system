# Triple T University (TTU) Enrollment System Documentation

Welcome to the technical source of truth for the **Triple T University (TTU) Enrollment & Learning Management System**.

This repository documentation maps out how the codebase, database, authentication pipeline, and academic business rules interact to support the complete student lifecycle.

> [!NOTE]
> **Authoritative System Audit Completed (September 13, 2026)**: A full documentation-for-codebase reverse-engineering audit was performed, reconciling all 47 database tables, domain service layers, atomic sequences, and `.agents` skills. Review the [[DOCUMENTATION_AUDIT_REPORT_2026|Documentation Audit Report 2026]] for complete audit findings, coverage assessment, and maintenance standards.

---

## Master Documentation Index

### 1. Architecture & Security
- **[[System Architecture]]**: High-level overview of the backend, request lifecycle, and layer boundaries.
- **[[MVC Strangler Fig Migration]]**: Strategy for migrating legacy procedural scripts to the unified Hybrid MVC.
- **[[System Architecture Reconnaissance]]**: Deep-dive architectural report and dependency mapping.
- **[[Security Overview]]**: Middleware, authorization, firewall (`.htaccess`), and PDO SQL injection defenses.
- **[[Authentication & Email Verification]]**: 6-digit OTP registration gating, session security, and multi-portal login.
- **[[Email & Notification System]]**: PHPMailer, Google SMTP integration, verification and credentials emails.

### 2. Core Modules
- **[[Module Index]]**: Master catalog of all administrative, applicant, and academic modules.
- **[[Applicant Portal]]**: Online registration, document upload, health info submission, enrollment, and status tracking.
- **[[Admissions]]**: Application review, document verification, approval/rejection, and automated credential dispatch.
- **[[Clinic]]**: Health information evaluation, medical clearances, and applicant clearance gating.
- **[[Registrar]]**: Curriculum architecture, College programs, SHS strands, subjects, and student academic records.
- **[[Landing Page & Program Card Customization]]**: Public landing page courses card builder and customizer for College Programs and SHS Strands.
- **[[Scheduler]]**: Section management, timetable matrix, faculty advisers, and room assignments.
- **[[Finance]]**: Fee templates, dynamic tuition rate-per-unit assessments, cashier verification, and receipts.
- **[[Scholarship]]**: Financial aid programs, application evaluations, and discount deductions.
- **[[LMS]]**: Learning Management System for College & SHS students and faculty (courses, modules, assignments, quizzes, gradebook, attendance).

- **[[System Administration]]**: User management, granular RBAC permissions, audit trail logging, SQL database backup/restore, and global system configuration.
- **[[Reports Overview]]**: Institutional analytics, pipeline throughput, financial revenue summaries, and CSV data export.

### 3. Workflows
- **[[Workflow Index]]**: Directory of core business workflows.
- **[[Student Lifecycle Workflow]]**: End-to-end journey from applicant registration to enrollment and LMS access.
- **[[Applicant Registration Workflow]]**: Registration, 6-digit OTP email generation, and verification.
- **[[Document Submission Preference Workflow]]**: Online upload vs. on-campus physical submission selection.
- **[[Health Submission & Clearance Workflow]]**: Medical clearance submission and clinic review.
- **[[Payment & Assessment Workflow]]**: Assessment math, bank proof upload, and cashier confirmation.

### 4. Database & Curriculum
- **[[Database Overview]]**: The "Application as Term" concept and relational architecture.
- **[[Entity Relationship Architecture]]**: High-level and domain-level Mermaid ER diagrams, cardinality, and foreign key topology.
- **[[Data Dictionary]]**: Complete technical specifications for all 47 database tables and views.
- **[[Curriculum Architecture]]**: College programs vs. SHS strands, versioning, and curriculum immutability.
- **[[Users Table]]**: Identity root, roles enum, and OTP verification columns.
- **[[Applications Table]]**: Lifecycle anchor and student enrollment state.
- **[[Application Documents Table]]**: Requirement uploads and verification states.

### 5. API & Standards
- **[[API Documentation]]**: Complete reference for all internal JSON & HTML AJAX endpoints.
- **[[Business Rules]]**: Comprehensive catalog of confirmed and enforced business rules.
- **[[Coding Standards]]**: Hybrid MVC Fat Controller guidelines, input escaping, and security requirements.
- **[[Documentation Maintenance Standard]]**: Authoritative code-documentation synchronization protocol.
- **[[Testing Strategy]]**: Manual testing matrix, test scenarios, and verification plans.
- **[[Known Issues]]**: Documented bugs, runtime edge-cases, and technical debt log.

### 6. Development & Operations
- **[[Project Structure & Code Map]]**: Directory map, component placement rules, and file references.
- **[[AI Development Context]]**: Critical context, rules, and sensitive areas for AI coding assistants.
- **[[Development Guide]]**: Local environment setup, XAMPP, `.env` configuration, and debugging.
- **[[Installation & Setup Guide]]**: Step-by-step installation instructions.
- **[[Troubleshooting Runbook]]**: Common runtime error resolutions, SMTP debugging, and recovery.

### 7. Architectural Decision Records (ADRs)
- **[[ADR Index]]**: Architectural decision log index.
- **[[ADR-001 The Application as Term Concept]]**: Why there is no central `students` table.
- **[[ADR-002 Strangler Fig Migration]]**: Incremental MVC modernization strategy.
- **[[ADR-003 Hybrid SPA Navigation Design]]**: Dynamic HTML fragment swapping in the LMS.
- **[[ADR-004 Hybrid Navigation Adversarial Audit]]**: Security analysis of SPA navigation.
- **[[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]**: 3-state curriculum lifecycle and catalog historical integrity.
- **[[ADR-006 Deferred Account Creation via OTP]]**: Deferral of user record insertion until email OTP verification.
- **[[ADR-007 NSTP Modular Curriculum Integration]]**: RA 9163 statutory compliance and track mapping.
- **[[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]**: Separation of cashier verification from exclusive Registrar matriculation authority.
- **[[ADR-009 Financial Immutability and Assessment Snapshots]]**: Frozen line-item breakdowns and atomic receipt sequencing.
- **[[ADR-010 Domain Service Layer Extraction and Atomic Sequences]]**: Stateless domain service extraction and race-free sequence counters.

### 8. Page-to-Code Relationships & Dependency Maps
- **[[00 - Master Relationship Index & Matrix]]**: Master mapping of every page, controller, route, view, table, and AJAX handler.
- **[[01 - Shared Dependencies & Impact Analysis]]**: Blast radius analysis of shared components (`functions.php`, `Router.php`, `Middleware/*`, `database.php`).
- **[[02 - Cross-Module Data Flow & Table Sharing]]**: Data contracts and cross-department table sharing.
- **[[03 - Auth & Public Pages Relationship Map]]**: Trace for Login, Register, OTP verification, and Password Reset.
- **[[04 - Applicant Portal Relationship Map]]**: Trace for Dashboard, Application Form, Requirement Uploads, Health Info, Schedule Selection, and Assessment.
- **[[05 - Admissions Admin Relationship Map]]**: Trace for Intake Queue, Document Verification, Section Assignment, and Credential Dispatch.
- **[[06 - Clinic Admin Relationship Map]]**: Trace for Health Record Verification, Medical Conditions, and Clearance Gating.
- **[[07 - Registrar Admin Relationship Map]]**: Trace for Masterlist CSV Export, Server-Side Pagination, Subjects Catalog, College/SHS Curricula Builders, and Enrollment Finalization.
- **[[08 - Scheduler Admin Relationship Map]]**: Trace for Section Creation, Timetable Matrix, Room Assignments, and Conflict Detection.
- **[[09 - Finance & Cashier Relationship Map]]**: Trace for Dynamic Assessment Math, Payment Verification, OR Receipts, and Financial Snapshots.
- **[[10 - Scholarship Admin Relationship Map]]**: Trace for Grants Management, Application Reviews, and Assessment Discount Recalculation.
- **[[11 - System Admin & Reports Relationship Map]]**: Trace for User Management, Audit Trail Snapshots, SQL Backup/Restore, System Settings, and CSV Reports.
- **[[12 - LMS Student Portal Relationship Map]]**: Trace for Dynamic Dashboard, JIT Course Auto-Provisioning, Assignments, Quizzes, Attendance, and Gradebook.
- **[[13 - LMS Faculty Portal Relationship Map]]**: Trace for Faculty Dashboard, Module Uploads, Assignment Grading, Quiz Authoring, and Attendance Logging.

### 9. File-Level Technical Reference
- **[[00 - File Reference Index]]**: Master developer index and standard file-level metadata documentation framework.
- **[[01 - Controllers Reference]]**: Deep technical specifications for all 38 application controllers (methods, tables, roles, view chains).
- **[[02 - Models Reference]]**: Complete documentation for all 10 active database models (`User`, `Application`, `StudentAssessment`, `HealthRecord`, `BaseModel`, etc.).
- **[[03 - Services Reference]]**: In-depth specifications for all 9 core domain services (`StudentNumberService`, `AssessmentService`, `EnrollmentService`, and 6 LMS engine services) and 2 domain repositories.
- **[[04 - Core & Middleware Reference]]**: Architectural contracts for the 6 core framework engines (`Router`, `Database`, `Request`, `Response`, `Session`, `View`) and 6 middleware security layers.
- **[[05 - Views Catalog & Template Mapping]]**: Complete mapping of all 104 views across admin, applicant, auth, and LMS portals with layout and controller dependencies.

---

## High-Level System Summary
The TTU system manages both **College** and **Senior High School (SHS)** students. It is a monolithic **Hybrid MVC** Vanilla PHP web application using MariaDB/MySQL (47 tables/views), Bootstrap 5, Chart.js, Vanilla JavaScript, and PHPMailer SMTP. The database acts as the single source of truth across administrative, applicant, and academic domains.

