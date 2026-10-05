# TTU LMS PHASE 5 LMS ADMINISTRATION & GOVERNANCE SPECIFICATION

> **Subsystem**: LMS Administration, Governance & Enrollment Synchronization  
> **Phase**: Phase 5 — LMS Administration & Governance  
> **Status**: Completed & Verified (20/20 Phase 5 Tests Passed; Zero Regressions in Phases 1–4: 86/86 Total Passing)  
> **Implementation Date**: September 30, 2026  
> **Authoritative Root**: `c:\xampp\htdocs\sia`  

---

## 1. ARCHITECTURAL OVERVIEW & DOMAIN BOUNDARIES

Phase 5 completes and stabilizes the **LMS Administration and Governance Layer** in accordance with the governing architectural mandate:

> **ENROLLMENT OWNS ACADEMIC TRUTH.**  
> **LMS OWNS THE LEARNING EXPERIENCE.**

The LMS Admin interface provides centralized governance, operational observability, course lifecycle management, user access control, and reconciliation tools for the LMS *without duplicating or mutating official Enrollment academic records*.

### Key Domain Principles:
1. **No Shadow Registrar**: LMS Admin does not independently invent or alter curriculum, official subjects, sections, academic programs, or registrar enrollment statuses.
2. **Derived Course Shells**: LMS course shells exist solely as learning containers derived from official scheduling and section subjects (`college_section_subjects` / `shs_section_subjects`).
3. **Deterministic Reconciliation**: Synchronization detects discrepancies between timetable truth and LMS course shells (e.g. missing shells, faculty drift) and resolves them using transaction-safe, deterministic rules.
4. **Independent LMS Access Control**: LMS user status (`active`, `suspended`, `inactive` in `users.lms_status`) controls platform access without altering the student's authoritative academic enrollment (`applications.status`).
5. **Non-Destructive Archival**: Archival transitions course statuses from `active` to `archived` upon academic term completion, retaining all student submissions, grades, quiz attempts, and instructional materials intact.

```text
┌─────────────────────────────────────────────────────────────┐
│                 ENROLLMENT & SCHEDULING TRUTH               │
│   - College / SHS Sections (`college_sections`, `shs_sections`)│
│   - Timetable Subjects (`college_section_subjects`)         │
│   - Student Enrollments (`college_enrollments`)              │
│   - Faculty Timetable (`faculty_user_id`, `instructor`)      │
└──────────────────────────────┬──────────────────────────────┘
                               │
                Phase 2/5 Automated & Reconciled Sync
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    LMS GOVERNANCE LAYER                     │
│   - LMS Admin Dashboard (`/admin/lms/dashboard`)             │
│   - Course Catalog & Deep Inspection (`/admin/lms/courses`) │
│   - Synchronized Faculty Reassignment                        │
│   - User Access Governance (`/admin/lms/users`)              │
│   - Diagnostic Synchronization Scan (`/admin/lms/sync`)     │
│   - Academic Term Archival (`/admin/lms/archive`)            │
│   - Administrative Audit Logging (`/admin/lms/audit_logs`)   │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 LMS LEARNING EXPERIENCE LAYER               │
│   - Course Shells (`lms_courses`)                            │
│   - Modules & Learning Materials (`lms_modules`, `lms_materials`)│
│   - Assignments & Submissions (`lms_assignments`, `lms_submissions`)│
│   - Quizzes & Attempts (`lms_quizzes`, `lms_quiz_attempts`) │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. LMS ADMIN AUTHORIZATION & SECURITY

### 2.1 Route Guarding & RBAC
All LMS Admin endpoints are registered under strict administrative middleware in [`app/Routes/web.php`](file:///c:/xampp/htdocs/sia/app/Routes/web.php):
```php
$router->group([
    'middleware' => [
        'App\Middleware\SessionSecurityMiddleware',
        'App\Middleware\CsrfMiddleware',
        'App\Middleware\AuthMiddleware',
        'App\Middleware\RoleMiddleware:admin'
    ]
], function ($router) {
    // LMS Admin Routes
});
```
In addition, [`App\Controllers\Admin\LmsAdminController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php) enforces centralized programmatic server-side checks via `enforceAdminAccess()`:
- **Allowed**: `superadmin`, `admin`, or accounts with explicit LMS administrative permissions (`lms.manage`, `lms.admin`, `lms.courses.manage`).
- **Denied**: `scheduler`, `cashier`, `admissions`, `scholarship`, `clinic`, `faculty`, `student`, and unauthenticated users $\to$ **HTTP 403 Forbidden**.
- **Scheduler Boundary**: Scheduler does NOT inherit LMS Admin access from `sections.manage`.
- For complete specification, see [[LMS_ADMIN_RBAC.md]].

