# ADR-005: Curriculum Versioning and Subject Catalog Immutability

## Status
**ACCEPTED & IMPLEMENTED** (Audit Passed: 24/24 College Scenarios, 24/24 SHS Scenarios, 15/15 Subject Safety Checks)

## Context
In the TTU Enrollment System, historical academic records (grades, enrollments, transcripts), financial assessments, LMS courses, and curriculum requirements rely on a shared universal `subjects` table and curriculum tables (`college_curricula`, `shs_curricula`, `college_curriculum_subjects`, `shs_curriculum_subjects`).

Initial database inspections revealed two structural vulnerabilities:
1. **Foreign Key Cascade Deletions:** All 7 foreign keys pointing to `subjects.id` utilized `ON DELETE CASCADE`, meaning deleting a subject could silently purge downstream enrollments, transcripts, and active LMS modules.
2. **Mutable Curricula:** Active curricula previously allowed in-place modifications (editing units, deleting subjects, reordering), which risked corrupting the academic standing of currently enrolled students.

## Decision

We instituted a comprehensive Curriculum Versioning and Subject Catalog Immutability Architecture across both College and Senior High School domains:

### 1. 3-State Curriculum Lifecycle
- **`draft`:** Fully editable. Subjects can be added, removed, edited, and reordered via `display_order`. May be deleted if unreferenced.
- **`active`:** Strictly immutable. Adding, removing, or reordering subjects is rejected server-side. Curricula cannot be deleted. Administrators modify active programs by **cloning to a new draft version** ($v+1$).
- **`archived`:** Read-only historical catalog for past student cohorts.

### 2. Subject Catalog Immutability & Safety
- **Constraint Hardening:** Migrated all 7 foreign keys referencing `subjects.id` to `ON DELETE RESTRICT`. Direct deletion of in-use subjects is blocked at the MySQL engine level.
- **Controller-Level Guards:** `SubjectController` computes real-time usage across draft/active/archived curricula, section schedules, enrollments, and LMS courses. Structural mutations (`subject_code`, `units`, `subject_type`, `education_level`) are rejected if `is_locked` is true.
- **Non-Destructive Retirement:** Replaced physical deletion with soft status toggles (`status = 1` Active, `status = 0` Inactive). Inactive subjects are excluded from new curriculum builders but preserved in historical records.
- **Financial Snapshot Protection:** Auto-recalculation in `FinanceController` is strictly constrained to unpaid assessments with zero paid balance. Paid and partially settled assessments are immutable historical records.

## Consequences

### Positive
- **Complete Academic & Historical Integrity:** Past transcripts, enrolled units, and settled financial balances can never be corrupted by subject edits or deletions.
- **Predictable Curriculum Evolution:** Department heads and registrars can draft and activate new curriculum versions without disrupting existing student cohorts.
- **Fail-Safe Database Protection:** Database-level `RESTRICT` constraints prevent accidental cascade wipes.

### Considerations
- Making structural changes to an active subject requires retiring the old subject (`status = 0`), creating a new subject code/unit combination, and publishing a new curriculum version.

---
**Related:**
- [[Curriculum Architecture]]
- [[Subject Catalog Immutability Architecture]]
- [[Registrar]]
- [[Finance]]
- [[ADR Index]]
