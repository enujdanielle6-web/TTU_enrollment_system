---
name: Project Memory Manager
description: Preserves long-term project continuity, tracks architecture decisions, monitors technical debt, and prevents regression across conversation turns.
---

# Project Memory Manager

**Purpose**: Maintain persistent architectural continuity, track established design decisions, log technical debt, and prevent future agents from regressing verified implementations or rediscovering known pitfalls.

---

## 1. Institutional Knowledge Baseline

The Memory Manager maintains awareness of the core project realities:
1. **Module Ecosystem**:
   - Applicant Portal (`app/Controllers/ApplicantController.php`, `EnrollController.php`, `DocumentController.php`, `HealthController.php`).
   - Admissions (`app/Controllers/Admin/Admissions/AdmissionsController.php`).
   - Clinic (`app/Controllers/Admin/Clinic/ClinicController.php`).
   - Finance & Cashier (`app/Controllers/Admin/Finance/FinanceController.php`, `FeeController.php`).
   - Registrar (`app/Controllers/Admin/Registrar/RegistrarController.php`, `CollegeController.php`, `ShsController.php`, `SubjectController.php`).
   - Scheduler (`app/Controllers/Admin/Scheduler/SchedulerController.php`).
   - Scholarship (`app/Controllers/Admin/Scholarship/ScholarshipController.php`).
   - System Admin & Reports (`app/Controllers/Admin/System/SystemController.php`, `ReportController.php`, `DashboardController.php`).
2. **Key Architectural Decisions**:
   - `ADR-001`: Application as Term (No `students` table).
   - `ADR-002`: Strangler Fig Migration (Hybrid backward-compatible routing).
   - `ADR-003` / `ADR-004`: Hybrid SPA Navigation (`spa-router.js`).
   - `ADR-005`: Curriculum Versioning (Draft $\rightarrow$ Active $\rightarrow$ Archived) and Subject Catalog Immutability.
   - `ADR-006`: Deferred Account Creation via Session OTP.
   - `ADR-007`: NSTP Modular Curriculum Integration.
   - `ADR-008`: Registrar-Exclusive Enrollment Finalization & Cashier Decoupling (`payment_verified`).
   - `ADR-009`: Financial Immutability via `assessment_items` snapshots & atomic receipt sequencing (`REC-YYYYMMDD-XXXX`).
   - `ADR-010`: Domain Services (`EnrollmentService`, `AssessmentService`, `StudentNumberService`) & Repositories.
3. **High-Risk Shared Core Files (Blast Radius)**:
   - `app/Helpers/functions.php`: Contains global helpers (`logActivity`, `generateAtomicReceiptNumber`, `snapshotAssessmentItems`, `sendVerificationCodeEmail`, `requirePermission`).
   - `app/Core/Router.php`, `Database.php`, `Request.php`, `Response.php`: Framework foundation.
   - `app/Middleware/`: Security, CSRF, Session, and RBAC filters.

---

## 2. Continuity Protocol for AI Agents

- **When Resuming from Compaction**: Consult `docs/obsidian/00 - Home/TTU Enrollment System Home.md` and the master matrix `docs/obsidian/16 - Page Relationships/00 - Master Relationship Index & Matrix.md`.
- **When Adding Features**: Check `docs/obsidian/10 - Bugs & Issues/Known Issues.md` to avoid previously encountered pitfalls.
- **When Updating Architecture**: Update the corresponding documentation in `docs/obsidian/` immediately to keep future agents informed.

---

## 3. Key Documentation References
- Home Index: [[TTU Enrollment System Home]]
- Architecture Reconnaissance: [[System Architecture Reconnaissance]]
- Shared Dependencies: [[01 - Shared Dependencies & Impact Analysis]]
- Known Issues & Technical Debt: [[Known Issues]]
- Architecture Decisions: [[ADR Index]]
