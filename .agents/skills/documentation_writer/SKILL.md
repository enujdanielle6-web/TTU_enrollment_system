---
name: Documentation Writer
description: Documents architecture, workflows, modules, database schema, and file-level relationships to keep Obsidian documentation synchronized with code.
---

# Documentation Writer

**Purpose**: Create, update, and maintain comprehensive, AI-operable technical documentation within `docs/obsidian/`. The documentation acts as a complete operational reference and architectural map, drastically reducing the need for developers or AI agents to perform blind codebase scans.

---

## 1. Core Documentation Principles

1. **Source Code is the Source of Truth**:
   - Never assume existing documentation is accurate without verifying against active PHP controllers, SQL schema (`database/schema.sql`), and views.
   - Never fabricate routes, database columns, class methods, or business logic.
2. **AI-Operability Standard**:
   - Provide concrete, discoverable file-level chains:
     $$\text{Entry Point} \rightarrow \text{Route (web.php)} \rightarrow \text{Controller} \rightarrow \text{Service/Repository} \rightarrow \text{Database Tables} \rightarrow \text{View}$$
   - Include exact repository paths (e.g. `app/Controllers/Admin/Finance/FinanceController.php`), exact class names, methods, parameters, and roles.
3. **Repair Over Duplicate**:
   - When an existing document has minor errors or omissions, repair and update it in place rather than creating competing duplicate files.

---

## 2. Directory Structure & Documentation Mapping

Documentation is organized under `docs/obsidian/`:
- `00 - Home/`: Master documentation index (`TTU Enrollment System Home.md`).
- `01 - Architecture/`: High-level system structure, Strangler Fig migration, request lifecycle.
- `02 - Modules/`: Departmental module specifications (Admissions, Clinic, Registrar, Scheduler, Finance, Scholarship, System Admin, Applicant Portal, Landing Cards).
- `03 - Workflows/`: End-to-end lifecycle flows (Student Lifecycle, Registration & OTP, Document Preferences, Health Clearance, Payment & Assessment).
- `04 - Database/`: Data Dictionary (`Data Dictionary.md`), ER Architecture diagrams, table-specific guides.
- `05 - Curriculum/`: Curriculum versioning, Subject catalog immutability, NSTP statutory rules.
- `06 - Business Rules/`: Master rule catalog (`Business Rules.md`).
- `07 - Security/`: Authentication, OTP, session management, RBAC, firewall rules.
- `08 - API & AJAX/`: Internal JSON and HTML AJAX endpoints.
- `14 - Architecture Decisions/`: Architectural Decision Records (`ADR-001` through `ADR-010`).
- `16 - Page Relationships/`: Detailed cross-department data flow and screen relationship maps (`00` through `11`).
- `17 - File Reference/`: Technical reference manuals for Controllers (`01`), Models (`02`), Services & Repositories (`03`), Core/Middleware (`04`), and Views (`05`).

---

## 3. Synchronization & Maintenance Trigger Rules

Whenever a code change alters:
- **Routes / Controllers**: Update `01 - Controllers Reference.md`, the corresponding module doc in `02 - Modules/`, and relationship map in `16 - Page Relationships/`.
- **Database Schema**: Update `04 - Database/Data Dictionary.md` and `Entity Relationship Architecture.md`.
- **Business Workflows**: Update `03 - Workflows/` and `06 - Business Rules/Business Rules.md`.
- **Services / Repositories**: Update `17 - File Reference/03 - Services Reference.md`.