### 2.2 IDOR & Parameter Tampering Defenses
- All resource identifiers (`course_id`, `user_id`, `faculty_user_id`, `subject_id`, `section_id`) are validated as positive integers.
- Non-existent or invalid entity IDs cleanly return 404 or redirect with flash alerts rather than triggering uncaught exceptions or raw database errors.
- Target faculty members must possess `role = 'faculty'` and `is_active = 1` before assignment is permitted.

### 2.3 CSRF & HTTP Method Hygiene
- Destructive and state-changing actions (`reassign_faculty`, `toggle_status`, `archive_term`, `reconcile`, `update_user_status`, `generate`) accept **only POST** requests.
- All forms require a valid CSRF token (`<?= csrf_token() ?>`) validated by [`CsrfMiddleware`](file:///c:/xampp/htdocs/sia/app/Middleware/CsrfMiddleware.php).
- No state changes are permitted via GET requests.

---

## 3. LMS ADMIN ROUTES MATRIX

| Method | Endpoint | Handler | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/lms/dashboard` | `LmsAdminController@dashboard` | High-level operational metrics, term summary, recent activity |
| `GET` | `/admin/lms/courses` | `LmsAdminController@courses` | Filterable, paginated LMS course shell catalog |
| `GET` | `/admin/lms/courses/{id}` | `LmsAdminController@courseDetail` | Deep inspection of shell, timetable, roster, modules, assignments |
| `POST` | `/admin/lms/courses/{id}/reassign` | `LmsAdminController@reassignFaculty` | Reassigns instructor and synchronizes with authoritative schedule |
| `POST` | `/admin/lms/courses/{id}/status` | `LmsAdminController@updateCourseStatus` | Toggles course between 'active' and 'archived' |
| `GET` | `/admin/lms/generator` | `LmsAdminController@generator` | Existing course shell provisioning interface (preserved) |
| `POST` | `/admin/lms/generate` | `LmsAdminController@generateCourse` | Idempotent, duplicate-safe course shell generator action |
| `GET` | `/admin/lms/users` | `LmsAdminController@users` | LMS user access directory with enrollment & activity context |
| `POST` | `/admin/lms/users/{id}/status` | `LmsAdminController@updateUserStatus` | Modifies user `lms_status` ('active', 'suspended', 'inactive') |
| `GET` | `/admin/lms/sync` | `LmsAdminController@sync` | Diagnostic reconciliation scan identifying discrepancies |
| `POST` | `/admin/lms/sync/reconcile` | `LmsAdminController@reconcile` | Deterministic auto-reconciliation of missing shells & faculty drift |
| `GET` | `/admin/lms/archive` | `LmsAdminController@archive` | Academic term archival overview and course status breakdown |
| `POST` | `/admin/lms/archive/term` | `LmsAdminController@archiveTerm` | Bulk archives all active courses for a completed academic term |
| `GET` | `/admin/lms/audit_logs` | `LmsAdminController@auditLogs` | LMS administrative audit trail with actor, action, and change logs |

---

## 4. COMPONENT ARCHITECTURE & SERVICES

### 4.1 `LmsAdminService.php`
Central domain service implementing administrative operations with raw PDO queries in accordance with the project's Hybrid MVC architecture:
- `getDashboardStats()`: Aggregates operational KPIs (total courses, active courses, unassigned shells, active students/faculty, content items, submissions, quizzes, recent audit logs) without loading unbounded tables.
- `getCourses($filters, $page, $limit)`: Paginated, multi-filter course catalog with enrollment and content item counts.
- `getCourseInspection($courseId)`: Complete dossier assembling shell metadata, timetable schedule, student roster (regular and irregular), modules, materials, assignments, quizzes, and eligible faculty.
- `reassignFaculty($courseId, $facultyUserId, $adminUserId, $syncAuthoritativeSchedule)`: Reassigns LMS course instructor and atomically updates `college_section_subjects` / `shs_section_subjects` within a PDO transaction to prevent configuration drift.
- `updateCourseStatus($courseId, $status, $adminUserId)`: Updates course shell status (`active` $\leftrightarrow$ `archived`) while preserving all learning artifacts.
- `archiveTerm($academicLevel, $academicYear, $semester, $adminUserId)`: Transaction-safe bulk transition of active course shells to archived state for a completed term.
- `getLmsUsers($filters, $page, $limit)`: Lists users with role, LMS status, course count, and activity timestamps.
- `updateUserLmsStatus($userId, $lmsStatus, $adminUserId, $reason)`: Toggles LMS platform access (`active`, `suspended`, `inactive`) while leaving academic enrollment (`applications.status`) untouched.
- `scanEnrollmentSync()`: Comprehensive diagnostic scan comparing timetable section subjects against LMS course shells. Detects:
  - Missing course shells (official section subject exists, but no LMS shell)
  - Faculty mismatches (LMS shell instructor differs from timetable assignment)
  - Duplicate course shells (multiple shells for the same section & subject)
  - Orphan course shells (LMS shell without a valid section subject)
  - Healthy synchronized shells
- `reconcileAllDeterministic($adminUserId)`: Transactionally provisions missing course shells and aligns LMS faculty assignments to the official timetable. Leaves ambiguous duplicates and orphans flagged for administrator inspection without destructive writes.
- `getLmsAuditLogs($filters, $page, $limit)`: Filterable query on `activity_logs` scoped to LMS operations (`lms_%`, `bi-mortarboard`, `bi-archive`, etc.).

---

## 5. SYNCHRONIZATION & RECONCILIATION RULES

### 5.1 Authoritative Hierarchy
```text
Authoritative Truth: `college_section_subjects` / `shs_section_subjects`
                              │
                              ▼
Synchronized Downstream: `lms_courses`
```

### 5.2 Deterministic Auto-Reconciliation Actions
When an administrator executes **Run Reconcile**:
1. **Missing Shells**: If an active section subject has no corresponding `lms_course`, the system provisions a new course shell bound to the authoritative section, subject, and assigned faculty.
2. **Faculty Mismatches**: If `lms_courses.faculty_user_id` does not match `section_subjects.faculty_user_id`, the LMS course instructor is updated to match the authoritative schedule.
3. **Atomicity**: Both operations run within an isolated PDO transaction with activity logging.

### 5.3 Ambiguous Conflicts (Never Automatically Overwritten)
1. **Duplicate Course Shells**: If multiple LMS course shells exist for the same section and subject, the system flags them in the reconciliation report with their respective IDs, module counts, and submission counts. It **does NOT** delete either shell automatically, as one shell may contain submitted student work and grades.
2. **Orphan Course Shells**: If an LMS course exists for a section subject that was removed from the official curriculum, the system flags the shell as an orphan but preserves it to protect historical learning data.

---

## 6. ACADEMIC TERM & COURSE ARCHIVAL

### 6.1 State Definitions
- **Active**: Courses currently in session. Fully visible to students and editable by faculty.
- **Archived**: Historical courses whose academic term has concluded. Read-only reference state; student submissions, grades, feedback, materials, and quizzes remain intact.
- **Inactive / Suspended**: Administrative suspension of course or user access.

### 6.2 Data Retention Guarantee
The archival process is strictly non-destructive. Archival updates `lms_courses.status = 'archived'` without deleting:
- `lms_modules` or `lms_materials`
- `lms_assignments` or `lms_submissions`
- `lms_quizzes`, `lms_questions`, or `lms_quiz_attempts`
- `lms_announcements` or `lms_attendance`

---

## 7. AUDIT LOGGING ARCHITECTURE

All administrative operations are permanently recorded in the institutional `activity_logs` table via `logActivity()`:

| Action | Icon | Target Resource | Description & Context |
| :--- | :--- | :--- | :--- |
| LMS Course Created | `bi-mortarboard` | `lms_courses:{id}` | Course shell provisioned with section, subject, and instructor |
| LMS Faculty Reassigned | `bi-person-badge` | `lms_courses:{id}` | Old faculty ID $\to$ New faculty ID; schedule sync recorded |
| LMS Course Status Updated | `bi-archive` | `lms_courses:{id}` | Transitioned between `active` and `archived` |
| LMS Term Archived | `bi-archive-fill` | `lms_terms:{level}:{ay}:{sem}` | Bulk archival count recorded for completed term |
| LMS User Status Changed | `bi-shield-lock` | `users:{id}` | User LMS access updated (`active` $\leftrightarrow$ `suspended`), reason recorded |
| LMS Enrollment Reconciled | `bi-arrow-repeat` | `lms_reconciliation` | Count of provisioned shells and aligned faculty recorded |

---

## 8. VERIFICATION & TEST MATRIX

### 8.1 Automated Verification Suite (`scripts/tests/test_phase5_verification.php`)
All 20 test scenarios passed with 100% success rate:
- **Test 1**: LMS Admin dashboard KPIs retrieved (Total Courses: 18, Active Students: 21, Term: 2026-2027) $\to$ **PASS**
- **Test 2**: LMS Admin courses catalog retrieved (Retrieved 18 courses across 1 page) $\to$ **PASS**
- **Test 3**: Deep course inspection complete (Course #1: CC101, Roster: 13 students, Timetable: Lab 101) $\to$ **PASS**
- **Test 4**: LMS Admin user access list retrieved (Found 27 platform accounts with LMS access metadata) $\to$ **PASS**
- **Test 5**: User LMS status modified without corrupting enrollment (Suspended then restored; `applications.status` remained 'enrolled') $\to$ **PASS**
- **Test 6**: Enrollment synchronization diagnostic scan (Scanned: 18, Healthy: 18, Mismatches: 0) $\to$ **PASS**
- **Test 7**: Synchronization detects faculty mismatch (Course #2 flagged with LMS faculty 10 vs timetable faculty 8) $\to$ **PASS**
- **Test 8**: Deterministic reconciliation aligns faculty to timetable (Reconciliation synced 1 course; Course #2 restored to UID 8) $\to$ **PASS**
- **Test 9**: Course archival preserves historical data (Course #1 marked 'archived'; 8 modules and submissions preserved intact) $\to$ **PASS**
- **Test 10**: Restoring course returns status to active (Course #1 successfully restored to 'active' status) $\to$ **PASS**
- **Test 11**: Bulk term archival runs safely in transaction (Term query executed safely; 0 active shells for historic dummy term) $\to$ **PASS**
- **Test 12**: Audit logs record administrative operations (Retrieved 75 audit entries; LMS action logged with actor and timestamp) $\to$ **PASS**
- **Test 13**: Audit logs protected from student access (Role 'student' denied from administrative routes) $\to$ **PASS**
- **Test 14**: Audit logs protected from faculty access (Role 'faculty' denied from administrative routes) $\to$ **PASS**
- **Test 15**: Student rejected from LMS Admin endpoints (Student role blocked from LMS Admin dashboard, courses, sync, and users) $\to$ **PASS**
- **Test 16**: Faculty rejected from LMS Admin endpoints (Faculty role blocked from LMS Admin governance routes) $\to$ **PASS**
- **Test 17**: Unrelated authenticated roles rejected (Cashier role denied access to LMS Admin endpoints) $\to$ **PASS**
- **Test 18**: Unauthenticated requests are denied (Unauthenticated sessions intercepted by AuthMiddleware) $\to$ **PASS**
- **Test 19**: Course generator is duplicate-safe and idempotent (Pre-existing Course #1 (Sec: 1, Sub: 1) prevents duplicate insertion) $\to$ **PASS**
- **Test 20**: Faculty reassignment synchronizes LMS and Scheduling (Synchronized both `lms_courses` and `college_section_subjects` atomically without drift) $\to$ **PASS**

### 8.2 Full Cumulative Regression Suite
- Phase 1 Architecture & Storage Integrity: **14 / 14 PASS**
- Phase 2 Lifecycle & Provisioning Integration: **8 / 8 PASS**
- Phase 3 Student LMS Completion: **20 / 20 PASS**
- Phase 4 Faculty LMS Completion: **24 / 24 PASS**
- Phase 5 LMS Administration & Governance: **20 / 20 PASS**
- **Cumulative Total: 86 / 86 PASS (100%) — ZERO REGRESSIONS**

---

## 9. KNOWN LIMITATIONS & PHASE 6 BOUNDARIES

1. **Phase 6 Scope Boundary**: Phase 5 focused exclusively on LMS Administration and Governance. Full cross-portal end-to-end integration, global browser automated testing, and production regression hardening belong to **Phase 6**.
2. **Ambiguous Duplicate Resolution**: Phase 5 reconciliation automatically detects duplicates and alerts the administrator, but safely avoids automatic deletion. A manual merge wizard for legacy duplicate courses with student submissions can be added in Phase 6.
3. **Queue / Asynchronous Jobs**: Reconcile operations execute synchronously within PDO transactions. With the current institutional dataset (sub-second execution), this is optimal. Background queue workers are neither required nor introduced in accordance with core guidelines.
