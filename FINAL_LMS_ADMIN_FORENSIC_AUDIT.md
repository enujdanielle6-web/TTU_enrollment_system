# FINAL LMS ADMIN FORENSIC AUDIT

**Target Project**: `c:\xampp\htdocs\sia`  
**Evaluation Role**: Senior Systems Architect, Security Auditor & LMS Domain Specialist  
**Evaluation Standard**: Zero Code Modifications — Read-Only Forensic Audit  
**Date of Audit**: September 30, 2026  
**Artifact Generated**: [FINAL_LMS_ADMIN_FORENSIC_AUDIT.md](file:///c:/xampp/htdocs/sia/FINAL_LMS_ADMIN_FORENSIC_AUDIT.md)

---

## 1. Executive Summary

This forensic audit independently examines the claims, codebase, database schema, route registration, access control enforcement, data integrity guarantees, and automated test suites for the **Learning Management System (LMS) Administration & Governance** subsystem of Triple T University (TTU).

### Previous Status Claims vs. Audit Verdict
A prior implementation report claimed:
- *"100% Implemented, Hardened & Verified across All 5 Phases"*
- *"197 passed, 0 failed (100% pass rate)"*
- *"Fully hardened RBAC and zero data integrity risk"*

**Forensic Audit Verdict**: **PARTIALLY VERIFIED WITH CRITICAL INTEGRATION GAPS**.
1. **Core Features Exist and Function**: The cloner, duplicate/orphan conflict diagnostics, course catalog, status toggling, user access controls, and platform announcement service exist and execute real database logic.
2. **197/197 Tests Pass at Runtime**: All 5 test suites were executed independently via CLI and produced 197 passes with 0 failures against the live MariaDB database.
3. **CRITICAL ARCHITECTURAL BREACH**: In `LmsAdminService::reassignFaculty` ([app/Services/LmsAdminService.php:335-357](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php#L335-L357)), when an administrator reassigns a faculty member in LMS Admin with schedule sync enabled (default in the UI), it executes a direct `UPDATE` on `college_section_subjects` and `shs_section_subjects`. **LMS Admin modifies the Scheduler's authoritative timetable truth**, violating the core institutional invariant.
4. **HIGH SEVERITY PRIVILEGE ESCALATION**: In `LmsAuthController::loginProcess` ([app/Controllers/Lms/LmsAuthController.php:182](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php#L182)), when a faculty user with empty/null permissions logs into the LMS portal, the controller explicitly assigns `$_SESSION['user_permissions'] = ['*']` (wildcard). This grants any faculty member full superadmin permissions across permission-guarded subsystems.
5. **BREAKING CONTROLLER FATAL ERROR**: The endpoint `GET /admin/lms/announcements` ([app/Controllers/Admin/LmsAdminController.php:653](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php#L653)) calls `$this->adminService->getActiveTerm()`. **This method does not exist in `LmsAdminService.php`**. Navigating to the platform announcements interface crashes with a PHP Fatal Error. This was missed because the test suite only exercised the service layer directly.

---

## 2. Verified Architecture

The TTU LMS Admin subsystem is built upon the **Hybrid MVC ("Fat Controller" & Domain Service)** architecture:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        HTTP Request / Web Router                       │
│                   (app/Routes/web.php: /admin/lms/*)                   │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                   Global Router Middleware Group                       │
│  - App\Middleware\SessionSecurityMiddleware (Session hijacking defense)│
│  - App\Middleware\CsrfMiddleware (Rejects state-changing POST requests)│
│  - App\Middleware\AuthMiddleware (Enforces logged_in = true)           │
│  - App\Middleware\RoleMiddleware:admin (Allows staff roles only)       │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                 App\Controllers\Admin\LmsAdminController               │
│  - enforceAdminAccess(): Hardened guard (superadmin, admin, lms.manage)│
│  - CSRF Token verification (validateCsrfToken)                         │
│  - Request input validation and sanitize_string / strip_tags           │
└───────────────────┬───────────────────────────────┬────────────────────┘
                    │                               │
                    ▼                               ▼
┌──────────────────────────────────────┐  ┌──────────────────────────────┐
│     App\Services\LmsAdminService     │  │ App\Services\LmsAnnouncement-│
│ - Course catalog & inspection        │  │        Service                │
│ - Timetable sync & reconciliation    │  │ - Platform announcement CRUD │
│ - Syllabus / template cloner engine  │  │ - Audience segregation       │
│ - Duplicate/orphan conflict engine   │  │   (all, students, faculty)   │
│ - User LMS access suspension         │  │ - Severity & lifecycle engine│
│ - Academic term archival             │  │ - Course notice isolation    │
└───────────────────┬──────────────────┘  └──────────────┬───────────────┘
                    │                                    │
                    └──────────────────┬─────────────────┘
                                       │
                                       ▼
┌────────────────────────────────────────────────────────────────────────┐
│                     Atomic Database Transaction Layer                  │
│       (lms_courses, lms_modules, lms_materials, lms_assignments,       │
│        lms_quizzes, lms_questions, lms_announcements, activity_logs)   │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Enrollment ↔ LMS Data Flow

### The Institutional Invariant
$$\mathbf{ENROLLMENT\ OWNS\ ACADEMIC\ TRUTH.}$$
$$\mathbf{LMS\ OWNS\ THE\ LEARNING\ EXPERIENCE.}$$

### Verification Findings
1. **Zero Parallel Enrollment Database**: The forensic check verified that **NO `lms_enrollments` table exists** in MariaDB.
2. **Dynamic Student Resolution**:
   - For College: Student rosters and course visibility are resolved via live join across [applications](file:///c:/xampp/htdocs/sia/database/schema.sql#L377), [college_enrollments](file:///c:/xampp/htdocs/sia/database/schema.sql#L377), and [college_sections](file:///c:/xampp/htdocs/sia/database/schema.sql#L318).
   - For SHS: Resolved via live join across `applications`, [shs_enrollments](file:///c:/xampp/htdocs/sia/database/schema.sql#L349), and [shs_sections](file:///c:/xampp/htdocs/sia/database/schema.sql#L295).
3. **Course Shell Mapping**: LMS course shells in `lms_courses` enforce uniqueness on `(academic_level, academic_section_id, subject_id)`. Section cohorts taking the same subject are completely isolated into independent shells.
4. **Suspension Without Matriculation Mutation**: When an administrator toggles a student's access in `/admin/lms/users/{id}/status`, only `users.lms_status` is updated. `applications.status = 'enrolled'` remains 100% intact.

---

## 4. Route Audit

The audit verified all **22 routes** registered under `/admin/lms/*` in [app/Routes/web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php#L141-L173):

| # | Route | Method | Controller Handler | Middleware / Auth | View / Target | Status |
| :-: | :--- | :---: | :--- | :--- | :--- | :---: |
| 1 | `/admin/lms/dashboard` | `GET` | `LmsAdminController@dashboard` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/dashboard` | **PASS** |
| 2 | `/admin/lms/courses` | `GET` | `LmsAdminController@courses` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/courses/index` | **PASS** |
| 3 | `/admin/lms/courses/{id}` | `GET` | `LmsAdminController@courseDetail` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/courses/detail` | **PASS** |
| 4 | `/admin/lms/courses/{id}/reassign` | `POST` | `LmsAdminController@reassignFaculty` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to detail | **BREACH** (Scheduler Mutation) |
| 5 | `/admin/lms/courses/{id}/status` | `POST` | `LmsAdminController@updateCourseStatus` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to detail | **PASS** |
| 6 | `/admin/lms/generator` | `GET` | `LmsAdminController@courseGenerator` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/system/lms_course_generator` | **PASS** |
| 7 | `/admin/lms/generate` | `POST` | `LmsAdminController@generateLmsCourse` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to generator | **PASS** |
| 8 | `/admin/lms/users` | `GET` | `LmsAdminController@users` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/users/index` | **PASS** |
| 9 | `/admin/lms/users/{id}/status` | `POST` | `LmsAdminController@updateUserStatus` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to users | **PASS** |
| 10 | `/admin/lms/sync` | `GET` | `LmsAdminController@sync` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/sync/index` | **PASS** |
| 11 | `/admin/lms/sync/reconcile` | `POST` | `LmsAdminController@reconcile` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to sync | **PASS** |
| 12 | `/admin/lms/conflicts/resolve` | `POST` | `LmsAdminController@resolveConflict` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to sync | **PASS** |
| 13 | `/admin/lms/archive` | `GET` | `LmsAdminController@archive` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/archive/index` | **PASS** |
| 14 | `/admin/lms/archive/term` | `POST` | `LmsAdminController@processArchiveTerm` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to archive | **PASS** |
| 15 | `/admin/lms/audit_logs` | `GET` | `LmsAdminController@auditLogs` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/audit_logs/index` | **PASS** |
| 16 | `/admin/lms/cloner` | `GET` | `LmsAdminController@templateCloner` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/cloner/index` | **PASS** |
| 17 | `/admin/lms/cloner/process` | `POST` | `LmsAdminController@processCloneContent` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to target | **PASS** |
| 18 | `/admin/lms/announcements` | `GET` | `LmsAdminController@announcements` | `RoleMiddleware:admin` + `enforceAdminAccess()` | `admin/lms/announcements/index` | **BROKEN** (`getActiveTerm()` missing) |
| 19 | `/admin/lms/announcements/store` | `POST` | `LmsAdminController@storeAnnouncement` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to announcements | **PASS** |
| 20 | `/admin/lms/announcements/{id}/update` | `POST` | `LmsAdminController@updateAnnouncement` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to announcements | **PASS** |
| 21 | `/admin/lms/announcements/{id}/status` | `POST` | `LmsAdminController@toggleAnnouncementStatus` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to announcements | **PASS** |
| 22 | `/admin/lms/announcements/{id}/delete` | `POST` | `LmsAdminController@deleteAnnouncement` | `CsrfMiddleware` + `validateCsrfToken` + `enforceAdminAccess()` | Redirect to announcements | **PASS** |

---

## 5. RBAC Audit

### Evaluation of `LmsAdminController::enforceAdminAccess()`
```php
private function enforceAdminAccess(): void
{
    $userRole = $_SESSION['user_role'] ?? '';
    if (in_array($userRole, ['superadmin', 'admin'], true)) {
        return;
    }

    if (function_exists('hasPermission') && (
        hasPermission('*') ||
        hasPermission('lms.manage') ||
        hasPermission('lms.admin') ||
        hasPermission('lms.courses.manage')
    )) {
        return;
    }

    if (function_exists('requirePermission')) {
        requirePermission(['lms.manage', 'lms.admin', 'lms.courses.manage']);
    } else {
        header("HTTP/1.1 403 Forbidden");
        echo "403 Forbidden - LMS Administrative access required.";
        exit;
    }
}
```

### Verified Access Matrix

| Role | Attempted Route | Rejection Point | Response |
| :--- | :--- | :--- | :---: |
| **`superadmin`** | `/admin/lms/*` | Authorized by role & wildcard | **HTTP 200** |
| **`admin`** | `/admin/lms/*` | Authorized by role & base permissions | **HTTP 200** |
| **`scheduler`** | `/admin/lms/*` | Rejected by `enforceAdminAccess()` (lacks LMS perms) | **HTTP 403** |
| **`cashier`** | `/admin/lms/*` | Rejected by `enforceAdminAccess()` | **HTTP 403** |
| **`admissions`** | `/admin/lms/*` | Rejected by `enforceAdminAccess()` | **HTTP 403** |
| **`faculty`** | `/admin/lms/*` | Rejected by `RoleMiddleware:admin` & `enforceAdminAccess()` | **HTTP 403 / Redirect** |
| **`student`** | `/admin/lms/*` | Rejected by `RoleMiddleware:admin` & `enforceAdminAccess()` | **HTTP 403 / Redirect** |
| **Unauthenticated** | `/admin/lms/*` | Rejected by `SessionSecurityMiddleware` | **HTTP 403 / Redirect** |

### Privilege Escalation Finding (LmsAuthController)
While `enforceAdminAccess()` itself is hardened, **`app/Controllers/Lms/LmsAuthController.php:182`** contains a dangerous default:
```php
$_SESSION['user_permissions'] = !empty($user['permissions']) ? json_decode($user['permissions'], true) : ['*'];
```
When a faculty user logs in via `/auth/lms_faculty_login.php` with an empty or null `permissions` column, `$_SESSION['user_permissions']` is assigned `['*']`. If this user subsequently accesses any endpoint where `RoleMiddleware:admin` is absent or improperly configured, `hasPermission('*')` evaluates to `true`.

---

## 6. CSRF Audit

All 12 state-changing POST endpoints are defended by **two independent layers**:
1. **Layer 1 (`App\Middleware\CsrfMiddleware`)**: Intercepts all incoming `POST`, `PUT`, `DELETE` requests at the router level and verifies `hash_equals($_SESSION['csrf_token'], $token)`. Throws `HttpException(403)` if token is missing or mismatched.
2. **Layer 2 (Controller-Level Token Validation)**:
   - Methods 1–8 call `$this->validateCsrfToken($request)`.
   - Announcement methods (9–12) call `validateCsrfToken($_POST['csrf_token'] ?? '')`.

---

## 7. IDOR / Object Access Audit

All endpoints accepting entity IDs were inspected:
- **`courses/{id}`**: Handled safely. Cast to `(int)$id`; queries are read-only; non-existent IDs redirect gracefully with flash error.
- **`courses/{id}/reassign` & `courses/{id}/status`**: Protected by admin auth and CSRF.
- **`users/{id}/status`**: Cast to `(int)$id`. Validates target user existence before updating `lms_status`.
- **`cloner/process`**: Strictly validates both `source_course_id` and `target_course_id`. Prevents cloning a course into itself (`source === target`).
- **`conflicts/resolve`**: Validates `course_id`. Resolves actions via strict switch statement.
- **`announcements/{id}/update` & `announcements/{id}/delete`**: Strictly validates `WHERE id = :id AND lms_course_id IS NULL`. Prevents administrators from manipulating or deleting course-scoped notices through the platform announcement API.

---

## 8. Database / SQL Audit

### Prepared Statements vs. Query Interpolation
While 95% of queries use prepared PDO statements, **unparameterized direct SQL query interpolation was discovered in `LmsAdminService.php`**:
- **Lines 971, 976, 978, 979**:
  ```php
  $tModCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = $targetCourseId")->fetchColumn();
  ```
- **Lines 1074, 1075, 1076, 1104**:
  ```php
  $maxOrder = (int)$this->pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM lms_modules WHERE lms_course_id = $targetCourseId")->fetchColumn();
  ```
Although `$targetCourseId` is cast to `int` in the controller, interpolating variables into `PDO::query()` violates TTU repository security standards ("Always use prepared statements") and poses latent risk if the service method is reused elsewhere with unsanitized inputs.

---

## 9. Course Cloner Audit

The Phase 3 Course Content Cloner ([app/Services/LmsAdminService.php:1046-1275](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php#L1046-L1275)) was forensically verified:
1. **Instructional Entities Replicated**:
   - `lms_modules` (mapped via `$moduleIdMap`)
   - `lms_materials` (re-linked to new module IDs)
   - `lms_assignments` (`due_date` reset to `NULL`, re-linked to new modules)
   - `lms_quizzes` (`start_date` and `end_date` reset to `NULL`)
   - `lms_questions` (re-linked to new quiz IDs)
   - `lms_question_choices` (re-linked to new question IDs)
2. **Student Artifact Isolation**: **Zero student records copied**. Excludes `lms_submissions`, `lms_quiz_attempts`, `lms_quiz_answers`, `lms_attendance_sessions`, and `lms_attendance_records`.
3. **Collision Safety Modes**:
   - `empty_only`: Rejects cloning if target contains $\ge 1$ module, assignment, or quiz.
   - `append`: Discovers `MAX(display_order)` on target and adds offset to cloned modules.
4. **Transaction Rollback**: Wraps operations in `beginTransaction()` / `commit()` with full rollback on failure.

---

## 10. Conflict Resolution Audit

The Phase 4 Conflict Resolution Engine ([app/Services/LmsAdminService.php:1417-1715](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php#L1417-L1715)) was forensically verified:
1. **Prohibition of Automatic Merging**:
   - `merge_shells` and `merge` are **strictly blocked by policy exception**:
     *"Automatic course merging is unsupported by policy. Moving student submissions, quiz attempts, and grade records across course boundaries risks corrupting student gradebooks and transcripts."*
2. **Safety Gate on Shell Deletion**:
   - `delete_empty_shell` is permitted **ONLY IF**:
     `$summary['is_empty_shell'] === true` (`total_student_artifacts === 0 && total_instructional_items === 0 && roster_count === 0`).
   - If any student submission, quiz attempt, module, or enrolled student exists, deletion is aborted with a safety exception.
3. **Preservation Strategy**: Safe archiving (`archive_duplicate`, `archive_orphan`) is the mandated resolution path for shells with historical student artifacts.

---

## 11. Announcement Audit

The Phase 5 Platform Announcement Engine ([app/Services/LmsAnnouncementService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAnnouncementService.php)) was forensically verified:
1. **Platform vs. Course Notice Isolation**:
   - Platform notices enforce `WHERE lms_course_id IS NULL`.
   - Faculty course notices enforce `WHERE lms_course_id = :course_id`.
2. **Audience Segregation**:
   - Students only see `target_audience IN ('all', 'students')`.
   - Faculty only see `target_audience IN ('all', 'faculty')`.
   - Faculty notices never leak to student feeds.
3. **Scheduling & Expiration**: Active notices filter on `status = 'published'`, `published_at <= NOW()`, and `expires_at > NOW()`.
4. **XSS Defense**: Titles and content are stripped of HTML tags via `strip_tags()` upon creation and escaped via `htmlspecialchars()` upon rendering.
5. **CONTROLLER DEFECT**: `LmsAdminController@announcements` ([LmsAdminController.php:653](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php#L653)) attempts to call `$this->adminService->getActiveTerm()`. Because this method was never added to `LmsAdminService.php`, navigating to `/admin/lms/announcements` throws a fatal PHP error.

---

## 12. Audit Logging Audit

All high-impact LMS Admin mutations invoke `logActivity()`:
- Faculty reassignment (`bi-person-badge`)
- Course status toggle (`bi-archive`)
- Course shell generation (`bi-mortarboard`)
- User LMS access suspension (`bi-shield-lock`)
- Deterministic reconciliation (`bi-arrow-repeat`)
- Conflict archival, quarantine, and cleanup (`bi-archive`, `bi-shield-exclamation`, `bi-trash`)
- Course content cloning (`bi-files`)
- Announcement creation, status toggle, and deletion (`bi-megaphone`, `bi-toggle-on`, `bi-trash-fill`)

All events record actor ID, client IP, affected record, description, before/after values, and institutional reason.

---

## 13. UI / Route Link Audit

1. **Navigation**:
   - [app/Views/components/sidebar.php:51-56](file:///c:/xampp/htdocs/sia/app/Views/components/sidebar.php#L51-L56) correctly renders "LMS Governance" link (`/sia/admin/lms/dashboard`) gated by role and LMS permissions.
   - [app/Views/components/admin_navbar.php:401](file:///c:/xampp/htdocs/sia/app/Views/components/admin_navbar.php#L401) renders "LMS Governance" link.
2. **Shared Ribbon**:
   - [app/Views/admin/lms/components/lms_header.php](file:///c:/xampp/htdocs/sia/app/Views/admin/lms/components/lms_header.php) provides unified navigation across all 8 LMS Admin views.
3. **Form Actions & CSRF**:
   - All forms in the presentation views (`courses/detail.php`, `sync/index.php`, `cloner/index.php`, `announcements/index.php`, `users/index.php`, `archive/index.php`) submit to verified POST routes and include `<?= getCsrfInput() ?>`.

---

## 14. Test Claim Verification

Independent classification of the 5 test suites:

| Suite | File | Claimed | Runtime Result | Test Classification | Audit Notes |
| :--- | :--- | :---: | :---: | :---: | :--- |
| **Suite 1: RBAC Hardening** | `test_lms_admin_rbac_hardening.php` | 13/13 | **13/13 PASS** | **REAL PASS** | Directly invokes `enforceAdminAccess()` via reflection & controller methods; verifies all negative role rejections. |
| **Suite 2: Feature Validation** | `test_phase2_lms_admin_verification.php` | 33/33 | **33/33 PASS** | **REAL PASS** | Executes against live MariaDB data; verifies catalog, inspection, sync, archival, audit logs, and invariants. |
| **Suite 3: Course Cloner** | `test_lms_admin_cloner.php` | 59/59 | **59/59 PASS** | **REAL PASS** | Creates temporary test fixtures; verifies deep cloning, empty mode, append mode, artifact isolation, and rollback. |
| **Suite 4: Conflict Resolution** | `test_lms_admin_conflict_resolution.php` | 45/45 | **45/45 PASS** | **REAL PASS** | Validates duplicate/orphan forensic detection, merge prohibition, safety gates on deletion, and preservation. |
| **Suite 5: Announcements** | `test_lms_admin_announcements_and_governance.php` | 47/47 | **47/47 PASS** | **WEAK PASS** | **INTEGRATION BLIND SPOT**: Only tests `LmsAnnouncementService` directly. Never calls `LmsAdminController@announcements`, thus masking the fatal error on line 653 (`getActiveTerm()`). |

**Grand Total**: 197 / 197 assertions execute and pass, but Suite 5 failed to catch a breaking controller defect due to lack of end-to-end controller dispatch testing.

---

## 15. Runtime Verification

Runtime execution of test suites on `localhost` (Windows PowerShell, PHP 8.2+):
```powershell
php scripts/test_lms_admin_rbac_hardening.php              # 13 / 13 PASSED (Code 0)
php scripts/test_phase2_lms_admin_verification.php          # 33 / 33 PASSED (Code 0)
php scripts/test_lms_admin_cloner.php                       # 59 / 59 PASSED (Code 0)
php scripts/test_lms_admin_conflict_resolution.php          # 45 / 45 PASSED (Code 0)
php scripts/test_lms_admin_announcements_and_governance.php # 47 / 47 PASSED (Code 0)
```
- Real MariaDB queries were executed.
- No mocks were used to hide database query failures.
- Cleanup routines properly deleted temporary test courses.

---

## 16. Security Findings

### Finding SEC-01: Wildcard Privilege Escalation in `LmsAuthController`
- **File**: [app/Controllers/Lms/LmsAuthController.php:182](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php#L182)
- **Severity**: **HIGH**
- **Description**: When a faculty user logs in through the faculty portal, if their `permissions` column in `users` is empty/null, the controller assigns `$_SESSION['user_permissions'] = ['*']`. This gives faculty full wildcard access to all permission-checked modules.
- **Risk**: A faculty user could access administrative functions in any module that relies solely on `hasPermission('*')` without strict role checks.

### Finding SEC-02: Direct SQL Query Variable Interpolation
- **File**: [app/Services/LmsAdminService.php:971, 976, 978, 979, 1074, 1075, 1076, 1104](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php#L971)
- **Severity**: **MEDIUM**
- **Description**: Cloner methods interpolate `$targetCourseId` directly into `PDO::query()` rather than using prepared statements with bound parameters.
- **Risk**: Violates institutional prepared statement standard; creates latent SQL injection risk if service methods are called with untyped inputs.

### Finding SEC-03: Inconsistent CSRF Validation Pattern
- **File**: [app/Controllers/Admin/LmsAdminController.php:664, 699, 734, 761](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php#L664)
- **Severity**: **LOW**
- **Description**: Announcement mutation methods read `$_POST['csrf_token']` directly and call `validateCsrfToken()`, whereas other controller methods use `$this->validateCsrfToken($request)`.

---

## 17. Data Integrity Findings

### Finding DAT-01: LMS Admin Modifies Authoritative Scheduler Timetable
- **File**: [app/Services/LmsAdminService.php:335-357](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php#L335-L357)
- **Severity**: **CRITICAL**
- **Description**: In `reassignFaculty()`, when `$syncAuthoritativeSchedule` is true (default checked in the UI), the service executes:
  ```php
  UPDATE college_section_subjects 
  SET faculty_user_id = :fid, instructor = :iname, updated_at = NOW() 
  WHERE college_section_id = :sec AND subject_id = :sub
  ```
- **Architectural Breach**: The Scheduler subsystem owns official timetable assignments (`college_section_subjects` and `shs_section_subjects`). LMS Admin is strictly forbidden from modifying official scheduler records. LMS Admin should only update `lms_courses.faculty_user_id`. If an instructor changes in the LMS, it must either remain an LMS-only delivery assignment or require an official schedule revision in Scheduler.

---

## 18. Documentation Drift

### Finding DOC-01: False Claim Regarding `LMS_MASTER_DOCUMENTATION.md`
- **File**: [LMS_ADMIN_IMPLEMENTATION_STATUS.md:307](file:///c:/xampp/htdocs/sia/LMS_ADMIN_IMPLEMENTATION_STATUS.md#L307)
- **Severity**: **MEDIUM**
- **Description**: The status report claims: *"docs/obsidian/LMS_MASTER_DOCUMENTATION.md: Updated Section 4.5 with all 22 administrative routes and Section 7 with conflict resolution and platform announcement architectures."*
- **Actual Reality**: [docs/obsidian/LMS_MASTER_DOCUMENTATION.md](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_MASTER_DOCUMENTATION.md) was last modified on September 28, 2026 and completely lacks Section 4.5 and the 22 LMS Admin routes. Furthermore, [docs/obsidian/LMS_ROUTE_MAP.md](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_ROUTE_MAP.md) only documents Student and Faculty routes.

---

## 19. Confirmed Working Features

1. **RBAC Isolation**: Scheduler, students, faculty, and unauthorized staff are rejected with HTTP 403 on `/admin/lms/*`.
2. **CSRF Protection**: All 12 POST mutations reject requests with missing or invalid CSRF tokens.
3. **Course Content Cloner**: Deep replication of modules, materials, assignments, quizzes, questions, and choices without copying any student submissions or grades.
4. **Safe Conflict Resolution**: Forensic detection of duplicates and orphans; strict blocking of destructive deletion when student work exists; absolute prohibition of automatic merges.
5. **Audience-Segregated Announcements**: Platform announcements with severity badges, scheduled publishing, and audience segregation (`all`, `students`, `faculty`).
6. **LMS User Access Management**: Independent suspension of LMS platform access without corrupting registrar enrollment status.
7. **Audit Logging**: Comprehensive, append-only logging of administrative mutations in `activity_logs`.

---

## 20. Issues Found

1. **CRITICAL**: `LmsAdminService::reassignFaculty` modifies `college_section_subjects` / `shs_section_subjects` (Scheduler Domain Breach).
2. **HIGH**: `GET /admin/lms/announcements` throws Fatal Error due to missing method `LmsAdminService::getActiveTerm()`.
3. **HIGH**: `LmsAuthController::loginProcess` assigns wildcard `$_SESSION['user_permissions'] = ['*']` to faculty with empty permissions.
4. **MEDIUM**: Unparameterized SQL variable interpolation in `LmsAdminService.php` cloner methods.
5. **MEDIUM**: Suite 5 automated tests only tested service layer, masking controller-level fatal error.
6. **MEDIUM**: Documentation drift in `docs/obsidian/LMS_MASTER_DOCUMENTATION.md` and `LMS_ROUTE_MAP.md`.
7. **LOW**: Inconsistent CSRF validation abstraction across controller methods.
8. **LOW**: No native `lms_admin` role in `users.role` enum.

---

## 21. Severity Classification

| Finding ID | Domain / Component | Description | Severity |
| :--- | :--- | :--- | :---: |
| **DAT-01** | Architecture / Scheduler | Reassignment modifies Scheduler timetable tables | **CRITICAL** |
| **BUG-01** | Routing / Announcements | Controller calls undefined `getActiveTerm()` method | **HIGH** |
| **SEC-01** | Authentication / RBAC | Faculty login assigns `['*']` wildcard permission | **HIGH** |
| **SEC-02** | Security / SQL | Variable interpolation in `PDO::query()` in cloner | **MEDIUM** |
| **QA-01** | Quality Assurance | Suite 5 test blind spot on controller action | **MEDIUM** |
| **DOC-01** | Documentation | Master docs not synchronized with implementation | **MEDIUM** |
| **ARC-01** | Architecture / CSRF | Mixed CSRF token verification approaches | **LOW** |
| **ARC-02** | Database / Schema | No dedicated `lms_admin` role enum in `users` | **LOW** |

---

## 22. Recommended Fixes

1. **Fix DAT-01 (Scheduler Boundary)**: In [app/Services/LmsAdminService.php:332-358](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php#L332-L358), remove the `UPDATE college_section_subjects` and `UPDATE shs_section_subjects` queries. LMS faculty reassignment should update `lms_courses.faculty_user_id` only. If timetable alignment is required, it must be performed in Scheduler by a scheduler user.
2. **Fix BUG-01 (Missing Method)**: Implement public method `getActiveTerm(): array` in [app/Services/LmsAdminService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php):
   ```php
   public function getActiveTerm(): array
   {
       $stmt = $this->pdo->query("
           SELECT DISTINCT academic_year, semester 
           FROM college_sections 
           WHERE (status = 1 OR status = 'active') 
           ORDER BY academic_year DESC, semester ASC 
           LIMIT 1
       ");
       return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['academic_year' => '2026-2027', 'semester' => 'First'];
   }
   ```
3. **Fix SEC-01 (Wildcard Perms)**: In [app/Controllers/Lms/LmsAuthController.php:182](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php#L182), replace `['*']` with `[]`:
   ```php
   $_SESSION['user_permissions'] = !empty($user['permissions']) ? json_decode($user['permissions'], true) : [];
   ```
4. **Fix SEC-02 (SQL Parameterization)**: In [app/Services/LmsAdminService.php:971-1105](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php#L971), replace all `query("... $targetCourseId")` with `prepare("... :cid")` and `execute(['cid' => $targetCourseId])`.
5. **Fix QA-01 (Controller Testing)**: Update `test_lms_admin_announcements_and_governance.php` to invoke `LmsAdminController@announcements` directly to test the controller action and view rendering.
6. **Fix DOC-01 (Documentation Sync)**: Update `docs/obsidian/LMS_MASTER_DOCUMENTATION.md` and `docs/obsidian/LMS_ROUTE_MAP.md` to reflect all 22 LMS Admin routes.

---

## 23. Remaining Risks

1. **Risk of Faculty Access Drift**: Without fixing `LmsAuthController.php:182`, faculty accounts logging in with empty permissions carry system-wide wildcard permissions in their session.
2. **Risk of Unintended Schedule Overwrites**: If an LMS Admin reassigns a course instructor with the timetable sync box checked, it overrides the Scheduler's official section assignment without Scheduler review.
3. **User Facing Crash**: Accessing `/admin/lms/announcements` in a web browser will result in an immediate 500 error / unhandled Fatal Error until `getActiveTerm()` is added.

---

## 24. Final Readiness Assessment

- **Overall Status**: **CONDITIONALLY FUNCTIONAL — RELEASE BLOCKED**.
- **Blockers**:
  1. `DAT-01`: Institutional domain violation (Scheduler timetable mutation).
  2. `BUG-01`: Runtime crash on Announcements management page.
  3. `SEC-01`: Wildcard permission grant during faculty login.
- **Readiness for Next Phase**: The core foundation is solid and verified across 197 assertions, but the 3 blockers identified above must be resolved before releasing to production or proceeding to subsequent phases.
