# TTU LMS INTEGRATION & REBUILD: PHASES 1, 2, 3, 4 & 5 TECHNICAL HANDOFF SPECIFICATION

> **Subsystem**: Learning Management System (LMS) & Enrollment System Integration  
> **Repository Root**: `c:\xampp\htdocs\sia`  
> **Status**: Phase 1, Phase 2, Phase 3, Phase 4 & Phase 5 Fully Implemented, Verified, and Locked  
> **Verification Suites**:
> * Phase 1: [`scripts/test_phase1_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase1_verification.php) — **14 / 14 Passed (100%)**
> * Phase 2: [`scripts/test_phase2_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase2_verification.php) — **8 / 8 Passed (100%)**  
> * Phase 3: [`scripts/test_phase3_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase3_verification.php) — **20 / 20 Passed (100%)**  
> * Phase 4: [`scripts/test_phase4_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase4_verification.php) — **24 / 24 Passed (100%)**  
> * Phase 5: [`scripts/test_phase5_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase5_verification.php) — **20 / 20 Passed (100%)**  
> * **Total Verification Score**: **86 / 86 Automated Tests Passing (Zero Regressions)**  
> **Next Target**: Phase 6 (Full Integration, Security & Regression Hardening) — *Do NOT start until directed.*

---

## 1. PURPOSE & AGENT CONTEXT FOR FUTURE SESSIONS

This document is the **authoritative single-source technical handoff** for the rebuild, stabilization, and security hardening of the TTU Learning Management System (LMS) and its bidirectional integration with the TTU Enrollment System.

If you are an agent resuming work in a future session:
1. **Enrollment is the Academic Source of Truth**: The LMS never owns student enrollment records or course catalogs. It is a downstream learning experience dynamically derived from official enrollment.
2. **Hybrid MVC Architecture**: We follow the project's Fat Controller / Lightweight Repository pattern using Vanilla PHP, raw PDO queries, prepared statements, and Bootstrap 5 presentation views. SQL queries meant for business logic remain in controllers or domain services (`EnrollmentService`, `LmsService`, `LmsGradebookService`, `LmsQuizService`, `LmsAdminService`).
3. **No Unplanned Mutations**: Read operations (`GET` requests, course listing queries) must NEVER mutate database state (e.g. creating courses on page load is strictly forbidden).
4. **Non-Destructive Academic History**: Student academic activity (submissions, quiz attempts, attendance) must never be hard-deleted when students drop subjects, transfer sections, or when courses are archived.
5. **Always Run Regression Suites Before & After Modifying Code**:
   * `php scripts/test_phase1_verification.php` (14/14 PASS)
   * `php scripts/test_phase2_verification.php` (8/8 PASS)
   * `php scripts/test_phase3_verification.php` (20/20 PASS)
   * `php scripts/test_phase4_verification.php` (24/24 PASS)
   * `php scripts/test_phase5_verification.php` (20/20 PASS)
   * Cumulative Total: **86 / 86 PASS (100%)**

---

## 2. SYSTEM ARCHITECTURE & HIGH-LEVEL DOMAIN RELATIONSHIP

```text
┌────────────────────────────────────────────────────────────────────────┐
│                      ENROLLMENT SUBSYSTEM                              │
│                    (Academic Source of Truth)                          │
├────────────────────────────────────────────────────────────────────────┤
│  • Academic Hierarchy: College & SHS Curricula, Programs & Strands     │
│  • Sections: `college_sections`, `shs_sections`                        │
│  • Subject Offerings & Schedules: `college_section_subjects`           │
│  • Student Admissions & Applications: `applications`                   │
│  • Official Course Enrollment Roster: `college_enrollments`,           │
│    `shs_enrollments` (states: 'enrolled', 'dropped', 'withdrawn')      │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼ Unidirectional Derivation & Reconcile
┌────────────────────────────────────────────────────────────────────────┐
│                      LMS GOVERNANCE & ADMIN                            │
│                 (Central Governance & Management)                      │
├────────────────────────────────────────────────────────────────────────┤
│  • Central Admin Dashboard: KPIs, Active Term, Submissions, Quizzes    │
│  • Course Shell Catalog & Deep Inspection: Modules, Timetable, Roster  │
│  • Synchronized Faculty Reassignment: LMS & Timetable atomic updates   │
│  • User Access Governance: Independent `lms_status` ('suspended', etc.) │
│  • Diagnostic Sync & Auto-Reconcile: Missing shells, faculty mismatches│
│  • Academic Term Archival: Preserves learning artifacts                │
│  • LMS Audit Trail: Logged with actor, target, timestamp               │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                         LMS SUBSYSTEM                                  │
│                 (Derived Learning Experience)                          │
├────────────────────────────────────────────────────────────────────────┤
│  • Course Shells: `lms_courses` (tied to section + subject)            │
│  • Learning Content: `lms_modules`, `lms_materials`                    │
│  • Assessments: `lms_assignments`, `lms_quizzes`                       │
│  • Student Activity: `lms_submissions`, `lms_quiz_attempts`            │
│  • Faculty Management: Roster, Modules, Materials, Grading, Quizzes    │
│  • Bulk-Prefetched Gradebook: 4 queries total (eliminated N+1 problem) │
│  • Dynamic Student Access: Derived from active enrollment rows         │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. SUMMARY OF COMPLETED PHASES

### Phase 1: Architecture & Data Integrity Baseline
* **Canonical Storage Contract**: All instructional materials write to and stream from `storage/uploads/lms/materials/`, protected by `.htaccess` and session validation.
* **Authentication Gating**: Only applicants with `applications.status = 'enrolled'` can access the LMS.
* **Route Middleware**: Enforced `RoleMiddleware:student` and `RoleMiddleware:faculty` across all respective routes.
* **Repository Read Purity**: Eliminated write-on-read side effects from repositories.
* **TBA Faculty Handling**: Permitted unassigned course shells (`faculty_user_id = NULL`) to render as "Instructor TBA".
* **Automated Tests**: 14/14 PASS.

### Phase 2: Enrollment ↔ LMS Lifecycle Integration
* **Course Shell Provisioning**: Explicit, deterministic, and duplicate-safe provisioning (`lms_courses` UNIQUE on `academic_level`, `academic_section_id`, `subject_id`).
* **Atomic Section Transfer**: Moving a student transfers both `applications.section_id` and all active rows in `college_enrollments` or `shs_enrollments` in a single transaction.
* **Subject Drop / Withdrawal Lifecycle**: Statuses `'dropped'` and `'withdrawn'` set `dropped_at` and immediately revoke LMS access while retaining submissions and quiz history.
* **Faculty Reassignment**: Updating schedule timetable updates LMS course ownership cleanly.
* **Irregular Student Support**: Irregular students enrolling in individual subjects dynamically resolve their respective course shells without requiring block section membership.
* **Automated Tests**: 8/8 PASS.

### Phase 3: Student LMS Completion
* **Student Access Pipeline**: Student course visibility is dynamically computed from active enrollments (`status = 'enrolled'`).
* **Personal Gradebook Optimization**: Individual gradebook computes student score in $O(1)$ query complexity without aggregating whole-class roster matrices.
* **Assignment Deliverable Workflow**: Multi-attempt resubmissions supported via `ON DUPLICATE KEY UPDATE` without duplicate key errors; cross-course submissions strictly prevented.
* **Quiz Experience**: Enforced time limits, attempt tracking, and question isolation.
* **Automated Tests**: 20/20 PASS.

### Phase 4: Faculty LMS Completion
* **Faculty Ownership & Course Gating**: Server-side checks via `LmsService::isFacultyAuthorizedForCourse()` guard every course action.
* **Instructional Content Authoring**: Full management for modules, uploaded materials, assignments, and quizzes.
* **High-Performance Faculty Gradebook**: Eliminated the $N \times M$ query trap by pre-fetching all students, assignments, quizzes, submissions, and attempts in exactly 4 queries.
* **Roster Management**: Dynamically reflects regular and irregular students while excluding dropped/withdrawn students.
* **Automated Tests**: 24/24 PASS.

### Phase 5: LMS Administration & Governance
* **LMS Admin Dashboard**: Centralized operational KPIs, term summary, student/faculty activity, and recent audit trail.
* **Course Catalog & Deep Inspection**: Filterable paginated course table with deep inspection modal showing schedule timetable, student roster, modules, assignments, and quizzes.
* **Synchronized Faculty Reassignment**: Allows admins to reassign instructors while atomically updating both `lms_courses` and authoritative timetable (`college_section_subjects` or `shs_section_subjects`).
* **LMS User Access Governance**: Dedicated user access directory allowing admins to modify `lms_status` (`'active'`, `'suspended'`, `'inactive'`) without touching registrar enrollment.
* **Enrollment Synchronization & Diagnostic Scan**: Identifies missing course shells, faculty mismatches, duplicate shells, and orphan shells. Deterministic reconciliation repairs missing shells and aligns faculty in PDO transactions.
* **Academic Term Archival**: Bulk archives courses belonging to completed academic terms while preserving all grades, submissions, attempts, and learning materials.
* **LMS Audit Logging**: Administrative operations tracked in `activity_logs` with actor, action, timestamp, and affected records.
* **Automated Tests**: 20/20 PASS.

---

## 4. VERIFICATION COMMANDS FOR NEXT SESSION

Whenever resuming work, run all verification suites:
```bash
php scripts/test_phase1_verification.php   # 14 / 14 PASS
php scripts/test_phase2_verification.php   # 8 / 8 PASS
php scripts/test_phase3_verification.php   # 20 / 20 PASS
php scripts/test_phase4_verification.php   # 24 / 24 PASS
php scripts/test_phase5_verification.php   # 20 / 20 PASS
```
**Total Passing: 86 / 86 (100%)**
