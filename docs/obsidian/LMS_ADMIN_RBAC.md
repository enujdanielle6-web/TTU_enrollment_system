# LMS Admin Role-Based Access Control (RBAC) & Boundary Isolation

## 1. Executive Summary

This specification governs the authorization architecture and domain isolation for the **Learning Management System (LMS) Administration & Governance** subsystem of Triple T University (TTU).

### Governing Institutional Principle
> **ENROLLMENT OWNS ACADEMIC TRUTH. LMS OWNS THE LEARNING EXPERIENCE.**

- **Enrollment / Registrar**: Authoritative source of academic truth, student identities, degree curricula, subjects, and official enrolled statuses.
- **Scheduler**: Authoritative builder of the physical and virtual timetable matrix, section block names, room logistics, and initial timetable instructor assignments.
- **LMS Admin**: Governance of digital classroom delivery, learning materials audit, student instructional status, deterministic course shell synchronization, and academic term archiving.

---

## 2. Who Can Access LMS Admin

Access to `/admin/lms/*` endpoints and [`LmsAdminController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php) is restricted exclusively to authorized institutional administrators.

### Authorized Roles
1. **`superadmin`**:
   - Institutional Super Administrator (`role = 'superadmin'`).
   - Possesses wildcard permission (`*`).
   - Unrestricted operational and system authority.
2. **`admin`**:
   - Institutional Administrator / Registrar (`role = 'admin'`).
   - Directly authorized via controller role verification and base permissions (`lms.manage`, `lms.courses.manage`).
3. **Explicit LMS Administrators**:
   - Any staff account explicitly provisioned with granular LMS administrative permissions in `users.permissions`:
     - `lms.manage`
     - `lms.admin`
     - `lms.courses.manage`

### Strictly Denied Roles
The following roles are **explicitly denied** access to LMS Admin, returning **HTTP 403 Forbidden** on any request:
- **`scheduler`**: Denied server-side. Scheduler permissions (`sections.manage`, `schedules.manage`) do NOT grant LMS Admin access.
- **`cashier`**: Denied server-side.
- **`admissions`**: Denied server-side.
- **`scholarship`**: Denied server-side.
- **`clinic`**: Denied server-side.
- **`faculty`**: Denied server-side. Must use `/lms/faculty/*`.
- **`student`**: Denied server-side. Must use `/lms/student/*`.
- **`applicant`**: Denied server-side. Must use `/applicant/*`.
- **Unauthenticated**: Redirected to `/sia/auth/login.php` or denied with HTTP 403.

---

## 3. Required Permissions Specification

Authorization in [`LmsAdminController::enforceAdminAccess()`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php) evaluates permissions according to the following strict evaluation hierarchy:

```
[Incoming Request to /admin/lms/*]
                │
                ▼
        Is User Logged In? ─── NO ───► Redirect / 403 Forbidden
                │ YES
                ▼
   user_role IN ('superadmin', 'admin')? ─── YES ───► AUTHORIZED (Access Granted)
                │ NO
                ▼
   hasPermission('*') OR
   hasPermission('lms.manage') OR
   hasPermission('lms.admin') OR
   hasPermission('lms.courses.manage')? ─── YES ───► AUTHORIZED (Access Granted)
                │ NO
                ▼
   requirePermission(['lms.manage', 'lms.admin', 'lms.courses.manage'])
                │
                ▼
         HTTP 403 FORBIDDEN (Access Denied)
```

### Permission Definitions
| Permission Key | Description | Default Granted Roles |
| :--- | :--- | :--- |
| `*` | Full system wildcard access | `superadmin` |
| `lms.manage` | Full LMS administrative governance, synchronization, reconciliation, and archival | `admin` |
| `lms.admin` | LMS administrative portal access | Explicit JSON in `users.permissions` |
| `lms.courses.manage` | Course shell inspection, faculty reassignment, and status management | `admin` |
| `sections.manage` | Academic section creation and timetable maintenance | `scheduler` (Does NOT grant LMS Admin) |

---

## 4. Scheduler vs. LMS Admin Boundaries

A critical boundary exists between **Scheduler** and **LMS Admin**. The Scheduler creates sections and timetables; LMS Admin governs course shells and digital delivery.

### What Scheduler CAN Do
1. Create and manage section cohorts in `college_sections` and `shs_sections` (`BSIT 1-A`, `STEM 11-1`).
2. Attach curriculum subjects to sections via `college_section_subjects` and `shs_section_subjects`.
3. Assign physical rooms, days, times, and delivery modes (`Face-to-Face`, `Online Synchronous`, `Blended`, `Asynchronous`).
4. Assign timetable instructors (`faculty_user_id` and `instructor` string) to timetable slots.
5. Trigger automated course shell provisioning as a side-effect when saving timetable schedules via `SchedulerController::process`.

### What Scheduler CANNOT Do
1. Access the LMS Admin Operational Dashboard (`/admin/lms/dashboard`).
2. Access the LMS Course Shell Catalog (`/admin/lms/courses`).
3. Perform deep inspection of LMS course shells, modules, student rosters, assignments, and quizzes (`/admin/lms/courses/{id}`).
4. Reassign LMS instructors directly through LMS Admin (`/admin/lms/courses/{id}/reassign`).
5. Modify course lifecycle status (`active` $\leftrightarrow$ `archived`) (`/admin/lms/courses/{id}/status`).
6. Access or toggle user LMS statuses (`active`, `suspended`, `inactive`) (`/admin/lms/users`).
7. Run the Enrollment Synchronization diagnostic scan (`/admin/lms/sync`).
8. Execute deterministic synchronization reconciliation (`/admin/lms/sync/reconcile`).
9. Execute academic term bulk archival (`/admin/lms/archive/term`).
10. View LMS administrative audit trails (`/admin/lms/audit_logs`).

---

## 5. What LMS Admin CAN and CANNOT Do

### What LMS Admin CAN Do
1. **Operational Monitoring**: Monitor active course shells, student accounts, faculty coverage, and synchronization health.
2. **Deep Course Inspection**: Audit instructional materials, syllabi, assignment prompts, quiz items, and classroom activity without student impersonation.
3. **Emergency Faculty Reassignment**: Reassign a course instructor in `lms_courses` and atomically update the timetable slot (`college_section_subjects.faculty_user_id`) to maintain schedule consistency.
4. **Course Lifecycle Governance**: Transition completed semester courses to read-only `archived` status while preserving all student submissions and grades.
5. **Deterministic Reconciliation**: Provision missing course shells for scheduled sections and align faculty drift against authoritative timetable truth.
6. **Student LMS Access Control**: Suspend student LMS access (`users.lms_status = 'suspended'`) for disciplinary or administrative holds without modifying or corrupting official registrar enrollment (`applications.status = 'enrolled'`).
7. **Audit Trail Review**: Audit all administrative mutations and reconciliation actions via append-only logging in `activity_logs`.

### What LMS Admin CANNOT Do (Domain Invariants)
1. **NO Duplicate Enrollment Tables**: LMS Admin does NOT create, maintain, or query an `lms_enrollments` table. Student course rosters are dynamically derived from official `college_enrollments` and `shs_enrollments`.
2. **NO Subject Creation**: LMS Admin cannot create or modify academic subjects. Subjects are strictly owned by Registrar in `subjects`.
3. **NO Section Creation**: LMS Admin cannot create academic block sections. Sections are strictly owned by Scheduler in `college_sections` and `shs_sections`.
4. **NO Timetable Building**: LMS Admin cannot assign days, times, or physical classrooms. Timetable logistics belong strictly to Scheduler.
5. **NO Official Grade Conferral**: LMS Admin grades are formative classroom marks. Official transcripts and graduation audits belong strictly to Registrar.
6. **NO Tuition / Payment Processing**: LMS Admin cannot assess fees or record payments. Financial operations belong strictly to Cashier / Finance.

---

## 6. Complete Authorization Matrix

| User Role | Base Permissions | Access to LMS Admin? | Rejection Mechanism |
| :--- | :--- | :---: | :--- |
| **`superadmin`** | `*` | **YES** | N/A (Full access) |
| **`admin`** | `students.*`, `programs.*`, `curriculum.*`, `enrollment.finalize`, `lms.manage`, `lms.courses.manage` | **YES** | N/A (Administrative access) |
| **`scheduler`** | `sections.manage`, `shs_sections.manage`, `college_sections.manage`, `schedules.manage` | **NO** | **HTTP 403** via `enforceAdminAccess()` |
| **`cashier`** | `fees.manage`, `assessments.generate`, `payments.record`, `receipts.print` | **NO** | **HTTP 403** via `enforceAdminAccess()` |
| **`admissions`** | `applications.view_queue`, `applications.review`, `documents.verify` | **NO** | **HTTP 403** via `enforceAdminAccess()` |
| **`scholarship`** | `scholarships.manage`, `scholarship_applications.review` | **NO** | **HTTP 403** via `enforceAdminAccess()` |
| **`clinic`** | `medical.review` | **NO** | **HTTP 403** via `enforceAdminAccess()` |
| **`faculty`** | `lms_faculty`, `manage_courses`, `grade_assignments` | **NO** | **HTTP 403** via `RoleMiddleware` & `enforceAdminAccess()` |
| **`student`** | N/A | **NO** | **HTTP 403** via `RoleMiddleware` & `enforceAdminAccess()` |
| **`applicant`** | N/A | **NO** | **HTTP 403** via `RoleMiddleware` & `enforceAdminAccess()` |
| **Unauthenticated** | N/A | **NO** | Redirect to login / **HTTP 403** |
| **Custom Staff** *(with `lms.manage`)* | Any + `["lms.manage"]` | **YES** | N/A (Authorized via custom permission) |

---

## 7. Implementation Reference

- Controller Gate: [`App\Controllers\Admin\LmsAdminController::enforceAdminAccess()`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php)
- Helper Functions: [`hasPermission()`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php#L672) & [`requirePermission()`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php#L715)
- Role Definitions: `ROLE_PERMISSIONS` in [`functions.php`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php#L642) and [`RoleMiddleware.php`](file:///c:/xampp/htdocs/sia/app/Middleware/RoleMiddleware.php#L12)
- Automated Verification: [`scripts/test_lms_admin_rbac_hardening.php`](file:///c:/xampp/htdocs/sia/scripts/test_lms_admin_rbac_hardening.php)
